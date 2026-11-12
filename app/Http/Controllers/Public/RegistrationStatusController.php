<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Edition\Enums\TermsType;
use App\Domain\Edition\Models\TermsVersion;
use App\Domain\Intake\Actions\RequestPickup;
use App\Domain\Intake\Actions\ScheduleDelivery;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Intake\Services\PlanningOptions;
use App\Domain\Participants\Actions\AcceptTermsForEntry;
use App\Domain\Participants\Actions\ApplyRate;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

/**
 * Status van de aanmelding na de checkout of na de aanmelding met kortingsactie; een geslaagde betaling landt
 * eerst op de eigen bedankpagina (meetbare URL, besluit Raphael 9 nov 2026). Na de betaling
 * kiest de bakker hier direct een aanlevermoment of ophaalvrijdag, zonder eerst in te loggen (besluit Raphael
 * 2 nov 2026); de link is uniek per order (ULID) en staat ook in de herinneringsmail.
 */
class RegistrationStatusController extends Controller
{
    public const string PAYMENT_UNAVAILABLE = 'Het betaalsysteem is op dit moment niet bereikbaar. Uw aanmelding is opgeslagen en uw plek blijft gereserveerd. Probeer het over een paar minuten opnieuw; lukt het dan nog niet, mail ons.';

    public function show(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid, PlanningOptions $options): View
    {
        abort_unless($order->orderable instanceof Entry, 404);

        $this->syncPayment($order, $gateway, $markPaid);

        return $this->statusView($order, $options);
    }

    /**
     * Terugkeer-URL van de checkout. Betaald: door naar de bedankpagina, de vaste URL waarop de conversie wordt
     * gemeten. Nog open of mislukt: naar de status (die bij "wordt verwerkt" hierheen terug ververst).
     */
    public function returned(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): RedirectResponse
    {
        abort_unless($order->orderable instanceof Entry, 404);

        $this->syncPayment($order, $gateway, $markPaid);

        return $order->refresh()->isPaid()
            ? redirect()->route('aanmelden.bedankt', $order)
            : redirect()->route('aanmelden.status', $order);
    }

    /**
     * Bedankpagina na een geslaagde betaling. Alleen bereikbaar via de terugkeer-URL van de checkout, zodat een
     * bezoek hier gelijkstaat aan een nieuwe betaalde aanmelding; herinneringsmails en het plannen van een
     * aanlevermoment lopen via de statuspagina.
     */
    public function thanks(Order $order, PlanningOptions $options): View|RedirectResponse
    {
        abort_unless($order->orderable instanceof Entry, 404);

        if (! $order->isPaid()) {
            return redirect()->route('aanmelden.status', $order);
        }

        return $this->statusView($order, $options, isThankYouPage: true);
    }

    public function retry(Request $request, Order $order, PaymentGateway $gateway, AcceptTermsForEntry $acceptTerms): RedirectResponse
    {
        $entry = $order->orderable;
        abort_unless($entry instanceof Entry, 404);

        if (! $order->isPending() || ! $entry->canPay()) {
            return redirect()->route('aanmelden.status', $order);
        }

        // Klaargezet door de organisatie (betaallink): de klant gaat hier zelf akkoord, vóór de betaling.
        if ($entry->terms_acceptance_id === null) {
            $request->validate([
                'akkoord_voorwaarden' => ['accepted'],
                'akkoord_privacy' => ['accepted'],
            ], [
                'akkoord_voorwaarden.accepted' => 'Ga akkoord met de deelnamevoorwaarden om te kunnen betalen.',
                'akkoord_privacy.accepted' => 'Bevestig dat u de privacyverklaring heeft gelezen.',
            ]);

            $acceptTerms($entry, $request->ip(), $request->userAgent());
        }

        return $this->toCheckout($order, $gateway);
    }

    /**
     * Kortingsactie overslaan: de bakker betaalt de standaardprijs en rondt direct af.
     */
    public function payStandard(Order $order, PaymentGateway $gateway, ApplyRate $applyRate): RedirectResponse
    {
        $entry = $order->orderable;
        abort_unless($entry instanceof Entry, 404);

        if (! $order->isPending() || ! $entry->isPendingPayment() || $entry->isReservationExpired()) {
            return redirect()->route('aanmelden.status', $order);
        }

        $applyRate($entry, EntryRate::Standard);

        return $this->toCheckout($order->refresh(), $gateway);
    }

    /**
     * Aanlevermoment kiezen vanaf de bedankpagina; zelfde regels als in het portaal (ScheduleDelivery).
     */
    public function plan(Request $request, Order $order, ScheduleDelivery $schedule): RedirectResponse
    {
        $entry = $this->paidEntry($order);
        $data = $request->validate(['slot' => ['required', 'integer']], [], ['slot' => 'aanlevermoment']);
        $slot = DeliverySlot::query()->where('edition_id', $entry->edition_id)->find($data['slot']);

        if ($slot === null || $slot->starts_at->isPast()) {
            return back()->withErrors(['slot' => 'Dit aanlevermoment is niet (meer) te kiezen. Kies een ander moment.']);
        }

        try {
            $entry = $schedule($entry, $slot);
        } catch (SlotFullException) {
            return back()->withErrors(['slot' => 'Dit aanlevermoment is inmiddels vol. Kies een ander moment.']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['slot' => $exception->getMessage()]);
        }

        foreach ($entry->company->owners() as $owner) {
            $owner->notify(new DeliveryScheduledNotification($entry));
        }

        return redirect()->route('aanmelden.status', $order)->with('gepland', 'Uw aanlevermoment is vastgelegd. U ontvangt een bevestiging per e-mail.');
    }

    /**
     * Ophaalvrijdag kiezen vanaf de bedankpagina, als de voorrijkosten al bij de aanmelding zijn betaald.
     */
    public function pickup(Request $request, Order $order, RequestPickup $requestPickup): RedirectResponse
    {
        $entry = $this->paidEntry($order);
        abort_unless($entry->pickup_prepaid, 404);

        $data = $request->validate([
            'pickup_day' => ['required', 'integer'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'street' => ['required', 'string', 'max:120'],
            'house_number' => ['required', 'string', 'max:16'],
            'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'city' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'pickup_day' => 'vrijdag',
            'contact_name' => 'contactpersoon',
            'phone' => 'telefoon',
            'street' => 'straat',
            'house_number' => 'huisnummer',
            'postcode' => 'postcode',
            'city' => 'plaats',
            'instructions' => 'instructies',
        ]);

        $day = PickupDay::query()->where('edition_id', $entry->edition_id)->find($data['pickup_day']);
        $owner = $entry->company->owners()->first();

        if ($day === null || $owner === null) {
            return back()->withInput()->withErrors(['pickup_day' => 'Deze vrijdag is niet (meer) te kiezen. Kies een andere vrijdag.']);
        }

        try {
            $requestPickup($entry, $day, [...$data, 'company_name' => $entry->company->name], $owner);
        } catch (SlotFullException) {
            return back()->withInput()->withErrors(['pickup_day' => 'Deze vrijdag is inmiddels vol. Kies een andere vrijdag.']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['pickup_day' => $exception->getMessage()]);
        }

        return redirect()->route('aanmelden.status', $order)->with('gepland', 'Uw ophaalmoment is vastgelegd. U ontvangt een bevestiging per e-mail.');
    }

    /**
     * Haalt de status van een lopende betaling op bij de provider en zet de order op betaald als dat zo is,
     * voor het geval de webhook nog niet binnen is.
     */
    private function syncPayment(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): void
    {
        $payment = $order->latestPayment;

        if ($payment !== null && ! $order->isPaid() && ! $payment->status->isFinal() && $payment->provider === $gateway->name()) {
            try {
                $payment = $gateway->refresh($payment);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($payment?->isPaid() && ! $order->isPaid()) {
            $markPaid($order);
        }
    }

    private function statusView(Order $order, PlanningOptions $options, bool $isThankYouPage = false): View
    {
        $order->refresh()->load(['orderable.company.primaryLocation', 'orderable.province', 'orderable.edition', 'orderable.deliverySlot.testLocation', 'orderable.pickupRequest.day', 'latestPayment', 'lines']);
        $entry = $order->orderable;
        $needsPlanning = $order->isPaid() && $entry->needsPlanning();
        $needsAcceptance = ! $order->isPaid() && $entry->terms_acceptance_id === null;

        return view('public.aanmelden.status', [
            'order' => $order,
            'entry' => $entry,
            'payment' => $order->latestPayment,
            'isThankYouPage' => $isThankYouPage,
            'canRetry' => $order->isPending() && $entry->canPay(),
            'discountPending' => $order->isPending() && $entry->wantsDiscount() && (bool) $entry->discount_status?->blocksPayment() && ! $entry->isReservationExpired(),
            'needsPlanning' => $needsPlanning,
            // Betaallink van de organisatie: overzicht tonen en de klant zelf akkoord laten geven.
            'needsAcceptance' => $needsAcceptance,
            'terms' => $needsAcceptance ? TermsVersion::latestPublished(TermsType::Participation) : null,
            'slots' => $needsPlanning && ! $entry->pickup_prepaid ? $options->slots($entry->edition) : collect(),
            'pickupDays' => $needsPlanning && $entry->pickup_prepaid ? $options->pickupDays($entry->edition) : collect(),
        ]);
    }

    private function paidEntry(Order $order): Entry
    {
        $entry = $order->orderable;
        abort_unless($entry instanceof Entry && $order->isPaid(), 404);

        return $entry;
    }

    /**
     * Naar de betaalpagina; lukt het aanmaken niet (bijvoorbeeld Stripe onbereikbaar of verkeerd ingesteld),
     * dan terug naar de status met een melding in plaats van een serverfout. De fout gaat naar de log.
     */
    private function toCheckout(Order $order, PaymentGateway $gateway): RedirectResponse
    {
        try {
            return redirect()->away($this->checkout($order, $gateway));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('aanmelden.status', $order)->with('betaalfout', self::PAYMENT_UNAVAILABLE);
        }
    }

    private function checkout(Order $order, PaymentGateway $gateway): string
    {
        return $gateway->create(
            $order,
            "De Gouden Bol {$order->edition->year} – deelname {$order->orderable->public_name}",
            route('aanmelden.terug', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        )->checkout_url;
    }
}

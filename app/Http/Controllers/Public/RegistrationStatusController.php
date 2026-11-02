<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Intake\Actions\RequestPickup;
use App\Domain\Intake\Actions\ScheduleDelivery;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Notifications\DeliveryScheduledNotification;
use App\Domain\Intake\Services\PlanningOptions;
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
 * Pagina waar de deelnemer na de checkout terugkomt, of na de aanmelding met kortingsactie. Na de betaling
 * kiest de bakker hier direct een aanlevermoment of ophaalvrijdag, zonder eerst in te loggen (besluit Raphael
 * 2 nov 2026); de link is uniek per order (ULID) en staat ook in de herinneringsmail.
 */
class RegistrationStatusController extends Controller
{
    public const string PAYMENT_UNAVAILABLE = 'Het betaalsysteem is op dit moment niet bereikbaar. Uw aanmelding is opgeslagen en uw plek blijft gereserveerd. Probeer het over een paar minuten opnieuw; lukt het dan nog niet, mail ons.';

    public function show(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid, PlanningOptions $options): View
    {
        abort_unless($order->orderable instanceof Entry, 404);

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

        $order->refresh()->load(['orderable.company.primaryLocation', 'orderable.province', 'orderable.edition', 'orderable.deliverySlot.testLocation', 'orderable.pickupRequest.day', 'latestPayment']);
        $entry = $order->orderable;
        $needsPlanning = $order->isPaid() && $entry->needsPlanning();

        return view('public.aanmelden.status', [
            'order' => $order,
            'entry' => $entry,
            'payment' => $order->latestPayment,
            'canRetry' => $order->isPending() && $entry->canPay(),
            'discountPending' => $order->isPending() && $entry->wantsDiscount() && (bool) $entry->discount_status?->blocksPayment() && ! $entry->isReservationExpired(),
            'needsPlanning' => $needsPlanning,
            'slots' => $needsPlanning && ! $entry->pickup_prepaid ? $options->slots($entry->edition) : collect(),
            'pickupDays' => $needsPlanning && $entry->pickup_prepaid ? $options->pickupDays($entry->edition) : collect(),
        ]);
    }

    public function retry(Order $order, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($order->orderable instanceof Entry, 404);

        if (! $order->isPending() || ! $order->orderable->canPay()) {
            return redirect()->route('aanmelden.status', $order);
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
            route('aanmelden.status', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        )->checkout_url;
    }
}

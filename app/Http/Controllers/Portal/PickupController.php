<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Actions\RequestPickup;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Ophaalservice in het portaal (briefing 29 okt §5): vrijdag kiezen, adres en contact invullen,
 * voorrijkosten betalen via Stripe. Pas na betaling is de ophaalservice actief.
 */
class PickupController extends Controller
{
    public function store(Request $request, CompanyAccess $access, RequestPickup $requestPickup, PaymentGateway $gateway, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner, CompanyUserRole::Staff]);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;

        abort_if($entry === null, 404);

        $data = $request->validate([
            'pickup_day' => ['required', 'integer', 'exists:pickup_days,id'],
            'company_name' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'street' => ['required', 'string', 'max:120'],
            'house_number' => ['required', 'string', 'max:16'],
            'postcode' => ['required', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'city' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'pickup_day' => 'vrijdag',
            'company_name' => 'bedrijf',
            'contact_name' => 'contactpersoon',
            'phone' => 'telefoon',
            'street' => 'straat',
            'house_number' => 'huisnummer',
            'postcode' => 'postcode',
            'city' => 'plaats',
            'instructions' => 'instructies',
        ]);

        try {
            $pickup = $requestPickup($entry, PickupDay::query()->findOrFail($data['pickup_day']), $data, $request->user('participant'));
        } catch (SlotFullException) {
            return back()->withInput()->withErrors(['pickup_day' => 'Deze vrijdag is inmiddels vol. Kies een andere vrijdag.']);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['pickup_day' => $exception->getMessage()]);
        }

        if ($pickup->isPaid()) {
            return redirect()->route('portaal.planning', $company)->with('status', 'Uw ophaalmoment is gewijzigd naar '.$pickup->day->label().'.');
        }

        return redirect()->away($this->checkout($pickup, $gateway));
    }

    public function pay(Request $request, CompanyAccess $access, PaymentGateway $gateway, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner, CompanyUserRole::Staff]);
        $edition = Edition::current();
        $pickup = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first()?->pickupRequest : null;

        if ($pickup === null || $pickup->status !== PickupStatus::Requested || ! $pickup->order?->isPending()) {
            return redirect()->route('portaal.planning', $company);
        }

        return redirect()->away($this->checkout($pickup, $gateway));
    }

    /**
     * Terugkeer na de checkout: status bij de betaalprovider ophalen en verwerken (de webhook doet hetzelfde).
     */
    public function return(Request $request, Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): RedirectResponse
    {
        $pickup = $order->orderable;
        abort_unless($pickup instanceof PickupRequest, 404);

        $company = $pickup->entry->company;
        abort_unless($request->user('participant')->companies()->whereKey($company->getKey())->exists(), 403);

        $payment = $order->latestPayment;

        if ($payment !== null && ! $order->isPaid() && ! $payment->status->isFinal() && $payment->provider === $gateway->name()) {
            $payment = $gateway->refresh($payment);
        }

        if ($payment?->isPaid() && ! $order->isPaid()) {
            $markPaid($order);
        }

        return $order->refresh()->isPaid()
            ? redirect()->route('portaal.planning', $company)->with('status', 'Betaald. De ophaalservice op '.$pickup->day->label().' is bevestigd.')
            : redirect()->route('portaal.planning', $company)->withErrors(['pickup_day' => 'De betaling is nog niet gelukt. Uw aanvraag staat klaar; probeer het opnieuw.']);
    }

    private function checkout(PickupRequest $pickup, PaymentGateway $gateway): string
    {
        $order = $pickup->order;

        return $gateway->create(
            $order,
            "Ophaalservice De Gouden Bol {$order->edition->year} – {$pickup->company_name}",
            route('portaal.ophalen.terug', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        )->checkout_url;
    }
}

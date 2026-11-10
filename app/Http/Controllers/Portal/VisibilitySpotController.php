<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Actions\OrderVisibilitySpot;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Exceptions\VisibilitySpotTakenException;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Services\CompanyAccess;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

/**
 * Extra zichtbaarheid kopen vanuit het portaal: plek kiezen en direct afrekenen. Staat los van de test.
 */
class VisibilitySpotController extends Controller
{
    public function store(Request $request, CompanyAccess $access, OrderVisibilitySpot $orderSpot, PaymentGateway $gateway, string $bedrijf): RedirectResponse
    {
        $company = $access->resolve($request->user('participant'), $bedrijf, [CompanyUserRole::Owner]);
        $edition = Edition::current();
        $entry = $edition ? $company->entries()->where('edition_id', $edition->getKey())->first() : null;

        abort_unless($entry !== null && $entry->order?->isPaid(), 404);

        $data = $request->validate(['position' => ['required', 'integer']], [], ['position' => 'plek']);

        try {
            $order = $orderSpot($entry, (int) $data['position'], $request->user('participant'));
        } catch (VisibilitySpotTakenException|InvalidArgumentException $exception) {
            return back()->withErrors(['position' => $exception->getMessage()]);
        }

        try {
            $payment = $gateway->create(
                $order,
                "Extra zichtbaarheid De Gouden Bol {$edition->year} – {$entry->public_name}",
                route('portaal.zichtbaarheid.terug', $order),
                config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['position' => 'Het betaalsysteem is op dit moment niet bereikbaar. Probeer het over een paar minuten opnieuw.']);
        }

        return redirect()->away($payment->checkout_url);
    }

    /**
     * Terugkeer na de checkout: status bij de betaalprovider ophalen en verwerken (de webhook doet hetzelfde).
     */
    public function return(Request $request, Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): RedirectResponse
    {
        $spot = $order->orderable;
        abort_unless($spot instanceof VisibilitySpot, 404);

        $company = $spot->company;
        abort_unless($request->user('participant')->companies()->whereKey($company->getKey())->exists(), 403);

        $payment = $order->latestPayment;

        if ($payment !== null && ! $order->isPaid() && ! $payment->status->isFinal() && $payment->provider === $gateway->name()) {
            $payment = $gateway->refresh($payment);
        }

        if ($payment?->isPaid() && ! $order->isPaid()) {
            $markPaid($order);
        }

        return $order->refresh()->isPaid()
            ? redirect()->route('portaal.aanmelding', $company)->with('status', $spot->starts_at?->isFuture() ? "Betaald. Plek {$spot->position} in {$spot->province->name} staat vast en gaat in bij de bekendmaking." : "Betaald. U staat uitgelicht op plek {$spot->position} in {$spot->province->name}.")
            : redirect()->route('portaal.aanmelding', $company)->withErrors(['position' => 'De betaling is nog niet gelukt. Kies opnieuw een plek om het nog eens te proberen.']);
    }
}

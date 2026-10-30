<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Participants\Actions\ApplyRate;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Pagina waar de deelnemer na de checkout terugkomt, of na de aanmelding met kortingsactie.
 */
class RegistrationStatusController extends Controller
{
    public function show(Order $order, PaymentGateway $gateway, MarkOrderPaid $markPaid): View
    {
        abort_unless($order->orderable instanceof Entry, 404);

        $payment = $order->latestPayment;

        if ($payment !== null && ! $order->isPaid() && ! $payment->status->isFinal() && $payment->provider === $gateway->name()) {
            $payment = $gateway->refresh($payment);
        }

        if ($payment?->isPaid() && ! $order->isPaid()) {
            $markPaid($order);
        }

        $order->refresh()->load(['orderable.company', 'orderable.province', 'orderable.edition', 'latestPayment']);
        $entry = $order->orderable;

        return view('public.aanmelden.status', [
            'order' => $order,
            'entry' => $entry,
            'payment' => $order->latestPayment,
            'canRetry' => $order->isPending() && $entry->canPay(),
            'discountPending' => $order->isPending() && $entry->wantsDiscount() && (bool) $entry->discount_status?->blocksPayment() && ! $entry->isReservationExpired(),
        ]);
    }

    public function retry(Order $order, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($order->orderable instanceof Entry, 404);

        if (! $order->isPending() || ! $order->orderable->canPay()) {
            return redirect()->route('aanmelden.status', $order);
        }

        return redirect()->away($this->checkout($order, $gateway));
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

        return redirect()->away($this->checkout($order->refresh(), $gateway));
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

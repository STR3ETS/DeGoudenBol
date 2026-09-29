<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Order;
use App\Domain\Participants\Models\Entry;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Pagina waar de deelnemer na de checkout terugkomt.
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

        $order->refresh()->load(['orderable.company', 'orderable.province', 'latestPayment']);

        return view('public.aanmelden.status', [
            'order' => $order,
            'entry' => $order->orderable,
            'payment' => $order->latestPayment,
            'canRetry' => $order->isPending() && ! $order->orderable->isReservationExpired(),
        ]);
    }

    public function retry(Order $order, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($order->orderable instanceof Entry, 404);

        if (! $order->isPending() || $order->orderable->isReservationExpired()) {
            return redirect()->route('aanmelden.status', $order);
        }

        $payment = $gateway->create(
            $order,
            "De Gouden Bol {$order->edition->year} – deelname {$order->orderable->public_name}",
            route('aanmelden.status', $order),
            config('commerce.payment_driver') === 'mollie' ? route('webhooks.mollie') : null,
        );

        return redirect()->away($payment->checkout_url);
    }
}

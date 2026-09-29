<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Gateways\FakePaymentGateway;
use App\Domain\Commerce\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Nep-checkout voor lokale ontwikkeling (PAYMENT_DRIVER=fake). Bestaat niet met Mollie.
 */
class FakeCheckoutController extends Controller
{
    public function show(Payment $payment): View
    {
        abort_unless(config('commerce.payment_driver') === 'fake' && $payment->provider === 'fake', 404);

        return view('public.betaling.fake', ['payment' => $payment->load('order.lines')]);
    }

    public function settle(Request $request, Payment $payment, FakePaymentGateway $gateway, MarkOrderPaid $markPaid): RedirectResponse
    {
        abort_unless(config('commerce.payment_driver') === 'fake' && $payment->provider === 'fake', 404);

        $status = PaymentStatus::from($request->validate([
            'status' => ['required', 'in:paid,failed,canceled,expired'],
        ])['status']);

        $gateway->settle($payment, $status);

        if ($status === PaymentStatus::Paid) {
            $markPaid($payment->order);
        }

        return redirect()->to($payment->raw['redirect_url'] ?? route('home'));
    }
}

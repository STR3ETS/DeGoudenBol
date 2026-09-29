<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Mollie stuurt alleen het betaal-id; de status halen we zelf op bij Mollie.
 */
class MollieWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, MarkOrderPaid $markPaid): Response
    {
        $providerId = (string) $request->input('id', '');

        $payment = Payment::query()->where('provider', 'mollie')->where('provider_id', $providerId)->first();

        if ($payment === null) {
            return response('', 200);
        }

        $payment = $gateway->refresh($payment);

        if ($payment->isPaid()) {
            $markPaid($payment->order);
        }

        return response('', 200);
    }
}

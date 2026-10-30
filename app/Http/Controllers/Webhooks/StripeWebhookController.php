<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Commerce\Actions\MarkOrderPaid;
use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Stripe meldt een wijziging aan een Checkout Session. We controleren de handtekening
 * (Stripe-Signature, geheim STRIPE_WEBHOOK_SECRET) en halen de status daarna zelf op bij Stripe.
 */
class StripeWebhookController extends Controller
{
    private const array EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    /** Maximale leeftijd van een gebeurtenis, tegen het opnieuw afspelen van een oud verzoek. */
    private const int TOLERANCE_SECONDS = 300;

    public function __invoke(Request $request, PaymentGateway $gateway, MarkOrderPaid $markPaid): Response
    {
        $payload = $request->getContent();

        if (! self::signatureIsValid($payload, (string) $request->header('Stripe-Signature'), (string) config('services.stripe.webhook_secret'))) {
            return response('Ongeldige handtekening', 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event) || ! in_array($event['type'] ?? null, self::EVENTS, true)) {
            return response('', 200);
        }

        $sessionId = (string) ($event['data']['object']['id'] ?? '');
        $payment = Payment::query()->where('provider', 'stripe')->where('provider_id', $sessionId)->first();

        if ($payment === null) {
            return response('', 200);
        }

        $payment = $gateway->refresh($payment);

        if ($payment->isPaid() && ! $payment->order->isPaid()) {
            $markPaid($payment->order);
        }

        return response('', 200);
    }

    /**
     * Stripe-Signature: "t=1700000000,v1=hexdigest[,v1=...]"; de handtekening is HMAC-SHA256 over "t.payload".
     */
    public static function signatureIsValid(string $payload, string $header, string $secret, ?int $now = null): bool
    {
        if ($secret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === [] || abs(($now ?? time()) - $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}

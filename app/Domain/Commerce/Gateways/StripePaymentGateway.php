<?php

namespace App\Domain\Commerce\Gateways;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe Checkout: de bakker rekent af op de betaalpagina van Stripe (iDEAL, kaart, Bancontact;
 * welke methoden verschijnen, stel je in het Stripe-dashboard in). Eén Checkout Session per betaalpoging.
 *
 * De status komt binnen via de webhook (/webhooks/stripe) en bij terugkeer op de statuspagina, en wordt
 * altijd opnieuw bij Stripe opgehaald; de inhoud van een webhook vertrouwen we nooit.
 * Bewust zonder Stripe-SDK: twee REST-aanroepen via de HTTP-client van Laravel.
 */
final class StripePaymentGateway implements PaymentGateway
{
    private const string API = 'https://api.stripe.com/v1';

    /** Stripe accepteert een vervaltijd tussen 30 minuten en 24 uur. */
    private const int MIN_EXPIRY_MINUTES = 31;

    private const int MAX_EXPIRY_MINUTES = 1440;

    public function create(Order $order, string $description, string $redirectUrl, ?string $webhookUrl = null): Payment
    {
        $expiresIn = min(self::MAX_EXPIRY_MINUTES, max(self::MIN_EXPIRY_MINUTES, (int) config('commerce.reservation_minutes', 60)));

        $session = $this->request()->asForm()->post(self::API.'/checkout/sessions', array_filter([
            'mode' => 'payment',
            'locale' => 'nl',
            'success_url' => $redirectUrl,
            'cancel_url' => $redirectUrl,
            'client_reference_id' => $order->ulid,
            'expires_at' => now()->addMinutes($expiresIn)->getTimestamp(),
            'customer_email' => $this->customerEmail($order),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => $order->total_cents,
                    'product_data' => ['name' => $description],
                ],
            ]],
            'metadata' => ['order' => $order->number, 'ulid' => $order->ulid],
            'payment_intent_data' => ['description' => $description, 'metadata' => ['order' => $order->number]],
        ]))->throw()->json();

        return $order->payments()->create([
            'provider' => $this->name(),
            'provider_id' => $session['id'],
            'status' => PaymentStatus::Open,
            'amount_cents' => $order->total_cents,
            'checkout_url' => $session['url'],
            'raw' => ['status' => $session['status'] ?? null, 'payment_status' => $session['payment_status'] ?? null],
        ]);
    }

    public function refresh(Payment $payment): Payment
    {
        $session = $this->request()
            ->get(self::API.'/checkout/sessions/'.$payment->provider_id, ['expand' => ['payment_intent.latest_charge']])
            ->throw()
            ->json();

        $status = self::statusFor((string) ($session['status'] ?? ''), (string) ($session['payment_status'] ?? ''));
        $charge = $session['payment_intent']['latest_charge'] ?? null;
        $paidAt = is_array($charge) && isset($charge['created']) ? CarbonImmutable::createFromTimestamp((int) $charge['created']) : now()->toImmutable();

        $payment->forceFill([
            'status' => $status,
            'method' => is_array($charge) ? ($charge['payment_method_details']['type'] ?? null) : $payment->method,
            'paid_at' => $status === PaymentStatus::Paid ? ($payment->paid_at ?? $paidAt) : null,
            'raw' => ['status' => $session['status'] ?? null, 'payment_status' => $session['payment_status'] ?? null],
        ])->save();

        return $payment;
    }

    public function name(): string
    {
        return 'stripe';
    }

    /**
     * Vertaalt de Checkout Session naar onze status. "complete" met "unpaid" betekent een
     * betaalmethode die later bevestigt (bijvoorbeeld SEPA-incasso): dan is de betaling onderweg.
     */
    public static function statusFor(string $sessionStatus, string $paymentStatus): PaymentStatus
    {
        return match (true) {
            in_array($paymentStatus, ['paid', 'no_payment_required'], true) => PaymentStatus::Paid,
            $sessionStatus === 'expired' => PaymentStatus::Expired,
            $sessionStatus === 'complete' => PaymentStatus::Pending,
            default => PaymentStatus::Open,
        };
    }

    private function request(): PendingRequest
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new RuntimeException('STRIPE_SECRET ontbreekt in .env; zonder sleutel kan er niet betaald worden.');
        }

        return Http::withToken($secret)->acceptJson()->timeout(15)->retry(2, 250, throw: false);
    }

    private function customerEmail(Order $order): ?string
    {
        $email = $order->metadata['email'] ?? null;

        if (! is_string($email) && method_exists($order->orderable, 'company')) {
            $email = $order->orderable?->company?->users()->orderBy('company_user.created_at')->value('email');
        }

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}

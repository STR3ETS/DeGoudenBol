<?php

namespace App\Domain\Commerce\Gateways;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Payment;
use Illuminate\Support\Str;

/**
 * Lokale betaalsimulatie: de checkout-pagina laat je zelf kiezen of de betaling slaagt.
 * Alleen voor ontwikkeling en tests; nooit op productie (PAYMENT_DRIVER=mollie).
 */
final class FakePaymentGateway implements PaymentGateway
{
    public function create(Order $order, string $description, string $redirectUrl, ?string $webhookUrl = null): Payment
    {
        $payment = $order->payments()->create([
            'provider' => $this->name(),
            'provider_id' => 'fake_'.Str::lower(Str::ulid()),
            'status' => PaymentStatus::Open,
            'amount_cents' => $order->total_cents,
            'raw' => ['description' => $description, 'redirect_url' => $redirectUrl, 'webhook_url' => $webhookUrl],
        ]);

        $payment->forceFill(['checkout_url' => route('betaling.fake', $payment)])->save();

        return $payment;
    }

    public function refresh(Payment $payment): Payment
    {
        return $payment;
    }

    public function name(): string
    {
        return 'fake';
    }

    /**
     * Wordt door de nep-checkoutpagina aangeroepen.
     */
    public function settle(Payment $payment, PaymentStatus $status): Payment
    {
        $payment->forceFill([
            'status' => $status,
            'method' => 'ideal',
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ])->save();

        return $payment;
    }
}

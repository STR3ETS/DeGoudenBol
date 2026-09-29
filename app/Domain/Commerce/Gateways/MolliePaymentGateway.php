<?php

namespace App\Domain\Commerce\Gateways;

use App\Domain\Commerce\Contracts\PaymentGateway;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Payment;
use Carbon\CarbonImmutable;
use Mollie\Laravel\Facades\Mollie;

/**
 * Mollie: iDEAL en creditcard. De status komt binnen via de webhook (/webhooks/mollie)
 * en wordt altijd opnieuw bij Mollie opgehaald, nooit uit het verzoek vertrouwd.
 */
final class MolliePaymentGateway implements PaymentGateway
{
    public function create(Order $order, string $description, string $redirectUrl, ?string $webhookUrl = null): Payment
    {
        $molliePayment = Mollie::api()->payments->create(array_filter([
            'amount' => [
                'currency' => $order->currency,
                'value' => number_format($order->total_cents / 100, 2, '.', ''),
            ],
            'description' => $description,
            'redirectUrl' => $redirectUrl,
            'webhookUrl' => $webhookUrl,
            'metadata' => ['order' => $order->number, 'ulid' => $order->ulid],
            'locale' => 'nl_NL',
        ]));

        return $order->payments()->create([
            'provider' => $this->name(),
            'provider_id' => $molliePayment->id,
            'status' => PaymentStatus::from($molliePayment->status),
            'amount_cents' => $order->total_cents,
            'checkout_url' => $molliePayment->getCheckoutUrl(),
            'raw' => ['status' => $molliePayment->status],
        ]);
    }

    public function refresh(Payment $payment): Payment
    {
        $molliePayment = Mollie::api()->payments->get($payment->provider_id);

        $payment->forceFill([
            'status' => PaymentStatus::from($molliePayment->status),
            'method' => $molliePayment->method,
            'paid_at' => $molliePayment->paidAt ? CarbonImmutable::parse($molliePayment->paidAt) : null,
            'raw' => ['status' => $molliePayment->status, 'method' => $molliePayment->method],
        ])->save();

        return $payment;
    }

    public function name(): string
    {
        return 'mollie';
    }
}

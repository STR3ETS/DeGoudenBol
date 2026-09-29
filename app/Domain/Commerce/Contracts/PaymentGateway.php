<?php

namespace App\Domain\Commerce\Contracts;

use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Payment;

interface PaymentGateway
{
    /**
     * Maakt een betaling aan bij de provider en slaat hem op met een checkout-URL.
     */
    public function create(Order $order, string $description, string $redirectUrl, ?string $webhookUrl = null): Payment;

    /**
     * Haalt de actuele status op bij de provider en werkt de betaling bij.
     */
    public function refresh(Payment $payment): Payment;

    public function name(): string;
}

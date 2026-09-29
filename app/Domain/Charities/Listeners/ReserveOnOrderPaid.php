<?php

namespace App\Domain\Charities\Listeners;

use App\Domain\Charities\Actions\ReserveForCharity;
use App\Domain\Commerce\Events\OrderPaid;

final class ReserveOnOrderPaid
{
    public function __construct(private readonly ReserveForCharity $reserve) {}

    public function handle(OrderPaid $event): void
    {
        ($this->reserve)($event->order);
    }
}

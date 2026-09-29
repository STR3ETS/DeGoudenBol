<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Sponsor;

/**
 * Betaalde sponsororder → plaatsingen actief.
 */
final class ActivateSponsorPlacements
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        if (! $order->orderable instanceof Sponsor) {
            return;
        }

        Placement::query()
            ->whereIn('order_line_id', $order->lines()->pluck('id'))
            ->where('status', PlacementStatus::Draft)
            ->update(['status' => PlacementStatus::Active->value, 'updated_at' => now()]);
    }
}

<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;

/**
 * Onbetaalde reserveringen die verlopen zijn geven hun plek terug.
 */
final class CancelExpiredReservations
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(): int
    {
        $cancelled = 0;

        Entry::query()
            ->where('status', EntryStatus::PendingPayment)
            ->where('reservation_expires_at', '<=', now())
            ->with('order')
            ->each(function (Entry $entry) use (&$cancelled): void {
                $entry->forceFill(['status' => EntryStatus::Cancelled])->save();

                if ($entry->order?->isPending()) {
                    $entry->order->forceFill(['status' => OrderStatus::Expired, 'cancelled_at' => now()])->save();
                }

                $this->audit->record('entry.reservation_expired', $entry, ['ulid' => $entry->ulid], null);
                $cancelled++;
            });

        return $cancelled;
    }
}

<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Models\Recognition;
use App\Domain\Ranking\Events\ProvinceRevealed;

/**
 * Zodra een provincie onthuld is, vervalt het embargo op haar erkenningen, ook als de reveal
 * eerder plaatsvindt dan het oorspronkelijk geplande moment.
 */
final class LiftProvinceEmbargo
{
    public function handle(ProvinceRevealed $event): void
    {
        Recognition::query()
            ->where('edition_id', $event->edition->getKey())
            ->where('province_id', $event->province->getKey())
            ->where('embargo_until', '>', now())
            ->update(['embargo_until' => now()]);
    }
}

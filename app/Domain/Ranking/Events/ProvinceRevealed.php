<?php

namespace App\Domain\Ranking\Events;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Ranking\Models\RankingSnapshot;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * De definitieve Top 10 van een provincie is op de publicatiedag onthuld.
 */
class ProvinceRevealed
{
    use Dispatchable;

    public function __construct(
        public readonly Edition $edition,
        public readonly Province $province,
        public readonly RankingSnapshot $snapshot,
    ) {}
}

<?php

namespace App\Domain\Ranking\Data;

/**
 * Invoer van de rankingengine: één gepubliceerde uitslag. Geen betaalstatus, geen sponsoring.
 */
final readonly class RankedEntry
{
    /**
     * @param  array<string, float>  $criterionAverages
     */
    public function __construct(
        public int $entryId,
        public float $total,
        public array $criterionAverages,
    ) {}
}

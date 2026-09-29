<?php

namespace App\Domain\Ranking\Data;

/**
 * Uitkomst van de rankingengine: geordende posities plus de hash van de invoer.
 */
final readonly class RankingSnapshotData
{
    /**
     * @param  list<RankedPosition>  $positions
     */
    public function __construct(
        public string $engineVersion,
        public string $inputHash,
        public array $positions,
    ) {}

    /**
     * @return list<RankedPosition>
     */
    public function topList(int $length): array
    {
        return array_values(array_filter($this->positions, fn (RankedPosition $position) => $position->position <= $length));
    }

    public function needsTieBreak(): bool
    {
        foreach ($this->positions as $position) {
            if ($position->needsTieBreak) {
                return true;
            }
        }

        return false;
    }
}

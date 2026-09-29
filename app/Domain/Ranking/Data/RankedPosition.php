<?php

namespace App\Domain\Ranking\Data;

use App\Domain\Ranking\Enums\PositionLabel;

final readonly class RankedPosition
{
    public function __construct(
        public int $entryId,
        public int $position,
        public float $total,
        public ?int $tieGroup,
        public PositionLabel $label,
        public bool $needsTieBreak,
    ) {}
}

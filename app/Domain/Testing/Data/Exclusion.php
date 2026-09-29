<?php

namespace App\Domain\Testing\Data;

use App\Domain\Testing\Enums\ExclusionReason;

final readonly class Exclusion
{
    public function __construct(
        public int $panelistId,
        public int $sampleId,
        public ExclusionReason $reason,
    ) {}
}

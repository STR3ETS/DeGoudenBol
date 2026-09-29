<?php

namespace App\Domain\Testing\Data;

use App\Domain\Testing\Models\PaperEntry;
use App\Domain\Testing\Models\Scorecard;

final readonly class PaperEntryOutcome
{
    public function __construct(
        public PaperEntry $entry,
        public ?Scorecard $scorecard,
        public bool $mismatch,
    ) {}

    public function isConfirmed(): bool
    {
        return $this->scorecard !== null;
    }
}

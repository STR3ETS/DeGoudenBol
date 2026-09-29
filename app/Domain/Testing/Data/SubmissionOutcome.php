<?php

namespace App\Domain\Testing\Data;

use App\Domain\Testing\Models\Scorecard;

final readonly class SubmissionOutcome
{
    public function __construct(
        public Scorecard $scorecard,
        public bool $wasDuplicate,
    ) {}
}

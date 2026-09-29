<?php

namespace App\Domain\Testing\Data;

use Carbon\CarbonImmutable;

/**
 * Wat de panel-app (of de papieren invoer) aanlevert voor één kaart.
 */
final readonly class ScorecardSubmission
{
    /**
     * @param  array<string, int>  $scores  criteriumcode => hele punten
     */
    public function __construct(
        public string $uuid,
        public int $assignmentId,
        public array $scores,
        public ?string $strengths,
        public ?string $opportunities,
        public CarbonImmutable $submittedAt,
    ) {}
}

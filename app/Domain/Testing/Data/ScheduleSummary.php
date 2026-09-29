<?php

namespace App\Domain\Testing\Data;

/**
 * Wat het genereren van een uitserveerschema opleverde.
 *
 * @param  list<string>  $warnings
 */
final readonly class ScheduleSummary
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int $assignments,
        public int $exclusions,
        public int $panelists,
        public int $samples,
        public array $warnings = [],
    ) {}
}

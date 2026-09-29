<?php

namespace App\Domain\Intake\Data;

use Carbon\CarbonInterface;

/**
 * Wat Ontvangst vastlegt bij het inscannen van een aanleverbewijs.
 */
final readonly class IntakeData
{
    public function __construct(
        public int $pieceCount,
        public ?float $temperatureC = null,
        public ?string $photoPath = null,
        public ?string $notes = null,
        public ?int $testLocationId = null,
        public ?CarbonInterface $receivedAt = null,
    ) {}
}

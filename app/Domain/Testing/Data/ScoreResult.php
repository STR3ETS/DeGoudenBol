<?php

namespace App\Domain\Testing\Data;

/**
 * Uitkomst van de scoreberekening (docs/04 §2): gemiddelden per onderdeel, ruw totaal,
 * afgerond cijfer en vlaggen voor scorecontrole.
 */
final readonly class ScoreResult
{
    /**
     * @param  array<string, float>  $criterionAverages
     * @param  list<string>  $outlierCardUuids
     */
    public function __construct(
        public int $cardCount,
        public int $minValidCards,
        public array $criterionAverages,
        public float $totalRaw,
        public float $total,
        public array $outlierCardUuids,
        public int $scoringModelVersion,
    ) {}

    public function meetsMinimum(): bool
    {
        return $this->cardCount >= $this->minValidCards;
    }

    /**
     * @return array<string, mixed>
     */
    public function flags(): array
    {
        return [
            'missing_cards' => ! $this->meetsMinimum(),
            'card_count' => $this->cardCount,
            'min_valid_cards' => $this->minValidCards,
            'outliers' => $this->outlierCardUuids,
        ];
    }
}

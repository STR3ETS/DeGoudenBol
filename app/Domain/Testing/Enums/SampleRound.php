<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Ronde waarin een monster getest wordt. Bepaalt het nummerformaat.
 */
enum SampleRound: string implements HasLabel
{
    case Provincial = 'provincial';
    case Final = 'final';
    case TieBreak = 'tie_break';

    public function getLabel(): string
    {
        return match ($this) {
            self::Provincial => 'Provinciale ronde',
            self::Final => 'Landelijke finale',
            self::TieBreak => 'Beslisronde',
        };
    }

    /**
     * 0001 voor de provinciale ronde, F01 voor de finale, B01 voor een beslisronde.
     */
    public function formatNumber(int $sequence): string
    {
        return match ($this) {
            self::Provincial => sprintf('%04d', $sequence),
            self::Final => sprintf('F%02d', $sequence),
            self::TieBreak => sprintf('B%02d', $sequence),
        };
    }
}

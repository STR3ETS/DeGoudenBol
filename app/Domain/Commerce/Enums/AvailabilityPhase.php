<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasLabel;

enum AvailabilityPhase: string implements HasLabel
{
    case Always = 'always';
    case FinalistsKnown = 'finalists_known';

    public function getLabel(): string
    {
        return match ($this) {
            self::Always => 'Altijd',
            self::FinalistsKnown => 'Zodra de finalisten bekend zijn',
        };
    }
}

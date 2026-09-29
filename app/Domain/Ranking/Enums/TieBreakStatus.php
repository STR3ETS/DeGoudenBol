<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TieBreakStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Decided = 'decided';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Beslissende beoordeling nodig',
            self::Decided => 'Beslist',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Decided => 'success',
        };
    }
}

<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasLabel;

enum ScorecardSource: string implements HasLabel
{
    case App = 'app';
    case Paper = 'paper';

    public function getLabel(): string
    {
        return match ($this) {
            self::App => 'Panel-app',
            self::Paper => 'Papieren kaart',
        };
    }
}

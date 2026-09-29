<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Besluit 17: wat gebeurt er als een provinciewinnaar niet aan de finale kan meedoen.
 */
enum FinalistFallback: string implements HasLabel
{
    case Drop = 'drop';
    case NextInLine = 'next_in_line';

    public function getLabel(): string
    {
        return match ($this) {
            self::Drop => 'Finaleplek vervalt',
            self::NextInLine => 'Finaleplek gaat naar nummer 2',
        };
    }
}

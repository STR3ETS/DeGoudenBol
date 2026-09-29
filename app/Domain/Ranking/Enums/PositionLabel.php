<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Beweging ten opzichte van de vorige snapshot. "Gedaald" krijgt een neutrale chip.
 */
enum PositionLabel: string implements HasLabel
{
    case New = 'new';
    case Up = 'up';
    case Down = 'down';
    case Same = 'same';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nieuw',
            self::Up => 'Gestegen',
            self::Down => 'Gedaald',
            self::Same => 'Gelijk',
        };
    }

    public function chipStatus(): ?string
    {
        return match ($this) {
            self::New => 'goud',
            self::Up => 'succes',
            self::Down => 'neutraal',
            self::Same => null,
        };
    }
}

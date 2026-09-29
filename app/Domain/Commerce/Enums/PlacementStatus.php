<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PlacementStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Gereserveerd (nog niet betaald)',
            self::Active => 'Actief',
            self::Ended => 'Afgelopen',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            self::Ended => 'gray',
            self::Cancelled => 'danger',
        };
    }

    public function blocksLocation(): bool
    {
        return in_array($this, [self::Draft, self::Active], true);
    }
}

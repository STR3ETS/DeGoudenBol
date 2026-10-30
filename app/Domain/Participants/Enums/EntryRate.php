<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Gekozen tarief (briefing 29 okt §3): standaardprijs of actieprijs na de socialmedia-actie.
 */
enum EntryRate: string implements HasColor, HasLabel
{
    case Standard = 'standard';
    case Discount = 'discount';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standaardprijs',
            self::Discount => 'Actieprijs',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Discount => 'warning',
        };
    }
}

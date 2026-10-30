<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Gekozen tarief (briefing 29 okt §3): standaardprijs, actieprijs na de socialmedia-actie, of de
 * tijdelijke openingsactie (besluit Bennie 30 okt 2026) die beide vervangt zolang hij loopt.
 */
enum EntryRate: string implements HasColor, HasLabel
{
    case Standard = 'standard';
    case Discount = 'discount';
    case OpeningOffer = 'opening_offer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standaardprijs',
            self::Discount => 'Actieprijs',
            self::OpeningOffer => 'Openingsactie',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Discount => 'warning',
            self::OpeningOffer => 'success',
        };
    }
}

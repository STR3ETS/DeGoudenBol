<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Gekozen tarief (briefing 29 okt §3): standaardprijs, actieprijs na de socialmedia-actie, of de
 * tijdelijke openingsactie (besluit Bennie 30 okt 2026) die beide vervangt zolang hij loopt. Sinds 10 nov 2026
 * is er één alles-in-één pakket (krenten, naturel en ophalen) dat alle keuzes vervangt zolang het aan staat.
 */
enum EntryRate: string implements HasColor, HasLabel
{
    case Standard = 'standard';
    case Discount = 'discount';
    case OpeningOffer = 'opening_offer';
    case Bundle = 'bundle';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standaardprijs',
            self::Discount => 'Actieprijs',
            self::OpeningOffer => 'Openingsactie',
            self::Bundle => 'Alles-in-één pakket',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Discount => 'warning',
            self::OpeningOffer, self::Bundle => 'success',
        };
    }
}

<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Kortingsactie: de deelnemer levert een link of bewijs van de socialmedia-publicatie aan; een
 * administrator keurt dat goed. Pas daarna geldt de actieprijs.
 */
enum DiscountStatus: string implements HasColor, HasLabel
{
    case AwaitingProof = 'awaiting_proof';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::AwaitingProof => 'Wacht op bewijs',
            self::Submitted => 'Te keuren',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AwaitingProof => 'gray',
            self::Submitted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Zolang de korting nog niet beslist is, kan er niet betaald worden.
     */
    public function blocksPayment(): bool
    {
        return in_array($this, [self::AwaitingProof, self::Submitted], true);
    }
}

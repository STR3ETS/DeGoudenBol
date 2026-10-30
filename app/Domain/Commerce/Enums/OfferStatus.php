<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Sponsoracties gaan eerst door redactionele goedkeuring (briefing 29 okt §10).
 */
enum OfferStatus: string implements HasColor, HasLabel
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Te beoordelen',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}

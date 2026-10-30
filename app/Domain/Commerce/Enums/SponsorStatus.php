<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Een sponsor is pas zichtbaar als hij actief is. Aanmeldingen via de website wachten op controle;
 * maatwerk en de Top 10-reservering beginnen als aanvraag (briefing 29 okt §8 en §9).
 */
enum SponsorStatus: string implements HasColor, HasLabel
{
    case Request = 'request';
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Request => 'Aanvraag',
            self::Pending => 'Wacht op controle',
            self::Active => 'Actief',
            self::Inactive => 'Inactief',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Request => 'info',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Inactive => 'gray',
        };
    }
}

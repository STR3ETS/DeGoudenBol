<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Rol van een deelnemersaccount binnen een bedrijf. Medewerkers met alleen
 * 'scanner' kunnen niets anders dan cadeaubonnen scannen.
 */
enum CompanyUserRole: string implements HasLabel
{
    case Owner = 'owner';
    case Staff = 'staff';
    case Scanner = 'scanner';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Eigenaar',
            self::Staff => 'Medewerker',
            self::Scanner => 'Alleen bonnen scannen',
        };
    }

    public function canManageCompany(): bool
    {
        return $this === self::Owner;
    }

    public function canEditProfile(): bool
    {
        return $this !== self::Scanner;
    }
}

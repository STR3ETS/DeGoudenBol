<?php

namespace App\Domain\Platform\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Medewerkersrollen uit de briefing (hoofdstuk 3). Deelnemers en sponsoren zijn geen medewerkers.
 */
enum StaffRole: string implements HasLabel
{
    case Admin = 'admin';
    case Intake = 'intake';
    case Coordinator = 'coordinator';
    case Panelist = 'panelist';
    case Reviewer = 'reviewer';
    case Publisher = 'publisher';
    case Communication = 'communication';
    case VoucherManager = 'voucher_manager';
    case Finance = 'finance';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Beheerder',
            self::Intake => 'Ontvangst & registratie',
            self::Coordinator => 'Testcoördinatie',
            self::Panelist => 'Panellid',
            self::Reviewer => 'Scorecontrole',
            self::Publisher => 'Publicatie',
            self::Communication => 'Communicatie',
            self::VoucherManager => 'Bonbeheer',
            self::Finance => 'Financiën',
        };
    }

    public function seesParticipantIdentity(): bool
    {
        return match ($this) {
            self::Admin, self::Intake, self::Publisher, self::VoucherManager, self::Finance => true,
            self::Coordinator, self::Panelist, self::Reviewer, self::Communication => false,
        };
    }

    /**
     * Harde regel 1: een panellid is nooit ook ontvangst/registratie of publicatie.
     *
     * @return list<array{0: self, 1: self}>
     */
    public static function forbiddenCombinations(): array
    {
        return [
            [self::Panelist, self::Intake],
            [self::Panelist, self::Publisher],
        ];
    }

    public function conflictsWith(self $other): bool
    {
        foreach (self::forbiddenCombinations() as [$a, $b]) {
            if (($this === $a && $other === $b) || ($this === $b && $other === $a)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}

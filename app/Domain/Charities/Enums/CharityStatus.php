<?php

namespace App\Domain\Charities\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Voorgedragen → in beoordeling → goedgekeurd / alternatief in overleg → gekoppeld → uitbetaald.
 */
enum CharityStatus: string implements HasColor, HasLabel
{
    case Nominated = 'nominated';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Alternative = 'alternative';
    case Linked = 'linked';
    case PaidOut = 'paid_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nominated => 'Voorgedragen',
            self::Reviewing => 'In beoordeling',
            self::Approved => 'Goedgekeurd',
            self::Alternative => 'Alternatief in overleg',
            self::Linked => 'Gekoppeld',
            self::PaidOut => 'Uitbetaald',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Nominated => 'gray',
            self::Reviewing => 'warning',
            self::Approved, self::Linked => 'success',
            self::Alternative => 'danger',
            self::PaidOut => 'info',
        };
    }

    /**
     * Ontvangt de 10%-reservering van zijn voordrager en staat op de openbare pagina.
     */
    public function receivesReservations(): bool
    {
        return in_array($this, [self::Approved, self::Linked, self::PaidOut], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Nominated => [self::Reviewing, self::Approved, self::Alternative],
            self::Reviewing => [self::Approved, self::Alternative],
            self::Approved => [self::Linked, self::Alternative],
            self::Alternative => [self::Reviewing, self::Approved],
            self::Linked => [self::PaidOut, self::Alternative],
            self::PaidOut => [],
        };
    }
}

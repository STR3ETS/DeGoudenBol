<?php

namespace App\Domain\Vouchers\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VoucherStatus: string implements HasColor, HasLabel
{
    case Issued = 'issued';
    case Redeemed = 'redeemed';
    case Expired = 'expired';
    case Void = 'void';

    public function getLabel(): string
    {
        return match ($this) {
            self::Issued => 'Geldig',
            self::Redeemed => 'Verzilverd',
            self::Expired => 'Verlopen',
            self::Void => 'Ongeldig',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Issued => 'success',
            self::Redeemed => 'info',
            self::Expired => 'gray',
            self::Void => 'danger',
        };
    }

    /**
     * Kleur van de statuschip op de site en in de scanner (docs/02 §7).
     */
    public function chipStatus(): string
    {
        return match ($this) {
            self::Issued => 'succes',
            self::Redeemed => 'waarschuwing',
            self::Expired => 'neutraal',
            self::Void => 'fout',
        };
    }
}

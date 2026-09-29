<?php

namespace App\Domain\Vouchers\Enums;

use Filament\Support\Contracts\HasLabel;

enum RedemptionMethod: string implements HasLabel
{
    case Scan = 'scan';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scan => 'Gescand',
            self::Manual => 'Handmatig ingevoerd',
        };
    }
}

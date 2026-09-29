<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Besluit 5: aanleveren op een tijdslot of aankoop door de organisatie.
 */
enum LogisticsMode: string implements HasLabel
{
    case Delivery = 'delivery';
    case Purchase = 'purchase';

    public function getLabel(): string
    {
        return match ($this) {
            self::Delivery => 'Aanleveren op tijdslot',
            self::Purchase => 'Aankoop door de organisatie',
        };
    }
}

<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Besluit 8: grondslag van de 10%-reservering voor goede doelen.
 */
enum CharityBasis: string implements HasLabel
{
    case Invoiced = 'invoiced';
    case Received = 'received';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoiced => 'Gefactureerde bedragen excl. btw',
            self::Received => 'Ontvangen bedragen excl. btw',
        };
    }
}

<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Credited = 'credited';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Paid => 'Betaald',
            self::Overdue => 'Vervallen',
            self::Credited => 'Gecrediteerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Credited => 'gray',
        };
    }
}

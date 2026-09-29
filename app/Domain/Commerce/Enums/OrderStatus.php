<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Wacht op betaling',
            self::Paid => 'Betaald',
            self::Expired => 'Verlopen',
            self::Cancelled => 'Geannuleerd',
            self::Refunded => 'Terugbetaald',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Expired, self::Cancelled => 'gray',
            self::Refunded => 'danger',
        };
    }
}

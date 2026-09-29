<?php

namespace App\Domain\Commerce\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Volgt de statussen van Mollie, zodat de webhook één op één kan mappen.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Pending => 'In behandeling',
            self::Authorized => 'Geautoriseerd',
            self::Paid => 'Betaald',
            self::Failed => 'Mislukt',
            self::Canceled => 'Geannuleerd',
            self::Expired => 'Verlopen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open, self::Pending, self::Authorized => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Canceled, self::Expired => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Failed, self::Canceled, self::Expired], true);
    }
}

<?php

namespace App\Domain\Vouchers\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CampaignStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Klaargezet',
            self::Open => 'Winnaars invoeren',
            self::Closed => 'Bonnen uitgegeven',
            self::Expired => 'Afgelopen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'warning',
            self::Closed => 'info',
            self::Expired => 'gray',
        };
    }
}

<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BatchStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'In opbouw',
            self::PendingApproval => 'Wacht op goedkeuring',
            self::Approved => 'Goedgekeurd',
            self::Published => 'Gepubliceerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'info',
            self::Published => 'success',
        };
    }
}

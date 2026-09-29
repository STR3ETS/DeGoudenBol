<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CorrectionCaseStatus: string implements HasColor, HasLabel
{
    case PendingApproval = 'pending_approval';
    case Applied = 'applied';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingApproval => 'Wacht op goedkeuring',
            self::Applied => 'Toegepast',
            self::Rejected => 'Afgewezen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingApproval => 'warning',
            self::Applied => 'success',
            self::Rejected => 'gray',
        };
    }
}

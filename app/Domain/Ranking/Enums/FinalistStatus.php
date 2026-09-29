<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinalistStatus: string implements HasColor, HasLabel
{
    case Invited = 'invited';
    case Confirmed = 'confirmed';
    case Declined = 'declined';

    public function getLabel(): string
    {
        return match ($this) {
            self::Invited => 'Uitgenodigd',
            self::Confirmed => 'Bevestigd',
            self::Declined => 'Afgemeld',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Confirmed => 'success',
            self::Declined => 'gray',
        };
    }

    public function participates(): bool
    {
        return in_array($this, [self::Invited, self::Confirmed], true);
    }
}

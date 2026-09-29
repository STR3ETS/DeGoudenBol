<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SessionStatus: string implements HasColor, HasLabel
{
    case Planned = 'planned';
    case Running = 'running';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Planned => 'Gepland',
            self::Running => 'Bezig',
            self::Closed => 'Afgesloten',
            self::Cancelled => 'Geannuleerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Running => 'warning',
            self::Closed => 'success',
            self::Cancelled => 'gray',
        };
    }

    public function acceptsScorecards(): bool
    {
        return in_array($this, [self::Planned, self::Running], true);
    }
}

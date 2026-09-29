<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ObjectionStatus: string implements HasColor, HasLabel
{
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Ingediend',
            self::Reviewing => 'In behandeling',
            self::Upheld => 'Gegrond',
            self::Dismissed => 'Ongegrond',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Reviewing => 'info',
            self::Upheld => 'success',
            self::Dismissed => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::Reviewing], true);
    }
}

<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ResultStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Voorlopig',
            self::Final => 'Definitief',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Final => 'success',
        };
    }
}

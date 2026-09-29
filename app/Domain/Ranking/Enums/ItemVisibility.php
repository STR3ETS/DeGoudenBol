<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cijfer vanaf de publicatiedrempel (5,0) wordt openbaar; daaronder alleen vertrouwelijke terugkoppeling.
 */
enum ItemVisibility: string implements HasColor, HasLabel
{
    case Public = 'public';
    case Confidential = 'confidential';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => 'Openbaar',
            self::Confidential => 'Vertrouwelijk',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Public => 'success',
            self::Confidential => 'gray',
        };
    }
}

<?php

namespace App\Domain\Marketing\Enums;

use Filament\Support\Contracts\HasLabel;

enum PressMilestone: string implements HasLabel
{
    case ProvinceTop10 = 'province_top10';
    case NationalFinal = 'national_final';

    public function getLabel(): string
    {
        return match ($this) {
            self::ProvinceTop10 => 'Definitieve Top 10 en provinciewinnaar',
            self::NationalFinal => 'Landelijke uitslag',
        };
    }
}

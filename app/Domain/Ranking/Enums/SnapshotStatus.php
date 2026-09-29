<?php

namespace App\Domain\Ranking\Enums;

use Filament\Support\Contracts\HasLabel;

enum SnapshotStatus: string implements HasLabel
{
    case Provisional = 'provisional';
    case Frozen = 'frozen';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Provisional => 'Voorlopige Top 10',
            self::Frozen => 'Definitieve Top 10',
            self::Final => 'Definitief',
        };
    }
}

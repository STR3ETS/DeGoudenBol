<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssignmentStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Scored = 'scored';
    case Skipped = 'skipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Te beoordelen',
            self::Scored => 'Ingediend',
            self::Skipped => 'Overgeslagen',
        };
    }
}

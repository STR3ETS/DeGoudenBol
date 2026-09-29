<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Levensloop van een monster binnen de testketen (stap 3 t/m 7 van het kernproces).
 */
enum SampleStatus: string implements HasColor, HasLabel
{
    case Received = 'received';
    case Numbered = 'numbered';
    case Scheduled = 'scheduled';
    case Scored = 'scored';
    case Final = 'final';
    case FreshnessExpired = 'freshness_expired';
    case Void = 'void';

    public function getLabel(): string
    {
        return match ($this) {
            self::Received => 'Ontvangen',
            self::Numbered => 'Genummerd',
            self::Scheduled => 'In sessie',
            self::Scored => 'Beoordeeld',
            self::Final => 'Definitief',
            self::FreshnessExpired => 'Versheid verlopen',
            self::Void => 'Vervallen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Received, self::Numbered => 'info',
            self::Scheduled => 'warning',
            self::Scored => 'primary',
            self::Final => 'success',
            self::FreshnessExpired => 'danger',
            self::Void => 'gray',
        };
    }

    public function canBeServed(): bool
    {
        return in_array($this, [self::Numbered, self::Scheduled, self::Scored], true);
    }
}

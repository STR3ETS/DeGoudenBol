<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Stap 2 in het dashboard (briefing 29 okt §4): zelf brengen op een tijdslot of laten ophalen op vrijdag.
 */
enum DeliveryMethod: string implements HasColor, HasLabel
{
    case Self = 'self';
    case Pickup = 'pickup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Self => 'Zelf brengen',
            self::Pickup => 'Laten ophalen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Self => 'info',
            self::Pickup => 'warning',
        };
    }
}

<?php

namespace App\Domain\Testing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Waarom een panellid een monster niet krijgt. Nooit met naam of bedrijf.
 */
enum ExclusionReason: string implements HasLabel
{
    case Conflict = 'conflict';
    case Allergen = 'allergen';

    public function getLabel(): string
    {
        return match ($this) {
            self::Conflict => 'Belangenconflict',
            self::Allergen => 'Allergeen',
        };
    }
}

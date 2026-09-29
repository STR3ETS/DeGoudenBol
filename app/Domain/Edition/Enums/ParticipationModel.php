<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Besluit 2: A = tien pakketten vooraf (Deelnemers- & Sponsorplan),
 * B = basisprijs voor iedereen en een promotiepakket na de bevriezing (advies Conceptdossier).
 */
enum ParticipationModel: string implements HasLabel
{
    case A = 'A';
    case B = 'B';

    public function getLabel(): string
    {
        return match ($this) {
            self::A => 'A – tien pakketten vooraf per provincie',
            self::B => 'B – basisprijs, promotiepakket na bevriezing',
        };
    }
}

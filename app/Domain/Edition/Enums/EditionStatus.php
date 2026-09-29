<?php

namespace App\Domain\Edition\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EditionStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case Testing = 'testing';
    case Closed = 'closed';
    case Frozen = 'frozen';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Concept',
            self::RegistrationOpen => 'Inschrijving open',
            self::Testing => 'Testfase',
            self::Closed => 'Gesloten',
            self::Frozen => 'Bevroren',
            self::Published => 'Gepubliceerd',
            self::Archived => 'Gearchiveerd',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'gray',
            self::RegistrationOpen, self::Published => 'success',
            self::Testing => 'info',
            self::Closed, self::Frozen => 'warning',
        };
    }
}

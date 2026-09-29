<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyType: string implements HasLabel
{
    case Bakkerij = 'bakkerij';
    case Seizoenskraam = 'seizoenskraam';
    case Frituurkraam = 'frituurkraam';
    case Snackbar = 'snackbar';
    case Marktbakker = 'marktbakker';
    case Overig = 'overig';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bakkerij => 'Bakkerij',
            self::Seizoenskraam => 'Seizoenskraam',
            self::Frituurkraam => 'Frituurkraam',
            self::Snackbar => 'Snackbar',
            self::Marktbakker => 'Marktbakker',
            self::Overig => 'Overig',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}

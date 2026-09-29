<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * De veertien allergenen uit de EU-verordening 1169/2011.
 */
enum Allergen: string implements HasLabel
{
    case Gluten = 'gluten';
    case Schaaldieren = 'schaaldieren';
    case Ei = 'ei';
    case Vis = 'vis';
    case Pinda = 'pinda';
    case Soja = 'soja';
    case Melk = 'melk';
    case Noten = 'noten';
    case Selderij = 'selderij';
    case Mosterd = 'mosterd';
    case Sesam = 'sesam';
    case Sulfiet = 'sulfiet';
    case Lupine = 'lupine';
    case Weekdieren = 'weekdieren';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gluten => 'Glutenbevattende granen',
            self::Schaaldieren => 'Schaaldieren',
            self::Ei => 'Ei',
            self::Vis => 'Vis',
            self::Pinda => 'Pinda',
            self::Soja => 'Soja',
            self::Melk => 'Melk (incl. lactose)',
            self::Noten => 'Noten',
            self::Selderij => 'Selderij',
            self::Mosterd => 'Mosterd',
            self::Sesam => 'Sesamzaad',
            self::Sulfiet => 'Zwaveldioxide en sulfiet',
            self::Lupine => 'Lupine',
            self::Weekdieren => 'Weekdieren',
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

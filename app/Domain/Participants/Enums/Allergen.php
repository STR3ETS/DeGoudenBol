<?php

namespace App\Domain\Participants\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Allergenen die een bakker voor zijn oliebol aanvinkt; het panel gebruikt ze om panelleden met een
 * allergie uit te sluiten. Lijst van Bennie (30 okt 2026): selderij, schaaldieren, vis, weekdieren en
 * zwaveldioxide vervallen; pinda en noten zijn één keuze; sulfiet zonder zwavel; tarwe / granen.
 * De codes blijven stabiel: ze staan in inschrijvingen en bij panelleden opgeslagen.
 */
enum Allergen: string implements HasLabel
{
    case Gluten = 'gluten';
    case Ei = 'ei';
    case Melk = 'melk';
    case Noten = 'noten';
    case Sesam = 'sesam';
    case Soja = 'soja';
    case Mosterd = 'mosterd';
    case Lupine = 'lupine';
    case Sulfiet = 'sulfiet';

    /**
     * Vervallen codes en waar ze naartoe gaan; null = vervalt zonder vervanging.
     *
     * @var array<string, string|null>
     */
    public const array RETIRED = [
        'pinda' => 'noten',
        'selderij' => null,
        'schaaldieren' => null,
        'vis' => null,
        'weekdieren' => null,
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Gluten => 'Tarwe / granen',
            self::Ei => 'Ei',
            self::Melk => 'Melk (incl. lactose)',
            self::Noten => "Pinda's / noten",
            self::Sesam => 'Sesamzaad',
            self::Soja => 'Soja',
            self::Mosterd => 'Mosterd',
            self::Lupine => 'Lupine',
            self::Sulfiet => 'Sulfiet',
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

    /**
     * Zet een opgeslagen lijst om naar de huidige codes (vervallen codes vertaald of weggelaten).
     *
     * @param  list<string>  $codes
     * @return list<string>
     */
    public static function normalize(array $codes): array
    {
        $result = [];

        foreach ($codes as $code) {
            $code = array_key_exists($code, self::RETIRED) ? self::RETIRED[$code] : $code;

            if ($code !== null && self::tryFrom($code) !== null && ! in_array($code, $result, true)) {
                $result[] = $code;
            }
        }

        return $result;
    }
}

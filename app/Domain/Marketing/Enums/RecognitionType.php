<?php

namespace App\Domain\Marketing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Erkenningen uit docs/04 §6. Hangen aan de ranking, nooit aan een pakket.
 */
enum RecognitionType: string implements HasLabel
{
    case Participant = 'participant';
    case Tested = 'tested';
    case Top10 = 'top10';
    case ProvinceWinner = 'province_winner';
    case NationalList = 'national_list';
    case NationalWinner = 'national_winner';

    public function getLabel(): string
    {
        return match ($this) {
            self::Participant => 'Deelnemer',
            self::Tested => 'Officieel getest',
            self::Top10 => 'Top 10 provincie',
            self::ProvinceWinner => 'Provinciewinnaar',
            self::NationalList => 'Landelijke lijst',
            self::NationalWinner => 'Landelijke winnaar',
        };
    }

    public function badgeText(int $year, ?string $province = null): string
    {
        return match ($this) {
            self::Participant => "Deelnemer De Gouden Bol {$year}",
            self::Tested => "Officieel getest – De Gouden Bol {$year}",
            self::Top10 => "Top 10 {$province} – De Gouden Bol {$year}",
            self::ProvinceWinner => "Winnaar {$province} – De Gouden Bol {$year}",
            self::NationalList => "Landelijke lijst – De Gouden Bol {$year}",
            self::NationalWinner => "De Gouden Bol {$year} – Nummer 1 van Nederland",
        };
    }
}

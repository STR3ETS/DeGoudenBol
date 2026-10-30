<?php

namespace App\Domain\Commerce\Enums;

use App\Domain\Marketing\Enums\RecognitionType;
use Filament\Support\Contracts\HasLabel;

/**
 * Voor wie een sponsoractie bedoeld is: alle deelnemers, of pas na de uitslag voor de Top 10,
 * de provinciewinnaars of de landelijke winnaar.
 */
enum OfferAudience: string implements HasLabel
{
    case AllParticipants = 'all';
    case Top10 = 'top10';
    case ProvinceWinner = 'province_winner';
    case NationalWinner = 'national_winner';

    public function getLabel(): string
    {
        return match ($this) {
            self::AllParticipants => 'Alle deelnemers',
            self::Top10 => 'Top 10 per provincie',
            self::ProvinceWinner => 'Provinciewinnaars',
            self::NationalWinner => 'Landelijke winnaar',
        };
    }

    /**
     * De erkenning die een deelnemer nodig heeft om deze actie te zien; null = iedereen.
     */
    public function requiredRecognition(): ?RecognitionType
    {
        return match ($this) {
            self::AllParticipants => null,
            self::Top10 => RecognitionType::Top10,
            self::ProvinceWinner => RecognitionType::ProvinceWinner,
            self::NationalWinner => RecognitionType::NationalWinner,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $audience) => [$audience->value => $audience->getLabel()])->all();
    }
}

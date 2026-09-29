<?php

namespace App\Domain\Marketing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Mijlpalen van de socialkit (docs/04 §7). Iedere mijlpaal levert teksten en beeld in vier formaten.
 */
enum Milestone: string implements HasLabel
{
    case Participant = 'participant';
    case Tested = 'tested';
    case Top10 = 'top10';
    case ProvinceWinner = 'province_winner';
    case Finalist = 'finalist';
    case NationalList = 'national_list';
    case NationalWinner = 'national_winner';
    case VoucherStart = 'voucher_start';

    public function getLabel(): string
    {
        return match ($this) {
            self::Participant => 'Bevestigde deelname',
            self::Tested => 'Officieel getest',
            self::Top10 => 'Top 10 van de provincie',
            self::ProvinceWinner => 'Provinciewinnaar',
            self::Finalist => 'Finalist',
            self::NationalList => 'Landelijke lijst',
            self::NationalWinner => 'Landelijke winnaar',
            self::VoucherStart => 'Start cadeaubonnenactie',
        };
    }

    public static function fromRecognition(RecognitionType $type): self
    {
        return match ($type) {
            RecognitionType::Participant => self::Participant,
            RecognitionType::Tested => self::Tested,
            RecognitionType::Top10 => self::Top10,
            RecognitionType::ProvinceWinner => self::ProvinceWinner,
            RecognitionType::NationalList => self::NationalList,
            RecognitionType::NationalWinner => self::NationalWinner,
        };
    }

    /**
     * Kop op het beeld.
     */
    public function headline(?string $province = null): string
    {
        return match ($this) {
            self::Participant => 'Wij doen mee',
            self::Tested => 'Officieel getest',
            self::Top10 => 'Top 10 van '.($province ?? 'de provincie'),
            self::ProvinceWinner => 'De beste oliebol van '.($province ?? 'de provincie'),
            self::Finalist => 'Finalist van Nederland',
            self::NationalList => 'Top van Nederland',
            self::NationalWinner => 'De beste oliebol van Nederland',
            self::VoucherStart => 'Win een cadeaubon',
        };
    }

    /**
     * Kant-en-klare tekst voor social media, met hashtag, tag en link.
     */
    public function text(string $company, int $year, string $url, ?string $province = null, ?string $score = null): string
    {
        $hashtag = (string) config('marketing.hashtag');
        $handle = (string) config('marketing.social_handle');

        $body = match ($this) {
            self::Participant => "{$company} doet mee aan De Gouden Bol {$year}, de onafhankelijke oliebollenkeuring van Nederland. Ons product wordt blind beoordeeld door een vakpanel.",
            self::Tested => "Officieel getest! Het panel van De Gouden Bol {$year} beoordeelde onze oliebollen blind".($score ? " met een {$score}" : '').'. Trots op dit resultaat.',
            self::Top10 => "{$company} staat in de definitieve Top 10 van ".($province ?? 'onze provincie')." bij De Gouden Bol {$year}. Blind beoordeeld, openbaar gepubliceerd.",
            self::ProvinceWinner => 'De beste oliebol van '.($province ?? 'onze provincie')." {$year} komt van {$company}! Gekozen door het onafhankelijke panel van De Gouden Bol.",
            self::Finalist => "{$company} vertegenwoordigt ".($province ?? 'onze provincie')." in de landelijke finale van De Gouden Bol {$year}.",
            self::NationalList => "{$company} staat op de landelijke lijst van De Gouden Bol {$year}: bij de beste oliebollen van Nederland.",
            self::NationalWinner => "De beste oliebol van Nederland {$year} komt van {$company}! Winnaar van de landelijke finale van De Gouden Bol.",
            self::VoucherStart => "Vier het met ons: wij geven cadeaubonnen weg aan onze klanten. Kom langs bij {$company}.",
        };

        return "{$body}\n\nBekijk ons profiel: {$url}\n{$hashtag} {$handle}";
    }
}

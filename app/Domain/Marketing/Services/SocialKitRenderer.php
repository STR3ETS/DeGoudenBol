<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Enums\Milestone;
use App\Domain\Participants\Models\Entry;
use InvalidArgumentException;
use ZipArchive;

/**
 * Socialkit per mijlpaal (docs/04 §7): beeld in vier formaten als SVG, kant-en-klare teksten en een zip.
 * De layout schaalt op de breedte; lange bedrijfsnamen breken op een spatie.
 */
final class SocialKitRenderer
{
    /**
     * @return array<string, array{0: int, 1: int, 2: string}>
     */
    public function formats(): array
    {
        return (array) config('marketing.formats');
    }

    public function image(Entry $entry, Milestone $milestone, string $format): string
    {
        $formats = $this->formats();

        if (! isset($formats[$format])) {
            throw new InvalidArgumentException("Onbekend formaat: {$format}");
        }

        [$width, $height] = $formats[$format];
        $entry->loadMissing(['edition', 'province', 'company']);

        $light = $format === 'link';
        $background = $light ? '#FAF5EA' : '#1C0F03';
        $text = $light ? '#1C0F03' : '#FAF5EA';
        $muted = $light ? '#6B5B4A' : 'rgba(250,245,234,0.55)';
        $inner = $light ? '#FAF5EA' : '#1C0F03';

        $unit = $width / 1080;
        $tall = $height > $width * 1.2;
        $centerY = $height / 2 + ($tall ? -40 * $unit : 0);

        $headline = $milestone->headline($entry->province?->name);
        $headlineLines = $this->lines($headline, $format === 'link' ? 26 : 20);
        $headlineSize = round(($format === 'link' ? 72 : 92) * $unit * (count($headlineLines) > 1 ? 0.9 : 1));
        $nameLines = $this->lines($entry->public_name, 24);
        $nameSize = round(44 * $unit);
        $year = $entry->edition->year;
        $site = $this->escape((string) config('marketing.site_label'));
        $hashtag = $this->escape((string) config('marketing.hashtag'));
        $fonts = BrandFonts::css();

        $logoSize = round(96 * $unit);
        $logoX = $width / 2 - $logoSize / 2;
        $logoY = $tall ? $height * 0.16 : $height * 0.12;
        $eyebrowY = $logoY + $logoSize + 60 * $unit;

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\" role=\"img\" aria-label=\"".$this->escape($headline).' – '.$this->escape($entry->public_name)."\">\n";
        $svg .= "<style>{$fonts}.t{font-family:'Cormorant Garamond',Georgia,serif;font-weight:600;font-variant-numeric:lining-nums;}.m{font-family:Manrope,Arial,sans-serif;font-weight:800;letter-spacing:0.22em;text-transform:uppercase;}.c{font-family:Manrope,Arial,sans-serif;font-weight:500;}</style>\n";
        $svg .= "<defs><radialGradient id=\"g\" cx=\"100%\" cy=\"0%\" r=\"90%\"><stop offset=\"0\" stop-color=\"#C8860A\" stop-opacity=\"0.22\"/><stop offset=\"1\" stop-color=\"#C8860A\" stop-opacity=\"0\"/></radialGradient></defs>\n";
        $svg .= "<rect width=\"{$width}\" height=\"{$height}\" fill=\"{$background}\"/><rect width=\"{$width}\" height=\"{$height}\" fill=\"url(#g)\"/>\n";
        $svg .= '<g transform="translate('.round($logoX).','.round($logoY).') scale('.round($logoSize / 96, 4).')"><circle cx="48" cy="48" r="46" fill="#C8860A"/><circle cx="48" cy="48" r="32" fill="'.$inner.'"/><polygon points="48,22 53,38 70,38 57,47 62,64 48,55 34,64 39,47 26,38 43,38" fill="#C8860A"/></g>'."\n";
        $svg .= '<text x="'.($width / 2).'" y="'.round($eyebrowY).'" text-anchor="middle" class="m" font-size="'.round(22 * $unit).'" fill="#C8860A">De Gouden Bol '.$year.'</text>'."\n";

        $y = $centerY - ($headlineSize * (count($headlineLines) - 1)) / 2;

        foreach ($headlineLines as $line) {
            $svg .= '<text x="'.($width / 2).'" y="'.round($y).'" text-anchor="middle" class="t" font-size="'.$headlineSize.'" fill="'.$text.'">'.$this->escape($line).'</text>'."\n";
            $y += $headlineSize * 1.05;
        }

        $y += $nameSize * 0.6;

        foreach ($nameLines as $line) {
            $svg .= '<text x="'.($width / 2).'" y="'.round($y).'" text-anchor="middle" class="c" font-size="'.$nameSize.'" fill="'.$text.'">'.$this->escape($line).'</text>'."\n";
            $y += $nameSize * 1.25;
        }

        $footerY = $height - ($tall ? 0.12 : 0.1) * $height;
        $svg .= '<text x="'.($width / 2).'" y="'.round($footerY).'" text-anchor="middle" class="c" font-size="'.round(26 * $unit).'" fill="'.$muted.'">'.$site.'  ·  '.$hashtag.'</text>'."\n";
        $svg .= '</svg>';

        return $svg;
    }

    public function text(Entry $entry, Milestone $milestone): string
    {
        $entry->loadMissing(['edition', 'province', 'company', 'publicationItems.batch']);
        $total = $entry->publicTotal();

        return $milestone->text(
            $entry->public_name,
            $entry->edition->year,
            route('bakkers.toon', $entry->company),
            $entry->province?->name,
            $total !== null ? number_format($total, 1, ',', '.') : null,
        );
    }

    /**
     * Zip met de vier SVG-beelden en de teksten, als binaire string.
     */
    public function zip(Entry $entry, Milestone $milestone): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dgb-kit-');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new InvalidArgumentException('Kan de zip niet aanmaken.');
        }

        foreach ($this->formats() as $format => [$width, $height]) {
            $zip->addFromString("de-gouden-bol-{$milestone->value}-{$format}-{$width}x{$height}.svg", $this->image($entry, $milestone, $format));
        }

        $zip->addFromString('tekst.txt', $this->text($entry, $milestone));
        $zip->close();

        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    /**
     * @return list<string>
     */
    private function lines(string $value, int $maxChars): array
    {
        $words = preg_split('/\s+/', trim($value)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : "{$current} {$word}";

            if (mb_strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

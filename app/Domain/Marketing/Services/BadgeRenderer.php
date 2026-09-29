<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Models\Recognition;

/**
 * Badge als SVG (600 × 200) in een lichte en donkere variant. Jaar en categorie altijd zichtbaar;
 * cirkel-sterlogo; Cormorant voor de titel. PNG en print-pdf volgen zodra Browsershot beschikbaar is.
 */
final class BadgeRenderer
{
    public const array VARIANTS = ['licht', 'donker'];

    public function svg(Recognition $recognition, string $variant = 'licht'): string
    {
        $recognition->loadMissing(['edition', 'province', 'company']);

        $dark = $variant === 'donker';
        $background = $dark ? '#1C0F03' : '#FAF5EA';
        $text = $dark ? '#FAF5EA' : '#1C0F03';
        $muted = $dark ? 'rgba(250,245,234,0.55)' : '#6B5B4A';
        $ring = $dark ? 'rgba(250,245,234,0.14)' : 'rgba(28,15,3,0.10)';
        $inner = $dark ? '#1C0F03' : '#FAF5EA';

        $title = $this->escape($recognition->type->getLabel());
        $year = $recognition->edition->year;
        $category = $this->escape(match (true) {
            $recognition->province !== null && in_array($recognition->type->value, ['top10', 'province_winner'], true) => $recognition->province->name,
            default => 'Oliebollenkeuring',
        });
        $company = $this->escape($this->truncate($recognition->company->name, 34));
        $titleSize = mb_strlen($recognition->type->getLabel()) > 18 ? 40 : 46;
        $fonts = BrandFonts::css();

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="200" viewBox="0 0 600 200" role="img" aria-label="{$title} – De Gouden Bol {$year}">
<style>{$fonts}
.t{font-family:'Cormorant Garamond',Georgia,serif;font-weight:700;font-variant-numeric:lining-nums;}
.m{font-family:Manrope,Arial,sans-serif;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;}
.c{font-family:Manrope,Arial,sans-serif;font-weight:500;}</style>
<rect x="1" y="1" width="598" height="198" rx="28" fill="{$background}" stroke="{$ring}" stroke-width="2"/>
<g transform="translate(40,52)">
<circle cx="48" cy="48" r="46" fill="#C8860A"/>
<circle cx="48" cy="48" r="32" fill="{$inner}"/>
<polygon points="48,22 53,38 70,38 57,47 62,64 48,55 34,64 39,47 26,38 43,38" fill="#C8860A"/>
</g>
<text x="162" y="66" class="m" font-size="13" fill="#C8860A">De Gouden Bol {$year} · {$category}</text>
<text x="162" y="118" class="t" font-size="{$titleSize}" fill="{$text}">{$title}</text>
<text x="162" y="156" class="c" font-size="18" fill="{$muted}">{$company}</text>
</svg>
SVG;
    }

    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1).'…' : $value;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

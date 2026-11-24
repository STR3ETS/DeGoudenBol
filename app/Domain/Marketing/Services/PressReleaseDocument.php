<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Models\PressRelease;
use App\Support\DutchTime;

/**
 * Leesmodel voor het persbericht als A4-document (naar het aangeleverde sjabloon): verdeelt de platte tekst
 * uit de backoffice over kop, plaats en datum, lead, alinea's, citaat en de noot voor de redactie.
 * Er wordt niets bij verzonnen: wat niet is ingevuld, staat niet in het document.
 */
final readonly class PressReleaseDocument
{
    /**
     * @param  list<array{heading: string|null, text: string}>  $blocks
     */
    private function __construct(
        public PressRelease $release,
        public ?string $place,
        public ?string $date,
        public ?string $lead,
        public array $blocks,
        public ?string $embargo,
        public string $moreUrl,
    ) {}

    public static function for(PressRelease $release): self
    {
        $release->loadMissing('province');

        $paragraphs = collect(preg_split('/\R{2,}/', trim((string) $release->body)) ?: [])
            ->map(fn (string $paragraph) => trim($paragraph))
            ->filter()
            // De kopregel en de noot voor de redactie hebben in het document een eigen plek.
            ->reject(fn (string $paragraph) => str_starts_with(mb_strtoupper($paragraph), 'PERSBERICHT') && ! str_contains($paragraph, "\n"))
            ->reject(fn (string $paragraph) => str_starts_with(mb_strtolower($paragraph), 'noot voor de redactie'))
            ->values();

        $place = $date = $lead = null;
        $first = $paragraphs->first();

        // "Arnhem, 21 december 2026 – De lead." wordt plaats, datum en lead.
        if ($first !== null && preg_match('/^([^,\n]{2,50}),\s+(\d{1,2}\s+\p{L}+\s+\d{4})\s+[–—-]\s+(.+)$/su', $first, $match)) {
            [$place, $date, $lead] = [trim($match[1]), trim($match[2]), trim($match[3])];
            $paragraphs = $paragraphs->slice(1)->values();
        }

        $blocks = $paragraphs->map(function (string $paragraph): array {
            $lines = preg_split('/\R/', $paragraph) ?: [$paragraph];
            $heading = trim($lines[0]);

            // Een korte eerste regel zonder punt, gevolgd door meer tekst, is een tussenkop.
            if (count($lines) > 1 && mb_strlen($heading) <= 70 && ! str_ends_with($heading, '.')) {
                return ['heading' => rtrim($heading, ':'), 'text' => trim(implode("\n", array_slice($lines, 1)))];
            }

            return ['heading' => null, 'text' => $paragraph];
        })->all();

        return new self(
            release: $release,
            place: $place,
            date: $date,
            lead: $lead,
            blocks: array_values($blocks),
            embargo: $release->isEmbargoed() ? 'Onder embargo tot '.DutchTime::format($release->embargo_until, 'dddd D MMMM YYYY, HH:mm').' uur' : null,
            moreUrl: $release->province ? route('provincies.show', $release->province) : route('finale'),
        );
    }

    public function hasQuote(): bool
    {
        return filled($this->release->quote);
    }

    /**
     * Contactregels voor de noot; een placeholder uit de configuratie ("[... VOLGT]") komt niet in het document.
     *
     * @return list<string>
     */
    public function contactLines(): array
    {
        return array_values(array_filter(
            [config('press.contact_name'), config('press.contact_email'), config('press.contact_phone')],
            fn (mixed $line): bool => filled($line) && ! str_starts_with((string) $line, '['),
        ));
    }
}

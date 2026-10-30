<?php

namespace App\Support\Site;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Blogs en landingspagina's van de contentmachine. Die schrijft in EazyDash, op de CMS-site
 * waaruit deze publiekssite is geëxporteerd, dus de opmaak is dezelfde. Deze site haalt de
 * pagina's op met het leestoken en bewaart ze, zodat een bezoeker nooit op de API wacht en de
 * site gewoon doorwerkt als die even weg is.
 *
 * Zonder token (EAZY_CMS_TOKEN) doet dit niets en blijft de site zoals hij was.
 */
class MachineContent
{
    /** Vijf minuten vers; daarna krijgt de bezoeker de vorige stand en komt de nieuwe op de achtergrond. */
    private const int VERS = 300;

    /** Zo lang houden we de vorige stand aan als de API wegblijft. */
    private const int BEWAAR = 604800;

    /** Na een storing zo lang niet opnieuw proberen, anders wacht elke bezoeker op de time-out. */
    private const int STORING = 60;

    /** @return Collection<string, array<string, mixed>> artikelen op slug, nieuwste eerst */
    public function artikelen(): Collection
    {
        return $this->overzicht()->where('type', 'blog');
    }

    /** @return Collection<string, array<string, mixed>> landingspagina's op slug */
    public function landingspaginas(): Collection
    {
        return $this->overzicht()->where('type', 'service');
    }

    /** @return array<string, mixed>|null */
    public function artikel(string $slug): ?array
    {
        return $this->volledig($this->artikelen()->get($slug));
    }

    /** @return array<string, mixed>|null */
    public function landingspagina(string $slug): ?array
    {
        return $this->volledig($this->landingspaginas()->get($slug));
    }

    /** De html zoals hij in de pagina komt: zonder scripts en zonder gebeurtenissen op tags. */
    public static function schoon(string $html): string
    {
        $html = (string) preg_replace(['~<script\b[^>]*>.*?</script\s*>~is', '~</?script\b[^>]*>~i'], '', $html);

        // Alleen binnen een tag: "online = ..." in de lopende tekst blijft staan.
        return (string) preg_replace_callback('~<[a-z][a-z0-9-]*\b[^>]*>~i', fn (array $tag) => (string) preg_replace(
            ['~\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)~i', '~(\s(?:href|src)\s*=\s*)(["\'])\s*javascript:[^"\']*\2~i'],
            ['', '$1$2#$2'],
            $tag[0]
        ), $html);
    }

    /** @return Collection<string, array<string, mixed>> */
    private function overzicht(): Collection
    {
        return collect($this->bewaard('machine.overzicht', $this->haalOverzicht(...)));
    }

    /**
     * Haalt de tekst erbij. Met de wijzigingsdatum in de sleutel: is de tekst aangepast, dan is
     * dat na de volgende ronde van het overzicht meteen te zien.
     *
     * @param  array<string, mixed>|null  $stuk
     * @return array<string, mixed>|null
     */
    private function volledig(?array $stuk): ?array
    {
        if (! $stuk) {
            return null;
        }

        try {
            return Cache::remember(
                'machine.pagina.'.md5($stuk['slug'].'|'.($stuk['updated_at'] ?? '')),
                self::BEWAAR,
                fn () => $this->vraag('/content/'.rawurlencode($stuk['slug']))->json('data')
            );
        } catch (\Throwable $e) {
            Log::warning('Pagina uit EazyDash niet opgehaald', ['slug' => $stuk['slug'], 'fout' => $e->getMessage()]);

            // Geen 404: de pagina bestaat wel, en Google hoeft hem niet te vergeten.
            abort(503);
        }
    }

    /** @return array<string, array<string, mixed>> */
    private function haalOverzicht(): array
    {
        $stukken = [];
        $pagina = 1;

        do {
            $antwoord = $this->vraag('/content', ['per_page' => 100, 'page' => $pagina]);
            array_push($stukken, ...$antwoord->json('data', []));
        } while ($pagina++ < (int) $antwoord->json('meta.last_page', 1));

        return collect($stukken)->keyBy('slug')->all();
    }

    /** @param  array<string, mixed>  $query */
    private function vraag(string $pad, array $query = []): Response
    {
        return Http::withToken((string) config('services.eazyonline.token'))
            ->acceptJson()
            ->timeout(4)
            ->get(rtrim((string) config('services.eazyonline.url'), '/').$pad, $query)
            ->throw();
    }

    /** Uit de cache, of opgehaald. Lukt dat niet, dan leeg: de vaste pagina's van de site doen het gewoon. */
    private function bewaard(string $sleutel, callable $ophalen): array
    {
        if (blank(config('services.eazyonline.token'))) {
            return [];
        }

        if (Cache::has('machine.storing') && ! Cache::has($sleutel)) {
            return [];
        }

        try {
            // alwaysDefer: anders slaat Laravel het verversen over na een 404, en dat is
            // juist wat de eerste bezoeker van een nieuwe pagina krijgt.
            return Cache::flexible($sleutel, [self::VERS, self::BEWAAR], $ophalen, alwaysDefer: true);
        } catch (\Throwable $e) {
            Log::warning('Content uit EazyDash niet opgehaald', ['fout' => $e->getMessage()]);
            Cache::put('machine.storing', true, self::STORING);

            return [];
        }
    }
}

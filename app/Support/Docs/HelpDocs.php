<?php

namespace App\Support\Docs;

use Illuminate\Support\Str;

/**
 * De handleiding voor beheer: Markdown-bestanden in resources/docs/beheer, één per onderwerp,
 * met per vraag een kop van niveau twee en daaronder het antwoord. Dezelfde structuur voedt
 * de pagina /docs, de JSON op /docs.json en later de hulpbot.
 *
 * @phpstan-type Vraag array{id: string, sectie: string, vraag: string, antwoord: string, antwoord_html: string}
 * @phpstan-type Sectie array{slug: string, titel: string, intro: string, vragen: list<Vraag>}
 */
final class HelpDocs
{
    public function __construct(private readonly string $path) {}

    public static function make(): self
    {
        return new self(resource_path('docs/beheer'));
    }

    /**
     * @return list<Sectie>
     */
    public function sections(): array
    {
        $files = glob($this->path.'/*.md') ?: [];
        sort($files);

        return array_values(array_filter(array_map(fn (string $file) => $this->parse($file), $files)));
    }

    /**
     * Alle vragen plat, voor de bot en voor zoeken.
     *
     * @return list<Vraag>
     */
    public function questions(): array
    {
        return array_merge(...array_map(fn (array $section) => $section['vragen'], $this->sections()) ?: [[]]);
    }

    /**
     * @return Sectie|null
     */
    private function parse(string $file): ?array
    {
        $raw = (string) file_get_contents($file);
        $lines = preg_split('/\R/', $raw) ?: [];
        $slug = Str::of(basename($file, '.md'))->replaceMatches('/^\d+-/', '')->toString();
        $title = Str::headline($slug);
        $intro = '';
        $questions = [];
        $current = null;
        $buffer = [];

        $flush = function () use (&$questions, &$current, &$buffer, $slug): void {
            if ($current === null) {
                return;
            }

            $markdown = trim(implode("\n", $buffer));
            $questions[] = [
                'id' => $slug.'-'.Str::slug($current),
                'sectie' => $slug,
                'vraag' => $current,
                'antwoord' => trim(preg_replace('/\s+/', ' ', strip_tags((string) Str::markdown($markdown))) ?? ''),
                'antwoord_html' => (string) Str::markdown($markdown),
            ];
            $buffer = [];
        };

        foreach ($lines as $line) {
            if (str_starts_with($line, '# ')) {
                $title = trim(substr($line, 2));

                continue;
            }

            if (str_starts_with($line, '> ') && $current === null) {
                $intro = trim(substr($line, 2));

                continue;
            }

            if (str_starts_with($line, '## ')) {
                $flush();
                $current = trim(substr($line, 3));

                continue;
            }

            if ($current !== null) {
                $buffer[] = $line;
            }
        }

        $flush();

        if ($questions === []) {
            return null;
        }

        return ['slug' => $slug, 'titel' => $title, 'intro' => $intro, 'vragen' => $questions];
    }
}

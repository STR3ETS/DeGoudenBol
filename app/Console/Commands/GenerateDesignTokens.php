<?php

namespace App\Console\Commands;

use App\Support\DesignTokens;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateDesignTokens extends Command
{
    protected $signature = 'design:tokens
        {--check : Controleer alleen of de gegenereerde bestanden actueel zijn}';

    protected $description = 'Genereert tokens.css (Tailwind @theme) en fonts.css uit resources/design/tokens.json';

    public function handle(DesignTokens $tokens, Filesystem $files): int
    {
        $fontsCss = $this->renderFontsCss($tokens);

        $outputs = [
            resource_path('css/tokens.css') => $this->renderTokensCss($tokens),
            resource_path('css/fonts.css') => $fontsCss,
            public_path('css/fonts.css') => $fontsCss,
        ];

        $stale = [];

        foreach ($outputs as $path => $contents) {
            $current = $files->exists($path) ? $files->get($path) : null;

            if ($current === $contents) {
                continue;
            }

            $stale[] = $path;

            if (! $this->option('check')) {
                $files->ensureDirectoryExists(dirname($path));
                $files->put($path, $contents);
                $this->components->info('Geschreven: '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
            }
        }

        if ($this->option('check') && $stale !== []) {
            $this->components->error('Gegenereerde tokenbestanden zijn verouderd. Draai `php artisan design:tokens`.');

            return self::FAILURE;
        }

        if ($stale === []) {
            $this->components->info('Tokenbestanden zijn actueel.');
        }

        return self::SUCCESS;
    }

    private function renderTokensCss(DesignTokens $tokens): string
    {
        $lines = [
            '/*',
            ' * Gegenereerd door `php artisan design:tokens` uit resources/design/tokens.json.',
            ' * Niet handmatig bewerken: pas tokens.json aan en genereer opnieuw.',
            ' */',
            '',
            '@theme {',
            '  /* Kleuren */',
        ];

        foreach ($tokens->colors() as $name => $value) {
            $lines[] = "  --color-{$name}: {$value};";
        }

        foreach ($tokens->statusColors() as $name => $definition) {
            $lines[] = "  --color-status-{$name}: {$definition['text']};";
            $lines[] = "  --color-status-{$name}-bg: {$definition['bg']};";
        }

        foreach ($tokens->grayPalette() as $shade => $hex) {
            $lines[] = "  --color-grijs-{$shade}: {$hex};";
        }

        $lines[] = '';
        $lines[] = '  /* Lettertypen */';

        foreach ($tokens->fonts() as $name => $font) {
            $lines[] = "  --font-{$name}: {$font['family']};";
        }

        $lines[] = '';
        $lines[] = '  /* Tekststijlen (text-<naam>) */';

        foreach ($tokens->typeScale() as $name => $style) {
            $lines[] = "  --text-{$name}: {$style['size']};";

            if (isset($style['lineHeight'])) {
                $lines[] = "  --text-{$name}--line-height: {$style['lineHeight']};";
            }

            if (isset($style['letterSpacing'])) {
                $lines[] = "  --text-{$name}--letter-spacing: {$style['letterSpacing']};";
            }

            if (isset($style['weight'])) {
                $lines[] = "  --text-{$name}--font-weight: {$style['weight']};";
            }
        }

        $lines[] = '';
        $lines[] = '  /* Vorm */';

        foreach ($tokens->radii() as $name => $value) {
            $lines[] = "  --radius-{$name}: {$value};";
        }

        $lines[] = '';
        $lines[] = '  /* Schaduw */';

        foreach ($tokens->shadows() as $name => $value) {
            $lines[] = "  --shadow-{$name}: {$value};";
        }

        $layout = $tokens->layout();
        $motion = $tokens->motion();

        $lines[] = '';
        $lines[] = '  /* Raster en beweging */';
        $lines[] = "  --container-site: {$layout['maxWidth']};";
        $lines[] = "  --spacing-gutter: {$layout['gutter']};";
        $lines[] = "  --spacing-gutter-mobiel: {$layout['gutterMobile']};";
        $lines[] = "  --spacing-sectie: {$layout['section']};";
        $lines[] = "  --spacing-sectie-sub: {$layout['sectionSub']};";
        $lines[] = "  --spacing-tikdoel: {$layout['tapTargetMin']};";
        $lines[] = "  --ease-zacht: {$motion['ease']};";
        $lines[] = "  --reveal-offset: {$motion['revealOffset']};";
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderFontsCss(DesignTokens $tokens): string
    {
        $lines = [
            '/*',
            ' * Gegenereerd door `php artisan design:tokens`. Zelf gehoste fonts uit public/fonts.',
            ' */',
            '',
        ];

        foreach ($tokens->fonts() as $font) {
            $family = trim(explode(',', $font['family'])[0], " \"'");
            $slug = $font['slug'];

            foreach ($font['weights'] as $weight) {
                $lines[] = $this->fontFace($family, $slug, $weight, 'normal');
            }

            foreach ($font['italic'] ?? [] as $weight) {
                $lines[] = $this->fontFace($family, $slug, $weight, 'italic');
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function fontFace(string $family, string $slug, int $weight, string $style): string
    {
        return <<<CSS
        @font-face {
          font-family: '{$family}';
          font-style: {$style};
          font-weight: {$weight};
          font-display: swap;
          src: url('/fonts/{$slug}-latin-{$weight}-{$style}.woff2') format('woff2');
        }

        CSS;
    }
}

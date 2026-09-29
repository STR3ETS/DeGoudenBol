<?php

namespace App\Domain\Marketing\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Ingesloten fonts voor SVG-beeld (badges, socialkit), zodat het beeld overal in de huisstijl rendert,
 * ook als het als <img> of los bestand wordt geopend.
 */
final class BrandFonts
{
    public static function css(): string
    {
        return Cache::rememberForever('marketing.brand-fonts.css', function (): string {
            $fonts = [
                ['Cormorant Garamond', 600, 'cormorant-garamond-latin-600-normal.woff2'],
                ['Cormorant Garamond', 700, 'cormorant-garamond-latin-700-normal.woff2'],
                ['Manrope', 500, 'manrope-latin-500-normal.woff2'],
                ['Manrope', 800, 'manrope-latin-800-normal.woff2'],
            ];

            $css = '';

            foreach ($fonts as [$family, $weight, $file]) {
                $path = public_path('fonts/'.$file);

                if (! is_file($path)) {
                    continue;
                }

                $data = base64_encode((string) file_get_contents($path));
                $css .= "@font-face{font-family:'{$family}';font-weight:{$weight};font-style:normal;src:url(data:font/woff2;base64,{$data}) format('woff2');}";
            }

            return $css;
        });
    }
}

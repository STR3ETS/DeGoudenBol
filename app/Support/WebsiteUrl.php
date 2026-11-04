<?php

namespace App\Support;

/**
 * Websiteadres zoals mensen het intypen ("www.bakkerij.nl", "bakkerij.nl/oliebollen") omzetten naar een
 * volledige URL met https://, zodat niemand hoeft te weten dat dat erbij moet (verzoek Bennie 3 nov 2026).
 */
final class WebsiteUrl
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return preg_replace('#^(https?)://#i', strtolower(strstr($value, ':', true)).'://', $value);
        }

        return 'https://'.ltrim($value, '/');
    }

    /**
     * Validatie na normalize(): een geldige URL met een punt in de domeinnaam (dus niet "https://bakkerij").
     *
     * @return list<string>
     */
    public static function rules(int $max = 255): array
    {
        return ['nullable', 'url', 'regex:#^https?://[^\s/]+\.[^\s/]+#i', "max:{$max}"];
    }
}

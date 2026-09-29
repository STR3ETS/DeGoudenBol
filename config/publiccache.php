<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paginacache publiekssite (docs/05 §4)
    |--------------------------------------------------------------------------
    |
    | Volledige HTML van anonieme GET-pagina's wordt in de cache bewaard onder een
    | generatienummer. Iedere wijziging aan publieke inhoud (publicatie, reveal,
    | profiel, sponsor, nieuws …) verhoogt het nummer, waardoor alle pagina's in
    | één keer vervallen. Een CDN volgt via de purge-driver.
    |
    */

    'enabled' => (bool) env('PUBLIC_CACHE_ENABLED', true),

    // Levensduur in minuten (vangnet; invalidatie gebeurt direct bij wijzigingen).
    'ttl_minutes' => (int) env('PUBLIC_CACHE_TTL', 10),

    // Alleen deze routes worden gecachet: anoniem, zonder formulieren met CSRF-token.
    'routes' => [
        'home', 'provincies.index', 'provincies.show', 'bakkers.index', 'bakkers.toon', 'finale',
        'edities.index', 'edities.show', 'hoe-werkt-de-test', 'panel', 'nieuws.index', 'nieuws.toon',
        'voorwaarden', 'privacy', 'actievoorwaarden', 'winnaars', 'sponsoren', 'goede-doelen',
        'pers', 'pers.toon', 'sitemap',
    ],

    // CDN-invalidatie: 'null' (geen CDN) of 'cloudflare' (purge everything bij iedere wijziging).
    'cdn' => [
        'driver' => env('CDN_DRIVER', 'null'),
        'cloudflare' => [
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
        ],
    ],

];

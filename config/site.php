<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Publiekssite
    |--------------------------------------------------------------------------
    |
    | Navigatie en footer verwijzen naar benoemde routes. Een link verschijnt
    | pas zodra die route bestaat; tot die tijd kan een 'fallback'-anker op de
    | homepage gebruikt worden. Zo staan er nooit dode links op de site.
    |
    | 'placeholders' toont blokken waarvoor nog geen echte inhoud is (beeld,
    | citaten) met een duidelijke markering. Alleen lokaal en op acceptatie.
    |
    */

    'styleguide_enabled' => (bool) env('STYLEGUIDE_ENABLED', false),

    'placeholders' => (bool) env('SITE_PLACEHOLDERS', false),

    // Google Tag Manager op de publiekssite (niet in portaal of backoffice); laadt alleen in productie.
    'gtm_id' => env('GTM_ID', 'GTM-TV5Z4R84'),

    // Google Analytics op de publiekssite; laadt alleen in productie en pas na toestemming (site.partials.cookiekeuze).
    'ga_id' => env('GA_ID', 'G-6V0TEJWL2E'),

    // Zelfde menu en voet als de website (site.partials.header en footer), zodat pagina's in de oude
    // layout (aanmelden, betaalstatus, cadeaubonnen, erkenning, archief) er hetzelfde uitzien.
    'nav' => [
        ['label' => 'De test', 'route' => 'hoe-werkt-de-test'],
        ['label' => 'Provincies', 'route' => 'provincies.index'],
        ['label' => 'Finale', 'route' => 'finale'],
        ['label' => 'Ondernemers', 'route' => 'ondernemers'],
        ['label' => 'Sponsoring', 'route' => 'sponsoren'],
        ['label' => 'Blog', 'route' => 'blog'],
    ],

    'nav_cta' => ['label' => 'Aanmelden als bakker', 'route' => 'deelnemen'],

    'footer' => [
        'De test' => [
            ['label' => 'De test', 'route' => 'hoe-werkt-de-test'],
            ['label' => 'Provincies', 'route' => 'provincies.index'],
            ['label' => 'Landelijke finale', 'route' => 'finale'],
            ['label' => 'Deelnemers', 'route' => 'bakkers.index'],
            ['label' => 'Over De Gouden Bol', 'route' => 'over'],
        ],
        'Meedoen' => [
            ['label' => 'Aanmelden 2026', 'route' => 'deelnemen'],
            ['label' => 'Voor ondernemers', 'route' => 'ondernemers'],
            ['label' => 'Sponsoring', 'route' => 'sponsoren'],
            ['label' => 'De Bakkers Academie', 'route' => 'bakkers-academie'],
            ['label' => 'Cadeaubonnen', 'route' => 'cadeaubonnen'],
        ],
        'Meer' => [
            ['label' => 'Goede doelen', 'route' => 'goede-doelen'],
            ['label' => 'Blog', 'route' => 'blog'],
            ['label' => 'Nieuws & pers', 'route' => 'pers'],
            ['label' => 'Veelgestelde vragen', 'route' => 'faq'],
            ['label' => 'Voorwaarden', 'route' => 'voorwaarden'],
            ['label' => 'Privacyverklaring', 'route' => 'privacy'],
        ],
    ],
];

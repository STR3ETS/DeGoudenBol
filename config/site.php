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

    'nav' => [
        ['label' => 'Voorlijst', 'route' => 'provincies.index', 'fallback' => '#voorlijst'],
        ['label' => 'Alle bakkers', 'route' => 'bakkers.index'],
        ['label' => 'Hoe werkt het', 'route' => 'hoe-werkt-de-test', 'fallback' => '#hoe-werkt'],
        ['label' => 'Het panel', 'route' => 'panel', 'fallback' => '#panel'],
        ['label' => 'Finale', 'route' => 'finale'],
        ['label' => 'Nieuws', 'route' => 'nieuws.index'],
    ],

    'nav_cta' => ['label' => 'Aanmelden als bakker', 'route' => 'aanmelden', 'fallback' => '#aanmelden'],

    'footer' => [
        'Ranglijst' => [
            ['label' => 'Per provincie', 'route' => 'provincies.index', 'fallback' => '#voorlijst'],
            ['label' => 'Landelijke finale', 'route' => 'finale'],
            ['label' => 'Archief', 'route' => 'edities.index'],
            ['label' => 'Winnaars cadeaubonnen', 'route' => 'winnaars'],
        ],
        'Deelnemers' => [
            ['label' => 'Aanmelden', 'route' => 'aanmelden', 'fallback' => '#aanmelden'],
            ['label' => 'Hoe werkt de test', 'route' => 'hoe-werkt-de-test', 'fallback' => '#hoe-werkt'],
            ['label' => 'Erkenning controleren', 'route' => 'erkenning.zoek'],
        ],
        'Over' => [
            ['label' => 'Het panel', 'route' => 'panel', 'fallback' => '#panel'],
            ['label' => 'Goede doelen', 'route' => 'goede-doelen'],
            ['label' => 'Sponsoren', 'route' => 'sponsoren'],
            ['label' => 'Pers & media', 'route' => 'pers'],
            ['label' => 'Privacy', 'route' => 'privacy'],
            ['label' => 'Voorwaarden', 'route' => 'voorwaarden'],
            ['label' => 'Actievoorwaarden', 'route' => 'actievoorwaarden'],
        ],
    ],

];

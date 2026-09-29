<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socialmediakit en badges (docs/04 §6 en §7)
    |--------------------------------------------------------------------------
    */

    'hashtag' => env('MARKETING_HASHTAG', '#DeGoudenBol'),

    'social_handle' => env('MARKETING_SOCIAL_HANDLE', '@degoudenbol'),

    // Leesbare weergave van de site in beeldmateriaal (zonder https://).
    'site_label' => env('MARKETING_SITE_LABEL', 'degoudenbol.nl'),

    // Formaten van de socialkit: naam => [breedte, hoogte, omschrijving].
    'formats' => [
        'feed' => [1080, 1080, 'Feed, vierkant'],
        'portrait' => [1080, 1350, 'Feed, staand'],
        'story' => [1080, 1920, 'Story'],
        'link' => [1200, 630, 'Facebook en linkvoorvertoning'],
    ],

];

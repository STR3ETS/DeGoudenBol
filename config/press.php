<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pers (docs/06: /pers en /pers/kit/{token})
    |--------------------------------------------------------------------------
    |
    | Contactgegevens onder ieder persbericht ("noot voor de redactie").
    | Tot de definitieve gegevens er zijn, blijven dit gemarkeerde placeholders.
    |
    */

    'contact_name' => env('PRESS_CONTACT_NAME', '[PERSCONTACT VOLGT]'),
    'contact_email' => env('PRESS_CONTACT_EMAIL', 'pers@degoudenbol.nl'),
    'contact_phone' => env('PRESS_CONTACT_PHONE', '[TELEFOON VOLGT]'),

    // Hoeveel dagen een perskit-link geldig blijft na het versturen.
    'kit_link_days' => (int) env('PRESS_KIT_LINK_DAYS', 14),

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cadeaubonnen (docs/04 §8)
    |--------------------------------------------------------------------------
    */

    // Winnaars invoeren kan tot dit aantal uren na de start van de actie, om dit tijdstip (Amsterdam).
    'winners_deadline_hours' => (int) env('VOUCHERS_WINNERS_DEADLINE_HOURS', 48),
    'winners_deadline_time' => env('VOUCHERS_WINNERS_DEADLINE_TIME', '12:00'),

    // Vanaf deze datum worden winnaarsgegevens geanonimiseerd (juridisch: minimaal en tijdelijk).
    'anonymise_from' => env('VOUCHERS_ANONYMISE_FROM', '2027-03-31'),

    // Leesbare code: GB{jj}-{provincie}-{4 tekens}.
    'code_prefix' => env('VOUCHERS_CODE_PREFIX', 'GB'),

];

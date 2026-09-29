<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kluis
    |--------------------------------------------------------------------------
    |
    | Eigen sleutel voor de koppeltabel testnummer <-> inschrijving, los van
    | APP_KEY. Genereer met: php -r "echo 'base64:'.base64_encode(random_bytes(32));"
    |
    */

    'key' => env('VAULT_ENCRYPTION_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    | Boven dit aantal inzages per uur door één actor schrijft de kluis een waarschuwing
    | in de log (melding aan de beheerder volgt via de monitoring).
    */
    'alert_threshold_per_hour' => (int) env('VAULT_ALERT_THRESHOLD_PER_HOUR', 200),

];

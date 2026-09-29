<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auditlog
    |--------------------------------------------------------------------------
    |
    | De salt maakt de hashketen onvoorspelbaar voor wie de database leest.
    | Bewaar hem buiten de code en wijzig hem nooit na de eerste regel.
    |
    */

    'salt' => env('AUDIT_HASH_SALT'),

];

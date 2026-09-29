<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Betalingen
    |--------------------------------------------------------------------------
    |
    | 'fake' toont lokaal een nep-checkout waar je zelf kiest of de betaling
    | slaagt. Op acceptatie en productie altijd 'mollie'.
    |
    */

    'payment_driver' => env('PAYMENT_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Reservering
    |--------------------------------------------------------------------------
    |
    | Zo lang houdt een onbetaalde aanmelding een plek in de provincie vast.
    |
    */

    'reservation_minutes' => (int) env('RESERVATION_MINUTES', 60),

    'default_vat_rate' => 21.0,

    /*
    |--------------------------------------------------------------------------
    | Factuurafzender
    |--------------------------------------------------------------------------
    |
    | Gegevens van de organisatie op de factuur. [PLACEHOLDERS] worden vervangen
    | zodra Bennie de bedrijfsgegevens en het boekhoudpakket heeft aangeleverd.
    |
    */

    'issuer' => [
        'name' => env('INVOICE_ISSUER_NAME', 'De Gouden Bol'),
        'address' => env('INVOICE_ISSUER_ADDRESS', '[ADRES ORGANISATIE VOLGT]'),
        'kvk' => env('INVOICE_ISSUER_KVK', '[KVK VOLGT]'),
        'vat' => env('INVOICE_ISSUER_VAT', '[BTW-NUMMER VOLGT]'),
        'email' => env('INVOICE_ISSUER_EMAIL', '[E-MAIL ADMINISTRATIE VOLGT]'),
        'footer' => env('INVOICE_ISSUER_FOOTER', 'Deze factuur is voldaan via online betaling. De omschrijving noemt de inhoud van het pakket; erkenningen en posities volgen uitsluitend uit de beoordeling.'),
    ],

];

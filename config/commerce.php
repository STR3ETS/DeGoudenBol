<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Betalingen
    |--------------------------------------------------------------------------
    |
    | 'fake' toont lokaal een nep-checkout waar je zelf kiest of de betaling
    | slaagt. Op acceptatie en productie altijd 'stripe' (besluit 29 okt 2026;
    | 'mollie' bestaat nog als alternatief). Sleutels in config/services.php.
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
    | Gegevens van de organisatie op facturen, in mails en voorwaarden (briefing
    | 29 okt §2). De Gouden Bol blijft de publieksnaam; de juridische entiteit en
    | het btw-nummer worden nog bevestigd. Aangeleverd zijn NL870123920B01 en
    | NL870123902B01; geen van beide wordt gebruikt tot het juiste is bevestigd.
    |
    */

    'issuer' => [
        'name' => env('INVOICE_ISSUER_NAME', 'De Gouden Bol'),
        'legal_name' => env('INVOICE_ISSUER_LEGAL_NAME', 'Nederlands Test Instituut'),
        'contact' => env('INVOICE_ISSUER_CONTACT', 'Betsy'),
        'address' => env('INVOICE_ISSUER_ADDRESS', 'Voorstraat 14, 6702 DC Wageningen'),
        'kvk' => env('INVOICE_ISSUER_KVK', '42193967'),
        'vat' => env('INVOICE_ISSUER_VAT', '[BTW-NUMMER WORDT BEVESTIGD]'),
        'email' => env('INVOICE_ISSUER_EMAIL', '[E-MAIL ADMINISTRATIE VOLGT]'),
        'footer' => env('INVOICE_ISSUER_FOOTER', 'Deze factuur is voldaan via online betaling. De omschrijving noemt de inhoud van het pakket; erkenningen en posities volgen uitsluitend uit de beoordeling.'),
    ],

];

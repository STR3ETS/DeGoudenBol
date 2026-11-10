<?php

use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Migrations\Migration;

/**
 * Besluiten Bennie 9 en 10 nov 2026 voor editie 2026: één deelnamepakket (krenten, naturel en ophalen) met normale
 * prijs EUR 390 excl. btw en als introductieactie de helft, EUR 195 excl. btw; landelijk maximaal 150 deelnemers, en de nieuwe prijslijst voor de
 * zichtbaarheidsplekken (introductie EUR 1.250 tot EUR 250, regulier EUR 2.250 tot EUR 650) die pas ingaan bij de
 * bekendmaking. Bestaande aanmeldingen en verkochte plekken blijven zoals ze zijn. Alles is aan te passen in de backoffice.
 */
return new class extends Migration
{
    public function up(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null || $edition->settings->bundleEnabled()) {
            return;
        }

        $edition->forceFill(['settings' => $edition->settings->with([
            'bundle_cents' => 19500,
            'bundle_regular_cents' => 39000,
            'total_capacity' => 150,
            'visibility_spot_prices_cents' => [125000, 95000, 65000, 55000, 45000, 25000, 25000, 25000, 25000, 25000],
            'visibility_spot_regular_prices_cents' => [225000, 195000, 165000, 135000, 95000, 65000, 65000, 65000, 65000, 65000],
            'visibility_spots_start_at_publication' => true,
        ])])->save();
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }
};

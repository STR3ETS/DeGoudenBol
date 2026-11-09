<?php

use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Migrations\Migration;

/**
 * Introductietarieven zichtbaarheidsplekken editie 2026 uit "Tarieven deelnamepakketten" (6 nov 2026): plek 1 tot
 * en met 10, EUR 950 tot EUR 250 excl. btw, samen EUR 6.000 per provincie. Aanpassen of uitzetten kan in de backoffice.
 */
return new class extends Migration
{
    public function up(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null || $edition->settings->visibilitySpotsEnabled()) {
            return;
        }

        $edition->forceFill(['settings' => $edition->settings->with([
            'visibility_spot_prices_cents' => [95000, 87200, 79400, 71700, 63900, 56100, 48300, 40600, 32800, 25000],
        ])])->save();
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }
};

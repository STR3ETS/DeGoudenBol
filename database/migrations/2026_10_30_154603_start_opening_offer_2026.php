<?php

use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Migrations\Migration;

/**
 * Openingsactie editie 2026 (besluit Bennie 30 okt 2026): deelname EUR 75 in plaats van EUR 150, en voor
 * EUR 50 extra ook de naturel oliebol laten testen. Bedragen excl. btw. Raakt alleen instellingen die nog
 * uit staan; stoppen gaat in de backoffice (Editie > Prijzen, korting en ophaalservice).
 */
return new class extends Migration
{
    public function up(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null) {
            return;
        }

        $changes = [];

        if (! $edition->settings->openingOfferEnabled()) {
            $changes['opening_offer_cents'] = 7500;
        }

        if (! $edition->settings->plainAddonEnabled()) {
            $changes['plain_addon_cents'] = 5000;
        }

        if ($changes !== []) {
            $edition->forceFill(['settings' => $edition->settings->with($changes)])->save();
        }
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }
};

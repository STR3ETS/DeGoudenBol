<?php

use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Migrations\Migration;

/**
 * Besluit Gill 10 nov 2026 voor editie 2026: het filmpje bij het ophalen kost EUR 350 excl. btw
 * (normaal EUR 450) en staat daarmee aan als vinkje bij de aanmelding. Aan te passen in de backoffice.
 */
return new class extends Migration
{
    public function up(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null || $edition->settings->videoAddonEnabled()) {
            return;
        }

        $edition->forceFill(['settings' => $edition->settings->with([
            'video_addon_cents' => 35000,
            'video_addon_regular_cents' => 45000,
        ])])->save();
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }
};

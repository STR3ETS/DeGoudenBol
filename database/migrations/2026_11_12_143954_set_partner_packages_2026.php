<?php

use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Migrations\Migration;

/**
 * Verzoek Bennie 12 nov 2026 voor editie 2026: drie partnerpakketten die de organisatie bij een aanmelding met
 * betaallink kan meegeven, EUR 450, EUR 350 en EUR 250 excl. btw. Alleen de naam van het eerste is genoemd
 * ("top partner"); "Partner B" en "Partner C" zijn werknamen tot Bennie de echte namen aanlevert.
 * Namen en prijzen zijn aan te passen in de backoffice bij de editie.
 */
return new class extends Migration
{
    public function up(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null || $edition->settings->partnerPackages() !== []) {
            return;
        }

        $edition->forceFill(['settings' => $edition->settings->with([
            'partner_packages' => [
                ['name' => 'Top partner', 'price_cents' => 45000],
                ['name' => 'Partner B', 'price_cents' => 35000],
                ['name' => 'Partner C', 'price_cents' => 25000],
            ],
        ])])->save();
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }
};

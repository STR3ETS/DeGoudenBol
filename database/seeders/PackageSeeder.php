<?php

namespace Database\Seeders;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Seeder;

/**
 * Deelnamemodel B (advies briefing): één basispakket voor iedereen.
 * AANNAME: prijs en inhoud volgen uit besluit 2; tot die tijd staat hier het laagste tarief
 * uit het Deelnemers- & Sponsorplan (EUR 745 excl. btw) als duidelijk gemarkeerde placeholder.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null) {
            return;
        }

        Package::query()->updateOrCreate(
            ['edition_id' => $edition->getKey(), 'code' => 'deelname-2026-basis'],
            [
                'name' => 'Deelname De Gouden Bol 2026',
                'description' => 'Blinde beoordeling door het panel, vertrouwelijk rapport met sterke punten en ontwikkelkansen, publicatie op de Voorlijst vanaf 5,0, deelnemersbadge en socialmediakit. [PRIJS EN PAKKETINHOUD VOLGEN UIT BESLUIT 2]',
                'price_cents' => 74500,
                'vat_rate' => 21.00,
                'stock_per_province' => null,
                'is_base' => true,
                'is_active' => true,
                'sort' => 1,
                'entitlements' => ['badge_participant', 'social_kit', 'confidential_report', 'press_release_region'],
            ],
        );
    }
}

<?php

namespace Database\Seeders;

use App\Domain\Commerce\Models\Package;
use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Seeder;

/**
 * Deelname 2026 volgens de briefing van 29 okt (§3): standaardprijs EUR 150. De actieprijs (50% korting
 * na goedgekeurde socialmedia-actie) is een editie-instelling. AANNAME: EUR 150 is excl. btw, zoals de
 * sponsorprijzen; open beslispunt in docs/07.
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
                'description' => Package::BRIEFING_DESCRIPTION,
                'price_cents' => Package::BRIEFING_PRICE_CENTS,
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

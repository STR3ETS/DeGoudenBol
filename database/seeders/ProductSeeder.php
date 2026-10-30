<?php

namespace Database\Seeders;

use App\Domain\Commerce\Models\Product;
use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Seeder;

/**
 * Sponsorpakketten 2026 uit de briefing van 29 okt (§8), overgenomen uit het sponsorplan.
 * Maatwerk (provinciale partner, landelijke partner, hoofdsponsor) krijgt zijn prijs per aanvraag.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null) {
            return;
        }

        foreach (Product::briefingCatalog() as $product) {
            Product::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $product['code']],
                $product,
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Domain\Commerce\Enums\AvailabilityPhase;
use App\Domain\Commerce\Enums\ProductCode;
use App\Domain\Commerce\Models\Product;
use App\Domain\Edition\Models\Edition;
use Illuminate\Database\Seeder;

/**
 * Sponsorcatalogus 2026 met de tarieven uit de briefing (besluit 18, docs/04 §9).
 * Maatwerk (partners, hoofdsponsor) krijgt zijn prijs per plaatsing.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::query()->where('year', 2026)->first();

        if ($edition === null) {
            return;
        }

        $products = [
            [ProductCode::Homepage, 'Logo op de homepage', 'Logo met link op de homepage van De Gouden Bol, gelabeld als sponsor.', 25000, null, AvailabilityPhase::Always, false, 10],
            [ProductCode::ParticipantLink, 'Koppeling bij een deelnemer', 'Het profiel van de deelnemer toont "Bakt met [sponsor]" met logo en link, na bevestiging door de deelnemer.', 5000, 2500, AvailabilityPhase::Always, false, 20],
            [ProductCode::NationalTop, 'Landelijke toppositie', 'Logo met link bij de landelijke finale en de finalisten. Pas te koop zodra de finalisten bekend zijn.', 45000, 7500, AvailabilityPhase::FinalistsKnown, false, 30],
            [ProductCode::ProvincePartner, 'Provinciepartner', 'Exclusief per provincie: logo en vermelding op de provinciepagina en in het persbericht. Prijs op maat.', 0, null, AvailabilityPhase::Always, true, 40],
            [ProductCode::Custom, 'Maatwerk (landelijk partner, hoofdsponsor, finale, goede doelen)', 'Eigen plaatsingen en periode. Prijs op maat.', 0, null, AvailabilityPhase::Always, true, 50],
        ];

        foreach ($products as [$code, $name, $description, $price, $extra, $phase, $custom, $sort]) {
            Product::query()->updateOrCreate(
                ['edition_id' => $edition->getKey(), 'code' => $code->value],
                ['name' => $name, 'description' => $description, 'price_cents' => $price, 'extra_link_price_cents' => $extra, 'vat_rate' => 21, 'available_from_phase' => $phase, 'is_custom' => $custom, 'sort' => $sort],
            );
        }
    }
}

<?php

use App\Domain\Commerce\Models\Package;
use App\Domain\Commerce\Models\Product;
use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\ScoringCriterion;
use App\Domain\Edition\Models\ScoringModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zet de gegevens van de lopende editie op de briefing van 29 okt 2026. Raakt alleen waarden die nog
 * op de oude standaard of placeholder staan, zodat handmatige keuzes in de backoffice blijven staan.
 *
 * - 20 oliebollen per inzending (was 8), landelijke Top 10 (was 5).
 * - Deelnameprijs EUR 150 als het pakket nog op de placeholder van EUR 745 stond.
 * - Sponsorpakketten met de namen en prijzen uit het sponsorplan.
 * - Beoordelingsmodel met de acht onderdelen uit het officiële rapport, als nieuwe versie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Edition::query()->whereNot('status', EditionStatus::Archived)->get()->each(function (Edition $edition): void {
            DB::transaction(function () use ($edition): void {
                $this->settings($edition);
                $this->package($edition);
                $this->products($edition);
                $this->scoringModel($edition);
            });
        });
    }

    public function down(): void
    {
        // Gegevensmigratie; terugdraaien gaat via de backoffice.
    }

    private function settings(Edition $edition): void
    {
        $changes = [];

        if ($edition->settings->piecesPerEntry === 8) {
            $changes['pieces_per_entry'] = 20;
        }

        if ($edition->settings->nationalListLength === 5) {
            $changes['national_list_length'] = 10;
        }

        if ($changes !== []) {
            $edition->forceFill(['settings' => $edition->settings->with($changes)])->save();
        }
    }

    private function package(Edition $edition): void
    {
        Package::query()
            ->where('edition_id', $edition->getKey())
            ->where('is_base', true)
            ->where('price_cents', 74500)
            ->update(['price_cents' => Package::BRIEFING_PRICE_CENTS, 'description' => Package::BRIEFING_DESCRIPTION, 'updated_at' => now()]);
    }

    private function products(Edition $edition): void
    {
        foreach (Product::briefingCatalog() as $product) {
            Product::query()->updateOrCreate(['edition_id' => $edition->getKey(), 'code' => $product['code']], $product);
        }
    }

    private function scoringModel(Edition $edition): void
    {
        $criteria = ScoringCriterion::reportCriteria();
        $active = ScoringModel::query()->where('edition_id', $edition->getKey())->where('is_active', true)->with('criteria')->first();

        $current = $active?->criteria->mapWithKeys(fn (ScoringCriterion $criterion) => [$criterion->code => $criterion->max_points])->sortKeys()->all();
        $wanted = collect($criteria)->mapWithKeys(fn (array $criterion) => [$criterion['code'] => $criterion['max_points']])->sortKeys()->all();

        if ($active !== null && $current === $wanted) {
            return;
        }

        ScoringModel::query()->where('edition_id', $edition->getKey())->update(['is_active' => false]);

        $model = ScoringModel::query()->create([
            'edition_id' => $edition->getKey(),
            'product_type' => $active->product_type ?? 'oliebol',
            'version' => (int) ScoringModel::query()->where('edition_id', $edition->getKey())->max('version') + 1,
            'name' => 'Beoordelingsrapport oliebol '.$edition->year,
            'is_active' => true,
        ]);

        foreach ($criteria as $criterion) {
            $model->criteria()->create($criterion);
        }
    }
};

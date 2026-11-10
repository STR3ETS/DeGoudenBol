<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use Illuminate\Support\Collection;

/**
 * Welke zichtbaarheidsplekken er in een provincie te koop zijn, en wie er op de site uitgelicht staat.
 */
final class VisibilitySpots
{
    /**
     * @return list<array{position: int, price_cents: int, regular_price_cents: int|null, taken: bool}>
     */
    public function options(Edition $edition, Province $province): array
    {
        $taken = VisibilitySpot::query()
            ->where('edition_id', $edition->getKey())
            ->where('province_id', $province->getKey())
            ->taken()
            ->pluck('position')
            ->all();

        $options = [];

        foreach (array_values($edition->settings->visibilitySpotPricesCents) as $index => $price) {
            $options[] = [
                'position' => $index + 1,
                'price_cents' => (int) $price,
                'regular_price_cents' => $edition->settings->visibilitySpotRegularPrice($index + 1),
                'taken' => in_array($index + 1, $taken, true),
            ];
        }

        return $options;
    }

    /**
     * @return Collection<int, VisibilitySpot>
     */
    public function live(Edition $edition, Province $province): Collection
    {
        return VisibilitySpot::query()
            ->where('edition_id', $edition->getKey())
            ->where('province_id', $province->getKey())
            ->live()
            ->with(['entry', 'company.primaryLocation'])
            ->orderBy('position')
            ->get();
    }
}

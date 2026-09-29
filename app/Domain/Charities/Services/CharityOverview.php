<?php

namespace App\Domain\Charities\Services;

use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use Illuminate\Support\Collection;

/**
 * Transparantie-overzicht voor /goede-doelen: per doel grondslag, bedrag, selectie en uitbetaling;
 * daarnaast de regionale potten en de totalen.
 *
 * @phpstan-type CharityRow array{charity: Charity, basis_cents: int, reserved_cents: int, paid_cents: int, last_payout: \Carbon\CarbonImmutable|null}
 */
final class CharityOverview
{
    /**
     * @return array{charities: Collection<int, CharityRow>, pots: Collection<int, array{province: Province|null, amount_cents: int}>, reserved_cents: int, paid_cents: int, percentage: int}
     */
    public function forEdition(Edition $edition): array
    {
        $charities = Charity::query()
            ->where('edition_id', $edition->getKey())
            ->public()
            ->with(['province', 'payouts'])
            ->withSum('reservations as reserved_cents', 'amount_cents')
            ->withSum('reservations as basis_cents', 'basis_cents')
            ->orderBy('name')
            ->get()
            ->map(fn (Charity $charity) => [
                'charity' => $charity,
                'basis_cents' => (int) $charity->basis_cents,
                'reserved_cents' => (int) $charity->reserved_cents,
                'paid_cents' => (int) $charity->payouts->sum('amount_cents'),
                'last_payout' => $charity->payouts->first()?->paid_at,
            ]);

        $potSums = CharityReservation::query()
            ->where('edition_id', $edition->getKey())
            ->whereNull('charity_id')
            ->selectRaw('regional_pot_province_id, SUM(amount_cents) as total')
            ->groupBy('regional_pot_province_id')
            ->pluck('total', 'regional_pot_province_id');

        $provinces = Province::query()->orderBy('sort')->get()->keyBy('id');

        $pots = $potSums
            ->map(fn ($total, $provinceId) => ['province' => $provinceId ? $provinces->get((int) $provinceId) : null, 'amount_cents' => (int) $total])
            ->values()
            ->sortBy(fn (array $pot) => $pot['province']?->sort ?? 99)
            ->values();

        return [
            'charities' => $charities,
            'pots' => $pots,
            'reserved_cents' => (int) CharityReservation::query()->where('edition_id', $edition->getKey())->sum('amount_cents'),
            'paid_cents' => (int) $charities->sum('paid_cents'),
            'percentage' => $edition->settings->charityPercentage,
        ];
    }
}

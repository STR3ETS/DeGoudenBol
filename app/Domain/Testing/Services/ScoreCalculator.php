<?php

namespace App\Domain\Testing\Services;

use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Edition\Settings\EditionSettings;
use App\Domain\Testing\Data\ScoreResult;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ScoreCorrection;
use Illuminate\Support\Collection;

/**
 * Pure scoreberekening (docs/04 §2). Dezelfde kaarten, hetzelfde model en dezelfde
 * instellingen geven altijd hetzelfde resultaat.
 *
 *   gemiddelde_k = (1/n) · Σ s(i,k)      totaal_ruw = Σ gemiddelde_k      cijfer = round(totaal_ruw / 10, 1)
 */
final class ScoreCalculator
{
    /**
     * @param  Collection<int, Scorecard>  $scorecards  Kaarten van één monster; ongeldige kaarten worden genegeerd,
     *                                                  goedgekeurde correcties toegepast.
     */
    public function calculate(Collection $scorecards, ScoringModel $model, EditionSettings $settings): ScoreResult
    {
        $criteria = $model->criteria->pluck('max_points', 'code')->all();
        $cards = $scorecards->filter(fn (Scorecard $card) => $card->is_valid)->values();
        $n = $cards->count();

        $effective = $cards->map(fn (Scorecard $card) => $this->effectiveScores($card, array_keys($criteria)));

        $averages = [];

        foreach (array_keys($criteria) as $code) {
            $averages[$code] = $n === 0 ? 0.0 : round($effective->sum(fn (array $scores) => $scores[$code]) / $n, 4);
        }

        $totalRaw = round(array_sum($averages), 4);
        $total = $this->roundHalfUp($totalRaw / 10, $settings->roundingDecimals);

        return new ScoreResult(
            cardCount: $n,
            minValidCards: $settings->minValidCards,
            criterionAverages: $averages,
            totalRaw: $totalRaw,
            total: $total,
            outlierCardUuids: $this->outliers($cards, $effective, $settings->outlierDeviationPoints),
            scoringModelVersion: $model->version,
        );
    }

    /**
     * Scores van een kaart, met de laatst goedgekeurde correctie erin verwerkt.
     *
     * @param  list<string>  $codes
     * @return array<string, int>
     */
    public function effectiveScores(Scorecard $card, array $codes): array
    {
        $scores = $card->scores;

        $correction = $card->relationLoaded('corrections')
            ? $card->corrections->filter(fn (ScoreCorrection $c) => $c->isApproved())->sortByDesc('approved_at')->first()
            : $card->corrections()->where('status', 'approved')->orderByDesc('approved_at')->first();

        if ($correction !== null) {
            $scores = [...$scores, ...$correction->after];
        }

        $result = [];

        foreach ($codes as $code) {
            $result[$code] = (int) ($scores[$code] ?? 0);
        }

        return $result;
    }

    /**
     * Kaarten waarvan het totaal meer dan de toegestane afwijking van de mediaan ligt.
     *
     * @param  Collection<int, Scorecard>  $cards
     * @param  Collection<int, array<string, int>>  $effective
     * @return list<string>
     */
    private function outliers(Collection $cards, Collection $effective, int $maxDeviation): array
    {
        if ($cards->count() < 3) {
            return [];
        }

        $totals = $effective->map(fn (array $scores) => array_sum($scores))->values();
        $sorted = $totals->sort()->values();
        $count = $sorted->count();
        $median = $count % 2 === 1
            ? $sorted[intdiv($count, 2)]
            : ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;

        $outliers = [];

        foreach ($totals as $index => $total) {
            if (abs($total - $median) > $maxDeviation) {
                $outliers[] = $cards[$index]->uuid;
            }
        }

        return $outliers;
    }

    /**
     * Afronding half-up op het gevraagde aantal decimalen, zonder drijvende-komma-verrassingen.
     */
    private function roundHalfUp(float $value, int $decimals): float
    {
        $factor = 10 ** $decimals;

        return floor($value * $factor + 0.5 + 1e-9) / $factor;
    }
}

<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\ScoringModel;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Models\Result;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Services\ScoreCalculator;
use RuntimeException;

/**
 * Berekent de voorlopige uitslag van een monster opnieuw uit alle geldige kaarten.
 * Een definitieve uitslag wordt nooit stilzwijgend overschreven.
 */
final class RecalculateResult
{
    public function __construct(private readonly ScoreCalculator $calculator) {}

    public function __invoke(Sample $sample, bool $force = false): Result
    {
        $existing = $sample->result;

        if ($existing?->isFinal() && ! $force) {
            return $existing;
        }

        $edition = Edition::query()->findOrFail($sample->edition_id);
        $model = ScoringModel::query()->where('edition_id', $edition->getKey())->where('is_active', true)->with('criteria')->first()
            ?? throw new RuntimeException('Geen actief beoordelingsmodel voor deze editie.');

        $cards = $sample->scorecards()->with('corrections')->get();
        $score = $this->calculator->calculate($cards, $model, $edition->settings);

        $result = Result::query()->updateOrCreate(
            ['sample_id' => $sample->getKey()],
            [
                'scoring_model_version' => $score->scoringModelVersion,
                'card_count' => $score->cardCount,
                'criterion_averages' => $score->criterionAverages,
                'total_raw' => $score->totalRaw,
                'total' => $score->total,
                'flags' => $score->flags(),
                'status' => $existing?->status ?? ResultStatus::Pending,
                'computed_at' => now(),
            ],
        );

        $sample->setRelation('result', $result);

        return $result;
    }
}

<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Data\RankedPosition;
use App\Domain\Ranking\Data\RankingSnapshotData;
use App\Domain\Ranking\Engine\RankingEngineV2026;
use App\Domain\Ranking\Enums\SnapshotStatus;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Domain\Ranking\Services\RankingInputs;
use App\Domain\Testing\Enums\SampleRound;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Berekent een lijst opnieuw uit de gepubliceerde uitslagen en bewaart een snapshot.
 * Provinciaal: houdt beslisrondes bij voor gedeelde plaatsen op plaats 1 of de Top 10-grens.
 * Landelijk (finale): eigen scope, eigen lijstlengte, provinciale scores tellen niet mee.
 */
final class RecomputeRanking
{
    public function __construct(
        private readonly RankingInputs $inputs,
        private readonly RankingEngineV2026 $engine,
    ) {}

    public function __invoke(Edition $edition, int $provinceId, ?PublicationBatch $batch = null, SnapshotStatus $status = SnapshotStatus::Provisional, ?CarbonInterface $publishedAt = null, bool $publish = true): RankingSnapshot
    {
        $data = $this->provincial($edition, $provinceId);

        $snapshot = $this->store($edition, "province:{$provinceId}", $provinceId, SampleRound::Provincial, $data, $batch, $status, $publish ? ($publishedAt ?? now()) : null);

        $this->syncTieBreakRounds($edition, $provinceId, $data);

        return $snapshot;
    }

    /**
     * Berekent de provinciale lijst zonder hem op te slaan (voor controle en bevriezing).
     */
    public function provincial(Edition $edition, int $provinceId): RankingSnapshotData
    {
        $previous = RankingSnapshot::latestForProvince($edition->getKey(), $provinceId);
        $previousPositions = $previous?->positions()->pluck('position', 'entry_id')->all() ?? [];

        return $this->engine->compute(
            $this->inputs->forProvince($edition, $provinceId),
            $edition->settings,
            $previousPositions,
            $this->inputs->resolvedOrdersFor($edition, $provinceId),
        );
    }

    public function national(Edition $edition, ?PublicationBatch $batch = null, SnapshotStatus $status = SnapshotStatus::Provisional): RankingSnapshot
    {
        $previous = RankingSnapshot::latestNational($edition->getKey());
        $previousPositions = $previous?->positions()->pluck('position', 'entry_id')->all() ?? [];

        $data = $this->engine->compute(
            $this->inputs->forNational($edition),
            $edition->settings,
            $previousPositions,
            [],
            $edition->settings->nationalListLength,
        );

        return $this->store($edition, 'national', null, SampleRound::Final, $data, $batch, $status, now());
    }

    private function store(Edition $edition, string $scope, ?int $provinceId, SampleRound $round, RankingSnapshotData $data, ?PublicationBatch $batch, SnapshotStatus $status, ?CarbonInterface $publishedAt): RankingSnapshot
    {
        return DB::transaction(function () use ($edition, $scope, $provinceId, $round, $data, $batch, $status, $publishedAt): RankingSnapshot {
            $snapshot = RankingSnapshot::query()->create([
                'edition_id' => $edition->getKey(),
                'province_id' => $provinceId,
                'scope' => $scope,
                'round' => $round->value,
                'engine_version' => $data->engineVersion,
                'status' => $status,
                'input_hash' => $data->inputHash,
                'publication_batch_id' => $batch?->getKey(),
                'computed_at' => now(),
                'published_at' => $publishedAt,
            ]);

            foreach ($data->positions as $position) {
                $snapshot->positions()->create([
                    'entry_id' => $position->entryId,
                    'position' => $position->position,
                    'total' => $position->total,
                    'tie_group' => $position->tieGroup,
                    'label' => $position->label,
                    'needs_tie_break' => $position->needsTieBreak,
                ]);
            }

            return $snapshot;
        });
    }

    /**
     * Voor iedere groep die een beslissende beoordeling nodig heeft bestaat precies één open beslisronde.
     */
    private function syncTieBreakRounds(Edition $edition, int $provinceId, RankingSnapshotData $data): void
    {
        $groups = collect($data->positions)
            ->filter(fn (RankedPosition $position) => $position->needsTieBreak)
            ->groupBy(fn (RankedPosition $position) => $position->position);

        foreach ($groups as $position => $members) {
            $entryIds = $members->map(fn (RankedPosition $p) => $p->entryId)->sort()->values()->all();

            TieBreakRound::query()->firstOrCreate(
                ['edition_id' => $edition->getKey(), 'province_id' => $provinceId, 'key' => TieBreakRound::keyFor((int) $position, $entryIds)],
                [
                    'scope' => (int) $position === 1 ? 'position_1' : 'top10_boundary',
                    'position' => (int) $position,
                    'entry_ids' => $entryIds,
                    'status' => TieBreakStatus::Open,
                ],
            );
        }
    }
}

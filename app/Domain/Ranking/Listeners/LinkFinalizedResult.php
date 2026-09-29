<?php

namespace App\Domain\Ranking\Listeners;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Actions\RecomputeRanking;
use App\Domain\Ranking\Data\RankedEntry;
use App\Domain\Ranking\Engine\RankingEngineV2026;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Enums\ReportStatus;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\ConfidentialReport;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Domain\Ranking\Services\PublicationSchedule;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Events\ResultFinalized;
use App\Domain\Testing\Models\Result;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Vault\Services\VaultService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stap 8 uit het kernproces: na de definitieve score koppelt het systeem (gelogd, via de kluis) het
 * testnummer aan de inschrijving. Provinciale ronde: publicatie-item in de open batch en een
 * conceptrapport. Beslisronde: uitkomst in de beslisronde, daarna de lijst opnieuw berekend.
 * Finale: publicatie-item voor de landelijke lijst.
 */
final class LinkFinalizedResult
{
    public function __construct(
        private readonly VaultService $vault,
        private readonly PublicationSchedule $schedule,
        private readonly RankingEngineV2026 $engine,
        private readonly RecomputeRanking $recompute,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(ResultFinalized $event): void
    {
        $result = $event->result->loadMissing('sample');
        $sample = $result->sample;

        $entryUlid = $this->vault->asSystem(fn () => $this->vault->entryUlidForSample($sample->getKey(), 'koppeling na definitieve score'));

        if ($entryUlid === null) {
            Log::warning('Definitieve uitslag zonder kluiskoppeling', ['sample' => $sample->ulid]);

            return;
        }

        $entry = Entry::query()->where('ulid', $entryUlid)->with('edition')->first();

        if ($entry === null || ! $entry->status->occupiesPlace()) {
            return;
        }

        match ($sample->round) {
            SampleRound::Provincial => $this->linkProvincial($entry, $result, $sample),
            SampleRound::TieBreak => $this->recordTieBreak($entry, $result),
            SampleRound::Final => $this->linkFinal($entry, $result),
        };
    }

    private function linkProvincial(Entry $entry, Result $result, Sample $sample): void
    {
        if ($entry->published_at !== null) {
            // Na publicatie verandert een uitslag alleen via een correctiedossier met twee goedkeuringen.
            Log::info('Herberekende uitslag na publicatie genegeerd; correctiedossier vereist', ['entry' => $entry->ulid]);

            return;
        }

        /** @var Edition $edition */
        $edition = $entry->edition;
        $visibility = $this->visibility($edition, $result);
        $notes = Scorecard::query()->where('sample_id', $sample->getKey())->where('is_valid', true)->get(['strengths', 'opportunities']);

        DB::transaction(function () use ($entry, $edition, $result, $visibility, $notes): void {
            $batch = $this->schedule->openBatch($edition);

            PublicationItem::query()->updateOrCreate(
                ['publication_batch_id' => $batch->getKey(), 'entry_id' => $entry->getKey()],
                [
                    'province_id' => $entry->province_id,
                    'round' => SampleRound::Provincial,
                    'visibility' => $visibility,
                    'result_snapshot' => $this->snapshot($result),
                ],
            );

            $entry->forceFill(['status' => EntryStatus::Linked, 'linked_at' => now()])->save();

            // Eerste concept van het vertrouwelijke rapport uit de geanonimiseerde panelnotities.
            ConfidentialReport::query()->firstOrCreate(
                ['entry_id' => $entry->getKey()],
                [
                    'strengths' => $this->lines($notes->pluck('strengths')),
                    'opportunities' => $this->lines($notes->pluck('opportunities')),
                    'status' => ReportStatus::Draft,
                ],
            );

            $this->audit->record('entry.linked', $entry, ['batch' => $batch->getKey(), 'visibility' => $visibility->value], null);
        });
    }

    /**
     * Beslisronde: alleen de onderlinge volgorde van de gelijke deelnemers; het gepubliceerde cijfer blijft staan.
     */
    private function recordTieBreak(Entry $entry, Result $result): void
    {
        $round = TieBreakRound::query()
            ->where('edition_id', $entry->edition_id)
            ->where('province_id', $entry->province_id)
            ->where('status', TieBreakStatus::Open)
            ->get()
            ->first(fn (TieBreakRound $round) => in_array($entry->getKey(), array_map('intval', $round->entry_ids), true));

        if ($round === null) {
            Log::warning('Beslisronde-uitslag zonder open beslisronde', ['entry' => $entry->ulid]);

            return;
        }

        $scores = $round->scores ?? [];
        $scores[(string) $entry->getKey()] = ['total_raw' => $result->total_raw, 'criterion_averages' => $result->criterion_averages];
        $round->forceFill(['scores' => $scores])->save();

        $missing = array_diff(array_map('intval', $round->entry_ids), array_map('intval', array_keys($scores)));

        if ($missing !== []) {
            return;
        }

        $ranked = array_map(fn (int $id) => new RankedEntry($id, (float) $scores[(string) $id]['total_raw'], array_map('floatval', $scores[(string) $id]['criterion_averages'] ?? [])), array_map('intval', $round->entry_ids));

        $round->forceFill([
            'outcome_order' => $this->engine->order($ranked, $entry->edition->settings),
            'status' => TieBreakStatus::Decided,
            'decided_at' => now(),
        ])->save();

        $this->audit->record('tie_break.decided', $round, ['order' => $round->outcome_order], null);

        ($this->recompute)($entry->edition, $entry->province_id);
    }

    private function linkFinal(Entry $entry, Result $result): void
    {
        $participates = Finalist::query()->where('edition_id', $entry->edition_id)->where('entry_id', $entry->getKey())->participating()->exists();

        if (! $participates) {
            Log::warning('Finale-uitslag voor een inschrijving die geen finalist is', ['entry' => $entry->ulid]);

            return;
        }

        /** @var Edition $edition */
        $edition = $entry->edition;
        $visibility = $this->visibility($edition, $result);

        DB::transaction(function () use ($entry, $edition, $result, $visibility): void {
            $batch = $this->schedule->openBatch($edition);

            PublicationItem::query()->updateOrCreate(
                ['publication_batch_id' => $batch->getKey(), 'entry_id' => $entry->getKey()],
                [
                    'province_id' => $entry->province_id,
                    'round' => SampleRound::Final,
                    'visibility' => $visibility,
                    'result_snapshot' => $this->snapshot($result),
                ],
            );

            $this->audit->record('entry.final_linked', $entry, ['batch' => $batch->getKey()], null);
        });
    }

    private function visibility(Edition $edition, Result $result): ItemVisibility
    {
        return $result->total >= $edition->settings->publishThreshold ? ItemVisibility::Public : ItemVisibility::Confidential;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Result $result): array
    {
        return [
            'total' => $result->total,
            'total_raw' => $result->total_raw,
            'card_count' => $result->card_count,
            'criterion_averages' => $result->criterion_averages,
            'scoring_model_version' => $result->scoring_model_version,
        ];
    }

    /**
     * @param  Collection<int, string|null>  $notes
     */
    private function lines(Collection $notes): ?string
    {
        $clean = $notes->filter(fn (?string $note) => filled($note))->map(fn (string $note) => '- '.trim($note))->unique()->values();

        return $clean->isEmpty() ? null : $clean->implode("\n");
    }
}

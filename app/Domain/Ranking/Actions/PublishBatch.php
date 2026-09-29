<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Events\BatchPublished;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Ranking\Notifications\ConfidentialResultNotification;
use App\Domain\Ranking\Notifications\ResultPublishedNotification;
use App\Models\User;
use App\Support\PublicCache;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Stap 9 uit het kernproces: statussen → ranking herberekend (provinciaal en/of landelijk) →
 * snapshot → cache-invalidatie → BatchPublished (erkenningen ontstaan in Marketing) → notificatie.
 */
final class PublishBatch
{
    public function __construct(
        private readonly RecomputeRanking $recompute,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(PublicationBatch $batch, ?User $by = null): PublicationBatch
    {
        $batch = DB::transaction(function () use ($batch, $by): PublicationBatch {
            $batch = PublicationBatch::query()->lockForUpdate()->with(['items.entry', 'edition'])->findOrFail($batch->getKey());

            if ($batch->status !== BatchStatus::Approved) {
                throw new LogicException('Alleen een goedgekeurde batch (twee goedkeuringen) kan worden gepubliceerd.');
            }

            $edition = $batch->edition;
            $now = now();

            foreach ($batch->items as $item) {
                $entry = $item->entry;

                if ($item->isFinal() || ! $entry->status->occupiesPlace()) {
                    continue;
                }

                $entry->forceFill([
                    'status' => $item->isPublic() ? EntryStatus::Published : EntryStatus::Confidential,
                    'published_at' => $now,
                ])->save();
            }

            $batch->forceFill(['status' => BatchStatus::Published, 'published_at' => $now])->save();

            $provincial = $batch->items->reject(fn (PublicationItem $item) => $item->isFinal());

            foreach ($provincial->pluck('province_id')->unique() as $provinceId) {
                ($this->recompute)($edition, (int) $provinceId, $batch);
                PublicCache::forgetProvince($edition->getKey(), (int) $provinceId);
            }

            if ($batch->items->contains(fn (PublicationItem $item) => $item->isFinal())) {
                $this->recompute->national($edition, $batch);
                PublicCache::forgetEdition($edition->getKey());
            }

            $this->audit->record('publication_batch.published', $batch, ['items' => $batch->items->count()], $by);

            return $batch;
        });

        BatchPublished::dispatch($batch);

        $this->notify($batch);

        return $batch;
    }

    private function notify(PublicationBatch $batch): void
    {
        foreach ($batch->items()->with(['entry.company.users', 'province'])->get() as $item) {
            $owners = $item->entry->company->users->filter(fn ($user) => $user->pivot->role === CompanyUserRole::Owner);

            if ($owners->isEmpty()) {
                continue;
            }

            if (! $item->isPublic()) {
                $owners->each->notify(new ConfidentialResultNotification($item->entry));

                continue;
            }

            $snapshot = $item->isFinal()
                ? RankingSnapshot::latestNational($batch->edition_id)
                : RankingSnapshot::latestForProvince($batch->edition_id, $item->province_id);

            $position = $snapshot?->positions()->where('entry_id', $item->entry_id)->first();
            $owners->each->notify(new ResultPublishedNotification($item, $position));
        }
    }
}

<?php

namespace App\Domain\Ranking\Services;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Ranking\Data\RankedEntry;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Enums\TieBreakStatus;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Models\TieBreakRound;
use App\Domain\Testing\Enums\SampleRound;
use Illuminate\Support\Collection;

/**
 * Verzamelt de invoer voor de rankingengine: per gepubliceerde deelnemer de laatst gepubliceerde
 * uitslag. Betaalstatus en sponsoring komen hier nooit langs.
 */
final class RankingInputs
{
    /**
     * @return list<RankedEntry>
     */
    public function forProvince(Edition $edition, int $provinceId): array
    {
        $items = $this->latestPublishedItems($edition, SampleRound::Provincial)->where('province_id', $provinceId);

        $published = Entry::query()
            ->whereIn('id', $items->pluck('entry_id'))
            ->where('status', EntryStatus::Published)
            ->pluck('id')
            ->all();

        return $items
            ->filter(fn (PublicationItem $item) => in_array($item->entry_id, $published, true))
            ->map(fn (PublicationItem $item) => $this->rankedEntry($item))
            ->values()
            ->all();
    }

    /**
     * Landelijke finale: alleen finalisten die meedoen, met hun finale-uitslag (provinciale scores tellen niet mee).
     *
     * @return list<RankedEntry>
     */
    public function forNational(Edition $edition): array
    {
        $finalists = Finalist::query()->where('edition_id', $edition->getKey())->participating()->pluck('entry_id')->all();

        return $this->latestPublishedItems($edition, SampleRound::Final)
            ->filter(fn (PublicationItem $item) => in_array($item->entry_id, $finalists, true))
            ->map(fn (PublicationItem $item) => $this->rankedEntry($item))
            ->values()
            ->all();
    }

    /**
     * Besliste beslisrondes van een provincie: entry_ids in vastgestelde volgorde.
     *
     * @return list<list<int>>
     */
    public function resolvedOrdersFor(Edition $edition, int $provinceId): array
    {
        return TieBreakRound::query()
            ->where('edition_id', $edition->getKey())
            ->where('province_id', $provinceId)
            ->where('status', TieBreakStatus::Decided)
            ->get()
            ->map(fn (TieBreakRound $round) => array_values(array_map('intval', $round->outcome_order ?? [])))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, PublicationItem>
     */
    private function latestPublishedItems(Edition $edition, SampleRound $round): Collection
    {
        return PublicationItem::query()
            ->where('round', $round->value)
            ->where('visibility', ItemVisibility::Public)
            ->whereHas('batch', fn ($query) => $query->where('edition_id', $edition->getKey())->where('status', BatchStatus::Published))
            ->with('batch')
            ->get()
            ->sortByDesc(fn (PublicationItem $item) => [$item->batch->published_at?->timestamp ?? 0, $item->getKey()])
            ->unique('entry_id');
    }

    private function rankedEntry(PublicationItem $item): RankedEntry
    {
        return new RankedEntry(
            entryId: $item->entry_id,
            total: (float) ($item->result_snapshot['total'] ?? 0),
            criterionAverages: array_map('floatval', $item->result_snapshot['criterion_averages'] ?? []),
        );
    }
}

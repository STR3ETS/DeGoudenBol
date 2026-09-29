<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Enums\Milestone;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Models\Entry;
use App\Domain\Vouchers\Enums\CampaignStatus;
use App\Domain\Vouchers\Models\VoucherCampaign;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Welke mijlpalen een deelnemer heeft bereikt, en welke nog onder embargo staan.
 *
 * @phpstan-type Reached array{milestone: Milestone, recognition: Recognition|null, available_from: CarbonImmutable|null}
 */
final class MilestoneResolver
{
    /**
     * @return Collection<int, array{milestone: Milestone, recognition: Recognition|null, available_from: CarbonImmutable|null}>
     */
    public function forEntry(Entry $entry): Collection
    {
        $entry->loadMissing(['recognitions.edition', 'recognitions.province', 'recognitions.company', 'finalist', 'edition']);
        $reached = collect();

        foreach ($entry->recognitions->filter(fn (Recognition $r) => ! $r->isRevoked()) as $recognition) {
            $reached->push([
                'milestone' => $recognition->milestone(),
                'recognition' => $recognition,
                'available_from' => $recognition->isEmbargoed() ? $recognition->embargo_until : null,
            ]);
        }

        $finalist = $entry->finalist;

        if ($finalist !== null && $finalist->status->participates()) {
            $pivot = $entry->edition->provinces()->whereKey($entry->province_id)->first()?->pivot;
            $revealedAt = $pivot?->revealed_at;
            $revealAt = $pivot?->reveal_at ?? $entry->edition->main_publication_at;

            $reached->push([
                'milestone' => Milestone::Finalist,
                'recognition' => null,
                'available_from' => $revealedAt !== null ? null : $revealAt,
            ]);
        }

        $campaign = VoucherCampaign::query()->where('entry_id', $entry->getKey())->whereIn('status', [CampaignStatus::Draft, CampaignStatus::Open, CampaignStatus::Closed])->first();

        if ($campaign !== null) {
            $reached->push([
                'milestone' => Milestone::VoucherStart,
                'recognition' => null,
                'available_from' => $campaign->status === CampaignStatus::Draft ? $campaign->starts_at : null,
            ]);
        }

        return $reached
            ->sortBy(fn (array $item) => array_search($item['milestone'], Milestone::cases(), true))
            ->values();
    }

    public function isReached(Entry $entry, Milestone $milestone): bool
    {
        return $this->forEntry($entry)->contains(fn (array $item) => $item['milestone'] === $milestone && $item['available_from'] === null);
    }
}

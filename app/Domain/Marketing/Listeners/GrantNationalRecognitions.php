<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Ranking\Events\BatchPublished;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Domain\Testing\Enums\SampleRound;

/**
 * Na publicatie van finale-uitslagen: erkenningen landelijke lijst en landelijke winnaar.
 */
final class GrantNationalRecognitions
{
    public function handle(BatchPublished $event): void
    {
        $batch = $event->batch;

        if (! $batch->items()->where('round', SampleRound::Final->value)->exists()) {
            return;
        }

        $snapshot = RankingSnapshot::latestNational($batch->edition_id);

        if ($snapshot === null) {
            return;
        }

        $listLength = $batch->edition->settings->nationalListLength;

        foreach ($snapshot->positions()->with('entry')->get() as $position) {
            if ($position->position > $listLength) {
                continue;
            }

            $types = [RecognitionType::NationalList];

            if ($position->position === 1) {
                $types[] = RecognitionType::NationalWinner;
            }

            foreach ($types as $type) {
                Recognition::query()->firstOrCreate(
                    ['entry_id' => $position->entry_id, 'type' => $type],
                    [
                        'company_id' => $position->entry->company_id,
                        'edition_id' => $batch->edition_id,
                        'province_id' => $position->entry->province_id,
                        'status' => 'active',
                        'valid_from' => now()->toDateString(),
                        'valid_until' => null,
                    ],
                );
            }
        }
    }
}

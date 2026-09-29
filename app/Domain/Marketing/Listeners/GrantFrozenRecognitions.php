<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Models\RankingSnapshot;

/**
 * Na de bevriezing: erkenningen Top 10 en provinciewinnaar, onder embargo tot de reveal van de provincie.
 */
final class GrantFrozenRecognitions
{
    public function handle(EditionFrozen $event): void
    {
        $edition = $event->edition->loadMissing('provinces');
        $listLength = $edition->settings->provincialListLength;

        foreach ($edition->provinces as $province) {
            $snapshot = RankingSnapshot::frozenForProvince($edition->getKey(), $province->getKey());

            if ($snapshot === null) {
                continue;
            }

            $embargo = $province->pivot->reveal_at ?? $edition->main_publication_at;

            foreach ($snapshot->positions()->with('entry')->get() as $position) {
                if ($position->position > $listLength) {
                    continue;
                }

                $types = [RecognitionType::Top10];

                if ($position->position === 1) {
                    $types[] = RecognitionType::ProvinceWinner;
                }

                foreach ($types as $type) {
                    Recognition::query()->firstOrCreate(
                        ['entry_id' => $position->entry_id, 'type' => $type],
                        [
                            'company_id' => $position->entry->company_id,
                            'edition_id' => $edition->getKey(),
                            'province_id' => $province->getKey(),
                            'status' => 'active',
                            'valid_from' => ($embargo ?? now())->toDateString(),
                            'valid_until' => null,
                            'embargo_until' => $embargo,
                        ],
                    );
                }
            }
        }
    }
}

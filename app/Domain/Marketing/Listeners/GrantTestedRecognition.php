<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Ranking\Events\BatchPublished;

/**
 * Erkenning "Officieel getest" voor iedere openbaar gepubliceerde uitslag (docs/04 §6).
 * Badge-assets en socialkits volgen in release 3.
 */
final class GrantTestedRecognition
{
    public function handle(BatchPublished $event): void
    {
        $batch = $event->batch->loadMissing('items.entry');

        foreach ($batch->items as $item) {
            if (! $item->isPublic() || ! $item->entry->status->isPubliclyVisible()) {
                continue;
            }

            Recognition::query()->firstOrCreate(
                ['entry_id' => $item->entry_id, 'type' => RecognitionType::Tested],
                [
                    'company_id' => $item->entry->company_id,
                    'edition_id' => $batch->edition_id,
                    'province_id' => $item->province_id,
                    'status' => 'active',
                    'valid_from' => now()->toDateString(),
                    'valid_until' => null,
                ],
            );
        }
    }
}

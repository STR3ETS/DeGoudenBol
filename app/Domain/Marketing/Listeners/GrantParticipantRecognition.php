<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Enums\RecognitionType;
use App\Domain\Marketing\Models\Recognition;
use App\Domain\Participants\Events\EntryRegistered;

/**
 * Bevestigde deelname (betaald) → erkenning "Deelnemer", geldig tot de hoofdpublicatie (besluit 12).
 */
final class GrantParticipantRecognition
{
    public function handle(EntryRegistered $event): void
    {
        $entry = $event->entry->loadMissing('edition');

        Recognition::query()->firstOrCreate(
            ['entry_id' => $entry->getKey(), 'type' => RecognitionType::Participant],
            [
                'company_id' => $entry->company_id,
                'edition_id' => $entry->edition_id,
                'province_id' => $entry->province_id,
                'status' => 'active',
                'valid_from' => now()->toDateString(),
                'valid_until' => $entry->edition->main_publication_at?->toDateString(),
            ],
        );
    }
}

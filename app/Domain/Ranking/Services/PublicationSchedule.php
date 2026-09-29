<?php

namespace App\Domain\Ranking\Services;

use App\Domain\Edition\Models\Edition;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;

/**
 * Publicatieritme (besluit 20): vaste batches op de ingestelde dagen en tijd; in de stille periode
 * schuift alles door naar de hoofdpublicatie.
 */
final class PublicationSchedule
{
    public function nextSlot(Edition $edition, ?CarbonImmutable $after = null): CarbonImmutable
    {
        $settings = $edition->settings;
        $now = DutchTime::display($after ?? now());
        [$hour, $minute] = array_map('intval', explode(':', $settings->publicationTime.':00'));
        $days = array_map('strtolower', $settings->publicationDays);

        $candidate = null;

        for ($offset = 0; $offset <= 14; $offset++) {
            $day = $now->addDays($offset);

            if (! in_array(strtolower($day->englishDayOfWeek), $days, true)) {
                continue;
            }

            $slot = $day->setTime($hour, $minute);

            if ($slot->greaterThan($now)) {
                $candidate = $slot;
                break;
            }
        }

        $candidate ??= $now->addDay()->setTime($hour, $minute);

        if ($settings->quietPeriodFrom !== null && $edition->main_publication_at !== null) {
            $quietFrom = CarbonImmutable::parse($settings->quietPeriodFrom, DutchTime::zone())->startOfDay();

            if ($candidate->greaterThanOrEqualTo($quietFrom) && $edition->main_publication_at->greaterThan($now->utc())) {
                return $edition->main_publication_at;
            }
        }

        return $candidate->utc();
    }

    /**
     * De batch in opbouw voor deze editie; bestaat er geen, dan ontstaat er een voor het volgende slot.
     */
    public function openBatch(Edition $edition): PublicationBatch
    {
        $batch = PublicationBatch::query()->where('edition_id', $edition->getKey())->where('status', BatchStatus::Draft)->orderBy('scheduled_at')->first();

        if ($batch === null) {
            return PublicationBatch::query()->create([
                'edition_id' => $edition->getKey(),
                'scheduled_at' => $this->nextSlot($edition),
                'status' => BatchStatus::Draft,
            ]);
        }

        if ($batch->scheduled_at->isPast()) {
            $batch->forceFill(['scheduled_at' => $this->nextSlot($edition)])->save();
        }

        return $batch;
    }
}

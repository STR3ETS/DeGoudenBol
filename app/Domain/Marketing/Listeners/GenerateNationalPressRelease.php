<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Actions\GeneratePressReleases;
use App\Domain\Ranking\Events\BatchPublished;
use App\Domain\Ranking\Models\PublicationItem;

/**
 * Zodra een batch met finale-uitslagen is gepubliceerd, ontstaat het landelijke persbericht.
 */
final class GenerateNationalPressRelease
{
    public function __construct(private readonly GeneratePressReleases $generate) {}

    public function handle(BatchPublished $event): void
    {
        $batch = $event->batch;

        if (! $batch->items()->get()->contains(fn (PublicationItem $item) => $item->isFinal())) {
            return;
        }

        ($this->generate)($batch->edition);
    }
}

<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Actions\GeneratePressReleases;
use App\Domain\Ranking\Events\EditionFrozen;

final class GeneratePressReleasesOnFreeze
{
    public function __construct(private readonly GeneratePressReleases $generate) {}

    public function handle(EditionFrozen $event): void
    {
        ($this->generate)($event->edition);
    }
}

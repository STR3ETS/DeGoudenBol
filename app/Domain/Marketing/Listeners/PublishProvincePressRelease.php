<?php

namespace App\Domain\Marketing\Listeners;

use App\Domain\Marketing\Enums\PressMilestone;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Ranking\Events\ProvinceRevealed;

/**
 * Op de reveal vervalt het embargo en gaat het persbericht van de provincie op /pers.
 */
final class PublishProvincePressRelease
{
    public function handle(ProvinceRevealed $event): void
    {
        PressRelease::query()
            ->where('edition_id', $event->edition->getKey())
            ->where('province_id', $event->province->getKey())
            ->where('milestone', PressMilestone::ProvinceTop10)
            ->whereNull('published_at')
            ->each(function (PressRelease $release): void {
                $release->forceFill([
                    'published_at' => now(),
                    'embargo_until' => now(),
                    'body' => preg_replace('/^PERSBERICHT – ONDER EMBARGO TOT .*$/mu', 'PERSBERICHT', $release->body),
                ])->save();
            });
    }
}

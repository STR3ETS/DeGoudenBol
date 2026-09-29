<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Edition\Enums\EditionStatus;
use App\Domain\Edition\Models\Edition;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Events\ProvinceRevealed;
use App\Domain\Ranking\Models\RankingSnapshot;
use App\Support\PublicCache;
use Carbon\CarbonInterface;

/**
 * Provincie-reveal op de publicatiedag (besluit 20): iedere provincie gaat op haar eigen tijd
 * live. Zodra alle provincies onthuld zijn, is de editie gepubliceerd.
 *
 * @return list<string> Namen van de zojuist onthulde provincies
 */
final class RevealProvinces
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return list<string>
     */
    public function __invoke(Edition $edition, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        if ($edition->status !== EditionStatus::Frozen) {
            return [];
        }

        $edition->loadMissing('provinces');
        $revealed = [];

        foreach ($edition->provinces as $province) {
            $revealAt = $province->pivot->reveal_at ?? $edition->main_publication_at;

            if ($province->pivot->revealed_at !== null || $revealAt === null || $revealAt->greaterThan($now)) {
                continue;
            }

            $snapshot = RankingSnapshot::frozenForProvince($edition->getKey(), $province->getKey());

            if ($snapshot === null) {
                continue;
            }

            $snapshot->forceFill(['published_at' => $now])->save();
            $edition->provinces()->updateExistingPivot($province->getKey(), ['revealed_at' => $now]);
            PublicCache::forgetProvince($edition->getKey(), $province->getKey());

            $this->audit->record('province.revealed', $snapshot, ['province' => $province->slug], null);
            ProvinceRevealed::dispatch($edition, $province, $snapshot);

            $revealed[] = $province->name;
        }

        $edition->unsetRelation('provinces');

        $allRevealed = $edition->provinces()->whereNull('edition_province.revealed_at')->doesntExist();

        if ($allRevealed) {
            $edition->forceFill(['status' => EditionStatus::Published])->save();
            PublicCache::forgetEdition($edition->getKey());
        }

        return $revealed;
    }
}

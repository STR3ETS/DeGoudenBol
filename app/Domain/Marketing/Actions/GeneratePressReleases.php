<?php

namespace App\Domain\Marketing\Actions;

use App\Domain\Edition\Models\Edition;
use App\Domain\Marketing\Enums\PressMilestone;
use App\Domain\Marketing\Models\PressRelease;
use App\Domain\Marketing\Services\PressReleaseWriter;
use App\Domain\Ranking\Models\RankingSnapshot;

/**
 * Persbericht per provincie met een bevroren Top 10 (embargo tot de reveal) en, zodra de landelijke
 * lijst er is, één landelijk bericht. Bestaande berichten blijven staan, tenzij `$overwrite`.
 */
final class GeneratePressReleases
{
    public function __construct(private readonly PressReleaseWriter $writer) {}

    public function __invoke(Edition $edition, bool $overwrite = false): int
    {
        $edition->loadMissing(['provinces', 'activeScoringModel.criteria']);
        $count = 0;

        foreach ($edition->provinces as $province) {
            $snapshot = RankingSnapshot::frozenForProvince($edition->getKey(), $province->getKey());

            if ($snapshot === null) {
                continue;
            }

            $embargoUntil = $province->pivot->revealed_at !== null ? null : ($province->pivot->reveal_at ?? $edition->main_publication_at);
            $existing = PressRelease::query()->where('edition_id', $edition->getKey())->where('province_id', $province->getKey())->where('milestone', PressMilestone::ProvinceTop10)->first();

            if ($existing !== null && ! $overwrite) {
                continue;
            }

            $text = $this->writer->province($edition, $province, $snapshot, $embargoUntil);

            $this->store($existing, [
                'edition_id' => $edition->getKey(),
                'province_id' => $province->getKey(),
                'milestone' => PressMilestone::ProvinceTop10,
                'embargo_until' => $embargoUntil,
                'published_at' => $province->pivot->revealed_at,
                ...$text,
            ]);

            $count++;
        }

        $national = RankingSnapshot::latestNational($edition->getKey());

        if ($national !== null) {
            $existing = PressRelease::query()->where('edition_id', $edition->getKey())->whereNull('province_id')->where('milestone', PressMilestone::NationalFinal)->first();

            if ($existing === null || $overwrite) {
                $this->store($existing, [
                    'edition_id' => $edition->getKey(),
                    'province_id' => null,
                    'milestone' => PressMilestone::NationalFinal,
                    'embargo_until' => null,
                    'published_at' => $existing?->published_at ?? $national->published_at ?? now(),
                    ...$this->writer->national($edition, $national),
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(?PressRelease $existing, array $attributes): PressRelease
    {
        $attributes['generated_at'] = now();

        if ($existing === null) {
            return PressRelease::query()->create($attributes);
        }

        // Bij hergenereren blijft de slug (en dus de openbare URL) hetzelfde.
        $existing->fill(array_diff_key($attributes, ['slug' => true]))->save();

        return $existing;
    }
}

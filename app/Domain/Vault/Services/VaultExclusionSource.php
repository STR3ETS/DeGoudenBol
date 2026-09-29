<?php

namespace App\Domain\Vault\Services;

use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Models\PanelistConflict;
use App\Domain\Testing\Contracts\ExclusionSource;
use App\Domain\Testing\Data\Exclusion;
use App\Domain\Testing\Enums\ExclusionReason;
use App\Domain\Testing\Models\TestSession;

/**
 * Vertaalt gemelde belangenconflicten en allergenen naar uitsluitingen op testnummer.
 * Draait als systeemproces: de uitkomst bevat geen naam en geen bedrijf.
 */
final class VaultExclusionSource implements ExclusionSource
{
    public function __construct(private readonly VaultService $vault) {}

    public function exclusionsFor(TestSession $session): array
    {
        $session->loadMissing(['samples', 'panelists']);

        $sampleIds = $session->samples->modelKeys();
        $entryBySample = $this->vault->asSystem(fn () => $this->vault->entryUlidsForSamples($sampleIds, 'uitserveerschema sessie '.$session->ulid));

        if ($entryBySample === []) {
            return [];
        }

        $entries = Entry::query()->whereIn('ulid', array_values($entryBySample))->get(['id', 'ulid', 'company_id', 'allergens'])->keyBy('ulid');
        $userIds = $session->panelists->pluck('user_id')->all();
        $conflicts = PanelistConflict::query()->whereIn('user_id', $userIds)->get()->groupBy('user_id');

        $exclusions = [];

        foreach ($session->panelists as $panelist) {
            $conflictCompanies = $conflicts->get($panelist->user_id)?->pluck('company_id')->all() ?? [];
            $allergens = array_map('strval', $panelist->allergens ?? []);

            foreach ($entryBySample as $sampleId => $entryUlid) {
                $entry = $entries->get($entryUlid);

                if ($entry === null) {
                    continue;
                }

                if (in_array($entry->company_id, $conflictCompanies, true)) {
                    $exclusions[] = new Exclusion($panelist->getKey(), (int) $sampleId, ExclusionReason::Conflict);

                    continue;
                }

                if ($allergens !== [] && array_intersect($allergens, array_map('strval', $entry->allergens ?? [])) !== []) {
                    $exclusions[] = new Exclusion($panelist->getKey(), (int) $sampleId, ExclusionReason::Allergen);
                }
            }
        }

        return $exclusions;
    }
}

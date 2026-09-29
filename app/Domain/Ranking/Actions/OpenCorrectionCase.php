<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Domain\Ranking\Models\CorrectionCase;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Correctiedossier openen voor een al gepubliceerde uitslag. Het monster gaat terug naar
 * scorecontrole (uitslag voorlopig) zodat kaarten ongeldig verklaard of gecorrigeerd kunnen worden;
 * de nieuwe uitslag wordt pas na twee goedkeuringen toegepast.
 */
final class OpenCorrectionCase
{
    public function __construct(
        private readonly VaultService $vault,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Entry $entry, string $reason, User $submitter): CorrectionCase
    {
        $entry->loadMissing('publicationItems.batch');
        $item = $entry->publishedItem();

        if ($item === null) {
            throw new LogicException('Voor deze inschrijving is nog niets gepubliceerd; gebruik scorecontrole.');
        }

        if (CorrectionCase::query()->where('entry_id', $entry->getKey())->where('status', CorrectionCaseStatus::PendingApproval)->exists()) {
            throw new LogicException('Er loopt al een correctiedossier voor deze inschrijving.');
        }

        $sampleIds = $this->vault->asSystem(fn () => $this->vault->sampleIdsForEntry($entry->ulid, 'correctiedossier openen'));
        $sample = Sample::query()->whereIn('id', $sampleIds)->where('round', SampleRound::Provincial)->orderByDesc('id')->with('result')->first()
            ?? throw new LogicException('Geen provinciaal testnummer gevonden voor deze inschrijving.');

        return DB::transaction(function () use ($entry, $reason, $submitter, $item, $sample): CorrectionCase {
            $case = CorrectionCase::query()->create([
                'entry_id' => $entry->getKey(),
                'reason' => $reason,
                'original_snapshot' => $item->result_snapshot,
                'status' => CorrectionCaseStatus::PendingApproval,
                'submitted_by' => $submitter->getKey(),
            ]);

            $sample->result?->forceFill(['status' => ResultStatus::Pending])->save();
            $sample->forceFill(['status' => SampleStatus::Scored])->save();

            $this->audit->record('correction_case.opened', $case, ['entry' => $entry->ulid], $submitter);

            return $case;
        });
    }
}

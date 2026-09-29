<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\CorrectionCaseStatus;
use App\Domain\Ranking\Enums\ItemVisibility;
use App\Domain\Ranking\Models\CorrectionCase;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Notifications\CorrectionAppliedNotification;
use App\Domain\Ranking\Services\PublicationSchedule;
use App\Domain\Testing\Actions\RecalculateResult;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleRound;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Models\Sample;
use App\Domain\Vault\Services\VaultService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Twee goedkeuringen (nooit de indiener), daarna: uitslag opnieuw berekend uit de huidige geldige
 * kaarten en correcties, nieuwe uitslag in de eerstvolgende batch, melding aan betrokkenen.
 */
final class ApproveCorrectionCase
{
    public function __construct(
        private readonly VaultService $vault,
        private readonly RecalculateResult $recalculate,
        private readonly PublicationSchedule $schedule,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(CorrectionCase $case, User $approver, bool $approve = true): CorrectionCase
    {
        if (! $approver->hasRole(StaffRole::Publisher->value)) {
            throw new LogicException('Alleen de rol Publicatie mag een correctiedossier goedkeuren.');
        }

        if ($case->status !== CorrectionCaseStatus::PendingApproval) {
            throw new LogicException('Dit dossier is al afgehandeld.');
        }

        if ((int) $case->submitted_by === (int) $approver->getKey()) {
            throw new LogicException('De indiener mag zijn eigen dossier niet goedkeuren.');
        }

        if (! $approve) {
            $case->forceFill(['status' => CorrectionCaseStatus::Rejected])->save();
            $this->audit->record('correction_case.rejected', $case, [], $approver);

            return $case;
        }

        if ($case->approvals()->where('user_id', $approver->getKey())->exists()) {
            throw new LogicException('U heeft dit dossier al goedgekeurd.');
        }

        $case->approvals()->create(['user_id' => $approver->getKey(), 'approved_at' => now()]);
        $this->audit->record('correction_case.approved', $case, ['approvals' => $case->approvals()->count()], $approver);

        if ($case->approvals()->count() < CorrectionCase::REQUIRED_APPROVALS) {
            return $case->refresh();
        }

        return $this->apply($case, $approver);
    }

    private function apply(CorrectionCase $case, User $by): CorrectionCase
    {
        $entry = $case->entry()->with('edition')->firstOrFail();
        $sampleIds = $this->vault->asSystem(fn () => $this->vault->sampleIdsForEntry($entry->ulid, 'correctiedossier '.$case->getKey()));

        $sample = Sample::query()->whereIn('id', $sampleIds)->where('round', SampleRound::Provincial)->orderByDesc('id')->first()
            ?? throw new LogicException('Geen provinciaal testnummer gevonden voor deze inschrijving.');
        $result = ($this->recalculate)($sample, force: true);

        if ($result->flags['missing_cards'] ?? false) {
            throw new LogicException("Te weinig geldige kaarten ({$result->card_count}) om een nieuwe uitslag vast te stellen.");
        }

        $result->forceFill(['status' => ResultStatus::Final, 'finalized_by' => $by->getKey(), 'finalized_at' => now()])->save();
        $sample->forceFill(['status' => SampleStatus::Final])->save();

        $edition = $entry->edition;
        $visibility = $result->total >= $edition->settings->publishThreshold ? ItemVisibility::Public : ItemVisibility::Confidential;

        $snapshot = [
            'total' => $result->total,
            'total_raw' => $result->total_raw,
            'card_count' => $result->card_count,
            'criterion_averages' => $result->criterion_averages,
            'scoring_model_version' => $result->scoring_model_version,
        ];

        DB::transaction(function () use ($case, $entry, $edition, $visibility, $snapshot, $sample, $by): void {
            $batch = $this->schedule->openBatch($edition);

            PublicationItem::query()->updateOrCreate(
                ['publication_batch_id' => $batch->getKey(), 'entry_id' => $entry->getKey()],
                ['province_id' => $entry->province_id, 'round' => $sample->round->value, 'visibility' => $visibility, 'result_snapshot' => $snapshot],
            );

            $case->forceFill(['status' => CorrectionCaseStatus::Applied, 'new_snapshot' => $snapshot, 'applied_at' => now()])->save();

            $this->audit->record('correction_case.applied', $case, ['batch' => $batch->getKey(), 'total' => $snapshot['total']], $by);
        });

        foreach ($entry->company->users as $user) {
            $user->notify(new CorrectionAppliedNotification($case));
        }

        return $case->refresh();
    }
}

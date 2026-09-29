<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Enums\CorrectionStatus;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Models\User;
use LogicException;

/**
 * Vier ogen op scorecontrole: de goedkeurder is nooit de aanvrager.
 */
final class ApproveScoreCorrection
{
    public function __construct(
        private readonly RecalculateResult $recalculate,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(ScoreCorrection $correction, User $approver, bool $approve = true, ?string $rejectionReason = null): ScoreCorrection
    {
        if ($correction->status !== CorrectionStatus::Pending) {
            throw new LogicException('Deze correctie is al afgehandeld.');
        }

        if ($correction->requested_by === (int) $approver->getKey()) {
            throw new LogicException('De aanvrager mag zijn eigen correctie niet goedkeuren.');
        }

        $correction->forceFill([
            'status' => $approve ? CorrectionStatus::Approved : CorrectionStatus::Rejected,
            'approved_by' => $approver->getKey(),
            'approved_at' => now(),
            'rejection_reason' => $approve ? null : $rejectionReason,
        ])->save();

        if ($approve) {
            ($this->recalculate)($correction->scorecard->sample);
        }

        $this->audit->record($approve ? 'scorecard.correction_approved' : 'scorecard.correction_rejected', null, ['correction' => $correction->getKey()], $approver);

        return $correction;
    }
}

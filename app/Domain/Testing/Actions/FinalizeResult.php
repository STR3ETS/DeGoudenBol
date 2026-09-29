<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Enums\CorrectionStatus;
use App\Domain\Testing\Enums\ResultStatus;
use App\Domain\Testing\Enums\SampleStatus;
use App\Domain\Testing\Events\ResultFinalized;
use App\Domain\Testing\Models\Result;
use App\Domain\Testing\Models\Sample;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Models\User;
use LogicException;

/**
 * Stap 7 uit het kernproces: score definitief maken na scorecontrole.
 */
final class FinalizeResult
{
    public function __construct(
        private readonly RecalculateResult $recalculate,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Sample $sample, ?User $by = null): Result
    {
        if ($sample->result?->isFinal()) {
            return $sample->result;
        }

        $pendingCorrections = ScoreCorrection::query()
            ->whereIn('scorecard_id', $sample->scorecards()->select('id'))
            ->where('status', CorrectionStatus::Pending)
            ->exists();

        if ($pendingCorrections) {
            throw new LogicException('Er staan nog correcties open voor dit monster.');
        }

        $result = ($this->recalculate)($sample);

        if ($result->flags['missing_cards'] ?? true) {
            throw new LogicException("Te weinig geldige kaarten ({$result->card_count} van minimaal {$result->flags['min_valid_cards']}).");
        }

        $result->forceFill([
            'status' => ResultStatus::Final,
            'finalized_by' => $by?->getKey(),
            'finalized_at' => now(),
        ])->save();

        $sample->forceFill(['status' => SampleStatus::Final])->save();

        $this->audit->record('result.finalized', null, ['sample' => $sample->ulid, 'number' => $sample->sample_number, 'total' => $result->total, 'cards' => $result->card_count], $by);

        ResultFinalized::dispatch($result);

        return $result;
    }
}

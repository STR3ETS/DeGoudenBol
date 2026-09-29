<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Enums\CorrectionStatus;
use App\Domain\Testing\Models\Scorecard;
use App\Domain\Testing\Models\ScoreCorrection;
use App\Models\User;
use LogicException;

/**
 * Correctie op een invoerfout aanvragen. Een andere scorecontroleur keurt goed (ApproveScoreCorrection).
 */
final class RequestScoreCorrection
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, int>  $after  Alleen de onderdelen die veranderen.
     */
    public function __invoke(Scorecard $card, array $after, string $reason, User $requestedBy): ScoreCorrection
    {
        if ($card->sample->result?->isFinal()) {
            throw new LogicException('De uitslag van dit monster is al definitief; gebruik het correctiedossier.');
        }

        $changes = [];

        foreach ($after as $code => $value) {
            if (array_key_exists($code, $card->scores) && (int) $card->scores[$code] !== (int) $value) {
                $changes[$code] = (int) $value;
            }
        }

        if ($changes === []) {
            throw new LogicException('De correctie bevat geen wijziging.');
        }

        $correction = ScoreCorrection::query()->create([
            'scorecard_id' => $card->getKey(),
            'reason' => $reason,
            'before' => array_intersect_key($card->scores, $changes),
            'after' => $changes,
            'status' => CorrectionStatus::Pending,
            'requested_by' => $requestedBy->getKey(),
        ]);

        $this->audit->record('scorecard.correction_requested', null, ['card' => $card->uuid, 'changes' => $changes], $requestedBy);

        return $correction;
    }
}

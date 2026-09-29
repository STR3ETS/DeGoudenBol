<?php

namespace App\Domain\Testing\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Testing\Models\Scorecard;
use App\Models\User;
use LogicException;

/**
 * Scorecontrole verklaart een kaart ongeldig (bijvoorbeeld een aantoonbare invoerfout of een
 * dubbele kaart). De kaart blijft bewaard; hij telt alleen niet meer mee.
 */
final class InvalidateScorecard
{
    public function __construct(
        private readonly RecalculateResult $recalculate,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Scorecard $card, string $reason, ?User $by = null): Scorecard
    {
        if (! $card->is_valid) {
            return $card;
        }

        if ($card->sample->result?->isFinal()) {
            throw new LogicException('De uitslag van dit monster is al definitief.');
        }

        $card->invalidate($reason, $by?->getKey());
        ($this->recalculate)($card->sample);

        $this->audit->record('scorecard.invalidated', null, ['card' => $card->uuid, 'reason' => $reason], $by);

        return $card;
    }
}

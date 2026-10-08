<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Events\BatchSubmitted;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Models\User;
use LogicException;

/**
 * De indiener biedt de batch aan; daarna moeten twee anderen goedkeuren.
 */
final class SubmitBatch
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(PublicationBatch $batch, User $submitter): PublicationBatch
    {
        if ($batch->status !== BatchStatus::Draft) {
            throw new LogicException('Alleen een batch in opbouw kan worden ingediend.');
        }

        if (! $batch->items()->exists()) {
            throw new LogicException('De batch bevat nog geen uitslagen.');
        }

        $batch->forceFill([
            'status' => BatchStatus::PendingApproval,
            'submitted_by' => $submitter->getKey(),
            'submitted_at' => now(),
        ])->save();

        $this->audit->record('publication_batch.submitted', $batch, ['items' => $batch->items()->count()], $submitter);

        BatchSubmitted::dispatch($batch, $submitter);

        return $batch;
    }
}

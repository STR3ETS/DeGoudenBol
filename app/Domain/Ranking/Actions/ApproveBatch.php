<?php

namespace App\Domain\Ranking\Actions;

use App\Domain\Platform\Enums\StaffRole;
use App\Domain\Platform\Services\AuditLogger;
use App\Domain\Ranking\Enums\BatchStatus;
use App\Domain\Ranking\Models\PublicationBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Harde regel: publicatie vereist twee verschillende goedkeurders; de indiener keurt nooit zelf.
 */
final class ApproveBatch
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(PublicationBatch $batch, User $approver): PublicationBatch
    {
        if (! $approver->hasRole(StaffRole::Publisher->value)) {
            throw new LogicException('Alleen de rol Publicatie mag goedkeuren.');
        }

        return DB::transaction(function () use ($batch, $approver): PublicationBatch {
            $batch = PublicationBatch::query()->lockForUpdate()->findOrFail($batch->getKey());

            if ($batch->status !== BatchStatus::PendingApproval) {
                throw new LogicException('Deze batch wacht niet op goedkeuring.');
            }

            if ((int) $batch->submitted_by === (int) $approver->getKey()) {
                throw new LogicException('De indiener mag zijn eigen batch niet goedkeuren.');
            }

            if ($batch->approvals()->where('user_id', $approver->getKey())->exists()) {
                throw new LogicException('U heeft deze batch al goedgekeurd.');
            }

            $batch->approvals()->create(['user_id' => $approver->getKey(), 'approved_at' => now()]);

            $count = $batch->approvals()->count();

            if ($count >= PublicationBatch::REQUIRED_APPROVALS) {
                $batch->forceFill(['status' => BatchStatus::Approved])->save();
            }

            $this->audit->record('publication_batch.approved', $batch, ['approvals' => $count], $approver);

            return $batch->refresh();
        });
    }
}

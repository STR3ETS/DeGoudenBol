<?php

namespace App\Domain\Ranking\Events;

use App\Domain\Ranking\Models\PublicationBatch;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BatchApproved
{
    use Dispatchable;

    public function __construct(
        public readonly PublicationBatch $batch,
        public readonly User $approver,
        public readonly int $approvalsCount,
    ) {}
}

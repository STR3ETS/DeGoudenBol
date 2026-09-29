<?php

namespace App\Domain\Ranking\Events;

use App\Domain\Ranking\Models\PublicationBatch;
use Illuminate\Foundation\Events\Dispatchable;

class BatchPublished
{
    use Dispatchable;

    public function __construct(public readonly PublicationBatch $batch) {}
}

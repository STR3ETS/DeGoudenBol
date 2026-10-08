<?php

namespace App\Domain\Ranking\Events;

use App\Domain\Ranking\Models\CorrectionCase;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CorrectionCaseOpened
{
    use Dispatchable;

    public function __construct(public readonly CorrectionCase $case, public readonly User $submitter) {}
}

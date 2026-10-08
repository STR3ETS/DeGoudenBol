<?php

namespace App\Domain\Participants\Events;

use App\Domain\Participants\Models\Objection;
use Illuminate\Foundation\Events\Dispatchable;

class ObjectionSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Objection $objection) {}
}

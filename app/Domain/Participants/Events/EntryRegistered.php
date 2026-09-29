<?php

namespace App\Domain\Participants\Events;

use App\Domain\Participants\Models\Entry;
use Illuminate\Foundation\Events\Dispatchable;

class EntryRegistered
{
    use Dispatchable;

    public function __construct(public readonly Entry $entry) {}
}

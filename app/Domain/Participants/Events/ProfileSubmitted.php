<?php

namespace App\Domain\Participants\Events;

use App\Domain\Participants\Models\Profile;
use Illuminate\Foundation\Events\Dispatchable;

class ProfileSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Profile $profile) {}
}

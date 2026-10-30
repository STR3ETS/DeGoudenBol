<?php

namespace App\Domain\Participants\Events;

use App\Domain\Participants\Models\Entry;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Link of bewijs van de socialmedia-publicatie staat klaar om te keuren.
 */
class DiscountProofSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Entry $entry) {}
}

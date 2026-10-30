<?php

namespace App\Domain\Participants\Events;

use App\Domain\Participants\Models\Entry;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Een bakker koos bij het aanmelden de actieprijs. Hij krijgt uitleg en een portaallink; beheer ziet
 * de aanvraag zodra er bewijs is.
 */
class DiscountRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Entry $entry) {}
}

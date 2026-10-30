<?php

namespace App\Domain\Intake\Events;

use App\Domain\Participants\Models\Entry;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * De oliebollen van een deelnemer zijn ontvangen. Bevat bewust alleen de inschrijving: het
 * testnummer staat uitsluitend in de kluis.
 */
class EntryReceived
{
    use Dispatchable;

    public function __construct(public readonly Entry $entry) {}
}

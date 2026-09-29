<?php

namespace App\Domain\Ranking\Events;

use App\Domain\Edition\Models\Edition;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * De Voorlijsten zijn bevroren: definitieve Top 10, provinciewinnaars en finale-inschrijvingen
 * staan vast. Marketing maakt hierna (onder embargo) erkenningen, badges en kits.
 */
class EditionFrozen
{
    use Dispatchable;

    public function __construct(public readonly Edition $edition) {}
}

<?php

namespace App\Domain\Testing\Events;

use App\Domain\Testing\Models\Result;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Scorecontrole heeft de uitslag van een monster definitief gemaakt (stap 7). Hierna volgt de
 * koppeling via de kluis en het publicatie-item (stap 8).
 */
class ResultFinalized
{
    use Dispatchable;

    public function __construct(public readonly Result $result) {}
}

<?php

namespace App\Support\Models\Concerns;

use Carbon\CarbonInterface;

/**
 * Zet elke aangeleverde datum eerst naar de opslag-tijdzone (UTC), zodat een
 * Carbon in Europe/Amsterdam nooit als lokale tijd in een UTC-kolom belandt.
 */
trait NormalizesDatesToUtc
{
    protected function asDateTime($value): CarbonInterface
    {
        return parent::asDateTime($value)->setTimezone(config('app.timezone'));
    }
}

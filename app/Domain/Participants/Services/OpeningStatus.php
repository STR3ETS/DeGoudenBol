<?php

namespace App\Domain\Participants\Services;

use App\Domain\Participants\Models\Location;
use App\Support\DutchTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * "Nu open" op basis van seizoen, uitzonderingen (zoals oudjaarsdag) en reguliere tijden, in Nederlandse tijd.
 */
final class OpeningStatus
{
    /**
     * Null als er geen openingstijden bekend zijn.
     */
    public function isOpenNow(Location $location, ?CarbonInterface $now = null): ?bool
    {
        $now = DutchTime::display($now ?? now());

        if ($location->season_from && $now->lt(CarbonImmutable::parse($location->season_from->toDateString(), DutchTime::zone())->startOfDay())) {
            return false;
        }

        if ($location->season_to && $now->gt(CarbonImmutable::parse($location->season_to->toDateString(), DutchTime::zone())->endOfDay())) {
            return false;
        }

        $exception = $location->openingHourExceptions->first(fn ($item) => $item->date->toDateString() === $now->toDateString());

        if ($exception !== null) {
            return $exception->is_closed ? false : $this->between($now, $exception->opens_at, $exception->closes_at);
        }

        if ($location->openingHours->isEmpty()) {
            return null;
        }

        $hour = $location->openingHours->firstWhere('weekday', $now->isoWeekday());

        if ($hour === null || $hour->is_closed) {
            return false;
        }

        return $this->between($now, $hour->opens_at, $hour->closes_at);
    }

    private function between(CarbonInterface $now, ?string $opens, ?string $closes): bool
    {
        if ($opens === null || $closes === null) {
            return false;
        }

        $time = $now->format('H:i');

        return $time >= substr($opens, 0, 5) && $time < substr($closes, 0, 5);
    }
}

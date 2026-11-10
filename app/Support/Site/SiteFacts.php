<?php

namespace App\Support\Site;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Services\ProvinceCapacity;
use App\Support\DutchTime;

/**
 * Kleine feiten die de publiekssite op meerdere plekken in gewone taal toont.
 */
final class SiteFacts
{
    public static function startLabel(?Edition $edition): ?string
    {
        return $edition?->first_test_day ? DutchTime::format($edition->first_test_day, 'D MMMM') : null;
    }

    /**
     * Tekst per provincie voor de kaart van Nederland (data-info), bijvoorbeeld "Voorlijst opent 1 november".
     * Geen aantallen aanmeldingen of vrije plekken (besluit Bennie 3 en 9 nov 2026); alleen "Vol" als het vol is.
     *
     * @return array<string, string>
     */
    public static function mapInfo(?Edition $edition): array
    {
        if ($edition === null) {
            return [];
        }

        $edition->loadMissing('provinces');
        $overview = app(ProvinceCapacity::class)->overview($edition);
        $start = self::startLabel($edition);
        $started = $edition->first_test_day !== null && $edition->first_test_day->isPast();
        $live = LiveData::get();
        $info = [];

        foreach ($edition->provinces as $province) {
            $stats = $overview[$province->slug] ?? null;
            $ranked = count($live['rankings'][$province->slug] ?? []);
            $parts = [];

            if ($stats !== null && $stats['available'] <= 0) {
                $parts[] = 'Vol';
            }

            $parts[] = $ranked > 0 ? "{$ranked} op de Voorlijst" : ($started || $start === null ? 'Voorlijst volgt' : "Voorlijst opent {$start}");

            $info[$province->slug] = implode(' · ', $parts);
        }

        return $info;
    }
}

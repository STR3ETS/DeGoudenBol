<?php

namespace App\Domain\Participants\Services;

use App\Domain\Edition\Models\Edition;
use App\Domain\Edition\Models\Province;
use App\Domain\Participants\Exceptions\ProvinceFullException;
use App\Domain\Participants\Models\Entry;
use Illuminate\Support\Facades\DB;

/**
 * Bewaakt het aantal plekken per provincie. Reserveren gebeurt met een rijvergrendeling
 * op de koppeltabel editie-provincie, zodat twee gelijktijdige aanmeldingen nooit de
 * laatste plek allebei krijgen.
 */
final class ProvinceCapacity
{
    public function capacity(Edition $edition, Province $province): int
    {
        $pivot = DB::table('edition_province')
            ->where('edition_id', $edition->getKey())
            ->where('province_id', $province->getKey())
            ->value('capacity');

        return $pivot !== null ? (int) $pivot : $edition->settings->capacityPerProvince;
    }

    public function taken(Edition $edition, Province $province): int
    {
        return Entry::query()
            ->where('edition_id', $edition->getKey())
            ->where('province_id', $province->getKey())
            ->occupyingPlace()
            ->count();
    }

    public function available(Edition $edition, Province $province): int
    {
        return max(0, $this->capacity($edition, $province) - $this->taken($edition, $province));
    }

    /**
     * Overzicht voor alle provincies van een editie: slug => [capacity, taken, available].
     *
     * @return array<string, array{capacity: int, taken: int, available: int}>
     */
    public function overview(Edition $edition): array
    {
        $overview = [];

        foreach ($edition->provinces as $province) {
            $capacity = (int) ($province->pivot->capacity ?? $edition->settings->capacityPerProvince);
            $taken = $this->taken($edition, $province);

            $overview[$province->slug] = [
                'capacity' => $capacity,
                'taken' => $taken,
                'available' => max(0, $capacity - $taken),
            ];
        }

        return $overview;
    }

    /**
     * Voert $create uit terwijl de plekken van de provincie vergrendeld zijn.
     *
     * @template T
     *
     * @param  callable(): T  $create
     * @return T
     *
     * @throws ProvinceFullException
     */
    public function reserve(Edition $edition, Province $province, callable $create): mixed
    {
        return DB::transaction(function () use ($edition, $province, $create) {
            DB::table('edition_province')
                ->where('edition_id', $edition->getKey())
                ->where('province_id', $province->getKey())
                ->lockForUpdate()
                ->first();

            if ($this->available($edition, $province) <= 0) {
                throw ProvinceFullException::for($province);
            }

            return $create();
        });
    }
}

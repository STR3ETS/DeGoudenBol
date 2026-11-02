<?php

namespace App\Domain\Intake\Services;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Participants\Models\DeliverySlot;
use Illuminate\Support\Collection;

/**
 * Welke aanlevermomenten en ophaalvrijdagen een bakker nu nog kan kiezen. Gedeeld door de bedankpagina
 * na de betaling en de dagelijkse herinnering, zodat beide hetzelfde aanbod zien als het portaal.
 */
final class PlanningOptions
{
    /**
     * Komende aanlevermomenten met het aantal bezette plekken (`taken_count`).
     *
     * @return Collection<int, DeliverySlot>
     */
    public function slots(Edition $edition): Collection
    {
        return DeliverySlot::query()
            ->where('edition_id', $edition->getKey())
            ->upcoming()
            ->with('testLocation')
            ->withCount(['entries as taken_count' => fn ($query) => $query->occupyingPlace()])
            ->get();
    }

    /**
     * Ophaalvrijdagen die nog binnen de wijzigingstermijn vallen, met `taken_count`.
     *
     * @return Collection<int, PickupDay>
     */
    public function pickupDays(Edition $edition): Collection
    {
        if (! $edition->settings->pickupEnabled) {
            return collect();
        }

        return PickupDay::query()
            ->where('edition_id', $edition->getKey())
            ->bookable($edition->settings->changeDeadlineHours)
            ->withCount(['requests as taken_count' => fn ($query) => $query->whereIn('status', PickupStatus::occupying())])
            ->get();
    }

    /**
     * Is er nog iets te kiezen? Zonder vrije plek heeft een herinnering geen zin.
     */
    public function hasRoom(Edition $edition, bool $pickup = false): bool
    {
        $options = $pickup ? $this->pickupDays($edition) : $this->slots($edition);

        return $options->contains(fn (DeliverySlot|PickupDay $option) => $option->taken_count < $option->capacity);
    }
}

<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Exceptions\VisibilitySpotTakenException;
use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Participants\Models\Entry;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Houdt een zichtbaarheidsplek vast zolang de betaling loopt. De unieke index op editie, provincie en plek
 * voorkomt dat twee bakkers dezelfde plek krijgen. Verlopen reserveringen ruimen we hier zelf op.
 */
final class ReserveVisibilitySpot
{
    public function __invoke(Entry $entry, int $position, CarbonInterface $until): VisibilitySpot
    {
        $settings = $entry->edition->settings;
        $price = $settings->visibilitySpotPrice($position);

        if ($price === null) {
            throw new InvalidArgumentException('Deze plek bestaat niet.');
        }

        return DB::transaction(function () use ($entry, $position, $until, $price): VisibilitySpot {
            $own = VisibilitySpot::query()->where('entry_id', $entry->getKey())->lockForUpdate()->first();

            if ($own?->isPaid()) {
                throw new InvalidArgumentException('Deze inschrijving heeft al een zichtbaarheidsplek.');
            }

            if ($own !== null) {
                if ($own->order?->isPending()) {
                    $own->order->forceFill(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()])->save();
                }

                $own->delete();
            }

            VisibilitySpot::query()
                ->where('edition_id', $entry->edition_id)
                ->where('province_id', $entry->province_id)
                ->where('position', $position)
                ->whereNull('paid_at')
                ->where('reserved_until', '<=', now())
                ->delete();

            try {
                return VisibilitySpot::query()->create([
                    'edition_id' => $entry->edition_id,
                    'province_id' => $entry->province_id,
                    'entry_id' => $entry->getKey(),
                    'company_id' => $entry->company_id,
                    'position' => $position,
                    'price_cents' => $price,
                    'reserved_until' => $until,
                    'ends_at' => RegisterSponsor::seasonEnd($entry->edition),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new VisibilitySpotTakenException("Plek {$position} is net door iemand anders gekozen. Kies een andere plek.");
            }
        });
    }
}

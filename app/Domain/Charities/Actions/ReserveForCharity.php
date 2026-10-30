<?php

namespace App\Domain\Charities\Actions;

use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Participants\Models\Entry;
use Illuminate\Database\Eloquent\Model;

/**
 * Besluit 8: bij ontvangst van een betaling wordt per factuurregel met `counts_for_charity`
 * het ingestelde percentage over het bedrag excl. btw gereserveerd. Idempotent per regel.
 * Een kortingsregel (negatief bedrag) krijgt geen eigen reservering maar verlaagt de grondslag,
 * zodat de reservering een percentage is van wat er werkelijk betaald is.
 */
final class ReserveForCharity
{
    public function __invoke(Order $order): int
    {
        $order->loadMissing(['edition', 'lines', 'orderable']);
        $settings = $order->edition->settings;
        $percentage = $settings->charityPercentage;

        [$payer, $provinceId] = $this->payer($order);
        $charity = $payer ? $this->approvedCharityOf($payer, $order->edition_id) : null;
        $count = 0;
        $discount = (int) $order->lines->filter(fn (OrderLine $line) => $line->counts_for_charity && $line->subtotal_cents < 0)->sum('subtotal_cents');

        foreach ($order->lines as $line) {
            if (! $line->counts_for_charity || $line->subtotal_cents < 0 || CharityReservation::query()->where('order_line_id', $line->getKey())->exists()) {
                continue;
            }

            $basis = max(0, $line->subtotal_cents + $discount);
            $discount = min(0, $line->subtotal_cents + $discount);

            CharityReservation::query()->create([
                'edition_id' => $order->edition_id,
                'order_line_id' => $line->getKey(),
                'charity_id' => $charity?->getKey(),
                'regional_pot_province_id' => $provinceId,
                'payer_type' => $payer?->getMorphClass(),
                'payer_id' => $payer?->getKey(),
                'basis_cents' => $basis,
                'amount_cents' => (int) round($basis * $percentage / 100),
                'basis' => $settings->charityBasis->value,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * @return array{0: Model|null, 1: int|null}
     */
    private function payer(Order $order): array
    {
        $orderable = $order->orderable;

        if ($orderable instanceof Entry) {
            return [$orderable->company, $orderable->province_id];
        }

        if ($orderable instanceof Sponsor) {
            return [$orderable, null];
        }

        if ($orderable instanceof PickupRequest && $orderable->entry !== null) {
            return [$orderable->entry->company, $orderable->entry->province_id];
        }

        return [null, null];
    }

    private function approvedCharityOf(Model $payer, int $editionId): ?Charity
    {
        return Charity::query()
            ->where('edition_id', $editionId)
            ->where('nominated_by_type', $payer->getMorphClass())
            ->where('nominated_by_id', $payer->getKey())
            ->public()
            ->first();
    }
}

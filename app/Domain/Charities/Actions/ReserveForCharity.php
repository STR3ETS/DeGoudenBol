<?php

namespace App\Domain\Charities\Actions;

use App\Domain\Charities\Models\Charity;
use App\Domain\Charities\Models\CharityReservation;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Participants\Models\Entry;
use Illuminate\Database\Eloquent\Model;

/**
 * Besluit 8: bij ontvangst van een betaling wordt per factuurregel met `counts_for_charity`
 * het ingestelde percentage over het bedrag excl. btw gereserveerd. Idempotent per regel.
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

        foreach ($order->lines as $line) {
            if (! $line->counts_for_charity || CharityReservation::query()->where('order_line_id', $line->getKey())->exists()) {
                continue;
            }

            CharityReservation::query()->create([
                'edition_id' => $order->edition_id,
                'order_line_id' => $line->getKey(),
                'charity_id' => $charity?->getKey(),
                'regional_pot_province_id' => $provinceId,
                'payer_type' => $payer?->getMorphClass(),
                'payer_id' => $payer?->getKey(),
                'basis_cents' => $line->subtotal_cents,
                'amount_cents' => $this->amount($line, $percentage),
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

    private function amount(OrderLine $line, int $percentage): int
    {
        return (int) round($line->subtotal_cents * $percentage / 100);
    }
}

<?php

namespace App\Domain\Participants\Actions;

use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryRate;
use App\Domain\Participants\Models\Entry;
use InvalidArgumentException;

/**
 * Zet de kortingsregel op de openstaande order of haalt hem weg. De korting is een eigen regel met
 * een negatief bedrag, zodat factuur en 10%-reservering kloppen met wat er echt betaald is.
 */
final class ApplyRate
{
    public const string DISCOUNT_LINE_TYPE = 'discount';

    /**
     * @param  bool  $keepRequest  Bij afwijzing blijft de aanvraag zichtbaar (tarief actie, status afgewezen);
     *                             de order staat dan op de standaardprijs.
     */
    public function __invoke(Entry $entry, EntryRate $rate, bool $keepRequest = false): Order
    {
        $order = $entry->order()->first();

        if ($order === null || ! $order->isPending()) {
            throw new InvalidArgumentException('Er is geen openstaande order voor deze aanmelding.');
        }

        $order->lines()->where('lineable_type', self::DISCOUNT_LINE_TYPE)->delete();

        if ($rate === EntryRate::Discount && $entry->discount_status === DiscountStatus::Approved) {
            $settings = $entry->edition->settings;
            $base = $order->lines()->first();
            $discount = $base->unit_price_cents - $settings->discountedCents($base->unit_price_cents);

            $order->lines()->create([
                'lineable_type' => self::DISCOUNT_LINE_TYPE,
                'lineable_id' => $entry->getKey(),
                'description' => "Korting kortingsactie ({$settings->discountPercentage}%): filmpje gedeeld en De Gouden Bol getagd",
                'quantity' => 1,
                'unit_price_cents' => -$discount,
                'vat_rate' => $base->vat_rate,
                'counts_for_charity' => true,
                'sort' => 2,
                ...OrderLine::amounts(1, -$discount, $base->vat_rate),
            ]);
        }

        if (! $keepRequest) {
            $entry->forceFill(['rate' => $rate])->save();
        }

        $order->recalculateTotals();

        return $order->refresh();
    }
}

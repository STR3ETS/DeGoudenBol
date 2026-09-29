<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Enums\PlacementStatus;
use App\Domain\Commerce\Exceptions\SponsoringException;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Commerce\Models\Placement;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Edition\Models\Edition;
use Illuminate\Support\Facades\DB;

/**
 * Sponsorfactuur: één order voor alle gereserveerde plaatsingen die nog niet gefactureerd zijn.
 * Omschrijvingen noemen product en plek, nooit een positie. Regels tellen mee voor de 10%-reservering.
 */
final class CreateSponsorOrder
{
    public function __invoke(Sponsor $sponsor, Edition $edition): Order
    {
        return DB::transaction(function () use ($sponsor, $edition): Order {
            $placements = Placement::query()
                ->where('sponsor_id', $sponsor->getKey())
                ->where('edition_id', $edition->getKey())
                ->where('status', PlacementStatus::Draft)
                ->whereNull('order_line_id')
                ->with(['product', 'province', 'entry'])
                ->orderBy('id')
                ->get();

            if ($placements->isEmpty()) {
                throw new SponsoringException('Er zijn geen gereserveerde plaatsingen die nog gefactureerd moeten worden.');
            }

            $order = Order::query()->create([
                'edition_id' => $edition->getKey(),
                'orderable_type' => $sponsor->getMorphClass(),
                'orderable_id' => $sponsor->getKey(),
                'status' => OrderStatus::Pending,
                'currency' => 'EUR',
                'subtotal_cents' => 0,
                'vat_cents' => 0,
                'total_cents' => 0,
            ]);

            foreach ($placements as $index => $placement) {
                $line = $order->lines()->create([
                    'lineable_type' => $placement->getMorphClass(),
                    'lineable_id' => $placement->getKey(),
                    'description' => "{$placement->product->name} – {$placement->locationLabel()} – De Gouden Bol {$edition->year}",
                    'quantity' => 1,
                    'unit_price_cents' => $placement->price_cents,
                    'vat_rate' => $placement->product->vat_rate,
                    'counts_for_charity' => true,
                    'sort' => $index + 1,
                    ...OrderLine::amounts(1, $placement->price_cents, $placement->product->vat_rate),
                ]);

                $placement->forceFill(['order_line_id' => $line->getKey()])->save();
            }

            $order->recalculateTotals();

            return $order->refresh();
        });
    }
}

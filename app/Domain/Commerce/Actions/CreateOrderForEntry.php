<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Commerce\Models\Package;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use Carbon\CarbonInterface;

/**
 * Maakt de order met één pakketregel voor een inschrijving. De omschrijving noemt
 * de inhoud van het pakket, nooit een positie.
 */
final class CreateOrderForEntry
{
    public function __invoke(Entry $entry, Package $package, ParticipantUser $buyer, ?CarbonInterface $expiresAt = null): Order
    {
        $order = Order::query()->create([
            'edition_id' => $entry->edition_id,
            'orderable_type' => $entry->getMorphClass(),
            'orderable_id' => $entry->getKey(),
            'company_id' => $entry->company_id,
            'participant_user_id' => $buyer->getKey(),
            'status' => OrderStatus::Pending,
            'currency' => 'EUR',
            'subtotal_cents' => 0,
            'vat_cents' => 0,
            'total_cents' => 0,
            'expires_at' => $expiresAt,
        ]);

        $order->lines()->create([
            'lineable_type' => $package->getMorphClass(),
            'lineable_id' => $package->getKey(),
            'description' => "{$package->name} – De Gouden Bol {$entry->edition->year}",
            'quantity' => 1,
            'unit_price_cents' => $package->price_cents,
            'vat_rate' => $package->vat_rate,
            'counts_for_charity' => true,
            'sort' => 1,
            ...OrderLine::amounts(1, $package->price_cents, $package->vat_rate),
        ]);

        $order->recalculateTotals();

        return $order->refresh();
    }
}

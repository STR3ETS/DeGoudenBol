<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use Illuminate\Support\Facades\DB;

/**
 * Zichtbaarheidsplek kopen vanuit het portaal: plek vasthouden en een eigen order met factuur maken.
 */
final class OrderVisibilitySpot
{
    public function __construct(private readonly ReserveVisibilitySpot $reserve) {}

    public function __invoke(Entry $entry, int $position, ParticipantUser $buyer): Order
    {
        return DB::transaction(function () use ($entry, $position, $buyer): Order {
            $minutes = (int) config('commerce.reservation_minutes', 60);
            $spot = ($this->reserve)($entry, $position, now()->addMinutes($minutes + 15));
            $vat = (float) config('commerce.default_vat_rate', 21.0);

            $order = Order::query()->create([
                'edition_id' => $entry->edition_id,
                'orderable_type' => $spot->getMorphClass(),
                'orderable_id' => $spot->getKey(),
                'company_id' => $entry->company_id,
                'participant_user_id' => $buyer->getKey(),
                'status' => OrderStatus::Pending,
                'currency' => 'EUR',
                'subtotal_cents' => 0,
                'vat_cents' => 0,
                'total_cents' => 0,
                'expires_at' => now()->addMinutes($minutes),
                'metadata' => ['email' => $buyer->email],
            ]);

            $order->lines()->create([
                'lineable_type' => $spot->getMorphClass(),
                'lineable_id' => $spot->getKey(),
                'description' => CreateOrderForEntry::visibilitySpotDescription($position, $entry->province->name),
                'quantity' => 1,
                'unit_price_cents' => $spot->price_cents,
                'vat_rate' => $vat,
                'counts_for_charity' => true,
                'sort' => 1,
                ...OrderLine::amounts(1, $spot->price_cents, $vat),
            ]);

            $order->recalculateTotals();
            $spot->forceFill(['order_id' => $order->getKey()])->save();

            return $order->refresh();
        });
    }
}

<?php

namespace App\Domain\Intake\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderLine;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Platform\Services\AuditLogger;
use App\Support\DutchTime;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ophaalservice aanvragen of de vrijdag wijzigen (briefing 29 okt §5). Alleen op open vrijdagen met
 * capaciteit en binnen de wijzigingstermijn. Een nieuwe aanvraag krijgt een order voor de
 * voorrijkosten; de ophaalservice is pas actief na betaling.
 */
final class RequestPickup
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{company_name: string, contact_name: string, phone: string, street: string, house_number: string, postcode: string, city: string, instructions?: string|null}  $data
     */
    public function __invoke(Entry $entry, PickupDay $day, array $data, ParticipantUser $user): PickupRequest
    {
        $settings = $entry->edition->settings;

        if (! $settings->pickupEnabled) {
            throw new InvalidArgumentException('De ophaalservice is voor deze editie niet beschikbaar.');
        }

        if ($day->edition_id !== $entry->edition_id || ! $day->is_open) {
            throw new InvalidArgumentException('Deze vrijdag is niet beschikbaar voor de ophaalservice.');
        }

        if (DutchTime::display($day->date)->dayOfWeekIso !== 5) {
            throw new InvalidArgumentException('De ophaalservice rijdt alleen op vrijdag.');
        }

        if (! in_array($entry->status, [EntryStatus::Registered, EntryStatus::Scheduled, EntryStatus::FreshnessExpired], true)) {
            throw new InvalidArgumentException('Voor deze inschrijving kan geen ophaalmoment (meer) worden gekozen.');
        }

        if ($day->changeDeadline($settings->changeDeadlineHours)->isPast()) {
            throw new InvalidArgumentException('Deze vrijdag is te dichtbij om nog te kiezen.');
        }

        $existing = $entry->pickupRequest()->first();

        if ($existing !== null && $existing->status !== PickupStatus::Cancelled) {
            if (! $existing->status->isChangeable()) {
                throw new InvalidArgumentException('Uw oliebollen zijn al onderweg of opgehaald; wijzigen kan niet meer.');
            }

            if ($existing->day->changeDeadline($settings->changeDeadlineHours)->isPast()) {
                throw new InvalidArgumentException('De termijn om uw ophaalmoment te wijzigen is verstreken. Neem contact met ons op.');
            }
        }

        if ($entry->delivery_slot_id !== null && $entry->deliverySlot?->starts_at->isPast()) {
            throw new InvalidArgumentException('Uw aanlevermoment is al geweest; kies geen ophaalservice meer.');
        }

        return DB::transaction(function () use ($entry, $day, $data, $user, $existing, $settings): PickupRequest {
            $locked = PickupDay::query()->lockForUpdate()->findOrFail($day->getKey());

            $taken = $locked->requests()
                ->whereIn('status', PickupStatus::occupying())
                ->when($existing, fn ($query) => $query->whereKeyNot($existing->getKey()))
                ->count();

            if ($taken >= $locked->capacity) {
                throw new SlotFullException('Deze vrijdag is vol.');
            }

            $attributes = [
                'pickup_day_id' => $locked->getKey(),
                'participant_user_id' => $user->getKey(),
                'company_name' => trim($data['company_name']),
                'contact_name' => trim($data['contact_name']),
                'phone' => trim($data['phone']),
                'street' => trim($data['street']),
                'house_number' => trim($data['house_number']),
                'postcode' => strtoupper(trim($data['postcode'])),
                'city' => trim($data['city']),
                'instructions' => filled($data['instructions'] ?? null) ? trim((string) $data['instructions']) : null,
            ];

            if ($existing !== null && $existing->status !== PickupStatus::Cancelled) {
                $existing->forceFill([...$attributes, 'route_order' => null, 'driver_name' => $existing->pickup_day_id === $locked->getKey() ? $existing->driver_name : null])->save();
                $this->audit->record('pickup.changed', $existing, ['day' => $locked->date->toDateString()]);
                $this->refreshOrderDescription($existing->order, $locked);

                return $existing->refresh();
            }

            $request = $existing ?? new PickupRequest;
            $request->forceFill([
                ...$attributes,
                'edition_id' => $entry->edition_id,
                'entry_id' => $entry->getKey(),
                'status' => PickupStatus::Requested,
                'order_id' => null,
                'paid_at' => null,
                'scheduled_at' => null,
                'picked_up_at' => null,
                'received_at' => null,
                'cancelled_at' => null,
                'driver_name' => null,
                'route_order' => null,
            ])->save();

            $order = Order::query()->create([
                'edition_id' => $entry->edition_id,
                'orderable_type' => $request->getMorphClass(),
                'orderable_id' => $request->getKey(),
                'company_id' => $entry->company_id,
                'participant_user_id' => $user->getKey(),
                'status' => OrderStatus::Pending,
                'currency' => 'EUR',
                'subtotal_cents' => 0,
                'vat_cents' => 0,
                'total_cents' => 0,
                'metadata' => ['email' => $user->email],
            ]);

            $vat = (float) config('commerce.default_vat_rate', 21.0);

            $order->lines()->create([
                'lineable_type' => $request->getMorphClass(),
                'lineable_id' => $request->getKey(),
                'description' => $this->description($locked),
                'quantity' => 1,
                'unit_price_cents' => $settings->pickupFeeCents,
                'vat_rate' => $vat,
                'counts_for_charity' => true,
                'sort' => 1,
                ...OrderLine::amounts(1, $settings->pickupFeeCents, $vat),
            ]);

            $order->recalculateTotals();
            $request->forceFill(['order_id' => $order->getKey()])->save();

            $this->audit->record('pickup.requested', $request, ['day' => $locked->date->toDateString()]);

            return $request->refresh();
        });
    }

    private function description(PickupDay $day): string
    {
        return 'Ophaalservice De Gouden Bol '.$day->date->year.' (voorrijkosten), '.DutchTime::format($day->date, 'dddd D MMMM');
    }

    private function refreshOrderDescription(?Order $order, PickupDay $day): void
    {
        if ($order !== null && $order->isPending()) {
            $order->lines()->update(['description' => $this->description($day)]);
        }
    }
}

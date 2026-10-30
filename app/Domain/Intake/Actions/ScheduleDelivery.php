<?php

namespace App\Domain\Intake\Actions;

use App\Domain\Commerce\Enums\OrderStatus;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Services\DeliveryCode;
use App\Domain\Participants\Enums\DeliveryMethod;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\DeliverySlot;
use App\Domain\Participants\Models\Entry;
use App\Domain\Platform\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stap 2 uit het kernproces: de deelnemer kiest (of wijzigt) een aanleverslot en krijgt een
 * aanlevercode voor het QR-aanleverbewijs.
 */
final class ScheduleDelivery
{
    public function __construct(
        private readonly DeliveryCode $codes,
        private readonly AuditLogger $audit,
    ) {}

    public function __invoke(Entry $entry, DeliverySlot $slot): Entry
    {
        if ($slot->edition_id !== $entry->edition_id) {
            throw new InvalidArgumentException('Het aanleverslot hoort bij een andere editie.');
        }

        if (! in_array($entry->status, [EntryStatus::Registered, EntryStatus::Scheduled, EntryStatus::FreshnessExpired], true)) {
            throw new InvalidArgumentException('Deze inschrijving kan geen aanleverslot (meer) kiezen.');
        }

        $pickup = $entry->pickupRequest()->first();

        if ($pickup !== null && $pickup->status->isActive()) {
            throw new InvalidArgumentException('U heeft de ophaalservice al betaald. Wilt u toch zelf brengen? Neem dan contact met ons op.');
        }

        $deadlineHours = $entry->edition->settings->changeDeadlineHours;

        if ($entry->deliverySlot !== null && $entry->delivery_slot_id !== $slot->getKey() && $entry->deliverySlot->starts_at->subHours($deadlineHours)->isPast()) {
            throw new InvalidArgumentException("Wijzigen kan tot {$deadlineHours} uur voor uw aanlevermoment. Neem contact met ons op.");
        }

        return DB::transaction(function () use ($entry, $slot, $pickup): Entry {
            $locked = DeliverySlot::query()->lockForUpdate()->findOrFail($slot->getKey());

            $taken = $locked->entries()->occupyingPlace()->whereKeyNot($entry->getKey())->count();

            if ($taken >= $locked->capacity) {
                throw new SlotFullException('Dit aanleverslot is vol.');
            }

            // Een onbetaalde ophaalaanvraag vervalt zodra de bakker kiest om zelf te brengen.
            if ($pickup !== null && $pickup->status === PickupStatus::Requested) {
                $pickup->forceFill(['status' => PickupStatus::Cancelled, 'cancelled_at' => now()])->save();
                $pickup->order?->forceFill(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()])->save();
            }

            $entry->forceFill([
                'delivery_method' => DeliveryMethod::Self,
                'delivery_slot_id' => $locked->getKey(),
                'scheduled_at' => now(),
                'delivery_code' => $entry->delivery_code ?? $this->codes->generate(),
                'status' => $entry->status === EntryStatus::Registered ? EntryStatus::Scheduled : $entry->status,
            ])->save();

            $this->audit->record('entry.scheduled', $entry, ['slot' => $locked->getKey(), 'starts_at' => $locked->starts_at->toIso8601String()]);

            return $entry->refresh();
        });
    }
}

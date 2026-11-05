<?php

namespace App\Domain\Intake\Listeners;

use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Intake\Actions\RequestPickup;
use App\Domain\Intake\Exceptions\SlotFullException;
use App\Domain\Intake\Models\PickupDay;
use App\Domain\Participants\Models\Entry;
use InvalidArgumentException;

/**
 * Ophalen gekozen bij de aanmelding, met vrijdag en ophaallocatie: na de betaling legt dit de ophaalaanvraag
 * direct vast, zodat de bakker niets meer hoeft te doen. Is de vrijdag inmiddels vol of niet meer te kiezen,
 * dan blijft het open en vraagt de bedankpagina (en de herinnering) om een andere vrijdag.
 */
final class RequestPreferredPickup
{
    public function __construct(private readonly RequestPickup $requestPickup) {}

    public function handle(OrderPaid $event): void
    {
        $entry = $event->order->orderable;

        if (! $entry instanceof Entry || ! $entry->pickup_prepaid || $entry->pickupRequest()->exists()) {
            return;
        }

        $preference = $entry->pickup_preference ?? [];
        $day = isset($preference['pickup_day_id']) ? PickupDay::query()->where('edition_id', $entry->edition_id)->find($preference['pickup_day_id']) : null;
        $owner = $entry->company->owners()->first();

        if ($day === null || $owner === null || blank($preference['street'] ?? null)) {
            return;
        }

        try {
            ($this->requestPickup)($entry->refresh(), $day, [
                'company_name' => $entry->company->name,
                'contact_name' => $entry->company->contact_name ?: $owner->name,
                'phone' => (string) ($preference['phone'] ?? $entry->company->contact_phone ?? ''),
                'street' => (string) $preference['street'],
                'house_number' => (string) ($preference['house_number'] ?? ''),
                'postcode' => (string) ($preference['postcode'] ?? ''),
                'city' => (string) ($preference['city'] ?? ''),
                'instructions' => $preference['instructions'] ?? null,
            ], $owner);
        } catch (SlotFullException|InvalidArgumentException) {
            // Blijft open; de bakker kiest op de bedankpagina een andere vrijdag.
        }
    }
}

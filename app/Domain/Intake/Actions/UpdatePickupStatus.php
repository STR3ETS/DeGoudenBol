<?php

namespace App\Domain\Intake\Actions;

use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Intake\Notifications\PickupConfirmedNotification;
use App\Domain\Platform\Services\AuditLogger;
use App\Models\User;
use App\Support\Mail\SendOnce;
use InvalidArgumentException;

/**
 * Beheer van de ophaalservice: inplannen (chauffeur en volgorde), onderweg, opgehaald, ontvangen of
 * annuleren. Bij inplannen krijgt de bakker één keer per vrijdag bericht.
 */
final class UpdatePickupStatus
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function __invoke(PickupRequest $request, PickupStatus $status, ?User $by = null, ?string $driver = null, ?int $routeOrder = null): PickupRequest
    {
        if ($status !== PickupStatus::Cancelled && ! $request->isPaid()) {
            throw new InvalidArgumentException('De ophaalservice is pas actief na betaling van de voorrijkosten.');
        }

        $timestamps = match ($status) {
            PickupStatus::Scheduled => ['scheduled_at' => now()],
            PickupStatus::PickedUp => ['picked_up_at' => now()],
            PickupStatus::Received => ['received_at' => now()],
            PickupStatus::Cancelled => ['cancelled_at' => now()],
            default => [],
        };

        $request->forceFill([
            'status' => $status,
            ...$timestamps,
            ...($driver !== null ? ['driver_name' => $driver] : []),
            ...($routeOrder !== null ? ['route_order' => $routeOrder] : []),
        ])->save();

        $this->audit->record('pickup.status', $request, ['status' => $status->value, 'driver' => $request->driver_name], $by);

        if ($status === PickupStatus::Scheduled) {
            $request->loadMissing('entry.company', 'day');

            foreach ($request->entry->company->owners() as $owner) {
                $this->mail->send($owner, new PickupConfirmedNotification($request, scheduled: true), "pickup:{$request->getKey()}:scheduled:{$request->day->date->toDateString()}");
            }
        }

        return $request;
    }
}

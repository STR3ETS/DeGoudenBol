<?php

namespace App\Domain\Intake\Listeners;

use App\Domain\Commerce\Events\OrderPaid;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Intake\Notifications\PickupConfirmedNotification;
use App\Domain\Intake\Services\DeliveryCode;
use App\Domain\Participants\Enums\DeliveryMethod;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Platform\Services\AuditLogger;
use App\Support\Mail\SendOnce;
use Illuminate\Support\Facades\DB;

/**
 * Betaalde voorrijkosten: de ophaalservice is actief. De inschrijving staat dan ingepland met de
 * methode "laten ophalen" en krijgt een deelnemercode; een eerder gekozen tijdslot komt vrij.
 */
final class ConfirmPickupOnOrderPaid
{
    public function __construct(
        private readonly DeliveryCode $codes,
        private readonly AuditLogger $audit,
        private readonly SendOnce $mail,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $request = $event->order->orderable;

        if (! $request instanceof PickupRequest) {
            return;
        }

        DB::transaction(function () use ($request): void {
            $request = PickupRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($request->status !== PickupStatus::Requested) {
                return;
            }

            $request->forceFill(['status' => PickupStatus::Paid, 'paid_at' => now()])->save();

            $entry = $request->entry;
            $entry->forceFill([
                'delivery_method' => DeliveryMethod::Pickup,
                'delivery_slot_id' => null,
                'delivery_code' => $entry->delivery_code ?? $this->codes->generate(),
                'scheduled_at' => now(),
                'status' => in_array($entry->status, [EntryStatus::Registered, EntryStatus::FreshnessExpired], true) ? EntryStatus::Scheduled : $entry->status,
            ])->save();

            $this->audit->record('pickup.paid', $request, ['day' => $request->day->date->toDateString()], null);
        });

        $request->refresh()->loadMissing('entry.company');

        foreach ($request->entry->company->owners() as $owner) {
            $this->mail->send($owner, new PickupConfirmedNotification($request), "pickup:{$request->getKey()}:paid");
        }
    }
}

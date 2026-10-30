<?php

namespace App\Domain\Intake\Listeners;

use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Intake\Events\EntryReceived;
use App\Domain\Intake\Notifications\EntryReceivedNotification;
use App\Support\Mail\SendOnce;

/**
 * Ontvangst: een opgehaalde inzending krijgt de status "ontvangen" en de bakker krijgt één keer
 * bericht dat de keuring is gestart.
 */
final class HandleEntryReceived
{
    public function __construct(private readonly SendOnce $mail) {}

    public function handle(EntryReceived $event): void
    {
        $entry = $event->entry->loadMissing(['company', 'pickupRequest']);
        $pickup = $entry->pickupRequest;

        if ($pickup !== null && $pickup->status->isActive() && $pickup->status !== PickupStatus::Received) {
            $pickup->forceFill(['status' => PickupStatus::Received, 'received_at' => now()])->save();
        }

        foreach ($entry->company->owners() as $owner) {
            $this->mail->send($owner, new EntryReceivedNotification($entry), "entry:{$entry->getKey()}:received");
        }
    }
}

<?php

namespace App\Domain\Participants\Listeners;

use App\Domain\Participants\Events\EntryRegistered;
use App\Domain\Participants\Notifications\EntryRegisteredNotification;
use App\Support\Mail\SendOnce;

/**
 * Betaling ontvangen: bevestiging met factuur en de volgende stap. Eén keer per inschrijving, ook als
 * de webhook en de terugkeerpagina allebei de betaling verwerken.
 */
final class SendRegistrationConfirmation
{
    public function __construct(private readonly SendOnce $mail) {}

    public function handle(EntryRegistered $event): void
    {
        $entry = $event->entry->loadMissing('company');

        foreach ($entry->company->owners() as $owner) {
            $this->mail->send($owner, new EntryRegisteredNotification($entry), "entry:{$entry->getKey()}:registered");
        }
    }
}

<?php

namespace App\Domain\Participants\Listeners;

use App\Domain\Participants\Events\DiscountRequested;
use App\Domain\Participants\Notifications\DiscountRequestedNotification;
use App\Support\Mail\SendOnce;

/**
 * Aanmelding met kortingsaanvraag: de eigenaar krijgt uitleg en een portaallink.
 */
final class SendDiscountRequestConfirmation
{
    public function __construct(private readonly SendOnce $mail) {}

    public function handle(DiscountRequested $event): void
    {
        $entry = $event->entry->loadMissing('company');

        foreach ($entry->company->owners() as $owner) {
            $this->mail->send($owner, new DiscountRequestedNotification($entry), "entry:{$entry->getKey()}:discount-requested");
        }
    }
}

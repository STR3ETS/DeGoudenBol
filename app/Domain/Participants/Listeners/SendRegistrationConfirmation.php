<?php

namespace App\Domain\Participants\Listeners;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Participants\Events\EntryRegistered;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Notifications\EntryRegisteredNotification;

final class SendRegistrationConfirmation
{
    public function handle(EntryRegistered $event): void
    {
        $entry = $event->entry->loadMissing('company');

        $entry->company->users()
            ->wherePivot('role', CompanyUserRole::Owner->value)
            ->get()
            ->each(fn (ParticipantUser $owner) => $owner->notify(new EntryRegisteredNotification($entry)));
    }
}

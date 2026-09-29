<?php

namespace App\Domain\Ranking\Listeners;

use App\Domain\Participants\Enums\CompanyUserRole;
use App\Domain\Ranking\Enums\FinalistStatus;
use App\Domain\Ranking\Events\EditionFrozen;
use App\Domain\Ranking\Models\Finalist;
use App\Domain\Ranking\Notifications\FinalInvitationNotification;

/**
 * Na de bevriezing: iedere provinciewinnaar krijgt de uitnodiging voor de finale.
 */
final class InviteFinalists
{
    public function handle(EditionFrozen $event): void
    {
        $finalists = Finalist::query()
            ->where('edition_id', $event->edition->getKey())
            ->where('status', FinalistStatus::Invited)
            ->with('entry.company.users')
            ->get();

        foreach ($finalists as $finalist) {
            $finalist->entry->company->users
                ->filter(fn ($user) => $user->pivot->role === CompanyUserRole::Owner)
                ->each->notify(new FinalInvitationNotification($finalist));
        }
    }
}

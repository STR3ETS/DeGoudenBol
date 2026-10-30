<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Landelijke publicatie, vertrouwelijk pad onder de publicatiedrempel: niets openbaar. Het
 * persoonlijke rapport had de bakker al binnen drie dagen na de keuring.
 */
class ConfidentialResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Entry $entry) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $entry = $this->entry->loadMissing(['company', 'edition']);

        return (new GoldenMail)
            ->subject("De landelijke uitslag van De Gouden Bol {$entry->edition->year} is bekend")
            ->hero('De landelijke uitslag', 'is bekend.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("Vandaag is de landelijke ranglijst van De Gouden Bol {$entry->edition->year} gepubliceerd. Het resultaat van **{$entry->public_name}** ligt onder de publicatiedrempel en wordt niet openbaar gemaakt. Niemand buiten uw bedrijf ziet het.")
            ->line('Uw persoonlijke keuringsrapport, met de sterke punten en concrete verbeterpunten van het panel, blijft voor u beschikbaar in het portaal.')
            ->action('Naar uw rapport', route('portaal.rapport', $entry->company));
    }
}

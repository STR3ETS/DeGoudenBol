<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Ranking\Models\Finalist;
use App\Support\DutchTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Uitnodiging voor de landelijke finale (onder embargo: de mail noemt de provinciale titel niet publiek).
 */
class FinalInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Finalist $finalist) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $finalist = $this->finalist->loadMissing(['entry.company', 'province', 'edition']);
        $edition = $finalist->edition;

        $message = (new MailMessage)
            ->subject("Uitnodiging voor de landelijke finale van De Gouden Bol {$edition->year}")
            ->greeting("Beste {$notifiable->name},")
            ->line("**{$finalist->entry->public_name}** is uitgenodigd voor de landelijke finale namens {$finalist->province->name}. Tot de publicatie op ".DutchTime::format($edition->main_publication_at, 'D MMMM').' is dit vertrouwelijk.');

        if ($edition->final_test_day) {
            $message->line('De finaletest is op '.DutchTime::date($edition->final_test_day).'. Het gaat om een nieuwe, blinde beoordeling; de provinciale score telt niet mee.');
        }

        return $message
            ->action('Bevestig uw deelname in het portaal', route('portaal.uitslag', $finalist->entry->company))
            ->line('Kunt u niet meedoen? Meld dat dan in het portaal; de plek gaat dan naar de volgende op de lijst. De provinciale titel blijft van u.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

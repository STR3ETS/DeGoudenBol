<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use App\Support\DutchTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntryRegisteredNotification extends Notification implements ShouldQueue
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

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $entry = $this->entry->loadMissing(['edition', 'province', 'company']);
        $edition = $entry->edition;
        $portalUrl = app(MagicLinkService::class)->url($notifiable, minutes: 60 * 24 * 3);

        $message = (new MailMessage)
            ->subject("Je aanmelding voor De Gouden Bol {$edition->year} is bevestigd")
            ->greeting("Beste {$notifiable->name},")
            ->line("De betaling is ontvangen en **{$entry->public_name}** doet mee aan De Gouden Bol {$edition->year} in de provincie {$entry->province->name}.")
            ->line('In het deelnemersportaal beheer je je profiel, kies je straks een aanleverslot en vind je je facturen en, na de test, het vertrouwelijke rapport.')
            ->action('Naar het deelnemersportaal', $portalUrl)
            ->line('Deze inloglink is drie dagen geldig en eenmalig te gebruiken. Daarna kun je op het portaal een nieuwe link aanvragen of een wachtwoord instellen.');

        if ($edition->first_test_day) {
            $message->line('De testdagen beginnen op '.DutchTime::date($edition->first_test_day).'. Je ontvangt op tijd bericht over het aanleveren.');
        }

        return $message->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

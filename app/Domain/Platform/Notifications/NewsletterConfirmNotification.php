<?php

namespace App\Domain\Platform\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterConfirmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $url) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bevestig je inschrijving voor de nieuwsbrief van De Gouden Bol')
            ->greeting('Hallo,')
            ->line('Je hebt je aangemeld voor de nieuwsbrief van De Gouden Bol: uitslagen per provincie, de finale en de cadeaubonnenactie. Bevestig je inschrijving met de knop hieronder.')
            ->action('Inschrijving bevestigen', $this->url)
            ->line('Heb je je niet aangemeld? Dan kun je deze mail negeren; zonder bevestiging sturen we niets.')
            ->salutation('Met vriendelijke groet, De Gouden Bol');
    }
}

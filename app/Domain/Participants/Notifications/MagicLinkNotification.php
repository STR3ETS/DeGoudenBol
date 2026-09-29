<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ?string $redirectTo = null) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $url = app(MagicLinkService::class)->url($notifiable, minutes: 30, redirectTo: $this->redirectTo);

        return (new MailMessage)
            ->subject('Je inloglink voor het deelnemersportaal')
            ->greeting("Beste {$notifiable->name},")
            ->line('Met de knop hieronder log je direct in op het deelnemersportaal van De Gouden Bol.')
            ->action('Inloggen', $url)
            ->line('De link is dertig minuten geldig en werkt één keer. Heb je hem niet aangevraagd? Dan kun je deze e-mail negeren.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

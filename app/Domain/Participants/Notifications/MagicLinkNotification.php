<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use App\Support\Mail\GoldenMail;
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

        return (new GoldenMail)
            ->subject('Uw inloglink voor het deelnemersportaal')
            ->hero('Welkom', 'terug.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line('Met de knop hieronder logt u direct in op het deelnemersportaal van De Gouden Bol.')
            ->action('Inloggen', $url)
            ->line('De link is dertig minuten geldig en werkt één keer. Heeft u hem niet aangevraagd? Dan kunt u deze e-mail negeren.');
    }
}

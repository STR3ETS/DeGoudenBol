<?php

namespace App\Domain\Commerce\Notifications;

use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Participants\Models\ParticipantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Een sponsor wil "Bakt met [sponsor]" op het profiel; de deelnemer beslist zelf.
 */
class SponsorLinkRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SponsorLink $link) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $link = $this->link->loadMissing(['sponsor', 'company']);

        return (new MailMessage)
            ->subject("{$link->sponsor->name} wil op uw profiel: \"Bakt met {$link->sponsor->name}\"")
            ->greeting("Beste {$notifiable->name},")
            ->line("**{$link->sponsor->name}** heeft een sponsorkoppeling met {$link->company->name} aangevraagd. Na uw bevestiging toont uw openbare profiel de regel \"Bakt met {$link->sponsor->name}\" met logo en link.")
            ->line('U beslist zelf; zonder bevestiging verschijnt er niets. Sponsoring heeft nooit invloed op de keuring of de uitslag.')
            ->action('Koppeling bekijken', route('portaal.sponsoren', $link->company))
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

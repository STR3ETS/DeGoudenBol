<?php

namespace App\Domain\Marketing\Notifications;

use App\Domain\Marketing\Models\MediaContact;
use App\Domain\Marketing\Models\PressRelease;
use App\Support\DutchTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PressKitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PressRelease $release,
        public readonly MediaContact $contact,
        public readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $release = $this->release->loadMissing('province');
        $embargo = $release->isEmbargoed() ? DutchTime::format($release->embargo_until, 'dddd D MMMM YYYY, HH:mm').' uur' : null;

        $mail = (new MailMessage)
            ->subject(($embargo ? 'Onder embargo: ' : 'Persbericht: ').$release->title)
            ->greeting("Beste {$this->contact->name},");

        if ($embargo) {
            $mail->line("**Dit bericht is onder embargo tot {$embargo}.** Tot dat moment is de inhoud vertrouwelijk en niet voor publicatie.");
        }

        return $mail
            ->line("Hierbij het persbericht van De Gouden Bol over {$release->scopeLabel()}: *{$release->title}*.")
            ->action('Open de perskit', $this->url)
            ->line('De link is persoonlijk en tijdelijk geldig. Beeld en badges komen op de perskitpagina beschikbaar zodra het embargo is vervallen.')
            ->line('Vragen: '.config('press.contact_email').'.')
            ->salutation('Met vriendelijke groet, De Gouden Bol');
    }
}

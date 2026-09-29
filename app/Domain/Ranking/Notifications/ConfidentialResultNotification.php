<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Vertrouwelijk pad onder de publicatiedrempel: niets openbaar, wel een persoonlijk verbeteradvies.
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

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $entry = $this->entry->loadMissing(['company', 'edition']);

        return (new MailMessage)
            ->subject("Persoonlijk verbeteradvies voor {$entry->public_name}")
            ->greeting("Beste {$notifiable->name},")
            ->line("Het panel heeft de oliebollen van **{$entry->public_name}** blind beoordeeld. Het resultaat wordt niet openbaar gemaakt; in het portaal vind je een persoonlijk verbeteradvies zodra de redactie het heeft afgerond.")
            ->line('Niemand buiten jouw bedrijf ziet dit resultaat. Sterke punten staan voorop, daarna concrete ontwikkelkansen.')
            ->action('Naar het portaal', route('portaal.uitslag', $entry->company))
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

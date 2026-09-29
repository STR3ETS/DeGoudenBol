<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Ranking\Models\CorrectionCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Melding aan de deelnemer dat een correctiedossier is toegepast (docs/04 §3, randgevallen).
 */
class CorrectionAppliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly CorrectionCase $case) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $entry = $this->case->loadMissing('entry.company')->entry;
        $old = number_format((float) ($this->case->original_snapshot['total'] ?? 0), 1, ',', '.');
        $new = number_format((float) ($this->case->new_snapshot['total'] ?? 0), 1, ',', '.');

        return (new MailMessage)
            ->subject("Correctie op de uitslag van {$entry->public_name}")
            ->greeting("Beste {$notifiable->name},")
            ->line("Na een correctiedossier met twee goedkeuringen is de uitslag van **{$entry->public_name}** opnieuw berekend: van {$old} naar **{$new}**.")
            ->line('De nieuwe uitslag gaat mee in de eerstvolgende publicatiebatch; daarna wordt de Voorlijst opnieuw berekend. Het oorspronkelijke resultaat blijft in het archief bewaard.')
            ->action('Naar het portaal', route('portaal.uitslag', $entry->company))
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

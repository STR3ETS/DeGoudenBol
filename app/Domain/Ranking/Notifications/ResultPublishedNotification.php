<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Models\RankingPosition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Goed nieuws: het resultaat staat online. Alleen bij publicatie; een daling is in het portaal te zien.
 */
class ResultPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PublicationItem $item,
        public readonly ?RankingPosition $position,
    ) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $entry = $this->item->loadMissing(['entry.company', 'province', 'batch.edition'])->entry;
        $year = $this->item->batch->edition->year;
        $total = number_format($this->item->total(), 1, ',', '.');

        if ($this->item->isFinal()) {
            $message = (new MailMessage)
                ->subject("De uitslag van de landelijke finale {$year} staat online")
                ->greeting("Beste {$notifiable->name},")
                ->line("Het finalepanel heeft de oliebollen van **{$entry->public_name}** blind beoordeeld met een **{$total}**.");

            if ($this->position !== null) {
                $message->line("{$entry->public_name} staat op **plaats {$this->position->position}** van Nederland.");
            }

            return $message
                ->action('Bekijk de landelijke uitslag', route('finale'))
                ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
        }

        $message = (new MailMessage)
            ->subject("Je resultaat van De Gouden Bol {$year} staat online")
            ->greeting("Beste {$notifiable->name},")
            ->line("Het panel heeft de oliebollen van **{$entry->public_name}** blind beoordeeld. Het cijfer is **{$total}** en staat vanaf nu op de Voorlijst van {$this->item->province->name}.");

        if ($this->position !== null) {
            $message->line("Op dit moment staat {$entry->public_name} op **plaats {$this->position->position}** in {$this->item->province->name}. De lijst kan nog bewegen tot de bevriezing.");
        }

        return $message
            ->line('Je mag je vanaf nu "Officieel getest" noemen. In het portaal vind je je positie en, zodra de redactie klaar is, het vertrouwelijke rapport met sterke punten en ontwikkelkansen.')
            ->action('Bekijk je uitslag in het portaal', route('portaal.uitslag', $entry->company))
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

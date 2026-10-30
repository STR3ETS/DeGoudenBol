<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Ranking\Models\PublicationItem;
use App\Domain\Ranking\Models\RankingPosition;
use App\Support\Mail\GoldenMail;
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
            $message = (new GoldenMail)
                ->subject("De uitslag van de landelijke finale {$year} staat online")
                ->greeting("Beste {$notifiable->name},")
                ->line("Het finalepanel heeft de oliebollen van **{$entry->public_name}** blind beoordeeld met een **{$total}**.");

            if ($this->position !== null) {
                $message->line("{$entry->public_name} staat op **plaats {$this->position->position}** van Nederland.");
            }

            return $message
                ->action('Bekijk de landelijke uitslag', route('finale'));
        }

        $message = (new GoldenMail)
            ->subject("De uitslag van De Gouden Bol {$year} is bekend: {$entry->public_name}")
            ->hero('De uitslag is', 'bekend.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("De ranglijst van {$this->item->province->name} is gepubliceerd. **{$entry->public_name}** staat erop met een **{$total}**.");

        if ($this->position !== null) {
            $message->line("{$entry->public_name} staat op **plaats {$this->position->position}** in {$this->item->province->name}.");
        }

        return $message
            ->line('U mag zich vanaf nu "Officieel getest" noemen. Uw badges staan klaar in het portaal; uw persoonlijke rapport blijft daar ook beschikbaar.')
            ->action('Bekijk uw uitslag in het portaal', route('portaal.uitslag', $entry->company));
    }
}

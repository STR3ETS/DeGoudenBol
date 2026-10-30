<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Uitkomst van de kortingscontrole: goedgekeurd met betaallink, of afgewezen met de reden en de keuze
 * tussen opnieuw aanleveren en de standaardprijs.
 */
class DiscountReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Entry $entry, public readonly bool $approved) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $entry = $this->entry->loadMissing(['edition', 'order']);
        $order = $entry->order;
        $until = $entry->reservation_expires_at ? DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY') : null;

        $mail = (new GoldenMail)
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},");

        if ($this->approved) {
            return $mail
                ->subject("Korting goedgekeurd: rond de aanmelding van {$entry->public_name} af")
                ->hero('Uw korting is', 'goedgekeurd.')
                ->preheader('Bedankt voor het delen. U betaalt nu de actieprijs.')
                ->line('Bedankt voor het delen van uw filmpje. We hebben uw post bekeken en de korting goedgekeurd.')
                ->panel('Uw aanmelding', $entry->public_name, [
                    'Te betalen: '.$order?->formattedTotal().' incl. btw',
                    $until ? "Plek gereserveerd tot: {$until}" : null,
                ])
                ->action('Nu betalen', route('aanmelden.status', $order))
                ->line('Na de betaling ontvangt u de bevestiging en de factuur, en kiest u in het portaal hoe uw oliebollen bij ons komen.');
        }

        $portal = app(MagicLinkService::class)->url($notifiable, minutes: 60 * 24 * 3, redirectTo: route('portaal.dashboard'));

        return $mail
            ->subject("Kortingsactie: uw bewijs is nog niet goedgekeurd ({$entry->public_name})")
            ->hero('Uw korting is nog', 'niet goedgekeurd.')
            ->preheader('Lever opnieuw een link aan of betaal de standaardprijs.')
            ->line('We hebben uw aanvraag voor de kortingsactie bekeken, maar kunnen de korting nog niet toekennen.')
            ->panel('Toelichting', null, [(string) $entry->discount_note, $until ? "Uw plek blijft gereserveerd tot {$until}." : null])
            ->action('Opnieuw aanleveren of betalen', $portal)
            ->line('In het portaal levert u een nieuwe link aan, of kiest u voor de standaardprijs en rondt u de betaling direct af.');
    }
}

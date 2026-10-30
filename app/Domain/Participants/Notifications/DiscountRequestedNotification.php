<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aanmelding met de actieprijs ontvangen: uitleg over het bewijs, de portaallink en hoe lang de plek
 * vastgehouden wordt.
 */
class DiscountRequestedNotification extends Notification implements ShouldQueue
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

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $entry = $this->entry->loadMissing(['edition', 'province', 'order.lines']);
        $settings = $entry->edition->settings;
        $base = $entry->order?->lines->first();
        $portal = app(MagicLinkService::class)->url($notifiable, minutes: 60 * 24 * 3, redirectTo: route('portaal.dashboard'));
        $hasProof = $entry->discount_status === DiscountStatus::Submitted;

        return (new GoldenMail)
            ->subject("Aanmelding ontvangen: {$entry->public_name} doet mee met de kortingsactie")
            ->hero('Bedankt voor uw', 'aanmelding.')
            ->preheader('Uw plek is gereserveerd. Na goedkeuring van uw socialmedia-post betaalt u de actieprijs.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("We hebben de aanmelding van **{$entry->public_name}** voor De Gouden Bol {$entry->edition->year} in {$entry->province->name} ontvangen. U koos voor de kortingsactie: maak een filmpje, deel het op social media en tag De Gouden Bol. Na goedkeuring betaalt u {$settings->discountPercentage}% minder.")
            ->panel('Uw aanmelding', 'Kortingsactie', [
                $base ? 'Standaardprijs: '.Money::format($base->unit_price_cents).' excl. btw' : null,
                $base ? 'Actieprijs na goedkeuring: '.Money::format($settings->discountedCents($base->unit_price_cents)).' excl. btw' : null,
                'Status: '.($hasProof ? 'bewijs ontvangen, wij controleren het' : 'wacht op de link naar uw post'),
                $entry->reservation_expires_at ? 'Plek gereserveerd tot: '.DutchTime::format($entry->reservation_expires_at, 'D MMMM YYYY') : null,
            ])
            ->action($hasProof ? 'Naar uw dashboard' : 'Link naar uw post aanleveren', $portal)
            ->line($hasProof
                ? 'Zodra wij uw post hebben bekeken, ontvangt u een mail met de betaallink.'
                : 'Plaats het filmpje, tag De Gouden Bol en zet de link naar uw post in het portaal. Een schermafbeelding mag ook.')
            ->note('Liever direct betalen?', 'Dat kan altijd: kies in het portaal voor de standaardprijs en rond de betaling meteen af. Deze inloglink is drie dagen geldig.');
    }
}

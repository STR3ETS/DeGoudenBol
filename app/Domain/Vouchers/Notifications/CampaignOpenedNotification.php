<?php

namespace App\Domain\Vouchers\Notifications;

use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Vouchers\Models\VoucherCampaign;
use App\Support\DutchTime;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * De cadeaubonnenactie is gestart: winnaars invoeren vóór de deadline.
 */
class CampaignOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly VoucherCampaign $campaign) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): MailMessage
    {
        $campaign = $this->campaign->loadMissing('company');

        return (new MailMessage)
            ->subject('Uw cadeaubonnenactie is gestart')
            ->greeting("Beste {$notifiable->name},")
            ->line("Gefeliciteerd met de Top 10! Bij die plek hoort de cadeaubonnenactie: u geeft {$campaign->winner_count} cadeaubonnen van ".Money::format($campaign->voucher_value_cents).' weg aan klanten.')
            ->line('Kies de winnaars op uw eigen social media en voer hun naam en e-mailadres in het portaal in vóór **'.DutchTime::format($campaign->winners_deadline_at, 'dddd D MMMM, HH:mm').'**. Het platform mailt de winnaars een claimlink en geeft de bonnen uit.')
            ->line('Verzilveren gebeurt met de scanner in uw portaal, t/m '.DutchTime::date($campaign->last_redeem_day).'. Voert u minder winnaars in, dan vult Bonbeheer aan en wordt dat doorgefactureerd.')
            ->action('Winnaars invoeren', route('portaal.cadeaubonnen', $campaign->company))
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

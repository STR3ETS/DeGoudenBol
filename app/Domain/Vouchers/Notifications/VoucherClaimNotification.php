<?php

namespace App\Domain\Vouchers\Notifications;

use App\Domain\Vouchers\Models\VoucherWinner;
use App\Support\DutchTime;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Claimlink voor de winnaar: bevestigen, actievoorwaarden accepteren, eventueel beeldtoestemming.
 */
class VoucherClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly VoucherWinner $winner,
        public readonly string $token,
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
        $campaign = $this->winner->loadMissing('campaign.company')->campaign;

        return (new MailMessage)
            ->subject("Je hebt een cadeaubon gewonnen bij {$campaign->company->name}")
            ->greeting("Beste {$this->winner->first_name},")
            ->line("**{$campaign->company->name}** staat in de Top 10 van De Gouden Bol en geeft cadeaubonnen weg. Jij bent als winnaar gekozen: een cadeaubon van ".Money::format($campaign->voucher_value_cents).'.')
            ->line('Bevestig je bon via de knop hieronder. Je accepteert daarbij de actievoorwaarden; daarna ontvang je de digitale bon met QR-code.')
            ->action('Cadeaubon claimen', route('cadeaubon.claim', $this->token))
            ->line('De bon is te verzilveren t/m '.DutchTime::date($campaign->last_redeem_day).' bij '.$campaign->company->name.'. Daarna vervalt hij automatisch.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

<?php

namespace App\Domain\Vouchers\Notifications;

use App\Domain\Vouchers\Models\Voucher;
use App\Support\DutchTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * De digitale bon: link naar de bonpagina met QR-code (printbaar) en het leesbare bonnummer.
 */
class VoucherIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Voucher $voucher,
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
        $voucher = $this->voucher->loadMissing(['campaign.company', 'winner']);
        $company = $voucher->campaign->company;

        return (new MailMessage)
            ->subject("Je cadeaubon van {$company->name}: {$voucher->code}")
            ->greeting("Beste {$voucher->winner->first_name},")
            ->line("Hierbij je cadeaubon van **{$voucher->formattedValue()}** voor {$company->name}.")
            ->line("**Bonnummer:** {$voucher->code}")
            ->line('Toon de bon (met QR-code) op je telefoon of print hem. De medewerker scant de QR of typt het bonnummer in.')
            ->action('Open je cadeaubon', route('bon.toon', $this->token))
            ->line('Geldig t/m '.DutchTime::date($voucher->expires_at).'. Eén keer te gebruiken; daarna is de bon verzilverd.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

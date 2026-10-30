<?php

namespace App\Domain\Intake\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aanlevermoment bevestigd, met de deelnemercode, het aanleveradres en de instructies.
 */
class DeliveryScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Entry $entry, public readonly bool $reminder = false) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $entry = $this->entry->loadMissing(['edition', 'deliverySlot.testLocation', 'company']);
        $slot = $entry->deliverySlot;
        $location = $slot?->testLocation;
        $settings = $entry->edition->settings;
        $when = DutchTime::format($slot->starts_at, 'dddd D MMMM YYYY, HH:mm').' – '.DutchTime::format($slot->ends_at, 'HH:mm');

        return (new GoldenMail)
            ->subject($this->reminder ? "Herinnering: morgen brengt u uw oliebollen ({$entry->public_name})" : "Aanlevermoment bevestigd: {$entry->public_name}")
            ->hero($this->reminder ? 'Morgen is het' : 'Uw aanlevermoment is', $this->reminder ? 'zover.' : 'vastgelegd.')
            ->preheader(ucfirst($when).' · deelnemercode '.$entry->delivery_code)
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line($this->reminder
                ? "Een korte herinnering: morgen brengt u de oliebollen van **{$entry->public_name}**."
                : "Het aanlevermoment voor **{$entry->public_name}** is vastgelegd.")
            ->panel('Aanleveren', ucfirst($when), [
                $location ? "Adres: {$location->name}, {$location->street}, {$location->postcode} {$location->city}" : null,
                "Deelnemercode: {$entry->delivery_code}",
                "Meenemen: {$settings->piecesPerEntry} oliebollen in een doos of zak, zonder naam of logo",
            ])
            ->action('Aanleverbewijs openen', route('portaal.planning.bewijs', $entry->company))
            ->line('Toon bij aankomst het aanleverbewijs met QR-code, of noem uw deelnemercode.')
            ->note('Wilt u het moment wijzigen?', "Dat kan in het portaal tot {$settings->changeDeadlineHours} uur voor het aanlevermoment.");
    }
}

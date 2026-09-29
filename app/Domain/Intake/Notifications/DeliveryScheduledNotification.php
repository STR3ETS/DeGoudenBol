<?php

namespace App\Domain\Intake\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\DutchTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bevestiging van het aanleverslot met de aanlevercode en de instructies.
 */
class DeliveryScheduledNotification extends Notification implements ShouldQueue
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
        $entry = $this->entry->loadMissing(['edition', 'deliverySlot.testLocation', 'company']);
        $slot = $entry->deliverySlot;
        $location = $slot?->testLocation;
        $pieces = $entry->edition->settings->piecesPerEntry;

        $message = (new MailMessage)
            ->subject("Aanleverslot bevestigd: {$entry->public_name}")
            ->greeting("Beste {$notifiable->name},")
            ->line("Het aanleverslot voor **{$entry->public_name}** is vastgelegd.")
            ->line('**Wanneer:** '.DutchTime::format($slot->starts_at, 'dddd D MMMM YYYY, HH:mm').' – '.DutchTime::format($slot->ends_at, 'HH:mm'));

        if ($location) {
            $message->line("**Waar:** {$location->name}, {$location->street}, {$location->postcode} {$location->city}");
        }

        return $message
            ->line("**Aanlevercode:** {$entry->delivery_code}")
            ->line("Lever {$pieces} oliebollen aan in neutrale verpakking, zonder logo of naam. Toon bij aankomst het aanleverbewijs (QR) uit het portaal of noem de aanlevercode.")
            ->action('Aanleverbewijs openen', route('portaal.planning.bewijs', $entry->company))
            ->line('Wilt u het moment wijzigen? Dat kan in het portaal tot het slot begint.')
            ->salutation('Met vriendelijke groet, het team van De Gouden Bol');
    }
}

<?php

namespace App\Domain\Intake\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Betaald, maar nog geen aanlevermoment of ophaalvrijdag gekozen: dagelijkse herinnering met een directe
 * link naar de bedankpagina, waar de bakker zonder inloggen kiest (besluit Raphael 2 nov 2026).
 */
class PlanDeliveryReminderNotification extends Notification implements ShouldQueue
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
        $entry = $this->entry->loadMissing(['edition', 'order']);
        $pickup = $entry->pickup_prepaid;

        return (new GoldenMail)
            ->subject($pickup ? "Kies uw ophaalvrijdag ({$entry->public_name})" : "Kies uw aanlevermoment ({$entry->public_name})")
            ->hero('Nog één stap:', $pickup ? 'kies uw ophaalvrijdag.' : 'kies uw aanlevermoment.')
            ->preheader('Uw plek is betaald; kies nu wanneer uw oliebollen bij ons komen.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("Bedankt voor de aanmelding van **{$entry->public_name}**. Uw plek is betaald, maar we weten nog niet wanneer uw oliebollen bij ons komen.")
            ->line($pickup
                ? 'Kies de vrijdag waarop wij ze ophalen. Dat duurt een minuut.'
                : "Kies een moment om uw {$entry->edition->settings->piecesPerEntry} oliebollen te brengen. Dat duurt een minuut.")
            ->action($pickup ? 'Ophaalvrijdag kiezen' : 'Aanlevermoment kiezen', route('aanmelden.status', $entry->order))
            ->note('Al gekozen?', 'Dan kunt u deze mail negeren; hij is net verstuurd voordat uw keuze binnen was.');
    }
}

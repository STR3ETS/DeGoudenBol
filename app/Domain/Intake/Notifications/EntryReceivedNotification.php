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
 * Oliebollen ontvangen, keuring gestart (briefing 29 okt §11).
 */
class EntryReceivedNotification extends Notification implements ShouldQueue
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
        $entry = $this->entry->loadMissing(['edition', 'company']);
        $settings = $entry->edition->settings;
        $publication = $entry->edition->main_publication_at;

        return (new GoldenMail)
            ->subject("Uw oliebollen zijn ontvangen: de keuring is gestart ({$entry->public_name})")
            ->hero('Uw oliebollen zijn', 'in goede handen.')
            ->preheader('Ontvangen en geregistreerd. Het panel proeft blind; uw rapport volgt binnen enkele dagen.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line('Uw oliebollen zijn vandaag ontvangen en geregistreerd. Ze krijgen een eigen testnummer; het panel ziet nooit uw naam, logo of sponsors.')
            ->panel('Uw inzending', $entry->public_name, [
                'Deelnemercode: '.$entry->delivery_code,
                'Ontvangen op: '.DutchTime::format(now(), 'D MMMM YYYY, HH:mm'),
                "Persoonlijk rapport: uiterlijk {$settings->reportDueDays} dagen na de keuring in uw portaal",
            ])
            ->action('Naar uw dashboard', route('portaal.dashboard'))
            ->note('En de landelijke uitslag?', $publication
                ? 'De landelijke ranglijst maken wij bekend op '.DutchTime::format($publication, 'dddd D MMMM [om] HH:mm').'. Tot die tijd blijven alle andere beoordelingen vertrouwelijk.'
                : 'De landelijke ranglijst maken wij op een later, officieel aangekondigd moment bekend. Tot die tijd blijven alle andere beoordelingen vertrouwelijk.');
    }
}

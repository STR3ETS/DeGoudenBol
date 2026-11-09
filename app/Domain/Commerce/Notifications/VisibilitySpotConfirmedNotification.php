<?php

namespace App\Domain\Commerce\Notifications;

use App\Domain\Commerce\Models\VisibilitySpot;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Bevestiging van een betaalde zichtbaarheidsplek. Zegt uitdrukkelijk dat dit los staat van de test.
 */
class VisibilitySpotConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly VisibilitySpot $spot) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $spot = $this->spot->loadMissing(['entry', 'province', 'company']);

        return (new GoldenMail)
            ->subject("Extra zichtbaarheid in {$spot->province->name}: plek {$spot->position}")
            ->hero('U staat', 'uitgelicht.')
            ->preheader("Plek {$spot->position} in {$spot->province->name}, zichtbaar tot en met ".DutchTime::date($spot->ends_at).'.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("**{$spot->entry->public_name}** staat nu uitgelicht op de pagina van {$spot->province->name} en bovenaan het deelnemersoverzicht.")
            ->panel('Extra zichtbaarheid', "Plek {$spot->position} in {$spot->province->name}", [
                'Zichtbaar tot en met '.DutchTime::date($spot->ends_at),
                'Op de site staat dit als betaalde vermelding',
            ])
            ->action('Bekijk in uw portaal', route('portaal.aanmelding', $spot->company))
            ->note('Staat dit los van de test?', 'Ja. Deze plek is alleen voor zichtbaarheid. Uw score en plaats in de uitslag worden uitsluitend bepaald door het onafhankelijke testpanel.');
    }
}

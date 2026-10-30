<?php

namespace App\Domain\Ranking\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * "Uw keuringsrapport is beschikbaar": de mail uit het aangeleverde template (Emailtemplate Uitslag),
 * met voornaam, bedrijfsnaam, product, testdatum en de link naar het rapport.
 */
class ReportAvailableNotification extends Notification implements ShouldQueue
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
        $entry = $this->entry->loadMissing(['company', 'edition', 'confidentialReport']);
        $report = $entry->confidentialReport;
        $firstName = Str::before(trim((string) $notifiable->name), ' ') ?: $notifiable->name;
        $publication = $entry->edition->main_publication_at;

        return (new GoldenMail)
            ->subject('Uw keuringsrapport is beschikbaar | '.config('app.name'))
            ->hero('Uw keuringsrapport', 'staat voor u klaar.')
            ->preheader('Uw persoonlijke beoordelingsrapport staat klaar. Bekijk de uitslag van uw oliebol.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$firstName},")
            ->line('Het is zover. Het testpanel heeft uw ingezonden oliebol onafhankelijk beoordeeld. Uw persoonlijke keuringsrapport is nu beschikbaar.')
            ->line('In het rapport leest u hoe uw product scoort op onder andere smaak, structuur, versheid en totaalindruk, inclusief een toelichting van het panel.')
            ->panel('Uw persoonlijke keuring', 'Officieel beoordelingsrapport', [
                'Deelnemer: '.$entry->company->name,
                'Product: '.$entry->productLabel(),
                $report?->tested_on ? 'Testdatum: '.DutchTime::format($report->tested_on, 'D MMMM YYYY') : null,
            ], 'De beoordeling bestaat uit acht onderdelen en een gewogen totaalscore van maximaal 100 punten.')
            ->action('Bekijk uw keuringsrapport', route('portaal.rapport', $entry->company))
            ->line('U kunt het rapport ook downloaden via uw persoonlijke dashboard.')
            ->note('En de landelijke uitslag?', 'Uw individuele uitslag is persoonlijk beschikbaar. De landelijke ranglijst maken wij '.($publication ? 'op '.DutchTime::format($publication, 'dddd D MMMM [om] HH:mm') : 'op een later, officieel aangekondigd moment').' bekend. Tot die tijd blijven alle andere beoordelingen vertrouwelijk.')
            ->salutation("Bedankt voor uw deelname en het vertrouwen in een onafhankelijke beoordeling.\n\nMet vriendelijke groet,\n".config('mail.brand.team'));
    }
}

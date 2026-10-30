<?php

namespace App\Domain\Intake\Notifications;

use App\Domain\Intake\Models\PickupRequest;
use App\Domain\Participants\Models\ParticipantUser;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Ophaalservice bevestigd (na betaling) of ingepland (chauffeur en volgorde bekend).
 */
class PickupConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PickupRequest $request, public readonly bool $scheduled = false) {}

    /**
     * @return list<string>
     */
    public function via(ParticipantUser $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(ParticipantUser $notifiable): GoldenMail
    {
        $request = $this->request->loadMissing(['day', 'entry.edition', 'entry.company', 'order.invoice']);
        $entry = $request->entry;
        $day = DutchTime::format($request->day->date, 'dddd D MMMM YYYY');
        $pieces = $entry->edition->settings->piecesPerEntry;

        $mail = (new GoldenMail)
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->panel('Ophaalservice', ucfirst($day), [
                'Bedrijf: '.$request->company_name,
                'Ophaaladres: '.$request->address(),
                'Contactpersoon: '.$request->contact_name.' · '.$request->phone,
                'Deelnemercode: '.$entry->delivery_code,
                $this->scheduled && $request->driver_name ? 'Chauffeur: '.$request->driver_name : null,
            ])
            ->action('Bekijk in uw portaal', route('portaal.planning', $entry->company))
            ->note('Wat zet u klaar?', "{$pieces} oliebollen in een doos of zak, zonder naam of logo. Schrijf uw deelnemercode op de verpakking of leg het aanleverbewijs erbij.");

        if ($this->scheduled) {
            return $mail
                ->subject("Ophaalservice ingepland: {$day}")
                ->hero('Uw ophaalmoment is', 'ingepland.')
                ->preheader("Op {$day} halen wij uw oliebollen op.")
                ->line("De route voor {$day} staat. Een teamlid van De Gouden Bol komt uw oliebollen ophalen. Zorg dat er die dag iemand aanwezig is op het ophaaladres.");
        }

        return $mail
            ->subject("Ophaalservice bevestigd: {$day}")
            ->hero('Uw ophaalservice is', 'bevestigd.')
            ->preheader('De voorrijkosten zijn betaald. Wij halen uw oliebollen op vrijdag op.')
            ->line('Bedankt voor uw betaling. Een teamlid van De Gouden Bol haalt uw oliebollen op de gekozen vrijdag op. U ontvangt nog een bericht zodra de route vaststaat.')
            ->line($request->order?->invoice ? "De factuur ({$request->order->invoice->number}) staat in het portaal onder Facturen." : 'De factuur staat in het portaal onder Facturen.');
    }
}

<?php

namespace App\Domain\Participants\Notifications;

use App\Domain\Participants\Models\Entry;
use App\Domain\Participants\Models\ParticipantUser;
use App\Domain\Participants\Services\MagicLinkService;
use App\Support\DutchTime;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Registratie, bedankt en betaling ontvangen; de factuur staat klaar en de volgende stap is het
 * aanlevermoment kiezen (briefing 29 okt §11).
 */
class EntryRegisteredNotification extends Notification implements ShouldQueue
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
        $entry = $this->entry->loadMissing(['edition', 'province', 'company', 'order.invoice']);
        $edition = $entry->edition;
        $order = $entry->order;
        $portalUrl = app(MagicLinkService::class)->url($notifiable, minutes: 60 * 24 * 3, redirectTo: route('portaal.dashboard'));

        $mail = (new GoldenMail)
            ->subject("Bedankt voor uw aanmelding: {$entry->public_name} doet mee aan De Gouden Bol {$edition->year}")
            ->hero('Welkom bij', "De Gouden Bol {$edition->year}.")
            ->preheader('Uw betaling is ontvangen en uw plek staat vast. Kies nu hoe uw oliebollen bij ons komen.')
            ->recipient($notifiable->email)
            ->greeting("Beste {$notifiable->name},")
            ->line("Bedankt voor uw aanmelding. De betaling is ontvangen en **{$entry->public_name}** doet mee in de provincie {$entry->province->name}.")
            ->panel('Uw aanmelding', $entry->company->name, [
                'Contactpersoon: '.($entry->company->contact_name ?: $notifiable->name),
                'Aangemeld op: '.DutchTime::format($entry->registered_at ?? now(), 'D MMMM YYYY'),
                'Tarief: '.$entry->rate->getLabel().($order ? ' · betaald '.$order->formattedTotal().' incl. btw' : ''),
                $order?->invoice ? "Factuur: {$order->invoice->number}, te downloaden in het portaal" : null,
            ])
            ->action('Aanlevermoment kiezen', $portalUrl)
            ->line('In het portaal kiest u of u de oliebollen zelf brengt op een tijdslot of op vrijdag laat ophalen. Daar vindt u ook uw factuur en, na de keuring, uw persoonlijke rapport.')
            ->line('Deze inloglink is drie dagen geldig en eenmalig te gebruiken. Daarna vraagt u op het portaal een nieuwe link aan.');

        if ($edition->first_test_day) {
            $mail->note('Wat neemt u mee?', $edition->settings->piecesPerEntry.' oliebollen, aangeleverd in een doos of zak zonder naam of logo. De keuringen beginnen op '.DutchTime::date($edition->first_test_day).'.');
        }

        return $mail;
    }
}

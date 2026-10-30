<?php

namespace App\Domain\Commerce\Notifications;

use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\Sponsor;
use App\Domain\Commerce\Models\SponsorLink;
use App\Domain\Commerce\Models\SponsorOffer;
use App\Support\Mail\GoldenMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alle mails aan sponsoren (briefing 29 okt §11): registratie en betaling, factuur, aanvraag
 * ontvangen, koppeling goedgekeurd of afgewezen, vermelding gepubliceerd, actie beoordeeld en de
 * beheerlink. Sponsoren hebben geen account; ze krijgen een persoonlijke beheerlink.
 */
class SponsorMail extends Notification implements ShouldQueue
{
    use Queueable;

    public const string PAID = 'paid';

    public const string REQUEST = 'request';

    public const string LINK_DECIDED = 'link_decided';

    public const string PUBLISHED = 'published';

    public const string OFFER_REVIEWED = 'offer_reviewed';

    public const string ACCESS = 'access';

    public function __construct(
        public readonly string $kind,
        public readonly Sponsor $sponsor,
        public readonly ?Order $order = null,
        public readonly ?SponsorLink $link = null,
        public readonly ?SponsorOffer $offer = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): GoldenMail
    {
        $sponsor = $this->sponsor->loadMissing('requestedProduct');
        $mail = (new GoldenMail)
            ->recipient($sponsor->contact_email)
            ->greeting('Beste '.($sponsor->contact_name ?: $sponsor->name).',');

        return match ($this->kind) {
            self::PAID => $this->paid($mail, $sponsor),
            self::REQUEST => $this->request($mail, $sponsor),
            self::LINK_DECIDED => $this->linkDecided($mail, $sponsor),
            self::PUBLISHED => $mail
                ->subject("Uw sponsorvermelding staat online: {$sponsor->name}")
                ->hero('Uw vermelding staat', 'online.')
                ->line('We hebben uw gegevens en logo gecontroleerd. Uw sponsorvermelding is nu zichtbaar op de website van De Gouden Bol.')
                ->line('Koppelingen bij deelnemers ("Bakt met") verschijnen zodra de deelnemer akkoord geeft.')
                ->action('Naar uw sponsoromgeving', $sponsor->accessUrl()),
            self::OFFER_REVIEWED => $this->offerReviewed($mail, $sponsor),
            default => $mail
                ->subject('Uw beheerlink voor De Gouden Bol')
                ->hero('Uw', 'sponsoromgeving.')
                ->line('Met de knop hieronder opent u uw sponsoromgeving: pakket, logo, facturen, koppelingen, acties en goede doelen.')
                ->action('Naar uw sponsoromgeving', $sponsor->accessUrl())
                ->line('De link is '.Sponsor::ACCESS_LINK_DAYS.' dagen geldig. Deel hem niet met anderen; via de sponsorpagina vraagt u altijd een nieuwe aan.'),
        };
    }

    private function paid(GoldenMail $mail, Sponsor $sponsor): GoldenMail
    {
        $order = $this->order?->loadMissing(['lines', 'invoice']);

        return $mail
            ->subject("Bedankt voor uw sponsoring: betaling ontvangen ({$sponsor->name})")
            ->hero('Bedankt voor uw', 'sponsoring.')
            ->preheader('Uw betaling is ontvangen. Wij controleren uw vermelding en zetten hem daarna online.')
            ->line('Uw betaling is ontvangen. Wij controleren uw gegevens en logo en zetten uw vermelding daarna online; u krijgt bericht zodra dat gebeurd is.')
            ->panel('Uw bestelling', $order?->invoice ? "Factuur {$order->invoice->number}" : null, [
                ...($order?->lines->map(fn ($line) => $line->description)->all() ?? []),
                $order ? 'Totaal: '.$order->formattedTotal().' incl. btw' : null,
            ], 'De factuur staat in uw sponsoromgeving en kunt u daar als pdf bewaren.')
            ->action('Naar uw sponsoromgeving', $sponsor->accessUrl())
            ->note('Koppeling bij een deelnemer?', 'Koos u voor een vermelding bij een deelnemer, dan vragen wij die deelnemer nu om akkoord. Zonder akkoord verschijnt er niets; u krijgt bericht over de uitkomst.');
    }

    private function request(GoldenMail $mail, Sponsor $sponsor): GoldenMail
    {
        $product = $sponsor->requestedProduct;
        $isTop10 = $product?->code->value === 'national_top';

        return $mail
            ->subject($isTop10 ? "Reservering ontvangen: Top 10 partner ({$sponsor->name})" : "Maatwerkaanvraag ontvangen: {$product?->name} ({$sponsor->name})")
            ->hero('Uw aanvraag is', 'ontvangen.')
            ->line($isTop10
                ? 'Bedankt voor uw reservering als Top 10 partner. De landelijke Top 10 is pas bekend op de officiële publicatiedatum. Daarna nemen wij contact op: u kiest een deelnemer, die deelnemer geeft akkoord en pas dan rekent u af. Een plaats in de ranking wordt nooit gesuggereerd of gegarandeerd.'
                : 'Bedankt voor uw interesse. Maatwerkpakketten hebben geen vaste prijs; wij nemen contact met u op om de invulling en de prijs af te stemmen. U zit nergens aan vast.')
            ->panel('Uw aanvraag', $product?->name, [
                'Bedrijf: '.$sponsor->name,
                'Contactpersoon: '.$sponsor->contact_name,
                $sponsor->requestedProvince ? 'Provincie: '.$sponsor->requestedProvince->name : null,
                $sponsor->request_note ? 'Toelichting: '.$sponsor->request_note : null,
            ])
            ->action('Naar uw sponsoromgeving', $sponsor->accessUrl());
    }

    private function linkDecided(GoldenMail $mail, Sponsor $sponsor): GoldenMail
    {
        $link = $this->link?->loadMissing('company');
        $confirmed = $link?->isConfirmed() ?? false;
        $name = $link?->company?->name ?? 'de deelnemer';

        return $mail
            ->subject($confirmed ? "Koppeling goedgekeurd: Bakt met {$sponsor->name} bij {$name}" : "Koppeling afgewezen door {$name}")
            ->hero($confirmed ? 'Uw koppeling is' : 'Uw koppeling is', $confirmed ? 'goedgekeurd.' : 'niet geaccepteerd.')
            ->line($confirmed
                ? "{$name} heeft de koppeling bevestigd. Het profiel toont nu \"Bakt met {$sponsor->name}\" met uw logo en link."
                : "{$name} heeft de koppeling niet geaccepteerd. Er verschijnt niets op het profiel. Wij nemen contact met u op over een andere deelnemer of verrekening.")
            ->action('Naar uw sponsoromgeving', $sponsor->accessUrl());
    }

    private function offerReviewed(GoldenMail $mail, Sponsor $sponsor): GoldenMail
    {
        $offer = $this->offer;
        $approved = $offer?->status->value === 'approved';

        return $mail
            ->subject($approved ? "Sponsoractie goedgekeurd: {$offer?->title}" : "Sponsoractie niet goedgekeurd: {$offer?->title}")
            ->hero('Uw sponsoractie is', $approved ? 'goedgekeurd.' : 'niet goedgekeurd.')
            ->line($approved
                ? 'De redactie heeft uw actie goedgekeurd. Hij is nu zichtbaar voor '.mb_strtolower((string) $offer?->audience->getLabel()).'.'
                : 'De redactie heeft uw actie niet goedgekeurd.'.($offer?->review_note ? ' Toelichting: '.$offer->review_note : ''))
            ->action('Naar uw sponsoromgeving', $sponsor->accessUrl());
    }
}

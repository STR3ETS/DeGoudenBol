<?php

namespace App\Support\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Intake\Enums\PickupStatus;
use App\Domain\Participants\Enums\DeliveryMethod;
use App\Domain\Participants\Enums\DiscountStatus;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Support\DutchTime;
use Illuminate\Support\Facades\Route;

/**
 * Vertaalt de status van een inschrijving naar wat de bakker in het portaal moet zien: de volgende
 * stap (één actie), een statuszin in gewone taal en de vier stappen uit de briefing van 29 okt:
 * (1) aangemeld en betaald, (2) aanlevering of ophalen, (3) het panel proeft, (4) persoonlijke
 * uitslag en landelijke ranking.
 *
 * @phpstan-type Stap array{titel: string, tekst: string, staat: 'klaar'|'nu'|'later', url: string|null, knop: string|null}
 * @phpstan-type Actie array{eyebrow: string, titel: string, accent: string|null, tekst: string, knop: string|null, url: string|null, secundair: string|null, secundair_url: string|null}
 */
final class EntryJourney
{
    public function __construct(
        private readonly Company $company,
        private readonly ?Entry $entry,
        private readonly ?Edition $edition,
    ) {}

    /**
     * @return list<Stap>
     */
    public function steps(): array
    {
        $current = $this->currentStep();

        $state = fn (int $n): string => match (true) {
            $current === null => 'later',
            $n < $current => 'klaar',
            $n === $current && $current === 5 => 'klaar',
            $n === $current => 'nu',
            default => 'later',
        };

        return [
            ['titel' => 'Aangemeld en betaald', 'tekst' => $this->registrationText(), 'staat' => $state(1), 'url' => $this->route('portaal.aanmelding'), 'knop' => $state(1) === 'nu' ? 'Bekijk je aanmelding' : null],
            ['titel' => 'Zelf brengen of laten ophalen', 'tekst' => $this->deliveryText(), 'staat' => $state(2), 'url' => $this->route('portaal.planning'), 'knop' => $state(2) === 'nu' ? 'Kies hoe' : null],
            ['titel' => 'Het panel proeft', 'tekst' => $this->tastingText(), 'staat' => $state(3), 'url' => null, 'knop' => null],
            ['titel' => 'Persoonlijke uitslag en landelijke ranking', 'tekst' => $this->resultText(), 'staat' => $state(4), 'url' => $this->route('portaal.uitslag'), 'knop' => $this->reportAvailable() ? 'Bekijk je rapport' : null],
        ];
    }

    /**
     * Welke van de vier stappen nu aan de beurt is (5 = alles klaar), null bij zijpaden of zonder inschrijving.
     */
    private function currentStep(): ?int
    {
        return match ($this->entry?->status) {
            null, EntryStatus::Withdrawn, EntryStatus::Cancelled => null,
            EntryStatus::PendingPayment => 1,
            EntryStatus::Registered, EntryStatus::FreshnessExpired => 2,
            EntryStatus::Scheduled, EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed => 3,
            EntryStatus::Linked => 4,
            EntryStatus::Published, EntryStatus::Confidential => 5,
        };
    }

    public function stepsDone(): int
    {
        return count(array_filter($this->steps(), fn (array $step) => $step['staat'] === 'klaar'));
    }

    private function registrationText(): string
    {
        $entry = $this->entry;

        if ($entry === null) {
            return 'Meld je bedrijf aan voor deze editie.';
        }

        $parts = ['Aangemeld op '.DutchTime::date($entry->registered_at ?? $entry->created_at ?? now()), $entry->rate?->getLabel() ?? 'Standaardprijs'];

        if ($entry->wantsDiscount()) {
            $parts[] = 'korting: '.mb_strtolower((string) $entry->discount_status?->getLabel());
        }

        $parts[] = match (true) {
            $entry->order?->isPaid() => 'betaald'.($entry->order->invoice ? ', factuur '.$entry->order->invoice->number : ''),
            $entry->status === EntryStatus::PendingPayment => 'nog niet betaald',
            default => 'betaald',
        };

        return implode(' · ', $parts).'.';
    }

    private function deliveryText(): string
    {
        $entry = $this->entry;
        $pickup = $entry?->pickupRequest;

        if ($pickup !== null && $pickup->status !== PickupStatus::Cancelled) {
            return $pickup->isPaid()
                ? 'We halen je oliebollen op '.$pickup->day->label().' op ('.mb_strtolower($pickup->status->getLabel()).'). Je deelnemercode is '.$entry->delivery_code.'.'
                : 'Ophaalservice op '.$pickup->day->label().' aangevraagd. Betaal de voorrijkosten, dan staat het vast.';
        }

        if ($entry?->deliverySlot !== null) {
            return 'Je brengt ze zelf op '.DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM [om] HH:mm').'. Je deelnemercode is '.$entry->delivery_code.'.';
        }

        if ($entry?->status === EntryStatus::FreshnessExpired) {
            return 'Je vorige oliebollen waren te laat voor de proeverij. Kies een nieuw moment.';
        }

        $fee = $this->edition?->settings->pickupEnabled ? ' of laat ze op vrijdag ophalen' : '';
        $period = $this->testPeriod();

        return 'Breng je oliebollen zelf op een tijdslot'.($period ? ' tussen '.$period : '').$fee.'. Daarna krijg je je deelnemercode.';
    }

    private function tastingText(): string
    {
        return match ($this->entry?->status) {
            EntryStatus::Received, EntryStatus::Numbered => 'Ontvangen. Je inzending heeft een eigen code; het panel ziet nooit je naam of sponsors.',
            EntryStatus::Scored, EntryStatus::Reviewed => 'In keuring. Het panel heeft geproefd en de scorecontrole kijkt mee.',
            EntryStatus::Linked, EntryStatus::Published, EntryStatus::Confidential => 'Keuring afgerond.',
            default => 'Op de testdag proeft het panel blind. Het ziet alleen een code, nooit je naam of sponsors. Jij hoeft niets te doen.',
        };
    }

    private function resultText(): string
    {
        $entry = $this->entry;
        $report = $entry?->confidentialReport;
        $days = $this->edition?->settings->reportDueDays ?? 3;
        $publication = $this->publicationMoment();
        $ranking = $publication ? 'De landelijke ranking volgt '.$publication.'.' : 'De landelijke ranking volgt op de officiële publicatiedatum.';

        if ($this->reportAvailable()) {
            return 'Je persoonlijke rapport staat klaar. '.($this->rankingPublic() ? 'De landelijke ranking is bekend.' : $ranking);
        }

        if ($report?->due_at !== null) {
            return 'Je persoonlijke rapport volgt uiterlijk '.DutchTime::format($report->due_at, 'dddd D MMMM').'. '.$ranking;
        }

        return "Binnen {$days} dagen na de keuring je persoonlijke rapport. {$ranking}";
    }

    public function reportAvailable(): bool
    {
        return (bool) $this->entry?->confidentialReport?->isPublished();
    }

    private function rankingPublic(): bool
    {
        return $this->entry?->status === EntryStatus::Published || ($this->edition?->main_publication_at?->isPast() ?? false);
    }

    /**
     * De ene actie die bovenaan staat.
     *
     * @return Actie
     */
    public function nextAction(): array
    {
        $entry = $this->entry;
        $company = $this->company;

        if ($entry === null) {
            $open = $this->edition?->isRegistrationOpen() ?? false;

            return $this->action('Jouw volgende stap', 'Meld', $open ? 'je bedrijf aan' : null, $open
                ? 'Deze editie staat open voor inschrijving. Aanmelden duurt vijf minuten.'
                : 'Er is op dit moment geen inschrijving open. Zodra de volgende editie opent, zie je dat hier.',
                $open && Route::has('aanmelden') ? 'Aanmelden' : null, $open && Route::has('aanmelden') ? route('aanmelden') : null);
        }

        return match ($entry->status) {
            EntryStatus::PendingPayment => $this->paymentAction(),
            EntryStatus::Registered, EntryStatus::FreshnessExpired => $this->deliveryAction(),
            EntryStatus::Scheduled => $this->scheduledAction(),
            EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed => $this->action('Het panel is aan zet', 'Je oliebollen zijn', 'ontvangen', 'Het panel proeft blind en de scorecontrole kijkt mee. Binnen '.($this->edition?->settings->reportDueDays ?? 3).' dagen na de keuring staat je persoonlijke rapport hier klaar. Tot die tijd hoef je niets te doen.', Route::has('portaal.marketing') ? 'Bekijk je deelnemersbadge' : null, Route::has('portaal.marketing') ? route('portaal.marketing', $company) : null),
            EntryStatus::Linked => $this->reportAvailable()
                ? $this->action('Je rapport is er', 'Je persoonlijke rapport', 'staat klaar', 'Lees hoe je oliebollen scoren op smaak, structuur, versheid en de andere onderdelen, met de toelichting van het panel. Je kunt het als pdf bewaren. '.($this->publicationMoment() ? 'De landelijke ranking volgt '.$this->publicationMoment().'.' : ''), 'Bekijk je rapport', route('portaal.rapport', $company), 'Oorkonde downloaden', $this->route('portaal.oorkonde'))
                : $this->action('Bijna klaar', 'Het panel heeft', 'geproefd', 'Je persoonlijke rapport volgt '.($entry->confidentialReport?->due_at ? 'uiterlijk '.DutchTime::format($entry->confidentialReport->due_at, 'dddd D MMMM') : 'binnen enkele dagen').'. Je krijgt een e-mail zodra het klaarstaat.', null, null),
            EntryStatus::Published => $this->action('Gefeliciteerd', 'De landelijke uitslag', 'is er', $entry->publicTotal() !== null ? 'Je oliebollen zijn officieel getest en kregen een '.number_format($entry->publicTotal(), 1, ',', '.').'. Je badges staan klaar voor je raam, je website en je socials.' : 'Je oliebollen zijn officieel getest. Bekijk je uitslag en je badges.', 'Bekijk je uitslag', route('portaal.uitslag', $company), Route::has('portaal.marketing') ? 'Download je badges' : null, Route::has('portaal.marketing') ? route('portaal.marketing', $company) : null),
            EntryStatus::Confidential => $this->action('Vertrouwelijk', 'Je resultaat is', 'alleen voor jou', 'Je cijfer ligt onder de publicatiedrempel en komt niet op de site. Niemand buiten je bedrijf ziet het. Je persoonlijke rapport helpt je verder.', 'Bekijk je rapport', route('portaal.rapport', $company)),
            EntryStatus::Withdrawn, EntryStatus::Cancelled => $this->action('Deelname gestopt', 'Je doet dit jaar', 'niet mee', $entry->status === EntryStatus::Withdrawn ? 'Je hebt je deelname teruggetrokken. Vragen? Mail ons, we helpen graag.' : 'Je inschrijving is geannuleerd. Vragen? Mail ons, we helpen graag.', null, null),
        };
    }

    /**
     * @return Actie
     */
    private function paymentAction(): array
    {
        $entry = $this->entry;
        $company = $this->company;

        if ($entry->order === null || $entry->isReservationExpired()) {
            return $this->action('Let op', 'Je reservering is', 'verlopen', 'De betaling is niet op tijd binnengekomen, dus je plek is vrijgegeven. Meld je opnieuw aan als er nog plek is in '.$entry->province->name.'.', Route::has('aanmelden') ? 'Opnieuw aanmelden' : null, Route::has('aanmelden') ? route('aanmelden') : null);
        }

        $until = DutchTime::format($entry->reservation_expires_at, 'D MMMM');

        if ($entry->wantsDiscount()) {
            return match ($entry->discount_status) {
                DiscountStatus::AwaitingProof => $this->action('Kortingsactie', 'Deel je filmpje en', 'lever de link aan', "Plaats een filmpje over je deelname, tag De Gouden Bol en zet de link in het portaal. Na goedkeuring betaal je de actieprijs. Je plek blijft gereserveerd tot {$until}.", 'Link aanleveren', route('portaal.aanmelding', $company).'#korting', 'Toch standaardprijs betalen', route('portaal.aanmelding', $company)),
                DiscountStatus::Submitted => $this->action('Kortingsactie', 'We bekijken', 'je post', "Bedankt voor het delen. Zodra we je post hebben gezien, krijg je een mail met de betaallink. Je plek blijft gereserveerd tot {$until}.", 'Bekijk je aanmelding', route('portaal.aanmelding', $company)),
                DiscountStatus::Approved => $this->action('Korting goedgekeurd', 'Rond je', 'betaling af', "Je betaalt de actieprijs van {$entry->order->formattedTotal()} incl. btw. Je plek blijft gereserveerd tot {$until}.", 'Betaal nu', route('aanmelden.status', $entry->order)),
                default => $this->action('Kortingsactie', 'Je korting is nog', 'niet goedgekeurd', ($entry->discount_note ? $entry->discount_note.' ' : '').'Lever opnieuw een link aan of betaal de standaardprijs.', 'Opnieuw aanleveren', route('portaal.aanmelding', $company).'#korting', 'Standaardprijs betalen', route('aanmelden.status', $entry->order)),
            };
        }

        return $this->action('Jouw volgende stap', 'Rond je', 'betaling af', 'Je plek is gereserveerd tot '.DutchTime::format($entry->reservation_expires_at, 'HH:mm').' uur. Daarna komt hij vrij voor een andere bakker. Betalen duurt een minuut.', 'Betaal nu', route('aanmelden.status', $entry->order));
    }

    /**
     * @return Actie
     */
    private function deliveryAction(): array
    {
        $entry = $this->entry;
        $company = $this->company;
        $pickup = $entry->pickupRequest;

        if ($pickup !== null && $pickup->status === PickupStatus::Requested) {
            return $this->action('Jouw volgende stap', 'Betaal de', 'ophaalservice', 'Je vroeg de ophaalservice aan voor '.$pickup->day->label().'. Na betaling van de voorrijkosten staat het vast.', 'Naar de ophaalservice', route('portaal.planning', $company));
        }

        $intro = $entry->status === EntryStatus::FreshnessExpired ? 'Je vorige oliebollen waren te laat voor de proeverij. ' : 'Je plek is betaald, dat is geregeld. ';
        $pickupText = $this->edition?->settings->pickupEnabled ? ' Liever laten ophalen? Dat kan op vrijdag.' : '';
        $period = $this->testPeriod();

        return $this->action('Jouw volgende stap', 'Kies hoe je', 'oliebollen bij ons komen', $intro.'Breng ze zelf op een tijdslot'.($period ? ' tussen '.$period : '').'.'.$pickupText, 'Kies hoe', route('portaal.planning', $company));
    }

    /**
     * @return Actie
     */
    private function scheduledAction(): array
    {
        $entry = $this->entry;
        $company = $this->company;
        $pickup = $entry->pickupRequest;

        if ($entry->delivery_method === DeliveryMethod::Pickup && $pickup !== null) {
            return $this->action('Je bent ingepland', 'We halen ze op', $pickup->day->label(), 'Zet '.($this->edition?->settings->piecesPerEntry ?? 20).' oliebollen klaar in een doos of zak, met je deelnemercode '.$entry->delivery_code.' erop. Status: '.mb_strtolower($pickup->status->getLabel()).'.', 'Open je ophaalbewijs', route('portaal.planning.bewijs', $company), 'Details ophaalservice', route('portaal.planning', $company));
        }

        $start = $entry->deliverySlot?->starts_at ?? $entry->scheduled_at;
        $end = $entry->deliverySlot?->ends_at ?? $entry->scheduled_at;

        return $this->action('Je bent ingepland', 'Breng je oliebollen op', ucfirst(DutchTime::format($start, 'dddd D MMMM')), 'Tussen '.DutchTime::format($start, 'HH:mm').' en '.DutchTime::format($end, 'HH:mm').' uur. Neem je aanleverbewijs mee, daar staat je deelnemercode op. Verder hoef je nu niets te doen.', 'Open je aanleverbewijs', route('portaal.planning.bewijs', $company), 'Ander moment kiezen', route('portaal.planning', $company));
    }

    /**
     * Eén zin die zegt hoe het ervoor staat en wat je (niet) hoeft te doen.
     *
     * @return array{toon: string, icoon: string, tekst: string}|null
     */
    public function statusLine(): ?array
    {
        $entry = $this->entry;

        if ($entry === null) {
            return null;
        }

        $publicName = '<strong>'.e($entry->public_name).'</strong>';

        return match ($entry->status) {
            EntryStatus::PendingPayment => match (true) {
                $entry->isReservationExpired() => ['toon' => 'fout', 'icoon' => 'waarschuwing', 'tekst' => '<strong>Je reservering is verlopen.</strong> De plek is vrijgegeven. Meld je opnieuw aan als er nog plek is.'],
                $entry->wantsDiscount() && (bool) $entry->discount_status?->blocksPayment() => ['toon' => 'info', 'icoon' => 'klok', 'tekst' => '<strong>Je plek is gereserveerd tot '.e(DutchTime::format($entry->reservation_expires_at, 'D MMMM')).'.</strong> Eerst keuren we je socialmedia-post, daarna betaal je de actieprijs.'],
                default => ['toon' => 'waarschuwing', 'icoon' => 'klok', 'tekst' => '<strong>Je plek is nog even gereserveerd.</strong> Rond de betaling af vóór '.e(DutchTime::format($entry->reservation_expires_at, $entry->wantsDiscount() ? 'D MMMM' : 'HH:mm [uur]')).', anders komt hij vrij voor een andere bakker.'],
            },
            EntryStatus::Registered => ['toon' => 'succes', 'icoon' => 'vink', 'tekst' => '<strong>Alles is in orde.</strong> Je betaling is ontvangen'.($entry->confirmed_at ? ' op '.DutchTime::date($entry->confirmed_at) : '').'. Op de website sta je als '.$publicName.'.'],
            EntryStatus::Scheduled => ['toon' => 'succes', 'icoon' => 'vink', 'tekst' => '<strong>Je bent ingepland.</strong> Je deelnemercode is <strong>'.e((string) $entry->delivery_code).'</strong>. Tot de keuring hoef je niets te doen.'],
            EntryStatus::Received, EntryStatus::Numbered => ['toon' => 'info', 'icoon' => 'info', 'tekst' => '<strong>Je oliebollen zijn ontvangen.</strong> Ze hebben een eigen code gekregen; het panel ziet nooit je naam.'],
            EntryStatus::Scored, EntryStatus::Reviewed => ['toon' => 'info', 'icoon' => 'info', 'tekst' => '<strong>In keuring.</strong> Het panel heeft geproefd; de scorecontrole maakt de uitslag definitief.'],
            EntryStatus::Linked => $this->reportAvailable()
                ? ['toon' => 'succes', 'icoon' => 'ster', 'tekst' => '<strong>Je persoonlijke rapport staat klaar.</strong> De landelijke ranking blijft geheim tot de officiële publicatie.']
                : ['toon' => 'info', 'icoon' => 'info', 'tekst' => '<strong>Keuring afgerond.</strong> Je persoonlijke rapport volgt binnen enkele dagen.'],
            EntryStatus::Published => ['toon' => 'succes', 'icoon' => 'ster', 'tekst' => '<strong>Je uitslag is gepubliceerd.</strong> Iedereen kan hem zien op de site onder '.$publicName.'.'],
            EntryStatus::Confidential => ['toon' => 'info', 'icoon' => 'vergrendel', 'tekst' => '<strong>Je resultaat is vertrouwelijk.</strong> Het staat niet op de site; alleen jij ziet het in het portaal.'],
            EntryStatus::FreshnessExpired => ['toon' => 'waarschuwing', 'icoon' => 'klok', 'tekst' => '<strong>Je oliebollen waren te laat voor de proeverij.</strong> Kies een nieuw aanlevermoment, dan proeft het panel alsnog.'],
            EntryStatus::Withdrawn, EntryStatus::Cancelled => ['toon' => 'fout', 'icoon' => 'kruis', 'tekst' => '<strong>Je doet dit jaar niet mee.</strong> Je inschrijving is '.($entry->status === EntryStatus::Withdrawn ? 'teruggetrokken' : 'geannuleerd').'.'],
        };
    }

    /**
     * Handige, niet-verplichte acties naast de hoofdstap.
     *
     * @return list<array{titel: string, tekst: string, icoon: string, toon: string, url: string|null, staat: string, pijl: string}>
     */
    public function sideActions(): array
    {
        $company = $this->company;
        $entry = $this->entry;
        $profile = $company->profile;
        $invoice = $entry?->order?->invoice;
        $actions = [];

        if (Route::has('portaal.profiel')) {
            $actions[] = $profile === null || ! $profile->isPublished()
                ? ['titel' => 'Vertel iets over je zaak', 'tekst' => 'Een verhaal en je specialiteiten op je pagina. Bakkers met een profiel worden vaker bekeken.', 'icoon' => 'potlood', 'toon' => 'goud', 'url' => route('portaal.profiel', $company), 'staat' => '', 'pijl' => 'pijl-rechts']
                : ['titel' => 'Je profiel staat online', 'tekst' => $profile->hasUnpublishedChanges() ? 'Je laatste wijziging wacht op de redactie.' : 'Aanpassen kan altijd; de redactie kijkt even mee.', 'icoon' => 'oog', 'toon' => 'succes', 'url' => route('portaal.profiel', $company), 'staat' => 'klaar', 'pijl' => 'potlood'];
        }

        if ($invoice !== null && Route::has('portaal.facturen.toon')) {
            $actions[] = ['titel' => 'Factuur '.$invoice->number, 'tekst' => $invoice->status->getLabel().' · '.$invoice->formattedTotal().' · bekijken of opslaan als pdf', 'icoon' => 'document', 'toon' => 'succes', 'url' => route('portaal.facturen.toon', $invoice), 'staat' => 'klaar', 'pijl' => 'download'];
        }

        if ($entry !== null && Route::has('portaal.planning.bewijs')) {
            $actions[] = $entry->delivery_code !== null && ($entry->deliverySlot !== null || $entry->pickupRequest?->isPaid())
                ? ['titel' => 'Aanleverbewijs', 'tekst' => 'Met QR-code en je deelnemercode. Afdrukken of op je telefoon laten zien.', 'icoon' => 'klembord', 'toon' => 'goud', 'url' => route('portaal.planning.bewijs', $company), 'staat' => '', 'pijl' => 'pijl-rechts']
                : ['titel' => 'Aanleverbewijs', 'tekst' => 'Komt beschikbaar zodra je hebt gekozen hoe je oliebollen bij ons komen.', 'icoon' => 'klembord', 'toon' => 'neutraal', 'url' => null, 'staat' => 'later', 'pijl' => 'vergrendel'];
        }

        if ($entry !== null && $entry->status->occupiesPlace() && $entry->status !== EntryStatus::PendingPayment && Route::has('portaal.oorkonde')) {
            $actions[] = ['titel' => 'Oorkonde', 'tekst' => 'Officiële deelnemer van De Gouden Bol. Afdrukken en ophangen in je zaak.', 'icoon' => 'ster', 'toon' => 'goud', 'url' => route('portaal.oorkonde', $company), 'staat' => '', 'pijl' => 'download'];
        }

        if (Route::has('portaal.medewerkers')) {
            $actions[] = ['titel' => 'Geef een collega toegang', 'tekst' => 'Dan kan die ook het aanlevermoment zien en bonnen scannen.', 'icoon' => 'mensen', 'toon' => 'info', 'url' => route('portaal.medewerkers', $company), 'staat' => '', 'pijl' => 'pijl-rechts'];
        }

        return $actions;
    }

    public function testPeriod(): ?string
    {
        $edition = $this->edition;

        if ($edition?->first_test_day === null || $edition->last_test_day === null) {
            return null;
        }

        return DutchTime::format($edition->first_test_day, 'D MMMM').' en '.DutchTime::format($edition->last_test_day, 'D MMMM');
    }

    public function publicationMoment(): ?string
    {
        $moment = $this->edition?->main_publication_at;

        return $moment === null ? null : DutchTime::format($moment, 'dddd D MMMM [om] HH:mm');
    }

    private function route(string $name): ?string
    {
        return Route::has($name) ? route($name, $this->company) : null;
    }

    /**
     * @return Actie
     */
    private function action(string $eyebrow, string $titel, ?string $accent, string $tekst, ?string $knop, ?string $url, ?string $secundair = null, ?string $secundairUrl = null): array
    {
        return ['eyebrow' => $eyebrow, 'titel' => $titel, 'accent' => $accent, 'tekst' => $tekst, 'knop' => $knop, 'url' => $url, 'secundair' => $secundair, 'secundair_url' => $secundairUrl];
    }
}

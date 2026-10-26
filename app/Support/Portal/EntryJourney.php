<?php

namespace App\Support\Portal;

use App\Domain\Edition\Models\Edition;
use App\Domain\Participants\Enums\EntryStatus;
use App\Domain\Participants\Models\Company;
use App\Domain\Participants\Models\Entry;
use App\Support\DutchTime;
use Illuminate\Support\Facades\Route;

/**
 * Vertaalt de status van een inschrijving naar wat de bakker in het portaal moet zien:
 * de volgende stap (één actie), een statuszin in gewone taal en de vier stappen van de deelname.
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
        $entry = $this->entry;
        $status = $entry?->status;
        $current = $this->currentStep();
        $period = $this->testPeriod();
        $publication = $this->publicationMoment();

        $state = fn (int $n): string => match (true) {
            $current === null => 'later',
            $n < $current => 'klaar',
            $n === $current && $current === 5 => 'klaar',
            $n === $current => 'nu',
            default => 'later',
        };

        $first = match (true) {
            $entry === null => 'Meld je bedrijf aan voor deze editie.',
            $status === EntryStatus::PendingPayment => 'Je plek is gereserveerd. Rond de betaling af, dan is je deelname zeker.',
            $entry->confirmed_at !== null => 'Op '.DutchTime::date($entry->confirmed_at).'. '.($entry->order?->invoice ? 'Factuur '.$entry->order->invoice->number.' staat onder Facturen.' : 'Je betaling is ontvangen.'),
            default => 'Je betaling is ontvangen.',
        };

        $second = match (true) {
            $entry?->deliverySlot !== null => 'Op '.ucfirst(DutchTime::format($entry->deliverySlot->starts_at, 'dddd D MMMM [om] HH:mm')).'. Je aanlevercode is '.$entry->delivery_code.'.',
            $status === EntryStatus::FreshnessExpired => 'Je vorige oliebollen waren te laat voor de proeverij. Kies een nieuw moment.',
            default => 'Je kiest een dag en tijd'.($period ? ' tussen '.$period : '').'. Daarna krijg je een aanleverbewijs met je code.',
        };

        return [
            ['titel' => 'Aangemeld en betaald', 'tekst' => $first, 'staat' => $state(1), 'url' => null, 'knop' => null],
            ['titel' => 'Kies wanneer je je oliebollen brengt', 'tekst' => $second, 'staat' => $state(2), 'url' => Route::has('portaal.planning') ? route('portaal.planning', $this->company) : null, 'knop' => $state(2) === 'nu' ? 'Kies een moment' : null],
            ['titel' => 'Het panel proeft', 'tekst' => 'Op de testdag. Het panel ziet alleen een nummer, nooit je naam. Jij hoeft niets te doen.', 'staat' => $state(3), 'url' => null, 'knop' => null],
            ['titel' => 'Uitslag', 'tekst' => $current === 5 ? 'Je uitslag is er. Bekijk je cijfer, je plek en je badges.' : ($publication ? ucfirst($publication).' zie je hier je cijfer en je plek in '.($entry?->province?->name ?? 'je provincie').'.' : 'Zodra het panel klaar is, zie je hier je cijfer.'), 'staat' => $state(4), 'url' => Route::has('portaal.uitslag') ? route('portaal.uitslag', $this->company) : null, 'knop' => $current === 5 ? 'Bekijk je uitslag' : null],
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
            EntryStatus::Scheduled, EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed, EntryStatus::Linked => 3,
            EntryStatus::Published, EntryStatus::Confidential => 5,
        };
    }

    public function stepsDone(): int
    {
        return count(array_filter($this->steps(), fn (array $step) => $step['staat'] === 'klaar'));
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
        $period = $this->testPeriod();

        if ($entry === null) {
            $open = $this->edition?->isRegistrationOpen() ?? false;

            return $this->action('Jouw volgende stap', 'Meld', $open ? 'je bedrijf aan' : null, $open
                ? 'Deze editie staat open voor inschrijving. Aanmelden duurt vijf minuten; je betaalt direct en je plek is dan zeker.'
                : 'Er is op dit moment geen inschrijving open. Zodra de volgende editie opent, zie je dat hier.',
                $open && Route::has('aanmelden') ? 'Aanmelden' : null, $open && Route::has('aanmelden') ? route('aanmelden') : null);
        }

        return match ($entry->status) {
            EntryStatus::PendingPayment => $entry->order !== null && ! $entry->isReservationExpired()
                ? $this->action('Jouw volgende stap', 'Rond je', 'betaling af', 'Je plek is gereserveerd tot '.DutchTime::format($entry->reservation_expires_at, 'HH:mm').' uur. Daarna komt hij vrij voor een andere bakker. Betalen duurt een minuut.', 'Betaal nu', route('aanmelden.status', $entry->order))
                : $this->action('Let op', 'Je reservering is', 'verlopen', 'De betaling is niet op tijd binnengekomen, dus je plek is vrijgegeven. Meld je opnieuw aan als er nog plek is in '.$entry->province->name.'.', Route::has('aanmelden') ? 'Opnieuw aanmelden' : null, Route::has('aanmelden') ? route('aanmelden') : null),
            EntryStatus::Registered, EntryStatus::FreshnessExpired => $this->action('Jouw volgende stap', 'Kies wanneer je', 'je oliebollen brengt', ($entry->status === EntryStatus::FreshnessExpired ? 'Je vorige oliebollen waren te laat voor de proeverij. ' : 'Je plek is betaald, dat is geregeld. ').'Kies nu een dag en tijd'.($period ? ' tussen '.$period : '').'. Dat duurt een minuut.', 'Kies een moment', route('portaal.planning', $company)),
            EntryStatus::Scheduled => $this->action('Je bent ingepland', 'Breng je oliebollen op', ucfirst(DutchTime::format($entry->deliverySlot?->starts_at ?? $entry->scheduled_at, 'dddd D MMMM')), 'Tussen '.DutchTime::format($entry->deliverySlot?->starts_at ?? $entry->scheduled_at, 'HH:mm').' en '.DutchTime::format($entry->deliverySlot?->ends_at ?? $entry->scheduled_at, 'HH:mm').' uur. Neem je aanleverbewijs mee, daar staat je code op. Verder hoef je nu niets te doen.', 'Open je aanleverbewijs', route('portaal.planning.bewijs', $company), 'Ander moment kiezen', route('portaal.planning', $company)),
            EntryStatus::Received, EntryStatus::Numbered, EntryStatus::Scored, EntryStatus::Reviewed, EntryStatus::Linked => $this->action('Het panel is aan zet', 'Je oliebollen zijn', 'ontvangen', 'Het panel proeft blind en de scorecontrole kijkt mee. '.($this->publicationMoment() ? ucfirst($this->publicationMoment()).' zie je hier je uitslag.' : 'Je krijgt een e-mail zodra je uitslag er is.').' Tot die tijd hoef je niets te doen.', Route::has('portaal.marketing') ? 'Bekijk je deelnemersbadge' : null, Route::has('portaal.marketing') ? route('portaal.marketing', $company) : null),
            EntryStatus::Published => $this->action('Gefeliciteerd', 'Je uitslag', 'is er', $entry->publicTotal() !== null ? 'Je oliebollen zijn officieel getest en kregen een '.number_format($entry->publicTotal(), 1, ',', '.').'. Je badges staan klaar voor je raam, je website en je socials.' : 'Je oliebollen zijn officieel getest. Bekijk je uitslag en je badges.', 'Bekijk je uitslag', route('portaal.uitslag', $company), Route::has('portaal.marketing') ? 'Download je badges' : null, Route::has('portaal.marketing') ? route('portaal.marketing', $company) : null),
            EntryStatus::Confidential => $this->action('Vertrouwelijk', 'Je resultaat is', 'alleen voor jou', 'Je cijfer ligt onder de publicatiedrempel en komt niet op de site. Niemand buiten je bedrijf ziet het. Je krijgt een persoonlijk verbeteradvies.', 'Bekijk je rapport', route('portaal.uitslag', $company)),
            EntryStatus::Withdrawn, EntryStatus::Cancelled => $this->action('Deelname gestopt', 'Je doet dit jaar', 'niet mee', $entry->status === EntryStatus::Withdrawn ? 'Je hebt je deelname teruggetrokken. Vragen? Mail ons, we helpen graag.' : 'Je inschrijving is geannuleerd. Vragen? Mail ons, we helpen graag.', null, null),
        };
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
            EntryStatus::PendingPayment => $entry->isReservationExpired()
                ? ['toon' => 'fout', 'icoon' => 'waarschuwing', 'tekst' => '<strong>Je reservering is verlopen.</strong> De plek is vrijgegeven. Meld je opnieuw aan als er nog plek is.']
                : ['toon' => 'waarschuwing', 'icoon' => 'klok', 'tekst' => '<strong>Je plek is nog even gereserveerd.</strong> Rond de betaling af vóór '.DutchTime::format($entry->reservation_expires_at, 'HH:mm').' uur, anders komt hij vrij voor een andere bakker.'],
            EntryStatus::Registered => ['toon' => 'succes', 'icoon' => 'vink', 'tekst' => '<strong>Alles is in orde.</strong> Je betaling is ontvangen'.($entry->confirmed_at ? ' op '.DutchTime::date($entry->confirmed_at) : '').'. Op de website sta je als '.$publicName.'.'],
            EntryStatus::Scheduled => ['toon' => 'succes', 'icoon' => 'vink', 'tekst' => '<strong>Je bent ingepland.</strong> Je aanlevercode is <strong>'.e((string) $entry->delivery_code).'</strong>. Tot de testdag hoef je niets te doen.'],
            EntryStatus::Received, EntryStatus::Numbered => ['toon' => 'info', 'icoon' => 'info', 'tekst' => '<strong>Je oliebollen zijn binnen.</strong> Ze hebben een testnummer gekregen; het panel ziet nooit je naam.'],
            EntryStatus::Scored, EntryStatus::Reviewed, EntryStatus::Linked => ['toon' => 'info', 'icoon' => 'info', 'tekst' => '<strong>Het panel heeft geproefd.</strong> De scorecontrole maakt de uitslag definitief; daarna komt hij in de eerstvolgende publicatie.'],
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

        if (Route::has('portaal.medewerkers')) {
            $actions[] = ['titel' => 'Geef een collega toegang', 'tekst' => 'Dan kan die ook het aanlevermoment zien en bonnen scannen.', 'icoon' => 'mensen', 'toon' => 'info', 'url' => route('portaal.medewerkers', $company), 'staat' => '', 'pijl' => 'pijl-rechts'];
        }

        if ($invoice !== null && Route::has('portaal.facturen.toon')) {
            $actions[] = ['titel' => 'Factuur '.$invoice->number, 'tekst' => $invoice->status->getLabel().' · '.$invoice->formattedTotal().' · bekijken of opslaan als pdf', 'icoon' => 'document', 'toon' => 'succes', 'url' => route('portaal.facturen.toon', $invoice), 'staat' => 'klaar', 'pijl' => 'download'];
        }

        if ($entry !== null && Route::has('portaal.planning.bewijs')) {
            $actions[] = $entry->deliverySlot !== null
                ? ['titel' => 'Aanleverbewijs', 'tekst' => 'Met QR-code en je aanlevercode. Afdrukken of op je telefoon laten zien.', 'icoon' => 'klembord', 'toon' => 'goud', 'url' => route('portaal.planning.bewijs', $company), 'staat' => '', 'pijl' => 'pijl-rechts']
                : ['titel' => 'Aanleverbewijs', 'tekst' => 'Komt beschikbaar zodra je een moment hebt gekozen.', 'icoon' => 'klembord', 'toon' => 'neutraal', 'url' => null, 'staat' => 'later', 'pijl' => 'vergrendel'];
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

    /**
     * @return Actie
     */
    private function action(string $eyebrow, string $titel, ?string $accent, string $tekst, ?string $knop, ?string $url, ?string $secundair = null, ?string $secundairUrl = null): array
    {
        return ['eyebrow' => $eyebrow, 'titel' => $titel, 'accent' => $accent, 'tekst' => $tekst, 'knop' => $knop, 'url' => $url, 'secundair' => $secundair, 'secundair_url' => $secundairUrl];
    }
}

@php
    use App\Domain\Edition\Models\Edition;
    use App\Domain\Edition\Enums\TermsType;
    use App\Domain\Edition\Models\TermsVersion;
    use App\Support\DutchTime;

    $edition = Edition::current();
    $open = ($edition?->isRegistrationOpen() ?? false) && TermsVersion::latestPublished(TermsType::Participation) !== null;
    $opens = $edition?->registration_opens_at;
    $packages = $edition ? \App\Domain\Commerce\Models\Package::query()->where('edition_id', $edition->getKey())->active()->get() : collect();
@endphp
<h3>Aanmelden</h3><p class="form-sub">Editie {{ $edition?->year ?? now()->year }}</p>
@if ($open)
    <p class="body-text" style="margin-bottom:18px">Aanmelden duurt vijf minuten: bedrijfsgegevens, provincie, de naam waarmee je op de site wilt staan en je akkoord op de voorwaarden. Je rekent direct af en je plek is dan zeker.</p>
    @if ($packages->isNotEmpty() && $edition->settings->openingOfferEnabled())
        <p class="body-text" style="margin-bottom:18px"><b>Openingsactie:</b> deelname voor {{ \App\Support\Money::format($edition->settings->openingOfferCents) }} in plaats van {{ \App\Support\Money::format($packages->first()->price_cents) }} excl. btw.@if ($edition->settings->plainAddonEnabled()) Test voor {{ \App\Support\Money::format($edition->settings->plainAddonCents) }} extra ook je naturel oliebol.@endif</p>
    @elseif ($packages->isNotEmpty())
        <p class="body-text" style="margin-bottom:18px">Deelname: {{ $packages->map(fn ($p) => $p->name.' '.$p->formattedPriceInclVat())->implode(', ') }}.</p>
    @endif
    <a class="f-submit" href="{{ route('aanmelden') }}" style="display:block;text-align:center;text-decoration:none">Start je aanmelding</a>
    <p class="f-note">Je gegevens gebruiken we om je aanmelding te verwerken. Lees de <a href="{{ route('privacy') }}" style="color:var(--gold)">privacyverklaring</a> en de <a href="{{ route('voorwaarden.deelname') }}" style="color:var(--gold)">voorwaarden voor deelneming</a>.</p>
@else
    <p class="body-text" style="margin-bottom:18px">
        @if ($edition?->registrationHasClosed())
            De inschrijving voor editie {{ $edition->year }} is gesloten.
        @elseif ($opens && $opens->isFuture())
            De inschrijving opent op {{ DutchTime::format($opens, 'dddd D MMMM [om] HH:mm') }} uur. Vanaf dan kun je hier je bedrijf aanmelden.
        @else
            De inschrijving opent binnenkort. Houd deze pagina in de gaten of stel je vraag per e-mail.
        @endif
    </p>
    <a class="f-submit" href="mailto:info@degoudenoliebol.nl?subject=Aanmelden%20De%20Gouden%20Bol" style="display:block;text-align:center;text-decoration:none">Houd mij op de hoogte</a>
    <p class="f-note">Lees alvast de <a href="{{ route('voorwaarden.deelname') }}" style="color:var(--gold)">voorwaarden voor deelneming</a>.</p>
@endif

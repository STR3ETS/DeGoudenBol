@php
    use App\Domain\Charities\Services\CharityOverview;
    use App\Domain\Commerce\Services\SponsorPlacements;
    use App\Domain\Edition\Models\Edition;
    use App\Support\DutchTime;
    use App\Support\Money;

    $edition = Edition::current();
    $overview = $edition ? app(CharityOverview::class)->forEdition($edition) : null;
    $partners = $edition ? app(SponsorPlacements::class)->charities($edition) : collect();
@endphp
@if ($overview && ($overview['charities']->isNotEmpty() || $overview['pots']->isNotEmpty() || $partners->isNotEmpty()))
<section class="sec" id="stand" aria-labelledby="stand-h"><div class="sec-inner">
    <span class="eyebrow rev">Transparantie {{ $edition->year }}</span><h2 class="h2 rev" id="stand-h">Zo staat het ervoor</h2>
    <p class="body-text rev" style="max-width:620px">Tot nu toe is <strong>{{ Money::format($overview['reserved_cents']) }}</strong> gereserveerd voor goede doelen: {{ $edition->settings->charityPercentage }}% van de ontvangen bedragen exclusief btw uit deelname en sponsoring.</p>

    @if ($overview['charities']->isNotEmpty())
        <h3 style="margin:28px 0 12px">Goede doelen</h3>
        <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
            @foreach ($overview['charities'] as $row)
                @php $charity = $row['charity']; @endphp
                <div class="jury-slot">
                    <span class="eyebrow" style="display:block">{{ $charity->province?->name ?? 'Landelijk' }} · {{ $charity->status->getLabel() }}{{ $charity->is_anbi ? ' · ANBI' : '' }}</span>
                    <strong>{{ $charity->name }}</strong>
                    <p class="body-text" style="margin:6px 0 0;font-size:.9rem">Gereserveerd {{ Money::format($row['reserved_cents']) }}@if ($row['paid_cents'] > 0) · uitbetaald {{ Money::format($row['paid_cents']) }}@if ($row['last_payout']) op {{ DutchTime::date($row['last_payout']) }}@endif @endif</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($overview['pots']->isNotEmpty())
        <h3 style="margin:28px 0 6px">Regionale potten</h3>
        <p class="body-text" style="max-width:620px;font-size:.9rem;margin-bottom:12px">Bedragen van betalers zonder goedgekeurd doel. Per provincie wordt na de editie een doel gekozen in overleg met de deelnemers.</p>
        <div class="stagger-grid" style="display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
            @foreach ($overview['pots'] as $pot)
                <div class="jury-slot" style="display:flex;justify-content:space-between;gap:12px"><strong>{{ $pot['province']?->name ?? 'Landelijk' }}</strong><span>{{ Money::format($pot['amount_cents']) }}</span></div>
            @endforeach
        </div>
    @endif

    @if ($partners->isNotEmpty())
        <h3 style="margin:28px 0 12px">Partners goede doelen</h3>
        <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($partners as $placement)
                @include('site.partials.sponsor-kaart', ['sponsor' => $placement->sponsor, 'label' => $placement->label ?: 'Partner goede doelen'])
            @endforeach
        </div>
    @endif
</div></section>
@endif

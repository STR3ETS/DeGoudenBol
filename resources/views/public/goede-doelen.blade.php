@php
    use App\Support\DutchTime;
    use App\Support\Money;
    $percentage = $overview['percentage'] ?? ($edition?->settings->charityPercentage ?? 10);
@endphp
<x-layouts.public title="Goede doelen" description="Van iedere deelname en sponsorbijdrage gaat een vast percentage naar goede doelen. Hier staat per doel wat er is gereserveerd en uitbetaald.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container">
            <x-ui.eyebrow>Goede doelen</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">{{ $percentage }}% gaat naar een <em>goed doel</em></h1>
            <p class="body-text max-w-[640px]">Van ieder ontvangen bedrag uit deelname en sponsoring (excl. btw) reserveert De Gouden Bol {{ $percentage }}% voor het goede doel dat de betaler zelf voordroeg. Is er geen goedgekeurd doel, dan gaat het bedrag naar de regionale pot van die provincie. Alles staat hier open en bloot: grondslag, bedrag, selectie en uitbetaling.</p>
        </div>
    </section>

    <section class="bg-zand py-16">
        <div class="site-container">
            @if ($overview)
                <div class="mb-10 grid gap-4 sm:grid-cols-3">
                    <x-ui.stat :cijfer="Money::format($overview['reserved_cents'])" label="Gereserveerd deze editie" />
                    <x-ui.stat :cijfer="Money::format($overview['paid_cents'])" label="Uitbetaald" />
                    <x-ui.stat :cijfer="(string) $overview['charities']->count()" label="Goedgekeurde doelen" />
                </div>
            @endif

            @if (! $overview || $overview['charities']->isEmpty())
                <x-ui.empty-state titel="Nog geen goedgekeurde doelen" tekst="Deelnemers dragen hun goede doel voor in het portaal; na beoordeling verschijnt het hier met grondslag en bedrag." />
            @else
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($overview['charities'] as $row)
                        @php $charity = $row['charity']; @endphp
                        <div class="card">
                            <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $charity->province?->name ?? 'Landelijk' }}{{ $charity->categoryLabel() ? ' · '.$charity->categoryLabel() : '' }}</div>
                            <h2 class="font-kop text-kaarttitel font-semibold text-espresso">@if ($charity->website)<a href="{{ $charity->website }}" rel="noopener" target="_blank" class="no-underline">{{ $charity->name }}</a>@else{{ $charity->name }}@endif</h2>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-ui.chip :status="$charity->status->value === 'paid_out' ? 'succes' : 'goud'">{{ $charity->status->getLabel() }}</x-ui.chip>
                                @if ($charity->is_anbi)<x-ui.chip status="neutraal">ANBI</x-ui.chip>@endif
                            </div>
                            <dl class="mt-4 grid grid-cols-[130px_1fr] gap-y-1.5 text-[0.85rem]">
                                <dt class="text-gedempt">Selectie</dt><dd>Voorgedragen door een {{ $charity->nominated_by_type && str_contains($charity->nominated_by_type, 'Sponsor') ? 'sponsor' : 'deelnemer' }}, beoordeeld op de checklist{{ $charity->reviewed_at ? ' ('.DutchTime::date($charity->reviewed_at).')' : '' }}</dd>
                                <dt class="text-gedempt">Grondslag</dt><dd>{{ Money::format($row['basis_cents']) }} ontvangen excl. btw</dd>
                                <dt class="text-gedempt">Gereserveerd</dt><dd class="font-semibold">{{ Money::format($row['reserved_cents']) }} ({{ $percentage }}%)</dd>
                                <dt class="text-gedempt">Uitbetaald</dt><dd>{{ $row['paid_cents'] > 0 ? Money::format($row['paid_cents']).' op '.DutchTime::date($row['last_payout']) : 'nog niet' }}</dd>
                            </dl>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($overview && $overview['pots']->isNotEmpty())
                <h2 class="kop-3 mt-12 mb-4">Regionale potten</h2>
                <p class="mb-4 max-w-[620px] text-[0.85rem] text-gedempt">Bedragen van betalers zonder goedgekeurd doel. Per provincie wordt na de editie een doel gekozen in overleg met de deelnemers.</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($overview['pots'] as $pot)
                        <div class="card !p-4 flex items-center justify-between gap-3">
                            <span class="font-semibold text-espresso">{{ $pot['province']?->name ?? 'Landelijk' }}</span>
                            <span class="tabular-nums">{{ Money::format($pot['amount_cents']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($sponsors->isNotEmpty())
                <h2 class="kop-3 mt-12 mb-4">Partners goede doelen</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sponsors as $placement)
                        <x-sponsor-tile :sponsor="$placement->sponsor" :label="$placement->label ?: 'Partner goede doelen'" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>

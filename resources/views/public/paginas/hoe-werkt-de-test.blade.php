@php
    use App\Support\DutchTime;

    $year = $edition?->year ?? now()->year;
    $threshold = number_format($settings?->publishThreshold ?? 5.0, 1, ',', '.');
    $count = $criteria->count() ?: 8;
@endphp
<x-layouts.public title="Hoe werkt de keuring" description="Zo beoordeelt De Gouden Bol oliebollen: blind, op {{ $count }} onderdelen tot 100 punten, met scorecontrole en publicatie vanaf {{ $threshold }}.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="watermerk pointer-events-none absolute top-1/2 -right-10 -translate-y-1/2 text-[clamp(8rem,16vw,14rem)] !opacity-[0.05]" aria-hidden="true">100</div>
        <div class="site-container relative max-w-[820px]">
            <x-ui.eyebrow>Het keuringsproces</x-ui.eyebrow>
            <h1 class="kop-pagina mb-5">Hoe wij <em>keuren</em></h1>
            <p class="body-text text-[1.05rem]">Iedere oliebol doorloopt dezelfde negen stappen, van aanmelding tot publicatie. Het panel ziet nooit wie er bakt; alleen een testnummer. Geen cijfer wordt openbaar zonder scorecontrole en de goedkeuring van twee verschillende personen.</p>
        </div>
    </section>

    <section class="bg-zand py-16">
        <div class="site-container">
            <x-ui.section-heading eyebrow="Negen stappen" class="mb-10">Van aanmelding tot <em>Voorlijst</em></x-ui.section-heading>
            <ol class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['Aanmelding', 'Bedrijf, adres, provincie en publicatiegegevens. De plek in de provincie wordt gereserveerd tot de betaling rond is.'],
                    ['Inplannen', 'De bakker kiest een aanleverslot en ontvangt instructies en een QR-aanleverbewijs.'],
                    ['Ontvangst', 'Bij ontvangst worden tijd, temperatuur en aantal vastgelegd. De versheidsklok van '.($settings?->freshnessWindowMinutes ?? 180).' minuten start.'],
                    ['Registratie', 'De inzending krijgt een neutraal testnummer. De koppeling met de bakker staat alleen in een afgeschermde kluis.'],
                    ['Sessie', 'Monsters worden verdeeld over sessies van maximaal '.($settings?->maxSamplesPerSession ?? 8).'. Panelleden met een belangenconflict proeven dat monster niet.'],
                    ['Beoordelen', 'Ieder panellid vult per monster een scorekaart in met '.$count.' onderdelen, sterke punten en ontwikkelkansen. Na indienen is de kaart vergrendeld.'],
                    ['Scorecontrole', 'Gemiddelden, afwijkende kaarten en ontbrekende kaarten worden gecontroleerd. Een bol telt pas mee bij minimaal '.($settings?->minValidCards ?? 6).' kaarten.'],
                    ['Koppeling', 'Pas na de definitieve score wordt het testnummer aan de bakker gekoppeld. Iedere inzage wordt gelogd.'],
                    ['Publicatie', 'Twee verschillende personen keuren de publicatie goed. Vanaf '.$threshold.' komt het cijfer op de Voorlijst; daaronder volgt vertrouwelijke terugkoppeling.'],
                ] as $index => [$title, $text])
                    <li class="card">
                        <div class="watermerk mb-1 text-[2.4rem]">{{ sprintf('%02d', $index + 1) }}</div>
                        <h3 class="mb-2 font-kop text-[1.2rem] font-semibold">{{ $title }}</h3>
                        <p class="body-text text-[0.85rem]">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="py-16">
        <div class="site-container grid gap-12 lg:grid-cols-[1fr_1fr]">
            <div>
                <x-ui.eyebrow>Het 100-puntenmodel</x-ui.eyebrow>
                <h2 class="kop-2 mb-5">{{ $count }} onderdelen, <em>100 punten</em></h2>
                <p class="body-text mb-6">Ieder panellid scoort in hele punten. Het cijfer is het gemiddelde per onderdeel over alle geldige kaarten, opgeteld en gedeeld door tien, afgerond op één decimaal. Die afgeronde waarde bepaalt de rang.</p>
                @if ($criteria->isNotEmpty())
                    <table class="w-full text-[0.9rem]">
                        <thead class="text-left text-[0.68rem] font-bold tracking-[0.12em] text-gedempt uppercase"><tr><th class="border-b border-rand-sterk py-2">Onderdeel</th><th class="border-b border-rand-sterk py-2 text-right">Max. punten</th></tr></thead>
                        <tbody>
                            @foreach ($criteria as $criterion)
                                <tr><td class="border-b border-rand py-2.5 font-semibold">{{ $criterion->name }}</td><td class="border-b border-rand py-2.5 text-right"><x-ui.rank-pill :score="$criterion->max_points" :top="$loop->first" /></td></tr>
                            @endforeach
                            <tr><td class="py-2.5 font-bold">Totaal</td><td class="py-2.5 text-right font-bold">100</td></tr>
                        </tbody>
                    </table>
                @endif
            </div>
            <div>
                <x-ui.eyebrow>Gelijke stand en publicatie</x-ui.eyebrow>
                <h2 class="kop-2 mb-5">Uitlegbaar tot <em>op de decimaal</em></h2>
                <ul class="flex flex-col gap-3">
                    <li class="checklist-rij items-start"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Bij een gelijk cijfer beslist achtereenvolgens het gemiddelde op {{ collect($settings?->tieBreakOrder ?? [])->map(fn ($code) => $criteria->firstWhere('code', $code)?->name)->filter()->join(', ', ' en ') ?: 'de hoofdonderdelen' }}.</span></li>
                    <li class="checklist-rij items-start"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Blijft het gelijk op plaats 1 of op de grens van de Top {{ $settings?->provincialListLength ?? 10 }}, dan volgt een beslisronde: alleen die deelnemers worden opnieuw blind geproefd.</span></li>
                    <li class="checklist-rij items-start"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Publicatie gebeurt op vaste momenten, zodat een nieuwe binnenkomer niet te herleiden is tot één sessie.</span></li>
                    <li class="checklist-rij items-start"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Onder {{ $threshold }} verschijnt niets openbaar. Iedere deelnemer krijgt een vertrouwelijk rapport met sterke punten en concrete ontwikkelkansen.</span></li>
                    @if ($edition?->main_publication_at)
                        <li class="checklist-rij items-start"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>De definitieve Top {{ $settings->provincialListLength }} per provincie en de provinciewinnaars worden bekend op {{ DutchTime::format($edition->main_publication_at, 'dddd D MMMM YYYY') }}. Daarna volgt de landelijke finale, blind en vanaf nul.</span></li>
                    @endif
                </ul>
            </div>
        </div>
    </section>

    <section class="op-donker dot-grid bg-espresso py-20 text-center">
        <div class="site-container">
            <h2 class="kop-2 mx-auto mb-6 max-w-[640px]">Zelf <em>meedoen</em>?</h2>
            <div class="flex flex-wrap justify-center gap-4">
                <x-ui.button :href="route('aanmelden')">Aanmelden als bakker</x-ui.button>
                <x-ui.button variant="outline-licht" :href="route('panel')">Over het panel</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>

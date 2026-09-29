@php
    $year = $edition?->year ?? now()->year;
    $criteriaCount = $criteria->count() ?: 8;
    $capacity = $settings?->capacityPerProvince ?? 50;
    $listLength = $settings?->provincialListLength ?? 10;
    $minCards = $settings?->minValidCards ?? 6;
    $registrationOpen = $edition?->isRegistrationOpen() && Route::has('aanmelden');
    $tickerItems = array_filter([
        "Editie {$year}",
        $provinces->count() ? $provinces->count().' provincies' : null,
        'Blind beoordeeld',
        "{$criteriaCount} onderdelen · 100 punten",
        $publicationShort ? "Uitslag {$publicationDate}" : null,
        $registrationOpen ? 'Inschrijving open' : ($opensAt ? "Inschrijving opent {$opensAt}" : null),
    ]);
@endphp
<x-layouts.public
    title="De onafhankelijke oliebollenkeuring van Nederland"
    description="De Gouden Bol beoordeelt oliebollen blind op een 100-puntenmodel en publiceert per provincie een openbare Voorlijst."
>
    {{-- ============ HERO ============ --}}
    <section class="relative flex min-h-[85vh] items-center overflow-hidden bg-room pt-32 pb-24 text-center" aria-labelledby="hero-titel">
        <div class="blob -top-32 -left-32 h-[500px] w-[500px]" aria-hidden="true"></div>
        <div class="blob -right-20 -bottom-20 h-[360px] w-[360px]" aria-hidden="true"></div>
        <div class="blob top-[35%] right-[6%] h-[220px] w-[220px] !bg-goud-tint/60" aria-hidden="true"></div>
        <div class="watermerk pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[clamp(10rem,20vw,18rem)] !opacity-[0.05]" aria-hidden="true">BOL</div>

        <div class="site-container relative flex flex-col items-center">
            <x-ui.eyebrow class="reveal">&#9733; De onafhankelijke oliebollenkeuring van Nederland &#9733;</x-ui.eyebrow>

            <h1 id="hero-titel" class="kop-display reveal mb-7 max-w-[820px]" data-reveal-delay="80">
                Wie bakt de<br><em>beste bol</em><br>van Nederland?
            </h1>

            <p class="body-text reveal mx-auto mb-12 max-w-[500px] text-[1.05rem]" data-reveal-delay="160">
                De onafhankelijke keuring die bakkers uitdaagt en liefhebbers de weg wijst naar de perfecte oliebol.
            </p>

            <div class="reveal flex flex-wrap items-center justify-center gap-3.5" data-reveal-delay="240">
                <x-ui.button href="#voorlijst">Bekijk de Voorlijst {{ $year }}</x-ui.button>
                @if ($registrationOpen)
                    <x-ui.button variant="outline" :href="route('aanmelden')">Aanmelden als bakker</x-ui.button>
                @else
                    <x-ui.button variant="outline" href="#aanmelden">Aanmelden als bakker</x-ui.button>
                @endif
            </div>
        </div>

        <div class="absolute bottom-8 left-1/2 flex -translate-x-1/2 flex-col items-center gap-2" aria-hidden="true">
            <div class="scroll-lijn"></div>
            <div class="text-[0.6rem] font-bold tracking-[0.2em] text-gedempt uppercase">Scroll</div>
        </div>
    </section>

    {{-- ============ TICKER ============ --}}
    @if ($tickerItems !== [])
        <div class="ticker" aria-hidden="true">
            <div class="ticker__track">
                @foreach ([$tickerItems, $tickerItems] as $set)
                    @foreach ($set as $item)
                        <span class="ticker__item">{{ $item }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============ STATISTIEKSTRIP ============ --}}
    @if ($edition)
        <section class="border-b border-rand-sterk bg-zand" aria-label="Editie {{ $year }} in het kort">
            <div class="site-container grid grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-rand-sterk">
                <x-ui.stat class="reveal" :cijfer="$provinces->count() ?: 12" label="Provincies" />
                <x-ui.stat class="reveal" :cijfer="'Top '.$listLength" label="Per provincie" data-reveal-delay="70" />
                <x-ui.stat class="reveal" :cijfer="$criteriaCount" label="Onderdelen, 100 punten" data-reveal-delay="140" />
                <x-ui.stat class="reveal" :cijfer="$publicationShort ?? '—'" label="Uitslag" data-reveal-delay="210" />
            </div>
        </section>
    @endif

    {{-- ============ OVER DE KEURING ============ --}}
    <section class="section" id="over" aria-labelledby="over-titel">
        <div class="site-container grid items-center gap-16 lg:grid-cols-2 lg:gap-[72px]">
            <div class="reveal relative">
                <x-ui.placeholder-image label="Beeld: oliebollen uit de ketel" class="h-[420px] md:h-[540px]" />
                <div class="zweefkaart zweefkaart--goud -right-4 -bottom-5 flex-col gap-0 md:-right-5">
                    <div class="cijfer text-[2.6rem] text-espresso">100</div>
                    <div class="mt-1 text-[0.65rem] font-bold tracking-[0.14em] uppercase opacity-75">punten per bol</div>
                </div>
                <div class="zweefkaart -top-4 -left-4">
                    <span class="text-[1.4rem] text-goud" aria-hidden="true">&#9733;</span>
                    <div>
                        <div class="text-[0.85rem] font-bold">Blind beoordeeld</div>
                        <div class="text-[0.7rem] text-gedempt">Het panel ziet alleen een testnummer</div>
                    </div>
                </div>
            </div>

            <div class="reveal" data-reveal-delay="120">
                <x-ui.eyebrow>Over de keuring</x-ui.eyebrow>
                <h2 id="over-titel" class="kop-2 mb-5">Het <em>register</em><br>voor de Nederlandse oliebol</h2>
                <p class="body-text mb-6">
                    Ieder seizoen beoordeelt een onafhankelijk panel de oliebollen van deelnemende bakkers en kramen in alle {{ $provinces->count() ?: 12 }} provincies. Niet op naam, maar op testnummer. Het resultaat staat openbaar op de Voorlijst van de provincie.
                </p>
                <ul class="mb-9 flex flex-col gap-3">
                    <li class="checklist-rij"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Blind protocol: verpakking en logo komen de testruimte niet in</span></li>
                    <li class="checklist-rij"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>{{ $criteriaCount }} onderdelen, samen 100 punten, minimaal {{ $minCards }} panelleden per bol</span></li>
                    <li class="checklist-rij"><span class="checklist-rij__icoon" aria-hidden="true">&#9733;</span><span>Cijfer openbaar vanaf {{ $threshold }}; het volledige rapport blijft vertrouwelijk</span></li>
                </ul>
                <x-ui.button href="#hoe-werkt">Hoe werkt de keuring?</x-ui.button>
            </div>
        </div>
    </section>

    {{-- ============ VOORLIJST ============ --}}
    <section class="section bg-zand" id="voorlijst" aria-labelledby="voorlijst-titel">
        <div class="site-container">
            <div class="mb-12 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <x-ui.eyebrow class="reveal">Voorlijst {{ $year }}</x-ui.eyebrow>
                    <h2 id="voorlijst-titel" class="kop-2 reveal">De beste oliebollen<br><em>van dit seizoen</em></h2>
                </div>
                <p class="body-text reveal max-w-[420px] text-[0.9rem]" data-reveal-delay="80">
                    @if ($firstTestDay)
                        De eerste cijfers verschijnen na de eerste testdag op {{ $firstTestDay }}. Tot die tijd tonen we per provincie de beschikbare plekken.
                    @else
                        Zodra de eerste beoordelingen zijn gepubliceerd, verschijnt hier per provincie de Voorlijst.
                    @endif
                </p>
            </div>

            @if ($provinces->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($provinces as $province)
                        <x-ui.card :hover="true" class="reveal relative" :data-reveal-delay="($loop->index % 4) * 70">
                            <div class="watermerk absolute top-3 right-4 text-[3.2rem]">{{ $province->code }}</div>
                            <x-ui.chip status="neutraal" class="mb-3">Nog geen resultaten</x-ui.chip>
                            <div class="font-kop text-kaarttitel font-semibold">{{ $province->name }}</div>
                            <div class="text-[0.78rem] text-gedempt">
                                {{ $province->pivot->capacity ?? $capacity }} plekken
                                @if ($opensAt) &middot; inschrijving opent {{ $opensAt }} @endif
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state titel="Nog geen gepubliceerde resultaten" tekst="De Voorlijst per provincie verschijnt na de eerste publicatie." />
            @endif
        </div>
    </section>

    {{-- ============ HOE WERKT HET ============ --}}
    <section class="section" id="hoe-werkt" aria-labelledby="hoe-werkt-titel">
        <div class="site-container">
            <x-ui.section-heading eyebrow="Het keuringsproces" :centered="true" class="reveal mb-14">
                <span id="hoe-werkt-titel">Hoe wij <em>keuren</em></span>
            </x-ui.section-heading>

            <div class="grid gap-6 md:grid-cols-3">
                <x-ui.card variant="zand" class="reveal">
                    <div class="watermerk mb-2 text-5xl">01</div>
                    <h3 class="kop-3 mb-3">Aanmelden en aanleveren</h3>
                    <p class="body-text text-[0.9rem]">Bakkers melden zich per provincie aan en leveren hun oliebollen aan op een afgesproken tijdslot. Bij ontvangst krijgt iedere inzending een neutraal testnummer.</p>
                    <span class="chip chip--solid mt-4">{{ $capacity }} plekken per provincie</span>
                </x-ui.card>

                <x-ui.card variant="zand" class="reveal" data-reveal-delay="80">
                    <div class="watermerk mb-2 text-5xl">02</div>
                    <h3 class="kop-3 mb-3">Blind beoordeeld</h3>
                    <p class="body-text mb-4 text-[0.9rem]">Het panel ziet alleen het testnummer en scoort {{ $criteriaCount }} onderdelen in hele punten. Het cijfer is het gemiddelde per onderdeel, opgeteld en gedeeld door tien.</p>
                    @if ($criteria->isNotEmpty())
                        <ul class="flex flex-wrap gap-2" aria-label="Onderdelen van het beoordelingsmodel">
                            @foreach ($criteria as $criterion)
                                <li><x-ui.chip status="goud">{{ $criterion->name }} · {{ $criterion->max_points }}</x-ui.chip></li>
                            @endforeach
                        </ul>
                    @endif
                    <span class="chip chip--solid mt-4">{{ $criteriaCount }} onderdelen · 100 punten</span>
                </x-ui.card>

                <x-ui.card variant="zand" class="reveal" data-reveal-delay="160">
                    <div class="watermerk mb-2 text-5xl">03</div>
                    <h3 class="kop-3 mb-3">Openbaar op de Voorlijst</h3>
                    <p class="body-text text-[0.9rem]">Na scorecontrole en goedkeuring door twee personen verschijnt het cijfer op de Voorlijst van de provincie. Iedere deelnemer ontvangt een vertrouwelijk rapport met sterke punten en ontwikkelkansen.</p>
                    <span class="chip chip--solid mt-4">Officieel getest vanaf {{ $threshold }}</span>
                </x-ui.card>
            </div>

            {{-- FAQ --}}
            <div class="mx-auto mt-20 max-w-[760px]">
                <h3 class="kop-3 reveal mb-6">Veelgestelde vragen</h3>
                <details class="faq reveal" open>
                    <summary>Hoe wordt een oliebol beoordeeld?<span class="faq__icoon" aria-hidden="true">+</span></summary>
                    <p class="faq__antwoord">Een onafhankelijk panel proeft blind en ziet alleen een testnummer. Ieder panellid scoort {{ $criteriaCount }} onderdelen in hele punten, samen 100. Het cijfer is het gemiddelde per onderdeel, opgeteld en gedeeld door tien, afgerond op één decimaal. Een bol telt pas mee bij minimaal {{ $minCards }} scorekaarten.</p>
                </details>
                <details class="faq reveal">
                    <summary>Wanneer verschijnen de resultaten?<span class="faq__icoon" aria-hidden="true">+</span></summary>
                    <p class="faq__antwoord">
                        @if ($firstTestDay && $lastTestDay)
                            De testdagen lopen van {{ $firstTestDay }} tot en met {{ $lastTestDay }}.
                        @endif
                        @if ($publicationMoment)
                            Nieuwe cijfers worden op vaste momenten gepubliceerd: {{ $publicationMoment }}.
                        @endif
                        @if ($publicationDate)
                            De definitieve Top {{ $listLength }} per provincie en de provinciewinnaars gaan live op {{ $publicationDate }}. Daarna volgt de landelijke finale.
                        @endif
                    </p>
                </details>
                <details class="faq reveal">
                    <summary>Wat ziet het publiek van mijn resultaat?<span class="faq__icoon" aria-hidden="true">+</span></summary>
                    <p class="faq__antwoord">Vanaf een cijfer van {{ $threshold }} staat u met naam en cijfer op de Voorlijst van uw provincie. Daaronder verschijnt niets openbaar. Iedere deelnemer krijgt in het portaal een vertrouwelijk rapport met sterke punten en concrete ontwikkelkansen.</p>
                </details>
                <details class="faq reveal">
                    <summary>Wie zit er in het panel?<span class="faq__icoon" aria-hidden="true">+</span></summary>
                    <p class="faq__antwoord">Een pool van onafhankelijke proevers werkt volgens een vast rooster. Wie een deelnemer kan herkennen, scoort niet. Een gemeld belangenconflict sluit een panellid voor dat monster uit. De namen maken we na afloop van de editie bekend.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- ============ BEELDBLOK ============ --}}
    <section class="px-6 pb-24 md:px-12" aria-label="Beeld van de keuring">
        <div class="site-container !px-0">
            <div class="beeldblok reveal h-[420px] md:h-[520px]">
                <x-ui.placeholder-image label="Beeld of video: oliebollen in de ketel" class="h-full !rounded-none" />
                <div class="beeldblok__overlay"></div>
                <div class="absolute right-6 bottom-8 left-6 flex flex-wrap items-end justify-between gap-4 md:right-10 md:left-10">
                    <div>
                        <div class="font-kop text-[2rem] leading-[1.15] font-semibold text-room">Oliebollen in de <em class="text-goud italic">ketel</em></div>
                        <div class="mt-1.5 text-[0.75rem] text-room-zacht">Editie {{ $year }} &middot; beeldmateriaal volgt tijdens het seizoen</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ HET PANEL ============ --}}
    <section class="section bg-zand" id="panel" aria-labelledby="panel-titel">
        <div class="site-container">
            <div class="mb-14 max-w-[640px]">
                <x-ui.eyebrow class="reveal">Het panel</x-ui.eyebrow>
                <h2 id="panel-titel" class="kop-2 reveal mb-4">Onafhankelijk en <em>blind</em></h2>
                <p class="body-text reveal" data-reveal-delay="80">Het panel proeft zonder te weten wie er bakt. Zo blijft de smaak het enige dat telt. De namen van de panelleden maken we na afloop van de editie bekend.</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['titel' => 'Alleen testnummers', 'tekst' => 'Het panel ziet nooit een naam, logo of verpakking. De koppeling naar de bakker staat in een afgeschermde kluis.'],
                    ['titel' => 'Minimaal '.$minCards.' proevers per bol', 'tekst' => 'Een bol krijgt pas een cijfer als genoeg panelleden hem onafhankelijk hebben gescoord.'],
                    ['titel' => 'Vergrendelde scorekaart', 'tekst' => 'Na indienen kan een kaart niet meer worden aangepast. Correcties lopen via scorecontrole en zijn altijd traceerbaar.'],
                    ['titel' => 'Vier ogen op elke publicatie', 'tekst' => 'Geen cijfer wordt openbaar zonder scorecontrole en goedkeuring door twee verschillende personen.'],
                ] as $index => $item)
                    <x-ui.card class="reveal" :data-reveal-delay="$index * 70">
                        <div class="icoonvak mb-4" aria-hidden="true">&#9733;</div>
                        <h3 class="font-kop mb-2 text-[1.15rem] font-semibold">{{ $item['titel'] }}</h3>
                        <p class="body-text text-[0.85rem]">{{ $item['tekst'] }}</p>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ BAKKERS AAN HET WOORD (alleen met placeholders) ============ --}}
    @if ($placeholders)
        <section class="section" aria-labelledby="citaten-titel">
            <div class="site-container">
                <x-ui.section-heading eyebrow="Bakkers aan het woord" :centered="true" class="reveal mb-4">
                    <span id="citaten-titel">Wat deelnemers <em>zeggen</em></span>
                </x-ui.section-heading>
                <p class="reveal mx-auto mb-12 max-w-[520px] text-center"><x-ui.chip status="waarschuwing">[PLACEHOLDER: citaten pas na verificatie]</x-ui.chip></p>

                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ([false, true, false] as $index => $dark)
                        <x-ui.card :variant="$dark ? 'espresso' : 'wit'" class="reveal" :data-reveal-delay="$index * 80">
                            <div class="font-kop text-[4.5rem] leading-[0.8] text-goud" aria-hidden="true">&ldquo;</div>
                            <p class="citaat mb-5 {{ $dark ? 'text-room-zacht' : '' }}">[CITAAT VOLGT] Hier komt een geverifieerd citaat van een deelnemer aan De Gouden Bol {{ $year }}.</p>
                            <div class="text-[0.82rem] font-bold {{ $dark ? 'text-room' : '' }}">[Naam deelnemer]</div>
                            <div class="text-[0.72rem] {{ $dark ? 'text-room-meta' : 'text-gedempt' }}">[Bedrijf &middot; provincie]</div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ AANMELDEN ============ --}}
    <section class="section bg-zand" id="aanmelden" aria-labelledby="aanmelden-titel">
        <div class="site-container grid items-center gap-16 lg:grid-cols-2 lg:gap-[72px]">
            <div class="reveal">
                <x-ui.eyebrow>Meld je aan</x-ui.eyebrow>
                <h2 id="aanmelden-titel" class="kop-2 mb-5">Laat uw oliebollen<br><em>officieel keuren</em></h2>
                <p class="body-text mb-7">
                    @if ($registrationOpen)
                        De inschrijving voor editie {{ $year }} is open. Per provincie zijn {{ $capacity }} plekken beschikbaar.
                    @elseif ($opensAtLong)
                        De inschrijving voor editie {{ $year }} opent op {{ $opensAtLong }}. Per provincie zijn {{ $capacity }} plekken beschikbaar.
                    @else
                        De inschrijving voor de volgende editie wordt binnenkort aangekondigd.
                    @endif
                </p>
                <ul class="mb-9 flex flex-col gap-3">
                    <li class="checklist-rij checklist-rij--wit"><span class="checklist-rij__icoon" aria-hidden="true">&rarr;</span><span>Blinde beoordeling door een onafhankelijk panel</span></li>
                    <li class="checklist-rij checklist-rij--wit"><span class="checklist-rij__icoon" aria-hidden="true">&rarr;</span><span>Vertrouwelijk rapport met sterke punten en ontwikkelkansen</span></li>
                    <li class="checklist-rij checklist-rij--wit"><span class="checklist-rij__icoon" aria-hidden="true">&rarr;</span><span>Publicatie op de Voorlijst van uw provincie vanaf {{ $threshold }}</span></li>
                    <li class="checklist-rij checklist-rij--wit"><span class="checklist-rij__icoon" aria-hidden="true">&rarr;</span><span>Badge, socialmediakit en persbericht bij een erkenning</span></li>
                </ul>
                <x-ui.placeholder-image label="Beeld: bakker aan het werk" class="h-[240px] !rounded-kaart" />
            </div>

            <div class="reveal" data-reveal-delay="120">
                <x-ui.card variant="wit" :paneel="true" class="!p-8 md:!p-12">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-kop text-[1.8rem] font-semibold">Aanmeldformulier</h3>
                        @if ($registrationOpen)
                            <x-ui.chip status="succes" :dot="true">Inschrijving open</x-ui.chip>
                        @elseif ($opensAt)
                            <x-ui.chip status="info" :dot="true">Opent {{ $opensAt }}</x-ui.chip>
                        @endif
                    </div>
                    <p class="mb-7 text-[0.8rem] text-gedempt">Editie {{ $year }} &middot; blind beoordeeld, {{ $capacity }} plekken per provincie</p>

                    @if ($registrationOpen)
                        <p class="body-text mb-6 text-[0.9rem]">De aanmelding bestaat uit een paar korte stappen: bedrijfsgegevens, adres en provincie, publicatiegegevens, voorwaarden en betaling.</p>
                        <x-ui.button :href="route('aanmelden')" :breed="true">Start de aanmelding</x-ui.button>
                    @else
                        <fieldset disabled class="contents">
                            <legend class="sr-only">Voorbeeld van het aanmeldformulier, nog niet actief</legend>
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <x-ui.field name="bedrijfsnaam" label="Naam bedrijf" placeholder="Bakkerij…" :required="true" />
                                <x-ui.field name="contactpersoon" label="Contactpersoon" placeholder="Naam…" :required="true" />
                                <x-ui.field name="email" label="E-mail" type="email" placeholder="naam@bedrijf.nl" :required="true" />
                                <x-ui.field name="telefoon" label="Telefoon" type="tel" placeholder="06…" />
                            </div>
                            <x-ui.field name="kvk" label="KvK-nummer" placeholder="12345678" :required="true" class="mt-3.5" />
                            <x-ui.select name="provincie" label="Provincie" :required="true" class="mt-3.5" :options="$provinces->pluck('name', 'slug')->all()" />
                        </fieldset>
                        <x-ui.button class="mt-6" :breed="true" disabled>
                            @if ($opensAt) Aanmelden vanaf {{ $opensAt }} @else Aanmelden binnenkort mogelijk @endif
                        </x-ui.button>
                        <p class="mt-3 text-center text-[0.72rem] text-gedempt">Blinde beoordeling. Bevestiging en aanleverbewijs per e-mail.</p>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </section>

    {{-- ============ DONKERE CTA ============ --}}
    @if ($sponsors->isNotEmpty())
        <section class="section--sub border-t border-rand-sterk py-14" aria-labelledby="sponsoren-titel">
            <div class="site-container">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <x-ui.eyebrow>Sponsoren</x-ui.eyebrow>
                        <h2 id="sponsoren-titel" class="kop-3">Mede mogelijk gemaakt door</h2>
                    </div>
                    <a href="{{ route('sponsoren') }}" class="text-[0.85rem] font-bold text-goud-tekst">Alle sponsoren en partners &rarr;</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($sponsors as $placement)
                        <x-sponsor-tile :sponsor="$placement->sponsor" :label="$placement->location === 'national' ? ($placement->label ?: 'Landelijk partner') : 'Sponsor'" :compact="true" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="op-donker dot-grid relative overflow-hidden bg-espresso py-24 text-center">
        <div class="blob -top-32 -right-20 h-[500px] w-[500px]" aria-hidden="true"></div>
        <div class="site-container relative">
            <x-ui.eyebrow :licht="true" class="reveal">Editie {{ $year }}</x-ui.eyebrow>
            <h2 class="kop-2 reveal mx-auto mb-5 max-w-[680px]" data-reveal-delay="80">Uw oliebollen verdienen<br><em>een eerlijk oordeel</em></h2>
            <p class="body-text reveal mx-auto mb-10 max-w-[460px]" data-reveal-delay="160">
                @if ($registrationOpen)
                    Meld uw bakkerij of kraam aan voor De Gouden Bol {{ $year }}.
                @elseif ($opensAtLong)
                    De inschrijving opent op {{ $opensAtLong }}.
                @else
                    De inschrijving voor de volgende editie wordt binnenkort aangekondigd.
                @endif
            </p>
            <div class="reveal flex flex-wrap justify-center gap-4" data-reveal-delay="240">
                @if ($registrationOpen)
                    <x-ui.button :href="route('aanmelden')">Nu aanmelden voor {{ $year }}</x-ui.button>
                @else
                    <x-ui.button href="#aanmelden">Aanmelden voor {{ $year }}</x-ui.button>
                @endif
                <x-ui.button variant="outline-licht" href="#voorlijst">Voorlijst {{ $year }}</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>

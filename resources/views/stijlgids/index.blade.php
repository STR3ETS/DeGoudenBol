@inject('tokens', 'App\Support\DesignTokens')
<x-layouts.public title="Stijlgids">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <div class="site-container pt-36 pb-24">
        <x-ui.eyebrow>Levende stijlgids</x-ui.eyebrow>
        <h1 class="kop-pagina mb-4">Het Register <em>Soft</em></h1>
        <p class="body-text mb-16 max-w-[640px]">Alle tokens en componenten uit <code class="rounded-rij bg-zand px-1.5 py-0.5 text-[0.85em]">resources/design/tokens.json</code>. Deze pagina is alleen lokaal en op acceptatie zichtbaar.</p>

        {{-- Kleuren --}}
        <section class="mb-20" aria-labelledby="kleuren">
            <h2 id="kleuren" class="kop-2 mb-8">Kleuren</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($tokens->colors() as $name => $value)
                    <div class="card !p-0 overflow-hidden">
                        <div class="h-20 border-b border-rand" style="background: {{ $value }}"></div>
                        <div class="p-3">
                            <div class="text-[0.8rem] font-bold">{{ $name }}</div>
                            <div class="text-[0.72rem] text-gedempt">{{ $value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h3 class="kop-3 mt-12 mb-6">Statuskleuren</h3>
            <div class="flex flex-wrap gap-3">
                @foreach ($tokens->statusColors() as $name => $definition)
                    <x-ui.chip :status="$name" :dot="true">{{ ucfirst($name) }} · {{ $definition['contrast'] ?? '' }}</x-ui.chip>
                @endforeach
                <x-ui.chip status="goud">goud-licht</x-ui.chip>
                <x-ui.chip>standaard</x-ui.chip>
            </div>

            <h3 class="kop-3 mt-12 mb-6">Backoffice-grijs</h3>
            <div class="flex overflow-hidden rounded-veld border border-rand">
                @foreach ($tokens->grayPalette() as $shade => $hex)
                    <div class="flex-1 py-6 text-center text-[0.65rem] font-bold {{ $shade >= 500 ? 'text-room' : 'text-espresso' }}" style="background: {{ $hex }}">{{ $shade }}</div>
                @endforeach
            </div>
        </section>

        {{-- Typografie --}}
        <section class="mb-20" aria-labelledby="typografie">
            <h2 id="typografie" class="kop-2 mb-8">Typografie</h2>
            <div class="flex flex-col gap-8">
                <div><x-ui.eyebrow>Eyebrow · Manrope .65rem 700</x-ui.eyebrow><div class="kop-display">Display H1 <em>cursief goud</em></div></div>
                <div class="kop-pagina">Pagina H1 met <em>nadruk</em></div>
                <div class="kop-2">H2 kopregel <em>cursief</em></div>
                <div class="kop-3">H3 kaarttitel</div>
                <div class="cijfer text-cijfer-xl text-goud-tekst">9,4</div>
                <p class="body-text max-w-[640px]">Broodtekst in Manrope .97rem met regelafstand 1.82 en de kleur gedempt (#6B5B4A, 6,0:1 op room). Het panel ziet alleen testnummers; de koppeltabel staat in de kluis.</p>
                <p class="citaat max-w-[560px]">“Citaat of jurynotitie in Cormorant cursief, alleen met echte en geverifieerde tekst.”</p>
                <div class="text-meta text-gedempt">Meta · .75rem 600</div>
                <div class="label">Formulierlabel</div>
            </div>
        </section>

        {{-- Knoppen --}}
        <section class="mb-20" aria-labelledby="knoppen">
            <h2 id="knoppen" class="kop-2 mb-8">Knoppen</h2>
            <div class="flex flex-wrap items-center gap-4">
                <x-ui.button>Primair (goud)</x-ui.button>
                <x-ui.button variant="outline">Outline</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button size="sm">Klein</x-ui.button>
                <x-ui.button disabled>Uitgeschakeld</x-ui.button>
            </div>
            <div class="op-donker mt-6 flex flex-wrap items-center gap-4 rounded-kaart bg-espresso p-8">
                <x-ui.button>Primair op donker</x-ui.button>
                <x-ui.button variant="outline-licht">Outline licht</x-ui.button>
            </div>
        </section>

        {{-- Pillen --}}
        <section class="mb-20" aria-labelledby="pillen">
            <h2 id="pillen" class="kop-2 mb-8">Rangpillen en chips</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.rank-pill :score="9.4" :top="true" />
                <x-ui.rank-pill :score="8.7" />
                <x-ui.chip status="succes" :dot="true">Nu open</x-ui.chip>
                <x-ui.chip status="neutraal" :dot="true">Gesloten</x-ui.chip>
                <x-ui.chip status="neutraal">Gedaald</x-ui.chip>
                <x-ui.chip status="succes">Gestegen</x-ui.chip>
                <x-ui.chip status="info">Nieuw</x-ui.chip>
                <x-ui.chip status="waarschuwing">Wacht op goedkeuring</x-ui.chip>
                <x-ui.chip status="fout">Versheid verlopen</x-ui.chip>
            </div>
        </section>

        {{-- Kaarten --}}
        <section class="mb-20" aria-labelledby="kaarten">
            <h2 id="kaarten" class="kop-2 mb-8">Kaarten</h2>
            <div class="grid gap-6 md:grid-cols-3">
                <x-ui.card :hover="true" class="relative">
                    <div class="watermerk absolute top-3 right-4 text-6xl">01</div>
                    <x-ui.rank-pill :score="9.4" :top="true" class="mb-3" />
                    <div class="text-kaarttitel font-kop font-semibold">Ranglijstkaart</div>
                    <div class="text-[0.78rem] text-gedempt">Plaats · Provincie</div>
                    <p class="citaat mt-3 border-t border-rand pt-3 text-[0.82rem]">Notitie onder een lijn.</p>
                </x-ui.card>
                <x-ui.card variant="zand">
                    <div class="watermerk mb-2 text-5xl">02</div>
                    <h3 class="kop-3 mb-2">Stapkaart</h3>
                    <p class="body-text text-[0.9rem]">Zand, 36 px padding, watermerknummer.</p>
                </x-ui.card>
                <x-ui.card variant="espresso">
                    <div class="font-kop text-6xl leading-[0.8] text-goud">&ldquo;</div>
                    <p class="citaat text-room-zacht">Donkere kaart voor citaten.</p>
                </x-ui.card>
            </div>
        </section>

        {{-- Formulier --}}
        <section class="mb-20" aria-labelledby="formulier">
            <h2 id="formulier" class="kop-2 mb-8">Formulier</h2>
            <x-ui.card variant="zand" :paneel="true" class="max-w-[640px]">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field name="naam" label="Naam bedrijf" placeholder="Bakkerij…" :required="true" />
                    <x-ui.field name="email" label="E-mail" type="email" placeholder="naam@bedrijf.nl" :required="true" help="Wij mailen hier het aanleverbewijs naartoe." />
                </div>
                <x-ui.select name="provincie" label="Provincie" :required="true" class="mt-4" :options="\App\Domain\Edition\Models\Province::query()->orderBy('sort')->pluck('name', 'slug')->all()" />
                <x-ui.button class="mt-6" :breed="true" type="submit">Aanmelding versturen</x-ui.button>
            </x-ui.card>
        </section>

        {{-- Lege staat en stats --}}
        <section class="mb-20" aria-labelledby="overig">
            <h2 id="overig" class="kop-2 mb-8">Lege staat en statistiek</h2>
            <x-ui.empty-state titel="Nog geen gepubliceerde resultaten" tekst="De eerste Voorlijst verschijnt na de eerste publicatiebatch in november." />
            <div class="mt-8 grid grid-cols-2 border-y border-rand-sterk bg-zand lg:grid-cols-4 lg:divide-x lg:divide-rand-sterk">
                <x-ui.stat cijfer="12" label="Provincies" />
                <x-ui.stat cijfer="100" label="Punten" />
                <x-ui.stat cijfer="8" label="Onderdelen" />
                <x-ui.stat cijfer="5" suffix=",0" label="Publicatiedrempel" />
            </div>
        </section>

        {{-- Logo --}}
        <section aria-labelledby="logo">
            <h2 id="logo" class="kop-2 mb-8">Logo</h2>
            <div class="flex flex-wrap items-center gap-12">
                <x-ui.logo />
                <div class="rounded-kaart bg-espresso p-6"><x-ui.logo :dark="true" /></div>
                <x-ui.logo :naam="false" :size="96" />
            </div>
        </section>
    </div>
</x-layouts.public>

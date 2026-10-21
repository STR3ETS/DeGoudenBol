@php
    $secties = [
        'fundament' => 'Fundament',
        'knoppen' => 'Knoppen',
        'velden' => 'Velden',
        'keuze' => 'Dropdowns',
        'datum' => 'Kalender',
        'chips' => 'Chips & badges',
        'feedback' => 'Meldingen',
        'kaarten' => 'Kaarten & tegels',
        'rijen' => 'Rijen & lijsten',
        'tabel' => 'Tabellen',
        'structuur' => 'Tabs, stappen, tijdlijn',
        'overlays' => 'Menu, modal, lade',
        'navigatie' => 'Navigatie',
        'dashboard' => 'Voorbeeld dashboard',
    ];
    $kleuren = [
        ['goud', '#C8860A', 'Accent, knoppen, pillen. Nooit als tekst op licht.'],
        ['goud-tekst', '#8A5A00', 'Gouden tekst en links op licht.'],
        ['goud-licht', '#FEF3E2', 'Achtergrond chips, vandaag-rij, gekozen optie.'],
        ['espresso', '#1C0F03', 'Primaire tekst, sidebar, donkere secties.'],
        ['room', '#FAF5EA', 'Paginabasis.'],
        ['zand', '#F0E8D5', 'Secties, velden, filterbalk.'],
        ['wit', '#FFFFFF', 'Kaarten en panelen.'],
        ['gedempt', '#6B5B4A', 'Secundaire tekst, labels.'],
    ];
    $statussen = [
        ['succes', '#2F6B3A', '#E6F2E7', 'Betaald, gepubliceerd, verzilverd'],
        ['waarschuwing', '#8A5A00', '#FEF3E2', 'Wacht op goedkeuring, bijna verlopen'],
        ['fout', '#9B2C2C', '#FBE9E7', 'Geweigerd, ongeldig, verlopen'],
        ['info', '#2C5A7A', '#E8F0F6', 'Ingepland, in behandeling'],
        ['neutraal', '#5A5A6A', '#ECE9E2', 'Gedaald, historisch, gesloten'],
    ];
    $provincies = ['Drenthe', 'Flevoland', 'Friesland', 'Gelderland', 'Groningen', 'Limburg', 'Noord-Brabant', 'Noord-Holland', 'Overijssel', 'Utrecht', 'Zeeland', 'Zuid-Holland'];
@endphp
<x-layouts.public title="Stijlgids" description="Componentbibliotheek van De Gouden Bol.">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="pt-32 pb-10">
        <div class="site-container">
            <x-ui.eyebrow>Het Register Soft · UI-kit 2026</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Stijl<em>gids</em></h1>
            <p class="body-text max-w-[680px]">Alle bouwstenen van De Gouden Bol in één bibliotheek: tokens, knoppen, velden met eigen dropdowns en kalender, chips, meldingen, tabellen, tegels, overlays en navigatie. Klassen heten <code class="text-goud-tekst">ui-*</code>, zijn Nederlands en werken op de publiekssite, in het portaal en in de backoffice (gedrag in <code class="text-goud-tekst">resources/js/ui.js</code>, geen dependencies).</p>
        </div>
    </section>

    <div class="site-container grid gap-10 pb-24 lg:grid-cols-[200px_1fr]">
        <nav class="hidden lg:block" aria-label="Stijlgids-secties">
            <ul class="sticky top-[100px] flex flex-col gap-1 text-[0.8rem]">
                @foreach ($secties as $anker => $label)
                    <li><a href="#{{ $anker }}" class="block rounded-[8px] px-3 py-1.5 font-semibold text-gedempt no-underline hover:bg-zand hover:text-espresso">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0">
                        @include('stijlgids.secties.fundament')
            @include('stijlgids.secties.knoppen')
            @include('stijlgids.secties.velden')
            @include('stijlgids.secties.dropdowns')
            @include('stijlgids.secties.kalender')
            @include('stijlgids.secties.chips')
            @include('stijlgids.secties.meldingen')
            @include('stijlgids.secties.kaarten')
            @include('stijlgids.secties.rijen')
            @include('stijlgids.secties.tabel')
            @include('stijlgids.secties.structuur')
            @include('stijlgids.secties.overlays')
            @include('stijlgids.secties.navigatie')
            @include('stijlgids.secties.dashboard')
        </div>
    </div>
</x-layouts.public>

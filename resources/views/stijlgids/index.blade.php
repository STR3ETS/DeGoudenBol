@php
    $secties = [
        'principes' => 'Zes principes',
        'liefde' => 'Nieuw in versie 2',
        'prototypes' => 'Prototypes',
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
<x-layouts.public title="Stijlgids" description="Ontwerpprincipes en componentbibliotheek van De Gouden Bol.">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="pt-32 pb-10">
        <div class="site-container">
            <div class="flex flex-wrap items-end gap-6">
                <div class="min-w-0 flex-1">
                    <x-ui.eyebrow>Het Register Soft · UI-kit versie 2 · oktober 2026</x-ui.eyebrow>
                    <h1 class="kop-pagina mb-4">Stijlgids <em>met liefde</em></h1>
                    <p class="body-text max-w-[680px]">Hoe De Gouden Bol eruitziet en aanvoelt, voor de publiekssite, het deelnemersportaal en de backoffice. Eerst de zes principes die elk scherm eenvoudig en vriendelijk houden, dan de nieuwe componenten, twee klikbare prototypes en de volledige bibliotheek. Klassen heten <code class="text-goud-tekst">ui-*</code>, zijn Nederlands en hebben geen dependencies.</p>
                </div>
                <x-ui.bol stemming="blij" class="ui-bol--groot" />
            </div>
        </div>
    </section>

    <div class="site-container grid gap-10 pb-24 lg:grid-cols-[200px_1fr]">
        <nav class="hidden lg:block" aria-label="Stijlgids-secties">
            <ul class="sticky top-[100px] flex flex-col gap-1 text-[0.8rem]">
                @foreach ($secties as $anker => $label)
                    <li><a href="#{{ $anker }}" class="block rounded-[8px] px-3 py-1.5 font-semibold text-gedempt no-underline hover:bg-zand hover:text-espresso">{{ $label }}</a></li>
                    @if ($anker === 'prototypes')<li class="my-2 border-t border-rand"></li>@endif
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0">
            @include('stijlgids.secties.principes')
            @include('stijlgids.secties.liefde')
            @include('stijlgids.secties.dashboard')
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
        </div>
    </div>
</x-layouts.public>

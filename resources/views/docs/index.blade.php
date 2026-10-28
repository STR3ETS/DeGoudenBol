@php
    $totaal = collect($secties)->sum(fn (array $sectie) => count($sectie['vragen']));
    $iconen = ['starten' => 'zon', 'inschrijving' => 'klembord', 'bakkers' => 'winkel', 'testdagen' => 'kalender', 'scores-en-uitslag' => 'ster', 'campagne' => 'bliksem', 'geld' => 'euro', 'instellingen-en-accounts' => 'instellingen', 'problemen' => 'waarschuwing'];
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Handleiding – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/cormorant-garamond-latin-600-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/manrope-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ui-app">
    <div class="ui-app__raster">
        <aside class="ui-app__zij">
            <nav class="ui-sidebar" aria-label="Hoofdstukken">
                <a href="{{ url('/admin') }}" class="ui-sidebar__merk"><x-ui.logo :dark="true" tagline="Handleiding" /></a>
                <div class="ui-sidebar__groep">Hoofdstukken</div>
                @foreach ($secties as $sectie)
                    <a href="#{{ $sectie['slug'] }}" class="ui-sidebar__item" data-hoofdstuk-link="{{ $sectie['slug'] }}"><span class="ui-icoonvak"><x-ui.icoon :naam="$iconen[$sectie['slug']] ?? 'document'" /></span>{{ $sectie['titel'] }}<span class="ui-badge">{{ count($sectie['vragen']) }}</span></a>
                @endforeach
                <div class="ui-sidebar__voet">
                    <a href="{{ url('/admin') }}" class="ui-sidebar__item"><span class="ui-icoonvak"><x-ui.icoon naam="chevron-links" /></span>Terug naar de backoffice</a>
                </div>
            </nav>
        </aside>

        <main class="ui-app__hoofd">
            <header class="ui-app__kop">
                <x-ui.bol class="ui-bol--klein" stemming="blij" />
                <div class="ui-app__kop-tekst">
                    <h1 class="ui-app__titel">Handleiding</h1>
                    <p class="ui-app__sub">{{ $totaal }} vragen en antwoorden over de backoffice, in gewone taal. Staat je vraag er niet bij? Mail Eazyonline, dan komt hij erbij.</p>
                </div>
                <a href="{{ route('docs.json') }}" class="ui-knop ui-knop--klein ui-knop--ghost">Als JSON</a>
            </header>

            <div class="ui-app__blok">
                <label class="ui-zoek" for="docs-zoek">
                    <span class="ui-zoek__icoon"><x-ui.icoon naam="zoek" /></span>
                    <input id="docs-zoek" class="ui-invoer" type="search" placeholder="Typ je vraag, bijvoorbeeld: inschrijving openzetten" autocomplete="off" autofocus>
                </label>
                <p class="ui-hulp mt-2" data-docs-teller>Begin te typen om te zoeken in alle {{ $totaal }} vragen.</p>
            </div>

            @foreach ($secties as $sectie)
                <section id="{{ $sectie['slug'] }}" class="ui-app__blok scroll-mt-6" data-hoofdstuk>
                    <div class="ui-sectie">
                        <h2 class="ui-sectie__titel">{{ $sectie['titel'] }} <span class="ui-sectie__sub">{{ $sectie['intro'] }}</span></h2>
                    </div>
                    <div class="ui-kaart">
                        <div class="ui-kaart__inhoud !pt-1">
                            @foreach ($sectie['vragen'] as $vraag)
                                <details class="ui-vraag" id="{{ $vraag['id'] }}" data-vraag data-tekst="{{ Str::lower($vraag['vraag'].' '.$vraag['antwoord']) }}">
                                    <summary>{{ $vraag['vraag'] }} <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary>
                                    <div class="docs-antwoord">{!! $vraag['antwoord_html'] !!}</div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach

            <div class="ui-hulpkaart">
                <div class="ui-hulpkaart__kop"><x-ui.icoon naam="hart" class="h-5 w-5 text-goud-tekst" /> Niet gevonden?</div>
                <p class="ui-hulpkaart__tekst">Mail Eazyonline met je vraag en, als het kan, de link van de pagina waar je vastliep. Je krijgt op werkdagen dezelfde dag antwoord en de vraag komt in deze handleiding.</p>
                <a href="mailto:{{ config('mail.from.address') }}" class="ui-hulpkaart__link">Mail Eazyonline <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const veld = document.getElementById('docs-zoek');
            const teller = document.querySelector('[data-docs-teller]');
            const vragen = [...document.querySelectorAll('[data-vraag]')];
            const hoofdstukken = [...document.querySelectorAll('[data-hoofdstuk]')];
            const totaal = vragen.length;

            const filter = () => {
                const termen = veld.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
                let zichtbaar = 0;

                vragen.forEach((vraag) => {
                    const tekst = vraag.dataset.tekst;
                    const past = termen.every((term) => tekst.includes(term));
                    vraag.hidden = ! past;
                    if (termen.length > 0 && past) vraag.open = true;
                    if (termen.length === 0) vraag.open = false;
                    if (past) zichtbaar++;
                });

                hoofdstukken.forEach((hoofdstuk) => {
                    hoofdstuk.hidden = ! hoofdstuk.querySelector('[data-vraag]:not([hidden])');
                });

                teller.textContent = termen.length === 0
                    ? `Begin te typen om te zoeken in alle ${totaal} vragen.`
                    : (zichtbaar === 0 ? 'Geen vraag gevonden. Probeer een ander woord, of mail ons onderaan.' : `${zichtbaar} van ${totaal} vragen`);
            };

            veld.addEventListener('input', filter);

            if (location.hash) {
                const doel = document.getElementById(location.hash.slice(1));
                if (doel && doel.matches('[data-vraag]')) doel.open = true;
            }
        })();
    </script>
</body>
</html>

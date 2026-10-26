@props([
    'title' => null,
])
@php
    $user = auth('participant')->user();
    $groepen = [
        'Deelname' => [
            ['label' => 'Mijn deelname', 'route' => 'portaal.dashboard', 'icoon' => 'huis'],
            ['label' => 'Aanleveren', 'route' => 'portaal.planning', 'icoon' => 'kalender'],
            ['label' => 'Uitslag', 'route' => 'portaal.uitslag', 'icoon' => 'ster'],
        ],
        'Mijn bedrijf' => [
            ['label' => 'Profiel op de site', 'route' => 'portaal.profiel', 'icoon' => 'winkel'],
            ['label' => 'Medewerkers', 'route' => 'portaal.medewerkers', 'icoon' => 'mensen'],
            ['label' => 'Facturen', 'route' => 'portaal.facturen', 'icoon' => 'document'],
        ],
        'Na de uitslag' => [
            ['label' => 'Badges en socialkit', 'route' => 'portaal.marketing', 'icoon' => 'bliksem'],
            ['label' => 'Cadeaubonnen', 'route' => 'portaal.cadeaubonnen', 'icoon' => 'cadeau'],
            ['label' => 'Scanner', 'route' => 'portaal.scan', 'icoon' => 'zoek'],
            ['label' => 'Sponsoren', 'route' => 'portaal.sponsoren', 'icoon' => 'link'],
            ['label' => 'Goed doel', 'route' => 'portaal.goed-doel', 'icoon' => 'hart'],
        ],
    ];
    $isActief = fn (string $route): bool => request()->routeIs($route, $route.'.*');
    $mobiel = [
        ['label' => 'Deelname', 'route' => 'portaal.dashboard', 'icoon' => 'huis'],
        ['label' => 'Aanleveren', 'route' => 'portaal.planning', 'icoon' => 'kalender'],
        ['label' => 'Uitslag', 'route' => 'portaal.uitslag', 'icoon' => 'ster'],
        ['label' => 'Bedrijf', 'route' => 'portaal.profiel', 'icoon' => 'winkel'],
    ];
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.classList.add('js');</script>
    <title>{{ $title ? $title.' – ' : '' }}Portaal – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/cormorant-garamond-latin-600-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/manrope-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ui-app {{ $user ? '' : 'ui-app--gast' }}">
    <a href="#inhoud" class="skip-link">Direct naar de inhoud</a>

    @if ($user)
        <div class="ui-app__raster">
            <aside class="ui-app__zij print:hidden">
                <nav class="ui-sidebar" aria-label="Portaalmenu">
                    <a href="{{ route('portaal.dashboard') }}" class="ui-sidebar__merk" aria-label="Deelnemersportaal De Gouden Bol"><x-ui.logo :dark="true" tagline="Deelnemersportaal" /></a>
                    @foreach ($groepen as $groep => $items)
                        <div class="ui-sidebar__groep">{{ $groep }}</div>
                        @foreach ($items as $item)
                            @if (Route::has($item['route']))
                                <a href="{{ route($item['route']) }}" class="ui-sidebar__item {{ $isActief($item['route']) ? 'is-actief' : '' }}" @if ($isActief($item['route'])) aria-current="page" @endif><span class="ui-icoonvak"><x-ui.icoon :naam="$item['icoon']" /></span>{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @endforeach
                    <div class="ui-sidebar__voet">
                        <a href="{{ route('portaal.wachtwoord') }}" class="ui-sidebar__item {{ $isActief('portaal.wachtwoord') ? 'is-actief' : '' }}"><span class="ui-icoonvak"><x-ui.icoon naam="vergrendel" /></span>Wachtwoord</a>
                        <form method="post" action="{{ route('portaal.uitloggen') }}">
                            @csrf
                            <button type="submit" class="ui-sidebar__item w-full"><span class="ui-icoonvak"><x-ui.icoon naam="uitloggen" /></span>Uitloggen</button>
                        </form>
                    </div>
                </nav>
            </aside>

            <main id="inhoud" class="ui-app__hoofd">
                @if (session('status'))
                    <div class="ui-statusregel ui-statusregel--succes mb-6 print:hidden" role="status"><span class="ui-icoonvak ui-icoonvak--succes ui-icoonvak--rond"><x-ui.icoon naam="vink" dikte="2.6" /></span><div>{{ session('status') }}</div></div>
                @endif
                {{ $slot }}
            </main>
        </div>

        <nav class="ui-mobielnav print:hidden" aria-label="Portaalmenu">
            @foreach ($mobiel as $item)
                @if (Route::has($item['route']))
                    <a href="{{ route($item['route']) }}" class="{{ $isActief($item['route']) ? 'is-actief' : '' }}"><x-ui.icoon :naam="$item['icoon']" />{{ $item['label'] }}</a>
                @endif
            @endforeach
            <button type="button" data-ui-modal="portaal-menu"><x-ui.icoon naam="menu" />Meer</button>
        </nav>

        <dialog id="portaal-menu" class="ui-lade" aria-label="Volledig menu">
            <div class="ui-modal__kop"><div><div class="ui-modal__titel">Menu</div><div class="ui-modal__sub">{{ $user->name }}</div></div><button type="button" class="ui-modal__sluit" data-ui-sluit aria-label="Sluiten"><x-ui.icoon naam="kruis" /></button></div>
            <div class="ui-modal__inhoud">
                @foreach ($groepen as $groep => $items)
                    <div class="ui-label mt-4 mb-1">{{ $groep }}</div>
                    <div class="ui-lijst">
                        @foreach ($items as $item)
                            @if (Route::has($item['route']))
                                <a href="{{ route($item['route']) }}" class="ui-rij ui-rij--klik {{ $isActief($item['route']) ? 'ui-rij--goud' : '' }}"><span class="ui-icoonvak"><x-ui.icoon :naam="$item['icoon']" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $item['label'] }}</span></span><span class="ui-rij__chevron"><x-ui.icoon naam="chevron-rechts" dikte="2.4" /></span></a>
                            @endif
                        @endforeach
                    </div>
                @endforeach
                <div class="ui-label mt-4 mb-1">Account</div>
                <div class="ui-lijst">
                    <a href="{{ route('portaal.wachtwoord') }}" class="ui-rij ui-rij--klik"><span class="ui-icoonvak"><x-ui.icoon naam="vergrendel" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Wachtwoord</span></span></a>
                    <form method="post" action="{{ route('portaal.uitloggen') }}">@csrf<button type="submit" class="ui-rij ui-rij--klik w-full text-left"><span class="ui-icoonvak"><x-ui.icoon naam="uitloggen" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Uitloggen</span></span></button></form>
                </div>
            </div>
        </dialog>
    @else
        <main id="inhoud" class="ui-app__gast">
            <a href="{{ route('home') }}" class="mb-8 inline-flex no-underline" aria-label="De Gouden Bol"><x-ui.logo tagline="Deelnemersportaal" /></a>
            @if (session('status'))
                <div class="ui-statusregel ui-statusregel--succes mb-6" role="status"><span class="ui-icoonvak ui-icoonvak--succes ui-icoonvak--rond"><x-ui.icoon naam="vink" dikte="2.6" /></span><div>{{ session('status') }}</div></div>
            @endif
            {{ $slot }}
            <p class="mt-10 text-center text-[0.75rem] text-gedempt">&copy; {{ now()->year }} De Gouden Bol &middot; <a href="{{ route('home') }}" class="text-goud-tekst">Naar de publiekssite</a></p>
        </main>
    @endif
</body>
</html>

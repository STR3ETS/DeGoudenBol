@props([
    'title' => null,
])
@php
    $user = auth('participant')->user();
    $items = [
        ['label' => 'Overzicht', 'route' => 'portaal.dashboard'],
        ['label' => 'Profiel', 'route' => 'portaal.profiel'],
        ['label' => 'Planning', 'route' => 'portaal.planning'],
        ['label' => 'Uitslag', 'route' => 'portaal.uitslag'],
        ['label' => 'Marketing', 'route' => 'portaal.marketing'],
        ['label' => 'Cadeaubonnen', 'route' => 'portaal.cadeaubonnen'],
        ['label' => 'Scanner', 'route' => 'portaal.scan'],
        ['label' => 'Sponsoren', 'route' => 'portaal.sponsoren'],
        ['label' => 'Goed doel', 'route' => 'portaal.goed-doel'],
        ['label' => 'Facturen', 'route' => 'portaal.facturen'],
        ['label' => 'Medewerkers', 'route' => 'portaal.medewerkers'],
        ['label' => 'Wachtwoord', 'route' => 'portaal.wachtwoord'],
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-zand">
    <a href="#inhoud" class="skip-link">Direct naar de inhoud</a>

    <header class="border-b border-rand-sterk bg-room print:hidden">
        <div class="site-container flex flex-wrap items-center justify-between gap-4 py-4">
            <a href="{{ $user ? route('portaal.dashboard') : route('portaal.inloggen') }}" class="no-underline" aria-label="Deelnemersportaal De Gouden Bol">
                <x-ui.logo tagline="Deelnemersportaal" />
            </a>
            @if ($user)
                <nav class="flex flex-wrap items-center gap-1" aria-label="Portaalmenu">
                    @foreach ($items as $item)
                        @if (Route::has($item['route']))
                            <a href="{{ route($item['route']) }}" class="nav__link rounded-pil px-3 {{ request()->routeIs($item['route'], $item['route'].'.*') ? 'bg-goud-licht' : '' }}" @if (request()->routeIs($item['route'], $item['route'].'.*')) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                    <form method="post" action="{{ route('portaal.uitloggen') }}" class="ml-2">
                        @csrf
                        <button type="submit" class="nav__link rounded-pil px-3 text-gedempt">Uitloggen</button>
                    </form>
                </nav>
            @endif
        </div>
    </header>

    <main id="inhoud" class="flex-1">
        <div class="site-container py-10 md:py-14">
            @if (session('status'))
                <x-ui.chip status="succes" :dot="true" class="mb-6 print:hidden">{{ session('status') }}</x-ui.chip>
            @endif
            {{ $slot }}
        </div>
    </main>

    <footer class="border-t border-rand-sterk py-6 text-center text-[0.75rem] text-gedempt print:hidden">
        &copy; {{ now()->year }} De Gouden Bol &middot; <a href="{{ route('home') }}" class="text-goud-tekst">Naar de publiekssite</a>
    </footer>
</body>
</html>

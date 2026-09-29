@props([
    'title' => null,
    'panelist' => null,
])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1C0F03">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Panel De Gouden Bol">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' – ' : '' }}Panel – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ asset('panel.webmanifest') }}">
    @vite(['resources/css/app.css', 'resources/js/panel.js'])
</head>
<body class="panel-body flex min-h-screen flex-col bg-zand" data-panel data-sync-url="{{ route('panel.api.scorekaarten') }}">
    <a href="#inhoud" class="skip-link">Direct naar de inhoud</a>

    <header class="bg-espresso text-room">
        <div class="panel-shell flex items-center justify-between gap-4 !py-3">
            <a href="{{ route('panel.overzicht') }}" class="flex items-center gap-3 no-underline" aria-label="Panel De Gouden Bol">
                <x-ui.logo tagline="Panel" :dark="true" />
            </a>
            <div class="flex items-center gap-3">
                @if ($panelist)
                    <span class="rounded-pil bg-goud px-3 py-1 text-[0.95rem] font-extrabold text-espresso" aria-label="Uw panelcode">{{ $panelist->display_code }}</span>
                @endif
                <span class="rounded-pil bg-room/10 px-3 py-1 text-[0.8rem] font-bold" data-panel-queue hidden></span>
            </div>
        </div>
        <div class="bg-status-waarschuwing-bg px-4 py-2 text-center text-[0.9rem] font-bold text-status-waarschuwing" data-panel-offline hidden>Geen verbinding. Kaarten worden bewaard en verstuurd zodra er weer verbinding is.</div>
    </header>

    <main id="inhoud" class="flex-1">
        <div class="panel-shell">
            @if (session('status'))
                <x-ui.chip status="succes" :dot="true" class="mb-5 !text-[0.95rem]">{{ session('status') }}</x-ui.chip>
            @endif
            {{ $slot }}
        </div>
    </main>

    @if ($panelist)
        <nav class="border-t border-rand-sterk bg-room" aria-label="Panelmenu">
            <div class="panel-shell flex items-center justify-between gap-2 !py-2">
                <a href="{{ route('panel.overzicht') }}" class="panel-navknop {{ request()->routeIs('panel.overzicht', 'panel.monster') ? 'is-actief' : '' }}">Mijn schema</a>
                <a href="{{ route('panel.conflicten') }}" class="panel-navknop {{ request()->routeIs('panel.conflicten') ? 'is-actief' : '' }}">Belangenconflicten</a>
                <form method="post" action="{{ route('panel.uitloggen') }}">
                    @csrf
                    <button type="submit" class="panel-navknop text-gedempt">Uitloggen</button>
                </form>
            </div>
        </nav>
    @endif
</body>
</html>

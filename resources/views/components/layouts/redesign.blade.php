@props([
    'title' => 'Redesign',
    'tagline' => 'Backoffice',
    'menu' => [],
    'actief' => null,
    'merkDonker' => true,
])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} – prototype – {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ui-app">
    <div class="ui-app__raster">
        <aside class="ui-app__zij">
            <nav class="ui-sidebar" aria-label="Hoofdmenu">
                <a href="#" class="ui-sidebar__merk"><x-ui.logo :dark="true" :tagline="$tagline" /></a>
                @foreach ($menu as $groep => $items)
                    @if (! is_int($groep))<div class="ui-sidebar__groep">{{ $groep }}</div>@endif
                    @foreach ($items as $item)
                        <a href="{{ $item['url'] ?? '#' }}" class="ui-sidebar__item {{ ($item['key'] ?? null) === $actief ? 'is-actief' : '' }}">
                            <span class="ui-icoonvak"><x-ui.icoon :naam="$item['icoon']" /></span>{{ $item['label'] }}
                            @if (! empty($item['teller']))<span class="ui-badge">{{ $item['teller'] }}</span>@endif
                        </a>
                    @endforeach
                @endforeach
                <div class="ui-sidebar__voet">{{ $voet ?? '' }}</div>
            </nav>
        </aside>
        <main class="ui-app__hoofd">
            {{ $slot }}
        </main>
    </div>
    {{ $mobiel ?? '' }}
</body>
</html>

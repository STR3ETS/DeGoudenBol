@php
    use App\Support\SiteLinks;

    $links = SiteLinks::resolve(config('site.nav'));
    $cta = config('site.nav_cta');
    $ctaUrl = SiteLinks::url($cta);
@endphp
<header class="nav" data-nav>
    <div class="site-container flex flex-wrap items-center justify-between gap-x-6 gap-y-2 !px-0">
        <a href="{{ route('home') }}" class="no-underline" aria-label="De Gouden Bol, naar de homepage">
            <x-ui.logo />
        </a>

        <button
            type="button"
            class="nav__toggle nav__link gap-2 rounded-pil border border-rand-sterk px-4"
            data-nav-toggle
            aria-expanded="false"
            aria-controls="hoofdmenu"
        >
            <span>Menu</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <nav id="hoofdmenu" class="nav__menu w-full lg:w-auto" data-nav-menu aria-label="Hoofdmenu">
            @foreach ($links as $link)
                <a href="{{ $link['url'] }}" class="nav__link" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach

            @if ($ctaUrl)
                <x-ui.button :href="$ctaUrl" size="sm" class="mt-2 lg:mt-0 lg:ml-2">{{ $cta['label'] }}</x-ui.button>
            @endif
        </nav>
    </div>
</header>

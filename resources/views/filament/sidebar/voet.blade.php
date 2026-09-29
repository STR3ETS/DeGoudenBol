{{-- Voet van de sidebar: editie-instellingen en de inklapknop (render hook SIDEBAR_FOOTER). --}}
@php
    use App\Filament\Pages\Settings;

    $settingsUrl = filament()->auth()->check() ? Settings::getUrl() : null;
    $isSettingsActive = request()->routeIs(Settings::getRouteName());
    $isCollapsible = filament()->isSidebarCollapsibleOnDesktop();
@endphp
<div class="dgb-sidebar-voet">
    @if ($settingsUrl !== null)
        <a
            {{ \Filament\Support\generate_href_html($settingsUrl) }}
            class="dgb-sidebar-voet__knop {{ $isSettingsActive ? 'dgb-sidebar-voet__knop--actief' : '' }}"
            title="Instellingen"
            @if ($isSettingsActive) aria-current="page" @endif
            x-on:click="window.matchMedia(`(max-width: 1024px)`).matches && $store.sidebar.close()"
        >
            <span class="dgb-sidebar-voet__icoon"><x-filament::icon icon="heroicon-o-cog-6-tooth" /></span>
            <span
                class="dgb-sidebar-voet__label fi-sidebar-item-label"
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            >Instellingen</span>
        </a>
    @endif

    @if ($isCollapsible)
        <button
            type="button"
            class="dgb-sidebar-voet__knop"
            aria-controls="fi-main-sidebar"
            x-bind:aria-expanded="$store.sidebar.isOpen"
            x-bind:title="$store.sidebar.isOpen ? 'Menu inklappen' : 'Menu uitklappen'"
            x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        >
            <span class="dgb-sidebar-voet__icoon">
                <x-filament::icon icon="heroicon-o-chevron-double-left" x-show="$store.sidebar.isOpen" />
                <x-filament::icon icon="heroicon-o-chevron-double-right" x-show="! $store.sidebar.isOpen" x-cloak />
            </span>
            <span
                class="dgb-sidebar-voet__label dgb-sidebar-voet__label--desktop fi-sidebar-item-label"
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            >Inklappen</span>
            <span
                class="dgb-sidebar-voet__label dgb-sidebar-voet__label--mobiel fi-sidebar-item-label"
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            >Menu sluiten</span>
        </button>
    @endif
</div>

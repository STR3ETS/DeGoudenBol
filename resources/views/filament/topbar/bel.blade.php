{{-- Bel in de topbar: aantal punten in "Jouw werk vandaag", linkt naar dat blok op het overzicht. --}}
@php
    use App\Filament\Pages\Dashboard;
    use App\Filament\Support\DashboardData;

    $count = filament()->auth()->check() ? app(DashboardData::class)->work()->count() : 0;
    $label = match (true) {
        $count === 0 => 'Niets dat op je wacht',
        $count === 1 => '1 punt vraagt aandacht',
        default => "{$count} punten vragen aandacht",
    };
@endphp
<a {{ \Filament\Support\generate_href_html(Dashboard::getUrl().'#werk') }} class="dgb-bel" title="{{ $label }}" aria-label="{{ $label }}">
    <x-filament::icon icon="heroicon-o-bell" class="dgb-bel__icoon" />
    @if ($count > 0)
        <span class="dgb-bel__badge">{{ $count }}</span>
    @endif
</a>

@php
    /** @var \App\Filament\Support\DashboardData|null $data */
    $data = auth()->check() ? app(\App\Filament\Support\DashboardData::class) : null;
@endphp
@if ($data)
    <div class="dgb-begroeting">
        <span class="dgb-begroeting__kop">{{ $data->greeting() }} <span aria-hidden="true">👋</span></span>
        <span class="dgb-begroeting__sub">{{ $data->subheading() }}</span>
    </div>
@endif

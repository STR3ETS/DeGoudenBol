@props([
    'dark' => false,
    'size' => 44,
    'naam' => true,
    'tagline' => 'Oliebollen Test',
])
{{-- Hetzelfde merkteken als de website (site.partials.header): oliebol in een gouden ring. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <svg viewBox="0 0 48 48" width="{{ $size }}" height="{{ $size }}" fill="none" aria-hidden="true" focusable="false">
        <circle cx="24" cy="24" r="23" fill="#C8860A"/>
        <circle cx="24" cy="24" r="19.6" fill="{{ $dark ? '#1C0F03' : '#FAF5EA' }}"/>
        <image href="{{ asset('site/images/5097bdefb5478dc6e6537a40211dbf6432a82738.png') }}" x="7" y="7" width="34" height="34"/>
    </svg>
    @if ($naam)
        <span>
            <span class="nav__naam block {{ $dark ? 'text-room!' : '' }}">De Gouden Bol</span>
            @if ($tagline)
                <span class="nav__tagline block {{ $dark ? 'text-room-meta!' : '' }}">{{ $tagline }}</span>
            @endif
        </span>
    @endif
</span>

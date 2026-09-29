@props([
    'dark' => false,
    'size' => 44,
    'naam' => true,
    'tagline' => 'Oliebollenkeuring',
])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <svg viewBox="0 0 48 48" width="{{ $size }}" height="{{ $size }}" fill="none" aria-hidden="true" focusable="false">
        <circle cx="24" cy="24" r="23" class="fill-goud"/>
        <circle cx="24" cy="24" r="16" class="{{ $dark ? 'fill-espresso' : 'fill-room' }}"/>
        <polygon points="24,11 26.5,19 35,19 28.5,23.5 31,32 24,27.5 17,32 19.5,23.5 13,19 21.5,19" class="fill-goud"/>
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

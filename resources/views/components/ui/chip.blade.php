@props([
    'status' => null,
    'dot' => false,
])
<span {{ $attributes->merge(['class' => 'chip'.($status ? ' chip--'.$status : '')]) }}>
    @if ($dot)
        <span class="chip__dot" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>

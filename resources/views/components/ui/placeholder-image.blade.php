@props([
    'label' => 'Beeld volgt',
    'src' => null,
    'alt' => '',
    'labelPosition' => 'bottom-left',
])
@php
    $labelClass = match ($labelPosition) {
        'top-left' => 'top-4 left-4',
        'top-right' => 'top-4 right-4',
        'bottom-right' => 'bottom-4 right-4',
        default => 'bottom-4 left-4',
    };
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'block h-full w-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'beeld-placeholder']) }} role="img" aria-label="{{ $label }}">
        <svg viewBox="0 0 48 48" width="96" height="96" fill="none" aria-hidden="true">
            <circle cx="24" cy="24" r="23" class="fill-goud"/>
            <circle cx="24" cy="24" r="16" class="fill-zand"/>
            <polygon points="24,11 26.5,19 35,19 28.5,23.5 31,32 24,27.5 17,32 19.5,23.5 13,19 21.5,19" class="fill-goud"/>
        </svg>
        @if (config('site.placeholders'))
            <span class="chip chip--waarschuwing absolute {{ $labelClass }}">[{{ strtoupper($label) }}]</span>
        @endif
    </div>
@endif

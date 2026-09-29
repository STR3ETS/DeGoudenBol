@props([
    'variant' => 'pill',
    'href' => null,
    'type' => 'button',
    'size' => null,
    'breed' => false,
])
@php
    $class = match ($variant) {
        'outline' => 'btn-pill-out',
        'outline-licht' => 'btn-pill-out btn-pill-out--licht',
        'ghost' => 'btn-ghost',
        default => 'btn-pill',
    };

    if ($size === 'sm') {
        $class .= ' btn--sm';
    }

    if ($breed) {
        $class .= ' btn--breed';
    }
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif

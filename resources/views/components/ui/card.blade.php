@props([
    'variant' => 'wit',
    'hover' => false,
    'paneel' => false,
])
@php
    $class = 'card';

    $class .= match ($variant) {
        'zand' => ' card--zand',
        'espresso' => ' card--espresso op-donker',
        default => '',
    };

    if ($hover) {
        $class .= ' card--hover';
    }

    if ($paneel) {
        $class .= ' card--paneel';
    }
@endphp
<div {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</div>

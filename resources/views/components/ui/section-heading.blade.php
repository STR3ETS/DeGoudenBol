@props([
    'eyebrow' => null,
    'centered' => false,
    'level' => 2,
    'licht' => false,
])
@php
    $tag = 'h'.$level;
@endphp
<div {{ $attributes->merge(['class' => $centered ? 'mx-auto max-w-[580px] text-center' : '']) }}>
    @if ($eyebrow)
        <x-ui.eyebrow :licht="$licht">{{ $eyebrow }}</x-ui.eyebrow>
    @endif
    <{{ $tag }} class="{{ $level === 1 ? 'kop-pagina' : 'kop-2' }} mb-4">{{ $slot }}</{{ $tag }}>
</div>

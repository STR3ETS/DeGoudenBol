@props([
    'sponsor',
    'label' => 'Sponsor',
    'compact' => false,
])
@php
    $logo = $sponsor->logoUrl();
    $tag = $sponsor->url ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($sponsor->url) href="{{ $sponsor->url }}" rel="sponsored noopener" target="_blank" @endif {{ $attributes->merge(['class' => 'card card--hover flex items-center gap-4 no-underline '.($compact ? '!p-4' : '!p-5')]) }}>
    <span class="flex h-14 w-24 shrink-0 items-center justify-center overflow-hidden rounded-[10px] border border-rand bg-room">
        @if ($logo)
            <img src="{{ $logo }}" alt="Logo {{ $sponsor->name }}" class="max-h-12 max-w-[88px] object-contain" loading="lazy">
        @else
            <span class="font-kop px-2 text-center text-[0.8rem] leading-tight font-semibold text-espresso">{{ $sponsor->name }}</span>
        @endif
    </span>
    <span class="min-w-0">
        <span class="block text-[0.6rem] font-bold tracking-[0.2em] text-gedempt uppercase">{{ $label }}</span>
        <span class="block truncate font-semibold text-espresso">{{ $sponsor->name }}</span>
    </span>
</{{ $tag }}>

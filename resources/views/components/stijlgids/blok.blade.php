@props([
    'titel',
    'tekst' => null,
    'klassen' => null,
    'donker' => false,
])
<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 class="font-kop text-[1.25rem] font-semibold text-espresso">{{ $titel }}</h3>
        @if ($klassen)
            <code class="rounded-[6px] bg-zand px-2 py-0.5 text-[0.7rem] font-semibold text-goud-tekst">{{ $klassen }}</code>
        @endif
    </div>
    @if ($tekst)
        <p class="mb-4 max-w-[720px] text-[0.85rem] leading-relaxed text-gedempt">{{ $tekst }}</p>
    @endif
    <div class="rounded-kaart border border-rand p-5 sm:p-7 {{ $donker ? 'bg-espresso' : 'bg-room' }}">
        {{ $slot }}
    </div>
</div>

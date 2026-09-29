@props([
    'titel',
    'tekst' => null,
])
<div {{ $attributes->merge(['class' => 'lege-staat']) }}>
    <div class="lege-staat__titel">{{ $titel }}</div>
    @if ($tekst)
        <p class="lege-staat__tekst">{{ $tekst }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>

@props([
    'cijfer',
    'label',
    'suffix' => null,
])
<div {{ $attributes->merge(['class' => 'stat']) }}>
    <div class="stat__cijfer">{{ $cijfer }}@if ($suffix)<span class="text-goud-tekst">{{ $suffix }}</span>@endif</div>
    <div class="stat__label">{{ $label }}</div>
</div>

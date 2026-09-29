@props([
    'score',
    'top' => false,
])
<span {{ $attributes->merge(['class' => 'rank-pill'.($top ? ' rank-pill--top' : '')]) }}>
    <span aria-hidden="true">&#9733;</span>
    <span class="sr-only">Cijfer</span>
    {{ number_format((float) $score, 1, ',', '.') }}
</span>

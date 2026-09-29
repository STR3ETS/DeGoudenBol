@props(['licht' => false])
<span {{ $attributes->merge(['class' => 'eyebrow'.($licht ? ' eyebrow--licht' : '')]) }}>{{ $slot }}</span>

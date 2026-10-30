@props(['eyebrow' => null, 'title' => null])
@if (filled($eyebrow)){{ $eyebrow }}
@endif
@if (filled($title)){{ $title }}
@endif
{{ $slot }}

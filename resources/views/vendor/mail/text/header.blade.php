@props(['url', 'title' => null, 'accent' => null])
{{ config('app.name') }}@if (filled($title)) – {{ $title }} {{ $accent }}@endif


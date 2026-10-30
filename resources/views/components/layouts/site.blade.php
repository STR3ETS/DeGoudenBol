@props([
    'title' => null,
    'description' => null,
    'mainClass' => '',
    'noindex' => false,
    'ogImage' => null,
])
@php
    $liveJson = \App\Support\Site\LiveData::json();
    $fullTitle = $title ? $title.' | De Gouden Bol' : 'De Gouden Bol | De Nieuwe Oliebollen Test';
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
@include('site.partials.head-script')
@include('site.partials.gtm-head')
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $fullTitle }}</title>
@if ($description)
<meta name="description" content="{{ $description }}">
@endif
@if ($noindex || ! app()->isProduction())
<meta name="robots" content="noindex,nofollow">
@endif
<meta property="og:type" content="website">
<meta property="og:locale" content="nl_NL">
<meta property="og:site_name" content="De Gouden Bol">
<meta property="og:title" content="{{ $fullTitle }}">
@if ($description)
<meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta name="theme-color" content="#FAF5EA">
<link rel="icon" href="/site/assets/775fcbfdfd6952a489c532c1b61d77ec83ffc479.svg" type="image/svg+xml">
<link rel="preload" href="/site/assets/b42951014c5eca12749d87a4706caf22dc4fe081.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/site/assets/74db058fe06da79b5dbf5b33d12041ddd45de510.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/site/assets/82843df790355b2d6db762b59a8ceb2db51b6cf1.css">
<script type="application/ld+json">{"@@context": "https://schema.org", "@type": "Organization", "name": "De Gouden Bol", "url": "{{ url('/') }}", "description": "De Nieuwe Oliebollen Test: een onafhankelijke, blinde zoektocht naar de beste oliebol van Nederland.", "email": "info@degoudenoliebol.nl"}</script>
<script type="application/ld+json">{"@@context": "https://schema.org", "@type": "WebSite", "name": "De Gouden Bol", "url": "{{ url('/') }}", "inLanguage": "nl"}</script>
{{ $structured ?? '' }}
{{ $head ?? '' }}
</head><body>
@include('site.partials.gtm-body')
<a class="skip" href="#main">Ga naar de inhoud</a>
@include('site.partials.loader')
@include('site.partials.header')
<main id="main"{!! $mainClass !== '' ? ' class="'.e($mainClass).'"' : '' !!}>
{{ $slot }}
</main>
@include('site.partials.footer')
@include('site.partials.widget')
<script src="/site/assets/f09daa7f7315e68a8b4af036abab52ea88446eb7.js" defer></script>
{{ $scripts ?? '' }}
@include('site.partials.cookiekeuze')
</body>
</html>

@php use App\Support\DutchTime; @endphp
<x-layouts.site :title="$post->meta_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt" main-class="page-sec">
    <x-slot:structured>
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $post->title, 'description' => $post->excerpt, 'datePublished' => $post->published_at?->toAtomString(), 'dateModified' => $post->updated_at?->toAtomString(), 'publisher' => ['@type' => 'Organization', 'name' => 'De Gouden Bol']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:structured>
    <section class="page-hero" aria-labelledby="h1">
        <div class="bgv bgv-sugar" aria-hidden="true" style="background-image:url(/site/assets/de9a2decc8dbca0f6e523df67380ff6cd81b1c8f.jpg)"></div>
        <div class="hero-vol" aria-hidden="true">NIEUWS</div>
        <div class="page-hero-inner">
            <span class="eyebrow">{{ DutchTime::format($post->published_at, 'D MMMM YYYY') }}</span>
            <h1 class="h1" id="h1">{{ $post->title }}</h1>
            @if ($post->excerpt)<p class="lead">{{ $post->excerpt }}</p>@endif
        </div>
    </section>
    <section class="sec" aria-label="Bericht"><div class="sec-inner"><div class="prose-sec" style="max-width:720px">
        {!! $post->body !!}
        <p style="margin-top:32px"><a href="{{ route('pers') }}#nieuws" class="btn-pill-out">&#8592; Alle berichten</a></p>
    </div></div></section>
</x-layouts.site>

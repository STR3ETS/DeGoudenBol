@php use App\Support\DutchTime; @endphp
<x-layouts.public :title="$post->meta_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt">
    <x-slot:head>
        <x-ui.json-ld :data="array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'publisher' => ['@type' => 'Organization', 'name' => 'De Gouden Bol'],
            'mainEntityOfPage' => route('nieuws.toon', $post),
        ])" />
    </x-slot:head>

    <article class="pt-36 pb-24">
        <div class="site-container max-w-[820px]">
            <nav class="mb-5 text-[0.7rem] font-bold tracking-[0.14em] text-gedempt uppercase" aria-label="Kruimelpad">
                <a href="{{ route('nieuws.index') }}" class="hover:text-goud-tekst">&larr; Nieuws</a>
            </nav>
            <x-ui.eyebrow>{{ DutchTime::format($post->published_at, 'D MMMM YYYY') }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-6">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="citaat mb-8 text-[1.2rem]">{{ $post->excerpt }}</p>
            @endif
            <div class="body-text prose-dgb flex flex-col gap-4">
                {!! $post->body !!}
            </div>
        </div>
    </article>
</x-layouts.public>

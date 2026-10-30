@php use App\Support\DutchTime; use Illuminate\Support\Carbon; @endphp
<x-layouts.site title="Blog" description="Alles over oliebollen: wat een goede oliebol maakt, hoe je ze bakt en bewaart, en hoe De Gouden Bol test." main-class="page-sec">
    <section class="page-hero" aria-labelledby="h1">
        <div class="bgv bgv-sugar" aria-hidden="true" style="background-image:url(/site/assets/de9a2decc8dbca0f6e523df67380ff6cd81b1c8f.jpg)"></div>
        <div class="hero-vol" aria-hidden="true">BLOG</div>
        <div class="page-hero-inner">
            <span class="eyebrow">Blog</span>
            <h1 class="h1" id="h1">Alles over <em>de oliebol</em></h1>
            <p class="lead">Wat een goede oliebol maakt, hoe je ze bakt en bewaart, en waar wij op letten bij de test.</p>
        </div>
    </section>
    <section class="sec" aria-label="Artikelen"><div class="sec-inner">
        @if ($artikelen->isEmpty())
            <div class="jury-slot" style="max-width:640px"><p class="body-text">Er staan nog geen artikelen. Binnenkort lees je hier alles over de oliebol.</p></div>
        @else
            <div style="display:grid;gap:24px;grid-template-columns:repeat(auto-fill,minmax(min(100%,320px),1fr))">
                @foreach ($artikelen as $artikel)
                    <article class="jury-slot rev" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
                        @if (! empty($artikel['featured_image']))
                            <a href="{{ route('blog.toon', $artikel['slug']) }}" tabindex="-1" aria-hidden="true"><img src="{{ $artikel['featured_image'] }}" alt="" loading="lazy" style="display:block;width:100%;aspect-ratio:16/10;object-fit:cover"></a>
                        @endif
                        <div style="padding:22px 24px 26px;display:flex;flex-direction:column;flex:1">
                            @if (! empty($artikel['published_at']))<span class="eyebrow">{{ DutchTime::format(Carbon::parse($artikel['published_at']), 'D MMMM YYYY') }}</span>@endif
                            <h2 style="margin:6px 0 8px;font-size:1.3rem;line-height:1.25"><a href="{{ route('blog.toon', $artikel['slug']) }}" style="color:inherit;text-decoration:none">{{ $artikel['title'] }}</a></h2>
                            @if (! empty($artikel['excerpt']))<p class="body-text">{{ $artikel['excerpt'] }}</p>@endif
                            <p style="margin-top:auto;padding-top:10px"><a href="{{ route('blog.toon', $artikel['slug']) }}" style="color:var(--gold);font-weight:700">Lees verder &rarr;</a></p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div></section>
</x-layouts.site>

@php use App\Support\DutchTime; @endphp
<x-layouts.public title="Nieuws" description="Nieuws en aankondigingen van De Gouden Bol.">
    <section class="pt-36 pb-12">
        <div class="site-container">
            <x-ui.eyebrow>Nieuws</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Het laatste <em>nieuws</em></h1>
        </div>
    </section>

    <section class="pb-24">
        <div class="site-container">
            @if ($posts->isEmpty())
                <x-ui.empty-state titel="Nog geen berichten" tekst="Zodra er nieuws is over de editie, verschijnt het hier." />
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <a href="{{ route('nieuws.toon', $post) }}" class="card card--hover block no-underline">
                            <div class="mb-3 text-[0.72rem] font-bold tracking-[0.12em] text-gedempt uppercase">{{ DutchTime::format($post->published_at, 'D MMMM YYYY') }}</div>
                            <h2 class="mb-3 font-kop text-kaarttitel font-semibold text-espresso">{{ $post->title }}</h2>
                            @if ($post->excerpt)
                                <p class="body-text text-[0.88rem]">{{ $post->excerpt }}</p>
                            @endif
                            <div class="mt-4 text-[0.78rem] font-bold text-goud-tekst">Lees verder &rarr;</div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-10">{{ $posts->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.public>

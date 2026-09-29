@php use App\Support\DutchTime; @endphp
<x-layouts.public :title="$title">
    <section class="pt-36 pb-20">
        <div class="site-container max-w-[820px]">
            <x-ui.eyebrow>Juridisch</x-ui.eyebrow>
            <h1 class="kop-pagina mb-6">{{ $title }}</h1>

            @if ($terms)
                <p class="mb-8 text-[0.8rem] text-gedempt">Versie {{ $terms->version }} &middot; gepubliceerd op {{ DutchTime::date($terms->published_at) }}</p>
                <div class="body-text prose-dgb flex flex-col gap-4 text-[0.95rem]">
                    {!! $terms->body !!}
                </div>
            @else
                <x-ui.empty-state titel="Nog niet gepubliceerd" tekst="De {{ strtolower($title) }} worden gepubliceerd vóór de opening van de inschrijving." />
            @endif
        </div>
    </section>
</x-layouts.public>

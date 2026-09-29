@php use App\Support\DutchTime; @endphp
<x-layouts.public :title="$release->title" :description="$embargoed ? 'Perskit onder embargo.' : 'Persbericht van De Gouden Bol: '.$release->title">
    @if ($embargoed)
        <x-slot:head><meta name="robots" content="noindex, nofollow"></x-slot:head>
    @endif
    <section class="relative overflow-hidden pt-36 pb-16">
        <div class="site-container max-w-[820px]">
            <nav class="mb-5 text-[0.7rem] font-bold tracking-[0.14em] text-gedempt uppercase" aria-label="Kruimelpad">
                <a href="{{ route('pers') }}" class="hover:text-goud-tekst">&larr; Pers &amp; media</a>
            </nav>
            @if ($embargoed)
                <div class="mb-6 rounded-kaart border-2 border-status-fout bg-status-fout-bg px-5 py-4 text-[0.9rem] font-bold text-status-fout" role="alert">
                    ONDER EMBARGO{{ $release->embargo_until ? ' TOT '.strtoupper(DutchTime::format($release->embargo_until, 'dddd D MMMM YYYY, HH:mm')).' UUR' : '' }}. Deze perskit is persoonlijk en vertrouwelijk; niet voor publicatie tot het embargo vervalt.
                </div>
            @endif
            <x-ui.eyebrow>{{ $release->scopeLabel() }} · {{ $release->milestone->getLabel() }}</x-ui.eyebrow>
            <h1 class="kop-pagina mb-6">{{ $release->title }}</h1>
            <x-ui.card class="!p-8">
                <div class="whitespace-pre-line text-[0.95rem] leading-relaxed text-espresso">{{ $release->body }}</div>
            </x-ui.card>
            <div class="mt-6 flex flex-wrap items-center gap-3 text-[0.8rem] text-gedempt">
                @if ($release->isPublished())
                    <span>Gepubliceerd {{ DutchTime::format($release->published_at, 'D MMMM YYYY, HH:mm') }}</span>
                    @if ($release->province)
                        &middot; <a href="{{ route('provincies.show', $release->province) }}" class="font-bold text-goud-tekst">Voorlijst {{ $release->province->name }}</a>
                    @else
                        &middot; <a href="{{ route('finale') }}" class="font-bold text-goud-tekst">Landelijke finale</a>
                    @endif
                @else
                    <span>Beeld en badges komen hier beschikbaar zodra het embargo is vervallen.</span>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>

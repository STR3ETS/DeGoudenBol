<x-layouts.public :title="$title" description="Nieuwsbrief van De Gouden Bol.">
    <x-slot:head><meta name="robots" content="noindex, nofollow"></x-slot:head>
    <section class="relative overflow-hidden pt-36 pb-24">
        <div class="site-container max-w-[640px]">
            <x-ui.eyebrow>Nieuwsbrief</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">{{ $title }}</h1>
            <p class="body-text mb-8">{{ $text }}</p>
            <x-ui.button :href="route('home')">Naar de homepage</x-ui.button>
        </div>
    </section>
</x-layouts.public>

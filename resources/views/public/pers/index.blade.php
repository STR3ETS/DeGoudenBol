@php use App\Support\DutchTime; @endphp
<x-layouts.public title="Pers en media" description="Persberichten van De Gouden Bol per provincie en landelijk, plus perscontact.">
    <section class="relative overflow-hidden pt-36 pb-14">
        <div class="site-container">
            <x-ui.eyebrow>Pers &amp; media</x-ui.eyebrow>
            <h1 class="kop-pagina mb-4">Pers<em>berichten</em></h1>
            <p class="body-text max-w-[620px]">Per provincie verschijnt op de publicatiedag een persbericht met de definitieve Top 10 en de provinciewinnaar; na de finale volgt de landelijke uitslag. Regionale media ontvangen de berichten vooraf onder embargo.</p>
        </div>
    </section>
    <section class="bg-zand py-16">
        <div class="site-container grid gap-10 lg:grid-cols-[2fr_1fr]">
            <div>
                @if ($releases->isEmpty())
                    <x-ui.empty-state titel="Nog geen persberichten" tekst="De eerste persberichten verschijnen op de publicatiedag." />
                @else
                    <ul class="flex flex-col gap-4">
                        @foreach ($releases as $release)
                            <li>
                                <a href="{{ route('pers.toon', $release) }}" class="card card--hover block no-underline">
                                    <div class="mb-1.5 text-[0.62rem] font-bold tracking-[0.2em] text-goud-tekst uppercase">{{ $release->scopeLabel() }} · {{ DutchTime::date($release->published_at) }}</div>
                                    <div class="font-kop text-kaarttitel font-semibold text-espresso">{{ $release->title }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <aside>
                <x-ui.card class="!p-7">
                    <h2 class="font-kop mb-3 text-[1.15rem] font-semibold">Perscontact</h2>
                    <p class="text-[0.9rem] leading-relaxed text-gedempt">{{ config('press.contact_name') }}<br><a href="mailto:{{ config('press.contact_email') }}" class="font-bold text-goud-tekst">{{ config('press.contact_email') }}</a><br>{{ config('press.contact_phone') }}</p>
                    <p class="mt-4 text-[0.8rem] leading-relaxed text-gedempt">Beeld en badges per deelnemer staan op de bakkersprofielen; iedere erkenning is te controleren via <a href="{{ route('erkenning.zoek') }}" class="font-bold text-goud-tekst">de verificatiepagina</a>.</p>
                </x-ui.card>
            </aside>
        </div>
    </section>
</x-layouts.public>

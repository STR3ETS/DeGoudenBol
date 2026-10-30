@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;
    use App\Support\Portal\PersonalReport;

    $publication = $edition?->main_publication_at;
    $rankingPublic = $item !== null;
@endphp
<x-layouts.portal title="Rapport en uitslag">
    <header class="ui-app__kop">
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Je rapport en <em>de uitslag</em></h1>
            <p class="ui-app__sub">{{ $company->name }}{{ $edition ? ' · De Gouden Bol '.$edition->year : '' }}</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="ui-statusregel ui-statusregel--fout ui-app__blok" role="alert"><span class="ui-icoonvak ui-icoonvak--fout ui-icoonvak--rond"><x-ui.icoon naam="waarschuwing" dikte="2.4" /></span><div>{{ $errors->first() }}</div></div>
    @endif

    @if ($entry === null)
        <div class="ui-kaart"><div class="ui-kaart__inhoud"><div class="ui-klaar !bg-zand"><div class="ui-klaar__titel">Geen inschrijving voor deze editie</div></div></div></div>
    @else
        <div class="ui-app__blok grid gap-6 xl:grid-cols-2">
            {{-- Blok 1: persoonlijk rapport, binnen enkele dagen na de keuring --}}
            <section class="ui-kaart" aria-labelledby="rapport-titel">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="document" /></span><div><div id="rapport-titel" class="ui-kaart__titel">Persoonlijk rapport</div><div class="ui-kaart__sub">Alleen voor jou · binnen {{ $edition?->settings->reportDueDays ?? 3 }} dagen na de keuring</div></div>@if ($report)<div class="ui-kaart__acties"><span class="ui-chip ui-chip--klein ui-chip--succes">Beschikbaar</span></div>@endif</div>
                <div class="ui-kaart__inhoud !pt-2">
                    @if ($report)
                        <div class="mb-4 flex items-end gap-3">
                            <span class="font-kop text-[3.2rem] leading-none font-bold text-espresso">{{ PersonalReport::number($report->totalPoints) }}</span>
                            <span class="mb-1 text-[0.9rem] text-gedempt">van de 100 punten</span>
                        </div>
                        <ul class="ui-lijst mb-5">
                            @foreach ($report->criteria as $criterion)
                                <li class="ui-rij"><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $criterion['name'] }}</span></span><span class="ui-rij__meta"><strong class="text-espresso">{{ PersonalReport::number($criterion['score']) }}</strong> / {{ $criterion['max'] }}</span></li>
                            @endforeach
                        </ul>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('portaal.rapport', $company) }}" class="ui-knop">Open het rapport <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                            <a href="{{ route('portaal.rapport', $company) }}" class="ui-knop ui-knop--ghost"><x-ui.icoon naam="download" /> Download als pdf</a>
                        </div>
                        <p class="mt-4 text-[0.8rem] text-gedempt">Je mag je eigen uitslag commercieel gebruiken, zolang je geen misleidende claims maakt.</p>
                    @elseif ($entry->confidentialReport?->due_at)
                        <p class="text-[0.9rem]">Het panel heeft geproefd. De redactie rondt je rapport af; het staat hier uiterlijk <strong>{{ DutchTime::format($entry->confidentialReport->due_at, 'dddd D MMMM') }}</strong>. Je krijgt er een e-mail over.</p>
                    @else
                        <p class="text-[0.9rem] text-gedempt">Na de keuring staat hier je rapport met de score per onderdeel, je totaalscore en de toelichting van het panel.</p>
                    @endif
                </div>
            </section>

            {{-- Blok 2: landelijke uitslag, pas op de officiële publicatiedatum --}}
            <section class="ui-kaart {{ $rankingPublic ? '' : 'ui-kaart--zand' }}" aria-labelledby="ranking-titel">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="ster" /></span><div><div id="ranking-titel" class="ui-kaart__titel">Landelijke uitslag</div><div class="ui-kaart__sub">{{ $publication ? ucfirst(DutchTime::format($publication, 'dddd D MMMM [om] HH:mm')) : 'Op de officiële publicatiedatum' }}</div></div></div>
                <div class="ui-kaart__inhoud !pt-2">
                    @if (! $rankingPublic)
                        <p class="text-[0.9rem]">De ranglijst blijft geheim tot de officiële publicatie. Tot die tijd ziet niemand de cijfers of posities van anderen, ook jij niet.</p>
                        @if ($publication && $publication->isFuture())
                            @php $dagen = (int) now()->startOfDay()->diffInDays(DutchTime::display($publication)->startOfDay(), false); @endphp
                            <p class="mt-3 text-[0.9rem]">Nog <strong>{{ $dagen }} {{ $dagen === 1 ? 'dag' : 'dagen' }}</strong>.</p>
                        @endif
                    @elseif ($item->isPublic())
                        <div class="flex flex-wrap items-end gap-4">
                            <span class="font-kop text-[3.2rem] leading-none font-bold text-espresso">{{ number_format($item->total(), 1, ',', '.') }}</span>
                            <span class="ui-chip ui-chip--succes mb-2">Officieel getest</span>
                        </div>
                        @if ($position)
                            <p class="mt-4 text-[1.05rem] font-semibold">Plaats {{ $position->position }} in {{ $entry->province->name }}</p>
                            <p class="mt-1 text-[0.85rem] text-gedempt">{{ $position->label->getLabel() }} ten opzichte van de vorige publicatie. <a href="{{ route('provincies.show', $entry->province) }}" class="font-bold text-goud-tekst underline">Bekijk de ranglijst</a></p>
                        @endif
                        <p class="mt-3 text-[0.8rem] text-gedempt">Gepubliceerd op {{ DutchTime::format($entry->published_at, 'D MMMM YYYY, HH:mm') }}.</p>
                    @else
                        <p class="text-[0.9rem]">Je cijfer ligt onder de publicatiedrempel van {{ number_format($edition->settings->publishThreshold, 1, ',', '.') }} en komt niet op de site. Niemand buiten je bedrijf ziet dit resultaat.</p>
                    @endif
                </div>
            </section>
        </div>

        @if ($finalist)
            <section class="ui-kaart ui-kaart--espresso ui-app__blok">
                <div class="ui-kaart__inhoud">
                    <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="ster" /> Landelijke finale</div>
                    @if ($finalist->status->participates())
                        <h2 class="ui-vandaag__titel">{{ $finalist->originLabel() }} van {{ $entry->province->name }}: <em>uitgenodigd voor de finale</em></h2>
                        <p class="ui-vandaag__tekst">Een nieuwe, blinde beoordeling{{ $edition?->final_test_day ? ' op '.DutchTime::date($edition->final_test_day) : '' }}; de provinciale score telt niet mee. Tot de publicatiedag is deze uitnodiging vertrouwelijk.</p>
                        @error('keuze')<p class="field-error mt-3">{{ $message }}</p>@enderror
                        <div class="ui-vandaag__acties">
                            @if ($finalist->status->value === 'invited')
                                <form method="post" action="{{ route('portaal.finale.reageren', $company) }}">@csrf<input type="hidden" name="keuze" value="bevestigen"><button type="submit" class="ui-knop">Deelname bevestigen</button></form>
                            @else
                                <span class="ui-chip ui-chip--succes">Deelname bevestigd</span>
                            @endif
                            <form method="post" action="{{ route('portaal.finale.reageren', $company) }}" onsubmit="return confirm('Afmelden voor de finale? De plek gaat naar de volgende op de lijst; je provinciale titel blijft.');">@csrf<input type="hidden" name="keuze" value="afmelden"><button type="submit" class="ui-knop ui-knop--ghost">Afmelden</button></form>
                        </div>
                    @else
                        <h2 class="ui-vandaag__titel">Afgemeld voor de finale</h2>
                        <p class="ui-vandaag__tekst">De provinciale titel blijft van jou.</p>
                    @endif
                </div>
            </section>
        @endif

        @if ($entry->published_at)
            <section class="ui-kaart ui-app__blok">
                <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="info" /></span><div><div class="ui-kaart__titel">Bezwaar</div><div class="ui-kaart__sub">Alleen over de procedure, binnen drie werkdagen na publicatie{{ $objectionDeadline ? ' (tot '.DutchTime::format($objectionDeadline, 'D MMMM, HH:mm').')' : '' }}</div></div></div>
                <div class="ui-kaart__inhoud !pt-2">
                    <p class="mb-4 text-[0.88rem] text-gedempt">Twee beslissers buiten het panel en de registratie beoordelen het; een hertest volgt alleen bij een erkende fout.</p>
                    @foreach ($objections as $objection)
                        <div class="mb-3 rounded-veld border border-rand p-4 text-[0.9rem]">
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <span class="text-gedempt">{{ DutchTime::format($objection->submitted_at, 'D MMM YYYY, HH:mm') }}</span>
                                <span class="ui-chip ui-chip--klein ui-chip--{{ $objection->status->isOpen() ? 'waarschuwing' : 'neutraal' }}">{{ $objection->status->getLabel() }}</span>
                            </div>
                            <p class="whitespace-pre-line">{{ $objection->reason }}</p>
                            @if ($objection->decision)
                                <p class="mt-2 border-t border-rand pt-2 text-gedempt"><strong>Beslissing:</strong> {{ $objection->decision }}</p>
                            @endif
                        </div>
                    @endforeach

                    @if ($objectionDeadline && $objectionDeadline->isFuture() && $objections->filter(fn ($o) => $o->status->isOpen())->isEmpty())
                        <form method="post" action="{{ route('portaal.bezwaar', $company) }}" class="grid gap-3">
                            @csrf
                            <div class="ui-veld"><label for="reason" class="ui-label">Toelichting op de procedurele fout</label><textarea id="reason" name="reason" rows="4" class="ui-invoer" required minlength="20">{{ old('reason') }}</textarea></div>
                            <div><button type="submit" class="ui-knop ui-knop--omlijnd">Bezwaar indienen</button></div>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    @endif
</x-layouts.portal>

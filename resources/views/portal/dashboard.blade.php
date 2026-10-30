@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Support\DutchTime;

    $first = $companies->first();
    $settings = $edition?->settings;
    $vragen = [
        ['Wat neem ik mee?', ($settings->piecesPerEntry ?? 20).' oliebollen, gebakken op de dag zelf, in een doos of zak zonder naam of logo. De deelnemercode op je aanleverbewijs is genoeg: wij plakken hem erop.'],
        ['Kan ik mijn aanlevermoment nog wijzigen?', 'Ja, tot '.($settings->changeDeadlineHours ?? 24).' uur vooraf. Open Aanleveren en kies een ander moment of een andere vrijdag.'],
        ['Kunnen jullie mijn oliebollen ophalen?', $settings?->pickupEnabled ? 'Ja, alleen op vrijdag, voor '.App\Support\Money::format($settings->pickupFeeCents).' voorrijkosten (excl. btw). Je kiest de vrijdag onder Aanleveren en betaalt direct.' : 'Dit jaar niet; je brengt je oliebollen zelf op een tijdslot.'],
        ['Wanneer krijg ik mijn persoonlijke uitslag?', 'Binnen '.($settings->reportDueDays ?? 3).' dagen na de keuring staat je rapport hier, met de scores per onderdeel en de toelichting van het panel. Je krijgt er een e-mail over.'],
        ['Wanneer is de landelijke ranking?', ($edition?->main_publication_at ? ucfirst(DutchTime::format($edition->main_publication_at, 'dddd D MMMM [om] HH:mm')).', tegelijk met heel Nederland. ' : 'Op de officiële publicatiedatum. ').'Tot die tijd blijft de ranglijst geheim.'],
        ['Ziet het panel mijn naam?', 'Nee. Het panel ziet alleen een code, nooit je naam, logo of sponsors. De koppeling staat in een aparte, versleutelde kluis en iedere inzage wordt vastgelegd.'],
    ];
@endphp
<x-layouts.portal title="Mijn deelname">
    <header class="ui-app__kop">
        <x-ui.bol class="ui-bol--klein" stemming="blij" />
        <div class="ui-app__kop-tekst">
            <h1 class="ui-app__titel">Hoi, {{ $first?->name ?? $user->name }}</h1>
            <p class="ui-app__sub">
                @if ($first && $first->entries->first())
                    Je doet mee in {{ $first->entries->first()->province->name }}{{ $edition ? ' · De Gouden Bol '.$edition->year : '' }}
                @else
                    {{ $user->name }}{{ $edition ? ' · De Gouden Bol '.$edition->year : '' }}
                @endif
            </p>
        </div>
    </header>

    @forelse ($companies as $company)
        @php
            $entry = $company->entries->first();
            $journey = $journeys[$company->getKey()];
            $actie = $journey->nextAction();
            $status = $journey->statusLine();
            $stappen = $journey->steps();
            $klaar = $journey->stepsDone();
            $zijacties = $journey->sideActions();
        @endphp

        @if ($companies->count() > 1)
            <div class="ui-sectie mt-6"><h2 class="ui-sectie__titel">{{ $company->name }} <span class="ui-sectie__sub">{{ $company->type->getLabel() }}@if ($company->kvk_number) · KvK {{ $company->kvk_number }}@endif</span></h2></div>
        @endif

        <section class="ui-vandaag ui-app__blok" aria-labelledby="vandaag-{{ $company->getKey() }}">
            <div>
                <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> {{ $actie['eyebrow'] }}</div>
                <h2 id="vandaag-{{ $company->getKey() }}" class="ui-vandaag__titel">{{ $actie['titel'] }}@if ($actie['accent']) <em>{{ $actie['accent'] }}</em>@endif.</h2>
                <p class="ui-vandaag__tekst">{{ $actie['tekst'] }}</p>
                @if ($actie['knop'] || $actie['secundair'])
                    <div class="ui-vandaag__acties">
                        @if ($actie['knop'])<a href="{{ $actie['url'] }}" class="ui-knop ui-knop--groot">{{ $actie['knop'] }} <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>@endif
                        @if ($actie['secundair'])<a href="{{ $actie['secundair_url'] }}" class="ui-knop ui-knop--groot ui-knop--ghost">{{ $actie['secundair'] }}</a>@endif
                    </div>
                @endif
            </div>
            <div class="ui-vandaag__zij">
                <div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: {{ $klaar * 25 }}"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">{{ $klaar }}<span class="text-[1.1rem] text-room-zacht">/4</span></span><span class="ui-ring__label">stappen klaar</span></span></div>
                <span class="text-[0.78rem] text-room-zacht">{{ $klaar === 4 ? 'Helemaal klaar' : ($klaar === 0 ? 'Nog niet begonnen' : 'Je bent goed op weg') }}</span>
            </div>
        </section>

        @if ($status)
            <div class="ui-statusregel ui-statusregel--{{ $status['toon'] }} ui-app__blok">
                <span class="ui-icoonvak ui-icoonvak--{{ $status['toon'] }} ui-icoonvak--rond"><x-ui.icoon :naam="$status['icoon']" dikte="2.4" /></span>
                <div>{!! $status['tekst'] !!} <span class="sr-only">Status: {{ $entry->status->getLabel() }}</span></div>
            </div>
        @endif

        <div class="ui-app__blok grid gap-6 xl:grid-cols-[3fr_2fr]">
            <div class="flex flex-col gap-6">
                <div class="ui-kaart">
                    <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Zo werkt je deelname</div><div class="ui-kaart__sub">Vier stappen. Jij doet er twee, wij de rest.</div></div>@if ($entry)<div class="ui-kaart__acties"><span class="ui-chip ui-chip--klein ui-chip--{{ $entry->status === EntryStatus::PendingPayment ? 'waarschuwing' : ($entry->status->isPubliclyVisible() ? 'succes' : 'info') }}">{{ $entry->status->getLabel() }}</span></div>@endif</div>
                    <div class="ui-kaart__inhoud !pt-2">
                        <ol class="ui-stappenhulp">
                            @foreach ($stappen as $i => $stap)
                                <li class="ui-stappenhulp__stap ui-stappenhulp__stap--{{ $stap['staat'] }}">
                                    <span class="ui-stappenhulp__nr">@if ($stap['staat'] === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $i + 1 }}@endif</span>
                                    <div><span class="ui-stappenhulp__titel">{{ $stap['titel'] }}</span><p class="ui-stappenhulp__tekst">{{ $stap['tekst'] }}</p>@if ($stap['knop'] && $stap['url'])<a href="{{ $stap['url'] }}" class="ui-knop ui-knop--klein mt-3">{{ $stap['knop'] }}</a>@endif</div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                @if ($zijacties !== [])
                    <div class="ui-sectie !mb-0"><h2 class="ui-sectie__titel">Ook handig <span class="ui-sectie__sub">Niet verplicht, wel fijn.</span></h2></div>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($zijacties as $zij)
                            @php $tag = $zij['url'] ? 'a' : 'span'; @endphp
                            <{{ $tag }} @if ($zij['url']) href="{{ $zij['url'] }}" @else aria-disabled="true" @endif class="ui-actie {{ $zij['staat'] ? 'ui-actie--'.$zij['staat'] : '' }}"><span class="ui-icoonvak ui-icoonvak--{{ $zij['toon'] }}"><x-ui.icoon :naam="$zij['icoon']" /></span><span class="ui-actie__tekst"><span class="ui-actie__titel">{{ $zij['titel'] }}</span><span class="ui-actie__sub">{{ $zij['tekst'] }}</span></span><span class="ui-actie__pijl"><x-ui.icoon :naam="$zij['pijl']" dikte="2.4" /></span></{{ $tag }}>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-6">
                @if ($entry && $entry->publishedItem())
                    <section class="ui-feest">
                        <x-ui.bol stemming="trots" />
                        <div class="ui-feest__tekstvak">
                            <h2 class="ui-feest__titel">@if ($entry->publicTotal() !== null)Een <em>{{ number_format($entry->publicTotal(), 1, ',', '.') }}</em> voor je oliebollen @else Officieel <em>getest</em>@endif</h2>
                            <p class="ui-feest__tekst">{{ $entry->publicTotal() !== null ? 'Officieel getest door De Gouden Bol. Je badges staan klaar voor je raam, je website en je socials.' : 'Je resultaat is vertrouwelijk teruggekoppeld. Bekijk je rapport en het verbeteradvies.' }}</p>
                            <div class="ui-feest__acties"><a href="{{ route('portaal.uitslag', $company) }}" class="ui-knop ui-knop--klein">Bekijk je uitslag</a>@if (Route::has('portaal.marketing') && $entry->publicTotal() !== null)<a href="{{ route('portaal.marketing', $company) }}" class="ui-knop ui-knop--klein ui-knop--secundair"><x-ui.icoon naam="download" /> Badges</a>@endif</div>
                        </div>
                    </section>
                @elseif ($edition?->main_publication_at)
                    @if ($entry && $entry->status->occupiesPlace() && $entry->status !== EntryStatus::PendingPayment)
                        <div class="ui-kaart">
                            <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="document" /></span><div><div class="ui-kaart__titel">Persoonlijk rapport</div><div class="ui-kaart__sub">Alleen voor jou, binnen {{ $settings->reportDueDays }} dagen na de keuring</div></div></div>
                            <div class="ui-kaart__inhoud !pt-3 text-[0.88rem] leading-relaxed text-gedempt">
                                @if ($journey->reportAvailable())
                                    <p class="mb-3"><strong class="text-espresso">Je rapport staat klaar.</strong> Scores per onderdeel, eindscore en de toelichting van het panel.</p>
                                    <a href="{{ route('portaal.rapport', $company) }}" class="ui-knop ui-knop--klein">Bekijk je rapport</a>
                                @elseif ($entry->confidentialReport?->due_at)
                                    <p>Het panel heeft geproefd. Je rapport volgt uiterlijk <strong class="text-espresso">{{ DutchTime::format($entry->confidentialReport->due_at, 'dddd D MMMM') }}</strong>.</p>
                                @else
                                    <p>Na de keuring staat hier je rapport met de scores per onderdeel. Je krijgt er een e-mail over.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="ui-kaart">
                        <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="ster" /></span><div><div class="ui-kaart__titel">Landelijke uitslag</div><div class="ui-kaart__sub">{{ ucfirst(DutchTime::format($edition->main_publication_at, 'dddd D MMMM [om] HH:mm')) }}</div></div></div>
                        <div class="ui-kaart__inhoud !pt-3">
                            <div class="flex items-center gap-4">
                                <span class="ui-datumblok ui-datumblok--goud"><span class="ui-datumblok__dag">{{ DutchTime::format($edition->main_publication_at, 'dd') }}</span><span class="ui-datumblok__nr">{{ DutchTime::format($edition->main_publication_at, 'D') }}</span><span class="ui-datumblok__maand">{{ DutchTime::format($edition->main_publication_at, 'MMM') }}</span></span>
                                @php $dagen = (int) now()->startOfDay()->diffInDays(DutchTime::display($edition->main_publication_at)->startOfDay(), false); @endphp
                                <div class="text-[0.88rem] leading-relaxed text-gedempt">@if ($dagen > 1)Nog <strong class="text-espresso">{{ $dagen }} dagen</strong>. @elseif ($dagen === 1)<strong class="text-espresso">Morgen.</strong> @elseif ($dagen === 0)<strong class="text-espresso">Vandaag.</strong> @endif De ranglijst blijft geheim tot de officiële publicatie. Je krijgt een e-mail zodra hij er is.</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="ui-kaart">
                    <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="vraag" /></span><div><div class="ui-kaart__titel">Veelgestelde vragen</div><div class="ui-kaart__sub">Kort en in gewone taal</div></div></div>
                    <div class="ui-kaart__inhoud !pt-1">
                        @foreach ($vragen as [$vraag, $antwoord])
                            <details class="ui-vraag"><summary>{{ $vraag }} <x-ui.icoon naam="chevron-omlaag" dikte="2.4" /></summary><p>{{ $antwoord }}</p></details>
                        @endforeach
                    </div>
                </div>

                <div class="ui-hulpkaart">
                    <div class="ui-hulpkaart__kop"><x-ui.icoon naam="hart" class="h-5 w-5 text-goud-tekst" /> We helpen graag</div>
                    <p class="ui-hulpkaart__tekst">Kom je ergens niet uit? Mail ons, je krijgt op werkdagen dezelfde dag antwoord.</p>
                    <div class="mt-3 flex flex-wrap gap-2"><a href="mailto:{{ config('site.contact_email', config('mail.from.address')) }}" class="ui-knop ui-knop--klein ui-knop--secundair"><x-ui.icoon naam="mail" /> Mail ons</a>@if (Route::has('hoe-werkt-de-test'))<a href="{{ route('hoe-werkt-de-test') }}" class="ui-knop ui-knop--klein ui-knop--ghost">Hoe werkt de keuring</a>@endif</div>
                </div>
            </div>
        </div>
    @empty
        <div class="ui-kaart">
            <div class="ui-kaart__inhoud">
                <div class="ui-klaar !bg-zand"><x-ui.bol stemming="neutraal" class="ui-bol--klein" /><div class="ui-klaar__titel">Aan dit account is nog geen bedrijf gekoppeld</div><p class="ui-klaar__tekst">Meld een bedrijf aan of vraag de eigenaar om je als medewerker toe te voegen.</p>@if (Route::has('aanmelden'))<a href="{{ route('aanmelden') }}" class="ui-knop mt-3">Bedrijf aanmelden</a>@endif</div>
            </div>
        </div>
    @endforelse
</x-layouts.portal>

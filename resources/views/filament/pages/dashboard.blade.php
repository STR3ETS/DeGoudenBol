@php
    use App\Domain\Participants\Enums\EntryStatus;
    use App\Filament\Resources\Charities\CharityResource;
    use App\Filament\Resources\Editions\EditionResource;
    use App\Filament\Resources\Entries\EntryResource;
    use App\Support\DutchTime;
    use App\Support\Money;

    $data = $this->data();
    $edition = $data->edition();
    $phase = $data->season()->phase();
    $steps = $data->season()->steps();
    $work = $data->work()->items();
    $kpis = $data->kpis();
    $recent = $data->recentEntries(5);
    $provinces = $data->provinces();
    $milestones = $data->milestones();
    $nextMilestone = collect($milestones)->firstWhere('is_next', true);
    $links = $data->quickLinks();
    $trend = $data->registrationTrend();
    $finance = $data->finance();
    $canSeeEntries = EntryResource::canViewAny();

    // Het belangrijkste van vandaag: het eerste werkitem; de rest wordt een actiekaart.
    $top = $work[0] ?? null;
    $rest = array_slice($work, 1);
    $doneSteps = count(array_filter($steps, fn (array $step) => $step['state'] === 'done'));
    $busySteps = count(array_filter($steps, fn (array $step) => in_array($step['state'], ['current', 'running'], true)));
    $seasonPct = (int) round(($doneSteps + $busySteps / 2) / max(count($steps), 1) * 100);
    // Donkere tegels passen niet tussen de pasteltinten; "espresso" wordt een waarschuwingstint.
    $tileTone = fn (string $tone): string => $tone === 'espresso' ? 'waarschuwing' : $tone;

    $statusTone = fn (EntryStatus $status): string => match ($status) {
        EntryStatus::PendingPayment => 'waarschuwing',
        EntryStatus::Published => 'succes',
        EntryStatus::Withdrawn, EntryStatus::Cancelled => 'neutraal',
        EntryStatus::FreshnessExpired => 'fout',
        default => 'info',
    };
    $daysLabel = fn (array $milestone): string => match (true) {
        $milestone['is_past'] => 'geweest',
        $milestone['days'] === 0 => 'vandaag',
        $milestone['days'] === 1 => 'morgen',
        default => "over {$milestone['days']} dagen",
    };
    $stepState = fn (string $state): string => match ($state) {
        'done' => 'klaar',
        'current', 'running' => 'nu',
        default => 'later',
    };
@endphp
<x-filament-panels::page>
    <div class="dgb-dash ui-dash">

        {{-- Het belangrijkste van vandaag --}}
        <section class="ui-vandaag ui-app__blok" aria-labelledby="dash-vandaag">
            <div>
                @if ($top !== null)
                    <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> Het belangrijkste van vandaag</div>
                    <h2 id="dash-vandaag" class="ui-vandaag__titel"><em>{{ $top['title'] }}</em></h2>
                    <p class="ui-vandaag__tekst">{{ $top['text'] }}</p>
                    <div class="ui-vandaag__acties">
                        @if ($top['url'])
                            <a {{ \Filament\Support\generate_href_html($top['url']) }} class="ui-knop ui-knop--groot">{{ $top['action'] }} <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                        @endif
                        @if ($top['count'] !== null)
                            <span class="text-[0.82rem] text-room-zacht">{{ $top['count'] }} {{ $top['count'] === 1 ? 'item' : 'items' }}</span>
                        @endif
                    </div>
                @else
                    <div class="ui-vandaag__eyebrow"><x-ui.icoon naam="zon" /> {{ $phase['label'] }}</div>
                    <h2 id="dash-vandaag" class="ui-vandaag__titel">Je bent <em>helemaal bij</em>.</h2>
                    <p class="ui-vandaag__tekst">{{ $phase['note'] }} @if ($nextMilestone !== null) Het eerstvolgende moment is {{ lcfirst($nextMilestone['label']) }} op {{ $nextMilestone['date'] }}.@endif</p>
                @endif
            </div>
            <div class="ui-vandaag__zij">
                <div class="ui-ring ui-ring--donker ui-ring--groot" style="--ring-pct: {{ $seasonPct }}"><span class="ui-ring__tekst"><span class="ui-ring__cijfer">{{ $seasonPct }}%</span><span class="ui-ring__label">van het seizoen</span></span></div>
                <span class="text-[0.78rem] text-room-zacht">{{ $edition !== null ? 'Editie '.$edition->year.' · '.$phase['label'] : 'Geen actieve editie' }}</span>
            </div>
        </section>

        {{-- Jouw werk vandaag --}}
        <div class="ui-sectie">
            <h2 class="ui-sectie__titel">Jouw werk vandaag <span class="ui-sectie__sub">{{ $work === [] ? 'Er wacht niets op je.' : (count($work) === 1 ? 'Eén ding, en dat staat hierboven.' : count($work).' dingen. Begin bovenaan, de rest kan wachten.') }}</span></h2>
        </div>
        @if ($rest !== [])
            <div class="ui-app__blok grid gap-4 md:grid-cols-2">
                @foreach ($rest as $item)
                    @php $tag = $item['url'] ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($item['url']) {{ \Filament\Support\generate_href_html($item['url']) }} @endif class="ui-actie">
                        <span class="ui-icoonvak ui-icoonvak--{{ $item['tone'] }}"><x-filament::icon :icon="$item['icon']" /></span>
                        <span class="ui-actie__tekst"><span class="ui-actie__titel">{{ $item['title'] }}</span><span class="ui-actie__sub">{{ $item['text'] }}</span></span>
                        @if ($item['count'] !== null)<span class="ui-badge ui-badge--espresso">{{ $item['count'] }}</span>@endif
                        <span class="ui-actie__pijl" aria-hidden="true"><x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>
                    </{{ $tag }}>
                @endforeach
            </div>
        @elseif ($work === [])
            <div class="ui-app__blok ui-klaar"><x-ui.bol stemming="blij" class="ui-bol--klein" /><div class="ui-klaar__titel">Je bent helemaal bij</div><p class="ui-klaar__tekst">Zodra er iets te doen is, staat het hier met de knop erbij.</p></div>
        @else
            <div class="ui-app__blok"></div>
        @endif

        {{-- Kerncijfers --}}
        @if ($kpis !== [])
            <div class="ui-sectie"><h2 class="ui-sectie__titel">Hoe staat het ervoor <span class="ui-sectie__sub">De cijfers die je vandaag nodig hebt.</span></h2></div>
            <div class="ui-app__blok grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    @php $tag = $kpi['url'] ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($kpi['url']) {{ \Filament\Support\generate_href_html($kpi['url']) }} @endif class="ui-tegel ui-tegel--{{ $tileTone($kpi['tone']) }}">
                        <div class="ui-tegel__kop"><span class="ui-icoonvak ui-icoonvak--{{ $tileTone($kpi['tone']) }}"><x-filament::icon :icon="$kpi['icon']" /></span>{{ $kpi['label'] }}</div>
                        <div class="ui-tegel__cijfer">{{ $kpi['value'] }}</div>
                        <div class="ui-tegel__voet">
                            @if ($kpi['url'])<span class="ui-tegel__link">Bekijk alle <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></span>@endif
                            <span>{{ $kpi['hint'] }}</span>
                        </div>
                        <span class="ui-tegel__watermerk" aria-hidden="true"><x-filament::icon :icon="$kpi['icon']" /></span>
                    </{{ $tag }}>
                @endforeach
            </div>
        @endif

        <div class="ui-app__blok grid gap-6 xl:grid-cols-[3fr_2fr]">
            {{-- Seizoen in negen stappen --}}
            <div class="ui-kaart dgb-card">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="vlag" /></span><div><div class="ui-kaart__titel">Waar we zijn in het seizoen</div><div class="ui-kaart__sub">{{ $phase['note'] }}</div></div>@if ($edition !== null)<div class="ui-kaart__acties"><span class="ui-chip ui-chip--goud ui-chip--klein">Editie {{ $edition->year }}</span></div>@endif</div>
                <div class="ui-kaart__inhoud !pt-2">
                    <ol class="ui-stappenhulp ui-stappenhulp--compact">
                        @foreach ($steps as $step)
                            @php $state = $stepState($step['state']); $tag = $step['url'] ? 'a' : 'span'; @endphp
                            <li class="ui-stappenhulp__stap ui-stappenhulp__stap--{{ $state }}">
                                <span class="ui-stappenhulp__nr">@if ($state === 'klaar')<x-ui.icoon naam="vink" dikte="3" />@else{{ $step['n'] }}@endif</span>
                                <div class="flex flex-wrap items-baseline gap-x-3">
                                    <{{ $tag }} @if ($step['url']) {{ \Filament\Support\generate_href_html($step['url']) }} @endif class="ui-stappenhulp__titel no-underline">{{ $step['label'] }}</{{ $tag }}>
                                    <span class="ui-stappenhulp__tekst !mt-0">
                                        @if ($step['note']) {{ $step['note'] }}
                                        @elseif ($step['count'] !== null) {{ $step['count'] }} {{ $step['count'] === 1 ? 'inschrijving' : 'inschrijvingen' }}
                                        @endif
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                {{-- Belangrijke data --}}
                <div class="ui-kaart dgb-card">
                    <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--espresso"><x-ui.icoon naam="kalender" /></span><div><div class="ui-kaart__titel">Belangrijke data</div><div class="ui-kaart__sub">Wat eraan komt</div></div>@if ($edition !== null && EditionResource::canViewAny())<div class="ui-kaart__acties"><a {{ \Filament\Support\generate_href_html(EditionResource::getUrl('edit', ['record' => $edition])) }} class="ui-sectie__link">Aanpassen</a></div>@endif</div>
                    <div class="ui-kaart__inhoud !pt-3"><div class="ui-lijst">
                        @forelse ($milestones as $milestone)
                            @if (! $milestone['is_past'] || $loop->remaining < 3)
                                <div class="ui-rij {{ $milestone['is_next'] ? 'ui-rij--goud' : '' }} {{ $milestone['is_past'] ? 'opacity-50' : '' }}"><span class="ui-datumblok {{ $milestone['is_next'] ? 'ui-datumblok--goud' : '' }}"><span class="ui-datumblok__dag">{{ $milestone['weekday'] }}</span><span class="ui-datumblok__nr">{{ $milestone['day'] }}</span><span class="ui-datumblok__maand">{{ $milestone['month'] }}</span></span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $milestone['label'] }}</span><span class="ui-rij__sub">{{ $milestone['time'] ? "om {$milestone['time']} uur" : 'hele dag' }}</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein {{ $milestone['is_next'] ? 'ui-chip--goud' : '' }}">{{ $daysLabel($milestone) }}</span></span></div>
                            @endif
                        @empty
                            <p class="text-[0.82rem] text-gedempt">Geen editie-datums ingesteld.</p>
                        @endforelse
                    </div></div>
                </div>

                <div class="ui-hulpkaart">
                    <div class="ui-hulpkaart__kop"><x-ui.icoon naam="vraag" class="h-5 w-5 text-goud-tekst" /> Hulp nodig?</div>
                    <p class="ui-hulpkaart__tekst">Elke pagina legt bovenaan uit wat je er doet. Kom je er niet uit, dan belt of mailt Eazyonline je terug op dezelfde werkdag.</p>
                    <a href="{{ route('docs') }}" class="ui-hulpkaart__link">Open de handleiding <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
                </div>
            </div>
        </div>

        {{-- Financiën per inkomstenstroom --}}
        @if ($finance)
            <div class="ui-sectie"><h2 class="ui-sectie__titel">Geld <span class="ui-sectie__sub">Betaald exclusief btw, wat nog openstaat en de {{ $finance['charity_percentage'] }}% voor goede doelen.</span></h2>@if (CharityResource::canViewAny())<a {{ \Filament\Support\generate_href_html(CharityResource::getUrl('index')) }} class="ui-sectie__link">Goede doelen <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>@endif</div>
            <div class="ui-app__blok grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($finance['streams'] as $stream)
                    <a {{ \Filament\Support\generate_href_html($stream['url']) }} class="ui-tegel ui-tegel--mini ui-tegel--{{ $loop->first ? 'succes' : 'info' }}"><div class="ui-label">{{ $stream['label'] }}</div><div class="ui-tegel__cijfer">{{ Money::format($stream['paid_cents']) }}</div><div class="ui-tegel__voet">{{ $stream['open_count'] > 0 ? $stream['open_count'].' open · '.Money::format($stream['open_cents']) : 'niets openstaand' }}</div></a>
                @endforeach
                <div class="ui-tegel ui-tegel--mini ui-tegel--goud"><div class="ui-label">Gereserveerd goede doelen</div><div class="ui-tegel__cijfer">{{ Money::format($finance['charity_reserved_cents']) }}</div><div class="ui-tegel__voet">{{ Money::format($finance['charity_pot_cents']) }} in de regionale potten</div></div>
                <div class="ui-tegel ui-tegel--mini ui-tegel--{{ $finance['charity_open_cents'] > 0 ? 'waarschuwing' : 'neutraal' }}"><div class="ui-label">Nog uit te betalen</div><div class="ui-tegel__cijfer">{{ Money::format($finance['charity_open_cents']) }}</div><div class="ui-tegel__voet">{{ Money::format($finance['charity_paid_cents']) }} al uitbetaald</div></div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Recente aanmeldingen --}}
            @if ($canSeeEntries && $edition !== null)
                <div class="ui-kaart dgb-card">
                    <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--info"><x-ui.icoon naam="klembord" /></span><div><div class="ui-kaart__titel">Nieuwe aanmeldingen</div><div class="ui-kaart__sub">{{ $trend['total'] }} in {{ count($trend['days']) }} dagen</div></div><div class="ui-kaart__acties"><a {{ \Filament\Support\generate_href_html(EntryResource::getUrl('index')) }} class="ui-sectie__link">Alle bakkers</a></div></div>
                    <div class="ui-kaart__inhoud !pt-3">
                        @if ($trend['days'] !== [])
                            <div class="ui-staven mb-4" aria-label="Aanmeldingen per dag, laatste {{ count($trend['days']) }} dagen">
                                @foreach ($trend['days'] as $day)
                                    <span class="ui-staven__staaf {{ $day['is_today'] ? 'ui-staven__staaf--vandaag' : '' }}" title="{{ $day['label'] }}: {{ $day['count'] }}"><span class="ui-staven__vulling" style="height: {{ $day['count'] > 0 ? max(10, (int) round($day['count'] / $trend['max'] * 100)) : 0 }}%"></span></span>
                                @endforeach
                            </div>
                        @endif
                        <div class="ui-lijst">
                            @forelse ($recent as $entry)
                                <a {{ \Filament\Support\generate_href_html(EntryResource::getUrl('index', ['tableSearch' => $entry->public_name])) }} class="ui-rij ui-rij--klik"><span class="ui-avatar {{ $loop->odd ? '' : 'ui-avatar--espresso' }}">{{ strtoupper(mb_substr($entry->public_name, 0, 1)) }}</span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $entry->public_name }}</span><span class="ui-rij__sub">{{ $entry->province->name }} · {{ $entry->registered_at ? DutchTime::display($entry->registered_at)->locale('nl')->diffForHumans() : '' }}</span></span><span class="ui-rij__meta"><span class="ui-chip ui-chip--klein ui-chip--{{ $statusTone($entry->status) }}">{{ $entry->status->getLabel() }}</span></span></a>
                            @empty
                                <p class="text-[0.82rem] text-gedempt">Nog geen aanmeldingen. Zodra de inschrijving open is verschijnen ze hier.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- Plekken per provincie --}}
            <div class="ui-kaart dgb-card">
                <div class="ui-kaart__kop"><span class="ui-icoonvak"><x-ui.icoon naam="kaart" /></span><div><div class="ui-kaart__titel">Plekken per provincie</div><div class="ui-kaart__sub">Rood betekent vol: daar kan niemand meer bij.</div></div></div>
                <div class="ui-kaart__inhoud !pt-3 flex flex-col gap-3">
                    @forelse ($provinces as $province)
                        <div><div class="mb-1 flex justify-between text-[0.78rem] font-bold"><span>{{ $province['name'] }}</span><span class="text-gedempt">{{ $province['taken'] }} van {{ $province['capacity'] }}</span></div><div class="ui-balk"><div class="ui-balk__vulling {{ $province['available'] === 0 ? '!bg-status-fout' : ($province['percentage'] >= 80 ? '!bg-status-waarschuwing' : '') }}" style="width: {{ max(2, $province['percentage']) }}%"></div></div></div>
                    @empty
                        <p class="text-[0.82rem] text-gedempt">Geen provincies gekoppeld aan de editie.</p>
                    @endforelse
                </div>
            </div>

            {{-- Snel naar --}}
            <div class="ui-kaart dgb-card">
                <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--neutraal"><x-ui.icoon naam="lijst" /></span><div><div class="ui-kaart__titel">Snel naar</div><div class="ui-kaart__sub">Veelgebruikte onderdelen</div></div></div>
                <div class="ui-kaart__inhoud !pt-3"><div class="ui-lijst">
                    @foreach ($links as $link)
                        <a {{ \Filament\Support\generate_href_html($link['url']) }} class="ui-rij ui-rij--klik"><span class="ui-icoonvak"><x-filament::icon :icon="$link['icon']" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">{{ $link['label'] }}</span></span><span class="ui-rij__chevron"><x-ui.icoon naam="chevron-rechts" dikte="2.4" /></span></a>
                    @endforeach
                    <a href="{{ $data->publicSiteUrl() }}" target="_blank" rel="noopener" class="ui-rij ui-rij--klik ui-rij--goud"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="link" /></span><span class="ui-rij__tekst"><span class="ui-rij__titel">Publiekssite</span><span class="ui-rij__sub">Opent in een nieuw tabblad</span></span><span class="ui-rij__chevron"><x-ui.icoon naam="chevron-rechts" dikte="2.4" /></span></a>
                </div></div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

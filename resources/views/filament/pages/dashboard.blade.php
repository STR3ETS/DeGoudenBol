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

    $tone = fn (string $tone): string => match ($tone) {
        'goud' => 'bg-goud-licht text-goud-tekst',
        'succes' => 'bg-status-succes-bg text-status-succes',
        'waarschuwing' => 'bg-status-waarschuwing-bg text-status-waarschuwing',
        'fout' => 'bg-status-fout-bg text-status-fout',
        'info' => 'bg-status-info-bg text-status-info',
        'espresso' => 'bg-espresso text-goud',
        default => 'bg-status-neutraal-bg text-status-neutraal',
    };
    $statusTone = fn (EntryStatus $status): string => match ($status) {
        EntryStatus::PendingPayment => 'waarschuwing',
        EntryStatus::Published => 'succes',
        EntryStatus::Withdrawn, EntryStatus::Cancelled => 'neutraal',
        EntryStatus::FreshnessExpired => 'fout',
        default => 'info',
    };
    $daysLabel = fn (array $milestone): string => match (true) {
        $milestone['is_past'] => 'klaar',
        $milestone['days'] === 0 => 'vandaag',
        $milestone['days'] === 1 => 'morgen',
        default => "over {$milestone['days']} dgn",
    };
    $canSeeEntries = EntryResource::canViewAny();
@endphp
<x-filament-panels::page>
    <div class="dgb-dash flex flex-col gap-6">

        {{-- Seizoen: fase en de negen processtappen --}}
        <section class="dgb-card dgb-keten overflow-hidden">
            <div class="dgb-card-head">
                <span class="dgb-icoon bg-goud text-espresso"><x-filament::icon icon="heroicon-o-flag" /></span>
                <div class="min-w-0 flex-1">
                    <p class="dgb-card-title">{{ $phase['label'] }}</p>
                    <p class="dgb-card-sub">{{ $phase['note'] }}</p>
                </div>
                @if ($edition !== null)
                    <span class="dgb-pill bg-goud-licht text-goud-tekst">Editie {{ $edition->year }}</span>
                @endif
            </div>
            <ol class="dgb-keten__stappen" aria-label="Processtappen van aanmelding tot publicatie">
                @foreach ($steps as $step)
                    @php $tag = $step['url'] ? 'a' : 'span'; @endphp
                    <li class="dgb-keten__stap dgb-keten__stap--{{ $step['state'] }}">
                        <{{ $tag }} @if ($step['url']) {{ \Filament\Support\generate_href_html($step['url']) }} @endif class="dgb-keten__knop" title="Stap {{ $step['n'] }}: {{ $step['label'] }}">
                            <span class="dgb-keten__nr">
                                @if ($step['state'] === 'done')
                                    <x-filament::icon icon="heroicon-m-check" />
                                @else
                                    {{ $step['n'] }}
                                @endif
                            </span>
                            <span class="dgb-keten__label">{{ $step['label'] }}</span>
                            @if ($step['note'])
                                <span class="dgb-keten__note">{{ $step['note'] }}</span>
                            @else
                                <span class="dgb-keten__teller">{{ $step['count'] === null ? '–' : $step['count'] }}</span>
                            @endif
                        </{{ $tag }}>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Kerncijfers --}}
        @if ($kpis !== [])
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    @php $tag = $kpi['url'] ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($kpi['url']) {{ \Filament\Support\generate_href_html($kpi['url']) }} @endif class="dgb-tegel dgb-tegel--{{ $kpi['tone'] }}">
                        <span class="dgb-tegel__kop">
                            <span class="dgb-tegel__icoon"><x-filament::icon :icon="$kpi['icon']" /></span>
                            <span class="dgb-tegel__label">{{ $kpi['label'] }}</span>
                        </span>
                        <span class="dgb-tegel__cijfer">{{ $kpi['value'] }}</span>
                        <span class="dgb-tegel__voet">
                            @if ($kpi['url'])
                                <span class="dgb-tegel__link"><x-filament::icon icon="heroicon-m-arrow-right" /> Bekijk alle</span>
                            @endif
                            <span class="dgb-tegel__hint">{{ $kpi['hint'] }}</span>
                        </span>
                        <span class="dgb-tegel__watermerk" aria-hidden="true"><x-filament::icon :icon="$kpi['icon']" /></span>
                    </{{ $tag }}>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-5">
            {{-- Jouw werk vandaag --}}
            <section id="werk" class="dgb-card overflow-hidden xl:col-span-3">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-goud text-espresso"><x-filament::icon icon="heroicon-o-bolt" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Jouw werk vandaag</p>
                        <p class="dgb-card-sub">Wat nu op jou wacht, met de knop erbij</p>
                    </div>
                    @if ($work !== [])
                        <span class="dgb-count">{{ count($work) }}</span>
                    @endif
                </div>
                <div class="dgb-lijst">
                    @forelse ($work as $item)
                        @php $tag = $item['url'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if ($item['url']) {{ \Filament\Support\generate_href_html($item['url']) }} @endif class="dgb-row dgb-row--tint dgb-row--{{ $item['tone'] }}">
                            <span class="dgb-rij-icoon"><x-filament::icon :icon="$item['icon']" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="dgb-rij-titel">{{ $item['title'] }}</span>
                                <span class="dgb-rij-sub">{{ $item['text'] }}</span>
                            </span>
                            @if ($item['count'] !== null)
                                <span class="dgb-pill bg-wit text-espresso">{{ $item['count'] }}</span>
                            @endif
                            <span class="dgb-chip dgb-chip--goud dgb-chip--klein">{{ $item['action'] }}</span>
                        </{{ $tag }}>
                    @empty
                        <div class="dgb-empty">
                            <span class="dgb-icoon bg-status-succes-bg text-status-succes"><x-filament::icon icon="heroicon-o-check" /></span>
                            <p class="text-[0.86rem] font-extrabold text-espresso">Niets wacht op je</p>
                            <p class="text-[0.75rem] text-gedempt">Zodra er iets te doen is, staat het hier met de knop erbij.</p>
                        </div>
                    @endforelse
                </div>
                @if ($nextMilestone !== null)
                    <div class="dgb-notitie">
                        <span class="dgb-icoon"><x-filament::icon icon="heroicon-o-calendar-days" /></span>
                        <span class="min-w-0 flex-1 truncate">
                            {{ $nextMilestone['label'] }}
                            @if ($nextMilestone['days'] === 0) is vandaag
                            @elseif ($nextMilestone['days'] === 1) is morgen
                            @else over {{ $nextMilestone['days'] }} dagen
                            @endif
                            · {{ $nextMilestone['date'] }}
                        </span>
                    </div>
                @endif
            </section>

            {{-- Mijlpalen --}}
            <section class="dgb-card overflow-hidden xl:col-span-2">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-espresso text-goud"><x-filament::icon icon="heroicon-o-forward" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Mijlpalen {{ $edition?->year }}</p>
                        <p class="dgb-card-sub">Wat er komt</p>
                    </div>
                    @if ($edition !== null && EditionResource::canViewAny())
                        <a {{ \Filament\Support\generate_href_html(EditionResource::getUrl('edit', ['record' => $edition])) }} class="dgb-link">Instellen <x-filament::icon icon="heroicon-m-arrow-right" /></a>
                    @endif
                </div>
                <ol class="dgb-lijst">
                    @forelse ($milestones as $milestone)
                        <li class="dgb-row {{ $milestone['is_next'] ? 'dgb-row--tint dgb-row--goud' : '' }} {{ $milestone['is_past'] ? 'opacity-50' : '' }}">
                            <span class="dgb-datum {{ $milestone['is_next'] ? 'dgb-datum--goud' : '' }}">
                                <span class="dgb-datum__dag">{{ $milestone['weekday'] }}</span>
                                <span class="dgb-datum__nr">{{ $milestone['day'] }}</span>
                                <span class="dgb-datum__maand">{{ $milestone['month'] }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="dgb-rij-titel">{{ $milestone['label'] }}</span>
                                <span class="dgb-rij-sub">{{ $milestone['time'] ? "om {$milestone['time']} uur" : 'hele dag' }}</span>
                            </span>
                            <span class="dgb-pill {{ $milestone['is_next'] ? 'bg-goud text-espresso' : ($milestone['is_past'] ? 'bg-status-neutraal-bg text-status-neutraal' : 'bg-zand text-gedempt') }}">{{ $daysLabel($milestone) }}</span>
                        </li>
                    @empty
                        <li class="dgb-empty"><p class="text-[0.8rem] text-gedempt">Geen editie-datums ingesteld.</p></li>
                    @endforelse
                </ol>
            </section>
        </div>

        {{-- Financiën per inkomstenstroom --}}
        @if ($finance)
            <section class="dgb-card overflow-hidden">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-status-succes-bg text-status-succes"><x-filament::icon icon="heroicon-o-banknotes" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Financiën per inkomstenstroom</p>
                        <p class="dgb-card-sub">Betaald excl. btw · openstaand · {{ $finance['charity_percentage'] }}%-reservering goede doelen</p>
                    </div>
                    @if (CharityResource::canViewAny())
                        <a {{ \Filament\Support\generate_href_html(CharityResource::getUrl('index')) }} class="dgb-link">Goede doelen <x-filament::icon icon="heroicon-m-arrow-right" /></a>
                    @endif
                </div>
                <div class="grid gap-3 px-5 pb-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($finance['streams'] as $stream)
                        <a {{ \Filament\Support\generate_href_html($stream['url']) }} class="dgb-tegel dgb-tegel--mini dgb-tegel--{{ $loop->first ? 'succes' : 'info' }}">
                            <span class="dgb-tegel__label">{{ $stream['label'] }}</span>
                            <span class="dgb-tegel__cijfer">{{ Money::format($stream['paid_cents']) }}</span>
                            <span class="dgb-tegel__hint">{{ $stream['open_count'] > 0 ? $stream['open_count'].' open · '.Money::format($stream['open_cents']) : 'niets openstaand' }}</span>
                        </a>
                    @endforeach
                    <div class="dgb-tegel dgb-tegel--mini dgb-tegel--goud">
                        <span class="dgb-tegel__label">Gereserveerd goede doelen</span>
                        <span class="dgb-tegel__cijfer">{{ Money::format($finance['charity_reserved_cents']) }}</span>
                        <span class="dgb-tegel__hint">{{ Money::format($finance['charity_pot_cents']) }} in de regionale potten</span>
                    </div>
                    <div class="dgb-tegel dgb-tegel--mini dgb-tegel--{{ $finance['charity_open_cents'] > 0 ? 'waarschuwing' : 'neutraal' }}">
                        <span class="dgb-tegel__label">Nog uit te betalen</span>
                        <span class="dgb-tegel__cijfer">{{ Money::format($finance['charity_open_cents']) }}</span>
                        <span class="dgb-tegel__hint">{{ Money::format($finance['charity_paid_cents']) }} al uitbetaald</span>
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Recente aanmeldingen --}}
            @if ($canSeeEntries && $edition !== null)
                <section class="dgb-card overflow-hidden">
                    <div class="dgb-card-head">
                        <span class="dgb-icoon bg-status-info-bg text-status-info"><x-filament::icon icon="heroicon-o-clipboard-document-check" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="dgb-card-title">Recente aanmeldingen</p>
                            <p class="dgb-card-sub">{{ $trend['total'] }} in {{ count($trend['days']) }} dagen</p>
                        </div>
                        <a {{ \Filament\Support\generate_href_html(EntryResource::getUrl('index')) }} class="dgb-link">Alles <x-filament::icon icon="heroicon-m-arrow-right" /></a>
                    </div>
                    @if ($trend['days'] !== [])
                        <div class="dgb-trend" aria-label="Aanmeldingen per dag, laatste {{ count($trend['days']) }} dagen">
                            <div class="dgb-trend__staven">
                                @foreach ($trend['days'] as $day)
                                    <span class="dgb-trend__staaf {{ $day['is_today'] ? 'dgb-trend__staaf--vandaag' : '' }}" title="{{ $day['label'] }}: {{ $day['count'] }}">
                                        <span class="dgb-trend__vulling" style="height: {{ $day['count'] > 0 ? max(10, (int) round($day['count'] / $trend['max'] * 100)) : 0 }}%"></span>
                                    </span>
                                @endforeach
                            </div>
                            <div class="dgb-trend__onderschrift">
                                <span>{{ $trend['days'][0]['label'] }}</span>
                                <span>vandaag</span>
                            </div>
                        </div>
                    @endif
                    <div class="dgb-lijst">
                        @forelse ($recent as $entry)
                            <a {{ \Filament\Support\generate_href_html(EntryResource::getUrl('index', ['tableSearch' => $entry->public_name])) }} class="dgb-row">
                                <span class="dgb-avatar">{{ strtoupper(mb_substr($entry->public_name, 0, 1)) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="dgb-rij-titel">{{ $entry->public_name }}</span>
                                    <span class="dgb-rij-sub">{{ $entry->province->name }} · {{ $entry->registered_at ? DutchTime::display($entry->registered_at)->locale('nl')->diffForHumans() : '' }}</span>
                                </span>
                                <span class="dgb-pill {{ $tone($statusTone($entry->status)) }}">{{ $entry->status->getLabel() }}</span>
                            </a>
                        @empty
                            <div class="dgb-empty">
                                <p class="text-[0.86rem] font-extrabold text-espresso">Nog geen aanmeldingen</p>
                                <p class="text-[0.75rem] text-gedempt">Zodra de inschrijving open is verschijnen ze hier.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endif

            {{-- Plekken per provincie --}}
            <section class="dgb-card overflow-hidden">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-goud-licht text-goud-tekst"><x-filament::icon icon="heroicon-o-map" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Plekken per provincie</p>
                        <p class="dgb-card-sub">Bezet van beschikbaar</p>
                    </div>
                </div>
                <ul class="flex flex-col gap-2.5 px-5 pb-5">
                    @forelse ($provinces as $province)
                        <li>
                            <div class="mb-1 flex items-center justify-between text-[0.78rem]">
                                <span class="font-bold text-espresso">{{ $province['name'] }}</span>
                                <span class="tabular-nums text-gedempt">{{ $province['taken'] }} / {{ $province['capacity'] }}</span>
                            </div>
                            <div class="dgb-balk">
                                <div class="dgb-balk__vulling {{ $province['available'] === 0 ? 'dgb-balk__vulling--vol' : ($province['percentage'] >= 80 ? 'dgb-balk__vulling--bijna' : '') }}" style="width: {{ max(2, $province['percentage']) }}%"></div>
                            </div>
                        </li>
                    @empty
                        <li class="text-[0.8rem] text-gedempt">Geen provincies gekoppeld aan de editie.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Snel naar --}}
            <section class="dgb-card overflow-hidden">
                <div class="dgb-card-head">
                    <span class="dgb-icoon bg-zand text-espresso"><x-filament::icon icon="heroicon-o-squares-2x2" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="dgb-card-title">Snel naar</p>
                        <p class="dgb-card-sub">Veelgebruikte onderdelen</p>
                    </div>
                </div>
                <div class="dgb-lijst">
                    @foreach ($links as $link)
                        <a {{ \Filament\Support\generate_href_html($link['url']) }} class="dgb-row">
                            <span class="dgb-icoon bg-zand text-espresso"><x-filament::icon :icon="$link['icon']" /></span>
                            <span class="dgb-rij-titel min-w-0 flex-1">{{ $link['label'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="dgb-rij-chevron" />
                        </a>
                    @endforeach
                    <a href="{{ $data->publicSiteUrl() }}" target="_blank" rel="noopener" class="dgb-row dgb-row--tint dgb-row--goud">
                        <span class="dgb-rij-icoon"><x-filament::icon icon="heroicon-o-arrow-top-right-on-square" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="dgb-rij-titel">Publiekssite</span>
                            <span class="dgb-rij-sub">Opent in een nieuw tabblad</span>
                        </span>
                        <x-filament::icon icon="heroicon-m-chevron-right" class="dgb-rij-chevron" />
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>

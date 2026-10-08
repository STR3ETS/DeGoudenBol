@php
    use App\Domain\Testing\Enums\SessionStatus;
    use App\Filament\Pages\Intake;
    use App\Filament\Resources\TestSessions\TestSessionResource;
    use App\Support\DutchTime;

    $data = $this->data();
    $day = $data->day();
    $kpis = $data->kpis();
    $window = $data->window();
    $lanes = $data->lanes();
    $nowPosition = $data->nowPosition();
    $nextTestDay = $data->nextTestDay();
    $stateTone = fn (string $state): string => match ($state) {
        'verlopen' => 'bg-status-fout-bg text-status-fout',
        'bijna' => 'bg-status-waarschuwing-bg text-status-waarschuwing',
        'beoordeeld' => 'bg-status-succes-bg text-status-succes',
        default => 'bg-status-info-bg text-status-info',
    };
    $stateLabel = fn (array $row): string => match ($row['state']) {
        'verlopen' => 'verlopen',
        'beoordeeld' => 'beoordeeld',
        'bijna' => 'nog '.max(0, $row['minutes']).' min',
        default => $row['minutes'] === null ? 'vers' : 'nog '.intdiv($row['minutes'], 60).' u '.($row['minutes'] % 60).' min',
    };
    $sessionTone = fn (SessionStatus $status): string => match ($status) {
        SessionStatus::Running => 'goud',
        SessionStatus::Closed => 'succes',
        SessionStatus::Cancelled => 'neutraal',
        default => 'info',
    };
@endphp
<x-filament-panels::page>
    <div class="dgb-dash flex flex-col gap-6" @if ($data->isToday()) wire:poll.60s @endif>

        {{-- Dagnavigatie --}}
        <section class="dgb-card dgb-dagnav">
            <button type="button" class="dgb-dagnav__knop" wire:click="previousDay" title="Vorige dag" aria-label="Vorige dag">
                <x-filament::icon icon="heroicon-m-chevron-left" />
            </button>
            <div class="dgb-dagnav__midden">
                <p class="dgb-dagnav__titel">{{ ucfirst($day->locale('nl')->isoFormat('dddd D MMMM')) }}</p>
                <p class="dgb-dagnav__sub">
                    Week {{ $day->isoWeek() }}
                    @if ($data->isToday())
                        · vandaag
                    @else
                        · <button type="button" class="dgb-link" wire:click="today">Naar vandaag</button>
                    @endif
                    @if ($nextTestDay !== null && ! $nextTestDay->isSameDay($day))
                        · <button type="button" class="dgb-link" wire:click="goTo('{{ $nextTestDay->format('Y-m-d') }}')">Volgende testdag {{ $nextTestDay->locale('nl')->isoFormat('D MMM') }}</button>
                    @endif
                </p>
            </div>
            <button type="button" class="dgb-dagnav__knop" wire:click="nextDay" title="Volgende dag" aria-label="Volgende dag">
                <x-filament::icon icon="heroicon-m-chevron-right" />
            </button>
        </section>

        @if ($data->isEmpty())
            <section class="dgb-card">
                <div class="dgb-empty">
                    <span class="dgb-icoon bg-zand text-espresso"><x-filament::icon icon="heroicon-o-calendar" /></span>
                    <p class="text-[0.86rem] font-extrabold text-espresso">Geen testdag</p>
                    <p class="text-[0.75rem] text-gedempt">Op deze dag staan geen leveringen, sessies of monsters gepland.</p>
                    @if ($nextTestDay !== null)
                        <button type="button" class="dgb-chip dgb-chip--goud dgb-chip--klein mt-3" wire:click="goTo('{{ $nextTestDay->format('Y-m-d') }}')">Naar {{ $nextTestDay->locale('nl')->isoFormat('dddd D MMMM') }}</button>
                    @endif
                </div>
            </section>
        @else
            {{-- Kerncijfers van de dag --}}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if ($data->showsDeliveries())
                    @php $tag = $data->intakeUrl() ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($data->intakeUrl()) {{ \Filament\Support\generate_href_html($data->intakeUrl()) }} @endif class="dgb-tegel dgb-tegel--mini dgb-tegel--goud">
                        <span class="dgb-tegel__label">Leveringen</span>
                        <span class="dgb-tegel__cijfer">{{ $kpis['deliveries_received'] }} <span class="dgb-tegel__van">van {{ $kpis['deliveries_expected'] }}</span></span>
                        <span class="dgb-tegel__hint">ontvangen · {{ $data->slots()->count() }} {{ $data->slots()->count() === 1 ? 'slot' : 'slots' }}</span>
                    </{{ $tag }}>
                @endif
                @if ($data->showsSamples())
                    <div class="dgb-tegel dgb-tegel--mini dgb-tegel--info">
                        <span class="dgb-tegel__label">Monsters</span>
                        <span class="dgb-tegel__cijfer">{{ $kpis['samples'] }}</span>
                        <span class="dgb-tegel__hint">vandaag ontvangen en genummerd</span>
                    </div>
                @endif
                @if ($data->showsSessions())
                    <div class="dgb-tegel dgb-tegel--mini dgb-tegel--{{ $kpis['sessions_running'] > 0 ? 'goud' : 'espresso' }}">
                        <span class="dgb-tegel__label">Sessies</span>
                        <span class="dgb-tegel__cijfer">{{ $data->sessions()->count() }}</span>
                        <span class="dgb-tegel__hint">{{ $kpis['sessions_planned'] }} gepland · {{ $kpis['sessions_running'] }} bezig · {{ $kpis['sessions_closed'] }} klaar · {{ $kpis['panelists'] }} panelleden</span>
                    </div>
                    <div class="dgb-tegel dgb-tegel--mini dgb-tegel--{{ $kpis['cards_expected'] > 0 && $kpis['cards_submitted'] >= $kpis['cards_expected'] ? 'succes' : 'neutraal' }}">
                        <span class="dgb-tegel__label">Scorekaarten</span>
                        <span class="dgb-tegel__cijfer">{{ $kpis['cards_submitted'] }} <span class="dgb-tegel__van">van {{ $kpis['cards_expected'] }}</span></span>
                        <span class="dgb-tegel__hint">ingediend van het uitserveerschema</span>
                    </div>
                @endif
            </div>

            {{-- Tijdlijn --}}
            @if ($lanes !== [])
                <section class="dgb-card overflow-hidden">
                    <div class="dgb-card-head">
                        <span class="dgb-icoon bg-goud text-espresso"><x-filament::icon icon="heroicon-o-clock" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="dgb-card-title">Dagplanning</p>
                            <p class="dgb-card-sub">{{ $window['start']->format('H:i') }} tot {{ $window['end']->format('H:i') }} · klik op een blok om te openen</p>
                        </div>
                    </div>
                    <div class="dgb-tijdlijn">
                        <div class="dgb-tijdlijn__kop">
                            <div class="dgb-tijdlijn__label"></div>
                            <div class="dgb-tijdlijn__uren">
                                @foreach ($window['hours'] as $hour)
                                    <span class="dgb-tijdlijn__uur" style="width: {{ 100 / count($window['hours']) }}%">{{ $hour }}</span>
                                @endforeach
                            </div>
                        </div>
                        @foreach ($lanes as $lane)
                            <div class="dgb-tijdlijn__rij">
                                <div class="dgb-tijdlijn__label">
                                    <span class="dgb-rij-titel">{{ $lane['label'] }}</span>
                                    @if ($lane['sub'])
                                        <span class="dgb-rij-sub">{{ $lane['sub'] }}</span>
                                    @endif
                                </div>
                                <div class="dgb-tijdlijn__baan" style="--uren: {{ count($window['hours']) }}">
                                    @if ($nowPosition !== null)
                                        <span class="dgb-tijdlijn__nu" style="left: {{ $nowPosition }}%"></span>
                                    @endif
                                    @foreach ($lane['blocks'] as $block)
                                        @php $tag = $block['url'] ? 'a' : 'span'; @endphp
                                        <{{ $tag }} @if ($block['url']) {{ \Filament\Support\generate_href_html($block['url']) }} @endif class="dgb-blok dgb-blok--{{ $block['tone'] }}" style="left: {{ $block['left'] }}%; width: {{ $block['width'] }}%" title="{{ $block['title'] }} · {{ $block['sub'] }}">
                                            <span class="dgb-blok__titel">{{ $block['title'] }}</span>
                                            <span class="dgb-blok__sub">{{ $block['sub'] }}</span>
                                        </{{ $tag }}>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="grid gap-6 xl:grid-cols-2">
                {{-- Sessies met de volgende stap --}}
                @if ($data->showsSessions())
                    <section class="dgb-card overflow-hidden">
                        <div class="dgb-card-head">
                            <span class="dgb-icoon bg-status-info-bg text-status-info"><x-filament::icon icon="heroicon-o-beaker" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="dgb-card-title">Sessies</p>
                                <p class="dgb-card-sub">Schema, start en afsluiten vanuit de rij</p>
                            </div>
                            <a {{ \Filament\Support\generate_href_html(TestSessionResource::getUrl('index')) }} class="dgb-link">Alle sessies <x-filament::icon icon="heroicon-m-arrow-right" /></a>
                        </div>
                        <div class="dgb-lijst">
                            @forelse ($data->sessions() as $session)
                                @php $step = $this->nextStepFor($session); @endphp
                                <div class="dgb-row dgb-row--tint dgb-row--{{ $sessionTone($session->status) }}">
                                    <a {{ \Filament\Support\generate_href_html(TestSessionResource::getUrl('view', ['record' => $session])) }} class="flex min-w-0 flex-1 items-center gap-3 no-underline">
                                        <span class="dgb-rij-icoon"><x-filament::icon icon="heroicon-o-beaker" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="dgb-rij-titel">{{ $session->displayName() }} · {{ DutchTime::format($session->starts_at, 'HH:mm') }}</span>
                                            <span class="dgb-rij-sub">{{ $session->samples_count }} monsters · {{ $session->panelists_count }} panelleden · {{ $session->scorecards_count }} van {{ $session->assignments_count }} kaarten{{ $data->locationName($session->test_location_id) ? ' · '.$data->locationName($session->test_location_id) : '' }}</span>
                                        </span>
                                    </a>
                                    <span class="dgb-pill bg-wit text-espresso">{{ $session->status->getLabel() }}</span>
                                    @if ($step !== null)
                                        <button type="button" class="dgb-chip dgb-chip--goud dgb-chip--klein" wire:click="mountAction('{{ $step['action'] }}', { session: {{ $session->getKey() }} })">{{ $step['label'] }}</button>
                                    @endif
                                </div>
                            @empty
                                <div class="dgb-empty">
                                    <p class="text-[0.86rem] font-extrabold text-espresso">Geen sessies op deze dag</p>
                                    <p class="text-[0.75rem] text-gedempt">Plan een sessie bij Testsessies.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                {{-- Versheid --}}
                @if ($data->showsSamples())
                    <section class="dgb-card overflow-hidden">
                        <div class="dgb-card-head">
                            <span class="dgb-icoon bg-status-waarschuwing-bg text-status-waarschuwing"><x-filament::icon icon="heroicon-o-fire" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="dgb-card-title">Versheid</p>
                                <p class="dgb-card-sub">Monsters van deze dag, eerst wat het eerst verloopt</p>
                            </div>
                            @if ($data->samples()->isNotEmpty())
                                <span class="dgb-count">{{ $data->samples()->count() }}</span>
                            @endif
                        </div>
                        <div class="dgb-lijst">
                            @forelse ($data->samples() as $row)
                                <a {{ \Filament\Support\generate_href_html($row['url']) }} class="dgb-row">
                                    <span class="dgb-avatar">{{ $row['number'] }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="dgb-rij-titel">Monster {{ $row['number'] }}</span>
                                        <span class="dgb-rij-sub">
                                            @if ($row['received']) ontvangen {{ $row['received'] }} @endif
                                            @if ($row['expires']) · vers tot {{ $row['expires'] }} @endif
                                            @if ($row['sessions']) · {{ $row['sessions'] }} @endif
                                        </span>
                                    </span>
                                    <span class="dgb-pill {{ $stateTone($row['state']) }}">{{ $stateLabel($row) }}</span>
                                </a>
                            @empty
                                <div class="dgb-empty">
                                    <p class="text-[0.86rem] font-extrabold text-espresso">Nog geen monsters</p>
                                    <p class="text-[0.75rem] text-gedempt">Zodra ontvangst een monster registreert, loopt hier de versheidsklok.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                {{-- Leveringen per slot (alleen ontvangst) --}}
                @if ($data->showsDeliveries() && $data->slots()->isNotEmpty())
                    <section class="dgb-card overflow-hidden {{ $data->showsSessions() && $data->showsSamples() ? 'xl:col-span-2' : '' }}">
                        <div class="dgb-card-head">
                            <span class="dgb-icoon bg-goud-licht text-goud-tekst"><x-filament::icon icon="heroicon-o-truck" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="dgb-card-title">Leveringen per slot</p>
                                <p class="dgb-card-sub">Verwacht en ontvangen</p>
                            </div>
                            @if ($data->intakeUrl())
                                <a {{ \Filament\Support\generate_href_html($data->intakeUrl()) }} class="dgb-chip dgb-chip--goud dgb-chip--klein">Ontvangen</a>
                            @endif
                        </div>
                        <div class="dgb-lijst">
                            @foreach ($data->slots() as $slot)
                                @php $expected = (int) $slot->expected_count; $received = (int) $slot->received_count; @endphp
                                <div class="dgb-row dgb-row--tint dgb-row--{{ $expected === 0 ? ($received > 0 ? 'succes' : 'neutraal') : 'goud' }}">
                                    <span class="dgb-datum">
                                        <span class="dgb-datum__dag">van</span>
                                        <span class="dgb-datum__nr text-[1.1rem]">{{ DutchTime::format($slot->starts_at, 'HH:mm') }}</span>
                                        <span class="dgb-datum__maand">tot {{ DutchTime::format($slot->ends_at, 'HH:mm') }}</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="dgb-rij-titel">{{ $data->locationName($slot->test_location_id) ?? 'Aanleverslot' }}</span>
                                        <span class="dgb-rij-sub">{{ $received }} van {{ $expected + $received }} ontvangen · capaciteit {{ $slot->capacity }}</span>
                                    </span>
                                    <span class="dgb-pill bg-wit text-espresso">{{ $expected }} open</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

{{-- Publicatie-cockpit boven de batchpagina: vier stappen, goedkeuringen, volgende stap en blokkades. --}}
@php
    /** @var \App\Filament\Support\PublicationCockpit $cockpit */
    $steps = $cockpit->steps();
    $next = $cockpit->nextStep();
    $blockers = $cockpit->blockers();
    $approvals = $cockpit->approvals();
    $counts = $cockpit->counts();
@endphp
<div class="dgb-dash flex flex-col gap-6">
    <section class="dgb-card dgb-keten overflow-hidden">
        <div class="dgb-card-head">
            <span class="dgb-icoon bg-goud text-espresso"><x-filament::icon icon="heroicon-o-megaphone" /></span>
            <div class="min-w-0 flex-1">
                <p class="dgb-card-title">{{ $next['title'] }}</p>
                <p class="dgb-card-sub">{{ $next['text'] }}</p>
            </div>
            <span class="dgb-pill bg-goud-licht text-goud-tekst">{{ $counts['total'] }} {{ $counts['total'] === 1 ? 'uitslag' : 'uitslagen' }}</span>
        </div>
        <ol class="dgb-keten__stappen" aria-label="Stappen van de publicatiebatch">
            @foreach ($steps as $step)
                <li class="dgb-keten__stap dgb-keten__stap--{{ $step['state'] }}">
                    <span class="dgb-keten__knop">
                        <span class="dgb-keten__nr">
                            @if ($step['state'] === 'done')
                                <x-filament::icon icon="heroicon-m-check" />
                            @else
                                {{ $step['n'] }}
                            @endif
                        </span>
                        <span class="dgb-keten__label">{{ $step['label'] }}</span>
                        <span class="dgb-keten__teller">{{ $step['note'] ?? '–' }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="dgb-card overflow-hidden">
            <div class="dgb-card-head">
                <span class="dgb-icoon bg-status-succes-bg text-status-succes"><x-filament::icon icon="heroicon-o-check-badge" /></span>
                <div class="min-w-0 flex-1">
                    <p class="dgb-card-title">Vier ogen</p>
                    <p class="dgb-card-sub">Ingediend door {{ $cockpit->submitterName() ?? 'nog niemand' }} · de indiener keurt nooit zelf</p>
                </div>
                <span class="dgb-pill {{ $approvals->count() >= \App\Domain\Ranking\Models\PublicationBatch::REQUIRED_APPROVALS ? 'bg-status-succes-bg text-status-succes' : 'bg-zand text-gedempt' }}">{{ $approvals->count() }} van {{ \App\Domain\Ranking\Models\PublicationBatch::REQUIRED_APPROVALS }}</span>
            </div>
            <div class="dgb-lijst">
                @foreach ($approvals as $approval)
                    <div class="dgb-row dgb-row--tint dgb-row--succes">
                        <span class="dgb-rij-icoon"><x-filament::icon icon="heroicon-o-check" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="dgb-rij-titel">{{ $approval['name'] }}{{ $approval['is_me'] ? ' (jij)' : '' }}</span>
                            <span class="dgb-rij-sub">goedgekeurd {{ $approval['at'] }}</span>
                        </span>
                    </div>
                @endforeach
                @for ($i = $approvals->count(); $i < \App\Domain\Ranking\Models\PublicationBatch::REQUIRED_APPROVALS; $i++)
                    <div class="dgb-row dgb-row--tint dgb-row--neutraal">
                        <span class="dgb-rij-icoon"><x-filament::icon icon="heroicon-o-user" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="dgb-rij-titel">Nog een goedkeuring nodig</span>
                            <span class="dgb-rij-sub">van een publicatiemedewerker die niet de indiener is</span>
                        </span>
                    </div>
                @endfor
            </div>
        </section>

        <section class="dgb-card overflow-hidden">
            <div class="dgb-card-head">
                <span class="dgb-icoon {{ $blockers === [] ? 'bg-status-succes-bg text-status-succes' : 'bg-status-fout-bg text-status-fout' }}"><x-filament::icon :icon="$blockers === [] ? 'heroicon-o-shield-check' : 'heroicon-o-exclamation-triangle'" /></span>
                <div class="min-w-0 flex-1">
                    <p class="dgb-card-title">{{ $blockers === [] ? 'Niets blokkeert' : 'Let op' }}</p>
                    <p class="dgb-card-sub">{{ $counts['public'] }} openbaar · {{ $counts['confidential'] }} vertrouwelijk{{ $counts['provinces'] !== [] ? ' · '.implode(', ', $counts['provinces']) : '' }}</p>
                </div>
            </div>
            <div class="dgb-lijst">
                @forelse ($blockers as $blocker)
                    @php $tag = $blocker['url'] ? 'a' : 'div'; @endphp
                    <{{ $tag }} @if ($blocker['url']) {{ \Filament\Support\generate_href_html($blocker['url']) }} @endif class="dgb-row dgb-row--tint dgb-row--{{ $blocker['tone'] }}">
                        <span class="dgb-rij-icoon"><x-filament::icon icon="heroicon-o-exclamation-circle" /></span>
                        <span class="dgb-rij-titel min-w-0 flex-1 whitespace-normal">{{ $blocker['text'] }}</span>
                        @if ($blocker['url'])
                            <x-filament::icon icon="heroicon-m-chevron-right" class="dgb-rij-chevron" />
                        @endif
                    </{{ $tag }}>
                @empty
                    <div class="dgb-empty">
                        <p class="text-[0.86rem] font-extrabold text-espresso">Alles klopt</p>
                        <p class="text-[0.75rem] text-gedempt">Geen ingetrokken inschrijvingen of open bezwaren op deze uitslagen.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

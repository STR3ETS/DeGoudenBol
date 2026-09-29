@php
    use App\Domain\Testing\Enums\CorrectionStatus;
    use App\Support\DutchTime;

    $result = $sample->result;
    $flags = $result?->flags ?? [];
@endphp
<x-filament-panels::page>
    <div class="dgb-dash flex flex-col gap-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="dgb-card flex items-center gap-4 p-5">
                <span class="dgb-icoon bg-goud-licht text-goud-tekst"><x-filament::icon icon="heroicon-o-hashtag" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-kop text-[1.9rem] leading-none font-bold text-espresso">{{ $sample->label() }}</span>
                    <span class="mt-1 block text-[0.8rem] font-bold text-espresso">{{ $sample->round->getLabel() }}</span>
                    <span class="block text-[0.72rem] text-gedempt">{{ $sample->status->getLabel() }}</span>
                </span>
            </div>
            <div class="dgb-card flex items-center gap-4 p-5">
                <span class="dgb-icoon {{ $result === null ? 'bg-status-neutraal-bg text-status-neutraal' : ($result->isFinal() ? 'bg-status-succes-bg text-status-succes' : 'bg-status-waarschuwing-bg text-status-waarschuwing') }}"><x-filament::icon icon="heroicon-o-star" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-kop text-[1.9rem] leading-none font-bold text-espresso">{{ $result ? $result->formattedTotal() : '–' }}</span>
                    <span class="mt-1 block text-[0.8rem] font-bold text-espresso">{{ $result?->status->getLabel() ?? 'Nog geen uitslag' }}</span>
                    <span class="block text-[0.72rem] text-gedempt">{{ $result ? number_format($result->total_raw, 2, ',', '.').' van 100 punten' : 'geen kaarten' }}</span>
                </span>
            </div>
            <div class="dgb-card flex items-center gap-4 p-5">
                <span class="dgb-icoon {{ ($flags['missing_cards'] ?? true) ? 'bg-status-waarschuwing-bg text-status-waarschuwing' : 'bg-status-succes-bg text-status-succes' }}"><x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-kop text-[1.9rem] leading-none font-bold text-espresso">{{ $result?->card_count ?? 0 }}</span>
                    <span class="mt-1 block text-[0.8rem] font-bold text-espresso">Geldige kaarten</span>
                    <span class="block text-[0.72rem] text-gedempt">minimaal {{ $flags['min_valid_cards'] ?? '–' }} · {{ $pendingAssignments->count() }} nog open</span>
                </span>
            </div>
            <div class="dgb-card flex items-center gap-4 p-5">
                <span class="dgb-icoon {{ count($outliers) > 0 ? 'bg-status-waarschuwing-bg text-status-waarschuwing' : 'bg-status-neutraal-bg text-status-neutraal' }}"><x-filament::icon icon="heroicon-o-flag" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-kop text-[1.9rem] leading-none font-bold text-espresso">{{ count($outliers) }}</span>
                    <span class="mt-1 block text-[0.8rem] font-bold text-espresso">Afwijkers</span>
                    <span class="block text-[0.72rem] text-gedempt">meer dan {{ $sample->edition_id ? (\App\Domain\Edition\Models\Edition::query()->find($sample->edition_id)?->settings->outlierDeviationPoints ?? 15) : 15 }} punten van de mediaan</span>
                </span>
            </div>
        </div>

        <div class="dgb-card overflow-hidden">
            <div class="dgb-card-head">
                <span class="dgb-icoon bg-espresso text-goud"><x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="dgb-card-title">Scorekaarten</p>
                    <p class="dgb-card-sub">
                        @if ($sample->intake)
                            Ontvangen {{ DutchTime::format($sample->intake->received_at, 'D MMM HH:mm') }} · vers tot {{ DutchTime::format($sample->intake->freshness_expires_at, 'HH:mm') }} · {{ $sample->intake->piece_count }} stuks{{ $sample->intake->temperature_c !== null ? ' · '.number_format($sample->intake->temperature_c, 1, ',', '.').' °C' : '' }}
                        @else
                            Geen ontvangstregistratie
                        @endif
                    </p>
                </div>
                @if ($canReview && ! $result?->isFinal())
                    {{ $this->paperEntryAction }}
                @endif
            </div>

            @if ($cards->isEmpty())
                <div class="dgb-empty"><p class="text-[0.85rem] text-gedempt">Nog geen kaarten ingediend.</p></div>
            @else
                <div class="overflow-x-auto px-5 pb-5">
                    <table class="w-full text-[0.82rem]">
                        <thead>
                            <tr class="text-left text-[0.65rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                                <th class="py-2 pr-3">Panel</th>
                                @foreach ($criteria as $criterion)
                                    <th class="py-2 pr-3 text-right" title="{{ $criterion->name }}">{{ \Illuminate\Support\Str::limit($criterion->name, 12, '.') }}<br><span class="font-medium normal-case tracking-normal">/{{ $criterion->max_points }}</span></th>
                                @endforeach
                                <th class="py-2 pr-3 text-right">Totaal</th>
                                <th class="py-2 pr-3">Bron</th>
                                <th class="py-2 pr-3">Status</th>
                                @if ($canReview && ! $result?->isFinal())
                                    <th class="py-2"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cards as $card)
                                @php
                                    $scores = $effective[$card->getKey()];
                                    $isOutlier = in_array($card->uuid, $outliers, true);
                                    $pending = $card->corrections->firstWhere('status', CorrectionStatus::Pending);
                                    $approved = $card->corrections->where('status', CorrectionStatus::Approved)->sortByDesc('approved_at')->first();
                                @endphp
                                <tr class="border-t border-espresso/5 {{ ! $card->is_valid ? 'opacity-50' : '' }} {{ $isOutlier && $card->is_valid ? 'bg-status-waarschuwing-bg/60' : '' }}">
                                    <td class="py-3 pr-3 font-bold text-espresso">{{ $card->panelist->display_code }}</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="py-3 pr-3 text-right tabular-nums {{ $approved && array_key_exists($criterion->code, $approved->after) ? 'font-bold text-goud-tekst' : '' }}">{{ $scores[$criterion->code] ?? '–' }}</td>
                                    @endforeach
                                    <td class="py-3 pr-3 text-right font-bold tabular-nums">{{ array_sum($scores) }}</td>
                                    <td class="py-3 pr-3 text-gedempt">{{ $card->source->getLabel() }}</td>
                                    <td class="py-3 pr-3">
                                        <div class="flex flex-wrap gap-1">
                                            @if (! $card->is_valid)
                                                <span class="dgb-pill bg-status-fout-bg text-status-fout" title="{{ $card->invalidated_reason }}">Ongeldig</span>
                                            @elseif ($isOutlier)
                                                <span class="dgb-pill bg-status-waarschuwing-bg text-status-waarschuwing">Afwijker</span>
                                            @else
                                                <span class="dgb-pill bg-status-succes-bg text-status-succes">Geldig</span>
                                            @endif
                                            @if ($pending)
                                                <span class="dgb-pill bg-status-info-bg text-status-info">Correctie open</span>
                                            @elseif ($approved)
                                                <span class="dgb-pill bg-goud-licht text-goud-tekst">Gecorrigeerd</span>
                                            @endif
                                        </div>
                                    </td>
                                    @if ($canReview && ! $result?->isFinal())
                                        <td class="py-3 text-right whitespace-nowrap">
                                            @if ($pending)
                                                @if ($pending->requested_by !== (int) auth()->id())
                                                    <button type="button" class="dgb-link" wire:click="mountAction('approveCorrection', { correction: {{ $pending->getKey() }} })">Goedkeuren</button>
                                                    <button type="button" class="dgb-link !text-gedempt" wire:click="mountAction('rejectCorrection', { correction: {{ $pending->getKey() }} })">Afwijzen</button>
                                                @else
                                                    <span class="text-[0.72rem] text-gedempt">wacht op collega</span>
                                                @endif
                                            @elseif ($card->is_valid)
                                                <button type="button" class="dgb-link" wire:click="mountAction('requestCorrection', { card: {{ $card->getKey() }} })">Corrigeren</button>
                                                <button type="button" class="dgb-link !text-status-fout" wire:click="mountAction('invalidateCard', { card: {{ $card->getKey() }} })">Ongeldig</button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @if ($card->strengths || $card->opportunities)
                                    <tr class="{{ ! $card->is_valid ? 'opacity-50' : '' }}">
                                        <td></td>
                                        <td colspan="{{ $criteria->count() + 4 }}" class="pb-3 text-[0.75rem] text-gedempt">
                                            @if ($card->strengths)<span class="font-bold text-espresso">Sterk:</span> {{ $card->strengths }} @endif
                                            @if ($card->opportunities)<span class="font-bold text-espresso">Kans:</span> {{ $card->opportunities }} @endif
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($card->corrections as $correction)
                                    <tr class="text-[0.75rem]">
                                        <td></td>
                                        <td colspan="{{ $criteria->count() + 4 }}" class="pb-3 text-gedempt">
                                            <span class="dgb-pill {{ $correction->status === CorrectionStatus::Approved ? 'bg-status-succes-bg text-status-succes' : ($correction->status === CorrectionStatus::Pending ? 'bg-status-info-bg text-status-info' : 'bg-status-neutraal-bg text-status-neutraal') }}">{{ $correction->status->getLabel() }}</span>
                                            @foreach ($correction->after as $code => $value)
                                                {{ $criteria->firstWhere('code', $code)?->name ?? $code }}: {{ $correction->before[$code] ?? '?' }} &rarr; <strong>{{ $value }}</strong>@if (! $loop->last), @endif
                                            @endforeach
                                            · {{ $correction->reason }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        @if ($result)
                            <tfoot>
                                <tr class="border-t-2 border-espresso/10 font-bold text-espresso">
                                    <td class="py-3 pr-3">Gemiddeld</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="py-3 pr-3 text-right tabular-nums">{{ number_format($result->criterion_averages[$criterion->code] ?? 0, 1, ',', '.') }}</td>
                                    @endforeach
                                    <td class="py-3 pr-3 text-right tabular-nums">{{ number_format($result->total_raw, 1, ',', '.') }}</td>
                                    <td colspan="3" class="py-3 text-goud-tekst">cijfer {{ $result->formattedTotal() }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>

        @if ($pendingAssignments->isNotEmpty())
            <div class="dgb-card p-5">
                <p class="dgb-card-title mb-2">Nog geen kaart van</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($pendingAssignments as $assignment)
                        <span class="dgb-chip">{{ $assignment->panelist->display_code }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

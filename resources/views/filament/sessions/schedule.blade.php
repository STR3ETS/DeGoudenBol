@php
    use App\Domain\Testing\Enums\AssignmentStatus;

    /** @var \App\Domain\Testing\Models\TestSession $record */
    $record = $getRecord();
    $record->loadMissing(['panelists', 'samples', 'assignments', 'exclusions']);
    $assignments = $record->assignments->groupBy('panelist_id');
    $exclusions = $record->exclusions->groupBy('panelist_id');
    $samplesById = $record->samples->keyBy('id');
@endphp
<div class="dgb-card overflow-hidden">
    <div class="dgb-card-head">
        <span class="dgb-icoon bg-goud-licht text-goud-tekst"><x-filament::icon icon="heroicon-o-queue-list" class="h-4 w-4" /></span>
        <div class="min-w-0 flex-1">
            <p class="dgb-card-title">Uitserveerschema</p>
            <p class="dgb-card-sub">
                @if ($record->schedule_generated_at)
                    {{ $record->assignments->count() }} uitserveringen · {{ $record->exclusions->count() }} uitsluitingen · gegenereerd {{ $record->schedule_generated_at->timezone(config('app.display_timezone'))->format('d-m H:i') }}
                @else
                    Nog niet gegenereerd. Koppel monsters en panelleden en kies "Uitserveerschema genereren".
                @endif
            </p>
        </div>
    </div>

    @if ($record->schedule_generated_at)
        <div class="overflow-x-auto px-5 pb-5">
            <table class="w-full text-[0.82rem]">
                <thead>
                    <tr class="text-left text-[0.65rem] font-bold tracking-[0.12em] text-gedempt uppercase">
                        <th class="py-2 pr-4">Panellid</th>
                        <th class="py-2 pr-4">Volgorde van uitserveren</th>
                        <th class="py-2">Uitgesloten</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($record->panelists->sortBy('display_code') as $panelist)
                        <tr class="border-t border-espresso/5 align-top">
                            <td class="py-3 pr-4 font-bold text-espresso">{{ $panelist->display_code }}</td>
                            <td class="py-3 pr-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach (($assignments->get($panelist->getKey()) ?? collect())->sortBy('serving_order') as $assignment)
                                        <span class="dgb-pill {{ $assignment->status === AssignmentStatus::Scored ? 'bg-status-succes-bg text-status-succes' : 'bg-zand text-espresso' }}" title="{{ $assignment->status->getLabel() }}">
                                            {{ $assignment->serving_order }}. {{ $samplesById->get($assignment->sample_id)?->label() }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse (($exclusions->get($panelist->getKey()) ?? collect()) as $exclusion)
                                        <span class="dgb-pill bg-status-waarschuwing-bg text-status-waarschuwing">{{ $samplesById->get($exclusion->sample_id)?->label() }} · {{ $exclusion->reason_code->getLabel() }}</span>
                                    @empty
                                        <span class="text-gedempt">–</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

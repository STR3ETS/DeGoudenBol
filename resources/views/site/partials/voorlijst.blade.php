@php
    use App\Support\DutchTime;

    $rows = $top->keyBy('position');
    $noteStyle = 'background:rgba(250,245,234,.06)';
    $noteText = 'color:rgba(250,245,234,.7)';
@endphp
<ol class="voorlijst">
@for ($i = 1; $i <= $listLength; $i++)
    @php
        $position = $rows->get($i);
        $nr = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    @endphp
    @if ($position)
        @php
            $entry = $position->entry;
            $meta = array_filter([
                $i === 1 && $snapshot?->isFrozen() ? 'Provinciewinnaar '.($edition?->year ?? $snapshot->edition?->year) : null,
                $entry->company?->primaryLocation?->city,
                $position->label->getLabel() !== 'Gelijk' ? $position->label->getLabel() : null,
                $position->needs_tie_break ? 'beslissende beoordeling nodig' : null,
            ]);
        @endphp
        <li class="vl-row{{ $i === 1 ? ' vl-top' : '' }}"><span class="rank-no" aria-hidden="true">{{ $nr }}</span><div><h3 class="rank-name"><span class="sr-only">Plaats {{ $i }}: </span><a href="{{ route('bakkers.toon', $entry->company) }}">{{ $entry->public_name }}</a></h3><p class="rank-meta">{{ implode(' · ', $meta) }}</p></div><div class="rank-score"><b>{{ $position->formattedTotal() }}</b></div></li>
    @else
        <li class="vl-row vl-empty"><span class="rank-no" aria-hidden="true">{{ $nr }}</span><div><h3 class="rank-name"><span class="sr-only">Plaats {{ $i }}: </span>Nog niet bekend</h3></div><div></div></li>
    @endif
@endfor
</ol>
@if ($pendingReveal)
    <div class="note rev" style="{{ $noteStyle }}"><p style="{{ $noteText }}">De definitieve Top {{ $listLength }} en de provinciewinnaar van {{ $province->name }} worden bekendgemaakt op {{ DutchTime::format($pendingReveal, 'dddd D MMMM [om] HH:mm') }} uur.</p></div>
@elseif ($top->isEmpty())
    @php
        $leeg = 'Er zijn nog geen scores gepubliceerd.'
            .($startLabel ? " De officiële beoordelingen starten op {$startLabel}." : '')
            ." Alleen scores van {$threshold} of hoger worden openbaar.";
    @endphp
    <div class="note rev" style="{{ $noteStyle }}"><p style="{{ $noteText }}">{{ $leeg }}</p></div>
@else
    @php
        $stand = ($snapshot?->isFrozen() ? 'Definitieve' : 'Voorlopige')." Top {$listLength}"
            .($snapshot?->published_at ? ', bijgewerkt op '.DutchTime::format($snapshot->published_at, 'D MMMM [om] HH:mm').' uur' : '')
            .". Alleen scores van {$threshold} of hoger worden openbaar; nieuwe beoordelingen worden direct op score ingevoegd.";
    @endphp
    <div class="note rev" style="{{ $noteStyle }}"><p style="{{ $noteText }}">{{ $stand }}</p></div>
@endif
@if ($rest->isNotEmpty())
    <p class="body-text rev" style="margin-top:28px;max-width:620px">Ook beoordeeld in {{ $province->name }}: {{ $rest->map(fn ($p) => $p->entry->public_name.' ('.$p->formattedTotal().')')->implode(', ') }}.</p>
@endif
@if ($pressRelease ?? null)
    <p class="body-text rev" style="margin-top:20px"><a href="{{ route('pers.toon', $pressRelease) }}" style="color:var(--gold);font-weight:700">Persbericht: {{ $pressRelease->title }} &rarr;</a></p>
@endif

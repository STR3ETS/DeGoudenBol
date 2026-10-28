@php use App\Support\DutchTime; @endphp
@if ($positions->isNotEmpty() || $finalists->isNotEmpty())
<section class="sec on-dark-sec" id="uitslag" aria-labelledby="uitslag-h"><div class="bgv bgv-oil glow" aria-hidden="true"><i></i><i></i><i></i><b></b></div><div class="sec-inner">
    @if ($positions->isNotEmpty())
        @php $winner = $positions->first(); @endphp
        <span class="eyebrow rev">Landelijke uitslag {{ $edition?->year }}</span><h2 class="h2 rev" id="uitslag-h">Top {{ $listLength }} van Nederland</h2>
        <p class="body-text rev" style="max-width:620px;margin-bottom:32px">De beste oliebol van Nederland {{ $edition?->year }} komt van <strong>{{ $winner->entry->public_name }}</strong> uit {{ $winner->entry->province->name }}, met een {{ $winner->formattedTotal() }}.</p>
        <ol class="voorlijst">
            @foreach ($positions->take($listLength) as $position)
                <li class="vl-row{{ $position->position === 1 ? ' vl-top' : '' }}"><span class="rank-no" aria-hidden="true">{{ str_pad((string) $position->position, 2, '0', STR_PAD_LEFT) }}</span><div><h3 class="rank-name"><span class="sr-only">Plaats {{ $position->position }}: </span><a href="{{ route('bakkers.toon', $position->entry->company) }}">{{ $position->entry->public_name }}</a></h3><p class="rank-meta">{{ implode(' · ', array_filter([$position->entry->company?->primaryLocation?->city, $position->entry->province->name])) }}</p></div><div class="rank-score"><b>{{ $position->formattedTotal() }}</b></div></li>
            @endforeach
        </ol>
    @else
        <span class="eyebrow rev">Finale {{ $edition?->year }}</span><h2 class="h2 rev" id="uitslag-h">De {{ $finalists->count() }} provinciewinnaars</h2>
        <p class="body-text rev" style="max-width:620px;margin-bottom:32px">De provinciewinnaars nemen het tegen elkaar op in een nieuwe, blinde finaletest{{ $edition?->final_test_day ? ' op '.DutchTime::date($edition->final_test_day) : '' }}. Iedereen begint opnieuw op nul{{ $edition?->national_result_at ? '; de landelijke uitslag volgt '.DutchTime::format($edition->national_result_at, 'dddd D MMMM [om] HH:mm').' uur' : '' }}.</p>
        <ol class="voorlijst">
            @foreach ($finalists as $finalist)
                <li class="vl-row"><span class="rank-no" aria-hidden="true">&#9733;</span><div><h3 class="rank-name"><a href="{{ route('bakkers.toon', $finalist->entry->company) }}">{{ $finalist->entry->public_name }}</a></h3><p class="rank-meta">{{ implode(' · ', array_filter([$finalist->entry->company?->primaryLocation?->city, $finalist->province->name, $finalist->originLabel()])) }}</p></div><div></div></li>
            @endforeach
        </ol>
    @endif
</div></section>
@endif

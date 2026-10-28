@php
    use App\Domain\Commerce\Services\SponsorPlacements;
    use App\Domain\Edition\Models\Edition;

    $edition = Edition::current();
    $plaatsingen = $edition ? app(SponsorPlacements::class)->homepage($edition) : collect();
@endphp
@if ($plaatsingen->isNotEmpty())
<section class="sec bg-linen" id="sponsoren" aria-labelledby="sponsoren-h"><div class="sec-inner">
    <span class="eyebrow rev">Partners</span><h2 class="h2 rev" id="sponsoren-h">Mede mogelijk gemaakt door</h2>
    <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
        @foreach ($plaatsingen as $placement)
            @include('site.partials.sponsor-kaart', ['sponsor' => $placement->sponsor, 'label' => $placement->location === 'national' ? ($placement->label ?: 'Landelijk partner') : 'Sponsor'])
        @endforeach
    </div>
    <p style="margin-top:18px"><a href="{{ route('sponsoren') }}" style="color:var(--gold);font-weight:700">Alle sponsoren en partners &rarr;</a></p>
</div></section>
@endif

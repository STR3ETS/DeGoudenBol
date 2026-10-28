@php
    use App\Domain\Commerce\Models\Product;
    use App\Domain\Commerce\Services\SponsorPlacements;
    use App\Domain\Edition\Models\Edition;
    use App\Support\Money;

    $edition = Edition::current();
    $overview = $edition ? app(SponsorPlacements::class)->overview($edition) : collect();
    $groepen = [
        ['Landelijke partners', $overview->get('national', collect()), fn ($p) => $p->label ?: 'Landelijk partner'],
        ['Provinciepartners', $overview->get('province', collect())->sortBy(fn ($p) => $p->province?->sort), fn ($p) => $p->locationLabel()],
        ['Finale en landelijke toppositie', $overview->get('national_top', collect())->merge($overview->get('final', collect())), fn ($p) => $p->locationLabel()],
        ['Sponsoren', $overview->get('homepage', collect()), fn ($p) => 'Sponsor'],
    ];
    $heeftSponsoren = collect($groepen)->contains(fn ($g) => $g[1]->isNotEmpty());
    $products = $edition ? Product::query()->where('edition_id', $edition->getKey())->orderBy('sort')->get() : collect();
@endphp
@if ($heeftSponsoren || $products->isNotEmpty())
<section class="sec" id="partners" aria-labelledby="partners-h"><div class="sec-inner">
    <span class="eyebrow rev">Editie {{ $edition?->year }}</span><h2 class="h2 rev" id="partners-h">{{ $heeftSponsoren ? 'Onze partners en sponsoren' : 'Sponsorpakketten en tarieven' }}</h2>
    @foreach ($groepen as [$titel, $plaatsingen, $label])
        @if ($plaatsingen->isNotEmpty())
            <h3 style="margin:28px 0 12px">{{ $titel }}</h3>
            <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                @foreach ($plaatsingen as $placement)
                    @include('site.partials.sponsor-kaart', ['sponsor' => $placement->sponsor, 'label' => $label($placement)])
                @endforeach
            </div>
        @endif
    @endforeach
    @if ($products->isNotEmpty())
        <h3 style="margin:28px 0 12px">Tarieven {{ $edition?->year }}</h3>
        <div class="stagger-grid" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($products as $product)
                <div class="jury-slot">
                    <strong>{{ $product->name }}</strong>
                    <p class="body-text" style="margin:6px 0 0;font-size:.9rem">{{ $product->is_custom ? 'Op maat, in overleg' : Money::format($product->price_cents).' excl. btw' }}@if ($product->extra_link_price_cents) · elke extra koppeling {{ Money::format($product->extra_link_price_cents) }}@endif</p>
                    @if ($product->description)<p class="body-text" style="margin:6px 0 0;font-size:.85rem">{{ $product->description }}</p>@endif
                </div>
            @endforeach
        </div>
    @endif
    <p class="body-text" style="margin-top:24px;font-size:.85rem">Sponsoring is gelabeld en staat altijd los van de beoordeling: sponsoren zien geen testgegevens en hebben geen invloed op score of ranking. Interesse? Mail <a href="mailto:info@degoudenbol.nl?subject=Sponsoring" style="color:var(--gold)">info@degoudenbol.nl</a>.</p>
</div></section>
@endif

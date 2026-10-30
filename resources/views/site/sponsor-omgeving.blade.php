@php
    use App\Domain\Commerce\Enums\OfferStatus;
    use App\Support\DutchTime;
    use App\Support\Money;

    $kaart = 'background:#fff;border-radius:18px;padding:26px 28px;box-shadow:0 1px 0 rgba(200,134,10,.15);text-align:left';
    $chip = fn (string $tekst, string $kleur = '#8A5A00') => '<span style="display:inline-block;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:'.$kleur.';border:1px solid currentColor;border-radius:99px;padding:3px 10px">'.e($tekst).'</span>';
    $openOrders = $orders->filter(fn ($order) => $order->isPending());
@endphp
<x-layouts.site title="Sponsoromgeving" :noindex="true" main-class="page-sec">
<section class="page-hero" aria-labelledby="h1">
    <div class="page-hero-inner">
        <span class="eyebrow">Sponsoromgeving {{ $edition?->year }}</span>
        <h1 class="h1" id="h1">{{ $sponsor->name }}</h1>
        <p class="lead">{!! $chip($sponsor->status->getLabel()) !!} @if ($sponsor->status->value === 'pending') Wij controleren uw logo en gegevens; daarna staat uw vermelding online. @elseif ($sponsor->status->value === 'request') Uw aanvraag is in behandeling; wij nemen contact met u op. @endif</p>
    </div>
</section>

<section class="sec bg-linen" aria-label="Uw sponsoring">
    <div class="sec-inner" style="max-width:1000px;display:grid;gap:22px">
        @if (session('status'))
            <div class="f-status show ok" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="f-status show bad" role="alert">{{ $errors->first() }}</div>
        @endif

        @if ($openOrders->isNotEmpty())
            <div style="{{ $kaart }};border:2px solid #C8860A">
                <h2 class="h3-serif" style="margin:0 0 10px">Openstaande betaling</h2>
                @foreach ($openOrders as $order)
                    <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;padding:10px 0;border-top:1px solid #f0e6d2">
                        <span class="body-text">{{ $order->lines->pluck('description')->implode(', ') }} · <strong>{{ $order->formattedTotal() }}</strong> incl. btw</span>
                        <form method="post" action="{{ route('sponsor.betalen', $order) }}">@csrf<button type="submit" class="f-submit" style="width:auto;padding:11px 22px">Nu betalen</button></form>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display:grid;gap:22px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">
            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 12px">Uw pakket en vermeldingen</h2>
                @forelse ($sponsor->placements as $placement)
                    <div style="padding:10px 0;border-top:1px solid #f0e6d2">
                        <strong>{{ $placement->product?->name }}</strong> {!! $chip($placement->status->getLabel(), $placement->status->value === 'active' ? '#2F6B3A' : '#8A5A00') !!}
                        <div class="body-text" style="font-size:.85rem">{{ $placement->locationLabel() }} · tot {{ DutchTime::format($placement->ends_at, 'D MMMM YYYY') }}</div>
                    </div>
                @empty
                    <p class="body-text">{{ $sponsor->requestedProduct ? 'Aangevraagd: '.$sponsor->requestedProduct->name.'.' : 'Nog geen vermeldingen.' }} Wij nemen contact met u op.</p>
                @endforelse
            </div>

            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 12px">"Bakt met": koppelingen</h2>
                @forelse ($sponsor->links as $link)
                    <div style="padding:10px 0;border-top:1px solid #f0e6d2">
                        <strong>{{ $link->company?->name }}</strong>
                        {!! $link->isConfirmed() ? $chip('Akkoord', '#2F6B3A') : ($link->declined_at ? $chip('Afgewezen', '#9B2C2C') : $chip('Wacht op deelnemer')) !!}
                    </div>
                @empty
                    <p class="body-text">Nog geen koppelingen met deelnemers.</p>
                @endforelse

                @if ($participantProduct && $sponsor->status->value !== 'request' && $entries->isNotEmpty())
                    <form method="post" action="{{ route('sponsor.vermelding') }}" style="margin-top:16px">
                        @csrf
                        <label class="f-label" for="entry">Extra vermelding ({{ Money::format($participantProduct->extra_link_price_cents ?? $participantProduct->price_cents) }} excl. btw)</label>
                        <select id="entry" name="entry" class="f-input" required>
                            <option value="">Kies een deelnemer</option>
                            @foreach ($entries as $entry)
                                <option value="{{ $entry->getKey() }}">{{ $entry->public_name }} · {{ $entry->province?->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="f-submit" style="margin-top:10px">Toevoegen en betalen</button>
                    </form>
                @endif
            </div>

            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 12px">Facturen</h2>
                @forelse ($orders->filter(fn ($o) => $o->invoice) as $order)
                    <div style="padding:10px 0;border-top:1px solid #f0e6d2;display:flex;justify-content:space-between;gap:10px">
                        <a href="{{ route('sponsor.factuur', $order->invoice) }}" style="color:var(--gold);font-weight:700">{{ $order->invoice->number }}</a>
                        <span class="body-text">{{ $order->invoice->formattedTotal() }} · {{ DutchTime::format($order->invoice->issued_at, 'D MMM YYYY') }}</span>
                    </div>
                @empty
                    <p class="body-text">Nog geen facturen.</p>
                @endforelse
            </div>

            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 12px">Logo en website</h2>
                @if ($sponsor->logoUrl())
                    <img src="{{ $sponsor->logoUrl() }}" alt="Logo {{ $sponsor->name }}" style="max-height:70px;max-width:220px;margin-bottom:12px">
                @endif
                <form method="post" action="{{ route('sponsor.gegevens') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="f-field"><label class="f-label" for="website">Website</label><input id="website" name="website" type="url" class="f-input" value="{{ old('website', $sponsor->url) }}" style="cursor:text"></div>
                    <div class="f-field"><label class="f-label" for="logo">Nieuw logo</label><input id="logo" name="logo" type="file" accept="image/*" class="f-input"></div>
                    <button type="submit" class="f-submit">Opslaan</button>
                </form>
            </div>
        </div>

        <div style="display:grid;gap:22px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">
            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 6px">Sponsoractie aanbieden</h2>
                <p class="body-text" style="font-size:.88rem;margin-bottom:14px">Vouchers, proefpakketten, productcadeaus, adviesgesprekken of prijzen voor winnaars. De redactie beoordeelt elke actie voordat hij zichtbaar wordt.</p>
                @foreach ($sponsor->offers as $offer)
                    <div style="padding:8px 0;border-top:1px solid #f0e6d2"><strong>{{ $offer->title }}</strong> {!! $chip($offer->status->getLabel(), $offer->status === OfferStatus::Approved ? '#2F6B3A' : ($offer->status === OfferStatus::Rejected ? '#9B2C2C' : '#8A5A00')) !!}<div class="body-text" style="font-size:.82rem">{{ $offer->type->getLabel() }} · {{ $offer->audience->getLabel() }}</div></div>
                @endforeach
                <form method="post" action="{{ route('sponsor.actie') }}" style="margin-top:14px">
                    @csrf
                    <div class="f-row">
                        <div class="f-field"><label class="f-label" for="type">Soort</label><select id="type" name="type" class="f-input" required>@foreach ($offerTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        <div class="f-field"><label class="f-label" for="audience">Voor</label><select id="audience" name="audience" class="f-input" required>@foreach ($audiences as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                    </div>
                    <div class="f-field"><label class="f-label" for="title">Titel</label><input id="title" name="title" class="f-input" required maxlength="160" style="cursor:text"></div>
                    <div class="f-field"><label class="f-label" for="description">Omschrijving</label><textarea id="description" name="description" rows="3" class="f-input" required style="cursor:text"></textarea></div>
                    <button type="submit" class="f-submit">Actie voorleggen</button>
                </form>
            </div>

            <div style="{{ $kaart }}">
                <h2 class="h3-serif" style="margin:0 0 6px">Goed doel voordragen</h2>
                <p class="body-text" style="font-size:.88rem;margin-bottom:14px">10% van de opbrengst van De Gouden Bol gaat naar goede doelen. U mag één doel voordragen; de organisatie beslist welke doelen worden geselecteerd.</p>
                @if ($sponsor->nomination)
                    <p class="body-text"><strong>{{ $sponsor->nomination->name }}</strong> {!! $chip($sponsor->nomination->status->getLabel()) !!}</p>
                @else
                    <form method="post" action="{{ route('sponsor.goed-doel') }}">
                        @csrf
                        <div class="f-field"><label class="f-label" for="charity-name">Naam</label><input id="charity-name" name="name" class="f-input" required style="cursor:text"></div>
                        <div class="f-row">
                            <div class="f-field"><label class="f-label" for="charity-website">Website</label><input id="charity-website" name="website" type="url" class="f-input" style="cursor:text"></div>
                            <div class="f-field"><label class="f-label" for="charity-province">Provincie</label><select id="charity-province" name="province_id" class="f-input"><option value="">Landelijk</option>@foreach ($provinces as $province)<option value="{{ $province->getKey() }}">{{ $province->name }}</option>@endforeach</select></div>
                        </div>
                        @if ($categories)
                            <div class="f-field"><label class="f-label" for="charity-category">Categorie</label><select id="charity-category" name="category" class="f-input"><option value="">Kies</option>@foreach ($categories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                        @endif
                        <div class="f-field"><label class="f-label" for="charity-motivation">Motivatie</label><textarea id="charity-motivation" name="motivation" rows="3" class="f-input" required style="cursor:text"></textarea></div>
                        <button type="submit" class="f-submit">Voordragen</button>
                    </form>
                @endif
            </div>
        </div>

        <form method="post" action="{{ route('sponsor.uitloggen') }}" style="text-align:center">@csrf<button type="submit" class="body-text" style="background:none;border:0;text-decoration:underline;cursor:pointer">Uitloggen</button></form>
    </div>
</section>
</x-layouts.site>

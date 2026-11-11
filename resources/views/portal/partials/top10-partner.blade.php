{{-- Sponsorpakket "Top 10 partner" ook in het portaal (verzoek Bennie 11 nov 2026): deelnemers vragen het hier aan.
     Het blijft een aanvraag via de sponsorpagina; sponsoring staat los van de test. --}}
@php
    use App\Domain\Commerce\Enums\ProductCode;
    use App\Domain\Commerce\Models\Product;
    use App\Domain\Edition\Models\Edition;
    use App\Support\Money;

    $partnerEditie = Edition::current();
    $partnerPakket = $partnerEditie
        ? Product::query()->where('edition_id', $partnerEditie->getKey())->where('code', ProductCode::NationalTop->value)->first()
        : null;
@endphp
@if ($partnerPakket && Route::has('sponsoring.aanmelden'))
    <section class="ui-kaart ui-kaart--zand" id="top10-partner">
        <div class="ui-kaart__kop"><span class="ui-icoonvak ui-icoonvak--goud"><x-ui.icoon naam="ster" /></span><div><div class="ui-kaart__titel">{{ $partnerPakket->name }}</div><div class="ui-kaart__sub">Sponsorpakket, op aanvraag</div></div></div>
        <div class="ui-kaart__inhoud !pt-2">
            <p class="mb-1 text-[1.05rem] font-bold text-espresso">{{ Money::format($partnerPakket->price_cents) }} <span class="text-[0.8rem] font-normal text-gedempt">excl. btw</span></p>
            @if ($partnerPakket->extra_link_price_cents)
                <p class="mb-3 text-[0.82rem] text-gedempt">Elke extra vermelding {{ Money::format($partnerPakket->extra_link_price_cents) }}</p>
            @endif
            @if ($partnerPakket->description)
                <p class="mb-4 text-[0.88rem]">{{ $partnerPakket->description }}</p>
            @endif
            <a href="{{ route('sponsoring.aanmelden', ['pakket' => $partnerPakket->code->value]) }}" class="ui-knop">Aanvragen <x-ui.icoon naam="pijl-rechts" dikte="2.4" /></a>
            <p class="mt-3 text-[0.8rem] text-gedempt">Je vraagt het aan zonder betaalverplichting; wij nemen contact met je op. Sponsoring heeft nooit invloed op de keuring of de uitslag.</p>
        </div>
    </section>
@endif

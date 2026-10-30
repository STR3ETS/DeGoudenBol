@php
    use App\Domain\Commerce\Enums\ProductCode;
    use App\Http\Controllers\Public\SponsorRegistrationController;
    use App\Support\Money;

    $gekozen = (string) old('product', $products->firstWhere('code.value', $selected)?->getKey() ?? '');
    $codes = $products->mapWithKeys(fn ($p) => [$p->getKey() => $p->code->value])->all();
@endphp
<x-layouts.site title="Sponsor worden" description="Verbind uw bedrijf aan de zoektocht naar de beste oliebol van Nederland. Kies een sponsorpakket en reken direct af, of vraag maatwerk aan." main-class="page-sec">
<section class="page-hero" aria-labelledby="h1">
    <div class="page-hero-inner">
        <span class="eyebrow">Sponsoring {{ $edition?->year }}</span>
        <h1 class="h1" id="h1">Sponsor <em>worden</em></h1>
        <p class="lead">Kies een pakket, vul uw gegevens in en reken vaste pakketten direct af. Maatwerk en de Top 10 partner vraagt u aan; wij nemen dan contact op. Sponsoring heeft nooit invloed op de beoordeling.</p>
    </div>
</section>

<section class="sec bg-linen" aria-label="Aanmeldformulier">
    <div class="sec-inner" style="max-width:860px">
        @if ($products->isEmpty())
            <p class="body-text">Er zijn op dit moment geen sponsorpakketten beschikbaar. Mail ons gerust.</p>
        @else
        <form method="post" action="{{ route('sponsoring.aanmelden.verwerken') }}" enctype="multipart/form-data" class="form-box" style="text-align:left" data-sponsor-form>
            @csrf
            @if ($errors->any())
                <div class="f-status show bad" role="alert">{{ $errors->first() }}</div>
            @endif

            <h3>1. Kies uw pakket</h3>
            <div style="display:grid;gap:12px;margin:14px 0 26px">
                @foreach ($products as $product)
                    <label class="jury-slot" style="display:flex;gap:14px;align-items:flex-start;cursor:pointer;text-align:left">
                        <input type="radio" name="product" value="{{ $product->getKey() }}" data-code="{{ $product->code->value }}" @checked($gekozen === (string) $product->getKey()) required style="margin-top:5px;accent-color:#C8860A">
                        <span style="flex:1">
                            <strong style="display:block">{{ $product->name }}</strong>
                            <span class="body-text" style="display:block;font-size:.88rem;margin-top:4px">{{ $product->description }}</span>
                            @if ($label = SponsorRegistrationController::availabilityLabel($product))
                                <span class="body-text" style="display:block;font-size:.8rem;margin-top:4px;font-weight:700">{{ $label }}</span>
                            @endif
                        </span>
                        <span style="white-space:nowrap;text-align:right">
                            @if ($product->isCustomPriced())
                                <strong>Maatwerk</strong><span class="body-text" style="display:block;font-size:.75rem">aanvraag</span>
                            @else
                                <strong>{{ Money::format($product->price_cents) }}</strong><span class="body-text" style="display:block;font-size:.75rem">excl. btw @if ($product->extra_link_price_cents)<br>extra {{ Money::format($product->extra_link_price_cents) }}@endif</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            @error('product')<p class="f-err" style="display:block">{{ $message }}</p>@enderror

            <div data-voor="participant_link" @if (($codes[$gekozen] ?? '') !== 'participant_link') hidden @endif>
                <h3>Bij welke deelnemer(s)?</h3>
                <p class="body-text" style="font-size:.88rem">Kies de deelnemers die uw product of merk gebruiken. De eerste vermelding kost het volle tarief, elke extra vermelding het lagere tarief. De deelnemer bevestigt de koppeling zelf; zonder akkoord verschijnt er niets.</p>
                <div class="f-field">
                    <label class="f-label" for="entries">Deelnemers</label>
                    <select id="entries" name="entries[]" class="f-input" multiple size="8" style="cursor:default">
                        @foreach ($entries as $entry)
                            <option value="{{ $entry->getKey() }}" @selected(in_array($entry->getKey(), old('entries', [])))>{{ $entry->public_name }} · {{ $entry->province?->name }}</option>
                        @endforeach
                    </select>
                    <p class="body-text" style="font-size:.75rem;margin-top:6px">Houd Ctrl (of Cmd) ingedrukt om er meer te kiezen.</p>
                </div>
            </div>

            <div data-voor="province_partner" @if (($codes[$gekozen] ?? '') !== 'province_partner') hidden @endif>
                <div class="f-field">
                    <label class="f-label" for="province">Provincie</label>
                    <select id="province" name="province" class="f-input">
                        <option value="">Kies een provincie</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->getKey() }}" @selected((string) old('province') === (string) $province->getKey())>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h3 style="margin-top:22px">2. Uw gegevens</h3>
            <div class="f-row">
                <div class="f-field @error('company') invalid @enderror"><label class="f-label" for="company">Bedrijfsnaam</label><input id="company" name="company" class="f-input" value="{{ old('company') }}" required autocomplete="organization"><span class="f-err">{{ $errors->first('company') }}</span></div>
                <div class="f-field @error('kvk') invalid @enderror"><label class="f-label" for="kvk">KvK-nummer</label><input id="kvk" name="kvk" class="f-input" value="{{ old('kvk') }}" inputmode="numeric" placeholder="12345678"><span class="f-err">{{ $errors->first('kvk') }}</span></div>
            </div>
            <div class="f-row">
                <div class="f-field @error('contact_name') invalid @enderror"><label class="f-label" for="contact_name">Contactpersoon</label><input id="contact_name" name="contact_name" class="f-input" value="{{ old('contact_name') }}" required autocomplete="name"><span class="f-err">{{ $errors->first('contact_name') }}</span></div>
                <div class="f-field @error('email') invalid @enderror"><label class="f-label" for="email">E-mailadres</label><input id="email" name="email" type="email" class="f-input" value="{{ old('email') }}" required autocomplete="email"><span class="f-err">{{ $errors->first('email') }}</span></div>
            </div>
            <div class="f-row">
                <div class="f-field"><label class="f-label" for="phone">Telefoon</label><input id="phone" name="phone" type="tel" class="f-input" value="{{ old('phone') }}" autocomplete="tel"></div>
                <div class="f-field @error('website') invalid @enderror"><label class="f-label" for="website">Website</label><input id="website" name="website" type="url" class="f-input" value="{{ old('website') }}" placeholder="https://"><span class="f-err">{{ $errors->first('website') }}</span></div>
            </div>

            <h3 style="margin-top:8px">3. Factuuradres</h3>
            <div class="f-field @error('street') invalid @enderror"><label class="f-label" for="street">Straat en huisnummer</label><input id="street" name="street" class="f-input" value="{{ old('street') }}" required autocomplete="address-line1"><span class="f-err">{{ $errors->first('street') }}</span></div>
            <div class="f-row">
                <div class="f-field @error('postcode') invalid @enderror"><label class="f-label" for="postcode">Postcode</label><input id="postcode" name="postcode" class="f-input" value="{{ old('postcode') }}" required autocomplete="postal-code"><span class="f-err">{{ $errors->first('postcode') }}</span></div>
                <div class="f-field @error('city') invalid @enderror"><label class="f-label" for="city">Plaats</label><input id="city" name="city" class="f-input" value="{{ old('city') }}" required autocomplete="address-level2"><span class="f-err">{{ $errors->first('city') }}</span></div>
            </div>

            <h3 style="margin-top:8px">4. Logo en toelichting</h3>
            <div class="f-field @error('logo') invalid @enderror"><label class="f-label" for="logo">Logo (png, jpg of svg, max. 2 MB)</label><input id="logo" name="logo" type="file" accept="image/*" class="f-input"><span class="f-err">{{ $errors->first('logo') }}</span></div>
            <div class="f-field"><label class="f-label" for="note">Toelichting of wensen</label><textarea id="note" name="note" rows="4" class="f-input" style="cursor:text" placeholder="Bij maatwerk: wat heeft u in gedachten?">{{ old('note') }}</textarea></div>

            <label style="display:flex;gap:10px;align-items:flex-start;margin:18px 0;font-size:.88rem" class="body-text">
                <input type="checkbox" name="akkoord" value="1" @checked(old('akkoord')) required style="margin-top:4px;accent-color:#C8860A">
                <span>Ik ga akkoord met de <a href="{{ route('voorwaarden.onderdeel', 'sponsoring') }}" target="_blank" rel="noopener" style="color:var(--gold)">sponsorvoorwaarden</a>. Sponsoring heeft geen invloed op de beoordeling of de uitslag.</span>
            </label>
            @error('akkoord')<p class="f-err" style="display:block">{{ $message }}</p>@enderror

            @if ($turnstileSiteKey)
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light" data-language="nl" style="margin-bottom:14px"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif

            <button type="submit" class="f-submit" data-knop>Verder</button>
            <p class="f-note">Vaste pakketten rekent u direct af via iDEAL of kaart; u ontvangt de factuur per mail. Maatwerk en de Top 10 partner kosten nu niets.</p>
        </form>
        <p class="body-text" style="text-align:center;margin-top:18px;font-size:.88rem">Al sponsor? <a href="{{ route('sponsoring.beheer') }}" style="color:var(--gold)">Vraag uw beheerlink aan</a>.</p>
        @endif
    </div>
</section>

<script>
    (() => {
        const form = document.querySelector('[data-sponsor-form]');
        if (!form) return;
        const update = () => {
            const checked = form.querySelector('input[name="product"]:checked');
            const code = checked ? checked.dataset.code : '';
            form.querySelectorAll('[data-voor]').forEach((block) => { block.hidden = block.dataset.voor !== code; });
            const knop = form.querySelector('[data-knop]');
            knop.textContent = ['homepage', 'participant_link'].includes(code) ? 'Verder naar betalen' : (code ? 'Aanvraag versturen' : 'Verder');
        };
        form.addEventListener('change', update);
        update();
    })();
</script>
</x-layouts.site>

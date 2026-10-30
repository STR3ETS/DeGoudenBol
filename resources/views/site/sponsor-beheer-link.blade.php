<x-layouts.site title="Sponsoromgeving" :noindex="true" main-class="page-sec">
<section class="page-hero" aria-labelledby="h1">
    <div class="page-hero-inner">
        <span class="eyebrow">Voor sponsoren</span>
        <h1 class="h1" id="h1">Uw <em>sponsoromgeving</em></h1>
        <p class="lead">Pakket, logo, facturen, koppelingen bij deelnemers, acties en goede doelen. Vul het e-mailadres in waarmee u zich als sponsor aanmeldde; u krijgt een persoonlijke beheerlink.</p>
    </div>
</section>
<section class="sec bg-linen" aria-label="Beheerlink aanvragen">
    <div class="sec-inner" style="max-width:560px">
        <form method="post" action="{{ route('sponsoring.beheer.versturen') }}" class="form-box" style="text-align:left">
            @csrf
            @if (session('status'))
                <div class="f-status show ok" role="status">{{ session('status') }}</div>
            @endif
            <div class="f-field @error('email') invalid @enderror">
                <label class="f-label" for="email">E-mailadres</label>
                <input id="email" name="email" type="email" class="f-input" value="{{ old('email') }}" required autocomplete="email" style="cursor:text">
                <span class="f-err">{{ $errors->first('email') }}</span>
            </div>
            <button type="submit" class="f-submit">Stuur mij de beheerlink</button>
            <p class="f-note">Nog geen sponsor? <a href="{{ route('sponsoring.aanmelden') }}" style="color:var(--gold)">Meld u aan</a>.</p>
        </form>
    </div>
</section>
</x-layouts.site>

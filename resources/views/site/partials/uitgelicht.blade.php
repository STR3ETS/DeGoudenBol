{{-- Betaalde zichtbaarheidsplekken: bewust een eigen blok en naam, los van de Voorlijst en de Top 10 uit de test. --}}
@if (($uitgelicht ?? collect())->isNotEmpty())
<section class="sec bg-linen" id="uitgelicht" aria-labelledby="uitgelicht-h"><div class="sec-inner">
    <span class="eyebrow">Betaalde vermelding</span>
    <h2 class="h2" id="uitgelicht-h">Uitgelicht in {{ $province->name }}</h2>
    <p class="body-text" style="max-width:620px;margin-bottom:24px">Deze deelnemers hebben gekozen voor extra zichtbaarheid. Dit staat los van de test: de score en de plaats in de uitslag bepaalt alleen het onafhankelijke testpanel.</p>
    <ul class="link-list">
        @foreach ($uitgelicht as $plek)
            <li><a href="{{ route('bakkers.toon', $plek->company) }}">{{ $plek->entry->public_name }}@if ($plek->company->primaryLocation?->city) &middot; {{ $plek->company->primaryLocation->city }}@endif</a></li>
        @endforeach
    </ul>
</div></section>
@endif

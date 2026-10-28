{{-- Gegenereerd door tools/site-import.mjs; $liveJson komt uit App\Support\Site\LiveData. --}}
<aside id="gbw" class="gbw" data-live-url="/live.json" data-base="/" data-idx="" aria-label="Live: nieuwe aanmeldingen en ranglijst">
<button type="button" class="gbw-pill" aria-expanded="false" aria-controls="gbw-panel"><span class="gbw-dot" aria-hidden="true"></span><b>Live</b><small id="gbw-pill-t">Aanmeldingen en ranglijst</small></button>
<div class="gbw-panel" id="gbw-panel" role="region" aria-label="Aanmeldingen en ranglijst" hidden>
<div class="gbw-head"><div><span class="gbw-live"><i aria-hidden="true"></i>Live</span><b>De Gouden Bol 2026</b></div><button type="button" class="gbw-close" aria-label="Sluit het overzicht">&#10005;</button></div>
<div class="gbw-tabs" role="tablist" aria-label="Kies een overzicht"><button type="button" role="tab" id="gbw-t1" aria-selected="true" aria-controls="gbw-p1">Aanmeldingen</button><button type="button" role="tab" id="gbw-t2" aria-selected="false" aria-controls="gbw-p2" tabindex="-1">Ranglijst</button></div>
<div class="gbw-body" id="gbw-p1" role="tabpanel" aria-labelledby="gbw-t1"></div>
<div class="gbw-body" id="gbw-p2" role="tabpanel" aria-labelledby="gbw-t2" hidden></div>
<div class="gbw-foot"><a href="/deelnemers">Alle deelnemers</a><a class="gbw-cta" href="/deelnemen">Aanmelden</a></div>
<p class="gbw-upd" id="gbw-upd"></p>
</div>
<script type="application/json" id="gb-live">{!! $liveJson !!}</script>
</aside>

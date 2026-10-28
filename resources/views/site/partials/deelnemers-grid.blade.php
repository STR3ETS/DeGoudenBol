@php
    // Nog geen eigen foto's van bakkers: tot die tijd een wisselende oliebollenfoto uit de huisstijl.
    $fotos = ['b393fcd0d20bcd9d3a91868dab4c0a233c571787', '6063ee2b281fb9ba9d271b8bf92578fe65828f46', '6efde2762874a33f13d585207d006039014aa8f1', '83e9a266dc943c3670734663bd5a2d59eff4b963', '2e767c88d917a3dfceded31fdd371e77810b3b69'];
@endphp
<ul class="bk-grid stagger-grid" id="bkGrid">
@foreach ($entries as $entry)
    @php
        $company = $entry->company;
        $city = $company?->primaryLocation?->city;
        $total = $entry->publicTotal();
        $foto = '/site/assets/'.$fotos[$loop->index % count($fotos)].'.jpg';
    @endphp
    <li data-prov="{{ $entry->province->name }}" data-name="{{ Str::lower($entry->public_name) }}" data-city="{{ Str::lower((string) $city) }}"><a href="{{ route('bakkers.toon', $company) }}" class="bk-card"><div class="bk-img-wrap"><img src="{{ $foto }}" width="600" height="440" alt="" decoding="async" class="bk-img" loading="lazy"><div class="bk-img-ov"></div><div class="bk-type-badge">{{ $company->type?->getLabel() }}</div></div><div class="bk-body"><div class="bk-prov">{{ $entry->province->name }}</div><h2 class="bk-name">{{ $entry->public_name }}@if ($total !== null)<span class="tag-keur" style="margin-left:10px">{{ number_format($total, 1, ',', '.') }}</span>@endif</h2><p class="bk-city"><span aria-hidden="true">&#9679;</span> {{ $city ?: $entry->province->name }}</p><div class="bk-footer"><span class="bk-jaren">{{ $total !== null ? 'Officieel getest' : 'Deelnemer '.$entry->edition->year }}</span><span class="bk-cta">Bekijk profiel <span aria-hidden="true">&#8594;</span></span></div></div></a></li>
@endforeach
</ul>

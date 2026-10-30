{{-- Een blog of landingspagina van de contentmachine. De html heeft zijn eigen kopblok en secties in de opmaak van de site. --}}
@php
    use App\Support\Site\MachineContent;
    use Illuminate\Support\Str;

    $titel = Str::replaceLast(' | De Gouden Bol', '', (string) ($pagina['meta_title'] ?: $pagina['title']));
    $omschrijving = $pagina['meta_description'] ?: $pagina['excerpt'];
    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => $schema,
        'headline' => $pagina['title'],
        'description' => $omschrijving,
        'url' => url()->current(),
        'image' => $pagina['featured_image'] ?? null,
        'datePublished' => $pagina['published_at'] ?? null,
        'dateModified' => $pagina['updated_at'] ?? null,
        'author' => ! empty($pagina['author']['name']) ? ['@type' => 'Person', 'name' => $pagina['author']['name']] : null,
        'publisher' => ['@type' => 'Organization', 'name' => 'De Gouden Bol', 'url' => url('/')],
    ]);
@endphp
<x-layouts.site :title="$titel" :description="$omschrijving" :og-image="$pagina['featured_image'] ?? null">
    <x-slot:structured>
        <script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    </x-slot:structured>
    {{-- De kopblokken zijn donker en de menutekst is dat ook: daarom meteen de balk van na het scrollen. --}}
    <x-slot:head>
        <style>.nav{background:rgba(250,245,234,.96);backdrop-filter:blur(16px);border-bottom:1px solid rgba(28,15,3,.1)}[id^="faq-answer-"].hidden{display:none}</style>
    </x-slot:head>
    {!! MachineContent::schoon((string) ($pagina['content'] ?? '')) !!}
    {{-- Scripts uit de inhoud gaan eruit (MachineContent::schoon), dus de veelgestelde vragen openen hier. --}}
    <x-slot:scripts>
        <script>document.addEventListener('click',function(e){var k=e.target.closest('[aria-controls^="faq-answer-"]');if(!k)return;var p=document.getElementById(k.getAttribute('aria-controls'));if(!p)return;var open=k.getAttribute('aria-expanded')==='true';k.setAttribute('aria-expanded',open?'false':'true');p.classList.toggle('hidden',open);});</script>
    </x-slot:scripts>
</x-layouts.site>

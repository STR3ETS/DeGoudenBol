// Maakt van een paar gegenereerde pagina's (tools/site-import.mjs) de dynamische Blade-views:
// provincie, deelnemers, deelnemen, pers en home. De statische markup blijft de maat; alleen de
// stukken die uit de database komen, worden vervangen door Blade. Draai dit na site-import.mjs.
//
// Gebruik: node tools/site-dynamic.mjs
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';

const root = join(dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1')), '..');
const pages = join(root, 'resources/views/site/pages');
const views = join(root, 'resources/views/site');
mkdirSync(join(root, 'resources/views/errors'), { recursive: true });

const page = (slug) => {
    const raw = readFileSync(join(pages, slug + '.blade.php'), 'utf8');
    const props = raw.match(/<x-layouts\.site ([^>]*)>/)[1];
    const body = raw.match(/@verbatim\n([\s\S]*?)\n@endverbatim/)[1];
    const structured = (raw.match(/<x-slot:structured>\n([\s\S]*?)\n    <\/x-slot:structured>/) || [, ''])[1];
    return { props, body, structured };
};

// Blade mag de statische HTML niet interpreteren: {{ en @-woorden die Blade kent, worden geëscapet.
// Blade mag niets in de statische HTML als directive lezen (@context, @svg, @type, e-mailadressen): elke @woord wordt @@woord,
// wat Blade weer als een letterlijke @ uitvoert. {{ wordt @{{. Dit draait vóórdat eigen Blade wordt ingevoegd.
const safe = (html) => html.replace(/\{\{/g, '@{{').replace(/@(\w+)/g, '@@$1');
const must = (html, needle) => { if (!html.includes(needle)) throw new Error('Verwachte markup niet gevonden: ' + needle.slice(0, 80)); };

const header = '{{-- Gegenereerd door tools/site-dynamic.mjs; de statische delen komen uit de site-export. Pas de dynamische stukken aan in dat script. --}}\n';
const wrap = (props, inner, structured = '') => `${header}<x-layouts.site ${props}>\n${structured ? '    <x-slot:structured>\n' + structured + '\n    </x-slot:structured>\n' : ''}${inner}\n</x-layouts.site>\n`;

// ---------- Provincie ----------
{
    const { body } = page('provincies-gelderland');
    const split = body.indexOf('<section class="sec" aria-labelledby="andere-h">');
    const cta = body.indexOf('<section class="cta-dark', split);
    let a = body.slice(0, split), b = body.slice(split, cta), c = body.slice(cta);

    const ol = a.match(/<ol class="voorlijst">[\s\S]*?<\/ol>\s*<div class="note rev"[\s\S]*?<\/div>/);
    if (!ol) throw new Error('voorlijst niet gevonden');
    a = safe(a); // eerst escapen, dan pas eigen Blade invoegen
    a = a.replace(safe(ol[0]), "@include('site.partials.voorlijst')");
    a = a.replace(/<section class="sec-tight" aria-label="Beeld">[\s\S]*?<\/section>/, "@includeIf('site.partials.provincie-foto.'.$province->slug)");
    must(a, '0 van de 50 plaatsen ingevuld; nog 50 beschikbaar');
    must(a, "@include('site.partials.voorlijst')");
    a = a
        .replace(/0 van de 50 plaatsen ingevuld; nog 50 beschikbaar/g, '{{ $taken }} van de {{ $capacity }} plaatsen ingevuld; nog {{ $available }} beschikbaar')
        .replace(/maximum van 600/g, 'maximum van {{ $capacityTotal }}')
        .replace(/([Mm]aximaal|maximum van) 50\b/g, '$1 {{ $capacity }}')
        .replace(/1 november/g, '{{ $startLabel }}')
        .replace(/GELDERLAND/g, '{{ Str::upper($province->name) }}')
        .replace(/Gelderland/g, '{{ $province->name }}');
    b = safe(b).replace(/data-slug="([a-z-]+)" data-info="[^"]*"/g, 'data-slug="$1" data-info="{{ $mapInfo[\'$1\'] ?? \'\' }}"');
    c = safe(c).replace(/Gelderland/g, '{{ $province->name }}');
    const props = `:title="'De beste oliebollen van '.$province->name" :description="$metaDescription" main-class="page-sec"`;
    writeFileSync(join(views, 'provincie.blade.php'), wrap(props, a + b + c, "{!! $structuredData !!}"));
}

// ---------- Deelnemers ----------
{
    const { props, body } = page('deelnemers');
    let html = safe(body);
    must(html, '<p class="results-bar"');
    html = html.replace(/<div class="note" style="margin:0 0 28px"><p>[\s\S]*?<\/p><\/div>\s*/, `@if ($entries->isEmpty())\n<div class="note" style="margin:0 0 28px"><p>Er zijn nog geen deelnemers bekendgemaakt. Zodra de eerste betalingen binnen zijn, verschijnen de bakkers hier met hun profiel.</p></div>\n@endif\n`);
    must(html, '@if ($entries->isEmpty())');
    html = html.replace(/<p class="results-bar" aria-live="polite"><strong id="resultCount">1<\/strong> profielen<\/p>/, '<p class="results-bar" aria-live="polite"><strong id="resultCount">{{ $entries->count() }}</strong> {{ $entries->count() === 1 ? \'profiel\' : \'profielen\' }}</p>');
    html = html.replace(/<ul class="bk-grid stagger-grid" id="bkGrid">[\s\S]*?<\/ul>/, "@include('site.partials.deelnemers-grid')");
    html = html.replace(/<p class="lead">[^<]*<\/p>/, '<p class="lead">@if ($entries->isEmpty())Elke deelnemer krijgt een eigen profiel met bedrijfsinformatie, locatie, verhaal en resultaat. De eerste deelnemers worden bekendgemaakt voor de start van de beoordelingen{{ $startLabel ? \' op \'.$startLabel : \'\' }}.@else{{ $entries->count() }} {{ $entries->count() === 1 ? \'bakker doet\' : \'bakkers doen\' }} mee aan De Gouden Bol {{ $edition?->year }}. Elke deelnemer heeft een eigen profiel met bedrijfsinformatie, locatie, verhaal en resultaat.@endif</p>');
    writeFileSync(join(views, 'deelnemers.blade.php'), wrap(props, html));
}

// ---------- Deelnemen (aanmeldformulier → de echte aanmelding) ----------
{
    const { props, body } = page('deelnemen');
    let html = safe(body);
    must(html, '<form data-form-id');
    html = html.replace(/<h3>Aanmeldformulier<\/h3><p class="form-sub">Editie 2026<\/p>\s*<form data-form-id[\s\S]*?<\/form>/, "@include('site.partials.aanmeld-box')");
    must(html, "@include('site.partials.aanmeld-box')");
    writeFileSync(join(views, 'deelnemen.blade.php'), wrap(props, html));
}

// ---------- Pers en nieuws ----------
{
    const { props, body, structured } = page('pers');
    let html = safe(body);
    must(html, 'id="nieuws"');
    html = html.replace(/<div class="jury-slot" style="max-width:640px"><p class="body-text">Er zijn nog geen berichten\.[\s\S]*?<\/p><\/div>/, "@include('site.partials.nieuws-lijst')");
    writeFileSync(join(views, 'pers.blade.php'), wrap(props, html, safe(structured)));
}

// Een include vóór het donkere slotblok van een pagina.
const beforeCta = (html, include) => {
    must(html, '<section class="cta-dark');
    return html.replace('<section class="cta-dark', `@include('${include}')\n<section class="cta-dark`);
};

// ---------- Home (kaartinfo per provincie en sponsoren uit de database) ----------
{
    const { props, body, structured } = page('home');
    let html = safe(body).replace(/data-slug="([a-z-]+)" data-info="[^"]*"/g, 'data-slug="$1" data-info="{{ $mapInfo[\'$1\'] ?? \'\' }}"');
    html = beforeCta(html, 'site.partials.sponsoren-home');
    writeFileSync(join(views, 'home.blade.php'), wrap(props, html, safe(structured)));
}

// ---------- Sponsoring en goede doelen: stand van zaken uit de database ----------
for (const [slug, include] of [['faq', 'site.partials.faq-deelname'], ['sponsoring', 'site.partials.sponsoren-lijst'], ['goede-doelen', 'site.partials.goede-doelen-overzicht'], ['finale', 'site.partials.finale-stand']]) {
    const { props, body, structured } = page(slug);
    writeFileSync(join(views, slug + '.blade.php'), wrap(props, beforeCta(safe(body), include), safe(structured)));
}

// ---------- 404 ----------
writeFileSync(join(root, 'resources/views/errors/404.blade.php'), "@include('site.pages.404')\n");

console.log('Dynamische views geschreven: provincie, deelnemers, deelnemen, pers, home, errors/404.');

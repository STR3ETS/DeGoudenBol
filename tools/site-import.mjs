// Importeert de statische export van de publiekssite (CMS-export van Eazyonline) in de Laravel-app.
//
// Gebruik: node tools/site-import.mjs <uitgepakte-exportmap>
//
// Wat het doet:
//  1) Kopieert de bestanden uit storage/cms-assets en storage/cms-images naar public/site/{assets,images}
//     en herschrijft de font-paden in het CSS-bestand.
//  2) Haalt uit index.html de gedeelde onderdelen (kopscript, header, footer, live-widget) en schrijft ze
//     als Blade-partials in resources/views/site/partials.
//  3) Maakt van iedere pagina een Blade-view in resources/views/site/pages (statisch, binnen @verbatim),
//     met titel, omschrijving en structured data als props. Relatieve links worden absolute routes,
//     assetpaden wijzen naar /site/... en de "media-map" van de export wordt toegepast op assets/...-paden.
//  4) Schrijft per provincie het fotoblok als partial (dat is het enige wat per provinciepagina verschilt).
//
// Dynamische pagina's (provincie, deelnemers, deelnemer, pers, 404, deelnemen) hebben een eigen Blade-view
// in resources/views/site die de gegenereerde markup als basis gebruikt; die views worden NIET overschreven.
import { readFileSync, writeFileSync, mkdirSync, readdirSync, copyFileSync, statSync, existsSync } from 'node:fs';
import { join, dirname, extname, relative, sep } from 'node:path';

const [source] = process.argv.slice(2);
if (!source) { console.error('Gebruik: node tools/site-import.mjs <exportmap>'); process.exit(1); }

const root = join(dirname(new URL(import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1')), '..');
const out = { assets: join(root, 'public/site/assets'), images: join(root, 'public/site/images'), partials: join(root, 'resources/views/site/partials'), pages: join(root, 'resources/views/site/pages'), fotos: join(root, 'resources/views/site/partials/provincie-foto') };
Object.values(out).forEach((d) => mkdirSync(d, { recursive: true }));

const read = (p) => readFileSync(p, 'utf8');
const index = read(join(source, 'index.html'));

// Media-map: assets/x → storage/cms-assets/hash
const mediaMap = JSON.parse((index.match(/data-cms-media-overrides>([\s\S]*?)<\/script>/) || [, '{}'])[1]);
const resolveAsset = (p) => {
    const kaal = p.replace(/^(?:\.\.?\/)+/, '');
    const hit = mediaMap[kaal] ?? mediaMap['./' + kaal] ?? mediaMap['/' + kaal];
    return hit ? hit.replace(/^(?:\.\.?\/)+/, '') : kaal;
};

// 1) Assets
let copied = 0;
for (const [sub, dest] of [['storage/cms-assets', out.assets], ['storage/cms-images', out.images]]) {
    const dir = join(source, sub);
    if (!existsSync(dir)) continue;
    for (const f of readdirSync(dir)) {
        const src = join(dir, f);
        if (!statSync(src).isFile()) continue;
        if (extname(f) === '.css') {
            writeFileSync(join(dest, f), read(src).replace(/url\((['"]?)(?:\.\.\/)+storage\/cms-assets\//g, 'url($1/site/assets/'));
        } else if (extname(f) === '.json') {
            continue; // live.json komt uit de app (/live.json)
        } else {
            copyFileSync(src, join(dest, f));
        }
        copied++;
    }
}

// Pad-herschrijving
const toAbsolute = (value, pageDir) => {
    const v = value.trim();
    if (v === '' || /^(#|mailto:|tel:|https?:|data:|javascript:)/i.test(v)) return v;
    let p = v;
    const vraag = p.match(/[?#].*$/)?.[0] ?? '';
    p = p.replace(/[?#].*$/, '');
    // Media-map eerst (assets/video/sugar.webm → storage/cms-assets/...)
    const kaal = p.replace(/^(?:\.\.?\/)+/, '');
    if (/^assets\//.test(kaal)) p = resolveAsset(kaal);
    const url = new URL(p, 'https://site.local/' + (pageDir ? pageDir + '/' : ''));
    let path = url.pathname;
    if (/^\/storage\/cms-assets\//.test(path)) return path.replace('/storage/cms-assets/', '/site/assets/') + vraag;
    if (/^\/storage\/cms-images\//.test(path)) return path.replace('/storage/cms-images/', '/site/images/') + vraag;
    if (/^\/js\/cms-site\//.test(path)) return path.replace('/js/cms-site/', '/site/js/') + vraag;
    path = path.replace(/index\.html$/, '').replace(/\/+$/, '');
    return (path === '' ? '/' : path) + vraag;
};

const rewrite = (html, pageDir) => html
    .replace(/\b(href|src|poster|data-webm|data-mp4|data-live-url)="([^"]*)"/g, (m, attr, val) => {
        if (attr === 'data-live-url') return `${attr}="/live.json"`;
        return `${attr}="${toAbsolute(val, pageDir)}"`;
    })
    .replace(/\bsrcset="([^"]*)"/g, (m, val) => `srcset="${val.split(',').map((s) => { const [u, w] = s.trim().split(/\s+/); return [toAbsolute(u, pageDir), w].filter(Boolean).join(' '); }).join(', ')}"`)
    .replace(/url\((['"]?)([^)'"]+)\1\)/g, (m, q, val) => `url(${q}${toAbsolute(val, pageDir)}${q})`)
    .replace(/\bdata-base="[^"]*"/g, 'data-base="/"')
    .replace(/\bdata-idx="[^"]*"/g, 'data-idx=""');

const between = (html, startRe, endStr) => {
    const s = html.search(startRe);
    if (s < 0) return '';
    const e = html.indexOf(endStr, s);
    return html.slice(s, e + endStr.length);
};

// 2) Gedeelde onderdelen uit index.html (pagina in de root)
const headScript = (index.match(/<script>\(function\(\)\{var c=document[\s\S]*?<\/script>/) || [''])[0];
const header = between(index, /<header class="nav"/, '</header>');
const footer = between(index, /<footer class="site-footer"/, '</footer>');
const widget = between(index, /<aside id="gbw"/, '</aside>').replace(/<script type="application\/json" id="gb-live">[\s\S]*?<\/script>/, '<script type="application/json" id="gb-live">{!! $liveJson !!}</script>');
const loader = between(index, /<div id="loader"/, '</div></div>').replace(/<div class="l-bg">[\s\S]*$/, '') ;
const loaderFull = (index.match(/<div id="loader"[\s\S]*?<div class="l-pct" id="lPct">0%<\/div><\/div>/) || [''])[0];
const cursor = (index.match(/<div class="c-dot"[\s\S]*?<div id="sBar" aria-hidden="true"><\/div>/) || [''])[0];

writeFileSync(join(out.partials, 'head-script.blade.php'), headScript + '\n');
writeFileSync(join(out.partials, 'header.blade.php'), '{{-- Gegenereerd door tools/site-import.mjs; niet met de hand bewerken. --}}\n' + rewrite(header, '').replace('<li><a href="/sponsoring">Sponsoring</a></li>', '$&<li><a href="/blog">Blog</a></li>') + '\n');
// De nieuwsbrief (dubbele opt-in in de app) krijgt een plek boven "Een vraag?".
writeFileSync(join(out.partials, 'footer.blade.php'), '{{-- Gegenereerd door tools/site-import.mjs; niet met de hand bewerken. --}}\n' + rewrite(footer, '').replace('<li><a href="/pers">', '<li><a href="/blog">Blog</a></li>$&').replace(/&copy; 2026/, '&copy; {{ now()->year }}').replace('<div class="footer-ask">', "@include('site.partials.nieuwsbrief')\n<div class=\"footer-ask\">") + '\n');
writeFileSync(join(out.partials, 'widget.blade.php'), '{{-- Gegenereerd door tools/site-import.mjs; $liveJson komt uit App\\Support\\Site\\LiveData. --}}\n' + rewrite(widget, '') + '\n');
writeFileSync(join(out.partials, 'loader.blade.php'), rewrite(cursor + '\n' + loaderFull, '') + '\n');

// 3) Pagina's
const pages = [];
const walk = (dir) => {
    for (const f of readdirSync(dir)) {
        const p = join(dir, f);
        if (statSync(p).isDirectory()) { if (!['storage', 'js'].includes(f)) walk(p); }
        else if (f === 'index.html' || f === '404.html') pages.push(p);
    }
};
walk(source);

const attr = (html, re) => (html.match(re) || [, ''])[1].replace(/&quot;/g, '"');
const manifest = [];

for (const file of pages) {
    const html = read(file);
    const relDir = relative(source, dirname(file)).split(sep).join('/');
    const pageDir = file.endsWith('404.html') ? '' : relDir;
    const slug = pageDir === '' ? (file.endsWith('404.html') ? '404' : 'home') : pageDir.replace(/\//g, '-');
    if (slug === '404' && pages.some((p) => p.endsWith('404/index.html')) && file.endsWith('404.html')) continue;
    const main = between(html, /<main id="main"/, '</main>');
    if (!main) { console.warn('geen <main> in', file); continue; }
    const mainClass = attr(main, /^<main id="main" class="([^"]*)"/);
    const inner = main.replace(/^<main[^>]*>/, '').replace(/<\/main>$/, '').trim();
    const title = attr(html, /<title>([^<]*)<\/title>/).replace(/\s*\|\s*De Gouden Bol$/, '');
    const description = attr(html, /<meta name="description" content="([^"]*)"/);
    // "@context" is in Blade een directive (@context); in JSON daarom als @@context schrijven.
    const ld = (html.match(/<script type="application\/ld\+json">[\s\S]*?<\/script>/g) || []).filter((s) => !/"@type": "(Organization|WebSite)"/.test(s)).map((s) => s.replace(/"@/g, '"@@'));
    const body = rewrite(inner, pageDir);

    if (/^provincies-/.test(slug)) {
        const foto = (body.match(/<section class="sec-tight" aria-label="Beeld">[\s\S]*?<\/section>/) || [''])[0];
        writeFileSync(join(out.fotos, slug.replace('provincies-', '') + '.blade.php'), '@verbatim\n' + foto + '\n@endverbatim\n');
    }

    const view = `{{-- Gegenereerd door tools/site-import.mjs uit ${relDir || '.'}/index.html; niet met de hand bewerken. --}}
<x-layouts.site title="${title.replace(/"/g, '&quot;')}" description="${description.replace(/"/g, '&quot;')}" main-class="${mainClass}">
${ld.length ? '    <x-slot:structured>\n' + ld.join('\n') + '\n    </x-slot:structured>\n' : ''}@verbatim
${body}
@endverbatim
</x-layouts.site>
`;
    writeFileSync(join(out.pages, slug + '.blade.php'), view);
    manifest.push({ slug, path: pageDir === '' ? '/' : '/' + pageDir, title, mainClass });
}

writeFileSync(join(out.pages, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log(`${copied} assets gekopieerd, ${manifest.length} pagina's geschreven, partials bijgewerkt.`);

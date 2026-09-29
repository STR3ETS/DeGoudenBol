# 06 – Publiekssite: pagina's, live data, teksten, SEO

De drie concepten zijn samen "Het Register Soft" in drie paginatypes: homepage (c1), bakkersoverzicht (c2), bakkersprofiel (c3). In de software worden het live templates die hun inhoud uit de database halen. In 2026 rendert het platform de hele publiekssite; moeten contentpagina's ooit in het Eazyonline CMS, dan draaien `/provincie`, `/bakker`, `/finale`, `/bon`, `/erkenning` op hetzelfde domein via een reverse proxy.

## 1. Paginakaart

| URL | Pagina | Basis | Wat er live uit het platform komt | Structured data |
|---|---|---|---|---|
| `/` | Home | Concept 1 (volledige layout gebouwd op 24 sep 2026) | Hero, ticker met editiefeiten, statistiekstrip, "Over de keuring" met beeld-placeholder, Voorlijst-blok (nu: 12 provinciekaarten met plekken; na de eerste publicatie: Top-kaarten), "Hoe wij keuren" met onderdelen uit het beoordelingsmodel en FAQ uit de instellingen, beeldblok, "Het panel" zonder namen, citaten (alleen met `SITE_PLACEHOLDERS`), aanmeldblok met formulier-preview tot de inschrijving opent, donkere CTA. Later: Top 3-widget, sponsoren, nieuws | `Organization`, `WebSite` |
| `/provincies` | Alle provincies | Nieuw (gebouwd 24 sep) | Twaalf kaarten met aangemeld en vrije plekken | `ItemList` |
| `/provincie/{slug}` ×12 | Provinciepagina (gebouwd 24 sep: deelnemers en plekken; Voorlijst volgt in release 2) | Ranglijstblok uit c1 | Direct antwoord ("De beste oliebol van Gelderland 2026 komt van …"), Voorlijst / Voorlopige of Definitieve Top 10 met labels (Nieuw/Gestegen/Gedaald), overige ≥ 5,0, provinciepartner, persbericht, lege staat vóór de eerste publicatie | `ItemList`, `BreadcrumbList` |
| `/bakkers` | Alle bakkers (gebouwd 24 sep) | Concept 2 | Filter op alle 12 provincies, zoeken op naam/plaats, type bedrijf, cijfer; alleen gepubliceerde deelnemers (optioneel: "deelnemer" zonder cijfer) | `ItemList` |
| `/bakker/{slug}` | Bakkersprofiel (gebouwd 24 sep, alleen voor bevestigde deelnemers; foto's, kaart en cijfer volgen) | Concept 3 | Verhaal, foto's, uitslag (cijfer; deelscores alleen Definitieve Top 10), erkenningen, openingstijden met "nu open", kaart + route, "Bakt met [sponsor]", deelknoppen (Web Share), dynamische OG-image | `LocalBusiness` (+ `award`, adres, openingstijden, geo), `BreadcrumbList` |
| `/finale` | Landelijke finale | Nieuw | Finalisten (12), landelijke lijst (Top 5/10), winnaarsverhaal, sponsor "landelijke toppositie" | `ItemList` |
| `/hoe-werkt-de-test` | Hoe werkt de test (gebouwd 24 sep) | Sectie uit c1 |
| `/het-panel` | Het panel (gebouwd 24 sep) | Nieuw | Regels van het blind protocol; namen pas na de editie | – | 100-puntenmodel (8 onderdelen uit de editie-instellingen), blind protocol, publicatieritme, FAQ | `FAQPage` |
| `/aanmelden` | Aanmelden (gebouwd 24 sep: vijf stappen, PDOK, betaling, statuspagina) | Formulier uit c1, wordt meerstaps | Beschikbare plekken per provincie, pakketten, voorwaarden, betaling (Mollie) | – |
| `/goede-doelen` | Goede doelen (gebouwd 29 sep) | Nieuw | Transparantie-overzicht per doel: grondslag, bedrag, selectie, uitbetaaldatum; regionale potten; totalen | – |
| `/sponsoren` | Sponsoren (gebouwd 29 sep) | Nieuw | Partners en plaatsingen (gelabeld als sponsor), catalogus met tarieven, contact | – |
| `/nieuws`, `/nieuws/{slug}` | Nieuws (gebouwd 24 sep) | Nieuw | Nieuwsberichten, video | `NewsArticle` |
| `/pers`, `/pers/{slug}`, `/pers/kit/{slug}` | Pers (gebouwd 29 sep) | Nieuw | Persberichten per provincie en landelijk; embargo-perskit via tijdelijke ondertekende link (`signed`, 14 dagen), na de reveal redirect naar het openbare bericht | – |
| `/bon/{token}` | De bon zelf + statuscheck (gebouwd 28 sep; `/cadeaubon/claim/{token}` is de claimpagina) | Nieuw | QR, bonnummer, status, ondernemer, geldigheid (geen persoonsgegevens); printbaar; rate limited | – |
| `/winnaars` | Cadeaubonwinnaars (gebouwd 28 sep) | Nieuw | Per ondernemer voornaam + initiaal, alleen met toestemming | – |
| `/erkenning/{code}` | Badge-verificatie (gebouwd 27 sep; `/erkenning` is de zoekpagina, `/erkenning/{code}/badge.svg` de badge licht/donker) | Nieuw | Bedrijf, categorie, jaar, geldigheid; "historisch" na afloop; embed-bron | – |
| `/editie/{jaar}` | Archief | Nieuw | Uitslagen 2026, 2027 … blijven vindbaar | `ItemList` |
| `/voorwaarden`, `/privacy` (gebouwd 24 sep), `/actievoorwaarden` (28 sep) | Juridisch | Nieuw | Teksten van de jurist (versiebeheer) | – |
| `/stijlgids` | Stijlgids | Nieuw | Alle tokens en componenten; `noindex`, alleen lokaal/acceptatie | – |

**Navigatie**: Voorlijst (dropdown 12 provincies) · Alle bakkers · Hoe werkt het · Finale · Nieuws · [Aanmelden als bakker]. De "Jury"-link uit de concepten vervalt (panel pas na de editie tonen). Footer: Ranglijst (per provincie, finale, archief) · Deelnemers (aanmelden, hoe werkt het, erkenningen) · Over (goede doelen, sponsoren, pers, contact) · Juridisch.

**Provincies (slugs)**: drenthe, flevoland, friesland, gelderland, groningen, limburg, noord-brabant, noord-holland, overijssel, utrecht, zeeland, zuid-holland.

**Placeholderbeleid.** Beeld dat nog ontbreekt rendert via `x-ui.placeholder-image` als neutraal merkvlak (zand, dot-grid, logo); met `SITE_PLACEHOLDERS=true` (alleen lokaal/acceptatie) krijgt het een gele markering en verschijnen ook blokken die pas met echte inhoud mogen, zoals citaten. Op productie staat de vlag uit, dus daar bestaat het citatenblok niet tot er geverifieerde citaten zijn. Navigatie en footer gebruiken `config/site.php`: een link toont pas als de route bestaat, of tijdelijk als anker op de homepage (`fallback`).

## 2. Wat in de concepttekst moet veranderen (vóór release 1)

| Concepttekst | Probleem | Wordt |
|---|---|---|
| "Gratis deelname", "Geen kosten voor aanmelding", "gratis & anoniem gekeurd" | Botst met betaalde pakketten | Prijs/pakket uit de editie-instellingen |
| "Vijf criteria", deelscores Deeg/Kleur/Vulling/Suikerlaag/Totaalbeleving | Model is 8 onderdelen, 100 punten | Onderdelen uit het actieve beoordelingsmodel |
| "Keurmerk bij score 8,5+" | Onjuist | "Officieel getest" vanaf 5,0 |
| "Opgericht in 2016", "247 kramen", "9+ jaar keuring", "18 juryleden", "Ranglijst 2024", "Editie 2025" | Verzonnen | Alleen echte, geverifieerde cijfers; tellers live uit de database |
| Juryleden met naam, foto en functie | Nodigt uit tot beïnvloeding | Sectie "Het panel" zonder namen; namen pas na de editie (instelling) |
| Citaten van bakkers, klantreacties met sterren, zwevende reviewkaarten in hero | Verzonnen; reviews vragen verificatie/moderatie (EU-regels) | Weglaten in 2026 (besluit 21) |
| "De keuringsdag is onbekend", "anoniem langs kramen" | Veronderstelt aankoop door de organisatie | Tekst volgt besluit 5 (advies: aanleveren op tijdslot) |
| "U ontvangt uw rapport binnen 4 weken" | Onbekend | Volgt het publicatieritme |
| Provinciekeuze mist Overijssel | Fout | Altijd de 12 provincies |
| "Michelin-gids voor de Nederlandse oliebol" | Merkgebruik derde | Laten toetsen; alternatief "het register voor de Nederlandse oliebol" |
| "Made with care by Eazyonline" | Prima | Blijft |

## 3. SEO, GEO en delen

- Vaste URL's zoals hierboven; slugs, nooit oplopende id's; canonical op elke pagina; sitemap.xml; `robots.txt` blokkeert portaal, panel, scan, admin, stijlgids.
- Structured data: `Organization`, `ItemList` per Top 10, `LocalBusiness` per profiel met adres, openingstijden, geo en award, `BreadcrumbList`, `NewsArticle`, `FAQPage`.
- Iedere provinciepagina opent met een direct antwoord in één zin, zodat zoekmachines en AI-assistenten De Gouden Bol als bron citeren.
- Dynamische OG-image per profiel met actuele status (Browsershot, gecachet per snapshot).
- Web Share API op profielen en in de socialkit; fallback kopieerlink.
- Titels/descriptions per paginatype uit een template met editiejaar en provincie.

## 4. Interactie die in de browser blijft werken op een gecachte pagina

- **Nu open**: JSON met openingstijden, seizoen en uitzonderingen in de pagina; JS rekent in `Europe/Amsterdam` (Intl API) en toont chip + markeert vandaag. Server-side fallback zonder JS: geen chip.
- **Filter en zoeken op /bakkers**: server-side gefilterde URL's (`?provincie=`, `?type=`, `?q=`) zodat elke filterstand cachebaar en deelbaar is; JS versnelt client-side.
- **Reveal-animaties**: IntersectionObserver, uit bij reduced motion.
- **Kaart**: Leaflet laadt lazy; PDOK/OSM-tegels; routeknop naar Google/Apple Maps.
- **Countdown/reveal 21 december**: per provincie een tijdstip; pagina toont tot dan de embargo-stand ("Uitslag Gelderland om 12:20") en herlaadt daarna.

## 5. Performance en toegankelijkheid

- Geen laadscherm, geen custom cursor; animaties respecteren `prefers-reduced-motion`.
- Fonts self-hosted (woff2, preload, `font-display: swap`).
- Eigen beeldpijplijn (WebP/AVIF, `srcset`, lazy).
- Volledige paginacache + CDN; publicatie ververst alleen geraakte pagina's.
- WCAG 2.2 AA: contrast volgens doc 02, focus-stijlen zichtbaar, tikdoelen ≥ 44 px, skip-link, landmarks, alt-teksten verplicht bij upload, formulieren met labels en foutmeldingen.
- QA-gate per release: SEO, GEO, WCAG 2.2 AA, performance (Lighthouse), security headers, privacy (cookies, embeds).

## 6. Componenten per pagina (verwijst naar doc 02 §5)

| Pagina | Componenten |
|---|---|
| Home | Nav, Hero, Statistiekstrip, Over-blok (beeld + checklist), Ranglijstkaarten (Top 3 landelijk of per provincie-selector), Stapkaarten, Beeldblok, Panel-sectie (zonder namen), Aanmeld-CTA (twee kolommen), Donkere CTA, Footer, Sticky Top 3-float (optioneel) |
| Provincie | Nav, Pagina-hero met direct antwoord, Ranglijstkaarten 1–10 met labels, tabel/lijst 11+, Provinciepartner-blok, Persbericht-link, Lege staat, Donkere CTA, Footer |
| Alle bakkers | Nav, Pagina-hero met tellers, Filterbalk, Bakkerkaarten-grid, Lege staat, Donkere CTA, Footer |
| Profiel | Nav, Profiel-hero, Profiel-layout (verhaal, Scoreblok, Galerij, Erkenningen, "Bakt met") + sidebar (Openingstijden, Locatie & kaart, Over de bakkerij, knoppen), Footer |
| Finale | Nav, Pagina-hero, Finalistenkaarten, Landelijke lijst, Winnaarsverhaal, Footer |
| Aanmelden | Nav, Stapindicator, Formulierpaneel per stap, Samenvatting, Betaling, Footer |

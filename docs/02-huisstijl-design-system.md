# 02 – Huisstijl en design system: "Het Register Soft"

De drie concepten zijn samen één ontwerp. Karakter: warm, ambachtelijk, "de Michelin-gids voor de oliebol". Roomwit papier, espresso-bruine tekst, goud als enig accent. Grote, lichte serif-koppen met cursief goud voor nadruk; een strakke sans voor alles daaromheen. Alles afgerond en zacht: pillen, kaarten van 24 px, goudgetinte schaduwen. Toon: positief, uitnodigend, chic maar niet stijf. De concepten spreken bakkers aan met "u/uw"; consumenten neutraal ("Wie bakt de beste bol van Nederland?").

Bron van waarheid voor waarden: `docs/tokens.json`. Dit document legt uit *waarom* en *hoe*.

## 1. Kleuren

| Token | Waarde | Gebruik |
|---|---|---|
| `goud` | `#C8860A` | Accent: knoppen, rangpillen, iconen, sterren, decoratieve blobs, lijnen. **Niet als tekst op lichte achtergrond.** |
| `goud-tekst` (nieuw) | `#8A5A00` | Goudkleurige tekst en links op room/zand/wit: eyebrows, cursieve nadruk in koppen, scorecijfers in tekst, "Bekijk profiel →". |
| `espresso` | `#1C0F03` | Primaire tekst, donkere secties, footer, nav-CTA hover. |
| `room` | `#FAF5EA` | Paginabasis. Ook tekst op espresso. |
| `zand` | `#F0E8D5` | Secties, stapkaarten, invoervelden-achtergrond, filterbalk, icoonvakjes. |
| `wit` | `#FFFFFF` | Kaarten en panelen op room/zand. |
| `gedempt` (aangepast) | `#6B5B4A` (was `#7A6A58`) | Secundaire tekst, labels, meta. |
| `goud-licht` | `#FEF3E2` | Achtergrond van scorepillen/chips, "vandaag"-rij in openingstijden. |
| `goud-tint` | `rgba(200,134,10,.15)` | Achtergrond van niet-top rangpillen (tekst dan `goud-tekst`). |
| `rand` | `rgba(28,15,3,.08)` | Kaartranden. |
| `rand-sterk` | `rgba(28,15,3,.10)` | Scheidingslijnen, nav-onderrand, statistiekstrip. |
| `invoer-rand` | `rgba(200,134,10,.20)` | 1,5 px rand van invoervelden; focus → `goud`. |
| `room-zacht` | `rgba(250,245,234,.60)` | Lopende tekst op espresso (≈ 7:1). |
| `room-meta` | `rgba(250,245,234,.45)` | Footerlinks, meta op espresso (alleen ≥ 14 px). |
| `room-decor` | `rgba(250,245,234,.25)` | Kolomtitels footer, ©-regel, glas-randen. Decoratief, geen essentiële info. |

**Statuskleuren (nieuw, warm, getoetst op contrast)**

| Status | Tekst | Achtergrond | Contrast | Gebruik |
|---|---|---|---|---|
| succes | `#2F6B3A` | `#E6F2E7` | 5,6:1 | Verzilverd, betaald, gepubliceerd, "Nu open" |
| waarschuwing | `#8A5A00` | `#FEF3E2` | 5,4:1 | Al gebruikt, versheid bijna verlopen, wacht op goedkeuring |
| fout | `#9B2C2C` | `#FBE9E7` | 6,4:1 | Ongeldig, geweigerd, versheid verlopen |
| info | `#2C5A7A` | `#E8F0F6` | 6,4:1 | Ingepland, in behandeling |
| neutraal | `#5A5A6A` | `#ECE9E2` | 5,6:1 | **Gedaald** (nooit rood), verlopen bon, historisch, "Gesloten" |

Concept 3 gebruikt koel groen/rood (`#15803d`, `#b91c1c`) voor open/gesloten; dat vervangen we door bovenstaande warme varianten.

**Contrast (WCAG 2.2 AA, berekend met de relatieve-luminantieformule)**

| Combinatie | Ratio | Oordeel |
|---|---|---|
| goud op room | 2,81:1 | ✗ → gebruik `goud-tekst` (5,45:1) |
| room op goud (knop) | 2,81:1 | ✗ → **espresso tekst op gouden knop** (6,14:1) |
| gedempt oud `#7A6A58` op zand | 4,27:1 | ✗ → `#6B5B4A` (5,34:1) |
| `#6B5B4A` op room / wit | 6,00 / 6,52:1 | ✓ |
| `goud-tekst` op zand / wit / goud-licht | 4,86 / 5,93 / 5,40:1 | ✓ |
| espresso op room / zand | 17,3 / 15,4:1 | ✓ |
| goud op espresso | 6,14:1 | ✓ (goud als tekst op donker mag wél) |

Consequentie voor knoppen: de gouden pil krijgt **espresso tekst** in plaats van room. De hover-variant (espresso schuift in) krijgt dan room tekst; dat klopt qua contrast (17:1).

## 2. Typografie

- **Kopletter:** Cormorant Garamond 300, 400, 600, 700 + italic 300/400/600. Zelf hosten als woff2 in `public/fonts/` (open source, geen Google-request).
- **Broodletter:** Manrope 300–800. Zelf hosten.
- **E-mail-fallback:** Georgia (koppen), Arial (tekst).
- `font-display: swap`; preload van de twee meest gebruikte bestanden (Cormorant 300 + Manrope 400).

| Rol | Font | Grootte | Gewicht | Extra |
|---|---|---|---|---|
| Display H1 (home) | Cormorant | `clamp(3rem, 7vw, 6.8rem)` | 300 | lh 1.1; `<em>` italic 600 in `goud-tekst` |
| Pagina H1 | Cormorant | `clamp(2.4rem, 5vw, 4.8rem)` | 300 | lh 1.1 |
| H2 | Cormorant | `clamp(2rem, 4vw, 3.4rem)` | 600 | lh 1.2; `<em>` italic goud-tekst |
| H3 / kaarttitel | Cormorant | 1.35–1.8rem | 600 | |
| Groot cijfer (score, positie, teller) | Cormorant | 2.4–4rem | 700 | lh 1 |
| Watermerk-nummer (01, 02) | Cormorant | 3–4rem | 700 | `goud` op 10–18 % dekking |
| Eyebrow | Manrope | .65rem | 700 | uppercase, letter-spacing .26em, `goud-tekst` |
| Nav-link | Manrope | .8rem | 600 | uppercase, ls .06em |
| Body | Manrope | .97rem | 400 | lh 1.82, `gedempt` |
| Klein / meta | Manrope | .72–.78rem | 500–700 | |
| Formulierlabel | Manrope | .68rem | 700 | uppercase, ls .12em, `gedempt` |
| Knop | Manrope | .82rem | 700 | uppercase, ls .08em |
| Chip / pil | Manrope | .68–.75rem | 700–800 | |
| Citaat / jurynotitie | Cormorant | 1.05–1.1rem | 400 italic | lh 1.7 |

Portaal en backoffice: serif alleen voor paginatitels en grote cijfers; alle overige tekst Manrope. Panel-app: geen serif behalve het testnummer.

## 3. Vorm, schaduw, beweging, raster

**Radius:** pil 99 px · veld 12 px · klein blok/checklist-rij 16 px · kaart 24 px · paneel/hero-beeld/formulierpaneel 28 px · icoonvak 10 px · kaart-in-kaart (kaartje, review) 20 px.

**Randen:** 1 px `rand` op kaarten; 1 px `rand-sterk` als scheidingslijn; invoer 1,5 px `invoer-rand`, focus `goud`; outline-knop 1,5 px espresso.

**Schaduw (zacht, goudgetint):**
- kaart-hover: `0 20px 48px rgba(200,134,10,.12)`
- knop-hover: `0 12px 36px rgba(200,134,10,.32)`
- zwevende kaart: `0 12px 40px rgba(28,15,3,.10), 0 2px 8px rgba(28,15,3,.06)`
- paneel: `0 20px 60px rgba(28,15,3,.15), 0 4px 16px rgba(28,15,3,.07)`
- uitgelicht gouden blok: `0 12px 36px rgba(200,134,10,.30)`

**Beweging:** hover `translateY(-6px … -8px)` + `scale(1.01)`, `cubic-bezier(.16,1,.3,1)` 0,3 s. Reveal bij scrollen: opacity 0→1 en 28 px omhoog, 0,7 s, via IntersectionObserver. Alles uit bij `prefers-reduced-motion: reduce`. Geen laadscherm, geen custom cursor, geen zwevende blobs die de aandacht trekken (blobs mogen statisch of heel traag).

**Raster:** max 1360 px · marge 48 px (mobiel 24 px) · sectie 96 px verticaal (subpagina's 80 px) · grids 2/3/4 kolommen met gap 24 px (two-col 72 px) · breekpunten 640 / 768 / 1024 / 1200 px. Sticky elementen: filterbalk top 72 px, profiel-sidebar top 100 px.

## 4. Logo

Cirkel-ster, inline SVG 48×48. Buitencirkel `goud` r 23, binnencirkel `room` r 16 (op donkere achtergrond `espresso`), vijfpuntige ster `goud`. Naast het beeldmerk: "De Gouden Bol" in Cormorant 600 en de tagline "Oliebollenkeuring" in Manrope .55rem uppercase ls .18em `gedempt`.

```svg
<svg viewBox="0 0 48 48" width="44" height="44" fill="none">
  <circle cx="24" cy="24" r="23" fill="#C8860A"/>
  <circle cx="24" cy="24" r="16" fill="#FAF5EA"/>
  <polygon points="24,11 26.5,19 35,19 28.5,23.5 31,32 24,27.5 17,32 19.5,23.5 13,19 21.5,19" fill="#C8860A"/>
</svg>
```

Echte logobestanden ontbreken; dit is de werkversie. Het logo komt terug op badges, socialkits, drukwerk, e-mail en favicon.

## 5. Componentenbibliotheek (uit de drie concepten)

| # | Component | Beschrijving | Bron |
|---|---|---|---|
| 1 | **Nav** | Fixed; transparant, bij scroll > 60 px room 96 % + blur 16 px + `rand-sterk`. Logo links, links midden uppercase, CTA-pil rechts. Mobiel: hamburger + volledig menu (concept verbergt links; wij bouwen een menu). | c1–c3 |
| 2 | **Hero home** | Gecentreerd; watermerk "BOL" 5 % goud; eyebrow met sterren; H1 in drie regels; sub; twee knoppen; scroll-indicator. Zwevende reviewkaarten vervallen (reviews niet in 2026). | c1 |
| 3 | **Ticker** | Espresso strip, uppercase 70 % room, ster-separators, langzaam scrollend. Alleen met echte feiten; pauzeert bij hover en bij reduced motion. | c1 |
| 4 | **Statistiekstrip** | Zand, 4 kolommen gescheiden door `rand-sterk`, groot Cormorant cijfer + uppercase label. Gevoed door live tellers. | c1 |
| 5 | **Ranglijstkaart** | Wit, 24 px, positienummer als 4rem watermerk rechtsboven, rangpil, naam Cormorant 1.4rem, plaats · provincie, cursieve notitie onder lijn. | c1 |
| 6 | **Rangpil** | Top 3: solid `goud` met espresso tekst. Overig: `goud-tint` met `goud-tekst`. Formaat "★ 9,4". | c1–c3 |
| 7 | **Stapkaart** | Zand, 36 px padding, watermerknummer 18 %, H3, tekst, tag-pil. | c1 |
| 8 | **Knoppen** | `btn-pill` (goud, espresso tekst, espresso schuift in bij hover); `btn-pill-out` (1,5 px espresso rand); `btn-ghost` (12 px radius, rand 25 %); donkere variant (rand `room-decor`, tekst `room-zacht`, hover goud). | c1–c3 |
| 9 | **Eyebrow** | Zie typografie. | alle |
| 10 | **Checklist-rij** | Zand of wit, 16 px, ster/pijl in goud, tekst 600. | c1 |
| 11 | **Testimonialkaart** | Wit of espresso, groot aanhalingsteken 4.5rem goud, cursief citaat Cormorant, naam + meta. Alleen met echte, geverifieerde citaten. | c1 |
| 12 | **Beeldblok** | 28 px, gradient-overlay espresso 18→62 %, titel room met cursief goud, knop wit. | c1 |
| 13 | **Formulier** | Label uppercase; input wit 12 px 1,5 px `invoer-rand`; select idem; submit pil breed; hulptekst .72rem. Wordt meerstaps (stapindicator met pillen). | c1 |
| 14 | **Donkere CTA-sectie** | Espresso, statische blob of dot-grid (`radial-gradient` goud 8 %, 32 px), eyebrow goud, H2 room met em goud, twee knoppen. | c1, c2 |
| 15 | **Footer** | Espresso; grid 2fr 1fr 1fr 1fr; kolomtitels `room-decor` uppercase; links `room-meta`; onderregel met © en "Made with care by Eazyonline" in goud. | alle |
| 16 | **Sticky Top 3-float** | Rechtsonder, espresso header, drie rijen met positie-watermerk en rangpil, inklapbaar tot pil; verdwijnt bij aanmeldsectie. Kandidaat voor provinciepagina. | c1 |
| 17 | **Filterbalk** | Zand, sticky, filterpillen (actief = goud met espresso tekst), zoekveld als pil rechts, resultaatteller. | c2 |
| 18 | **Bakkerkaart** | Wit 24 px; foto 220 px met gradient; score-badge rechtsboven; type-badge glas linksonder; provincie-eyebrow; naam; plaats; tagline; footer met chip + "Bekijk profiel →". | c2 |
| 19 | **Profiel-hero** | 70vh beeld, gradient naar espresso 88 %; breadcrumb; naam H1 room met em goud; meta-pillen (score, erkenning glas, plaats, statuschip); ronde glas-deelknoppen. | c3 |
| 20 | **Profiel-layout** | `1fr 360px`, sidebar sticky; < 1024 px sidebar als 2-koloms grid onder de inhoud. | c3 |
| 21 | **Infokaart** | Wit 24 px 28 px padding; titel Cormorant 1.1rem met onderlijn; rijen met icoonvak 32 px zand 10 px + label uppercase + waarde. | c3 |
| 22 | **Openingstijden** | Rijen dag/tijd met `rand-sterk`; vandaag gemarkeerd `goud-licht` 8 px; statuschip "Nu open"/"Gesloten" (succes/neutraal); seizoensnotitie in zand 12 px. | c3 |
| 23 | **Scoreblok** | Zand 24 px, 36 px padding; eyebrow; titel; totaalcijfer 4rem `goud-tekst`; balken 6 px zand → goud (animatie alleen zonder reduced motion); notitie cursief. Aanpassen naar 8 onderdelen; deelscores alleen voor Definitieve Top 10 (besluit 6). | c3 |
| 24 | **Galerij** | 3 kolommen, 12 px gap, 16 px radius, eerste item `span 2`; mobiel 2 kolommen. | c3 |
| 25 | **Kaartblok** | 20 px radius, 220 px hoog, Leaflet + PDOK-tegels, routeknop als pil. | c3 |
| 26 | **FAQ-accordeon** | Vraag 600 .96rem; plus-cirkel zand → roteert 45° en wordt goud bij open. | c1 |
| 27 | **Reviewkaart** | Sterren + klantreactie. **Niet bouwen in 2026** (besluit 21). | c3 |

Nieuw te ontwerpen (niet in concepten): stapindicator aanmelden, tijdlijn inschrijving (portaal), statuschips-set, tabellen (portaal/backoffice), toast/melding, lege staat ("Nog geen gepubliceerde resultaten in Drenthe"), embargo-banner, scorekaart panel-app, scanresultaatscherm, badge-templates, socialkit-templates, e-mailtemplate, pdf-templates (bon, poster, sticker, toonbankkaart, certificaat).

## 6. Wat uit de concepten NIET meegaat

- Laadscherm van ~2 s en de custom cursor (`cursor:none`).
- Google Fonts-links → zelf hosten.
- Unsplash-beelden → eigen beeldpijplijn (WebP/AVIF, meerdere formaten).
- GSAP + ScrollTrigger als dependency → eigen lichte reveal-script.
- Inline styles → Tailwind v4 utilities + componentklassen op tokens.
- Verzonnen inhoud: "247 kramen", "9+ jaar keuring", "18 juryleden", "opgericht in 2016", juryleden met foto's, citaten, klantreacties, "Ranglijst 2024", "Editie 2025".
- Koel groen/rood → warme statustokens.
- "Gratis deelname" / "Geen kosten voor aanmelding" (botst met betaalde pakketten), "vijf criteria" (→ 8 onderdelen, 100 punten), "keurmerk bij 8,5+" (→ "Officieel getest" vanaf 5,0), "de keuringsdag is onbekend" (hangt af van besluit 5; advies is aanleveren op tijdslot).
- Jury met namen en foto's tijdens het seizoen → panel pas na de editie tonen.
- Provincielijst in het formulier mist Overijssel → altijd de 12.
- Cloudflare e-mail-obfuscation script.

## 7. Per oppervlak

| Oppervlak | Regels |
|---|---|
| **Publiekssite** | Volle expressie van het concept, zonder laadscherm en cursor. Animaties subtiel en respecteren reduced motion. |
| **Portaal** | Zelfde tokens, rustiger. Serif alleen voor paginatitels en grote cijfers (score, positie). Kaarten, pilknoppen, statuschips uit het concept. Meer witruimte, minder decoratie. |
| **Backoffice (Filament)** | Eigen thema in `resources/css/filament/admin/theme.css` (gebouwd 24 sep, herontworpen 25 sep 2026) naar het voorbeeld van het VastgoedFotoVideo-redesign, in onze tokens. **Sidebar:** espresso met verloop en onderin een stapel oliebollen met poedersuiker als gedempte decoratie (inline SVG als CSS-achtergrond, tegenhanger van de skyline in het voorbeeld); menu-items als afgeronde rijen met icoonvakje; actief item goud met espresso tekst en zachte gloed (blijft goud bij hover, de hover-regel voor gewone rijen mag er niet overheen); geen overgang bij in- en uitklappen, want Filament klapt de sidebar zonder animatie in en de donkere kolom bovenin moet meteen meeschuiven; aantallen als gouden pil (`getNavigationBadge` op profielmoderatie, bezwaren, correctiedossiers); eigen voet met "Instellingen" (huidige editie) en "Inklappen" (render hook `SIDEBAR_FOOTER`, view `filament/sidebar/voet`); het merk staat bovenin de donkere kolom (topbar-start), ingeklapt alleen het beeldmerk. **Topbar:** transparant met witte pillen: zoekveld met sneltoets ⌘K/Ctrl+K, bel met het aantal openstaande acties (hook `GLOBAL_SEARCH_AFTER`, view `filament/topbar/bel`, linkt naar `#acties`), profielpil met initialen, naam, rol en pijltje (override `resources/views/vendor/filament-panels/components/avatar/user.blade.php`, geen externe avatardienst). **Ondergrond** room/zand-mix met twee gouden radial gradients; de inhoud is altijd de volle breedte (`maxContentWidth(Width::Full)`), nooit een max-width. **Pagina's:** titel en begroeting in Cormorant 700; kaarten, tabellen, secties, modals en dropdowns wit met radius 24 px (dropdown 16 px) en zachte schaduw; tabs als gesegmenteerde pil met gouden actief item; kolomkoppen en veldlabels klein en uppercase; knoppen als pil (primair espresso op goud, hover espresso; secundair wit met schaduw); inlogpagina in dezelfde stijl. **Navigatie op werk, niet op datamodel** (besluit Raphael 25 sep 2026): vijf groepen in de volgorde van het seizoen, Bakkers (bedrijven, inschrijvingen, accounts, profielteksten keuren), Testdagen (ontvangst, aanleverslots, testsessies, panelleden, scorecontrole), Uitslag (publicaties, terugkoppeling onder 5,0, bezwaren, beslisrondes, finale, correcties na publicatie), Campagne (nieuws, badges, cadeaubonnen, persberichten, mediacontacten, nieuwsbrief) en Geld (orders, facturen, pakketten en prijzen, sponsoren, sponsorproducten, goede doelen). Menunamen zijn taakwoorden, geen entiteitsnamen. Bij het eerste bezoek staat alleen de groep van de huidige fase open plus de groepen van de eigen rollen (`AdminPanelProvider::GROUP_ROLES`); daarna onthoudt de browser de eigen toestand (bij een nieuwe menu-indeling hoogt `dgbMenuVersie` in `filament/head/navigatie` op). Editie, provincies, beoordelingsmodel, voorwaarden, testlocaties en medewerkers staan niet in het menu maar op de hubpagina **Instellingen** (`App\Filament\Pages\Settings`, `/admin/instellingen`, via de voet van de sidebar). **Werkvoorraad-tabs** met tellers op inschrijvingen, scorecontrole, orders, facturen, profielteksten en bezwaren (`getTabs()`); de wachtrij is de standaardtab waar dat logisch is. **Rij-acties in tabellen zijn altijd icoonknoppen** met het label als tooltip (globaal via `Table::configureUsing` en `modifyUngroupedRecordActionsUsing` in `AdminPanelProvider::boot`): tekstlinks namen te veel breedte in en gaven horizontale scrollbalken. Een eigen rij-actie moet dus altijd een `->icon()` hebben. Gegroepeerde acties (dropdown) en kopacties blijven knoppen met tekst. Niets in de inhoud mag breder worden dan de inhoudskolom: kinderen van `.fi-page-content` hebben `min-width: 0; max-width: 100%`, zodat een lange tabrij binnen de pil scrolt in plaats van uit te steken. **Zoekpalet** (Ctrl+K / ⌘K, `App\Filament\Search\CommandPaletteSearchProvider`): eerst pagina's (menunamen plus synoniemen zoals "batch", "monster", "bon"), dan acties ("Nieuwe testsessie"), dan records; bedrijven ook op KvK-nummer. **Paginawissels** via Filament SPA-modus met prefetch bij hover: gouden voortgangsbalk, inhoud dimt na 120 ms, laadpil "Pagina laden…" boven de inhoud, nieuwe pagina schuift zacht in (`html.dgb-laden` / `html.dgb-binnen`, script in `filament/head/navigatie`); de bel staat daarom via `TOPBAR_END` buiten het persistente topbar-blok en `.fi-topbar-end` is `display: contents` zodat de volgorde zoeken, bel, profiel blijft. **Laden binnen een pagina** (tab, filter, sorteren, zoeken, pagineren): een Livewire `commit`-hook zet `.dgb-bezig` op de component zolang het verzoek loopt; de tabel dimt met een shimmer, er staat een laadpil "Bezig met laden…" over de tabel en alles krijgt een wachtcursor, na 100 ms zodat snelle antwoorden niet flikkeren. **Dashboard** (`App\Filament\Pages\Dashboard` + `DashboardData`, per verzoek gedeeld via een scoped binding zodat bel, voet en pagina dezelfde cijfers gebruiken): seizoensbalk met fase (editiestatus gaat voor, anders de datums; `App\Filament\Support\Season`) en de negen processtappen met aantal en link; "Jouw werk vandaag" per rol met de knop erbij (`App\Filament\Support\WorkQueue`: leveringen vandaag, sessies vandaag, versheid, scorecontrole, correcties, publicatiebatch met vier-ogen-status, bezwaren, finalisten, profielteksten, winnaars, open orders, vervallen facturen, goede doelen, adressen zonder coördinaten); pastel kerncijfertegels met ronde gekleurde icoonknop, groot Cormorant-cijfer, "→ Bekijk alle" en watermerk-icoon (tinten goud / espresso / succes / info / waarschuwing via `color-mix` op de tokens); "Acties nodig" als getinte rijen met ronde icoonknop plus notitie met de eerstvolgende mijlpaal; mijlpalen met datumblok (weekdag, dagnummer, maand) en dagen-tot; financiën per inkomstenstroom als mini-tegels; recente aanmeldingen met staafjes per dag; plekken per provincie; "Snel naar" als lijst met chevrons. De verschijn-animatie van kaarten en tegels gebruikt `animation-fill-mode: backwards`, zodat de hover-verplaatsing daarna werkt en de animatie niet opnieuw afspeelt bij het verlaten van een tegel. Inschuif-animaties (pagina, kaarten, tegels) bewegen altijd van boven naar hun plek (negatieve `translateY`): een positieve verschuiving steekt tijdens de animatie onder de viewport uit en geeft een scrollbalk die na afloop weer verdwijnt. Klassen voor eigen onderdelen heten `dgb-*`: `dgb-tegel(--goud|--espresso|--succes|--info|--waarschuwing|--mini)`, `dgb-card`, `dgb-row(--tint --{toon})`, `dgb-rij-icoon`, `dgb-datum`, `dgb-notitie`, `dgb-pill`, `dgb-chip`, `dgb-balk`, `dgb-trend`. |
| **Panel-app** | Maximaal leesbaar: 18 px basis, tikdoelen ≥ 44 px, geen animatie, goud alleen als accent (actieve knop, lopend totaal). Testnummer groot in Cormorant 700. Eén monster per scherm. |
| **Scan-app** | Volledig scherm camera; resultaat groot, eenduidig en in statuskleur: verzilverd (succes) / al gebruikt (waarschuwing) / verlopen (neutraal) / ongeldig (fout). Handmatige invoer bonnummer als noodroute. |
| **Badges, socialkits, drukwerk** | SVG-templates; cirkel-sterlogo; Cormorant voor de titel; **jaartal en categorie altijd prominent**; licht en donker; print-ready pdf's (raamposter A3, raamsticker, toonbankkaart A5). |
| **E-mail** | Zelfde kleuren; Georgia/Arial-fallback; tabel-layout; knop als pil met espresso tekst op goud; logo bovenaan; max 600 px. |

Geen dark mode voor site, portaal en backoffice in 2026; alleen badges hebben een lichte en donkere variant.

## 8. Naamgeving van componentklassen

Componentklassen in `app.css` krijgen Nederlandse namen die nooit op een Tailwind-utility lijken: `kop-display`, `kop-pagina`, `kop-2`, `kop-3`, `btn-pill`, `rank-pill`, `chip`, `lege-staat`, `checklist-rij`. Patronen als `h-2`, `w-4`, `p-3`, `text-lg` zijn verboden als eigen klassenaam, omdat Tailwind v4 dan een utility met dezelfde selector genereert (bijvoorbeeld `h-2` = `height: 0.5rem`) die de component overschrijft.

## 9. Tokenbestand en stijlgids

`docs/tokens.json` is de bron. Bij de bouw genereert een klein script daaruit:
- `resources/css/tokens.css` – CSS-variabelen én Tailwind v4 `@theme`-blok
- Filament-kleurenconfiguratie (in de panelprovider)
- e-mail-inline-stijlen (`resources/views/mail/partials/styles.blade.php`)
- SVG-templates lezen de kleuren als variabelen

Een levende stijlgidspagina op `/stijlgids` (niet indexeren, alleen lokaal en acceptatie) toont alle tokens en componenten.

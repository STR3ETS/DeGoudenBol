# 08 – Bouwplan

Volgorde waarin ik bouw. Elk blok eindigt met tests en een werkende demo op https://degoudenbol.test. Boost-richtlijnen in `AGENTS.md` bepalen de Laravel-conventies; `docs/` bepaalt het domein.

## Voortgang

**24 september 2026 – Stap 0 (fundament) afgerond.** Wat er staat:

- Drie MySQL-databases met eigen gebruikers (`degoudenbol`, `degoudenbol_testing`, `degoudenbol_vault`) en connecties `mysql`, `testing`, `vault`; migratiemappen `database/migrations/{testing,vault}` worden automatisch geladen (migraties zetten zelf `$connection`). In de testsuite draaien alle drie op SQLite in-memory.
- Tijd: opslag in UTC (`APP_TIMEZONE=UTC`), weergave in `Europe/Amsterdam` via `App\Support\DutchTime` en `FilamentTimezone`. `App\Support\Models\DomainModel` zet elke aangeleverde datum eerst naar UTC.
- Tokens: `resources/design/tokens.json` → `php artisan design:tokens` → `resources/css/tokens.css` (Tailwind v4 `@theme`), `resources/css/fonts.css` en `public/css/fonts.css`. Fonts zelf gehost in `public/fonts` (uit `@fontsource`). Filament-kleuren komen uit dezelfde tokens.
- `resources/css/app.css` met alle componentklassen uit doc 02; Blade-componenten `x-ui.*` (logo, nav, footer, button, eyebrow, card, rank-pill, chip, field, select, empty-state, section-heading, stat) en layout `x-layouts.public`. Nav en footer tonen alleen links naar routes die bestaan (`config/site.php`).
- Publiekssite: `/` (homepage in de volledige concept-1-layout, alle cijfers en teksten uit de editie-instellingen; beeld en citaten als gemarkeerde placeholders via `SITE_PLACEHOLDERS`) en `/stijlgids` (alleen met `STYLEGUIDE_ENABLED=true`). Navigatie via `config/site.php` met `App\Support\SiteLinks` (routes of ankers).
- Les uit de eerste controle: eigen componentklassen mogen nooit op Tailwind-utilities lijken (`h-2` botste met `height`); kopklassen heten nu `kop-*`. Scroll-reveal werkt alleen met `html.js`, zodat inhoud zonder JavaScript zichtbaar blijft.
- Domein Editie: `Edition` (+ `EditionSettings` value object met cast), `Province`, `EditionProvince` (pivot: capaciteit, reveal), `ScoringModel`, `ScoringCriterion`, `TestLocation`, `TermsVersion`, enums voor status/voorwaarden/besluiten. Seeder voor de 12 provincies, editie 2026 met alle datums en instellingen, placeholder-beoordelingsmodel en lege voorwaardenversies.
- Domein Platform: `StaffRole` met verboden combinaties, `RoleAssignmentGuard` (gekoppeld aan `User::assignRole`/`syncRoles` en aan het Filament-rollenveld), append-only `AuditLog` met HMAC-hashketen (`AuditLogger`, `php artisan audit:verify [--hash]`).
- Backoffice: Filament 5.8 op `/admin` met verplichte TOTP-tweestapsverificatie, goud als primaire kleur, warm grijs, Manrope lokaal; resources voor edities (tabbladen: algemeen, datums, instellingen; provincies als relation manager), provincies, beoordelingsmodellen (onderdelen herschikbaar met puntentotaal), testlocaties, voorwaarden en medewerkers met rollen.
- Lokale accounts (`php artisan db:seed`): `{rol}@degoudenbol.test` / `password` voor admin, intake, coordinator, panelist, reviewer, publisher, communication, voucher_manager, finance.
- Tests: 42 (unit, feature, architectuur) groen; `vendor/bin/pint` schoon.

Nog niet gedaan uit stap 0 (bewust doorgeschoven): portaal-, panel-, scan- en mail-layouts (komen met hun release), Commerce-seeders voor pakketten en sponsorproducten (release 1), editie-gebonden rolverbod (nu globaal; wordt per editie zodra de panelpool in release 2 bestaat), overige pakketten (medialibrary, browsershot, responsecache, mollie, sanctum, sentry) worden per release toegevoegd.

**24 september 2026 – Release 1 (inschrijving) gebouwd.** Wat er staat:

- **Deelnemers-domein**: `Company` (ulid, slug), `ParticipantUser` (eigen guard `participant`, magic link of wachtwoord), `CompanyUser` (owner/staff/scanner), `Entry` (statussen uit hoofdstuk 5, reservering met vervaltijd, uniek per bedrijf per editie), `Location` met `OpeningHour` en `OpeningHourException`, `Profile` met moderatie en gepubliceerde snapshot, `TermsAcceptance` (versie, ip, tijdstip).
- **Commercie-domein**: `Package` (rechtenmatrix als json), `Order` met `OrderLine` (vlag `counts_for_charity`), `Invoice` (doorlopende nummering per jaar met lock), `Payment`. `PaymentGateway` met `FakePaymentGateway` (lokaal, `PAYMENT_DRIVER=fake`, nep-checkout op `/betaling/test/{payment}`) en `MolliePaymentGateway`; `AccountingGateway` met `NullAccountingGateway` tot het boekhoudpakket bekend is.
- **Acties**: `RegisterEntry` (account, bedrijf, standplaats, profiel, akkoord, inschrijving en order in één transactie met rijvergrendeling op de provinciecapaciteit), `MarkOrderPaid` (idempotent: order betaald, factuur, inschrijving bevestigd, events), `CancelExpiredReservations` (elke 5 minuten via de scheduler).
- **Aanmeldflow** `/aanmelden`: Livewire-wizard in vijf stappen met PDOK-adreslookup (`App\Support\Pdok`), KvK-dubbelcheck, capaciteitscheck, allergenen (EU-14), pakketkeuze, akkoord op de laatst gepubliceerde voorwaardenversie, betaling en statuspagina met retry. Mollie-webhook op `/webhooks/mollie`.
- **Portaal** `/portaal`: login (inloglink of wachtwoord), wachtwoord instellen en herstellen (eigen broker), dashboard met tijdlijn, profiel-editor (tekst, standplaats, seizoen, openingstijden; concept en indienen), facturen (HTML, printbaar), medewerkers (uitnodigen met rol, laatste eigenaar beschermd).
- **Publiekssite**: `/provincies`, `/provincie/{slug}` (deelnemers en plekken tot de Voorlijst er is), `/bakkers` (filters, zoeken, paginering), `/bakker/{slug}` (profiel volgens concept 3, "nu open", JSON-LD), `/hoe-werkt-de-test`, `/het-panel`, `/nieuws`, `/voorwaarden`, `/privacy`, `/sitemap.xml`, `robots.txt`. Navigatie linkt nu naar echte pagina's.
- **Backoffice**: resources voor bedrijven, inschrijvingen (terugtrekken), deelnemersaccounts (inloglink sturen), profielmoderatie (goedkeuren/afkeuren met toelichting), orders (handmatig betaald bij bankoverschrijving), facturen, pakketten, nieuws; dashboardwidgets met editiecijfers en plekken per provincie; zichtbaarheid per rol via `RestrictsToRoles`.
- **Mail**: bevestiging na betaling met inloglink, inloglink, wachtwoordherstel; eigen mailthema `degoudenbol` op de tokens. Nederlandse validatieteksten via `laravel-lang`.
- Tests: 81 groen.

Nog open binnen release 1: foto's en logo uploaden (medialibrary), factuur als pdf (nu printbare HTML), paginacache en CDN-invalidatie (komt met publicatie in release 2), Turnstile op het aanmeldformulier (klaar zodra sleutels er zijn), kaart met Leaflet op het profiel, uitzonderingen op openingstijden in het portaal. Zie ook de aannames in docs/07.

**24 september 2026 – Backoffice-thema en dashboard.** Op verzoek van Raphael ("mega standaard Laravel-backend") is de backoffice herstyled naar het VastgoedFotoVideo-redesign, in onze eigen huisstijl (zie docs/02 §7 voor de regels):

- `resources/css/filament/admin/theme.css` als Vite-thema: espresso sidebar met gouden actieve rij, merk in de donkere kolom, transparante topbar met witte pillen, zand-ondergrond met gouden gradients, witte kaarten van 20 px, pilknoppen, Cormorant voor paginatitels en begroeting, gestylede inlogpagina. Gecontroleerd met screenshots (dashboard, lijst, formulier met relation manager, modal, ingeklapte sidebar, mobiel, inlog).
- Eigen dashboard (`App\Filament\Pages\Dashboard`, view `filament/pages/dashboard.blade.php`, view-model `App\Filament\Support\DashboardData`): begroeting met voornaam en editiestatus, KPI-tegels (bevestigd/capaciteit, wacht op betaling, omzet, te modereren), "Acties nodig" met aantallen en directe links, mijlpalen met dagen-tot, staafjes aanmeldingen per dag (14 dagen), recente aanmeldingen, plekken per provincie, snelkoppelingen; alles per rol gefilterd. De oude widgets zijn weg.
- Lokaal hulpmiddel: `routes/dev.php` met `/dev/inloggen-als/{rol}` en `tools/screenshot.mjs` (docs/05 §8).
- Tests: 81 groen; Pint schoon.

**25 september 2026 – Backoffice herontworpen naar de look & feel van het VastgoedFotoVideo-dashboard.** Raphael stuurde twee schermen (dashboard en agenda) als referentie; de backoffice volgt die nu in vorm en dichtheid, in onze eigen tokens (regels in docs/02 §7):

- Sidebar met stapel oliebollen als decoratie onderin, menubadges (profielmoderatie, bezwaren, correctiedossiers), eigen voet met Instellingen en Inklappen; topbar met zoekpil (⌘K/Ctrl+K), bel met openstaande acties en profielpil (initialen, naam, rol).
- Dashboard: pastel kerncijfertegels met watermerk-icoon, getinte actierijen, mijlpalen met datumblok, financiën als mini-tegels, drie lijstkaarten onderaan. `DashboardData` is een scoped binding zodat pagina, bel en voet één berekening delen.
- Tabs als gesegmenteerde pil, kaarten 24 px, formulierlabels uppercase, modals en dropdowns in dezelfde stijl. Gecontroleerd met screenshots (dashboard, lijst, formulier met tabs en relation manager, modal, ingeklapte sidebar, mobiel met open menu, inlog, ontvangstpagina).
- **UX-ronde (zelfde dag, na feedback "je loopt er niet soepel doorheen")**: menu op werk in plaats van op datamodel (Bakkers, Testdagen, Uitslag, Campagne, Geld; instellingen op een hubpagina via de voet), taalnamen in plaats van jargon, groepen bij eerste bezoek open per fase en rol; seizoensbalk met fase en negen processtappen met aantallen (`Season`); "Jouw werk vandaag" per rol met actieknop (`WorkQueue`, ook de teller van de bel); werkvoorraad-tabs op zes lijsten; zoekpalet met pagina's, acties en records (`CommandPaletteSearchProvider`, bedrijven ook op KvK); Filament SPA-modus met prefetch, gouden voortgangsbalk, gedimde inhoud en laadpil tijdens de wissel, zacht inschuiven daarna. Regels in docs/02 §7. Tests: 133 groen; Pint schoon.

**25 september 2026 – Release 2 (testketen) gebouwd.** Wat er staat:

- **Testketen-domein op connectie `testing`** (`app/Domain/Testing`): `Sample` (alleen testnummer, ulid), `Intake` (tijd, temperatuur, stuks, foto, versheidsklok), `Panelist` (weergavecode P01, allergenen met toestemming), `TestSession` (+ `session_samples`, `session_panelists`), `ServingAssignment`, `ServingExclusion` (alleen reden-code), `Scorecard` (client-uuid, hash, onveranderlijk na indienen; alleen ongeldig verklaren mag), `PaperEntry` (dubbele invoer), `ScoreCorrection` (aanvrager ≠ goedkeurder), `Result`. Basis `App\Support\Models\TestingModel`.
- **Kluis op connectie `vault`** (`app/Domain/Vault`): `VaultLink` (inschrijving versleuteld met `VAULT_ENCRYPTION_KEY`, HMAC-zoeksleutel), append-only `VaultAccessLog`; `VaultService` logt iedere aanroep, staat alleen de rollen intake en publisher toe (de beheerder niet) en heeft `asSystem()` voor jobs en listeners. Drempel `VAULT_ALERT_THRESHOLD_PER_HOUR`.
- **Ontvangst & registratie** (`app/Domain/Intake`, de enige plek waar identiteit en testnummer samenkomen): `ScheduleDelivery` (slot met vergrendeling, aanlevercode XXXX-XXXX), `ReceiveSample` (monster + versheidsklok + kluiskoppeling; auditlog in twee losse regels zonder de koppeling), `AssignSampleNumber` (0001…, F01…, B01…). Filament-pagina `/admin/ontvangst` (alleen rol intake): QR scannen via de browser-`BarcodeDetector` of code typen, ontvangst vastleggen, direct nummeren, etiket 62×29 mm op `/etiket/{sample}`.
- **Portaal**: `/portaal/planning` (slot kiezen/wijzigen tot het slot begint, aanleverbewijs met QR via de al aanwezige `chillerlan/php-qrcode`, bevestigingsmail), `/portaal/uitslag` (cijfer, positie, vertrouwelijk rapport, bezwaar binnen drie werkdagen).
- **Testcoördinatie** (Filament, groep Testketen): aanleverslots, panelpool, testsessies met monsters en panelleden, uitserveerschema via `GenerateServingScheduleJob` (systeemproces; `ExclusionSource` wordt in het Vault-domein ingevuld met conflicten en allergenen, de testketen ziet alleen panellid/monster/reden), rotatie van de startvolgorde, versheidswaarschuwingen, sessie starten/afsluiten.
- **`ScoreCalculator`** (pure functie, unit-getest: gemiddelden, half-up-afronding, minimum kaarten, afwijkers > 15 punten van de mediaan, correcties) en **`RankingEngineV2026`** (pure, geversioneerd, `input_hash`; unit-getest: sortering, tiebreaks in volgorde, gedeelde plaatsen, vlag op plaats 1 en op de Top 10-grens, labels, < 10 resultaten, lege lijst, determinisme).
- **Panel-app** `/panel` (PWA, sessie-login met het medewerkersaccount, rol panellid, geen Filament-toegang): schema per panellid, één monster per scherm met steppers en lopend totaal, indienen vergrendelt (inhoud nooit meer zichtbaar), offline-wachtrij in IndexedDB met client-uuid en idempotente sync (`/panel/api/scorekaarten`), service worker voor de schil, belangenconflicten melden (bron voor uitsluitingen).
- **Scorecontrole** (`/admin/samples`, rol reviewer): kaarten per monster met deelscores, afwijkers, ongeldig verklaren, correctie met vier ogen, papieren kaart (twee gelijke invoeren), "definitief maken" (minimum kaarten, geen open correcties) → `ResultFinalized`.
- **Ranking & publicatie** (`app/Domain/Ranking`): listener `LinkFinalizedResult` koppelt als systeem via de kluis, maakt het publicatie-item (cijfer, deelscores, nooit het testnummer) in de open batch en een conceptrapport uit de panelnotities; `PublicationSchedule` (di/vr 12:00, stille periode → hoofdpublicatie); `SubmitBatch`/`ApproveBatch` (twee verschillende goedkeurders met rol publicatie, nooit de indiener)/`PublishBatch` (statussen, snapshot per provincie, cache-invalidatie, `BatchPublished`, notificaties); erkenning "Officieel getest" via `GrantTestedRecognition` in Marketing; `publication:run` (elke minuut) en `ranking:verify` (dagelijks, `--recompute`).
- **Publiekssite**: provinciepagina met direct antwoord, Voorlopige Top 10 met labels en tiebreak-vlag, "ook officieel getest", nog te beoordelen deelnemers; bakkerskaarten en profiel met cijfer en positie.
- **Backoffice** (groep Ranking & publicatie): publicatiebatches (indienen, goedkeuren, nu publiceren), vertrouwelijke rapporten (redactie), bezwaren.
- Lokaal: `LocalTestChainSeeder` (slots, zes demo-panelleden `panellid{1..6}@degoudenbol.test` / `password`, lopende demo-sessie met genummerde monsters en schema). Tests: 114 groen.

Nog open binnen release 2: OG-images en pagina-/CDN-cache (nu alleen cachesleutels per provincie), foto-upload bij ontvangst via medialibrary (nu lokale schijf), hersteltest back-up en externe quickscan, proefrun 28 oktober.

**26 september 2026 – Bevriezing, beslisronde, finale en correctiedossier (stap 4 uit het bouwplan, naar voren gehaald omdat het de rankingketen afmaakt).** Wat er staat:

- **Beslisronde**: na iedere herberekening bestaat per gedeelde plaats 1 of gedeelde Top 10-grens precies één open `TieBreakRound` (docs/03). Ontvangst ontvangt nieuwe monsters in de ronde "Beslisronde" (B01…; ronde kiezen op `/admin/ontvangst`, alleen voor al gepubliceerde deelnemers); na de definitieve score van alle betrokken monsters legt `LinkFinalizedResult` de onderlinge volgorde vast (`RankingEngineV2026::order`, op ruw totaal en dan de beslisregels) en berekent de lijst opnieuw. De engine krijgt besliste volgordes mee (`resolvedOrders`, onderdeel van de `input_hash`); het gepubliceerde cijfer blijft staan.
- **Bevriezing** (`FreezeEdition`, knop op de editiepagina voor Publicatie): weigert zolang een beslisronde open staat; anders in één transactie per provincie een bevroren snapshot zónder `published_at` (embargo), finale-inschrijvingen voor de provinciewinnaars (max. `max_finalists`) en editie-status `frozen`. Event `EditionFrozen` → Marketing maakt erkenningen Top 10 en provinciewinnaar met `embargo_until` = reveal-moment; Ranking mailt de finale-uitnodigingen.
- **Reveal per provincie** (besluit 20): `publication:run` onthult iedere bevroren provincie zodra haar `reveal_at` verstreken is (snapshot krijgt `published_at`, pivot `revealed_at`, cache weg, `ProvinceRevealed`); zijn alle provincies onthuld dan is de editie `published`. De provinciepagina toont tot die tijd de voorlopige lijst met een embargo-melding en daarna "Definitieve Top 10" met een winnaarsblok. Handmatig onthullen kan met de knop op de editiepagina.
- **Finale**: `Finalist` (provinciewinnaar of nummer 2 bij afmelding, instelling `finalist_fallback`; de provinciale titel blijft bij nummer 1), bevestigen/afmelden in het portaal en in de backoffice. Finale-monsters (F01…) doorlopen dezelfde keten; `LinkFinalizedResult` zet ze als ronde `final` in de batch, `PublishBatch` berekent de landelijke snapshot (scope `national`, lengte `national_list_length`), Marketing maakt erkenningen landelijke lijst en landelijke winnaar. Publieke pagina `/finale` (finalisten na de reveal, landelijke lijst na publicatie); archief `/edities` en `/editie/{jaar}`.
- **Correctiedossier na publicatie** (`CorrectionCase`): na publicatie negeert de koppeling herberekende uitslagen; alleen een dossier (Publicatie of Scorecontrole opent het, met reden) zet de uitslag terug naar scorecontrole. Daarna gewone kaartcorrecties met vier ogen, twee goedkeuringen van Publicatie (nooit de indiener), dan nieuwe uitslag (minimum kaarten), nieuwe uitslag in de eerstvolgende batch, nieuwe snapshot, mail aan de deelnemer. Origineel blijft in het dossier.
- Backoffice (groep Ranking & publicatie): beslisrondes, finalisten, correctiedossiers. Tests: 117 groen.

**27 september 2026 – Release 3, deel 1: erkenningen, badges en socialkit.** Wat er staat:

- **Erkenningen** (`Recognition`, groep Marketing): "Deelnemer" bij bevestigde betaling (geldig tot de hoofdpublicatie), "Officieel getest" bij publicatie, Top 10 en provinciewinnaar bij bevriezing (embargo tot de reveal), landelijke lijst en winnaar na de finale. Verificatiepagina `/erkenning/{code}` (bedrijf, categorie, jaar, geldigheid, "historisch" na afloop; 404 onder embargo of na intrekken), zoekpagina `/erkenning`, intrekken in de backoffice met reden in de auditlog.
- **Badges**: SVG 600×200 in licht en donker (`BadgeRenderer`, fonts ingesloten zodat het beeld overal in de huisstijl rendert), geladen vanaf ons platform op `/erkenning/{code}/badge.svg` en met embedcode die naar de verificatiepagina linkt (backlink). PNG en print-pdf's (raamposter, sticker, toonbankkaart) wachten op Browsershot: nieuwe dependency, aan Raphael.
- **Socialkit** per mijlpaal (`Milestone`, `SocialKitRenderer`, `MilestoneResolver`): beeld in vier formaten (1080×1080, 1080×1350, 1080×1920, 1200×630) als SVG, kant-en-klare tekst met hashtag, tag en profiellink (`config/marketing.php`), deelknop via de Web Share API met kopieer-fallback, zip met alles, embargo-melding voor nog niet onthulde mijlpalen. Portaalpagina `/portaal/marketing`, deelknop en erkenningschips op het openbare profiel.
- **Meting** (`share_events`): downloads, kopieer- en deelkliks per mijlpaal, alleen voor evaluatie. Tests: 122 groen.

**28 september 2026 – Release 3, deel 2: cadeaubonnen.** Wat er staat (`app/Domain/Vouchers`, docs/04 §8):

- **Actie per Top 10-ondernemer** (`VoucherCampaign`): automatisch aangemaakt bij de bevriezing (listener op `EditionFrozen`, idempotent; ook handmatig vanuit Bonbeheer), start op de hoofdpublicatie, winnaars invoeren tot 48 uur later om 12:00 (`config/vouchers.php`), laatste verzilverdag uit de editie, aantal en waarde uit de editie-instellingen, actievoorwaardenversie vastgelegd. `vouchers:tick` (ieder uur) opent acties en mailt de eigenaar, sluit ze op de deadline met een automatisch tekort, laat bonnen en acties vervallen en anonimiseert winnaars na 31 maart.
- **Winnaars** (`VoucherWinner`): de eigenaar voert voornaam, letter en e-mail in via `/portaal/cadeaubonnen` (max. 1 bon per e-mailadres per editie; scanner- en medewerkersrol mogen niet invoeren). Claimlink per mail; de winnaar accepteert op `/cadeaubon/claim/{token}` de actievoorwaarden en geeft optioneel toestemming voor naam en foto. Bonbeheer kan na de deadline aanvullen en claimlinks opnieuw sturen.
- **Bon** (`Voucher`): leesbaar nummer `GB26-GLD-7K3M` (provincieafkorting, alfabet zonder 0/O/1/I/L) plus een los 128-bit QR-token dat alleen gehasht wordt bewaard. `/bon/{token}` is de bon zelf (QR, nummer, status, geldig t/m, printbaar; geen persoonsgegevens; rate limited) én de statuscheck. Mail met link na uitgifte.
- **Verzilveren** (`RedeemVoucher`): alleen in het portaal van de uitgevende ondernemer, één atomaire `UPDATE … WHERE status = 'issued'`; twee telefoons kunnen nooit dubbel verzilveren. Scan-PWA op `/portaal/scan` (`resources/js/scan.js`: camera via `BarcodeDetector`, handmatige invoer als noodroute, resultaat groot in statuskleur, verzilverfoto alleen met toestemming). Elke poging wordt vastgelegd (`redemptions`, ook weigeringen met reden).
- **Backoffice**: `VoucherCampaignResource` (Bonbeheer, Financiën leest mee) met rapportage per ondernemer, winnaars en bonnen als relation managers, tekort vastleggen en als gefactureerd markeren, bon ongeldig maken met reden in de auditlog. Winnaarspagina `/winnaars` (alleen met toestemming), `/actievoorwaarden`, mijlpaal "cadeaubonnenactie" in de socialkit. Lokale demo via `LocalVoucherSeeder` (`/bon/demo-bon-token-alleen-lokaal-0001`). Tests: 125 groen.

Nog open: doorfactureren van tekorten loopt nog niet via Commercie (nu alleen markeren), Wallet-pass en pdf-bon wachten op Browsershot, ketentest met echte telefoons op 9 december, e-mailsjablonen in het eigen mailthema controleren zodra de juridische teksten er zijn.

**29 september 2026 – Release 3, deel 3: pers, sponsoring, goede doelen en nieuwsbrief.** Wat er staat:

- **Pers** (`PressRelease`, `MediaContact`, groep Marketing): bij de bevriezing schrijft `PressReleaseWriter` per provincie met bevroren Top 10 een eerste versie (kop, lead met winnaar en cijfer, de Top 10, "over de keuring", "over De Gouden Bol", noot voor de redactie uit `config/press.php`), onder embargo tot de reveal; het landelijke bericht ontstaat bij de publicatie van finale-uitslagen. Communicatie redigeert in de backoffice (tekst, embargo, publicatiemoment), stuurt de perskit naar de regionale plus landelijke mediacontacten via een tijdelijke ondertekende link (`/pers/kit/{slug}`, 14 dagen), kan hergenereren en handmatig publiceren. Op de reveal (`ProvinceRevealed`) vervalt het embargo en staat het bericht op `/pers` en `/pers/{slug}`, met een link op de provinciepagina en in het portaal (Marketing).
- **Sponsoring** (Commerce): `Product` (catalogus per editie met de dossiertarieven, `ProductSeeder`), `Sponsor` (logo via de publieke schijf, contact, factuurgegevens), `Placement` (locatie `homepage`, `province:{id}`, `entry:{id}`, `national_top`, `final`, `national`, `charities`; periode; exclusiviteit; status), `SponsorLink` ("Bakt met", bevestigd door de deelnemer in `/portaal/sponsoren`). `CreatePlacement` blokkeert dubbele verkoop van exclusieve plekken (rijvergrendeling op locatie en periode), verkoopt de landelijke toppositie pas zodra er finalisten zijn en rekent voor extra koppelingen het lagere tarief. `CreateSponsorOrder` maakt één order (regels noemen product en plek, nooit een positie); na betaling (`OrderPaid`) worden de plaatsingen actief. Publiek: `/sponsoren` (partners, plaatsingen, catalogus), sponsorblok op de homepage, provinciepartner op de provinciepagina, "Bakt met" op het profiel, finale-sponsoren. Backoffice: sponsoren met plaatsingen en koppelingen als relation managers, "Factureren", sponsorcatalogus (Financiën).
- **Goede doelen** (`app/Domain/Charities`): `Charity` (voordracht door bedrijf via `/portaal/goed-doel` of door een sponsor via de backoffice; één per voordrager per editie), statussen voorgedragen → in beoordeling → goedgekeurd / alternatief → gekoppeld → uitbetaald, checklist uit `config/charities.php` (werkversie tot het dossier er is). `ReserveForCharity` op `OrderPaid`: per factuurregel met `counts_for_charity` het editiepercentage over het bedrag excl. btw (`charity_percentage`, `charity_basis`), naar het goedgekeurde doel van de betaler, anders naar de regionale pot van de provincie (sponsoren: landelijke pot); goedkeuring of "alternatief" verplaatst de reserveringen mee. Uitbetalingen (`CharityPayout`) door Financiën. Publiek `/goede-doelen`: per doel grondslag, bedrag, selectie en uitbetaaldatum, plus regionale potten en totalen.
- **Nieuwsbrief**: opt-in met dubbele bevestiging (`NewsletterSubscription`, ondertekende bevestigings- en afmeldlinks) via het formulier in de footer; lijst voor Communicatie in de backoffice. Koppeling met een mailplatform volgt zodra dat gekozen is.
- Tests: 130 groen (persflow, sponsoring, reservering en beoordeling, nieuwsbrief). Lokale demo via `LocalCampaignSeeder` (demo-sponsor met betaalde plaatsingen en "Bakt met", goedgekeurd demo-doel, twee mediacontacten).

Nog open na release 3: PNG-badges, drukwerk-pdf, pdf-bon, OG-images en persbeeld wachten op Browsershot (nieuwe dependency); foto- en logo-upload via medialibrary (nu logo via `FileUpload` op de publieke schijf); paginacache en CDN-invalidatie (responsecache); Turnstile; kaart op het profiel; export van nieuwsbrief-opt-ins naar het mailplatform; doorfactureren van bontekorten via Commercie; dashboard per inkomstenstroom met openstaande 10%-reservering; Wallet-pass; proefrun 28 oktober, ketentest cadeaubonnen 9 december, loadtest en externe quickscan.

**30 september 2026 – Verharding richting release 1.** Wat er staat:

- **Paginacache** (`CachePublicPage`, `config/publiccache.php`, docs/05 §4): volledige HTML van de benoemde publieke routes voor anonieme bezoekers, 10 minuten, met één generatienummer dat bij iedere wijziging aan publieke inhoud omhoog gaat (modelobservers op edities, deelnemers, profielen, ranking, nieuws, pers, sponsoring, goede doelen, cadeaubonwinnaars; plus publicatie en reveal). `X-Public-Cache`-header, `s-maxage` voor een CDN, purge via `CdnPurger` (Cloudflare of geen). `php artisan public:cache-clear` na een deploy. Het nieuwsbriefformulier werkt zonder CSRF-token (honeypot, throttle, dubbele opt-in) omdat het op gecachte pagina's staat. Uit in de testsuite behalve in de eigen test.
- **Turnstile** op de laatste stap van de aanmeldwizard: widget alleen met sleutels, server-side verificatie vóór het aanmaken van de inschrijving, nette foutmelding.
- **Kaart** op het bakkersprofiel: OpenStreetMap-embed met marker zodra de PDOK-coördinaten er zijn (geen dependency).
- **Dashboard**: kaart "Financiën per inkomstenstroom" voor Financiën en beheer: betaald en openstaand per stroom (deelname, sponsoring), gereserveerd voor goede doelen, in de regionale potten, nog uit te betalen.
- Tests: 133 groen.

Nog open: PNG-badges, drukwerk-pdf, pdf-bon, OG-images (Browsershot); foto-upload (medialibrary); export nieuwsbrief naar mailplatform; doorfactureren bontekorten; Wallet-pass; backup-hersteltest, loadtest en externe quickscan; proefrun 28 oktober; ketentest cadeaubonnen 9 december. Zie ook docs/07 voor de open besluiten.

**Volgende stap:** ontbrekende input verwerken zodra die er is (beoordelingsmodel, pakketten, voorwaarden, logo's, persberichttemplates) en de release 1-QA: teksten nalopen, mails in het eigen mailthema controleren, toegankelijkheid, proefrun.

## Stap 0 – Fundament (vóór release 1)

1. `.env`: appnaam, locale `nl`, timezone `Europe/Amsterdam`, drie databases (`degoudenbol`, `degoudenbol_testing`, `degoudenbol_vault`) en connecties in `config/database.php`. Databases lokaal aanmaken.
2. Pakketten (versies controleren op compatibiliteit met Laravel 13): Filament, `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/browsershot`, `spatie/laravel-responsecache`, `spatie/laravel-sitemap`, `spatie/schema-org`, `mollie/laravel-mollie`, `bacon/bacon-qr-code`, `league/flysystem-aws-s3-v3`, `sentry/sentry-laravel`, `laravel/horizon` (prod), 2FA/passkeys (`laragear/webauthn` of Filament-2FA), `laravel/sanctum` (panel-API).
3. Tokens: `docs/tokens.json` → `resources/design/tokens.json`; generator-command `php artisan design:tokens` → `resources/css/tokens.css` (CSS-variabelen + Tailwind v4 `@theme`), Filament-kleuren, mail-stijlen.
4. Fonts self-hosten: Cormorant Garamond + Manrope als woff2 in `public/fonts/`, `@font-face` in `tokens.css`.
5. Layouts: `layouts/public`, `layouts/portal`, `layouts/panel`, `layouts/scan`, `layouts/mail`; Blade-componenten uit doc 02 §5 (nav, footer, knoppen, eyebrow, kaarten, pillen, chips, formulier-elementen, lege staat).
6. Stijlgids `/stijlgids` met alle tokens en componenten.
7. Domeinstructuur `app/Domain/*` + architectuurtests (Ranking importeert niets uit Commerce/Marketing; Testing-modellen gebruiken connectie `testing`; Vault alleen via `VaultService`).
8. Platform: medewerkersrollen (doc 03), verplichte 2FA op Filament-login, `AuditLog` met hashketen (observer op gevoelige modellen), editie-scoped verbodscheck op rolcombinaties.
9. Seeders: 12 provincies, editie 2026 met alle instellingen en datums, beoordelingsmodel (placeholder 8 onderdelen), pakketten (model B default), sponsorproducten, voorwaarden-versies, testgebruikers per rol.
10. Filament: resources voor Edition (instellingen als formulier), Province, ScoringModel, Package, Product, TermsVersion, User/Roles.

## Stap 1 – Release 1: Inschrijving (ma 12 okt)

1. Modellen Participants: Company, User/CompanyUser, Entry, Location, OpeningHours(+Exceptions), Profile, TermsAcceptance.
2. Aanmeldflow `/aanmelden` (Livewire, meerstaps): bedrijf/KvK → adres + PDOK (provinciebepaling, coördinaten) → publicatiegegevens + allergenen → pakket/plek (voorraad per provincie, reservering met vervaltijd) → voorwaarden (versie) → betaling Mollie → bevestiging. Turnstile op stap 1.
3. Commerce: Package, Order, InvoiceLine, Invoice, Payment; Mollie-webhook; `AccountingGateway` (Null/Moneybird/Exact); factuur-pdf; herinneringen (scheduler).
4. Portaal: auth (magic link + wachtwoord), dashboard met tijdlijn, profiel (tekst → moderatie, foto's via medialibrary met bijsnijden, standplaatsen, openingstijden), facturen, medewerkersaccounts.
5. Publiekssite basis: home (echte tellers, aanmeldstatus per provincie), provinciepagina's (lege staat), alle bakkers (deelnemers zonder cijfer of leeg), hoe werkt de test (uit instellingen), nieuws, juridische pagina's, sitemap, structured data, paginacache.
6. Mail: bevestiging aanmelding, betaling, factuur, magic link; mail-layout op tokens.
7. Backoffice: Entries (status, plekken), Companies, Orders/Invoices, Profiles-moderatie, News.
8. Tests: aanmeldflow, voorraad per provincie (race), Mollie-webhook, rolverboden, auditketen.
9. QA-gate + demo.

## Stap 2 – Release 2: Testketen (vr 30 okt; proefrun wo 28 okt)

1. Testing-domein op eigen connectie: TestLocation, DeliverySlot (hoofd), Sample, Intake, Session, Panelist(+allergenen, consent), ServingAssignment/Exclusion, Scorecard, PaperEntry, ScoreCorrection, Result.
2. Vault: VaultLink (encrypted), VaultAccessLog, `VaultService` (alles gelogd, melding bij drempel).
3. Portaal: slot kiezen/wijzigen, QR-aanleverbewijs (pdf + mail).
4. Backoffice Ontvangst: QR scannen (camera in browser), tijd/temperatuur/stuks/foto, versheidsklok; Registratie: testnummer + etiket (browser-print); Testcoördinatie: sessies plannen, uitserveerschema genereren (job met kluistoegang, conflicten en allergieën), versheidsbewaking.
5. `ScoreCalculator` (pure functie) + unit tests.
6. Panel-PWA: Sanctum-login per panellid, uitserveerschema, scorekaart per monster (8 onderdelen, hele punten, lopend totaal, sterke punten/ontwikkelkansen), offline-wachtrij (IndexedDB, client-UUID, idempotente sync), vergrendeling na indienen, geen inzage in eigen eerdere kaarten.
7. Scorecontrole (Filament): overzicht per monster, vlaggen (outliers, ontbrekend, versheid), papieren dubbele invoer, correcties met reden, "definitief maken" (min. kaarten).
8. `RankingEngineV2026` (pure, geversioneerd) + uitgebreide unit tests (sorteren, tiebreaks in volgorde, gedeelde plaatsen, vlag op plaats 1/grens 10, labels t.o.v. vorige snapshot, drempel 5,0, < 10 resultaten, lege provincie).
9. Publicatie: PublicationBatch (di/vr 12:00 gepland), items, twee goedkeuringen (policy: ≠ indiener, ≠ elkaar), `BatchPublished` → koppeling via kluis → snapshot → cache-invalidatie → erkenning "Officieel getest" → notificatie. Vertrouwelijk pad < 5,0.
10. Publiekssite: provinciepagina's live (Voorlopige Top 10 met labels, direct antwoord), profielen met uitslag, bakkersoverzicht met cijfers en filters, OG-images.
11. Portaal: uitslag + positie, vertrouwelijk rapport (redactie in backoffice; optioneel Claude-concept), bezwaar indienen.
12. Dagelijkse herberekeningsjob; hersteltest back-up; externe quickscan.
13. Proefrun 28 okt met echte panelleden op acceptatie; instellingen bijstellen (besluit 19).

## Stap 3 – Release 3: Campagne (ma 7 dec; ketentest wo 9 dec)

1. Marketing: Recognition (types, geldigheid, embargo), verificatiepagina `/erkenning/{code}`, embedcode, BadgeAsset-generatie (SVG-templates → PNG licht/donker, pdf poster/sticker/toonbankkaart via Browsershot, queue).
2. SocialKit per mijlpaal: vier formaten + teksten, zip, Web Share API-knop op mobiel, meting downloads/deelkliks.
3. PressRelease per provincie + embargo-perskit via ondertekende tijdelijke link; MediaContacts.
4. Vouchers: VoucherCampaign (auto voor Top 10 na bevriezing, maar bouwen en testen nu), winnaars invoeren, claimlink + voorwaarden + toestemming, uitgifte (code + 128-bit token, pdf met QR, mail), `/bon/{token}` controlepagina (rate limit), scan-PWA in portaal (camera, atomaire verzilvering, handmatige invoer, resultaatscherm in statuskleur, verzilverfoto), winnaarspagina, rapportage, shortfalls, automatisch vervallen, anonimisering per 31 mrt 2027, max per e-mail.
5. Commerce: Sponsor, Placement (exclusiviteit provinciepartner, verkoopfase landelijke toppositie), SponsorLink ("Bakt met", bevestiging door deelnemer), sponsorfacturatie; publieke sponsorpagina en plaatsingen op home/provincie/profiel.
6. Charities: voordracht (portaal/backoffice), beoordelingschecklist, statussen, Reservation-berekening over `counts_for_charity`-regels, regionale pot, payouts, openbare pagina.
7. Nieuwsbriefkoppeling opt-ins; Wallet-pas optioneel.
8. Ketentest 9 dec met echte telefoons bij twee proefondernemers.

## Stap 4 – Release 4: Finale (di 15 dec)

1. `FreezeEdition`: één transactie → definitieve snapshots, Top 10-erkenningen, provinciewinnaars, finale-inschrijvingen; daarna queue voor assets/mails onder embargo.
2. TieBreakRound: gelijke deelnemers, nieuwe monsters, uitkomst bepaalt volgorde; bevriezing wacht.
3. Stille periode en provincie-reveal: geplande `ReleaseEmbargo`-jobs per provincie op 21 dec; embargo-stand op de pagina's; live-modus/presentatiescherm (optioneel).
4. Finaleronde: samples F01–F12, eigen sessies, eigen snapshot `national`, landelijke lijst (Top 5), erkenningen landelijke lijst/winnaar, `/finale`.
5. Correctiedossier na publicatie (twee goedkeuringen, nieuwe snapshot, meldingen); terugtrekking vóór bevriezing.
6. Loadtest (10.000 bezoekers/min uit cache) en CDN-configuratie; wekelijkse hash-export (besluit 22).
7. Archiefpagina `/editie/2026`.

## Stap 5 – Begin 2027

Academy-domein (cursussen, data, boekingen via Mollie, wachtlijst, certificaten, oefenbeoordelingen, begeleiding → belangenconflict), publieksprijs, zichtbaarheidsscore, sponsor-selfservice, consumentenkaart (als niet al in 2026), anonimiseringsjobs draaien.

## Testen (minimaal)

- Unit: `ScoreCalculator`, `RankingEngineV2026` (alle randgevallen uit doc 04), voucher-codegenerator, hashketen, "nu open"-logica (PHP-equivalent voor server-fallback).
- Feature: aanmeldflow + voorraad (parallelle requests), Mollie-webhook idempotent, approval-policy (indiener ≠ goedkeurders), rolverboden per editie, kluis-logging bij elke toegang, panel-API exposeert nooit bedrijfsgegevens, scorekaart onveranderlijk, atomaire verzilvering onder concurrency, embargo (asset-URL vóór vrijgave = 403), cache-invalidatie na publicatie.
- Architectuur: importregels tussen domeinen; Testing-modellen op connectie `testing`.
- Browser/handmatig: panel-PWA offline → online sync; scan-PWA op iOS en Android; reduced motion; WCAG-check.

## Commando's die ik verwacht te gebruiken

```
composer run dev                      # server + queue + logs + vite
php artisan migrate --database=testing --path=database/migrations/testing
php artisan migrate --database=vault   --path=database/migrations/vault
php artisan db:seed
php artisan design:tokens              # tokens.json → css/filament/mail
php artisan test
vendor/bin/pint
php artisan ranking:recompute --edition=2026 --dry-run
php artisan audit:export-hash
```

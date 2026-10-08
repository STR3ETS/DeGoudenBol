# 05 – Techniek en architectuur

## 1. Stack (wat er staat + wat de briefing voorstelt)

| Laag | Keuze | Status |
|---|---|---|
| Framework | Laravel `^13.17` (in `composer.json`), PHP 8.5 lokaal (`composer.json` eist `^8.4`) | staat |
| Frontend build | Vite 8 + Tailwind CSS v4 (`@tailwindcss/vite`) | staat |
| Backoffice | Filament (nieuwste versie die Laravel 13 ondersteunt; bij installatie controleren) | te installeren |
| Publiekssite en portaal | Blade + Livewire (komt met Filament), server-rendered op de tokens | te bouwen |
| Panel-app en scan-app | PWA (manifest + service worker, offline-wachtrij in IndexedDB); Vite-plugin-pwa of handgeschreven SW | te bouwen |
| Database | MySQL 8 (Laragon) met drie databases: hoofd, testketen, kluis (zie doc 03); PostgreSQL blijft mogelijk | te configureren |
| Wachtrij en cache | Redis + aparte workers (Horizon); lokaal fallback `database` driver (staat al in `.env`) | te configureren |
| Beeld en pdf | Browsershot (headless Chromium) op HTML/SVG-templates; Intervention/Glide voor WebP/AVIF | te installeren |
| Bestanden | S3-compatibele opslag in de EU (lokaal `local` disk) | te configureren |
| Hosting | EU-VPS of cloud; Plesk voor test/acceptatie; aparte worker; back-ups; opschalen rond 21 dec | extern |
| CDN | Cloudflare of Bunny, alleen voor de publiekssite | extern |
| Monitoring | Sentry (EU), uptimemonitor, centrale logs | te koppelen |
| Tests | PHPUnit 12 (staat); Pint (staat) | staat |
| Dev tooling | Laravel Boost (geïnstalleerd, zie `AGENTS.md`), Pail, `composer run dev` | staat |

Boost-richtlijnen (`AGENTS.md`) zijn leidend voor Laravel-conventies; `docs/` is leidend voor het domein.

## 2. Modulaire monoliet: mappenstructuur (voorstel)

```
app/
  Domain/
    Edition/        Models, Enums, Settings, Seeders-data
    Participants/   Company, Entry, Location, OpeningHours, Profile, Terms
    Testing/        Sample, Intake, Session, Panelist, Scorecard, Result, ScoreCalculator   (connectie: testing)
    Vault/          VaultLink, VaultAccessLog, VaultService                                  (connectie: vault)
    Ranking/        Engine/RankingEngineV2026, PublicationBatch, Approval, Snapshot, TieBreak, CorrectionCase
    Marketing/      Recognition, BadgeAsset, SocialKit, PressRelease, NewsPost, generators
    Vouchers/       VoucherCampaign, Winner, Voucher, Redemption, RedeemVoucher (atomair)
    Commerce/       Package, Product, Order, InvoiceLine, Invoice, Payment, Sponsor, Placement
    Charities/      Charity, Nomination, Reservation, Payout
    Academy/        (2027)
    Platform/       Roles, TwoFactor, AuditLog (hashketen), Notifications, Media
  Http/
    Public/         Controllers + middleware (cache)      → resources/views/public
    Portal/         Livewire-componenten                  → resources/views/portal
    Panel/          API-controllers (alleen testnummers)  → resources/views/panel (PWA-shell)
    Scan/           API + PWA-shell                       → resources/views/scan
  Filament/         Resources, Pages, Widgets per domein (Admin-panel)
  Support/          Money, DutchTime, Slug, Hash helpers
resources/
  design/tokens.json            (kopie van docs/tokens.json → wordt de bron)
  css/tokens.css, app.css       (gegenereerd + Tailwind @theme)
  js/app.js, reveal.js, open-now.js, panel/, scan/
  views/{layouts,components,public,portal,panel,scan,mail,templates/{badges,social,print}}
  lang/nl/                      (alle UI-strings)
database/migrations/{main,testing,vault}/
tests/{Unit/Ranking,Unit/Scoring,Feature/...,Architecture/}
```

Elk domein: eigen modellen, acties (`Actions/`), events, policies. Domeinen praten via acties en events, niet via elkaars modellen. `Ranking` importeert niets uit `Commerce` of `Marketing` (architectuurtest).

## 3. Events en de publicatieketen

```
ScorecardSubmitted ─► (queue) RecalculateResult ─► ResultFinalized (door scorecontrole)
  ─► LinkSampleToEntry (VaultService, gelogd) ─► PublicationItemCreated
  ─► BatchSubmitted ─► BatchApproved (1) ─► BatchApproved (2, ander persoon) ─► BatchPublished
  ─► RecomputeRanking(scope) ─► SnapshotStored
  ─► GrantRecognitions ─► GenerateBadgeAssets / GenerateSocialKit / GeneratePressRelease (embargo_until)
  ─► InvalidatePages(provincie, profiel, home, bakkers) ─► NotifyParticipant (alleen goed nieuws)
```

Sinds 29 september 2026 bestaan `BatchSubmitted`, `BatchApproved` (met het aantal goedkeuringen), `CorrectionCaseOpened`, `ObjectionSubmitted` en `ProfileSubmitted` als echte events, naast `BatchPublished` en `OrderPaid`. `App\Domain\Platform\Listeners\NotifyStaff` zet ze om in meldingen in de bel van de backoffice via `App\Domain\Platform\Services\StaffNotifier` (per rol, alleen actieve accounts, nooit de veroorzaker). Meldingen gaan via de Laravel-notificatiequeue; lokaal moet dus een `queue:work` draaien om ze in de bel te zien.

Bevriezing (`FreezeEdition`) is één transactie in het hoofddomein; alles erna gaat via de queue met `embargo_until = 21 dec`. Publiceren op 21 december = `ReleaseEmbargo`-job per provincie op het ingestelde tijdstip.

## 4. Caching en performance

- Volledige paginacache voor de publiekssite, eigen middleware `CachePublicPage` (gebouwd 30 sep, zonder responsecache): alleen de benoemde routes uit `config/publiccache.php`, alleen anonieme GET's zonder flash of fouten, standaard 10 minuten. Eén generatienummer voor alle pagina's; iedere wijziging aan publieke inhoud (`PublicCache::bumpOn` op de publieke modellen, plus publicatie en reveal) verhoogt het nummer en laat alles vervallen. `X-Public-Cache: HIT|MISS` en `Cache-Control: public, s-maxage=…` voor een CDN; CDN-purge via `CdnPurger` (`null` of `cloudflare`, één keer per request). Na een deploy: `php artisan public:cache-clear`. Formulieren op gecachte pagina's (nieuwsbrief) werken zonder CSRF-token met honeypot en throttle; alles met sessie (aanmelden, bon, erkenning, portaal) blijft ongecachet.
- "Nu open" rekent in de browser (Nederlandse tijd, seizoensdata, uitzonderingen zoals 31 december) op JSON die in de pagina zit; gecachte pagina blijft actueel.
- Alles voor 21 december vooraf gegenereerd; publiceren is een schakelaar.
- Loadtest vóór 21 december: 10.000 bezoekers/minuut op publiekspagina's uit de cache terwijl portaal en backoffice bruikbaar blijven.
- Beeldpijplijn: uploads → WebP/AVIF in meerdere formaten, `srcset`, lazy loading. Fonts self-hosted met preload.

## 5. Beveiliging

| Dreiging | Maatregel |
|---|---|
| Koppeling testnummer ↔ bakker lekt | Kluis met eigen sleutel en gebruiker, rolbeperking, inzagelog, melding bij ongebruikelijke inzage |
| Score achteraf aangepast | Vergrendelde kaarten met hash, correcties alleen via dossier, hashketen in auditlog, dagelijkse herberekening van snapshots |
| Uitslag lekt vóór 21 dec | Embargo in de applicatie; assets alleen via tijdelijke ondertekende URL's tot vrijgave |
| Medewerkersaccount overgenomen | Verplichte 2FA (passkey of app), korte sessies, optioneel IP-beperking op `/admin` |
| Bonfraude | Onraadbare tokens, atomaire verzilvering, rate limiting op `/bon` |
| Datalek | Dataminimalisatie, versleuteling in rust, EU-hosting, verwerkersovereenkomsten |
| Overbelasting bij publicatie | CDN met aanvalsbescherming, paginacache, rate limiting |
| Fout in rekenregel | Geteste pure functie, proefrun week 44, herberekening bij iedere publicatie |

- Norm: OWASP ASVS niveau 2 voor portaal en backoffice; security headers (CSP, HSTS, frame-ancestors), dependency-scans, geheimen buiten de code.
- Auditlog: append-only; `hash = sha256(prev_hash . canonical_json(payload))`; wekelijks laatste hash naar notaris/toezichthouder (besluit 22, optioneel).
- Dagelijkse back-ups met point-in-time herstel; hersteltest vóór 1 november; externe security-quickscan vóór release 2 en 3.
- Privacy: verwerkingsregister, privacyverklaring, verwerkersovereenkomsten (hosting, mail, Mollie, boekhouding, monitoring, CDN). Allergieën panelleden = gezondheidsgegevens: uitdrukkelijke toestemming, alleen categorieën, alleen testcoördinatie, wissen na editie. Beeldtoestemming los van actievoorwaarden en intrekbaar. Export- en anonimiseerfunctie in backoffice. Alleen functionele cookies + Matomo/Plausible; social embeds pas na toestemming.

**Bewaartermijnen (voorstel)**: uitslagen en snapshots blijvend (openbaar archief) · scorekaarten blijvend, panelleden gepseudonimiseerd na 2 jaar · allergieën panelleden tot einde editie · cadeaubonwinnaars tot 31 maart 2027, daarna geanonimiseerd · facturen en betalingen 7 jaar.

## 6. Integraties

| Dienst | Waarvoor | Release |
|---|---|---|
| Mollie | iDEAL en creditcard, betaallinks op facturen, later Academie | 1 |
| Boekhoudpakket (Moneybird of Exact Online – nog te horen van Bennie) | Facturen, debiteuren, btw | 1 |
| PDOK Locatieserver | Adres aanvullen, coördinaten, provinciebepaling (gratis) | 1 |
| KvK-API (optioneel, betaald) | Bedrijfsgegevens bij aanmelding; handmatig blijft terugval | 1 |
| Transactionele mail (Postmark, Mailgun EU of Brevo) | Bevestigingen, claimlinks, bonnen, meldingen; SPF/DKIM/DMARC | 1 |
| Object storage EU (S3-compatibel) | Foto's, logo's, badges, pdf's | 1 |
| Sentry (EU) + uptimemonitor | Fouten en beschikbaarheid | 1 |
| Matomo of Plausible | Statistieken zonder trackingcookies | 1 |
| Cloudflare Turnstile of hCaptcha | Aanmeldformulier, later stemformulier | 1 |
| CDN (Cloudflare of Bunny) | Paginacache en aanvalsbescherming | 1 |
| Headless Chromium (Browsershot) | Badges, socialkits, bonnen, persberichten, OG-images | 2 en 3 |
| Leaflet + PDOK/OSM-tegels | Kaart op profielen, consumentenkaart | 2 |
| Labelprinter via browser | Neutrale testnummeretiketten | 2 |
| Nieuwsbriefpakket (Laposta of Brevo) | Mailings met gesynchroniseerde opt-ins | 3 |
| Apple/Google Wallet (optioneel) | Cadeaubon als wallet-pas | 3 |
| Claude API (optioneel) | Eerste versie terugkoppelrapport uit geanonimiseerde notities, altijd menselijke eindredactie | 2 |

Bewust niet: Meta-API's (delen via Web Share API en downloads).

## 7. Omgevingsvariabelen (toe te voegen)

```
APP_NAME="De Gouden Bol"
APP_LOCALE=nl  APP_FALLBACK_LOCALE=nl  APP_FAKER_LOCALE=nl_NL  APP_TIMEZONE=Europe/Amsterdam
DB_DATABASE=degoudenbol
DB_TESTING_DATABASE=degoudenbol_testing  DB_TESTING_USERNAME=  DB_TESTING_PASSWORD=
DB_VAULT_DATABASE=degoudenbol_vault      DB_VAULT_USERNAME=    DB_VAULT_PASSWORD=
VAULT_ENCRYPTION_KEY=                    (los van APP_KEY)
AUDIT_HASH_SALT=
MOLLIE_KEY=  MOLLIE_WEBHOOK_SECRET=
ACCOUNTING_DRIVER=moneybird|exact  ACCOUNTING_TOKEN=
PDOK_BASE_URL=https://api.pdok.nl/bzk/locatieserver/search/v3_1
KVK_API_KEY=
TURNSTILE_SITE_KEY=  TURNSTILE_SECRET=        (leeg = check uit)
PUBLIC_CACHE_ENABLED=true  PUBLIC_CACHE_TTL=10
CDN_DRIVER=null|cloudflare  CLOUDFLARE_ZONE_ID=  CLOUDFLARE_API_TOKEN=
SENTRY_LARAVEL_DSN=
BROWSERSHOT_CHROME_PATH=
FILESYSTEM_DISK=s3 (prod) / local (dev)
EMBARGO_DEFAULT_AT="2026-12-21 12:00"
```

## 8. Lokale omgeving

- Laragon, https://degoudenbol.test, PHP 8.5, MySQL 8.4, Node 24.
- Databases `degoudenbol` (root), `degoudenbol_testing` (gebruiker `dgb_testing`) en `degoudenbol_vault` (gebruiker `dgb_vault`) bestaan; wachtwoorden staan in `.env`. `VAULT_ENCRYPTION_KEY` en `AUDIT_HASH_SALT` zijn lokaal gegenereerd en mogen nooit wijzigen na de eerste regel.
- `composer run dev` start server, queue, logs en Vite; `npm run build` voor een productiebundel.
- `php artisan migrate:fresh --seed` zet alles neer inclusief lokale medewerkersaccounts en de demoketen (deelnemers, testsessie, publicatie, cadeaubonnenactie, sponsor, goed doel). `migrate:fresh` maakt via `FreshSecondaryConnections` ook de testketen- en kluisdatabase leeg (pas nadat de productiebevestiging is gepasseerd); een gewone `migrate` raakt die niet.
- `php artisan test --compact` draait alles op SQLite in-memory (drie connecties, zie `phpunit.xml`).
- `php artisan design:tokens` na elke wijziging in `resources/design/tokens.json` (`--check` in CI).
- Boost MCP-server staat in `.mcp.json`; Boost-skills in `.claude/skills`.

### Deploy op Plesk (eerste keer)

1. Drie databases aanmaken in Plesk: hoofd (`DB_*`), testketen (`DB_TESTING_*`) en kluis (`DB_VAULT_*`). Eigen gebruikers per database hebben de voorkeur; zonder `DB_TESTING_USERNAME`/`DB_VAULT_USERNAME` gebruiken de twee extra connecties de hoofdgebruiker (`DB_USERNAME`/`DB_PASSWORD`), die dan in Plesk toegang tot alle drie de databases moet hebben. "Access denied … (using password: NO)" betekent meestal dat de config nog gecachet is: `config:clear` of opnieuw `optimize`.
2. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, de drie databases, `VAULT_ENCRYPTION_KEY` (los van `APP_KEY`: `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`), `AUDIT_HASH_SALT` (lange willekeurige tekst), `QUEUE_CONNECTION=database`, mail, `PAYMENT_DRIVER=mollie` plus `MOLLIE_KEY`, `PUBLIC_CACHE_ENABLED=true`. Sleutel en salt mogen daarna nooit meer wijzigen.
3. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan storage:link`.
4. `php artisan migrate --force` (eerste keer; `migrate:fresh` alleen op een lege omgeving: het wist alle drie de databases). Seeders met `Local` in de naam doen buiten `local` niets; de productiedata komt uit `ProvinceSeeder`, `Edition2026Seeder`, `PackageSeeder`, `ProductSeeder` en `RoleSeeder`.
5. Eerste beheerder: `php artisan staff:create naam@domein.nl "Naam" --role=admin --password=...` (zonder `--password` wordt er een gegenereerd en eenmalig getoond). `make:filament-user` volstaat niet: zonder rol weigert `canAccessPanel`. Daarna maak je collega's aan onder Medewerkers in de backoffice. Eerste login vraagt om TOTP.
6. Na iedere deploy: `php artisan optimize` (na een `.env`-wijziging eerst `php artisan config:clear`), `php artisan public:cache-clear`. Cron: `* * * * * php artisan schedule:run`; queue: `php artisan queue:work --tries=3` als supervisor- of Plesk-achtergrondproces.
7. Zonder SSH (Plesk, 8 okt 2026): `.env` bewerken via de Laravel-toolkit (tab Environment) of de File Manager; artisan-commando's via de toolkit (Artisan) of als geplande taak "Run a command" met `/opt/plesk/php/8.4/bin/php /var/www/vhosts/<domein>/httpdocs/artisan <commando>` en "Nu uitvoeren". Zet in Git > Deploy actions vast: `php artisan config:clear`, `php artisan migrate --force`, `php artisan optimize`, `php artisan public:cache-clear`, zodat iedere deploy dat zelf doet. Geheimen (`VAULT_ENCRYPTION_KEY`, `AUDIT_HASH_SALT`) lokaal genereren en plakken; ze hoeven niet op de server gemaakt te worden.
- Alleen lokaal (`APP_ENV=local` en `APP_DEBUG=true`, `routes/dev.php`): `GET /dev/inloggen-als/{rol}` logt in op de backoffice als `screenshot-{rol}@degoudenbol.test` zonder wachtwoord of MFA-challenge, voor screenshots en snel testen.
- Visuele controle: `node tools/screenshot.mjs <url> <uitvoer.png> [breedte] [hoogte] [mobiel 0|1] [maxHoogte] [eerstBezoeken]` maakt een volledige-paginascreenshot met headless Chrome (DevTools-protocol). Backoffice: geef als laatste argument de dev-inlogroute mee. `SHOT_HOVER` (CSS-selector) zet de muis boven een element om een hover-toestand vast te leggen, `SHOT_EVAL` voert daarna een JS-expressie uit (mag een Promise teruggeven, handig om na een klik even te wachten en dan de toestand te rapporteren) (bijvoorbeeld een knop klikken of `Alpine.store('sidebar').close()`), `SHOT_WAIT` wacht daarna extra milliseconden. `SHOT_SCROLLBARS=1` toont scrollbalken zoals in een echte browser (standaard verborgen), nuttig voor breedteproblemen door een verticale scrollbalk. Het script zet de viewport op de inhoudshoogte en legt dan de viewport vast; `captureBeyondViewport` gaf verouderde frames.

## 9. Gemaakte keuzes tijdens de bouw

| Onderwerp | Keuze |
|---|---|
| Tijd | Opslag UTC; weergave `Europe/Amsterdam` via `App\Support\DutchTime` en `FilamentTimezone`. `DomainModel::asDateTime()` converteert elke aangeleverde datum naar UTC. |
| Modellen | Alle domeinmodellen extenden `App\Support\Models\DomainModel`; factories blijven plat in `database/factories` (resolver in `AppServiceProvider`). |
| Instellingen | `EditionSettings` is een readonly value object met een Eloquent-cast; Filament bewerkt de velden onder `settings.*` en `EditEdition` merget ze zodat niets verloren gaat. |
| Rollen | `spatie/laravel-permission`, rolnamen uit `StaffRole`; `User::assignRole` loopt altijd door `RoleAssignmentGuard`. Het Filament-rollenveld valideert dezelfde combinaties. |
| Auditlog | `hash = HMAC-SHA256(salt, canonical json(prev_hash, created_at, actor, action, subject, payload, ip))`; model weigert update/delete; `audit:verify` loopt de keten na, `audit:verify --hash` geeft de laatste hash voor externe borging. |
| Backoffice | Filament 5.8; navigatiegroepen volgen de tien domeinen; kleuren en fonts uit de tokens; MFA verplicht. Eigen Vite-thema (`->viteTheme('resources/css/filament/admin/theme.css')`) dat Filament-klassen (`.fi-sidebar*`, `.fi-topbar*`, `.fi-section`, `.fi-ta-*`, `.fi-btn`) ongelaagd overschrijft; geen widgets maar een eigen dashboardpagina met view-model `App\Filament\Support\DashboardData`. Zie docs/02 §7. |
| Publiekssite | Anonieme Blade-componenten onder `x-ui.*`; nav/footer uit `config/site.php` tonen alleen bestaande routes. |
| Testketen en kluis | Modellen op eigen connectie via `App\Support\Models\TestingModel` en `VaultModel`; geen foreign keys tussen databases. In de testsuite houden alle drie de in-memory SQLite-connecties hun tabellen vast via `$connectionsToTransact` in `tests/TestCase.php`. Kluis alleen via `VaultService` (rollen intake en publisher; `asSystem()` voor jobs/listeners). |
| Intake-domein | `app/Domain/Intake` is de enige plek waar identiteit en testnummer samenkomen (ontvangst, nummering, aanleverslot). De testketen kent geen deelnemer; cross-domein-informatie (conflicten, allergenen) komt binnen via het contract `Testing\Contracts\ExclusionSource`, ingevuld in het Vault-domein. |
| Panel-app | Geen Sanctum: same-origin PWA op `/panel` met de gewone sessie (guard `web`), middleware `panelist`, JSON-eindpunten onder `/panel/api`. Panelleden hebben geen toegang tot Filament (`User::canAccessPanel`). Offline-wachtrij in IndexedDB met client-uuid; de server accepteert een uuid één keer. |
| QR-codes | `chillerlan/php-qrcode` (al aanwezig via Filament) in plaats van het geplande `bacon/bacon-qr-code`; wrapper `App\Support\QrCode`. Scannen in de browser met `BarcodeDetector`, handmatige invoer als noodroute. |
| Publicatie | `PublicationSchedule` berekent het volgende slot uit de instellingen; `publication:run` publiceert goedgekeurde batches op tijd én onthult bevroren provincies op hun `reveal_at`. Erkenningen ontstaan in Marketing via listeners op `BatchPublished`, `EditionFrozen` en `ProvinceRevealed` (embargo), zodat Ranking nooit Marketing importeert (architectuurtest). |
| Badges en socialkit | Server-side SVG met ingesloten woff2-fonts (`BrandFonts`), geen rasterizer nodig; PNG/pdf volgen met Browsershot. Beeld en tekst worden per aanvraag gerenderd (badge gecachet per dag), niets wordt vooraf opgeslagen; de zip ontstaat met `ZipArchive`. Deelknop: Web Share API met tekst + link, fallback kopiëren. Meting via `sendBeacon` op een route zonder CSRF-check (alleen ingelogd, zonder gevolgen). |
| Cadeaubonnen | Bon en QR-token gescheiden: het leesbare nummer is de noodroute, het 128-bit token (alleen sha256-hash in de database) zit in de QR en in de link naar de bon. Verzilveren is één atomaire `UPDATE … WHERE status = 'issued' AND campaign van dit bedrijf`; 0 rijen betekent al gebruikt, verlopen of andermans bon. Winnaars claimen met een eigen token (nieuwe link maakt de oude ongeldig). De scan-app is dezelfde portaalsessie (rollen eigenaar/medewerker/scanner), `BarcodeDetector` in de browser, geen aparte app of API. Tijdpad via `vouchers:tick` (uurlijks), niet via losse jobs, zodat een gemiste run zichzelf herstelt. Persoonsgegevens van winnaars worden na `vouchers.anonymise_from` overschreven. |
| Pers | Persberichten zijn platte tekst met alinea's (geen rich text), gegenereerd uit de bevroren snapshot en daarna handwerk van Communicatie; hergenereren overschrijft bewust. Geen `kit_token`-kolom: de perskit gebruikt Laravels ondertekende tijdelijke URL's, dus een link is niet te raden en vervalt vanzelf. Embargo is applicatielogica (`published_at`), de reveal-listener publiceert. |
| Sponsoring | Plaatsingslocatie als string (`province:{id}`, `entry:{id}` …) plus losse `province_id`/`entry_id` voor queries. Exclusiviteit wordt in één transactie met `lockForUpdate` op locatie en periode gecontroleerd, zodat twee medewerkers nooit dezelfde provinciepartner verkopen. Sponsororders zijn gewone orders (`orderable` = `Sponsor`) en lopen door dezelfde `MarkOrderPaid`; facturatie in het boekhoudpakket blijft via `AccountingGateway`. Logo via Filament `FileUpload` op de publieke schijf tot medialibrary is goedgekeurd. |
| Goede doelen | Reservering per factuurregel bij `OrderPaid` (idempotent per regel), met `basis_cents` en `amount_cents` vastgezet op het moment van betalen; de bestemming (`charity_id` of pot) kan later wisselen met de status van het doel, het bedrag nooit. `regional_pot_province_id` blijft ook na koppeling staan, zodat "alternatief" terug naar de juiste pot valt. Het percentage en de grondslag komen uit de editie-instellingen. |
| Nieuwsbrief | Dubbele opt-in met ondertekende links (3 dagen), geen eigen tokens; e-mail is uniek, afmelden zet `unsubscribed_at`. Export naar het mailplatform is bewust nog niet gebouwd (keuze platform open). |
| Paginacache | Eigen middleware met generatienummer in plaats van responsecache met tags: driver-onafhankelijk (werkt op de database-cache), invalidatie in één regel (`PublicCache::bump()`), geen tag-administratie per pagina. De prijs is dat één wijziging alle pagina's laat vervallen; met een TTL van 10 minuten en een CDN ervoor is dat acceptabel. |
| Turnstile | `App\Support\Turnstile`: zonder sleutels is de check uit (lokaal, tests). De widget staat alleen op de laatste stap van de wizard; het token gaat via `@this.set` naar Livewire en wordt server-side geverifieerd vóór het aanmaken van de inschrijving. |
| Kaart | OpenStreetMap-embed (iframe, `loading="lazy"`, `referrerpolicy="no-referrer"`) op het profiel zodra lat/lng bekend zijn; geen npm-dependency. Leaflet met eigen tiles kan dit later vervangen als Raphael dat wil. |
| Na publicatie | Een herberekende uitslag wordt na publicatie genegeerd door de koppeling; wijzigen kan alleen via een `CorrectionCase` (dossier openen zet de uitslag terug naar scorecontrole, twee goedkeuringen van Publicatie, nieuwe uitslag in de volgende batch). Beslisrondes en finale gebruiken nieuwe monsters via dezelfde ontvangst (ronde kiezen) en dezelfde scoreketen; alleen `LinkFinalizedResult` gedraagt zich per ronde anders. |

## 9. Team en werkwijze (uit de briefing)

Lead developer (architectuur, testketen, ranking), full-stack developer (portaal, commercie, bonnen), front-end developer (design system, publiekssite, templates), designer 2 dagen/week, tester vanaf release 2. Elke vrijdag demo voor Bennie op acceptatie. Release live pas na acceptatie en QA-gate (SEO, GEO, WCAG 2.2 AA, performance, security, privacy).

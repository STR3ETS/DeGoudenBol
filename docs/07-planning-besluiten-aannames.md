# 07 – Planning, open besluiten en werkaannames

## 1. Releases

| Datum | Release | Wat er dan werkt |
|---|---|---|
| vr 2 okt 2026 | Besluiten en kick-off | Besluiten 1, 2, 5 genomen; stack en hergebruik pizzaplatform bevestigd; tokens en hosting staan |
| ma 12 okt 2026 | **Release 1: Inschrijving** | Publiekssite basis, aanmelden met plekken per provincie, betalen en factureren, portaal met profiel, backoffice, rollen en 2FA |
| wo 28 okt 2026 | Proefrun testketen | Echte panelleden en proefmonsters door de hele keten t/m publicatie op acceptatie |
| vr 30 okt 2026 | **Release 2: Testketen** | Inname, kluis, panel-app, scorecontrole, vier-ogen, rankingengine, provinciepagina's, profielen, vertrouwelijk rapport |
| zo 1 nov 2026 | Start beoordelingen | Eerste sessies; publicatiebatches op vaste dagen |
| ma 7 dec 2026 | **Release 3: Campagne** | Badges met verificatie, socialkits, persberichten, cadeaubonnen met QR en scan-app, sponsorplaatsingen, goede doelen |
| wo 9 dec 2026 | Ketentest cadeaubonnen | Claim, uitgifte, verzilvering met echte telefoons bij twee proefondernemers |
| di 15 dec 2026 | **Release 4: Finale** | Bevriezing, beslisrondes, geplande publicatie, finaleronde; loadtest geslaagd |
| do 17 dec 2026 | Laatste testdag (voorstel) | Daarna verwerken en beslisrondes |
| vr 18 dec 2026 | Bevriezing | Definitieve Top 10; alle assets klaar onder embargo |
| ma 21 dec 2026 | **Hoofdpublicatie** | Top 10 en provinciewinnaars live; kits, mails en bonnenactie starten |
| wo 23 dec 2026 | Finaletest (voorstel) | Blind en vanaf nul, één testdag |
| ma 28 dec 2026 | Laatste verzilverdag | Bonnen vervallen automatisch |
| di 29 dec 2026 | Landelijke uitslag (voorstel) | Vóór de drukste verkoopdagen |
| ma 1 feb 2027 | Bakkers Academie live | Cursusdagen boeken en betalen |

Grootste risico: late besluiten. Iedere week vertraging op besluit 2 schuift release 1 één op één mee.

## 2. Wat Bennie moet aanleveren

| Wat | Wanneer |
|---|---|
| Besluiten 1, 2 en 5 | vr 2 okt 2026 |
| Definitieve teksten en beeld voor de publiekssite | wo 7 okt 2026 |
| Deelnamevoorwaarden en privacyverklaring (jurist) | vr 9 okt 2026 |
| Panel, testlocatie en labelprinter voor de proefrun | vr 23 okt 2026 |
| Actievoorwaarden cadeaubonnen, juridisch getoetst | vr 27 nov 2026 |
| Persberichttemplates en mediacontacten per provincie | vr 4 dec 2026 |
| Boekhoudpakket + btw-tarief per product (boekhouder) | vóór kick-off |

## 3. Open besluiten met advies en mijn werkaanname

Zolang een besluit open is, bouw ik volgens het advies uit de briefing en maak ik het alternatief mogelijk als instelling waar de briefing dat vraagt.

| Nr | Besluit | Advies briefing | Nodig vóór | Werkaanname in de bouw |
|---|---|---|---|---|
| 1 | Sluitingsdatum Voorlijsten en datum finale | Laatste testdag do 17 dec, bevriezing vr 18 dec, finaletest wo 23 dec, landelijke uitslag di 29 dec | vr 2 okt | Datums als editie-instellingen met deze defaults |
| 2 | Deelnamemodel en capaciteit: A (10 pakketten vooraf) of B (basisprijs, max 50, promotiepakket na bevriezing) | B met 50 plekken | vr 2 okt | Beide modellen ondersteund via `participation_model`; B is default; pakketcatalogus + rechtenmatrix generiek |
| 3 | Cadeaubonnenplicht alleen voor Definitieve Top 10? | Ja, in de deelnamevoorwaarden | ma 5 okt | Campagne wordt automatisch aangemaakt voor Top 10-entries |
| 4 | Panel: samenstelling, omvang, vergoeding, onafhankelijkheid | Pool met rooster; min 6 kaarten per monster; vaste vergoeding per sessie; namen pas na editie openbaar | vr 9 okt | Panelpool + rooster; `show_panel_after_edition = true` |
| 5 | Aanleveren, aankoop of mystery shopping | Aanleveren op tijdslot | vr 2 okt | `logistics_mode = delivery`; aankoopopdracht als alternatief pad, later uitgewerkt |
| 6 | Welke uitslagdetails openbaar | Rang + eindcijfer ≥ 5,0; deelscores alleen Definitieve Top 10; rapport vertrouwelijk | vr 16 okt | Instelling `public_subscores_scope = top10_final` |
| 7 | Hertest, bezwaar, correctie | Bezwaar alleen procedureel, binnen 3 werkdagen via portaal; twee beslissers buiten panel/registratie; hertest alleen bij erkende fout | ma 5 okt | Bezwaarformulier in portaal; correctiedossier met twee goedkeuringen |
| 8 | Grondslag 10%-reservering | 10 % van ontvangen bedragen excl. btw uit deelname en sponsoring; naar het doel van de betaler; Academie telt niet mee | ma 5 okt | `charity_basis = received`, vlag per factuurregel |
| 9 | Publieksprijs in 2026 | Niet in 2026 | vr 30 okt | Niet bouwen; module-stub in de domeinstructuur |
| 10 | Campagneprijs op zichtbaarheidsscore | 2027, nooit gekoppeld aan productranking | vr 30 okt | Niet bouwen |
| 11 | Techniek unieke QR-codes en verzilvering | Eigen platform, één keten | vr 30 okt | Zoals doc 04 §8 |
| 12 | Merkrichtlijnen, badges, geldigheid | Geldig tot uitslag 2027, jaartal altijd zichtbaar; Deelnemerbadge tot 21 dec | vr 13 nov | Geldigheid per erkenningstype als instelling |
| 13 | Landelijke lijst Top 5 of Top 10 | Top 5 | ma 5 okt | `national_list_length = 5` |
| 14 | Beoordelingsmodel 8 onderdelen of 10 criteria | 8 onderdelen, 100 punten | ma 5 okt | Geversioneerd model; placeholder-verdeling in doc 03 tot dossier er is |
| 15 | Welke oliebol en hoeveel stuks | Alleen met krenten en rozijnen; 8 stuks | ma 5 okt | Instellingen `product_variant`, `pieces_per_entry` |
| 16 | Ketens met meerdere vestigingen | Eén inschrijving per productielocatie | ma 5 okt | Unieke index `(edition_id, company_id)`; verkooppunten als `locations` |
| 17 | Provinciewinnaar die niet naar de finale kan | Finaleplek naar nummer 2; titel blijft bij nummer 1 | ma 5 okt | `finalist_fallback = next_in_line` |
| 18 | Sponsorcatalogus vast of maatwerk | Vaste producten tegen dossiertarieven; maatwerk alleen partners/hoofdsponsor | vr 9 okt | Productcatalogus met `custom`-product |
| 19 | Test- en rekeninstellingen | 3 uur, 6 kaarten, 8 monsters per sessie, rang op cijfer met één decimaal | vr 16 okt | Defaults in editie-instellingen; bijstellen na proefrun |
| 20 | Publicatieritme | Di en vr 12:00; geen tussenstanden na vr 11 dec; 21 dec twaalf provincies op vaste tijden | vr 16 okt | Instellingen `publication_schedule`, `quiet_period_from`, `province_reveal_schedule` |
| 21 | Klantreacties met sterren op profielen | Niet in 2026 | vr 16 okt | Niet bouwen |
| 22 | Externe borging van de uitslag | Ja: wekelijks laatste hash naar notaris/toezichthouder | vr 30 okt | Hashketen bouwen; export-command voor de hash |

Intern vóór kick-off: stack bevestigen en bepalen wat uit het pizzaplatform komt. Zolang de pizzaplatform-code er niet is, bouw ik login/2FA, portaal-layout, media-upload, Mollie/orders/facturatie, mailtemplates en CMS-blokken zelf in Laravel; die zijn later inwisselbaar.

## 4. Aanvullende eigen aannames (niet in de briefing; corrigeerbaar)

| Onderwerp | Aanname |
|---|---|
| Taal in code | Engels in code/tabellen, Nederlands in UI/routes/docs (doc 03) |
| Database | MySQL 8 met drie databases en drie gebruikers (doc 03); PostgreSQL blijft mogelijk |
| Auth deelnemers | Eigen Laravel-auth met magic link + wachtwoord; medewerkers via Filament-login met verplichte 2FA |
| Rollen | `spatie/laravel-permission` met rollen uit doc 03 en editie-scoped verbodscheck |
| Publieke id's | ULID's; slugs voor bedrijven en provincies |
| Cijfernotatie | Komma in de UI ("9,4"), punt in data |
| Afronding | Half-up op één decimaal (PHP `round($x, 1)`) |
| Cadeaubon-code | `GB{jj}-{3 letters}-{4 alfanumeriek}` zonder verwarrende tekens (geen 0/O, 1/I/L) |
| Panel-API | Sanctum-tokens per panellid per sessie; korte levensduur |
| Backoffice-navigatie | Op werk in plaats van op de tien domeinen: Bakkers, Testdagen, Uitslag, Campagne, Geld, met Instellingen in de voet van de sidebar; menunamen als taakwoorden; dashboard met seizoensbalk en "Jouw werk vandaag" per rol. Akkoord Raphael 25 sep 2026 na de UX-review (docs/02 §7). |
| Dark mode | Niet voor site/portaal/backoffice in 2026 |
| Knoptekst | Espresso op goud (contrast) i.p.v. room op goud uit het concept |
| Aanspreekvorm | "u/uw" richting bakkers (zoals concepten); neutraal richting consument |
| Rolverbod | Tot release 2 globaal per gebruiker (panellid ≠ intake/publicatie); editie-scoping volgt zodra de panelpool per editie bestaat |
| Tijdopslag | UTC in de database, `Europe/Amsterdam` bij weergave (`App\Support\DutchTime`, `FilamentTimezone`) |
| Filament | Versie 5.8 met ingebouwde TOTP-MFA (verplicht voor alle medewerkers), geen aparte passkey-koppeling in 2026 |
| Pakketprijs | Tot besluit 2: één basispakket van EUR 745 excl. btw (laagste tarief uit het plan) als gemarkeerde placeholder; aanpasbaar in de backoffice |
| Betalingen | Lokaal een nep-checkout (`PAYMENT_DRIVER=fake`); acceptatie en productie Mollie met webhook. Bankoverschrijving kan Financiën handmatig als betaald markeren |
| Deelnemersaccounts | Eigen tabel en guard (`participant`), nooit toegang tot de backoffice; e-mailadres is de sleutel, één account kan meerdere bedrijven hebben |
| Reservering | Een onbetaalde aanmelding houdt 60 minuten een plek vast (`RESERVATION_MINUTES`); daarna vervalt de plek automatisch |
| Factuur | Doorlopend per jaar (`2026-0001`); afzendergegevens staan als placeholders in `config/commerce.php` tot Bennie ze aanlevert |
| Publiek vóór publicatie | Bevestigde deelnemers staan met naam, plaats en goedgekeurde tekst op de site als "Deelnemer 2026", zonder cijfer |

## 5. Ontbrekende input (blokkerend voor specifieke onderdelen)

| Ontbreekt | Blokkeert | Tijdelijke oplossing |
|---|---|---|
| Exacte 8 onderdelen + puntverdeling + toelichting | Panel-app, scoreblok, "hoe werkt de test" | Placeholder-model in seeder (doc 03) |
| Pakketinhoud en rechtenmatrix | Aanmeldstap "pakket", portaal-marketingrechten | Generieke catalogus met dummy-pakketten |
| Criteria goede doelen | Beoordelingschecklist | Vrije checklist, later vullen |
| Logobestanden en beeld | Alles visueel | SVG-werklogo uit concepten; placeholders zonder Unsplash |
| Teksten publiekssite, voorwaarden, privacy, actievoorwaarden | Release 1 QA-gate | Lorem-vrije placeholders met duidelijke `[TEKST VOLGT]`-markering |
| Boekhoudpakket | Facturatiekoppeling | Interface `AccountingGateway` met `NullDriver`; Moneybird en Exact als drivers |
| Pizzaplatform-code | Hergebruik | Zelf bouwen, inwisselbaar houden |
| Testlocatie(s), labelprinter-type | Etiketten, slots | Generieke browser-print op 62 mm labels |

## 6. Risico's die ik in de gaten houd

- **Tijd**: 19 dagen tot release 1. Prioriteit ligt bij aanmelden + betalen + portaalbasis + backoffice-rollen.
- **Rangberekening**: fout hier is fataal; engine eerst, met tests, vóór UI.
- **Cadeaubonnen**: zeven dagen venster; atomaire verzilvering en offline-gedrag van de scan-app testen met echte telefoons.
- **Contrast**: gouden tekst op licht faalt AA; consequent `goud-tekst` gebruiken.
- **Embargo**: assets alleen via ondertekende URL's; geen publieke storage-paden vóór 21 december.
- **Cache-invalidatie**: publicatie moet precies de geraakte pagina's verversen; test met tags.

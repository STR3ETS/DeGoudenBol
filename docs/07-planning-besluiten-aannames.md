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

## 3a. Briefing 29 oktober 2026 (Bennie, voor Boyd · Eazyonline)

Een nieuwe uitvoeringsbriefing ("Technische briefing platform & sponsoring", versie 1.0) en het officiële rapportsjabloon zijn op 30 okt verwerkt. Waar de briefing een eerder besluit of advies verandert, geldt de briefing.

| Onderwerp | Was | Nu (briefing 29 okt) | Waar in de app |
|---|---|---|---|
| Deelnameprijs (besluit 2) | Placeholder EUR 745 | Standaard EUR 150; actieprijs EUR 75 (50%) na goedgekeurde socialmedia-actie | Pakket bij Geld > Pakketten; `discount_percentage`, `discount_hold_days` |
| Aanleveren (besluit 5, 15) | 8 stuks, alleen tijdslot | 20 oliebollen in doos of zak; zelf brengen op tijdslot of ophaalservice op vrijdag (EUR 45 voorrijkosten) | `pieces_per_entry` = 20; Testdagen > Ophaalvrijdagen en Ophaalservice; `pickup_fee_cents`, `change_deadline_hours` |
| Beoordelingsmodel (besluit 14) | Placeholder-verdeling | Acht onderdelen uit het rapport: smaak 25, structuur en luchtigheid 20, versheid 15, vulling en verhouding 15, uiterlijk 10, geur 5, vetopname en mondgevoel 5, totaalindruk 5 | Beoordelingsmodel versie 2 (migratie) |
| Landelijke lijst (besluit 13) | Top 5 | Top 10 | `national_list_length` = 10 |
| Publicatieritme (besluit 20) | Di/vr-batches met Voorlijst | Ranglijst geheim tot de hoofdpublicatie; persoonlijk rapport binnen 3 dagen na de keuring | `ranking_secret_until_publication` (aan), `report_due_days` = 3 |
| Vertrouwelijk rapport (doc 04 §4) | Alleen onder 5,0 | Persoonlijk rapport voor iedereen, als pdf te bewaren | Uitslag > Persoonlijke rapporten; portaal `/portaal/rapport` |
| Sponsorcatalogus (besluit 18) | Vijf producten | Basis sponsor 250, Deelnemerspartner 50 (+25), Top 10 partner 450 (+75), provinciale partner, landelijke partner en hoofdsponsor op maat | `/sponsoring`, `/sponsoring/aanmelden`, sponsoromgeving `/sponsor` |
| Organisatie | Placeholders | Nederlands Test Instituut, Voorstraat 14, 6702 DC Wageningen, KvK 42193967, contact Betsy | `config/commerce.php` (issuer), voorwaarden en privacy |
| Mails | Laravel-standaard | Huisstijl uit "Emailtemplate Uitslag" | `resources/views/vendor/mail`, `App\Support\Mail\GoldenMail` |

| Openingsactie (Bennie, 30 okt) | Keuze standaardprijs EUR 150 of kortingsactie EUR 75 | Tijdelijk één prijs: EUR 75 in plaats van EUR 150, direct betalen, geen socialmediabewijs. Optie: voor EUR 50 extra ook de naturel oliebol laten testen (introductieprijs). Bedragen excl. btw | Editie > Prijzen, korting en ophaalservice: `opening_offer_cents` (7500) en `plain_addon_cents` (5000); 0 zet ze uit, dan komen standaardprijs en kortingsactie terug. Tarief "Openingsactie", kolom `entries.plain_addon`, orderregels `opening_offer` (negatief) en `plain_addon` |

**Open bij de naturel-test:** de extra test is verkocht en staat zichtbaar bij de inschrijving, in het portaal en bij ontvangst. De testketen zelf (aantal stuks, eigen testnummer, scorekaarten, rapport en of de naturel een eigen ranglijst krijgt) is nog niet ingericht. Bennie moet dit vóór de eerste naturel-levering beslissen.

**Eigen aannames bij de briefing (corrigeerbaar):**

- EUR 150, EUR 75 en EUR 45 zijn **exclusief btw**, net als de sponsorprijzen. Open beslispunt; omzetten is een prijswijziging in de backoffice.
- Publieksnaam in mails en rapport is **De Gouden Bol** (briefing §2). Het aangeleverde mail- en rapportsjabloon zeggen "De Gouden Oliebol"; omzetten kan met `MAIL_BRAND_ACCENT=Oliebol` en `APP_NAME`.
- **Btw-nummer**: geen van de twee aangeleverde nummers wordt gebruikt; facturen tonen "[BTW-NUMMER WORDT BEVESTIGD]" tot `INVOICE_ISSUER_VAT` is gezet.
- Kortingsactie: de bakker kiest bij het aanmelden de actieprijs, levert de link (of een schermafbeelding) aan in het portaal, en betaalt pas na goedkeuring. De plek blijft 7 dagen vast. Afgewezen: opnieuw aanleveren of de standaardprijs betalen.
- Ophaalservice: één ophaalaanvraag per inschrijving; wijzigen tot 24 uur vooraf; terugbetalen bij annuleren gaat via de boekhouding. Na betaling van de ophaalservice kan de bakker niet zelf terug naar zelf brengen.
- Sponsoren hebben geen account maar een persoonlijke beheerlink (30 dagen geldig, opnieuw aan te vragen op `/sponsoring/beheer`).
- Vaste sponsorpakketten worden na betaling eerst gecontroleerd door de organisatie ("gecontroleerd zichtbaar"); pas daarna staan ze online. Zichtbaarheid loopt tot 31 december van het editiejaar.
- Top 10 partner is altijd een reservering zonder betaling; de koppeling met een deelnemer en de betaling volgen na de officiële uitslag. Top 10-vermeldingen ("Bakt met") verschijnen nooit vóór de hoofdpublicatie.
- Een korting of ophaalservice telt mee voor de 10%-reservering (de regels hebben `counts_for_charity`); de kortingsregel is negatief, zodat de grondslag het werkelijk betaalde bedrag is.

**Open beslispunten uit de briefing (§14), nog niet beslist:**

| Beslispunt | Werkaanname |
|---|---|
| Juiste btw-nummer en juridische factuurentiteit | Nederlands Test Instituut, btw-nummer volgt |
| EUR 150 en EUR 45 inclusief of exclusief btw | Exclusief |
| Precieze voorwaarden kortingsactie, bewijs en moment van goedkeuring | Link of schermafbeelding; handmatige goedkeuring; 7 dagen plek vast |
| Ophaalvrijdagen, capaciteit, routeplanning, wijzigingstermijnen | Vrijdagen en capaciteit in de backoffice; 24 uur; CSV-export voor een externe routeplanner |
| Aanleverperiode "1 november t/m 4 januari" botst met 21 december | De app gebruikt de datums van de editie (eerste en laatste testdag) |
| Publicatie landelijke ranglijst 21 december 2026 12:00 definitief? | Ja, als datum in de editie; de aftelling op site en portaal leest die datum |
| Grondslag 10% goede doelen | Ongewijzigd: besluit 8 (ontvangen bedragen excl. btw) |

**Inhoud die nog botst met de briefing.** De pagina's uit de site-export (home, test, provincies, pers) spreken nog van een "Voorlijst" vanaf 1 november met tussenstanden. Met de geheime ranglijst tonen de provinciepagina's tot 21 december geen cijfers. De FAQ en de deelnamevoorwaarden zijn aangepast; de overige teksten moet Bennie in de CMS-export bijwerken, of de instelling "Ranglijst geheim tot de hoofdpublicatie" uitzetten.

**Voorwaarden en privacy zonder invulplekken (verzoek Raphael 30 okt 2026).** Alle gele invulplekken, conceptmeldingen en "nog te beslissen"-blokken op `/voorwaarden`, `/voorwaarden/*` en `/privacy` zijn vervangen door definitieve tekst (versie 30 oktober 2026), met de app en de briefing als bron. Waar nog geen besluit lag, is dit gekozen; Bennie (en bij voorkeur een jurist) moet dit bevestigen:

- Contact voor privacy en klachten: info@degoudenbol.nl. Bevoegde rechter: Rechtbank Gelderland. Rechtsvorm van Nederlands Test Instituut weggelaten.
- Bewaartermijnen: aanmelding 2 jaar na de editie; uitslagen en scoreregister blijvend; cadeaubonwinnaars tot 31 maart, daarna geanonimiseerd; contact 1 jaar; sponsors 2 jaar (facturen 7 jaar); panelleden 2 jaar, daarna gepseudonimiseerd (doc 05).
- Annuleren door de deelnemer: geen terugbetaling na betaling. Annuleren door de organisatie: volledige terugbetaling. Aansprakelijkheid beperkt tot het betaalde bedrag (niet bij opzet of bewuste roekeloosheid). Wijzigingen van de voorwaarden 14 dagen vooraf aangekondigd.
- Klachten: bezwaar tegen een uitslag via het portaal binnen 3 werkdagen; overige meldingen binnen 14 dagen; ontvangstbevestiging binnen 2 werkdagen; beslissing binnen 3 werkdagen door twee medewerkers buiten panel en registratie.
- Cadeaubonnen: 21 t/m 28 december 2026; 18+ en in Nederland; ondernemer kiest de winnaars; foto en delen bij verzilvering zijn vrijwillig (de bon blijft geldig); de bonwaarde komt voor rekening van de ondernemer.
- Merkgebruik: landelijke erkenning heet "Landelijke lijst" (Top 10, zoals in de app); geldigheid volgens doc 04 §6.
- Privacy noemt Mailgun, Stripe en Google Tag Manager. Er is nog geen cookiebanner; vraag Bennie welke tags in GTM komen en of er toestemming nodig is.
- Deze pagina's komen uit de site-export: een nieuwe `tools/site-import.mjs` overschrijft ze. De teksten in de backoffice (`TermsVersion`, akkoord in de wizard) zijn nog niet gelijkgetrokken.

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
| Knoptekst | Espresso op goud (contrast) i.p.v. room op goud uit het concept. Uitzondering (besluit Raphael 30 okt 2026): de aanmeldknop in de navigatie heeft crème tekst, gelijk aan de website. Let op: crème op goud haalt het WCAG-contrast voor kleine tekst niet |
| Aanspreekvorm | "u/uw" richting bakkers (zoals concepten); neutraal richting consument |
| Rolverbod | Tot release 2 globaal per gebruiker (panellid ≠ intake/publicatie); editie-scoping volgt zodra de panelpool per editie bestaat |
| Tijdopslag | UTC in de database, `Europe/Amsterdam` bij weergave (`App\Support\DutchTime`, `FilamentTimezone`) |
| Filament | Versie 5.8 met ingebouwde TOTP-MFA (verplicht voor alle medewerkers), geen aparte passkey-koppeling in 2026 |
| Pakketprijs | Briefing 29 okt: EUR 150 excl. btw, actieprijs EUR 75 na goedgekeurde kortingsactie (zie §3a); aanpasbaar in de backoffice |
| Betalingen | **Besluit Raphael 29 okt 2026: Stripe in plaats van Mollie.** Lokaal een nep-checkout (`PAYMENT_DRIVER=fake`); acceptatie en productie Stripe Checkout (`PAYMENT_DRIVER=stripe`) met webhook `/webhooks/stripe`; de Mollie-koppeling blijft als alternatief in de code. Bankoverschrijving kan Financiën handmatig als betaald markeren |
| Deelnemersaccounts | Eigen tabel en guard (`participant`), nooit toegang tot de backoffice; e-mailadres is de sleutel, één account kan meerdere bedrijven hebben |
| Reservering | Een onbetaalde aanmelding houdt 60 minuten een plek vast (`RESERVATION_MINUTES`); daarna vervalt de plek automatisch |
| Factuur | Doorlopend per jaar (`2026-0001`); afzender Nederlands Test Instituut (briefing 29 okt), btw-nummer volgt |
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

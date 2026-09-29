# 04 – Processen en rekenregels

## 1. Kernproces: van aanmelding tot publicatie (negen stappen)

Links van de koppeling is alles anoniem; de koppeling met de deelnemer gebeurt pas na de definitieve score.

| # | Stap | Eigenaar | Entry-status | Wat de software doet |
|---|---|---|---|---|
| 1 | Aanmelding | Deelnemer | `registered` | KvK- en adresgegevens (PDOK), provincie, publicatiegegevens, allergenen, akkoord op voorwaarden (met versie). Plek per provincie gereserveerd. Betaling/factuur volgens deelnamemodel. |
| 2 | Inplannen | Deelnemer of testcoördinatie | `scheduled` | Aanleverslot kiezen (of aankoopopdracht bij mystery shopping). Instructies en QR-aanleverbewijs per mail. |
| 3 | Ontvangst | Ontvangst | `received` | QR scannen; tijd, temperatuur, aantal stuks en foto vastleggen. Versheidsklok start (`freshness_expires_at = received_at + venster`). |
| 4 | Registratie | Registratie | `numbered` | Oplopend testnummer (0001) en neutraal etiket via browser naar labelprinter. Koppeling alleen in de kluis (gelogd). |
| 5 | Sessie | Testcoördinatie | – | Monsters verdelen over sessies (max per sessie). Uitserveerschema per panellid, zonder conflicten en allergieën. Versheid verlopen → scoren geblokkeerd, opnieuw aanleveren (`freshness_expired`). |
| 6 | Beoordelen | Panel | `scored` | Scorekaart per monster: 8 onderdelen in hele punten, sterke punten, ontwikkelkansen. Indienen vergrendelt. |
| 7 | Scorecontrole | Scorecontrole | `reviewed` | Gemiddelden, afwijkers (> 15 punten van mediaan) en ontbrekende kaarten controleren; score definitief maken (minimum aantal kaarten). |
| 8 | Koppeling | Systeem, gelogd | `linked` | Testnummer wordt aan de deelnemer gekoppeld; publicatie-item aangemaakt. |
| 9 | Publicatie | Twee goedkeurders | `published` / `confidential` | Naam, provincie, cijfer en weergave controleren; publiceren in vaste batch (≥ 5,0) of vertrouwelijk terugkoppelen (< 5,0). |

**Inname en logistiek.** Aanleveren op tijdslot (advies) of aankoop door de organisatie (besluit 5). Testlocaties zijn een eigen entiteit. Versheidsvenster (3 uur) is een editie-instelling. Geen verpakking of logo komt de testruimte in.

**Panel-app.** Tablet-PWA, één monster per scherm: testnummer groot, 8 onderdelen met toelichting en lopend totaal. Na indienen vergrendeld, geen inzage in andere of eigen eerdere kaarten. Offline-wachtrij met client-side UUID's (idempotente sync, geen dubbele of verloren kaarten). Papieren kaart = noodroute: twee mensen voeren onafhankelijk in, moet gelijk zijn.

## 2. Scoreberekening

Per monster, over alle **geldige** kaarten `n` (n ≥ `min_valid_cards`, standaard 6):

```
voor elk onderdeel k:   gemiddelde_k = (1/n) · Σ_i s(i,k)
totaal_ruw              = Σ_k gemiddelde_k             // 0 … 100
cijfer                  = round(totaal_ruw / 10, 1)    // 0,0 … 10,0; afronding half-up
```

- Invoer per onderdeel: gehele punten binnen `0 … max_points` van het onderdeel.
- Afwijkingsvlag: een kaart waarvan het kaarttotaal meer dan `outlier_deviation_points` (15) van de **mediaan** van de kaarttotalen afwijkt, krijgt een vlag voor scorecontrole. Scorecontrole zoekt invoerfouten, wijzigt geen smaakoordeel.
- Het **afgeronde** cijfer bepaalt de rang; gelijke standen worden met de beslisregels uit §3 opgelost.
- Onder `publish_threshold` (5,0): niets openbaar; portaal toont "persoonlijk verbeteradvies beschikbaar".
- Optioneel: bekend referentiemonster af en toe mee laten lopen om paneldrift over november/december te zien.

Implementatie: `App\Domain\Testing\Services\ScoreCalculator` – pure functie, invoer = lijst kaarten + scoringsmodel + instellingen, uitvoer = `Result`-DTO met gemiddelden, totaal, cijfer, vlaggen. Eigen unit tests (afronding, minimum kaarten, outliers, ongeldige kaarten).

## 3. Rankingengine

Pure, geversioneerde functie: `App\Domain\Ranking\Engine\RankingEngineV2026::compute(Collection $publishedResults, EditionSettings $settings, ?RankingSnapshot $previous): RankingSnapshotDTO`. Dezelfde invoer geeft altijd dezelfde lijst; elke snapshot slaat `input_hash` op zodat hij achteraf na te rekenen is. Niemand kan de volgorde handmatig aanpassen.

**Fases per provinciale lijst**

| Fase | Wanneer | Status |
|---|---|---|
| Open | vanaf 1 november; publicatiebatches di/vr 12:00 | `provisional` – "Voorlopige Top 10" |
| Gesloten | na de laatste testdag (do 17 dec, besluit 1) | verwerken, scorecontrole, beslisrondes |
| Bevroren | vr 18 dec | `frozen` – "Definitieve Top 10", provinciewinnaars, finale-inschrijvingen |
| Gepubliceerd | ma 21 dec | assets en mails vrijgegeven; provincie-reveal op 12 vaste tijden |
| Finale | wo 23 dec test, di 29 dec uitslag | aparte ronde, `national` scope |

**Rekenregels per provincie**

1. Neem alle gepubliceerde resultaten van de provinciale ronde met cijfer ≥ 5,0.
2. Sorteer op cijfer (één decimaal), hoogste eerst.
3. Bij gelijk cijfer beslist achtereenvolgens het gemiddelde op: smaak → structuur en luchtigheid → versheid → vulling en verhouding (`tie_break_order` uit de instellingen, verwijst naar criteriacodes).
4. Nog gelijk: gedeelde plaats (`tie_group`). Op plaats 1 of op de grens van de Top 10 → vlag `needs_tie_break` ("beslissende beoordeling nodig").
5. Beslisronde test alleen de gelijke deelnemers opnieuw, blind en samen (nieuwe monsters, `round = tie_break`). Die uitkomst bepaalt hun onderlinge volgorde; het gepubliceerde cijfer blijft staan.
6. Plaats 1–10 = Voorlopige Top 10 tot de bevriezing, daarna Definitieve Top 10.
7. Labels uit vergelijking met de vorige snapshot: `new`, `up`, `down`, `same`. "Gedaald" krijgt een neutrale chip.

**Bevriezing** – in één transactie: definitieve snapshot per provincie, Top 10-erkenningen, provinciewinnaars, finale-inschrijvingen. Direct daarna (queue): badges, socialkits, persberichten en mails genereren onder embargo tot 21 december.

Opties (instellingen): **stille periode** (resultaten uit de laatste dagen pas op 21 december zichtbaar; advies: geen tussenstanden na vr 11 dec) en **provincie-reveal** (12 provincies op 21 december op 12 vaste tijden).

**Landelijke finale** – max. 12 finalisten, nieuwe testnummers F01–F12, vanaf nul; provinciale scores tellen niet mee. Zelfde blind protocol, bij voorkeur één testdag. Lengte landelijke lijst = instelling (advies Top 5). Overweeg deels ander panel (panelleden hebben de Voorlijst gezien).

**Randgevallen**

| Situatie | Afhandeling |
|---|---|
| < 10 publiceerbare resultaten in een provincie | Top 10 toont er minder; niets wordt opgevuld |
| Geen publiceerbaar resultaat in een provincie | Geen provinciewinnaar; finale heeft minder deelnemers |
| Gelijke stand op plaats 1 of rond plaats 10 na alle beslisregels | Beslisronde; bevriezing wacht daarop |
| Provinciewinnaar kan niet naar de finale | Instelling `finalist_fallback`: plek vervalt of naar nummer 2 (advies: naar nummer 2; provinciale titel blijft bij nummer 1) |
| Deelnemer trekt zich terug vóór bevriezing | Verdwijnt van de lijst; resultaat blijft in archief |
| Correctie na publicatie | Alleen via correctiedossier met twee goedkeuringen; nieuwe snapshot; melding aan betrokkenen |

**Dagelijkse herberekening**: job die alle gepubliceerde snapshots opnieuw berekent uit de brondata en afwijkingen meldt (integriteitsbewaking).

## 4. Publicatie en terugkoppeling

- Vaste batches (di/vr 12:00): nieuwe binnenkomer niet herleidbaar tot één sessie; vast nieuwsmoment.
- Flow: `ResultFinalized` → `LinkSampleToEntry` (kluis, gelogd) → publicatie-item in openstaande batch → indiener dient batch in → goedkeuring 1 → goedkeuring 2 (andere persoon) → `BatchPublished` → ranking herberekend → snapshot → cache-invalidatie van geraakte pagina's → erkenning "Officieel getest" → notificatie (alleen bij goed nieuws; daling wel zichtbaar in portaal).
- Vertrouwelijk rapport in het portaal: sterke punten eerst, dan concrete ontwikkelkansen, positieve toon. Eventueel eerste versie uit geanonimiseerde panelnotities via Claude API, altijd menselijke eindredactie. Verwijzing naar een cursus pas ná de uitslag.
- Uitslagdetails openbaar (besluit 6, advies): rang en eindcijfer voor iedereen ≥ 5,0; deelscores alleen voor de Definitieve Top 10; volledig rapport vertrouwelijk.
- Bezwaar (besluit 7, advies): alleen over de procedure, binnen 3 werkdagen via het portaal; twee beslissers buiten panel en registratie; hertest alleen bij erkende fout.

## 5. Deelnemersportaal

- **Dashboard**: tijdlijn van de inzending (aangemeld → ingepland → getest → uitslag) en openstaande acties.
- **Profiel**: bedrijfsnaam, verhaal, foto's, logo, adres of standplaatsen met seizoensopeningstijden en uitzonderingen, website, socials, specialiteiten. Wijzigingen aan openbare tekst → moderatie door Communicatie.
- **Planning**: aanleverslot kiezen/wijzigen, instructies, QR-aanleverbewijs.
- **Uitslag**: live positie op de Voorlijst en het vertrouwelijke rapport.
- **Marketing**: badges (SVG/PNG licht/donker, embedcode), socialkit per mijlpaal, drukwerk-pdf's, persbericht eigen regio.
- **Cadeaubonnen**: winnaars invoeren, status, scanner, rapportage.
- **Facturen**, **goed doel voordragen**, **extra medewerkersaccounts** (bv. alleen scannen), **bezwaar indienen**.

## 6. Erkenningen en badges

| Erkenning | Voorwaarde | Tekst op de badge | Geldig tot (besluit 12) |
|---|---|---|---|
| Deelnemer | Bevestigde deelname | Deelnemer De Gouden Bol 2026 | 21 december 2026 |
| Officieel getest | Gepubliceerd cijfer ≥ 5,0 | Officieel getest – De Gouden Bol 2026 | uitslag editie 2027 |
| Top 10 provincie | Definitieve Top 10 | Top 10 [provincie] – De Gouden Bol 2026 | uitslag editie 2027 |
| Provinciewinnaar | Nummer 1 van de provincie | Winnaar [provincie] – De Gouden Bol 2026 | uitslag editie 2027 |
| Landelijke lijst | Top 5 / Top 10 van Nederland (instelling) | Top 5 van Nederland – De Gouden Bol 2026 | uitslag editie 2027 |
| Landelijke winnaar | Nummer 1 van de finale | De Gouden Bol 2026 – Nummer 1 van Nederland | uitslag editie 2027 |

- Verificatiepagina `/erkenning/{code}`: bedrijf, categorie, jaar, geldigheid; na afloop "historisch".
- Embedcode laadt de badge vanaf ons platform en linkt naar de verificatiepagina (backlinks).
- Formaten: SVG en PNG (licht/donker) + print-ready pdf's (raamposter, raamsticker, toonbankkaart). Jaar en categorie altijd zichtbaar.
- Badges hangen **nooit** aan een pakket; alleen aan de ranking.

## 7. Socialmediakit

Automatisch per mijlpaal: bevestigde deelname, officieel getest, Top 10, provinciewinnaar, finalist, landelijke lijst, landelijke winnaar, start cadeaubonnenactie.

- Vier formaten: 1080×1080 en 1080×1350 (feed), 1080×1920 (story), 1200×630 (Facebook/linkvoorvertoning).
- Kant-en-klare teksten met #DeGoudenBol, tag van De Gouden Bol en korte link naar het profiel.
- Mobiel: één knop deelt afbeelding + tekst via de Web Share API (Instagram, Facebook, WhatsApp). Desktop: zip + tekst kopiëren.
- Profielpagina's krijgen een dynamische linkvoorvertoning (OG-image) met actuele status.
- Downloads en deelkliks meten voor evaluatie; nooit voor de productscore.
- Generatie: HTML/SVG-templates → Browsershot (headless Chromium) in de queue.

## 8. Cadeaubonnen en QR-verzilvering

Krapste venster: 21 december (uitslag) → 28 december (laatste verzilverdag). Hele keten geautomatiseerd en vóór 21 december getest (ketentest 9 december met echte telefoons).

**Omvang**: 10 bonnen × EUR 45 per Top 10-ondernemer; landelijk max. 1.200 bonnen / EUR 54.000. Ondernemer draagt de waarde; geen geld via het platform.

**Flow**

1. Ondernemer voert 10 winnaars in (naam, e-mail) – keuze van winnaars blijft handwerk op social media.
2. Platform mailt een claimlink; winnaar bevestigt zelf, accepteert actievoorwaarden (versie vastgelegd) en geeft eventueel beeldtoestemming.
3. Platform geeft digitale bon uit: pdf met QR per mail + link naar de bon.
4. Winnaar toont de bon; ondernemer scant in het portaal (scan-PWA) → verzilverd, of reden van weigering.
5. Na verzilveren vraagt het portaal om een verzilverfoto (alleen plaatsen met toestemming).

**Tijdpad**: start ma 21 dec · deadline winnaars wo 23 dec 12:00 (tekort → Bonbeheer vult aan en factureert door) · bonnen uiterlijk do 24 dec · laatste verzilverdag ma 28 dec, daarna automatisch verlopen.

**Techniek**

- Leesbaar bonnummer `GB26-XXX-XXXX` (bv. `GB26-GLD-7K3M`) + los, onraadbaar QR-token van 128 bits (gehasht opgeslagen).
- QR opent openbare controlepagina `/bon/{token}`: alleen status, ondernemer, geldigheid; geen persoonsgegevens; rate limiting.
- Verzilveren alleen in het portaal van de uitgevende ondernemer. Eén atomaire bewerking: `UPDATE vouchers SET status='redeemed', ... WHERE id=? AND status='issued' AND campaign_company_id=?` → 0 rijen = al gebruikt/verlopen. Twee telefoons tegelijk kunnen nooit dubbel verzilveren.
- Scanner toont voornaam, waarde, geldigheid. Handmatig bonnummer invoeren = noodroute.
- Winnaarspagina per ondernemer: voornaam + initiaal, alleen met toestemming.
- Instelbaar maximum per e-mailadres per editie.
- Rapportage per ondernemer: uitgegeven, geclaimd, verzilverd, verlopen.
- Optioneel: Apple/Google Wallet-pas.

**Juridisch**: laten toetsen op de Gedragscode Promotionele Kansspelen; selectiemethode en voorwaardenversie per actie vastgelegd; winnaarsgegevens minimaal en geanonimiseerd per 31 maart 2027. Geen Meta-API-koppeling.

## 9. Sponsoring, goede doelen en facturatie

Eén orderadministratie. Iedere factuurregel weet of hij meetelt voor de 10%-reservering; geen enkele regel verwijst naar een rangpositie.

**Deelnamepakketten (besluit 2)**

| Model | Hoe | Software |
|---|---|---|
| A – Deelnemers- & Sponsorplan | Tien pakketten per provincie, EUR 2.250 t/m 745 excl. btw, vooraf, elk één keer per provincie | Pakketvoorraad per provincie; direct afrekenen bij aanmelding |
| B – advies Conceptdossier | Vaste basisprijs voor iedereen; na de bevriezing een promotiepakket per behaalde categorie | Basisfactuur bij aanmelding; promotiefactuur automatisch na bevriezing |

Badges en titels hangen nooit aan een pakket. Wat per pakket verschilt staat in de rechtenmatrix (marketingdiensten). Advies bij A: pakketnamen zonder rangnummer en concreet verschil per pakket.

**Sponsorcatalogus (besluit 18)**

| Product | Tarief excl. btw | Bijzonderheden |
|---|---|---|
| Homepage | EUR 250 | Logo met link, gelabeld als sponsor |
| Bij deelnemer | EUR 50 eerste, EUR 25 per extra koppeling | Deelnemer bevestigt zelf; profiel toont "Bakt met [sponsor]" |
| Landelijke toppositie | EUR 450 eerste, EUR 75 per extra | Pas te koop zodra finalisten bekend zijn |
| Provinciepartner | Maatwerk | Exclusief per provincie; systeem voorkomt dubbele verkoop |
| Landelijk partner, hoofdsponsor, finale, goede doelen | Maatwerk | Eigen plaatsingen en periode |

Plaatsingen zijn eigen records (locatie, periode, exclusiviteit). Sponsoren zien nooit testgegevens; sponsor–deelnemer bestaat niet in het testdomein.

**Goede doelen**: voordragen via portaal (deelnemers) of backoffice (sponsoren): naam, KvK/RSIN, ANBI, regio, categorie, motivatie. Statussen: voorgedragen → in beoordeling → goedgekeurd / alternatief in overleg → gekoppeld → uitbetaald. 10%-reservering rekent automatisch over factuurregels met `counts_for_charity`; grondslag (besluit 8, advies): 10 % van ontvangen bedragen excl. btw uit deelname en sponsoring, naar het doel dat de betaler voordroeg; zonder goedgekeurd doel → regionale pot. Academie telt niet mee. Openbare pagina per doel: grondslag, bedrag, selectie, uitbetaaldatum.

**Facturatie**: orders → facturen in het boekhoudpakket (Moneybird/Exact) met Mollie-betaallink (iDEAL, creditcard). Omschrijvingen noemen de inhoud, nooit een positie. Btw per product (standaard 21 %). Creditnota's, herinneringen, dashboard per inkomstenstroom met openstaande 10%-reservering.

## 10. Bakkers Academie (januari 2027) en ontwikkelopties

Cursustypen: groepscursus (8–12), Premium Meesterdag, cursus met hertest, bedrijfstraining op locatie, teamtraining, persoonlijk verbetertraject, voorbereidingsdag. Data feb–nov, capaciteit, wachtlijst, boeken en betalen via Mollie (EUR 495–795 excl. btw p.p.p.d.). Herinneringen, presentielijst, materiaal, evaluatie, certificaat-pdf. Oefenbeoordelingen met dezelfde scorekaart in een eigen domein; tellen nooit mee. Docent begeleidt ondernemer → automatisch belangenconflict. Beoordelingsmodel per productsoort instelbaar.

| Optie | Advies |
|---|---|
| Publieksprijs (stemmodule, 1 stem per geverifieerd e-mailadres, botbescherming, los van panel) | 2027; anders uiterlijk release 3 |
| Zichtbaarheidsscore 30 punten (aparte module, eigen erkenning) | 2027; nooit gekoppeld aan productranking |
| Consumentenkaart met filters en "vandaag open" (PDOK) | 2026 als er ruimte is |
| Fysieke finale met live-modus (uitslag op afgesproken seconde, presentatiescherm) | Klein; kan in 2026 |
| Video-interviews (videoveld in profielen en nieuws) | Klein |
| Sponsor-selfservice | 2027 |

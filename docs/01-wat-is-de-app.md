# 01 – Wat is De Gouden Bol?

## In één alinea

De Gouden Bol is de landelijke oliebollenkeuring van Nederland, editie 2026. Bakkers en kramen melden zich per provincie aan, leveren oliebollen aan op een tijdslot, en een onafhankelijk panel beoordeelt ze **blind** (alleen testnummers) op een 100-puntenmodel met 8 onderdelen. Na scorecontrole en vier-ogengoedkeuring verschijnt het cijfer (vanaf 5,0) op de openbare **Voorlijst** per provincie. Op **maandag 21 december 2026** gaan de definitieve Top 10 per provincie en de 12 provinciewinnaars live; die spelen daarna een landelijke finale (blind, vanaf nul, nieuwe testnummers F01–F12). Erkenningen worden badges met verificatiepagina, socialmediakits en persberichten. Top 10-ondernemers geven elk 10 cadeaubonnen van EUR 45 uit met unieke QR-code, te verzilveren tot 28 december. Deelname, sponsoring en goede doelen (10%-reservering) draaien op één orderadministratie.

- **Opdrachtgever:** Bennie (organisatie De Gouden Bol)
- **Bouwer:** Eazyonline (Raphael)
- **Vorm:** modulaire Laravel-monoliet, één codebase, één database met afgeschermde schema's
- **Werkdomein lokaal:** https://degoudenbol.test (Laragon)

## Vier ingangen, één applicatie

| Ingang | Voor wie | Wat | Techniek |
|---|---|---|---|
| **Publiekssite** | Consumenten, pers, zoekmachines, AI-assistenten | Home, 12 provinciepagina's met Voorlijst, alle bakkers, bakkersprofiel, finale, hoe werkt de test, aanmelden, goede doelen & sponsoren, nieuws & pers, bon-check, badge-verificatie, archief per editie | Blade, server-rendered, volledige paginacache + CDN |
| **Deelnemersportaal** (+ scan-PWA) | Bakkers (deelnemers) en hun medewerkers | Aanmelden, betalen, profiel, aanleverslot, uitslag en vertrouwelijk rapport, badges/socialkit/drukwerk/persbericht, cadeaubonnen uitgeven en scannen, facturen, goed doel voordragen, extra accounts | Blade/Livewire; scanner als PWA met camera |
| **Panel-app** (PWA) | Panelleden, testcoördinatie | Eén monster per scherm, 8 onderdelen in hele punten, sterke punten/ontwikkelkansen, lopend totaal, offline-wachtrij, uitserveerschema | PWA op tablet; API geeft alleen testnummers |
| **Backoffice** | Organisatie: beheer, ontvangst, registratie, testcoördinatie, scorecontrole, publicatie, communicatie, bonbeheer, financiën | Edities & instellingen, gebruikers/rollen, inname, kluis, sessies, scorecontrole, publicatiebatches, correcties, sponsoren, plaatsingen, goede doelen, facturatie, audittrail | Filament |

Alleen de publiekssite loopt via de CDN; de andere drie praten direct met de applicatie.

## Het hart: de afgeschermde testketen

1. Het panel ziet alleen testnummers (0001, 0002, …; finale F01–F12).
2. De koppeltabel testnummer ↔ inschrijving staat in een apart **kluisschema**, versleuteld met een eigen sleutel. Elke inzage wordt gelogd: wie, wanneer, met welke reden.
3. Een scorekaart is na indienen vergrendeld en krijgt een hash ("het originele formulier"). Correctie alleen via scorecontrole, met reden, zichtbaar in de audittrail.
4. Publicatie vraagt twee goedkeuringen van twee verschillende personen; de indiener keurt nooit zelf.
5. Betaalstatus, sponsoring en socialmedia-gegevens bestaan niet in het rankingdomein.
6. De rangberekening is een pure, geversioneerde functie met eigen tests. Iedere publicatie bewaart een snapshot per provincie.
7. Alles voor 21 december wordt vooraf gegenereerd onder embargo; publiceren is een schakelaar.

## Rollen en functiescheiding

| Rol | Ziet identiteit deelnemer | Belangrijkste rechten |
|---|---|---|
| Beheerder (organisatie) | Ja | Edities, instellingen, gebruikers; **kan geen scores wijzigen** |
| Ontvangst & registratie | Ja | Inname vastleggen, testnummer toekennen, koppeltabel beheren |
| Testcoördinatie | Nee | Sessies plannen, monsters uitserveren, versheid bewaken |
| Panellid | Nee | Alleen eigen scorekaarten in de panel-app |
| Scorecontrole | Nee | Berekeningen en afwijkingen controleren, score definitief maken |
| Publicatie (twee personen) | Ja, na koppeling | Vier-ogengoedkeuring en publicatie |
| Communicatie | Alleen gepubliceerde data | Nieuws, pers, profielen modereren, socialkits |
| Bonbeheer | Ja | Cadeaubonnen, winnaars, verzilvering |
| Financiën | Ja | Facturen, betalingen, sponsorgelden, 10%-reservering |
| Deelnemer en medewerkers | Alleen eigen bedrijf | Profiel, facturen, uitslag, badges; medewerkers eventueel alleen bonnen scannen |
| Sponsor | Nee | Eigen logo, links en koppelingen |
| Pers | Nee | Embargo-perskit via een tijdelijke link |

**Harde regels in de code**

1. Binnen één editie is een panellid nooit ook ontvangst, registratie of publicatie.
2. Panel-app en scorecontrole krijgen via de API alleen testnummers; deelnemersgegevens staan in een domein waar die rollen geen leesrecht op hebben.
3. Scorekaart na indienen vergrendeld; correctie alleen via scorecontrole met reden, in de audittrail.
4. Publicatie = twee goedkeuringen van twee verschillende personen; indiener keurt nooit zelf.
5. Een gemeld belangenconflict sluit het panellid uit voor dat monster via het uitserveerschema, zonder naam te tonen.
6. Iedere inzage in de koppeltabel wordt gelogd.
7. Betaalstatus, sponsoring en social bestaan niet in het rankingdomein.
8. Panelleden zien hun eerdere scores niet terug (blind voor de finale).

Alle medewerkersaccounts: tweestapsverificatie verplicht. Deelnemers: e-mail + wachtwoord of magic link. Consumenten: nooit een account.

## Kerngetallen editie 2026 (allemaal editie-instellingen)

| Instelling | Waarde 2026 (advies briefing) | Bron |
|---|---|---|
| Provincies | 12 | vast |
| Capaciteit per provincie | 50 (ontwerpen voor 600 landelijk); model A zou 10 zijn | besluit 2 |
| Beoordelingsmodel | 8 onderdelen, samen 100 punten; invoer in hele punten | besluit 14 |
| Publicatiedrempel | cijfer ≥ 5,0 | dossier |
| Minimum aantal geldige kaarten per monster | 6 | besluit 19 |
| Maximum monsters per sessie | 8 | besluit 19 |
| Versheidsvenster ontvangst → test | 3 uur | besluit 19 |
| Afwijkingsvlag scorecontrole | > 15 punten van de mediaan | briefing |
| Afronding | één decimaal; afgeronde waarde bepaalt de rang | briefing |
| Stuks per inzending / soort | 8 stuks, met krenten en rozijnen | besluit 15 |
| Provinciale lijst | Top 10 (Voorlopig tot bevriezing, daarna Definitief) | dossier |
| Landelijke lijst | Top 5 (dossier) of Top 10 (plan) → instelling, advies Top 5 | besluit 13 |
| Finalisten | max. 12, nieuwe testnummers F01–F12, vanaf nul | briefing |
| Publicatieritme | dinsdag en vrijdag 12.00 uur; stille periode na vr 11 dec; provincie-reveal 21 dec op 12 vaste tijden | besluit 20 |
| Cadeaubonnen | 10 × EUR 45 per Top 10-ondernemer; landelijk max. 1.200 bonnen / EUR 54.000 | dossier |
| Deelnamepakketten | A: tien pakketten EUR 2.250–745 vooraf; B: basisprijs + promotiepakket na bevriezing (advies B) | besluit 2 |
| Sponsortarieven | Homepage EUR 250; bij deelnemer EUR 50 + 25; landelijke toppositie EUR 450 + 75; partners maatwerk | besluit 18 |
| 10%-reservering | 10% van ontvangen bedragen excl. btw uit deelname en sponsoring | besluit 8 |
| Btw | standaard 21%, per product instelbaar | boekhouder |
| Badge-geldigheid | tot uitslag editie 2027; Deelnemerbadge tot 21 december | besluit 12 |

## Tijdlijn (vandaag: 23 september 2026)

| Datum | Mijlpaal |
|---|---|
| vr 2 okt | Besluiten 1, 2, 5 genomen; kick-off; stack en tokens staan |
| ma 12 okt | **Release 1: Inschrijving** |
| wo 28 okt | Proefrun testketen op acceptatie |
| vr 30 okt | **Release 2: Testketen** |
| zo 1 nov | Start beoordelingen; publicatiebatches di/vr 12.00 |
| ma 7 dec | **Release 3: Campagne** (badges, socialkits, pers, cadeaubonnen, sponsoring, goede doelen) |
| wo 9 dec | Ketentest cadeaubonnen met echte telefoons |
| vr 11 dec | Laatste tussenstand (stille periode start) |
| di 15 dec | **Release 4: Finale** (bevriezing, beslisrondes, geplande publicatie, finaleronde, loadtest) |
| do 17 dec | Laatste testdag (voorstel) |
| vr 18 dec | Bevriezing; Definitieve Top 10; assets klaar onder embargo |
| **ma 21 dec** | **Hoofdpublicatie**: Top 10 en provinciewinnaars live; kits, mails, bonnenactie starten |
| wo 23 dec 12.00 | Deadline winnaars invoeren; finaletest (voorstel) |
| do 24 dec | Uiterlijk bonnen uitgegeven |
| ma 28 dec | Laatste verzilverdag; bonnen vervallen daarna automatisch |
| di 29 dec | Landelijke uitslag (voorstel) |
| ma 1 feb 2027 | Bakkers Academie live |
| 31 mrt 2027 | Winnaarsgegevens cadeaubonnen geanonimiseerd |

## Scope

**Editie 2026 (in de software):** publiekssite met 12 provinciepagina's, Voorlijst, profielen, finale en nieuws; aanmelding, betaling, facturatie, portaal; inname, blind protocol, panel-app, scorecontrole, vier-ogenpublicatie; rankingengine met bevriezing, provinciewinnaars en finale; badges met verificatie, socialmediakit, persberichten per provincie; cadeaubonnen met QR en verzilvering; sponsorplaatsingen en goede doelen.

**Begin 2027:** Bakkers Academie (cursussen vanaf februari), publieksprijs, zichtbaarheidsscore (30 punten), sponsor-selfservice. Consumentenkaart met "vandaag open" alleen in 2026 als er ruimte is.

**Buiten de software:** keuze van cadeaubonwinnaars op social media (handwerk ondernemer), fysieke logistiek, juridische teksten (jurist levert; wij bouwen de plekken).

## Wat ik NIET heb en dus als aanname invul

- De exacte 8 onderdelen met puntverdeling en toelichting (Conceptdossier). Bekend uit de briefing: Smaak 0–25, Structuur en luchtigheid 0–20, "vulling en verhouding" (15 punten gaan over vulling), versheid. Placeholder in `03-domeinen-en-datamodel.md`.
- Pakketinhoud en rechtenmatrix (Deelnemers- & Sponsorplan).
- Beoordelingscriteria goede doelen (checklist uit dossier).
- Logobestanden, definitieve teksten, foto's, persberichttemplates, voorwaarden.
- Pizzaplatform-code (herbruikbare bouwstenen: login/2FA, portaal-layout, media-upload, Mollie/orders/facturatie, mailtemplates, CMS-blokken, deploy). Zolang die niet beschikbaar is, bouw ik die onderdelen zelf in Laravel.
- Boekhoudpakket van Bennie (Moneybird of Exact Online).

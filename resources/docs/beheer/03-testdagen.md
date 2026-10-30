# Testdagen

> Testlocaties, aanlevermomenten, ontvangst, testnummers, sessies en het panel.

## Wat moet er klaarstaan vóór de eerste testdag?

Minstens één actieve testlocatie (Instellingen > Testlocaties), aanleverslots per testdag (Testdagen > Aanleverslots), een actief beoordelingsmodel dat optelt tot honderd punten (Instellingen > Beoordelingsmodel), panelleden met een account (Testdagen > Panelleden) en per dag een of meer testsessies (Testdagen > Testsessies). Inrichten laat zien wat er nog ontbreekt.

## Hoe maak ik aanlevermomenten (slots) aan?

Bij Testdagen > Aanleverslots: kies de testlocatie, de dag, begin- en eindtijd en hoeveel bakkers er in dat slot passen. Bakkers kiezen daarna zelf een slot in het portaal en krijgen een aanleverbewijs met QR-code en aanlevercode. Maak slots ruim vóór de testdag, anders kan niemand kiezen.

## Wat is de testdag-cockpit?

Testdagen > Testdag (`/admin/testdag`) is het scherm voor de dag zelf: kerncijfers, een tijdlijn met de aanleverslots en de sessies, per sessie de volgende stap als knop (schema genereren, starten, afsluiten), de versheidsklokken en de leveringen per slot. Het scherm ververst elke minuut. Met de pijlen bovenin kijk je naar een andere dag.

## Hoe neem ik oliebollen in ontvangst?

Op Testdagen > Ontvangst en registratie. Scan de QR-code van het aanleverbewijs of typ de aanlevercode (vorm XXXX-XXXX). Vul het aantal stuks, de temperatuur en een foto in, eventueel een opmerking. Zet "Direct testnummer toekennen en etiket klaarzetten" aan als je dezelfde persoon bent die registreert. Vanaf dit moment loopt de versheidsklok: het panel moet binnen het versheidsvenster (standaard drie uur) proeven.

## Wat is een testnummer en wie mag de koppeling zien?

Elk monster krijgt een oplopend testnummer (0001, 0002, ...) en een neutraal etiket. Het panel, Testcoördinatie en Scorecontrole zien alleen dat nummer. De koppeling tussen nummer en bakker staat in een aparte, versleutelde kluis; alleen Ontvangst en registratie en Publicatie (na de koppeling) kunnen erbij en iedere inzage wordt vastgelegd. Dit is de kern van de blinde keuring; omzeil het nooit, ook niet "even snel".

## De versheid van een monster is verlopen. Wat nu?

Het panel kan het monster dan niet meer scoren en de inschrijving krijgt de status "Versheid verlopen". De bakker krijgt bericht en kiest in het portaal een nieuw aanlevermoment; hij levert opnieuw aan en krijgt een nieuw testnummer. Op testdagen krijgt Testcoördinatie een half uur van tevoren een waarschuwing in de bel en op het overzicht.

## Hoe plan ik een testsessie?

Bij Testdagen > Testsessies: kies de dag, de locatie, de begintijd, welke monsters (maximaal acht per sessie, instelling) en welke panelleden. Klik daarna op "Uitserveerschema genereren": het systeem verdeelt de monsters over de panelleden en sluit automatisch panelleden uit bij een gemeld belangenconflict of allergie, zonder namen te tonen. Dan "Sessie starten"; de panel-app toont de panelleden hun monsters. Na afloop "Sessie afsluiten", daarna kan niemand meer kaarten indienen.

## Hoe werkt de panel-app?

Panelleden loggen in op `/panel/inloggen` met hun e-mailadres en wachtwoord (account bij Testdagen > Panelleden, rol Panellid). Ze zien één monster per scherm: testnummer groot, de acht onderdelen met uitleg en een lopend totaal. Indienen vergrendelt de kaart; eerdere kaarten en kaarten van anderen zijn nooit zichtbaar. Vóór een sessie kunnen ze een belangenconflict melden (bijvoorbeeld: ik ken deze bakker), dan krijgen ze dat monster niet. Zonder internet blijft de app werken en synchroniseert hij later.

## Een panellid heeft een papieren kaart ingevuld. Hoe voer ik die in?

Alleen als noodroute. Open de sessie en kies "Papieren kaart invoeren". Twee verschillende medewerkers voeren dezelfde kaart onafhankelijk in; alleen als beide invoeren gelijk zijn, telt de kaart mee.

## Hoe maak ik een panellid aan?

Bij Testdagen > Panelleden: naam, e-mailadres, allergenen en eventuele bedrijven waar een belangenconflict mee bestaat. Het panellid krijgt een account voor de panel-app. Een panellid mag binnen dezelfde editie nooit ook Ontvangst, Registratie of Publicatie zijn; het systeem laat dat niet toe.

## Hoeveel kaarten zijn er nodig voor een geldige score?

Minimaal zes geldige scorekaarten per monster (instelling). Minder kaarten betekent dat Scorecontrole de score niet definitief kan maken; het monster moet dan in een volgende sessie opnieuw geproefd worden.

## Wat doe ik als een bakker op de testdag zonder aanleverbewijs komt?

Zoek de inschrijving op via het zoekveld (naam of KvK) en gebruik de aanlevercode die daar staat. Heeft de bakker geen aanlevermoment gekozen, overleg dan met Testcoördinatie of er ruimte is; kies in dat geval alsnog een slot bij Aanleverslots en neem daarna in ontvangst.

## Hoeveel oliebollen levert een bakker aan?

Twintig, in een doos of zak zonder naam of logo, met de deelnemercode erop of het aanleverbewijs erbij. Het aantal is de instelling "Stuks per inzending" bij Instellingen > Editie; FAQ, portaal, mails en aanleverbewijs lezen die instelling.

## Hoe werkt de ophaalservice?

Een bakker kan in het portaal kiezen voor laten ophalen in plaats van zelf brengen. Dat kan alleen op vrijdagen die bij Testdagen > Ophaalvrijdagen open staan en nog plek hebben. Hij vult adres, contactpersoon, telefoon en instructies in en betaalt de voorrijkosten (EUR 45 excl. btw) direct. Pas na betaling is de ophaalservice actief; hij krijgt dan een bevestiging met zijn deelnemercode.

## Hoe zet ik een ophaalvrijdag open?

Testdagen > Ophaalvrijdagen > Vrijdag toevoegen. Kies de datum (alleen vrijdagen worden geaccepteerd) en het maximum aantal adressen. Met "Open voor aanvragen" uit kun je een vrijdag tijdelijk sluiten zonder bestaande aanvragen te raken.

## Hoe plan ik de ophaalroute?

Open Testdagen > Ophaalservice en filter op de vrijdag. Selecteer de adressen en kies "Inplannen bij chauffeur", of plan per regel met chauffeur en volgorde. De bakker krijgt dan één mail dat het ophaalmoment is ingepland. Met "Route-export (CSV)" download je de adressen in volgorde voor een externe routeplanner; elk adres heeft ook een link naar Google Maps.

## Welke statussen heeft een ophaalaanvraag?

Aangevraagd (nog niet betaald), betaald, ingepland, onderweg, opgehaald en ontvangen. De knoppen in de lijst zetten de volgende stap. Zodra Ontvangst de oliebollen registreert, gaat de aanvraag vanzelf op ontvangen en krijgt de bakker de mail dat de keuring is gestart.

## Een bakker wil een andere vrijdag of toch zelf brengen. Wat nu?

Een andere vrijdag kiest hij zelf in het portaal, tot 24 uur vooraf (instelling "Wijzigen tot"). Zolang hij nog niet betaald heeft, vervalt de ophaalaanvraag vanzelf als hij een tijdslot kiest. Heeft hij de ophaalservice al betaald en wil hij toch zelf brengen, annuleer dan de aanvraag bij Testdagen > Ophaalservice; terugbetalen gaat via de boekhouding.

## Krijgen bakkers een herinnering voor het aanleveren?

Ja. Elke dag om 10:00 krijgt iedere bakker die de volgende dag een aanlevermoment heeft één herinnering met tijd, adres en deelnemercode.

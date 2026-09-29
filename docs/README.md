# De Gouden Bol – werkdocumentatie voor de bouw

Deze map is het werkgeheugen voor het bouwen van het platform. Alles hier is afgeleid uit de aangeleverde bronnen en aangevuld met eigen keuzes die expliciet als **aanname** zijn gemarkeerd.

## Bronnen (in de projectroot)

| Bestand | Rol |
|---|---|
| `De_Gouden_Bol_Technische_briefing_platform.pdf` | Eazyonline, versie 23 september 2026. **Leidend** voor scope, proces, rollen, datamodel, techniek, planning en besluiten. |
| `concept1.html` | Homepage "1A: Het Register Soft". **Leidend voor huisstijl.** Basis voor home, ranglijstblok, "hoe werkt het", aanmeldformulier. |
| `concept2.html` | Overzichtspagina "Alle bakkers" met filterbalk en bakkerkaarten. |
| `concept3.html` | Bakkersprofiel met hero, scoreblok, galerij, openingstijden, kaart. |

Niet in bezit (wel genoemd in de briefing): Compleet Conceptdossier 2026, Deelnemers- & Sponsorplan, Draaiboek, code van het pizzaplatform, logobestanden, definitieve teksten en juridische teksten. Zie `07-planning-besluiten-aannames.md` voor wat dat betekent.

## Leesvolgorde

1. `01-wat-is-de-app.md` – wat het is, voor wie, welke ingangen, kerngetallen, tijdlijn, scope
2. `02-huisstijl-design-system.md` – tokens, typografie, componenten, per oppervlak, wat niet meegaat
3. `tokens.json` – het tokenbestand (bron voor CSS, Tailwind, Filament, e-mail)
4. `03-domeinen-en-datamodel.md` – tien domeinen, entiteiten, naamgeving, database-indeling, harde regels
5. `04-processen-en-rekenregels.md` – negen stappen, scoreformule, rankingengine, publicatie, badges, cadeaubonnen, commercie
6. `05-techniek-en-architectuur.md` – stack, mappenstructuur, events, beveiliging, integraties, lokale omgeving
7. `06-publiekssite-paginas.md` – paginakaart, live data per pagina, tekstcorrecties, SEO
8. `07-planning-besluiten-aannames.md` – releases, open besluiten met werkaannames, ontbrekende input, risico's
9. `08-bouwplan.md` – concrete bouwvolgorde per release, pakketten, tests

## Kernwaarheden die nooit mogen sneuvelen

- Het panel ziet **alleen testnummers**. De koppeling naar een deelnemer bestaat uitsluitend in de versleutelde kluis.
- Publiceren vereist **twee verschillende goedkeurders**; de indiener keurt nooit zelf.
- Betaling, sponsoring en socialmedia-activiteit **bestaan niet** in het rankingdomein.
- De rangberekening is een **pure, geversioneerde functie**; iedere publicatie bewaart een snapshot.
- Alles wat per jaar kan verschillen is een **editie-instelling**, geen code.
- Eén tokenbestand voedt site, portaal, backoffice, panel-app, badges, socialkits en e-mails.

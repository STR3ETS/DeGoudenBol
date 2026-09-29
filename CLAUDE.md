@AGENTS.md

# De Gouden Bol – projectcontext

Landelijke oliebollenkeuring (editie 2026) voor opdrachtgever Bennie, gebouwd door Eazyonline. Eén Laravel-monoliet met vier ingangen: publiekssite, deelnemersportaal (+ scan-PWA), panel-PWA en Filament-backoffice. Hoofdpublicatie: maandag 21 december 2026. Release 1 (inschrijving): maandag 12 oktober 2026.

## Werkdocumentatie – lees vóór het bouwen

- `docs/README.md` – index, bronnen, leesvolgorde
- `docs/01-wat-is-de-app.md` – wat, voor wie, rollen, kerngetallen, tijdlijn, scope
- `docs/02-huisstijl-design-system.md` + `docs/tokens.json` – kleuren, typografie, componenten, per oppervlak
- `docs/03-domeinen-en-datamodel.md` – tien domeinen, entiteiten, NL↔EN-naamgeving, drie databases, harde regels
- `docs/04-processen-en-rekenregels.md` – negen stappen, scoreformule, rankingengine, publicatie, badges, cadeaubonnen, commercie
- `docs/05-techniek-en-architectuur.md` – stack, mappenstructuur, events, beveiliging, integraties, env
- `docs/06-publiekssite-paginas.md` – paginakaart, tekstcorrecties, SEO
- `docs/07-planning-besluiten-aannames.md` – releases, 22 open besluiten met werkaannames, ontbrekende input
- `docs/08-bouwplan.md` – bouwvolgorde per release, pakketten, tests

Bronnen in de projectroot: `De_Gouden_Bol_Technische_briefing_platform.pdf` (leidend voor alles), `concept1.html` / `concept2.html` / `concept3.html` (leidend voor huisstijl en UI).

## Onwrikbare regels

- Het panel ziet alleen testnummers. De koppeling naar een deelnemer bestaat uitsluitend in de versleutelde kluis (eigen connectie, eigen sleutel); elke inzage wordt gelogd.
- Publicatie vereist twee verschillende goedkeurders; de indiener keurt nooit zelf.
- Betaalstatus, sponsoring en socialmedia-gegevens bestaan niet in het rankingdomein.
- De rangberekening is een pure, geversioneerde functie met eigen tests; iedere publicatie bewaart een snapshot.
- Scorekaarten zijn na indienen onveranderlijk; correcties zijn aparte, goedgekeurde records.
- Alles wat per jaar kan verschillen is een editie-instelling, geen code.
- Gouden tekst op lichte achtergrond is `#8A5A00` (goud-tekst); `#C8860A` is alleen accent/achtergrond. Knoptekst op goud is espresso.

## Conventies

- Code, tabellen en kolommen in het Engels; UI, routes, lang-strings en docs in het Nederlands. Vertaaltabel in `docs/03`.
- Communicatie met de gebruiker in het Nederlands.
- Werk `docs/` bij zodra een besluit of aanname verandert; `docs/07` is de plek voor besluiten.
- Bewaar geen verzonnen cijfers, namen of citaten in seeders of views; gebruik duidelijke placeholders.

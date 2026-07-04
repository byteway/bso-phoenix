# User Story - Live route schermvullend toggle

## Story

Als geautoriseerde gebruiker wil ik tijdens een actieve of recente tocht kunnen wisselen tussen een schermvullende live routeweergave en het standaard dashboardformaat,
zodat ik tijdens het varen beter overzicht houd op de afgelegde route zonder de context van het dashboard te verliezen.

## Achtergrond

Op kleinere schermen of in fel buitenlicht is de standaard kaartweergave soms te compact. Een snelle toggle naar fullscreen verhoogt leesbaarheid en operationeel overzicht.

## Doel

- Snelle wissel tussen compacte kaart en fullscreen kaart.
- Beter zicht op routeverloop tijdens varen.
- Behoud van huidige gebruikersflow in dashboard.

## In Scope

- Toggle-knop in frontend dashboard bij live routekaart.
- Twee weergavestanden:
  - Standaard (huidige layout)
  - Schermvullend (fullscreen kaart)
- Terugschakelen naar standaardweergave zonder dat route- of sessiestatus verloren gaat.
- Toets/actie om fullscreen te sluiten (bijv. knop en Escape).

## Out of Scope

- Nieuwe kaartprovider.
- Wijzigingen in GPS-registratie of routeberekening.
- Native mobile app-specifieke fullscreen API buiten browsermogelijkheden.

## Rollen en rechten

- `bso_phoenix_read`: mag route bekijken en fullscreen toggle gebruiken.
- `bso_phoenix_write`: idem, plus bestaande mutatieacties.
- `bso_phoenix_manage`: idem.

## Functionele eisen

1. Gebruiker kan fullscreen aan/uit zetten met 1 actie.
2. Routekaart blijft actief en synchroon in beide weergaven.
3. Bij terugkeer naar standaardweergave blijft de dashboardstatus behouden.
4. De fullscreenweergave is bruikbaar op desktop, tablet en mobiel.
5. De toggle veroorzaakt geen reset van actieve trip, kaartlaag of trackpoints.

## Acceptatiecriteria

1. Toggle van standaard naar fullscreen werkt zonder pagina-herlaad.
2. Toggle van fullscreen terug naar standaard werkt zonder dat routegegevens verdwijnen.
3. Tijdens actieve tocht groeit de route door in fullscreenweergave.
4. Escape/sluitactie verlaat fullscreen betrouwbaar.
5. UI blijft bruikbaar en leesbaar op mobiele viewport.

## Niet-functionele eisen

- Overgang moet snel en zonder zichtbare flicker verlopen.
- Bestaande performance van route-updates blijft minimaal gelijk.
- Geen regressie op bestaande start/stop en trackpoint-flow.

## Definition of Done

1. Frontend implementatie afgerond met toggle + styling.
2. Relevante documentatie bijgewerkt (README, roadmap, ontwerp waar nodig).
3. Testplan toegevoegd of uitgebreid met fullscreen-togglescenario's.

# Testplan: Anonieme Dashboard Demo Modus

## Testdoel
Valideren dat anonieme gebruikers een beperkte demo zien zonder data-opslag, terwijl ingelogde gebruikers het volledige dashboard behouden.

## Testomgeving
- WordPress met plugin bso-phoenix actief
- Frontend pagina met dashboard shortcode
- Browser met geolocatie permissie

## Testdata
- Geen vaste testdata nodig voor anonieme flow
- Voor ingelogde regressietest: bestaande testaccount met dashboardtoegang

## Testcases

### T1 - Anonieme weergave is beperkt
Stappen:
1. Open een incognito venster.
2. Ga naar de frontend dashboard pagina.

Verwacht resultaat:
- Dashboard titel zichtbaar.
- Start/Stop knoppen zichtbaar.
- Live route kaart zichtbaar inclusief fullscreen knop.
- Overige onderdelen (wachtrij, samenvattingen, log, todo, kosten) zijn verborgen.

### T2 - Start demo-route zonder opslag
Stappen:
1. Klik als anonieme gebruiker op Start route.
2. Beweeg fysiek of simuleer GPS-locatieverandering.

Verwacht resultaat:
- Status gaat naar actief (demo).
- Route wordt live getekend.
- Feedback meldt dat niets wordt opgeslagen.
- Geen nieuwe trips/trackpoints in database.

### T3 - Stop demo-route zonder opslag
Stappen:
1. Start een demo-route.
2. Klik op Stop route.

Verwacht resultaat:
- Route stopt direct.
- Feedback meldt dat niets is opgeslagen.
- Geen trip wordt aangemaakt in database.

### T4 - Refresh sluit actieve demo-route
Stappen:
1. Start een demo-route als anonieme gebruiker.
2. Ververs de pagina.

Verwacht resultaat:
- Melding toont dat actieve demo-route is gesloten bij verversen.
- Melding toont dat niets is opgeslagen.
- Starten van een nieuwe demo-route blijft mogelijk.

### T5 - Fullscreen blijft werken in demo
Stappen:
1. Open live routekaart als anonieme gebruiker.
2. Klik op Volledig scherm.
3. Sluit fullscreen met Sluiten of Escape.

Verwacht resultaat:
- Kaart schakelt correct naar fullscreen en terug.
- Route blijft zichtbaar.

### T6 - Ingelogde regressie
Stappen:
1. Log in met een geldige gebruiker.
2. Open dashboard pagina.
3. Start en stop een normale route.

Verwacht resultaat:
- Volledig dashboard zichtbaar.
- Bestaande functionaliteit werkt ongewijzigd.
- Opslag- en synchronisatielogica blijft actief.

## Uitvoeringsoverzicht
- T1: Nog uit te voeren
- T2: Nog uit te voeren
- T3: Nog uit te voeren
- T4: Nog uit te voeren
- T5: Nog uit te voeren
- T6: Nog uit te voeren
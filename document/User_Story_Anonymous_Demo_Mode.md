# User Story: Anonieme Dashboard Demo Modus

## Doel
Potentiele gebruikers moeten zonder account een live demo van de route-ervaring kunnen zien, zonder dat data wordt opgeslagen in de database.

## User Story
Als anonieme gebruiker
wil ik een eenmalig te gebruiken dashboard zien dat niets opslaat
zodat ik de plugin kan ervaren en gemotiveerd word om me aan te melden voor volledige functionaliteit.

## Scope Voor Anonieme Gebruiker
- Dashboard titel
- Start knop
- Stop knop
- Live route scherm
- Volledig scherm knop op live route
- Route wordt live getekend op de kaart tijdens de sessie

## Belangrijke Randvoorwaarden
- Er wordt geen enkele demo-data opgeslagen in de database.
- Er worden geen trip-records of trackpoints naar server-endpoints gestuurd.
- Demo-route bestaat alleen in browsergeheugen.

## Gedrag Bij Pagina Verversen
- Er verschijnt een melding dat niets is opgeslagen.
- Eventuele actieve demo-route wordt als gesloten beschouwd.
- Gebruiker kan daarna alleen opnieuw starten met een nieuwe demo-route.

## Gedrag Voor Ingelogde Gebruikers
- Ingelogde gebruikers houden het bestaande volledige dashboard.
- Bestaande opslag-, wachtrij- en synchronisatielogica blijft actief voor ingelogde gebruikers.

## Acceptatiecriteria
1. Als anonieme gebruiker zie ik alleen dashboardtitel, start/stop en live routekaart met fullscreen.
2. Als anonieme gebruiker start ik een route en zie ik live track op de kaart.
3. Tijdens anonieme demo wordt niets naar database of server opgeslagen.
4. Bij verversen krijg ik een waarschuwing dat data niet bewaard is.
5. Na verversen kan ik opnieuw starten met een nieuwe demo-route.
6. Als ingelogde gebruiker zie ik het volledige bestaande dashboard en opslaggedrag.
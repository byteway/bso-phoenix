# Release Notes - v1.2.1

**Plugin:** BSO Phoenix  
**Release datum:** 7 juli 2026  
**Type:** Patch release

## Samenvatting

Release v1.2.1 levert een conversiegerichte frontend-uitbreiding: een anonieme demo-modus voor het dashboard zonder database-opslag, plus documentatie-updates.

## Highlights

- Story 8 opgeleverd: anonieme dashboard demo-modus
- Anonieme gebruiker ziet alleen:
  - dashboardtitel
  - Start/Stop
  - live routekaart inclusief fullscreen
- Demo-route wordt uitsluitend client-side bijgehouden (geen trip/trackpoint opslag op server)
- Bij paginaverversing wordt een actieve demo-route gesloten en krijgt gebruiker expliciete melding
- Ingelogde gebruikers behouden het volledige dashboard met bestaande opslag/sync-logica

## Technische impact

- Frontend anonieme modus toegevoegd in:
  - `assets/js/phoenix-frontend.js`
  - `assets/css/phoenix-frontend.css`
  - `templates/frontend-dashboard.php`

## Documentatie

Toegevoegd/gewijzigd:

- `document/User_Story_Anonymous_Demo_Mode.md`
- `document/Testplan_Anonymous_Demo_Mode.md`
- `document/CHANGES.md`
- `document/Release_Notes_1.2.0.md` (post-release update)
- `README.md`

## Upgrade notes

- Geen datamodelmigratie nodig
- Geen aanpassing aan bestaande data
- Aanbevolen: handmatige regressietest voor ingelogde routeflow en anonieme demo-flow

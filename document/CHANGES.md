# CHANGES

## 2026-07-07

### Added

- Story 8 opgeleverd: anonieme dashboard demo-modus zonder database-opslag.
- Nieuwe documentatie toegevoegd:
  - `document/User_Story_Anonymous_Demo_Mode.md`
  - `document/Testplan_Anonymous_Demo_Mode.md`

### Changed

- Frontend dashboard ondersteunt nu anonieme sessieroute:
  - alleen dashboardtitel, Start/Stop en live routekaart zichtbaar voor anonieme gebruiker
  - live route wordt alleen client-side bijgehouden (geen serverpersist)
  - actieve anonieme route wordt bij verversen gesloten met expliciete melding
- README bijgewerkt met Story 8 en documentatielinks.
- Release notes v1.2.0 bijgewerkt met post-release update voor anonieme demo-modus.

## 2026-07-05

### Added

- Productieklare deploy artifact opgebouwd: `dist/bso-phoenix-v1.2.0-prod-20260705.zip`.
- Runbook toegevoegd voor beheer en productie-uitrol: `document/Runbook_Beheer_En_Deploy.md`.
- Trackpoints beheer uitgebreid met:
  - selectietools `Alles selecteren` en `Selectie omkeren`
  - limietopties `25`, `50`, `100`
  - paginering met `Vorige` en `Volgende`

### Changed

- Trackpoints data-opvraag ondersteunt nu pagination op queryniveau (offset + count).
- User story en testplan voor trackpoints bijgewerkt op schaalbaarheid en beheerbaarheid.

### Notes

- Laatste gepushte commit: `0028023`.
- Branch: `main`.

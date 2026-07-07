# Release Notes - v1.2.0

**Plugin:** BSO Phoenix  
**Release datum:** 5 juli 2026  
**Type:** Minor release

## Samenvatting

Release v1.2.0 levert de geplande uitbreidingen uit de roadmap en rondt een volledige hardening-slag af op import/export validatie en foutrapportage.

Belangrijkste resultaat:

- Stories 1 t/m 7 opgeleverd
- Exportketen (CSV, GPX, ZIP) gehard
- Handmatige acceptatietest hardening: 10/10 PASS (GO)

## Highlights

- Offline/sync queue met retry voor log + foto-upload
- Caption- en sorteerbeheer voor bestaande logfoto's
- Recente tochtsamenvatting met directe GPX-download
- Uniforme frontend feedbackcomponent voor acties en fouten
- Rapportage exportpakket (ZIP met CSV + GPX + metadata)
- Bulkacties selectie/verwijderen voor trips, TODO en kosten
- Schermvullende live routekaart toggle in frontend dashboard

## Hardening import/export

Toegevoegd in v1.2.0:

- Datumrange-validatie op exportpaden
- Consistente `export_error` foutterugkoppeling in admin
- CSV write-checks op exports
- ZIP preflight/checks (open/write/close/read)
- Coordinaatvalidatie voor GPX-export
- Ongeldige GPX-data niet meer opgenomen in rapportage ZIP

## Acceptatie

Referentie testdocument:

- `document/Testplan_Hardening_Import_Export_Validatie_En_Foutrapportage.md`

Resultaat:

- Totaal: 10
- PASS: 10
- FAIL: 0
- BLOCKED: 0
- Eindadvies: GO

## Upgrade notes

- Geen datamodelmigratie nodig voor deze release
- Bestaande data blijft bruikbaar
- Advies: handmatige testdata (ongeldige trackpoints gebruikt tijdens validatietests) opschonen/herstellen

## Deploy artifact

- Productie-zip opgebouwd op 2026-07-05:
	- `dist/bso-phoenix-v1.2.0-prod-20260705.zip`

## Post-release update (beheerbaarheid trackpoints)

Toegevoegd na v1.2.0 tag als operationele verbetering:

- Alles selecteren knop in Trackpoints beheer
- Selectie omkeren knop in Trackpoints beheer
- Limietopties 25/50/100 voor trackpoints per pagina
- Paginering voor trackpoints met Vorige/Volgende

## Post-release update 2 (anonieme demo-modus)

Toegevoegd na v1.2.0 tag als conversiegerichte frontend verbetering:

- Nieuwe Story 8: anonieme dashboard demo-modus zonder database-opslag
- Anonieme gebruiker ziet alleen dashboardtitel, Start/Stop en live routekaart met fullscreen
- Route wordt alleen tijdelijk in de browser bijgehouden (geen trip- of trackpoint-opslag op server)
- Bij pagina verversen wordt actieve demo-route gesloten en verschijnt melding dat niets is opgeslagen
- Ingelogde gebruikers blijven het volledige dashboard met bestaande opslag- en synchronisatielogica gebruiken
- Referentie documentatie:
	- `document/User_Story_Anonymous_Demo_Mode.md`
	- `document/Testplan_Anonymous_Demo_Mode.md`

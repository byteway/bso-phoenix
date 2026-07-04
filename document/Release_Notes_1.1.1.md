# Release Notes - v1.1.1

**Plugin:** BSO Phoenix  
**Release datum:** 3 juli 2026  
**Type:** Patch release

## Samenvatting

Release v1.1.1 richt zich op consistentie, instellingen-doorwerking en beheerverbeteringen boven op de functionele uitbreiding uit v1.1.0.

Deze patch maakt de plugin betrouwbaarder in dagelijks gebruik door:

- uninstall-instellingen correct te laten werken
- brandstofschatting te koppelen aan de ingestelde verbruikswaarde
- afstands- en snelheidseenheden consistent door te voeren
- valuta-instellingen daadwerkelijk te gebruiken in kostenbeheer
- CSV-export van de gecombineerde rapportage toe te voegen

## Highlights

- Brandstofschatting gebruikt nu standaard de ingestelde `fuel_use_lph`
- `delete_data_on_uninstall` wordt correct uit de settings-tabel gelezen
- Afstandseenheid werkt nu door in adminoverzichten, CSV-export en kaartmetadata
- Snelheid wordt weergegeven als `km/u` of `kn` afhankelijk van de afstandseenheid
- Kostenbeheer gebruikt ingestelde valuta in formulieren, overzichten en opslag
- Rapportagepagina heeft nu een eigen CSV-export

## Fixes en verbeteringen

### 1. Route- en brandstoflogica

- Nieuwe trips nemen standaard het ingestelde brandstofverbruik over
- Brandstofschatting bij tripafsluiting valt terug op de plugininstelling wanneer nodig
- Kaartmeta toont nu ook berekende routeafstand

### 2. Instellingen consistent doorgevoerd

- Afstandseenheid (`km` of `nm`) wordt gebruikt in:
  - tripoverzichten
  - rapportages
  - kaartmeta
  - trip-CSV-export
- Snelheidseenheid volgt automatisch de gekozen afstandseenheid
- Valuta-instelling wordt gebruikt in kostenbeheer en rapportageweergave

### 3. Rapportage en export

- CSV-export toegevoegd aan de gecombineerde rapportagepagina
- Rapportage-export bevat kernmetrics, kostensoorten en taakstatussen

### 4. Uninstall gedrag

- De uninstall-routine leest nu de instelling uit `phoenix_settings`
- Legacy fallback op de oude WordPress option blijft behouden

## Upgrade notes

- v1.1.1 is een patchrelease zonder datamodelwijzigingen
- Bestaande data en eerdere routes blijven volledig bruikbaar
- Upgrade vanaf v1.1.0 vereist geen migratiestap

## Volgende stappen (v1.2.x)

- Offline captains log queue met foto-upload retry
- Logfoto captions en sortering in frontend galerij
- Route samenvatting per tocht met directe GPX-download
- Uniforme frontend feedbackcomponent voor acties en fouten
- Rapportage exportpakket (ZIP met CSV + GPX)

Zie ook: `document/Roadmap_v1.2.0.md`

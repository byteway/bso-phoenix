# Phoenix Logboek App

WordPress-plugin voor de Phoenix, een zelfgemaakt motorjacht van 7 meter lang, 3 meter breed, 80 cm diepgang, 2,35 meter hoogte en een dieselmotor met topsnelheid van ongeveer 8 km/uur.

> **Status / v1.2.0 (juli 2026)**
> Deze plugin is ingericht als app-achtige WordPress-oplossing voor een boot: de Phoenix, inclusief route logging, logboek, taken, kosten, bootprofiel, instellingen, rapportages en export-hardening.

## Inhoudsopgave

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Usage](#usage)
5. [Project Structure](#project-structure)
6. [MVP Status en Roadmap](#mvp-status-en-roadmap)
7. [Deploy Productie](#deploy-productie)
8. [Documentatie](#documentatie)
9. [Contributing](#contributing)
10. [License](#license)

## Features

- Bootprofiel voor de Phoenix met vaste eigenschappen zoals lengte, breedte, diepgang, hoogte, gewicht en brandstoftype
- Start/stop GPS route logging vanaf mobiel of tablet
- Vastleggen van trackpoints, duur, afstand en gemiddelde snelheid
- Brandstofinschatting op basis van gemiddeld verbruik
- Captain's log per dag met foto’s
- GPX kaartgeneratie en delen via download of e-mail
- TODO-beheer met status en prioriteit
- Kostenbeheer voor varen, onderhoud en onderhoudsartikelen
- Dashboard met vaartstatus, live routekaart en recente routeweergave
- Bootprofielbeheer in wp-admin
- Instellingen voor GPS-interval, brandstofverbruik, valuta en afstandseenheid
- Gecombineerde rapportagepagina voor trips, kosten, logboek en taken
- Dedicated admin-UI voor trackpoints bewerken/repareren per trip, inclusief filters op datumrange en laadlimiet
- Bulkacties voor selectie/verwijderen van trips (admin), TODO's en kosten (frontend)
- Schermvullende live routekaart in frontend dashboard
- Hardening op import/export validatie en foutrapportage (CSV/GPX/ZIP)

## Requirements

- WordPress omgeving
- PHP-ondersteuning voor standaard WordPress pluginfunctionaliteit
- Mobiel of tablet met GPS voor route logging
- Beheerder met voldoende rechten voor configuratie en onderhoud

## Installation

1. Plaats de pluginmap in de WordPress plugin directory.
2. Activeer de plugin via het WordPress dashboard.
3. Controleer of het bootprofiel voor de Phoenix is aangemaakt.
4. Stel indien nodig bootgegevens, brandstofverbruik en dashboardvoorkeuren in.

## Usage

### Bootprofiel

De plugin ondersteunt standaard één boot: de Phoenix.

Vast te leggen bootgegevens:

| Gegeven | Waarde |
|---------|--------|
| Naam | Phoenix |
| Type | Zelfgemaakt motorjacht |
| Lengte | 7 meter |
| Breedte | 3 meter |
| Diepgang | 80 cm |
| Hoogte | 2,35 meter |
| Motor | Diesel |
| Topsnelheid | Ongeveer 8 km/uur |
| Gewicht | Ongeveer 4 ton |

### Route logging

1. Open het dashboard.
2. Druk op Start om een tocht te beginnen.
3. Laat de app GPS-coördinaten vastleggen.
4. Druk op Stop om de tocht af te sluiten.
5. Bekijk duur, afstand, gemiddelde snelheid en geschat brandstofverbruik.

### Captain's log

Gebruik het logboek voor dagelijkse notities en voeg eventueel foto’s toe via admin of frontend.

### GPX en delen

Na een tocht kan de route als GPX worden gegenereerd en gedeeld via download of e-mail.

### TODO en kosten

Gebruik de aparte formulieren voor onderhoudstaken en kostenregistratie.

### Rapportages

Gebruik de rapportagepagina in wp-admin voor een gecombineerd periodeoverzicht van trips, kosten, logboekitems en open taken.

## Deploy Productie

Huidig productie-artifact:

- `dist/bso-phoenix-v1.2.0-prod-20260705.zip`

Standaard deployflow:

1. Maak backup van database en pluginmap.
2. Upload het zipbestand via WordPress plugin upload.
3. Activeer/update plugin.
4. Voer rooktest uit op dashboard, rapportages en trackpoints beheer.

Voor volledige operationele stappen en rollback: zie runbook.

## Project Structure

```text
bso-phoenix/
├── admin/
│   ├── class-phoenix-admin-page.php
│   ├── class-phoenix-boat-admin.php
│   ├── class-phoenix-cost-admin.php
│   ├── class-phoenix-log-admin.php
│   ├── class-phoenix-reports-admin.php
│   ├── class-phoenix-trackpoints-admin.php
│   ├── class-phoenix-settings-admin.php
│   └── class-phoenix-todo-admin.php
├── assets/
│   ├── css/
│   │   └── phoenix-frontend.css
│   └── js/
│       └── phoenix-frontend.js
├── document/
│   ├── Functional_Design.md
│   ├── Release_Notes_1.0.0.md
│   ├── Release_Notes_1.1.0.md
│   ├── Release_Notes_1.1.1.md
│   ├── Testplan_Trackpoints_Bewerken_Repareren.md
│   ├── User_Story_Trackpoints_Bewerken_Repareren.md
│   └── Technical_Design.md
├── includes/
│   ├── class-phoenix-ajax.php
│   ├── class-phoenix-boat-service.php
│   ├── class-phoenix-cost-ajax.php
│   ├── class-phoenix-cost-service.php
│   ├── class-phoenix-db.php
│   ├── class-phoenix-frontend.php
│   ├── class-phoenix-log-ajax.php
│   ├── class-phoenix-log-service.php
│   ├── class-phoenix-plugin.php
│   ├── class-phoenix-settings-service.php
│   ├── class-phoenix-todo-ajax.php
│   ├── class-phoenix-todo-service.php
│   └── class-phoenix-trip-service.php
├── templates/
│   └── frontend-dashboard.php
├── bso-phoenix.php
├── README.md
└── uninstall.php
```

## MVP Status en Roadmap

### Geimplementeerd

- [x] Eén bootprofiel voor de Phoenix
- [x] GPS route logging met start en stop
- [x] Captain's log met foto’s
- [x] GPX generatie en delen
- [x] TODO-beheer
- [x] Kostenbeheer
- [x] Dashboard met live routekaart
- [x] Instellingenmodule
- [x] Rapportagepagina
- [x] Story 1: offline/sync queue met retry voor log + foto-upload
- [x] Story 2: caption- en sorteerbeheer voor bestaande logfoto's
- [x] Story 3: route samenvatting per tocht met directe GPX-download
- [x] Story 4: uniforme actiefeedback en foutmeldingen op frontend
- [x] Story 5: rapportage exportpakket (ZIP met CSV + GPX + metadata)
- [x] Story 6: bulkacties selectie/verwijderen voor trips, TODO's en kosten
- [x] Story 7: schermvullende live routekaart toggle
- [x] Hardening: export-validatie en foutafhandeling (10/10 acceptatietests PASS)
- [x] Dedicated admin-UI voor trackpoints bewerken/repareren

### Verdere doorontwikkeling

- [x] Formele v1.2.0 release notes / tag
- [ ] Aanvullende regressietests rond exports en validatie

## Documentatie

Meer details staan in:

- [Functional Design](document/Functional_Design.md)
- [Technical Design](document/Technical_Design.md)
- [Installatie en Acceptatietest Stap 7](document/Installatie_En_Acceptatietest_Stap7.md)
- [Acceptatietest Run Template Stap 7](document/Acceptatietest_Run_Template_Stap7.md)
- [Roadmap v1.2.0](document/Roadmap_v1.2.0.md)
- [Runbook Beheer En Deploy](document/Runbook_Beheer_En_Deploy.md)
- [Changes](document/CHANGES.md)
- [Testplan Hardening Import Export Validatie En Foutrapportage](document/Testplan_Hardening_Import_Export_Validatie_En_Foutrapportage.md)
- [User Story Trackpoints Bewerken Repareren](document/User_Story_Trackpoints_Bewerken_Repareren.md)
- [Testplan Trackpoints Bewerken Repareren](document/Testplan_Trackpoints_Bewerken_Repareren.md)
- [Release Notes v1.0.0](document/Release_Notes_1.0.0.md)
- [Release Notes v1.1.0](document/Release_Notes_1.1.0.md)
- [Release Notes v1.1.1](document/Release_Notes_1.1.1.md)

## Contributing

1. Werk in kleine, testbare stappen.
2. Houd documentatie en implementatie synchroon.
3. Test route logging, logboek, TODO’s en kosten na wijzigingen.
4. Stem ingrijpende wijzigingen eerst af met de beheerder van de Phoenix-setup.

## License

GPL-2.0-or-later

# Phoenix Logboek App

WordPress-plugin voor de Phoenix, een zelfgemaakt motorjacht van 7 meter lang, 3 meter breed, 80 cm diepgang, 2,35 meter hoogte en een dieselmotor met topsnelheid van ongeveer 8 km/uur.

> **MVP / v1.0.0**
> Deze plugin is ingericht als app-achtige WordPress-oplossing voor één boot: de Phoenix.

## Inhoudsopgave

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Usage](#usage)
5. [Project Structure](#project-structure)
6. [MVP Status en Roadmap](#mvp-status-en-roadmap)
7. [Documentatie](#documentatie)
8. [Contributing](#contributing)
9. [License](#license)

## Features

- Bootprofiel voor de Phoenix met vaste eigenschappen zoals lengte, breedte, diepgang, hoogte, gewicht en brandstoftype
- Start/stop GPS route logging vanaf mobiel of tablet
- Vastleggen van trackpoints, duur, afstand en gemiddelde snelheid
- Brandstofinschatting op basis van gemiddeld verbruik
- Captain's log per dag met foto’s
- GPX kaartgeneratie en delen via download of e-mail
- TODO-beheer met status en prioriteit
- Kostenbeheer voor varen, onderhoud en onderhoudsartikelen
- Dashboard met vaartstatus, tankadvies en live routepreview

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

Gebruik het logboek voor dagelijkse notities en voeg eventueel foto’s toe.

### GPX en delen

Na een tocht kan de route als GPX worden gegenereerd en gedeeld via download of e-mail.

### TODO en kosten

Gebruik de aparte formulieren voor onderhoudstaken en kostenregistratie.

## Project Structure

```text
bso-phoenix/
├── admin/
│   └── class-phoenix-admin-page.php
├── assets/
│   ├── css/
│   │   └── phoenix-frontend.css
│   └── js/
│       └── phoenix-frontend.js
├── document/
│   ├── Functional_Design.md
│   └── Technical_Design.md
├── includes/
│   ├── class-phoenix-db.php
│   ├── class-phoenix-frontend.php
│   ├── class-phoenix-plugin.php
│   └── class-phoenix-trip-service.php
├── templates/
│   └── frontend-dashboard.php
├── bso-phoenix.php
├── README.md
└── uninstall.php
```

## MVP Status en Roadmap

### Geimplementeerd in ontwerp

- [x] Eén bootprofiel voor de Phoenix
- [x] GPS route logging met start en stop
- [x] Captain's log met foto’s
- [x] GPX generatie en delen
- [x] TODO-beheer
- [x] Kostenbeheer
- [x] Dashboard met vaartstatus en tankadvies

### Verdere doorontwikkeling

- [ ] Geautomatiseerde GPS- en kaartintegratie
- [ ] Uitgebreidere rapportages
- [ ] Eventuele geavanceerde offline ondersteuning
- [ ] Extra gebruikersrechten en rollen

## Documentatie

Meer details staan in:

- [Functional Design](document/Functional_Design.md)
- [Technical Design](document/Technical_Design.md)

## Contributing

1. Werk in kleine, testbare stappen.
2. Houd documentatie en implementatie synchroon.
3. Test route logging, logboek, TODO’s en kosten na wijzigingen.
4. Stem ingrijpende wijzigingen eerst af met de beheerder van de Phoenix-setup.

## License

GPL-2.0-or-later
# bso-phoenix

# Phoenix Logboek App

WordPress-plugin voor de Phoenix: een app-achtige dashboardomgeving voor varen, loggen, onderhoud en kostenbeheer rond een enkele boot.

> **MVP status (juli 2026): klaar voor doorontwikkeling**
>
> - Pluginversie in code: `1.1.1`
> - v1.2.0 stories 1 t/m 5 zijn technisch opgeleverd op de feature branch
> - Inclusief Story 5: deelbaar rapportagepakket als ZIP (CSV + GPX + metadata)

## Inhoudsopgave

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Usage](#usage)
5. [AJAX API](#ajax-api)
6. [Project Structure](#project-structure)
7. [MVP Status en Roadmap](#mvp-status-en-roadmap)
8. [Documentatie](#documentatie)
9. [Contributing](#contributing)
10. [License](#license)

## Features

- Bootprofiel voor de Phoenix (single-boat setup)
- GPS route logging met start/stop en trackpoints
- Captains log met foto-upload
- Bewerken van bestaande logfoto-caption en sorteervolgorde in frontend
- Queue/retry-afhandeling voor log-synchronisatie op wisselende verbinding
- Uniforme frontend feedback (success/info/warning/error)
- Recente tochtsamenvattingen met directe GPX-download
- TODO-beheer met status en prioriteit
- Kostenbeheer per type, leverancier en periode
- Bulkselectie met verwijderen in admin voor Recente tochten
- Bulkselectie met verwijderen in frontend voor TODO en kosten (schrijfrechten vereist)
- Toggle tussen standaard en schermvullende live routekaart in frontend dashboard
- Rapportages met trends, vergelijkingen en exports
- Exportpakket vanuit Rapportages als ZIP met:
	- `README.txt`
	- `summary.txt`
	- `csv/trips.csv`
	- `csv/costs.csv`
	- `csv/logs.csv`
	- `csv/todos.csv`
	- `gpx/trip-<id>.gpx` (indien trackpoints beschikbaar)

## Requirements

- WordPress (met plugin- en admin-ajax ondersteuning)
- PHP 7.1 of hoger
- Ingelogde gebruiker met Phoenix-capabilities
- Mobiel/tablet met GPS voor live route logging
- Server met `ZipArchive` voor ZIP-exportpakket

## Installation

1. Plaats de pluginmap `bso-phoenix` in `wp-content/plugins/`.
2. Activeer de plugin in WordPress.
3. Controleer of rollen/capabilities zijn toegekend na activatie.
4. Open `Phoenix > Instellingen` en controleer GPS-interval, valuta en eenheden.
5. Plaats shortcode op een pagina en test route start/stop met een account dat schrijfrechten heeft.

## Usage

### Shortcode

| Item | Waarde |
|------|--------|
| Shortcode | `[phoenix_dashboard]` |
| Attributen | Geen |
| Output | Frontend dashboard met route, logboek, TODO, kosten en recente tochten |

Voorbeeld:

```text
[phoenix_dashboard]
```

### Admin submenu's en rechten

| Menu | Slug | Vereiste capability |
|------|------|---------------------|
| Phoenix (hoofdpagina) | `bso-phoenix` | `bso_phoenix_read` |
| Captain's log | `bso-phoenix-log` | `bso_phoenix_write` |
| TODO | `bso-phoenix-todo` | `bso_phoenix_write` |
| Kosten | `bso-phoenix-costs` | `bso_phoenix_write` |
| Rapportages | `bso-phoenix-reports` | `bso_phoenix_read` |
| Bootprofiel | `bso-phoenix-boat` | `bso_phoenix_manage` |
| Instellingen | `bso-phoenix-settings` | `bso_phoenix_manage` |
| Toegang | `bso-phoenix-access` | `bso_phoenix_manage` |

### Dagelijks gebruik (kort)

1. Start een tocht in het frontend dashboard.
2. Laat GPS-trackpoints opslaan tijdens varen.
3. Stop de tocht en controleer samenvatting + GPX.
4. Voeg logregels/foto's toe en pas captions/sortering aan indien nodig.
5. Beheer TODO's en kosten.
6. Exporteer in admin rapportages als CSV of ZIP-pakket.
7. Gebruik bulkacties om meerdere recente tochten, TODO-items of kostenposten in 1 keer te selecteren en verwijderen.

## AJAX API

De plugin gebruikt geen publieke WordPress REST-routes, maar geauthenticeerde admin-ajax acties.

### Endpoint

- `POST /wp-admin/admin-ajax.php`

### Authenticatie

- Ingelogde gebruiker
- Geldige nonce per domein (`bso_phoenix_gps`, `bso_phoenix_log`, `bso_phoenix_todo`, `bso_phoenix_cost`)
- Capability-check (`bso_phoenix_read`, `bso_phoenix_write` of `bso_phoenix_manage`)

### Kernacties

| Actie (`action`) | Doel | Vereiste nonce | Min. capability |
|------------------|------|----------------|-----------------|
| `bso_phoenix_start_trip` | Start tocht | `bso_phoenix_gps` | `bso_phoenix_write` |
| `bso_phoenix_trackpoint` | Sla trackpoint op | `bso_phoenix_gps` | `bso_phoenix_write` |
| `bso_phoenix_stop_trip` | Stop actieve tocht | `bso_phoenix_gps` | `bso_phoenix_write` |
| `bso_phoenix_get_trip_trackpoints` | Haal route op | `bso_phoenix_gps` | `bso_phoenix_read` |
| `bso_phoenix_get_trip_summaries` | Recente tochten | `bso_phoenix_gps` | `bso_phoenix_read` |
| `bso_phoenix_create_log` | Nieuwe logregel | `bso_phoenix_log` | `bso_phoenix_write` |
| `bso_phoenix_add_log_photos` | Foto's aan log toevoegen | `bso_phoenix_log` | `bso_phoenix_write` |
| `bso_phoenix_update_log_photo` | Caption/sortering foto aanpassen | `bso_phoenix_log` | `bso_phoenix_write` |
| `bso_phoenix_get_logs` | Logoverzicht ophalen | `bso_phoenix_log` | `bso_phoenix_read` |
| `bso_phoenix_create_todo` | TODO aanmaken | `bso_phoenix_todo` | `bso_phoenix_write` |
| `bso_phoenix_update_todo_status` | TODO status wijzigen | `bso_phoenix_todo` | `bso_phoenix_write` |
| `bso_phoenix_get_todos` | TODO lijst ophalen | `bso_phoenix_todo` | `bso_phoenix_read` |
| `bso_phoenix_delete_todos` | Geselecteerde TODO-items verwijderen | `bso_phoenix_todo` | `bso_phoenix_write` |
| `bso_phoenix_create_cost` | Kostenregel opslaan | `bso_phoenix_cost` | `bso_phoenix_write` |
| `bso_phoenix_get_costs` | Kostenlijst ophalen | `bso_phoenix_cost` | `bso_phoenix_read` |
| `bso_phoenix_delete_costs` | Geselecteerde kostenposten verwijderen | `bso_phoenix_cost` | `bso_phoenix_write` |

### Voorbeeldrespons `bso_phoenix_get_trip_summaries`

```json
{
	"success": true,
	"data": {
		"trips": [
			{
				"id": 5,
				"started_at": "2026-07-04 08:30:00",
				"ended_at": "2026-07-04 10:05:00",
				"duration_minutes": 95,
				"distance_km": 12.4,
				"average_speed_kmh": 7.8,
				"estimated_fuel_used_l": 4.6,
				"download_url": "https://example.test/wp-admin/admin-ajax.php?action=bso_phoenix_download_trip_gpx&trip_id=5&nonce=..."
			}
		]
	}
}
```

## Project Structure

```text
bso-phoenix/
├── admin/
│   ├── class-phoenix-admin-page.php
│   ├── class-phoenix-access-admin.php
│   ├── class-phoenix-boat-admin.php
│   ├── class-phoenix-cost-admin.php
│   ├── class-phoenix-log-admin.php
│   ├── class-phoenix-reports-admin.php
│   ├── class-phoenix-settings-admin.php
│   └── class-phoenix-todo-admin.php
├── assets/
│   ├── css/phoenix-frontend.css
│   └── js/phoenix-frontend.js
├── document/
│   ├── Functional_Design.md
│   ├── Technical_Design.md
│   ├── Roadmap_v1.2.0.md
│   └── Dagafsluiting_2026-07-04.md
├── includes/
│   ├── class-phoenix-access.php
│   ├── class-phoenix-ajax.php
│   ├── class-phoenix-log-ajax.php
│   ├── class-phoenix-todo-ajax.php
│   ├── class-phoenix-cost-ajax.php
│   ├── class-phoenix-trip-service.php
│   ├── class-phoenix-log-service.php
│   ├── class-phoenix-todo-service.php
│   ├── class-phoenix-cost-service.php
│   ├── class-phoenix-settings-service.php
│   └── class-phoenix-frontend.php
├── templates/frontend-dashboard.php
├── bso-phoenix.php
├── README.md
└── uninstall.php
```

## MVP Status en Roadmap

### Geimplementeerd (v1.0.0 t/m v1.2.0 scope)

- [x] Eén bootprofiel voor de Phoenix
- [x] GPS route logging met start/stop en trackpoints
- [x] Captains log met foto-upload
- [x] TODO-beheer
- [x] Kostenbeheer
- [x] Dashboard met live kaart en status
- [x] Instellingenmodule
- [x] Rapportagepagina
- [x] Story 1: offline/sync queue met retry voor log + foto-upload
- [x] Story 2: caption- en sorteerbeheer voor bestaande logfoto's
- [x] Story 3: recente tochtsamenvatting + directe GPX-download
- [x] Story 4: uniforme frontend actiefeedback en meldingen
- [x] Story 5: rapportage exportpakket als ZIP (CSV + GPX + metadata)
- [x] Story 6: bulkselectie + bulkverwijderen voor Recente tochten (admin), TODO en kosten (frontend)
- [x] Story 7: toggle tussen standaard en schermvullende live routekaart in frontend dashboard

### Planned / ready for development

- [ ] Verdere hardening van import/export validatie en foutrapportage
- [ ] Release notes voor formele v1.2.0 versie-tag

## Documentatie

- [Functional Design](document/Functional_Design.md)
- [Technical Design](document/Technical_Design.md)
- [Roadmap v1.2.0](document/Roadmap_v1.2.0.md)
- [User Story: Bulkacties Selectie en Verwijderen](document/User_Story_Bulkacties_Selectie_En_Verwijderen.md)
- [User Story: Live route schermvullend toggle](document/User_Story_Live_Route_Schermvullend_Toggle.md)
- [Testplan: Bulkacties Recente tochten TODO Kosten](document/Testplan_Bulkacties_Recente_Tochten_TODO_Kosten.md)
- [Testplan: Live route schermvullend toggle](document/Testplan_Live_Route_Schermvullend_Toggle.md)
- [Dagafsluiting 2026-07-05](document/Dagafsluiting_2026-07-05.md)
- [Dagafsluiting 2026-07-04](document/Dagafsluiting_2026-07-04.md)
- [Acceptatietest Story 5 / PR comment](document/Acceptatietest_PR_comment.md)
- [Release Notes v1.0.0](document/Release_Notes_1.0.0.md)
- [Release Notes v1.1.0](document/Release_Notes_1.1.0.md)
- [Release Notes v1.1.1](document/Release_Notes_1.1.1.md)

## Contributing

1. Werk op een feature branch met kleine, toetsbare commits.
2. Houd documentatie synchroon met codewijzigingen.
3. Test minimaal route start/stop, logboek, TODO, kosten en rapportage-export.
4. Gebruik reviewbare PR's met acceptatiechecklist en testevidence.

## License

GPL-2.0-or-later

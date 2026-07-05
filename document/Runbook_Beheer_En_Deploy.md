# Runbook - Beheer en Deploy (BSO Phoenix)

## Doel

Dit runbook beschrijft de standaard beheer- en uitrolstappen voor productie van de plugin BSO Phoenix.

## Scope

- voorbereiden van een release-zip
- deploy op WordPress productie
- functionele rooktest na deploy
- rollback in geval van incident

### Procesoverzicht

```mermaid
flowchart TD
   A[Voorbereiding release] --> B[Bouw productie artifact]
   B --> C[Deploy naar productie]
   C --> D[Rooktest na deploy]
   D --> E[Monitoring eerste 30 minuten]
   E --> F{Incident?}
   F -- Nee --> G[Release stabiel]
   F -- Ja --> H[Rollback uitvoeren]
   H --> I[Incident loggen in CHANGES]
```

## Productie Artifact

- Huidig artifact: `dist/bso-phoenix-v1.2.0-prod-20260705.zip`
- Herkomst: opgebouwd vanuit de plugin root met uitsluiting van `.git` en `dist`

### Structuur artifact en bron

```mermaid
graph TD
   A[Plugin root bso-phoenix] --> B[admin/]
   A --> C[includes/]
   A --> D[assets/]
   A --> E[templates/]
   A --> F[bso-phoenix.php]
   A --> G[uninstall.php]
   A --> H[document/]
   A --> I[README.md]
   A --> J[zip build]
   J --> K[dist/bso-phoenix-v1.2.0-prod-20260705.zip]
```

## Voorbereiding release

1. Controleer branch en werkboom:
   - `git branch --show-current`
   - `git status --short`
2. Draai minimale validatie:
   - `php -l bso-phoenix.php`
   - `php -l admin/class-phoenix-trackpoints-admin.php`
   - `php -l includes/class-phoenix-trip-service.php`
3. Bouw artifact:
   - `mkdir -p dist`
   - `zip -r "dist/bso-phoenix-vX.Y.Z-prod-YYYYMMDD.zip" . -x ".git/*" "dist/*" "*.DS_Store"`
4. Controleer artifactgrootte en naam:
   - `ls -lh dist`

## Deploy naar productie (WordPress)

1. Maak een backup van database en huidige pluginmap.
2. Zet de site kort in onderhoudsmodus.
3. Upload en installeer het zip-artifact via:
   - WordPress admin > Plugins > Nieuwe plugin > Plugin uploaden
4. Activeer of update de plugin.
5. Controleer of plugin actief is en geen PHP-fout geeft.
6. Haal onderhoudsmodus weg.

### Informatieflow tijdens deploy

```mermaid
sequenceDiagram
   participant Dev as Ontwikkelaar/Beheerder
   participant WP as WordPress Admin
   participant FS as Productie Filesystem
   participant DB as WordPress Database

   Dev->>WP: Upload plugin zip
   WP->>FS: Schrijf/overschrijf pluginbestanden
   Dev->>WP: Activeer of update plugin
   WP->>DB: Lees instellingen en plugindata
   WP-->>Dev: Admin notice / activatiestatus
   Dev->>WP: Start rooktest
   WP-->>Dev: Functionele feedback (dashboard/export/trackpoints)
```

## Rooktest na deploy

1. Open dashboard en controleer of hoofdscherm laadt.
2. Start en stop een testtocht (indien toegestaan in productie).
3. Open Rapportages en test export (CSV/GPX/ZIP).
4. Open Trackpoints beheer en controleer:
   - filters op trips en trackpoints
   - limietopties 25/50/100
   - paginering Vorige/Volgende
   - knoppen Alles selecteren / Selectie omkeren

### Rooktest structuur

```mermaid
graph LR
   A[Dashboard] --> B[Route start/stop]
   A --> C[Rapportages CSV/GPX/ZIP]
   A --> D[Trackpoints beheer]
   D --> E[Filters]
   D --> F[Limiet 25/50/100]
   D --> G[Paginering]
   D --> H[Bulk selectie]
```

## Monitoring eerste 30 minuten

- controleer WordPress debug log / PHP error log
- controleer admin notices op export- of validatiefouten
- controleer dat GPX-downloads geen 500 geven

## Rollback

1. Zet onderhoudsmodus aan.
2. Deactiveer huidige pluginversie.
3. Herstel vorige bekende werkende zip-versie.
4. Activeer plugin opnieuw.
5. Herstel eventueel database uit backup als datamodel is gewijzigd.
6. Verifieer dashboard en kernacties.

### Rollback proces

```mermaid
flowchart TD
   A[Incident vastgesteld] --> B[Onderhoudsmodus aan]
   B --> C[Huidige plugin deactiveren]
   C --> D[Vorige werkende zip terugzetten]
   D --> E[Plugin opnieuw activeren]
   E --> F{Datamodel gewijzigd?}
   F -- Ja --> G[Database backup herstellen]
   F -- Nee --> H[Rooktest kernacties]
   G --> H
   H --> I[Incident loggen]
```

## Incident Logging

Registreer elk incident in het changes document met:

- datum/tijd
- impact
- root cause (indien bekend)
- tijdelijke maatregel
- structurele fix

### Informatieflow incident logging

```mermaid
flowchart LR
   A[Monitoring alerts/logs] --> B[Incident analyse]
   B --> C[Impact en oorzaak]
   C --> D[Tijdelijke maatregel]
   D --> E[Structurele fix]
   E --> F[Registratie in CHANGES.md]
```

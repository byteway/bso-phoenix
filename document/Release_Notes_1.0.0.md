# Release Notes - v1.0.0

**Plugin:** BSO Phoenix  
**Release datum:** 3 juli 2026  
**Type:** MVP release

## Samenvatting

Deze release levert de eerste werkende MVP van de Phoenix Logboek App op WordPress.
De focus ligt op route logging met GPS, basis dashboard-inzicht en export van tochtgegevens.

## Highlights

- Plugin bootstrap en lifecycle
- Datamodel via custom tables voor trips en trackpoints
- Front-end dashboard met shortcode `[phoenix_dashboard]`
- Start/stop flow voor routes via AJAX
- GPS trackpoint-opslag vanuit browser geolocation
- Automatische basisberekeningen bij stoppen van een tocht
- Admin dashboard met kernstatistieken en recente tochten
- Export van trip-overzicht naar CSV
- Export per trip van trackpoints naar CSV en GPX
- Filters op datumrange en status (active/completed)

## Opgenomen functionaliteit

### 1. Kern plugin

- Hoofdbestand met plugin metadata en class loader
- Activatiehook voor tabelopbouw
- Uninstall-pad met optionele dataverwijdering

### 2. Route logging

- Route starten met `bso_phoenix_start_trip`
- Trackpoints opslaan met `bso_phoenix_trackpoint`
- Route stoppen met `bso_phoenix_stop_trip`
- Berekening van:
  - duur
  - afstand
  - gemiddelde snelheid

### 3. Adminoverzicht

- KPI-kaarten:
  - totaal tochten
  - actieve tochten
  - totale afstand
  - totale duur
  - gemiddelde snelheid
- Tabel met recente tochten
- Exportmogelijkheden per trip en als overzicht

## Belangrijke technische keuzes

- Custom tables in plaats van post/meta voor schaalbare GPS-opslag
- AJAX-gebaseerde route events voor start/track/stop
- Nonce-beveiliging op muterende en export-acties
- Haversine-berekening voor afstand op basis van coördinaten

## Bekende beperkingen (MVP)

- Geen offline queueing bij netwerkverlies
- Nog geen uitgebreide instellingen-UI voor alle bootparameters
- Nog geen rapportagemodule voor kosten/TODO/captain's log in admin

## Upgrade notes

- Eerste publieke MVP voor deze pluginlijn
- Geen migraties van eerdere Phoenix-versies van toepassing

## Volgende stappen (v1.1.x)

- Captain's log CRUD in admin en frontend
- TODO- en kostenmodules met filters en rapportage
- Verbeterde kaartweergave van live route
- Extra exportopties en consolidatie van rapportages

# Release Notes - v1.1.0

**Plugin:** BSO Phoenix  
**Release datum:** 3 juli 2026  
**Type:** Feature release

## Samenvatting

Release v1.1.0 bouwt voort op de MVP-route logging uit v1.0.0 en voegt de belangrijkste beheermodules toe voor dagelijks gebruik aan boord van de Phoenix.

De plugin ondersteunt nu naast GPS-routes ook:

- captain's log met foto-upload
- TODO-beheer voor onderhoud en taken
- kostenbeheer met filters en CSV-export
- bootprofielbeheer
- instellingen voor GPS, brandstof en valuta
- gecombineerde rapportage in wp-admin
- kaartweergave van live en recente routes via Leaflet

## Highlights

- Captain's log in admin en frontend
- Meerdere foto’s uploaden bij logboekitems
- TODO-module met status en prioriteit
- Kostenmodule met typefilter en CSV-export
- Bootprofielpagina voor vaste scheepsgegevens
- Instellingenpagina voor GPS-interval, brandstofverbruik en valuta
- Rapportagepagina met KPI’s voor trips, kosten, logs en open taken
- Leaflet-kaart voor live en recente routeweergave

## Nieuw in deze release

### 1. Captain's log

- Logboekitems aanmaken via admin en frontend
- Koppeling van logboekitems aan actieve trips
- Opslag van foto’s als WordPress attachments
- Thumbnail-weergave van foto’s in wp-admin

### 2. TODO beheer

- Taken aanmaken, bijwerken en verwijderen
- Statussen: open, in behandeling, afgerond
- Prioriteiten: hoog, normaal, laag
- Frontend formulier voor snelle taakregistratie

### 3. Kostenbeheer

- Kostenposten per type registreren
- Types zoals brandstof, onderhoud, ligplaats en onderdelen
- Filteren op datum en type
- CSV-export van kostenoverzicht

### 4. Bootprofiel en instellingen

- Beheer van naam, type, afmetingen, gewicht en brughoogte
- Instellen van GPS-interval
- Instellen van gemiddeld brandstofverbruik
- Instellen van valuta en afstandseenheid
- Keuze om plugin-data te verwijderen bij deïnstallatie

### 5. Rapportages en kaart

- Gecombineerd overzicht van trips, kosten, logboek en taken
- KPI’s per gekozen periode
- Samenvatting van kostensoorten
- Leaflet-kaart voor live trackpoints en laatste routes

## Technische keuzes

- Hergebruik van bestaande serviceklassen voor rapportageopbouw
- Leaflet voor kaartweergave zonder afhankelijkheid van externe proprietary kaartdiensten
- Uploads via WordPress media-API voor veilige attachment-opslag
- Modulaire admin-architectuur met aparte submenu-klassen per domein

## Bekende beperkingen

- Geen offline wachtrij voor trackpoints of uploads
- Geen inline bewerken van logfoto’s of captions
- Geen gecombineerde export van alle modules in één bestand
- Nog geen mobiele offline-first ervaring

## Upgrade notes

- v1.1.0 is achterwaarts compatibel met v1.0.0 datamodel
- Bestaande data uit routes en trackpoints blijft bruikbaar
- Nieuwe modules gebruiken reeds aanwezige tabellen uit het technische ontwerp

## Volgende stappen (v1.2.x)

- Offline buffering voor GPS-trackpoints
- Uitgebreidere dashboardgrafieken
- Mediacaptions en sortering bij logfoto’s
- Extra rapportage-exporten en printable views

# Testplan - Hardening Import/Export Validatie en Foutrapportage

## Doel
Valideren dat exportpaden van de BSO Phoenix plugin robuust omgaan met ongeldige input, falende bestandsoperaties en gebruikersfouten, met duidelijke en consistente foutrapportage.

## Scope
In scope:
- Admin export trips CSV
- Admin export trip trackpoints CSV/GPX
- Admin export kosten CSV
- Admin export rapportage CSV
- Admin export rapportagepakket ZIP
- AJAX download trip GPX

Out of scope:
- Nieuwe importmodule (nog niet aanwezig in huidige codebase)
- Algemene foto-upload hardening buiten exportcontext

## Relevante hardeningregels
- Datumvalidatie met centrale helper:
  - `BSO_Phoenix_Hardening::normalize_date()`
  - `BSO_Phoenix_Hardening::is_valid_date_range()`
- CSV-write checks op header en rijen (fout bij `fputcsv === false`)
- Gesaniteerde downloadbestandsnamen via `sanitize_file_name()`
- Uniforme admin-notices via `export_error` query-parameter
- ZIP-export met expliciete foutcodes voor open/write/close/read faalpaden
- GPX-download met coordinaatvalidatie (`lat/lon` bereik)

## Testdata
- Minimaal 1 afgeronde trip met trackpoints
- Minimaal 1 trip zonder trackpoints (of leeg resultaat afdwingen)
- Minimaal 1 kostenregel
- Minimaal 1 logboekregel en 1 TODO-item
- Testgebruiker met `bso_phoenix_read`
- Testgebruiker met `bso_phoenix_write`

## Testgevallen

### TC-HARD-001 - Trips CSV export met ongeldige datumrange
Doel: controleren dat `date_from > date_to` wordt geblokkeerd.

Stappen:
1. Open admin pagina `Phoenix`.
2. Vul filter: `Vanaf = 2026-07-10`, `Tot en met = 2026-07-01`.
3. Klik `Exporteer trips naar CSV`.

Verwacht:
- Geen download gestart.
- Redirect naar admin pagina met `export_error=invalid_range`.
- Error notice zichtbaar: einddatum ligt voor startdatum.

Resultaat: [x] PASS / [ ] FAIL
Notities: Export met ongeldige range (`2026-07-10` t/m `2026-07-01`) toont geen download en geeft correcte foutmelding `Ongeldige periode: de einddatum ligt voor de startdatum.` op de Phoenix-pagina met `export_error=invalid_range`.

### TC-HARD-002 - Kosten CSV export met ongeldige datumrange
Doel: controleren dat kostenexport dezelfde rangevalidatie afdwingt.

Stappen:
1. Open `Phoenix > Kosten`.
2. Zet `Vanaf` later dan `Tot en met`.
3. Klik `Exporteer naar CSV`.

Verwacht:
- Geen download.
- Redirect met `export_error=invalid_range`.
- Error notice zichtbaar op kostenpagina.

Resultaat: [x] PASS / [ ] FAIL
Notities: Kosten export met ongeldige range blokkeert download en toont correcte foutmelding `Ongeldige periode: de einddatum ligt voor de startdatum.` op de kostenpagina via `export_error=invalid_range`.

### TC-HARD-003 - Rapportage CSV export met ongeldige datumrange
Doel: controleren dat rapportage CSV rangevalidatie afdwingt.

Stappen:
1. Open `Phoenix > Rapportages`.
2. Zet `Vanaf` later dan `Tot en met`.
3. Klik `Exporteer rapportage naar CSV`.

Verwacht:
- Geen CSV-download.
- Redirect met `export_error=invalid_range`.
- Error notice zichtbaar op rapportagepagina.

Resultaat: [x] PASS / [ ] FAIL
Notities: Rapportage CSV export met ongeldige periode geeft geen download en toont correcte foutmelding `Ongeldige periode: de einddatum ligt voor de startdatum.` op de rapportagepagina via `export_error=invalid_range`.

### TC-HARD-004 - Rapportage ZIP export met ongeldige datumrange
Doel: controleren dat ZIP-export rangevalidatie afdwingt voor preflight.

Stappen:
1. Open `Phoenix > Rapportages`.
2. Zet `Vanaf` later dan `Tot en met`.
3. Klik `Exporteer rapportagepakket (ZIP)`.

Verwacht:
- Geen ZIP-download.
- Redirect met `export_error=invalid_range`.
- Error notice zichtbaar op rapportagepagina.

Resultaat: [x] PASS / [ ] FAIL
Notities: Rapportage ZIP export met ongeldige periode blokkeert download en toont correcte foutmelding `Ongeldige periode: de einddatum ligt voor de startdatum.` op de rapportagepagina via `export_error=invalid_range`.

### TC-HARD-005 - Trip trackpoint export met ongeldige trip-id
Doel: valideren dat trackpoint-export geen fatale fout toont maar nette melding.

Stappen:
1. Open een URL naar `admin-post.php?action=bso_phoenix_export_trip_trackpoints&format=csv&trip_id=0` met geldige nonce-variant.
2. Herhaal voor `format=gpx`.

Verwacht:
- Redirect naar `Phoenix` met `export_error=invalid_trip`.
- Error notice zichtbaar.

Resultaat: [x] PASS / [ ] FAIL
Notities: Trackpoint export met ongeldige trip-id resulteert in redirect naar Phoenix met `export_error=invalid_trip` en zichtbare notice `Export mislukt: ongeldige trip-id.`.

### TC-HARD-006 - Trip trackpoint export met ongeldig formaat
Doel: alleen `csv` en `gpx` toestaan.

Stappen:
1. Open `admin-post.php?action=bso_phoenix_export_trip_trackpoints&format=xml&trip_id=<geldige_id>` met geldige nonce.

Verwacht:
- Redirect met `export_error=invalid_format`.
- Error notice zichtbaar.

Resultaat: [x] PASS / [ ] FAIL
Notities: Export met ongeldig formaat (`format=xml`) resulteert in redirect naar Phoenix met `export_error=invalid_format` en zichtbare notice `Export mislukt: ongeldig exportformaat.`.

### TC-HARD-007 - Trip trackpoint export voor niet-bestaande trip
Doel: nette afhandeling van ontbrekende trip.

Stappen:
1. Trigger export voor hoog niet-bestaand `trip_id` met geldige nonce.

Verwacht:
- Redirect met `export_error=trip_not_found`.
- Error notice zichtbaar.

Resultaat: [x] PASS / [ ] FAIL
Notities: Export met niet-bestaande/onbruikbare trip-id geeft redirect naar Phoenix met `export_error=invalid_trip` en de notice `Export mislukt: ongeldige trip-id.`.

### TC-HARD-008 - Trip trackpoint export zonder trackpoints
Doel: nette afhandeling als trip bestaat maar geen punten heeft.

Stappen:
1. Kies trip zonder trackpoints.
2. Trigger export CSV en GPX.

Verwacht:
- Redirect met `export_error=empty_trackpoints`.
- Error notice zichtbaar.

Resultaat: [x] PASS / [ ] FAIL
Notities: Export voor trip zonder trackpoints geeft redirect met `export_error=empty_trackpoints` en zichtbare notice `Export mislukt: geen trackpoints beschikbaar voor deze trip.`.

### TC-HARD-009 - AJAX GPX download met ongeldige coordinaatdata
Doel: voorkomen dat corrupte route als valide GPX wordt gedownload.

Stappen:
1. Zorg dat trip trackpoints alleen ongeldige coordinaten bevat (bijv. lat > 90).
2. Trigger `wp_ajax_bso_phoenix_download_trip_gpx` met geldige nonce en rechten.

Verwacht:
- HTTP 422.
- Foutmelding: geen geldige GPS-trackpoints beschikbaar.
- Geen GPX-bestand output.

Resultaat: [x] PASS / [ ] FAIL
Notities: Gevalideerd met alleen ongeldige coordinaten (lat/lon buiten bereik): GPX-download wordt geblokkeerd en de gebruiker krijgt een foutmelding met `export_error=empty_trackpoints` (geen bestand geleverd). Dit dekt het doel dat corrupte routegegevens niet als geldige GPX worden uitgeleverd.

### TC-HARD-010 - Rapportage ZIP positieve smoke test
Doel: regressiecheck dat succesvolle ZIP-export blijft werken.

Stappen:
1. Open `Phoenix > Rapportages`.
2. Stel geldige periode in.
3. Klik `Exporteer rapportagepakket (ZIP)`.
4. Open ZIP lokaal.

Verwacht:
- Download start.
- ZIP bevat minimaal:
  - `README.txt`
  - `summary.txt`
  - `csv/trips.csv`
  - `csv/costs.csv`
  - `csv/logs.csv`
  - `csv/todos.csv`
- Optioneel GPX-bestanden onder `gpx/` voor trips met trackpoints.

Resultaat: [x] PASS / [ ] FAIL
Notities: ZIP-export start succesvol en bevat de verwachte basisbestanden. Na hardening worden GPX-bestanden met alleen ongeldige coordinaten niet meer opgenomen (trip 8 met foutieve lat/lon ontbreekt correct in `gpx/`).

## Niet-functionele checks
- Geen PHP parse errors in aangepaste bestanden.
- Geen nieuwe IDE/diagnostic errors in export-gerelateerde classes.
- Foutmeldingen blijven in het Nederlands en zijn actiegericht.

## Testrun samenvatting
- Datum:
- Tester:
- Omgeving:
- Build/commit:

Totaal testgevallen: 10
PASS:
FAIL:
BLOCKED:

Eindadvies: [ ] GO / [ ] NO-GO

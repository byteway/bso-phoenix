# Handmatige acceptatiecheck Story 5 (admin rapportagepakket ZIP-export)

## Scope

- Admin kan vanuit Rapportages een ZIP-exportpakket downloaden.
- ZIP bevat leesbare metadata en datasets als CSV.
- ZIP bevat per tocht een GPX-bestand wanneer trackpoints aanwezig zijn.
- Export respecteert ingestelde periodefilters (vanaf/tot en met).
- Exportactie is afgeschermd met capability-check en nonce.

## Branch en commit

- Branch: `feature/v1.2.0-story-5-zip-export-package`
- Commit: `516c78e` - Implement Story 5 admin ZIP export package (CSV + GPX + metadata)

## Testomgeving

- WordPress admin, pagina `Phoenix > Rapportages`
- Desktop browser (Chrome/Edge)
- ZIP-tool (OS ingebouwd of 7-Zip)
- Spreadsheet viewer (LibreOffice/Excel) voor CSV-validatie
- Optioneel: gpx.studio voor GPX-validatie

## Checklist

1. ZIP-exportknop zichtbaar in Rapportages
- Actie: open admin Rapportages-pagina.
- Expected: knop `Exporteer rapportagepakket (ZIP)` is zichtbaar en klikbaar.
- Status: TO TEST

2. ZIP-download start succesvol
- Actie: klik op `Exporteer rapportagepakket (ZIP)`.
- Expected: browser downloadt een bestand `phoenix-exportpakket-YYYYMMDD-HHMMSS.zip`.
- Status: TO TEST

3. ZIP-inhoud compleet
- Actie: pak ZIP uit en controleer structuur.
- Expected: bestanden aanwezig:
	- `README.txt`
	- `summary.txt`
	- `csv/trips.csv`
	- `csv/costs.csv`
	- `csv/logs.csv`
	- `csv/todos.csv`
	- `gpx/trip-<id>.gpx` (voor trips met trackpoints)
- Status: TO TEST

4. CSV-bestanden zijn leesbaar en bevatten headers
- Actie: open alle CSV-bestanden in spreadsheet viewer.
- Expected: geen lege/corrupte files; headers en dataregels zijn correct parsebaar.
- Status: TO TEST

5. GPX-bestanden valide (indien aanwezig)
- Actie: open minimaal 1 GPX in gpx.studio.
- Expected: route wordt geladen en is valide GPX 1.1.
- Status: TO TEST

6. Periodefilter wordt toegepast
- Actie: kies afgebakende periode en exporteer opnieuw.
- Expected: `summary.txt` en CSV-inhoud weerspiegelen de gekozen periode.
- Status: TO TEST

7. Security check op exportactie
- Actie: probeer export zonder geldige nonce of zonder voldoende rechten.
- Expected: actie wordt geblokkeerd met foutmelding (geen ZIP-output).
- Status: TO TEST

## PR Testevidence (plakbaar)

Story 5 handmatige acceptatiecheck uitgevoerd volgens `document/Acceptatietest_PR_comment.md`.

Resultaten:

- ZIP-exportknop zichtbaar in Rapportages: [PASS/FAIL]
- ZIP-download start succesvol: [PASS/FAIL]
- ZIP-inhoud compleet (README, summary, csv/*, gpx/*): [PASS/FAIL]
- CSV-bestanden leesbaar en correct: [PASS/FAIL]
- GPX valide in gpx.studio (indien aanwezig): [PASS/FAIL]
- Periodefilter correct toegepast in exportdata: [PASS/FAIL]
- Security check (nonce/capability) blokkeert ongeldige calls: [PASS/FAIL]

Definitieve conclusie:

Story 5 heeft status [GO/NO GO].
Opmerkingen/bevindingen: [korte samenvatting].

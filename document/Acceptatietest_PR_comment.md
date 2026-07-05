# Handmatige acceptatiecheck Hardening Import/Export Validatie en Foutrapportage

## Scope

- Validatie van ongeldige datumranges in trips, kosten en rapportage exports
- Validatie van exportformaten en trip-identificatie bij trackpoint-export
- Blokkeren van GPX-export bij ontbrekende of ongeldige trackpoints
- Correct gedrag van rapportage ZIP-export met filtering van ongeldige GPX-data

## Testomgeving

- Productieomgeving: byteway.eu wp-admin
- Desktop browser (Chrome/Edge)
- Handmatige data-manipulatie via phpMyAdmin voor negatieve tests

## Resultaten

- TC-HARD-001: PASS
- TC-HARD-002: PASS
- TC-HARD-003: PASS
- TC-HARD-004: PASS
- TC-HARD-005: PASS
- TC-HARD-006: PASS
- TC-HARD-007: PASS
- TC-HARD-008: PASS
- TC-HARD-009: PASS
- TC-HARD-010: PASS

## PR Testevidence (plakbaar)

Hardening import/export validatie en foutrapportage handmatig geaccepteerd.

Samenvatting:

- Totaal testcases: 10
- PASS: 10
- FAIL: 0
- BLOCKED: 0
- Eindadvies: GO

Belangrijk gevalideerd gedrag:

- Ongeldige datumranges geven consistente `invalid_range` foutafhandeling zonder lege pagina.
- Ongeldige trackpoint-exportparameters geven duidelijke `invalid_trip`/`invalid_format` notices.
- Trips zonder geldige trackpoints leveren geen GPX meer op (losse export en ZIP).
- Rapportage ZIP blijft functioneel en bevat alleen geldige GPX-bestanden.

Referentie:

- document/Testplan_Hardening_Import_Export_Validatie_En_Foutrapportage.md

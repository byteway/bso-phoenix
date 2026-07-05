# Testplan - Trackpoints Bewerken en Repareren

## Doel

Valideren dat de nieuwe admin-UI voor trackpoints correct werkt voor bekijken, bewerken, verwijderen, opschonen en herberekenen.

## Testomgeving

- WordPress admin met plugin `bso-phoenix`
- Gebruiker met `bso_phoenix_manage`
- Minimaal 1 trip met trackpoints
- Minimaal 1 trip met bewust foutieve trackpoints voor repair-test

## Testcases

### TC-TP-001 - Trackpoints scherm openen

Stappen:
1. Open `Phoenix > Trackpoints`.
2. Kies een trip.

Verwacht:
- Trackpoints worden getoond.
- Tripinformatie is zichtbaar.
- Alleen beheerrechten hebben toegang.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-002 - Trackpoint bewerken

Stappen:
1. Wijzig latitude, longitude of recorded_at van een bestaand trackpoint.
2. Sla op.

Verwacht:
- Wijziging wordt opgeslagen.
- Succesmelding zichtbaar.
- Trip wordt herberekend als de trip afgerond is.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-003 - Trackpoint verwijderen

Stappen:
1. Selecteer één of meer trackpoints.
2. Klik `Verwijder geselecteerde punten`.

Verwacht:
- Geselecteerde punten worden verwijderd.
- Succesmelding zichtbaar.
- Trip wordt herberekend als de trip afgerond is.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-004 - Ongeldige trackpoints opschonen

Stappen:
1. Open trip met ongeldige coordinaten.
2. Klik `Verwijder ongeldige punten`.

Verwacht:
- Onjuiste punten worden verwijderd.
- Succesmelding zichtbaar.
- Geen ongeldige coordinaten blijven over.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-005 - Herberekenen trip

Stappen:
1. Klik `Herbereken trip`.

Verwacht:
- Tripmetrics worden opnieuw berekend.
- Succesmelding zichtbaar.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-006 - Rechtevalidatie

Stappen:
1. Open de pagina met een read-only gebruiker.
2. Roep de admin-post actie zonder manage-capability aan.

Verwacht:
- Pagina is niet toegankelijk.
- Handeling wordt geweigerd.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

## Testrun samenvatting

- Datum:
- Tester:
- Omgeving:
- Build/commit:

PASS:
FAIL:
BLOCKED:

Eindadvies: [ ] GO / [ ] NO-GO

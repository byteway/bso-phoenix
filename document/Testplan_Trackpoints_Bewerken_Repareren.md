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

### TC-TP-007 - Filter op trips (combobox)

Stappen:
1. Stel `Tochten vanaf`, `Tochten t/m` en `Max tochten` in.
2. Klik `Filter toepassen`.

Verwacht:
- De trip-combobox bevat minder items volgens de ingestelde filters.
- Alleen trips binnen de gekozen datumrange worden getoond.
- Het aantal opties overschrijdt de ingestelde limiet niet.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-008 - Filter op trackpoints en laadlimiet

Stappen:
1. Selecteer een trip met veel trackpoints.
2. Stel `Trackpoints vanaf`, `Trackpoints t/m` en `Max trackpoints` in.
3. Klik `Filter toepassen`.

Verwacht:
- Alleen trackpoints binnen de ingestelde periode worden geladen.
- Het aantal geladen rijen volgt de ingestelde limiet.
- Bij bereiken van de limiet verschijnt een informatieve melding.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-009 - Alles selecteren en selectie omkeren

Stappen:
1. Open een trip met meerdere trackpoints.
2. Klik `Alles selecteren`.
3. Klik daarna `Selectie omkeren`.

Verwacht:
- Na `Alles selecteren` zijn alle zichtbare checkboxen aangevinkt.
- Na `Selectie omkeren` wordt de selectie van alle zichtbare rijen omgedraaid.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-010 - Limietopties 25, 50, 100

Stappen:
1. Open filter `Max trackpoints`.
2. Controleer de beschikbare opties.
3. Kies elke optie één voor één en pas filter toe.

Verwacht:
- Alleen 25, 50 en 100 zijn beschikbaar.
- Het aantal zichtbare rijen per pagina volgt de gekozen limiet.

Resultaat: [ ] PASS / [ ] FAIL
Notities:

### TC-TP-011 - Trackpoints paginering

Stappen:
1. Kies een trip waarvan het aantal gefilterde trackpoints groter is dan de gekozen limiet.
2. Navigeer met `Volgende` en `Vorige`.

Verwacht:
- De pagina-indicator toont de juiste paginanummers.
- `Volgende` gaat naar de volgende set rijen en `Vorige` terug.
- Filters en limiet blijven behouden tijdens pagineren.

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

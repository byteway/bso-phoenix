# Testplan - Bulkacties Recente tochten TODO Kosten

## Doel

Valideren dat bulkselectie en bulkverwijdering correct werkt in:

- Admin: Recente tochten
- Frontend: TODO
- Frontend: Kosten

met correcte afhandeling van rechten, nonce en gebruikersfeedback.

## Testomgeving

- WordPress met plugin `bso-phoenix`
- Minimaal 2 testaccounts:
  - Account A met `bso_phoenix_write`
  - Account B met alleen `bso_phoenix_read`
- Data aanwezig:
  - Minimaal 3 afgeronde trips
  - Minimaal 3 TODO-items
  - Minimaal 3 kostenposten

## Preconditions

1. Plugin actief.
2. Pagina met shortcode `[phoenix_dashboard]` bereikbaar.
3. Adminmenu `Phoenix` zichtbaar voor testaccounts.

## Testcases

### A. Admin - Recente tochten bulkselectie

1. Open `Phoenix` adminpagina.
- Expected: tabel `Recente tochten` zichtbaar met checkbox per rij.

2. Klik `Selecteer alles`.
- Expected: alle zichtbare checkboxen aangevinkt.

3. Klik `Deselecteer alles`.
- Expected: alle checkboxen uitgevinkt.

4. Vink 1e en 3e rij aan, klik `Selectie omkeren`.
- Expected: eerder geselecteerde rijen worden uitgevinkt, overige aangevinkt.

5. Klik `Verwijder geselecteerde tochten` zonder selectie.
- Expected: actie geblokkeerd met melding dat selectie verplicht is.

### B. Admin - Recente tochten bulkverwijdering

1. Selecteer 2 afgeronde trips.
2. Klik `Verwijder geselecteerde tochten` en bevestig.
- Expected: succesmelding met verwijderd aantal.
- Expected: verwijderde trips niet meer zichtbaar.

3. Controleer gekoppelde data van verwijderde trip.
- Expected: trackpoints verwijderd.
- Expected: logs/kosten blijven bestaan maar zijn niet meer gekoppeld aan verwijderde trip.

### C. Frontend - TODO bulkselectie en verwijderen

1. Open dashboardpagina als Account A (write).
- Expected: TODO lijst met checkboxes zichtbaar.

2. Test `Alles`, `Geen`, `Omkeren`.
- Expected: selectiegedrag correct.

3. Selecteer 2 TODO-items en klik `Verwijder selectie`.
- Expected: bevestigingsprompt.
- Expected: na bevestigen succesfeedback met aantallen.
- Expected: verwijderde TODO-items verdwijnen uit lijst.

### D. Frontend - Kosten bulkselectie en verwijderen

1. Open dashboardpagina als Account A (write).
- Expected: kostenlijst met checkboxes zichtbaar.

2. Test `Alles`, `Geen`, `Omkeren`.
- Expected: selectiegedrag correct.

3. Selecteer 2 kostenposten en klik `Verwijder selectie`.
- Expected: bevestigingsprompt.
- Expected: na bevestigen succesfeedback met aantallen.
- Expected: verwijderde posten verdwijnen uit lijst.

### E. Rechtencontrole

1. Open admin en frontend als Account B (read-only).
- Expected: lijsten en selecties zichtbaar.

2. Probeer bulk verwijderen in frontend TODO/kosten.
- Expected: verwijdering geblokkeerd met rights-melding.

3. Roep delete-endpoints handmatig aan zonder write-capability.
- Expected: HTTP 403 / foutmelding `Onvoldoende rechten`.

### F. Nonce-validatie

1. Verstuur delete-request met ongeldige nonce.
- Expected: request afgewezen met HTTP 403 / `Ongeldige nonce`.

## Resultaatregistratie

| Testcase | Status (PASS/FAIL) | Opmerking |
|----------|---------------------|-----------|
| A. Admin bulkselectie |  |  |
| B. Admin bulkverwijdering |  |  |
| C. Frontend TODO bulk |  |  |
| D. Frontend Kosten bulk |  |  |
| E. Rechtencontrole |  |  |
| F. Nonce-validatie |  |  |

## Eindoordeel

- GO: alle testcases PASS.
- NO GO: minimaal 1 kritieke testcase FAIL (rechten, nonce, of foutieve verwijdering).

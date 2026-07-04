# Handmatige acceptatiecheck Story 4 (frontend feedback component)

## Scope

- Uniforme feedbacktypes: `success`, `info`, `warning`, `error`
- Banner- en toastgedrag
- Queueing van meldingen
- Koppeling met log, TODO, kosten, route en offline wachtrij

## Testomgeving

- Desktop browser (Chrome/Edge)
- Tablet (Samsung Tab S5 of vergelijkbaar)
- Test met online en offline netwerkstatus

## Checks

1. Route starten (online)
- Actie: klik op `Start route`.
- Expected: succesmelding zichtbaar als banner en toast.
- Expected: status verandert naar `Actief`.

2. Route stoppen
- Actie: klik op `Stop route`.
- Expected: succesmelding zichtbaar als banner en toast.
- Expected: status verandert naar `Gestopt`.

3. Captain's log validatie
- Actie: laat notitie leeg en klik op `Opslaan`.
- Expected: warningmelding (geen successmelding).
- Actie: vul notitie in en sla op.
- Expected: successmelding voor opgeslagen notitie.

4. TODO validatie en succes
- Actie: laat titel leeg en verstuur.
- Expected: warningmelding.
- Actie: vul titel in en verstuur.
- Expected: successmelding `Taak toegevoegd`.

5. Kosten validatie en succes
- Actie: vul ongeldig bedrag of lege datum in.
- Expected: warningmelding.
- Actie: vul geldig bedrag + datum in en verstuur.
- Expected: successmelding `Kostenpost opgeslagen`.

6. Offline gedrag (queue)
- Actie: zet device offline.
- Actie: verstuur log/TODO/kosten.
- Expected: warningmelding dat actie in wachtrij is geplaatst.
- Expected: sync-banner toont offline status.

7. Terug online + synchronisatie
- Actie: zet device weer online.
- Expected: infomelding dat synchronisatie start.
- Actie: klik op `Probeer alles opnieuw`.
- Expected: succes/foutmeldingen per queue-actie; queue wordt bijgewerkt.

8. Queue remove en retry single
- Actie: verwijder 1 wachtrij-item.
- Expected: success toast `Actie verwijderd uit wachtrij`.
- Actie: probeer 1 specifiek item opnieuw.
- Expected: success- of error-toast met correcte status.

9. Toast queue limiet
- Actie: trigger snel meerdere acties achter elkaar.
- Expected: maximaal beperkte set tegelijk zichtbaar.
- Expected: oudere toasts verdwijnen automatisch; nieuwe volgen in queue-volgorde.

10. Responsive controle
- Actie: herhaal kernflows op tablet.
- Expected: feedbackblokken blijven leesbaar en overlappen primaire acties niet.

## Acceptatiecriteria (GO)

- Alle kernacties tonen consistente typefeedback.
- Geen regressie in bestaande submit-flows.
- Offline/online feedback blijft correct en duidelijk.
- Desktop + tablet gedrag is functioneel en bruikbaar.
# User Story - Bulkacties Selectie en Verwijderen

## Story

Als beheerder wil ik in admin bij Recente tochten meerdere regels kunnen selecteren via checkboxes,
zodat ik sneller bulkverwijdering kan uitvoeren.

Als geautoriseerde frontend gebruiker wil ik dezelfde selectiefunctionaliteit voor TODO en kosten,
zodat foutief ingevoerde items direct gecorrigeerd kunnen worden.

## Probleem

Zonder bulkacties is verwijderen traag en foutgevoelig omdat items 1 voor 1 verwerkt moeten worden.

## Doel

- Sneller beheer van grotere lijsten.
- Minder klikwerk bij correcties.
- Consistente selectiebediening in admin en frontend.
- Veilige verwijdering met rol- en noncecontrole.

## In Scope

- Admin `Recente tochten`:
  - checkbox per rij
  - Selecteer alles
  - Deselecteer alles
  - Selectie omkeren
  - Verwijder geselecteerde tochten
- Frontend `TODO`:
  - lijst met checkboxes
  - Selecteer alles / Deselecteer alles / Selectie omkeren
  - Verwijder selectie
- Frontend `Kosten`:
  - lijst met checkboxes
  - Selecteer alles / Deselecteer alles / Selectie omkeren
  - Verwijder selectie

## Out of Scope

- Undo/herstel na verwijderen.
- Geavanceerde filterbuilder (multi-filter combinaties).
- Verwijderen van logboekitems in frontend.

## Rollen en rechten

- `bso_phoenix_read`:
  - mag lijsten zien
  - mag selectie gebruiken
  - mag niet verwijderen
- `bso_phoenix_write`:
  - mag bulk verwijderen uitvoeren
- `bso_phoenix_manage`:
  - heeft alle write-mogelijkheden

## Functionele eisen

1. Selectieknoppen werken op de huidige zichtbare lijst.
2. Verwijderen zonder selectie wordt geblokkeerd met melding.
3. Verwijderen vraagt expliciete bevestiging.
4. Na verwijderen wordt de lijst direct ververst.
5. Feedback toont aantal verwijderde en eventueel mislukte items.

## Technische eisen

1. Alle delete-acties hebben nonce-validatie.
2. Alle delete-acties hebben capability-check.
3. Bulk-ID-input wordt gesanitized en gevalideerd.
4. Responses bevatten `deleted_count` en `failed_count`.

## Acceptatiecriteria

1. Admin bulkselectie werkt voor Recente tochten.
2. Frontend bulkselectie werkt voor TODO.
3. Frontend bulkselectie werkt voor kosten.
4. Selectie omkeren werkt correct in alle 3 contexten.
5. Zonder `bso_phoenix_write` wordt verwijderen geblokkeerd.
6. Met `bso_phoenix_write` wordt alleen de geselecteerde set verwijderd.

## Definition of Done

1. Code geïmplementeerd in admin, frontend en AJAX handlers.
2. Functioneel en technisch ontwerp bijgewerkt.
3. README bijgewerkt met nieuwe endpoints en featurestatus.
4. Testplan beschikbaar en uitvoerbaar.

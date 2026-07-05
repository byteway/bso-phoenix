# User Story - Trackpoints Bewerken en Repareren

## Verhaal

Als beheerder van de Phoenix wil ik bestaande trackpoints per tocht kunnen bekijken, aanpassen, verwijderen en opschonen, zodat foutieve GPS-data snel hersteld kan worden en exports weer betrouwbaar zijn.

## Doel

Een dedicated admin-UI bieden voor:

- trackpoints per trip bekijken
- trackpoints inline bewerken
- geselecteerde trackpoints verwijderen
- ongeldige trackpoints in één keer opschonen
- de trip herberekenen op basis van de resterende trackpoints

## Functionaliteit

- nieuw submenu onder Phoenix in wp-admin: `Trackpoints`
- tripselectie via dropdown
- trackpointtabel met bewerkbare velden:
  - recorded_at
  - latitude
  - longitude
  - altitude_m
  - speed_kmh
  - accuracy_m
- bulk verwijderen van geselecteerde punten
- repair-actie voor ongeldige trackpoints
- herberekening van tripmetrics na wijzigingen

## Acceptatiecriteria

1. Ik kan een trip kiezen en de trackpoints van die trip zien.
2. Ik kan bestaande trackpoints inline aanpassen en opslaan.
3. Ongeldige coordinaten worden als zodanig zichtbaar gemaakt.
4. Ik kan geselecteerde trackpoints verwijderen.
5. Ik kan alle ongeldige trackpoints van een trip in één actie laten verwijderen.
6. Na wijzigen of verwijderen worden triptotalen opnieuw berekend.
7. De pagina is alleen toegankelijk voor gebruikers met beheerrechten.
8. Ongeldige invoer geeft duidelijke foutmeldingen en wordt niet opgeslagen.

## Niet-functionele eisen

- wijzigingen zijn traceerbaar per trip
- fouten blijven beperkt tot de gekozen trip
- bestaande exports blijven werken nadat trackpoints zijn aangepast

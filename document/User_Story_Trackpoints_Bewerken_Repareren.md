# User Story - Trackpoints Bewerken en Repareren

## Verhaal

Als beheerder van de Phoenix wil ik bestaande trackpoints per tocht kunnen bekijken, aanpassen, verwijderen en opschonen, zodat foutieve GPS-data snel hersteld kan worden en exports weer betrouwbaar zijn.

Als beheerder wil ik in Trackpoints beheer kunnen filteren op periode en laadlimiet, zodat de trip-combobox en trackpointtabel werkbaar blijven bij grote datasets.

## Doel

Een dedicated admin-UI bieden voor:

- trackpoints per trip bekijken
- trips filteren op datumrange en max aantal resultaten
- trackpoints inline bewerken
- trackpoints filteren op datum/tijd range
- trackpoints laden met instelbare limiet (25, 50, 100)
- alles selecteren voor bulkverwijdering
- selectie omkeren voor bulkverwijdering
- trackpoints pagineren over meerdere pagina's
- geselecteerde trackpoints verwijderen
- ongeldige trackpoints in één keer opschonen
- de trip herberekenen op basis van de resterende trackpoints

## Functionaliteit

- nieuw submenu onder Phoenix in wp-admin: `Trackpoints`
- tripselectie via dropdown met filter op:
  - tochten vanaf datum
  - tochten t/m datum
  - maximaal aantal trips
- trackpointtabel met bewerkbare velden:
  - recorded_at
  - latitude
  - longitude
  - altitude_m
  - speed_kmh
  - accuracy_m
- trackpointfilter op:
  - trackpoints vanaf datum/tijd
  - trackpoints t/m datum/tijd
  - maximaal aantal te laden trackpoints per pagina: 25, 50 of 100
- selectietools in de trackpointtabel:
  - knop `Alles selecteren`
  - knop `Selectie omkeren`
- paginering met navigatieknoppen `Vorige` en `Volgende`
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
9. Ik kan de trip-combobox beperken met datumfilters en een max aantal trips.
10. Ik kan het aantal geladen trackpoints beperken met een instelbare limiet.
11. Bij bereiken van de laadlimiet krijg ik een duidelijke melding om filters te verfijnen.
12. Na opslaan/verwijderen/herstel blijft de gekozen filtercontext behouden.
13. Ik kan met één knop alle zichtbare trackpoints selecteren.
14. Ik kan met één knop de huidige selectie omkeren.
15. De trackpointlimiet is beperkt tot 25, 50 of 100 per pagina.
16. Ik kan door gefilterde trackpoints bladeren via paginering.

## Niet-functionele eisen

- wijzigingen zijn traceerbaar per trip
- fouten blijven beperkt tot de gekozen trip
- bestaande exports blijven werken nadat trackpoints zijn aangepast
- de beheerpagina blijft bruikbaar bij grote aantallen trips en trackpoints

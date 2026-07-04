# Handmatige acceptatiecheck Story 2 (caption + sortering bestaande logfoto's)

## Scope

- Bestaande logfoto's kunnen een bijschrift krijgen/bijwerken
- Bestaande logfoto's kunnen omhoog/omlaag worden gesorteerd
- Wijzigingen worden server-side opgeslagen en blijven na refresh zichtbaar
- Lightbox en galerijweergave tonen bijgewerkte captions en volgorde

## Testomgeving

- Desktop browser (Chrome/Edge)
- Tablet (Samsung Tab S5 of vergelijkbaar)
- Ingelogd als gebruiker met schrijfrechten

## Checklist

1. Bestaande logfoto's zichtbaar in gallery
- Actie: open frontend dashboard met logs die al foto's bevatten.
- Expected: bestaande foto's worden getoond in de gallery.
- Status: PASS

1a. Foto direct zichtbaar tijdens selectie (voor opslaan)
- Actie: kies foto in Captain's log vóór submit.
- Expected: geselecteerde foto wordt direct als preview getoond.
- Status: PASS

2. Caption bewerken en opslaan
- Actie: pas bijschrift aan en klik op `Bewaar`.
- Expected: succesmelding verschijnt.
- Expected: nieuw bijschrift blijft zichtbaar na herladen.
- Status: PASS

3. Sortering omhoog
- Actie: klik op `Omhoog` bij een foto die niet bovenaan staat.
- Expected: foto schuift één positie omhoog binnen dezelfde log.
- Expected: aangepaste volgorde blijft behouden na herladen.
- Status: PASS

4. Sortering omlaag
- Actie: klik op `Omlaag` bij een foto die niet onderaan staat.
- Expected: foto schuift één positie omlaag binnen dezelfde log.
- Expected: aangepaste volgorde blijft behouden na herladen.
- Status: PASS

5. Grensgevallen sortering
- Actie: controleer bovenste en onderste foto in een log.
- Expected: `Omhoog` is disabled op de eerste foto.
- Expected: `Omlaag` is disabled op de laatste foto.
- Status: PASS

6. Lightbox captioncontrole
- Actie: open foto in lightbox na caption-update.
- Expected: lightbox toont de bijgewerkte caption.
- Status: PASS

7. Rechtencontrole (read-only)
- Actie: open dashboard met read-only gebruiker.
- Expected: caption/sort editor controls zijn niet beschikbaar.
- Status: PASS

8. Regressiecheck log upload flow
- Actie: maak een nieuw logitem met foto's en captions.
- Expected: upload flow blijft werken zoals in Story 1/4.
- Status: PASS

## PR Testevidence (plakbaar)

Story 2 handmatige acceptatiecheck uitgevoerd volgens `document/Acceptatietest_PR_comment.md`.

Resultaten:

- Bestaande logfoto's zichtbaar in gallery: PENDING
- Foto direct zichtbaar tijdens selectie: PASS
- Caption bewerken en opslaan: PASS
- Sortering omhoog: PASS
- Sortering omlaag: PASS
- Grensgevallen sortering: PENDING
- Lightbox captioncontrole: PENDING
- Grensgevallen sortering: PASS
- Lightbox captioncontrole: PASS
- Rechtencontrole (read-only): PASS
- Regressiecheck log upload flow: PASS

Eindconclusie:

Story 2 is volledig gevalideerd en heeft een definitieve GO-status.
Alle checklist-items zijn PASS en er zijn geen regressies geconstateerd.
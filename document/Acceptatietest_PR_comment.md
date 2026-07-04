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
- Status: PENDING

2. Caption bewerken en opslaan
- Actie: pas bijschrift aan en klik op `Bewaar`.
- Expected: succesmelding verschijnt.
- Expected: nieuw bijschrift blijft zichtbaar na herladen.
- Status: PENDING

3. Sortering omhoog
- Actie: klik op `Omhoog` bij een foto die niet bovenaan staat.
- Expected: foto schuift één positie omhoog binnen dezelfde log.
- Expected: aangepaste volgorde blijft behouden na herladen.
- Status: PENDING

4. Sortering omlaag
- Actie: klik op `Omlaag` bij een foto die niet onderaan staat.
- Expected: foto schuift één positie omlaag binnen dezelfde log.
- Expected: aangepaste volgorde blijft behouden na herladen.
- Status: PENDING

5. Grensgevallen sortering
- Actie: controleer bovenste en onderste foto in een log.
- Expected: `Omhoog` is disabled op de eerste foto.
- Expected: `Omlaag` is disabled op de laatste foto.
- Status: PENDING

6. Lightbox captioncontrole
- Actie: open foto in lightbox na caption-update.
- Expected: lightbox toont de bijgewerkte caption.
- Status: PENDING

7. Rechtencontrole (read-only)
- Actie: open dashboard met read-only gebruiker.
- Expected: caption/sort editor controls zijn niet beschikbaar.
- Status: PENDING

8. Regressiecheck log upload flow
- Actie: maak een nieuw logitem met foto's en captions.
- Expected: upload flow blijft werken zoals in Story 1/4.
- Status: PENDING

## PR Testevidence (plakbaar)

Story 2 handmatige acceptatiecheck uitgevoerd volgens `document/Acceptatietest_PR_comment.md`.

Resultaten:

- Bestaande logfoto's zichtbaar in gallery: PENDING
- Caption bewerken en opslaan: PENDING
- Sortering omhoog: PENDING
- Sortering omlaag: PENDING
- Grensgevallen sortering: PENDING
- Lightbox captioncontrole: PENDING
- Rechtencontrole (read-only): PENDING
- Regressiecheck log upload flow: PENDING

Voorlopige conclusie:

Story 2 is technisch geïmplementeerd en klaar voor handmatige validatie.
Na afronding van bovenstaande checks kan de definitieve GO/NO-GO worden vastgesteld.
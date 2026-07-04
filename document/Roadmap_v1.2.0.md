# Roadmap - v1.2.0

**Plugin:** BSO Phoenix  
**Roadmap status:** Voorbereid voor uitvoering  
**Doelrelease:** v1.2.0

## Scope

Deze roadmap bevat 5 concrete, gebruikersgerichte verbeteringen voor de volgende feature release.  
Focus: betrouwbaarheid op mobiel, sneller werken in het logboek en duidelijkere operationele inzichten.

## 1) Offline Captains Log Queue (incl. foto-upload retry)

**Gebruikerswaarde**
- Als er geen verbinding is, kunnen notities en foto's alsnog veilig worden vastgelegd en later automatisch worden gesynchroniseerd.

**v1.2.0 oplevering**
- Frontend queue-item met duidelijke status (`in wachtrij`, `gesynchroniseerd`, `mislukt`).
- Handmatige retry per queue-item en `Probeer alles opnieuw` blijft beschikbaar.
- Visuele feedback op dashboard wanneer er nog unsynced logitems bestaan.

**Acceptatiecriteria**
- Log submit zonder internet wordt niet verloren.
- Na herstel van verbinding wordt item automatisch of via retry verwerkt.
- Geen dubbele logitems na sync.

## 2) Logfoto Captions en Sortering in Frontend Galerij

**Gebruikerswaarde**
- Bemanning kan foto's logisch ordenen en voorzien van context (bijschrift), zodat terugkijken op tochten duidelijker wordt.

**v1.2.0 oplevering**
- Caption bewerken bij bestaande logfoto's in frontend.
- Volgorde aanpassen van bestaande logfoto's in frontend.
- Frontend galerij toont captions consistent in lijst en lightbox.

**Acceptatiecriteria**
- Gewijzigde caption blijft behouden na herladen.
- Gewijzigde sortering blijft behouden in admin en frontend.
- Lightbox toont dezelfde volgorde als galerij.

## 3) Route Samenvatting per Tocht (detailpaneel)

**Gebruikerswaarde**
- Na elke tocht ziet de gebruiker direct een compacte samenvatting met kerngegevens zonder extra klikken in admin.

**v1.2.0 oplevering**
- Detailkaart op frontend voor laatst afgeronde tocht met:
  - start/eindtijd
  - duur
  - afstand
  - gemiddelde snelheid
  - geschat brandstofverbruik
- Snelle knop om GPX direct voor deze tocht te downloaden.

**Acceptatiecriteria**
- Waarden komen overeen met admin tripoverzicht (binnen afrondingsverschil).
- GPX download werkt vanuit detailpaneel.

## 4) Slimme Meldingen en Actiefeedback (Frontend)

**Gebruikerswaarde**
- Gebruiker begrijpt direct wat er gebeurt bij fouten of blokkades (rechten, duplicate submits, invalid datum).

**v1.2.0 oplevering**
- Uniform feedbackcomponent met niveaus: `succes`, `info`, `waarschuwing`, `fout`.
- Heldere, niet-technische foutteksten voor alle hoofdacties (route, log, TODO, kosten).
- Laatste 3 meldingen compact zichtbaar in een kleine activiteitstijdlijn.

**Acceptatiecriteria**
- Geen "stille" mislukkingen meer op frontend.
- Elke afgewezen actie geeft een concrete reden en vervolgstap.

## 5) Rapportage "Klaar voor delen" (exportpakket)

**Gebruikerswaarde**
- In een paar klikken een deelbaar overzicht voor planning, evaluatie of onderhoudsbespreking.

**v1.2.0 oplevering**
- Nieuwe exportactie in Rapportages: `Exporteer rapportagepakket`.
- ZIP met minimaal:
  - rapportage-CSV
  - trip-CSV
  - geselecteerde GPX-bestanden van de periode
- Bestandsnamen met datumrange voor snelle terugvindbaarheid.

**Acceptatiecriteria**
- Exportpakket wordt zonder fouten opgebouwd.
- Inhoud van ZIP correspondeert met gekozen periodefilters.

## Niet in scope voor v1.2.0

- Multi-boat ondersteuning.
- Volledige native app of PWA-ombouw.
- Nieuwe externe kaartproviders.

## Voorstel uitvoerorde

1. Offline Captains Log Queue
2. Slimme Meldingen en Actiefeedback
3. Logfoto Captions en Sortering
4. Route Samenvatting per Tocht
5. Rapportage exportpakket

## Definitie van gereed (DoR/DoD)

- Per feature minimaal 1 functionele acceptatietest in productie-achtige omgeving.
- Geen regressie op T7-01 t/m T7-22.
- Release notes v1.2.0 en README bijgewerkt voor alle opgeleverde features.
# Stap 7 - Acceptatietest Run Template

**Plugin:** BSO Phoenix  
**Versie onder test:** __________  
**Omgeving:** Live / Staging  
**Datum test:** __________  
**Tester(s):** __________

## 1. Testcontext

| Onderdeel | Waarde |
|---|---|
| WordPress versie |  |
| PHP versie |  |
| Browser desktop |  |
| Browser mobiel |  |
| Mobiel toestel |  |
| HTTPS actief | Ja / Nee |

## 2. Accounts onder test

| Account | Toegang verwacht | Resultaat |
|---|---|---|
| owner_user | Eigenaar (beheer) |  |
| crew_user | Bemanning (schrijven) |  |
| reader_user | Alleen-lezen |  |

## 3. Testuitvoering

Vul per testregel in:

- **Resultaat:** PASS / FAIL / NVT
- **Bewijs:** screenshot, exportbestand, foutmelding, logregel
- **Opmerking:** kort en concreet

| ID | Test | Resultaat | Bewijs | Opmerking |
|---|---|---|---|---|
| T7-01 | Plugin activatie |  |  |  |
| T7-02 | Rollen toewijzen via Toegang-pagina |  |  |  |
| T7-03 | Read-only blokkade op frontend |  |  |  |
| T7-04 | Crew schrijfrechten (TODO + kosten) |  |  |  |
| T7-05 | Owner beheerrechten (instellingen/boot) |  |  |  |
| T7-06 | Start trip mobiel |  |  |  |
| T7-07 | Trackpoints tijdens actieve trip |  |  |  |
| T7-08 | Trackpoint buiten actieve trip wordt geweigerd |  |  |  |
| T7-09 | Stop trip en metrics |  |  |  |
| T7-10 | Duplicate start/stop preventie |  |  |  |
| T7-11 | Log met meerdere foto's + captions |  |  |  |
| T7-12 | Frontend galerij met bestaande logfoto's |  |  |  |
| T7-13 | Lightbox grote preview + navigatie |  |  |  |
| T7-14 | Duplicate log submit preventie |  |  |  |
| T7-15 | Duplicate TODO submit preventie |  |  |  |
| T7-16 | Duplicate kosten submit preventie |  |  |  |
| T7-17 | Datumvalidatie admin |  |  |  |
| T7-18 | Datumvalidatie frontend/AJAX |  |  |  |
| T7-19 | CSV trip export |  |  |  |
| T7-20 | GPX export |  |  |  |
| T7-21 | Rapportage CSV export |  |  |  |
| T7-22 | Media library koppeling |  |  |  |

## 4. Incidentlog

| Nr | Ernst | Omschrijving | Reproductie | Status |
|---|---|---|---|---|
| 1 |  |  |  | Open / Opgelost |
| 2 |  |  |  | Open / Opgelost |
| 3 |  |  |  | Open / Opgelost |

## 5. Samenvatting

**Totaal PASS:** ___  
**Totaal FAIL:** ___  
**Totaal NVT:** ___

**Go / No-Go advies:** GO / NO-GO

**Toelichting:**

_

## 6. Sign-off

| Rol | Naam | Datum | Akkoord |
|---|---|---|---|
| Tester |  |  | Ja / Nee |
| Product Owner |  |  | Ja / Nee |
| Technisch verantwoordelijke |  |  | Ja / Nee |

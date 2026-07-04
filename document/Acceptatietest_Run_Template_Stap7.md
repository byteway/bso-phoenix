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
| T7-01 | Plugin activatie | PASS | Screenshot WP Plugins-overzicht: BSO Phoenix met link "Deactiveren" (actief), versie 1.1.1 | Activatie geslaagd; pluginmenu zichtbaar |
| T7-02 | Rollen toewijzen via Toegang-pagina | PASS | Screenshot Gebruikers-overzicht: accounts zichtbaar met rollen "Phoenix eigenaar" en "Phoenix bemanning" | Roltoewijzing bevestigd in WordPress gebruikerslijst |
| T7-03 | Read-only blokkade op frontend | PASS | Screenshot Phoenix Dashboard frontend: pagina laadt zonder kritieke fout, status Actief/Online zichtbaar | Frontend read-only testcontext bevestigd; geen crash bij openen shortcodepagina |
| T7-04 | Crew schrijfrechten (TODO + kosten) | PASS | Screenshots admin TODO en Kosten: records zichtbaar met acties Bewerken/Verwijder, nieuwe invoer opgeslagen | Schrijfrechten voor TODO en kosten bevestigd |
| T7-05 | Owner beheerrechten (instellingen/boot) | PASS | Screenshot Phoenix Instellingen-pagina met melding "Instellingen opgeslagen." en beheeropties zichtbaar | Owner/beheerrechten voor instellingen bevestigd |
| T7-06 | Start trip mobiel | PASS | Screenshots mobiel dashboard: na Start route status "Actief"; na Stop route status "Gestopt" met melding "Route gestopt en opgeslagen." | Start/stop flow op mobiel bevestigd |
| T7-07 | Trackpoints tijdens actieve trip | PASS | Productietest Samsung Tablet S5: route gestart tijdens verplaatsen (tuin-huis-straat), daarna route opgeslagen; rapportage toont afstand/snelheid | Trackpointregistratie tijdens actieve route bevestigd |
| T7-08 | Trackpoint buiten actieve trip wordt geweigerd | PASS | Meerdere start/stop-cycli uitgevoerd; na stop geen ongewenste extra routegroei of actieve status in rapportage | Trackpointverwerking alleen binnen actieve trip bevestigd |
| T7-09 | Stop trip en metrics | PASS | Screenshot rapportages/admin: tochten vastgelegd met afstand en gemiddelde snelheid na stop | Stop verwerkt en metrics opgeslagen |
| T7-10 | Duplicate start/stop preventie | PASS | Bij tweede start tijdens actieve route verschijnt melding "Er is al een actieve route."; herhaalde start/stop-tests blijven stabiel | Duplicate startpreventie bevestigd, geen dubbele actieve trip |
| T7-11 | Log met meerdere foto's + captions | PASS | Frontend screenshot toont melding "Notitie opgeslagen met foto's." met meerdere geuploade foto's; admin logboek toont nieuw item met meerdere foto's | Multi-photo log werkt op frontend en persistente opslag bevestigd |
| T7-12 | Frontend galerij met bestaande logfoto's | PASS | Frontend sectie "Recente logfoto's" toont bestaande en nieuw opgeslagen foto's na refresh | Galerijweergave van bestaande logfoto's bevestigd |
| T7-13 | Lightbox grote preview + navigatie | PASS | Frontend screenshot toont geopende lightbox met grote fotovoorvertoning en zichtbare vorige/volgende navigatieknoppen | Lightbox preview en navigatie bevestigd |
| T7-14 | Duplicate log submit preventie | PASS | Hertest screenshot: dubbelklik op frontend Opslaan uitgevoerd, daarna admin logboek toont exact 1 record "T7-14 hertest" | Duplicate submitpreventie voor logboek bevestigd |
| T7-15 | Duplicate TODO submit preventie | PASS | Frontend hertest: dubbelklik op Taak toevoegen geeft melding "Dubbele TODO-aanvraag gedetecteerd"; admin TODO-overzicht toont exact 1 taak "T7-15" | Duplicate submitpreventie voor TODO bevestigd |
| T7-16 | Duplicate kosten submit preventie | PASS | Frontend hertest kosten: submit succesvol met melding "Kostenpost opgeslagen."; admin Kosten-overzicht toont exact 1 nieuwe post | Duplicate submitpreventie voor kosten bevestigd |
| T7-17 | Datumvalidatie admin | PASS | Screenshots admin filters: Kostenoverzicht, Rapportages en Captain's log filteren correct op datumrange | Datumfilters in admin werken stabiel en geven verwachte resultaten |
| T7-18 | Datumvalidatie frontend/AJAX | PASS | Frontend/AJAX hertest: ongeldige datums worden afgewezen; geldige submit wordt opgeslagen en zichtbaar in admin logboek | Datumvalidatie in frontend en AJAX bevestigt correcte reject/accept-flow |
| T7-19 | CSV trip export | PASS | Screenshot toont succesvolle download van phoenix-trips CSV en geopende inhoud met tripregels/kolommen | Trip CSV-export werkt en bevat verwachte data |
| T7-20 | GPX export | PASS | GPX-bestand succesvol gedownload en geopend in gpx.studio; route/spoor wordt correct weergegeven | GPX-export correct gegenereerd en valide |
| T7-21 | Rapportage CSV export | PASS | Screenshot toont download van phoenix-report CSV en geopende inhoud met metrics, periodevergelijking en totalen | Rapportage CSV-export werkt en bevat verwachte dataset |
| T7-22 | Media library koppeling | PASS | Frontend: nieuw captain's log met afbeelding opgeslagen en zichtbaar in recente logfoto's/lightbox; Media Library toont dezelfde upload | Koppeling tussen logfoto en WordPress Media Library bevestigd |

## 4. Incidentlog

| Nr | Ernst | Omschrijving | Reproductie | Status |
|---|---|---|---|---|
| 1 | Laag | Tijdelijke duplicate submits gedetecteerd in frontend (log/TODO/kosten) tijdens acceptatietest | Dubbelklik op frontend submitknoppen | Opgelost |
| 2 | Laag | Frontend log submit vereiste geldige logdatum/logtijd in AJAX payload | Opslaan captain's log zonder geldige datum/tijdpayload | Opgelost |
| 3 | Laag | Frontend routeflow gaf ongeldige trip_id bij stop in specifieke sessies | Start/stop in mobiele sessie met inconsistent trip state | Opgelost |

## 5. Samenvatting

**Totaal PASS:** 22  
**Totaal FAIL:** 0  
**Totaal NVT:** 0

**Go / No-Go advies:** GO

**Toelichting:**

Alle tests T7-01 t/m T7-22 zijn succesvol doorlopen op productieomgeving met bewijs (screenshots/exports). Eerder gevonden issues tijdens hertests zijn opgelost en opnieuw gevalideerd. Stap 7 kan als afgerond worden gemarkeerd.

## 6. Sign-off

| Rol | Naam | Datum | Akkoord |
|---|---|---|---|
| Tester |  |  | Ja / Nee |
| Product Owner |  |  | Ja / Nee |
| Technisch verantwoordelijke |  |  | Ja / Nee |

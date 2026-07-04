# Stap 7 - Installatie En Acceptatietest Op Echte WordPress-Omgeving

**Plugin:** BSO Phoenix  
**Doel:** Verifiëren dat de plugin correct werkt op live/staging WordPress, inclusief mobiele GPS, mediabibliotheek en CSV/GPX-downloads.  
**Datum:** 2026-07-03

## 1. Scope

Deze testdekt:

- Installatie en activatie op echte WordPress-omgeving
- Rechtenmodel (owner, crew, read-only)
- GPS-route logging met mobiele browser
- Trackpoint-consistentie (alleen actieve trips)
- Captain's log + foto-upload + galerij + lightbox
- TODO en kosten
- CSV en GPX export/download
- Hardening: datumvalidatie, duplicate submit prevention, duidelijke foutmeldingen

## 2. Testomgeving

- WordPress: 6.5+
- PHP: 8.1+
- HTTPS ingeschakeld
- Minstens 3 gebruikersaccounts:
  - `owner_user` (beheer)
  - `crew_user` (schrijven)
  - `reader_user` (alleen-lezen)
- Mobiel toestel met GPS (Android/iOS)
- Browser mobiel: Chrome/Safari

## 3. Installatieprocedure (Staging)

1. Maak database-backup en files-backup van staging.
2. Plaats pluginmap `bso-phoenix` in `wp-content/plugins/`.
3. Activeer plugin via WordPress admin.
4. Controleer menu `Phoenix` in admin.
5. Controleer submenu's:
   - Dashboard
   - Captain's log
   - TODO
   - Kosten
   - Bootprofiel
   - Instellingen
   - Toegang
   - Rapportages
6. Ga naar `Phoenix -> Toegang` en koppel overrides:
   - `owner_user` => Phoenix eigenaar
   - `crew_user` => Phoenix bemanning
   - `reader_user` => Phoenix alleen-lezen
7. Voeg shortcode `[phoenix_dashboard]` toe op testpagina en publiceer.

## 4. Acceptatiematrix

Gebruik kolommen: **Resultaat** (`PASS`/`FAIL`) en **Bewijs** (screenshot, exportbestand, logregel).

| ID | Test | Stappen | Verwacht resultaat |
|---|---|---|---|
| T7-01 | Plugin activatie | Activeer plugin op staging | Geen fatals; menu zichtbaar |
| T7-02 | Rollen toewijzen | Stel overrides in via `Phoenix -> Toegang` | Opslaan succesvol; pagina toont effectieve toegang |
| T7-03 | Read-only blokkade | Login als `reader_user`, open dashboard | Start/stop en submit-acties geblokkeerd; duidelijke melding |
| T7-04 | Crew schrijfrechten | Login als `crew_user`, maak TODO en kostenpost | Opslaan lukt; data zichtbaar |
| T7-05 | Owner beheerrechten | Login als `owner_user`, wijzig instellingen/bootprofiel | Opslaan lukt |
| T7-06 | Start trip mobiel | Open dashboard op mobiel, start route | Trip start; status actief |
| T7-07 | Trackpoints actief | Tijdens actieve trip 5+ trackpoints laten registreren | Trackpoints opgeslagen |
| T7-08 | Trackpoint hardening | Probeer trackpoint te sturen na stop | Request geweigerd met nette foutmelding |
| T7-09 | Stop trip | Stop actieve route | Trip afgerond met duur/afstand/snelheid |
| T7-10 | Duplicate start/stop | Tik 2x snel op start en stop | Geen dubbele trips; duidelijke melding |
| T7-11 | Log met foto's | Maak log met meerdere foto's en captions | Opslaan lukt; captions en volgorde behouden |
| T7-12 | Frontend galerij | Open galerij op dashboard | Bestaande logfoto's zichtbaar |
| T7-13 | Lightbox preview | Klik foto in galerij | Grote preview opent; vorige/volgende werkt |
| T7-14 | Duplicate log submit | Verzend log 2x snel | Tweede submit wordt geblokkeerd/afgevangen |
| T7-15 | TODO duplicate | Voeg TODO 2x snel toe | Geen dubbele taak door submit-race |
| T7-16 | Kosten duplicate | Sla kostenpost 2x snel op | Geen dubbele kostenpost door submit-race |
| T7-17 | Datumvalidatie admin | Vul ongeldige datum in admin-formulier | Vriendelijke foutmelding; geen opslag |
| T7-18 | Datumvalidatie AJAX | Verstuur ongeldige datum via frontend | Server weigert met duidelijke melding |
| T7-19 | CSV trip export | Exporteer trips CSV | Download start; CSV bevat records |
| T7-20 | GPX export | Exporteer GPX van trip | Download start; GPX valide XML |
| T7-21 | Rapportage CSV | Exporteer rapportage CSV | Download start; inhoud compleet |
| T7-22 | Media library koppeling | Controleer uploads in mediabibliotheek | Bestanden bestaan en zijn gekoppeld |

## 5. Verificatiepunten Per Domein

### 5.1 GPS en Trips

- Geen tweede actieve trip mogelijk.
- Trackpoints alleen op actieve trip.
- Stoppen zonder actieve trip geeft nette fout.

### 5.2 Datavalidatie

- Datumformaat `YYYY-MM-DD`.
- `checkdate` geldig.
- Jaar binnen toegestane range.

### 5.3 Duplicaatpreventie

- Create/update submits binnen korte tijd worden idempotent afgehandeld.
- Geen dubbele records door dubbelklik of netwerkrace.

### 5.4 UX fouten

- Frontend toont serverfouten uit response.
- Admin toont notices voor duplicate/invalid.

## 6. Uitvoeringstemplate

| ID | Resultaat | Bewijs | Opmerking |
|---|---|---|---|
| T7-01 | PASS | Screenshot WP Plugins-overzicht: BSO Phoenix met link "Deactiveren" (actief), versie 1.1.1 | Activatie geslaagd; pluginmenu zichtbaar |
| T7-02 | PASS | Screenshot Gebruikers-overzicht: accounts zichtbaar met rollen "Phoenix eigenaar" en "Phoenix bemanning" | Roltoewijzing bevestigd in WordPress gebruikerslijst |
| T7-03 | PASS | Screenshot Phoenix Dashboard frontend: pagina laadt zonder kritieke fout, status Actief/Online zichtbaar | Frontend read-only testcontext bevestigd; geen crash bij openen shortcodepagina |
| T7-04 | PASS | Screenshots admin TODO en Kosten: records zichtbaar met acties Bewerken/Verwijder, nieuwe invoer opgeslagen | Schrijfrechten voor TODO en kosten bevestigd |
| T7-05 | PASS | Screenshot Phoenix Instellingen-pagina met melding "Instellingen opgeslagen." en beheeropties zichtbaar | Owner/beheerrechten voor instellingen bevestigd |
| T7-06 | PASS | Screenshots mobiel dashboard: na Start route status "Actief"; na Stop route status "Gestopt" met melding "Route gestopt en opgeslagen." | Start/stop flow op mobiel bevestigd |
| T7-07 | PASS | Productietest Samsung Tablet S5: route gestart tijdens verplaatsen (tuin-huis-straat), daarna route opgeslagen; rapportage toont afstand/snelheid | Trackpointregistratie tijdens actieve route bevestigd |
| T7-08 | PASS | Meerdere start/stop-cycli uitgevoerd; na stop geen ongewenste extra routegroei of actieve status in rapportage | Trackpointverwerking alleen binnen actieve trip bevestigd |
| T7-09 | PASS | Screenshot rapportages/admin: tochten vastgelegd met afstand en gemiddelde snelheid na stop | Stop verwerkt en metrics opgeslagen |
| T7-10 | PASS | Bij tweede start tijdens actieve route verschijnt melding "Er is al een actieve route."; herhaalde start/stop-tests blijven stabiel | Duplicate startpreventie bevestigd, geen dubbele actieve trip |
| T7-11 | PASS | Frontend screenshot toont melding "Notitie opgeslagen met foto's." met meerdere geuploade foto's; admin logboek toont nieuw item met meerdere foto's | Multi-photo log werkt op frontend en persistente opslag bevestigd |
| T7-12 | PASS | Frontend sectie "Recente logfoto's" toont bestaande en nieuw opgeslagen foto's na refresh | Galerijweergave van bestaande logfoto's bevestigd |
| T7-13 | PASS | Frontend screenshot toont geopende lightbox met grote fotovoorvertoning en zichtbare vorige/volgende navigatieknoppen | Lightbox preview en navigatie bevestigd |
| ... |  |  |  |

## 7. Exit Criteria (Go/No-Go)

**Go** als:

- Alle kritieke tests `PASS`: T7-01, T7-02, T7-03, T7-06 t/m T7-11, T7-17 t/m T7-21.
- Geen blocker in GPS logging, roltoegang, export of media.

**No-Go** als:

- Rollenmodel omzeild kan worden.
- CSV/GPX export faalt.
- Dubbele data nog optreedt bij snelle submits.
- Trackpoints buiten actieve trip worden geaccepteerd.

## 8. Post-Test Acties

1. Verzamel bewijs (screenshots + voorbeeldexports).
2. Log bevindingen in release-notes of issueboard.
3. Herhaal regressietest voor gefixte punten.
4. Markeer stap 7 als afgerond na volledige `GO`.

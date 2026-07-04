# Testplan - Live route schermvullend toggle

**Story:** 7 - Live route schermvullend toggle  
**Plugin:** bso-phoenix  
**Versie:** v1.2.0 feature branch  
**Datum:** 4 juli 2026  
**Type:** Handmatige functionele test

---

## 1. Doel

Valideren dat de live routekaart in het frontend dashboard betrouwbaar kan schakelen tussen standaardweergave en schermvullende weergave, zonder routeverlies of pagina-herlaad.

---

## 2. Scope

In scope:

- Toggleknop `Volledig scherm` in Live route kaartheader
- Schakelen naar fullscreen en terug naar standaardweergave
- Sluiten via knop `Sluiten`
- Sluiten via `Escape`
- Behoud van routecontext en route-updates tijdens fullscreen
- Werking op desktop en mobiel/tablet viewport
- Gedrag voor read-only en write gebruikers

Out of scope:

- Browser native Fullscreen API permissiedialogen
- Kaartprovider wissels of tile-server performance

---

## 3. Testvoorwaarden

- WordPress-site met actieve plugin `bso-phoenix`
- Pagina met shortcode `[phoenix_dashboard]`
- Account 1: `bso_phoenix_write`
- Account 2: `bso_phoenix_read`
- Geldige GPS/browser permissies voor live route test

---

## 4. Testcases

## A. Fullscreen openen en sluiten (desktop)

**Stap A1**
Open dashboard en controleer zichtbaarheid knop `Volledig scherm` bij Live route.

**Verwacht A1**
Knop is zichtbaar en klikbaar.

**Stap A2**
Klik op `Volledig scherm`.

**Verwacht A2**
Kaart wordt schermvullend getoond, dashboard erachter is uit beeld, knoptekst wijzigt naar `Standaard weergave`.

**Stap A3**
Klik op `Sluiten`.

**Verwacht A3**
Kaart keert terug naar standaard dashboardpositie zonder herladen.

**Status**: OPEN

---

## B. Escape sluit fullscreen

**Stap B1**
Open fullscreen via knop `Volledig scherm`.

**Stap B2**
Druk op `Escape`.

**Verwacht B**
Fullscreen sluit direct en standaard layout wordt hersteld.

**Status**: OPEN

---

## C. Route blijft actief tijdens fullscreen

**Stap C1**
Start een route met write-account.

**Stap C2**
Wacht op meerdere trackpoints en open fullscreen.

**Stap C3**
Observeer trackpoint teller en route-lijn gedurende 30-60 seconden.

**Verwacht C**
Trackpoint teller loopt op en route blijft doorlopen in fullscreen.

**Status**: OPEN

---

## D. Routecontext blijft behouden na terugschakelen

**Stap D1**
Tijdens actieve route: open fullscreen en sluit fullscreen opnieuw.

**Stap D2**
Controleer triplabel, trackpoint teller, afstand en routevorm.

**Verwacht D**
Geen reset van routecontext; gegevens blijven consistent met pre-fullscreen status.

**Status**: OPEN

---

## E. Read-only rechten

**Stap E1**
Log in met account met alleen `bso_phoenix_read`.

**Stap E2**
Open dashboard en gebruik fullscreen toggle.

**Verwacht E**
Toggle werkt (geen mutatie), start/stop en andere schrijfacties blijven geblokkeerd.

**Status**: OPEN

---

## F. Responsief gedrag mobiel/tablet

**Stap F1**
Open dashboard op mobiel of via browser device emulation.

**Stap F2**
Open fullscreen en controleer closeknop bereikbaarheid.

**Stap F3**
Sluit fullscreen via knop of Escape (indien toetsenbord beschikbaar).

**Verwacht F**
Kaart blijft bruikbaar in viewport, closeknop zichtbaar/klikbaar, terugschakelen werkt zonder layout-breuk.

**Status**: OPEN

---

## 5. Verwachte regressiecontrole

- Lightbox Escape-gedrag blijft correct buiten fullscreen-context
- Start/stop route blijft werken na meerdere fullscreen toggles
- Geen JavaScript errors in browserconsole bij enter/exit fullscreen

---

## 6. Resultatenoverzicht

| Blok | Omschrijving | Verwacht | Status | Evidence |
|------|--------------|----------|--------|----------|
| A | Open/sluit fullscreen desktop | Correct schakelen | OPEN | - |
| B | Escape sluit fullscreen | Sluit zonder reload | OPEN | - |
| C | Live route in fullscreen | Blijft updaten | OPEN | - |
| D | Contextbehoud na exit | Geen reset | OPEN | - |
| E | Read-only gedrag | Toegestaan zonder mutatie | OPEN | - |
| F | Mobiele responsiviteit | Bedienbaar en stabiel | OPEN | - |

---

*Gegenereerd op 4 juli 2026 - Testplan Story 7 Live route schermvullend toggle*

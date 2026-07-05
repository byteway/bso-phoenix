Hier is een kant-en-klare PR-tekst die je direct kunt plakken.

PR titel
Hardening import/export validatie en foutrapportage (CSV, GPX, ZIP)

Samenvatting
Deze PR hardent de import/export-keten van BSO Phoenix met focus op:
- striktere validatie van datumranges en coordinaten
- consistente foutafhandeling en foutmeldingen voor admin-exportflows
- betrouwbaardere CSV/ZIP-output (write-checks en veilige bestandsnamen)
- voorkomen dat ongeldige GPX-data nog wordt uitgeleverd in losse export of ZIP-pakket

Doel
Robuuste exportfunctionaliteit leveren die:
- ongeldige input vroeg afwijst
- bij fouten duidelijke feedback aan de gebruiker geeft
- corrupte routegegevens niet als geldige GPX exporteert

Wat is aangepast
1. Centrale validatie
- Datumnormalisatie en datumrange-validatie gebruikt op exportpaden.
- Ongeldige range wordt geblokkeerd met duidelijke invalid_range melding.

2. Admin export hardening
- Trips CSV export:
  - blokkeert ongeldige datumrange
  - CSV write-checks toegevoegd
  - veilige bestandsnaam/header
- Trackpoint export CSV/GPX:
  - ongeldige trip-id, formaat en lege trackpoints geven nette export_error notices
  - coordinaatvalidatie toegevoegd
  - bij alleen ongeldige coordinaten wordt export geblokkeerd

3. Kosten en rapportages export hardening
- Kosten CSV export:
  - rangevalidatie en nette error notice
  - CSV write-checks toegevoegd
- Rapportage CSV export:
  - rangevalidatie
  - centrale row write-checks
- Rapportage ZIP export:
  - preflight en foutcodes voor zip_unavailable, temp_file_failed, zip_open_failed, zip_write_failed, zip_close_failed, zip_read_failed
  - ongeldige trackpoints worden gefilterd
  - GPX-bestanden met uitsluitend ongeldige coordinaten worden niet meer toegevoegd aan ZIP

4. AJAX GPX hardening
- Ongeldige coordinaten worden gefilterd.
- Als geen geldige punten overblijven, wordt download afgewezen.

Testresultaten
Alle hardening-tests zijn uitgevoerd en afgerond.

- Totaal: 10
- PASS: 10
- FAIL: 0
- BLOCKED: 0
- Eindadvies: GO

Gedekte testcases
- TC-HARD-001 t/m TC-HARD-010: PASS

Opmerkelijke validatie-uitkomst
- Voor tripdata met ongeldige coordinaten (bijv. lat 95, lon 181):
  - losse GPX export wordt geblokkeerd
  - CSV trackpoint export wordt nu ook geblokkeerd
  - rapportage ZIP bevat geen foutieve GPX voor die trip

Risico-inschatting
Laag tot middel:
- wijzigingen raken exportpaden en foutafhandeling
- kernfunctionaliteit buiten export is niet functioneel gewijzigd

Rollback
Volledige rollback mogelijk door revert van deze hardening-reeks op feature branch.
Geen database-migraties of schemawijzigingen nodig.

Nazorg
- Testdata in productie terugzetten/opruimen (triprecords/trackpoints die handmatig op ongeldige coordinaten zijn gezet tijdens testen).
- Na merge korte smoke-test:
  - Trips CSV export
  - Trackpoint CSV/GPX export
  - Rapportage CSV export
  - Rapportage ZIP export

Referentie testdocument
document/Testplan_Hardening_Import_Export_Validatie_En_Foutrapportage.md


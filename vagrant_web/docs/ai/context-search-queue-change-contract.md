# Änderungsvertrag: belastbare Kontextsuche-Queues

## 1. Status und Geltung

Dieser Vertrag konkretisiert die Schritte 1 bis 3 der Queue-Stabilisierung aus dem [Kontextsuche- und KI-Vertrag](context-search-ai-contract.md). Er ist ein **Ziel- und Abnahmevertrag**, keine bereits aktivierte Queue-Konfiguration. Es gibt **keine produktiv genutzte Zwischenlösung** und keine vorübergehende Änderung von `QUEUE_RETRY_AFTER`. Die drei Umsetzungsschritte bauen das endgültige Zielbild auf; der neue Kontextsuche-Worker wird erst nach bestandenem Schritt 3 freigegeben. Jeder Schritt erhält nach Prüfung einen **eigenen Commit**; ein Schritt darf nicht wegen eines erfolgreichen Dokumentations- oder Unit-Tests als produktiv freigegeben gelten. Dieses Dokument und seine Verknüpfung sind ein eigener Vertrags-Commit.

MySQL bleibt die fachliche Datenbank und zunächst auch der Laravel-Queue-Treiber. Es wird weder Redis noch Horizon als Voraussetzung eingeführt. Der Produktionsvertrag für `database/default` mit `--timeout=120` und `QUEUE_RETRY_AFTER=150` bleibt das **dauerhafte** Ziel für normale Jobs und Vorschauen. Bundle-Queues und die vorhandene Vorschaupriorität bleiben unberührt. Produktive `.env`-Werte, Supervisor-Konfiguration, Schema, Jobs und Qdrant-Daten werden durch diesen Vertrag nicht geändert.

### Nachgewiesener Ausgangszustand

- `IndexContextSearchResource` setzt einen Job-Timeout von 180 Sekunden, drei Versuche und Backoff von 30/120 Sekunden. Auch `ProcessOcrCalibrationPage` läuft auf `database/context-search-indexing`; dessen Timeout stammt bislang vom dokumentierten manuellen Worker mit `--timeout=180`.
- Die Datenbank-Connection hat `retry_after=150`. Damit kann ein noch laufender Kontextsuche-Job nach 150 Sekunden erneut reserviert werden. Laravel verlangt `job/worker timeout < retry_after`.
- Der Produktions-Supervisor konsumiert ausschließlich `default,resource-previews-low`; der Kontextsuche-Worker wird bislang manuell gestartet. `stopwaitsecs=150` des **Default-Workers** ist nicht auf einen späteren OCR-Worker übertragbar.
- Der Ressourcenjob extrahiert alle PDF-Seiten, erzeugt alle Chunks und bettet sie ein. 100-seitige PDFs und langsamere CPU-/Ollama-Server passen nicht zuverlässig in 180 Sekunden.
- Der Indexer löscht Qdrant-Punkte einer Ressource vor dem batchweisen Neuaufbau. Timeout, Prozessabbruch oder Netzfehler können daher bis zum Wiederanlauf einen unvollständigen Index hinterlassen.
- Produktionsrahmen: Webanwendung, MySQL und Qdrant teilen sich 2 CPU-Kerne, 4 GB RAM und eine 40-GB-SSD; Ollama läuft auf einem oder mehreren Rechnern im LAN. Eine hohe OCR-Parallelität ist deshalb kein geeignetes Mittel gegen Rückstau.

## 2. Übergreifende, nicht verhandelbare Invarianten

1. Für **jede** Queue-Connection gilt: maximaler effektiver Job-Timeout (Job-Eigenschaft hat Vorrang vor Worker-Option) **plus begründete Sicherheitsmarge** kleiner als `retry_after`; Supervisor-`stopwaitsecs` übersteigt die längste zulässige Joblaufzeit. PHP `pcntl`, HTTP- und Subprozess-Timeouts werden separat geprüft. Ein HTTP- oder Tesseract-Aufruf darf den Job-Timeout nicht unbemerkt überleben.
2. Höhere Timeouts sind keine Kapazitätsstrategie. Ein Job verarbeitet eine **begrenzte Arbeitseinheit**. Oversize-, beschädigte und nicht lesbare Quellen erhalten einen beobachtbaren Status; sie blockieren nicht dauerhaft den Worker.
3. Queue-Zustellung ist mindestens einmal möglich. Deduplizierung beim Dispatch (`ShouldBeUnique`) ist kein Exakt-einmal-Versprechen. Jeder externe Seiteneffekt ist mit stabilen Schlüsseln wiederholbar; Abschlusszustände und Zähler werden konkurrierungssicher gebildet.
4. In Job-Payloads und normalen Logs stehen ausschließlich Kennungen, Revisionen und Profil-IDs, niemals Dokumenttext, Vektoren, Secrets oder Dateiinhalte. Private abgeleitete Zwischenartefakte dürfen nur in nicht öffentlich erreichbaren, restriktiv berechtigten Ablagen liegen.
5. Jobs binden sich an **Quellrevision, Indexgeneration, Embedding-Modell-Digest und OCR-/Chunking-Profil**. Eine inzwischen geänderte Ressource oder Konfiguration darf weder alte noch gemischte Punkte als aktuell veröffentlichen.
6. Automatische Indexierung aus Material-/Resource-Events bleibt in der manuellen Stufe 1 deaktiviert. Ein Queue-Worker ist kein impliziter Auslöser neuer Indexläufe.
7. Unverfügbarkeit von Ollama, Qdrant oder OCR stoppt nur betroffene KI-Jobs. Fachliche Schreibvorgänge, normale Queue, Vorschauen und direkte Suche bleiben funktionsfähig. Harte Konfigurationsfehler werden nicht endlos wiederholt.
8. Kein Schritt löscht produktive Jobs, `failed_jobs`, Originaldateien oder Qdrant-Collections automatisch. Bereinigung abgeleiteter Daten erfordert nachvollziehbaren Zielumfang, Prüfung und Rückbaupfad.

## 3. Schritt 1 – Isolierte Queue-Connection ohne Betriebsumschaltung

### Änderung

Die endgültige eigene Laravel-**Datenbank-Queue-Connection** für Kontextsuche wird vorbereitet. Sie nutzt zunächst dieselbe MySQL-Instanz und darf dieselbe `jobs`-Tabelle verwenden, erhält aber eigene `retry_after`-Werte und exklusive Queue-Namen. Niemals dürfen Worker mit unterschiedlichen Reservierungsfristen dieselbe Queue abholen. `database/default,resource-previews-low` bleibt dauerhaft bei 150/120 Sekunden; Bundle-Queues und ihre API-gesteuerte Verarbeitung werden nicht verändert. Das historische `database/context-search-indexing` mit 180-Sekunden-Jobs und `retry_after=150` ist unsicher und darf **ab sofort nicht mehr gestartet** werden. Eine Änderung an der normalen `QUEUE_RETRY_AFTER`-Variable ist ausdrücklich ausgeschlossen.

In diesem Schritt werden Connection-Namen, Queue-Routing der Indexierungs- **und** OCR-Kalibrierungsjobs sowie Konfigurations-/Preflight-Prüfungen vorbereitet. Der neue Worker bleibt gestoppt und neue Kontextsuche-Dispatches bleiben bis Schritt 3 gesperrt; eine Codebereitstellung allein ist keine Betriebsfreigabe. Die Prüfung verwendet **aufgelöste** Laravel-Konfiguration statt allein `.env`-Text, insbesondere bei `config:cache`, berücksichtigt Job-Timeouts mit Vorrang vor Worker-Optionen, bestätigt `pcntl` und verweigert unsichere Timeout-Paare.

Schon gespeicherte Aufträge auf dem alten Queue-Namen werden read-only inventarisiert und einem dokumentierten Wiederanlaufplan zugeordnet. Weder `queue:clear` noch das Löschen von `jobs` ist zulässig. Bevor irgendein neuer Worker startet, müssen alle alten aktiven Jobs beendet und alte Worker nachweislich gestoppt sein; wartende Jobs werden später fachlich neu disponiert oder mit dem bisherigen sicheren Ausführungsweg gezielt abgeschlossen. Ein bloßes Umbenennen der Connection lässt alte Jobs sonst liegen und ist verboten.

### Bewusste Begrenzung

Dieser Schritt nimmt **keine** Indexierung wieder auf. Die bestehende Ressourcenarbeit bleibt bis zur Zerlegung in Schritt 3 zu groß; bloß höhere Timeouts würden langsame oder defekte PDFs nicht zuverlässig beherrschbar machen. Der 120-Sekunden-Timeout gilt derzeit pro externem OCR-Prozess und nicht für die gesamte Seite. Diese Kette erhält in Schritt 3 ein gemessenes, begrenztes Gesamtbudget.

### Abnahme und Rückbau

- Konfigurationstest: getrennte Connection und Queue-Namen für alle Kontextjobs; Default-Worker-Konfiguration, 150-Sekunden-Reservierung und Queue-Reihenfolge bleiben gleich.
- Tests blockieren unsichere Timeout-Paare und die irrtümliche Verwendung des alten `database/context-search-indexing`-Workers. OCR-Kalibrierung darf nicht auf der alten Connection verbleiben.
- Inventar und Cutover-Plan für wartende, reservierte und fehlgeschlagene Altjobs liegen vor. Kein Kontextworker und keine neue automatische Event-Indizierung werden durch diesen Schritt aktiviert.
- Rückbau: bei fehlgeschlagenem Schritt bleiben neue Dispatches und Worker gesperrt; die normalen Queues laufen unverändert weiter. Es ist keine produktive Timeout-Rückstellung nötig.

## 4. Schritt 2 – Endgültige Worker- und Betriebsgrenzen vorbereiten

### Ziel und Migrationsgrenze

Die dedizierten OCR-/Extraktions- und Embedding-/Qdrant-Worker werden mit **endgültigen getrennten Queue-Namen** vorbereitet. Der Start ist bis zur Abnahme von Schritt 3 deaktiviert. Verschiedene Job-Timeouts dürfen eigene Connections erhalten, sofern keine Queue von Workern mit unterschiedlichen `retry_after` konsumiert wird. Kein neuer Worker konsumiert `default`, `resource-previews-low` oder `bundle_{id}_queue`.

Die Betriebsdefinition enthält bereits Start/Pause/Fortsetzung, Monitoring, Kapazitätsstopp, Konfigurationscache- und Deploy-Reihenfolge sowie den Cutover für Altjobs. Sie wird erst mit der seitenweisen Jobarchitektur aus Schritt 3 aktiviert. Ein Zwischenbetrieb des alten Ressourcenjobs auf der neuen Connection ist ausgeschlossen.

### Betriebsprofil

- Zunächst ein dedizierter OCR-/Extraktions-Worker auf dem 2-Kern-Webserver (`numprocs=1`); kein OCR im Default-Worker. Embedding-/Qdrant-Jobs erhalten einen anderen Queue-Namen und eine eigene kleine Worker-Grenze. Eine zweite Embedding-Instanz wird erst nach RAM-, MySQL-, Qdrant- und Ollama-Messung freigegeben. Mehrere Ollama-Server erlauben verteilte Embeddings, aber keine Erhöhung der lokalen OCR-Konkurrenz ohne Hostmessung.
- Als **Messstart für das endgültige Seitenjob-Profil**: Job-Timeout höchstens 480 Sekunden, zugehörige Connection `retry_after` mindestens 600 Sekunden, Supervisor `stopwaitsecs` größer als 480 Sekunden. Diese Zahlen sind keine Produktionsfreigabe; sie werden auf OCR-/Last-Datasets einschließlich langsamer Seiten geprüft und vor Aktivierung nötigenfalls angepasst. Ein separater Embedding-Worker kann kürzere Werte erhalten, wenn Connection und Queue-Namen eindeutig getrennt sind.
- Jeder externe Aufruf erhält Verbindungs- und Gesamttimeout; sequenzielle Poppler-/Tesseract-/Ollama-/Qdrant-Budgets müssen **zusammen** unter dem Job-Budget bleiben. Subprozesse und temporäre Dateien werden auch nach Fehler, Abbruch und Timeout kontrolliert freigegeben; ein überlebender Kindprozess darf keine zweite OCR-Ausführung verdecken.
- Der normale Worker, interaktive KI-Jobs und die direkte Suche haben Vorrang. Hintergrund-Backfills bleiben manuell; das spätere Zeitfenster startet außerhalb der freigegebenen Zeit **keinen neuen** Hintergrundjob, lässt aber einen laufenden Job auslaufen. Berechtigte interaktive Jobs nutzen eine getrennte Queue mit eigener Obergrenze und serverseitiger Rechteprüfung; sie umgehen weder RAM-/Disk-Schutz noch Pause wegen kritischer Kapazität.
- Warteschlangentiefe, ältester wartender Job, tatsächliche Laufzeiten (p50/p95/p99), Timeout-/Retry-/Fehlerrate, OCR-Seiten/min, Qdrant-/Ollama-Latenz, freier RAM/SSD und Worker-Neustarts werden ohne Nutzdaten erfasst. Bei Disk unter der vertraglichen Stoppschwelle oder Ressourcenüberlast werden neue Hintergrundaufträge angehalten und Alarm ausgelöst, nicht verworfen. Wiederaufnahme erfolgt nach dokumentierter Prüfung.

### Abnahme und Rückbau

- Tests prüfen Connection **und** Queue-Namen jedes Kontextjobs, OCR-Kalibrierung, Preflight, Timeoutordnung, Backoff und konfigurationsgecachten Betrieb. Default-/Preview-/Bundle-Jobs bleiben auf bisherigen Queues.
- Ein gezielter Störungstest wird vorbereitet und in Schritt 3 mit laufenden Seitenjobs ausgeführt: pausierte, wartende und laufende Jobs, Deployment-Restart, Worker-Crash und keine zweite OCR-Ausführung.
- Die Produktionsanleitung enthält Supervisor-Definition, Start/Pause/Fortsetzung, Queue-Tiefen- und `failed_jobs`-Kontrolle, `config:cache`-/Deploy-Reihenfolge und schonenden Rückbau. Vor Schritt 3 erfolgt kein produktiver Cutover. Rückbau bedeutet: neue Dispatches sperren, Queue-Bestände inventarisieren, laufende Arbeit auslaufen lassen, erst danach Jobs bewusst neu disponieren; niemals die Connection bloß zurückbenennen.
- Der Produktionsvertrag, README, Beispielkonfiguration und Preflight spiegeln **ab Aktivierung in Schritt 3** den tatsächlich implementierten Zustand wider. `QUEUE_RETRY_AFTER=150` für den Default-Worker bleibt jederzeit unverändert.

## 5. Schritt 3 – Begrenzte Seitenarbeit und revisionssichere Veröffentlichung

### Zerlegung und Persistenz

Der bisherige Ressourcenjob wird zum kleinen Planungs-/Abschlusskoordinator. Für PDFs wird pro Seite oder für eine anhand **gemessener** Obergrenzen kleine Seitengruppe gearbeitet; Textressourcen erhalten ebenfalls eine begrenzte Arbeitseinheit. Extraktion/OCR, Chunking/Embedding und Qdrant-Upsert sind getrennte Jobtypen oder klar begrenzte, unabhängig wiederanlaufbare Phasen. Die Planung erzeugt höchstens ein konfiguriertes Fenster ausstehender Seitenjobs je Lauf; bei Tausenden Ressourcen werden Jobs cursorbasiert nachgefüllt, nicht auf einmal in MySQL abgelegt. Seitennummern sind einsbasiert und Chunks überschreiten keine Seitengrenze. **Erst nach diesem Schritt** dürfen der neue Worker und manuelle Indexläufe produktiv aktiviert werden.

Der Implementierungsschritt muss vor Schema-/Storage-Änderungen die **genaue** Zwischenablage freigeben lassen. Bevorzugtes Zielbild: MySQL führt nur revisionsgebundene Job-/Seitenzustände, Zähler, Prüfsummen und begrenzte Metadaten; extrahierter Volltext liegt, soweit ein Phasenübergang ihn erfordert, als privates, atomar geschriebenes und nach Ablauf bereinigbares **abgeleitetes** Artefakt im bestehenden persistenten Storage. Queue-Payloads enthalten nur dessen Referenz und Hash. Vor Verarbeitung wird der Hash geprüft. Eine Alternative ohne Zwischenartefakt ist nur zulässig, wenn sie keine wiederholte teure OCR, keine unbeschränkten Joblaufzeiten und keine ungeklärte Crash-Lücke erzeugt. Neue Tabellen/Spalten und Speicherorte sind eigene Entscheidungspunkte vor Codeänderung.

**Freigabe zu Schritt 3:** Option A ist bestätigt. Die privaten, wiederherstellbaren Seitenartefakte liegen unter `storage/app/context-search-ocr-artifacts` auf dem bestehenden lokalen Disk; neue MySQL-Seitenzustände und eine Tabelle für die veröffentlichte Indexrevision sind freigegeben. Diese Artefakte werden nicht mit den Originalen gesichert, sondern nach einem Verlust aus der Originalressource neu erzeugt. Ein fehlender oder beschädigter Artefakt-Hash führt ausdrücklich zurück zur Extraktions-Queue und niemals zur Markierung als erfolgreich. Die Quellrevision bleibt im Qdrant-Payload separat von der Indexrevision (Quellrevision plus OCR-/Chunking-/Embedding-Profil). Änderungen an Profilen dürfen die bisher veröffentlichte Revision nicht überschreiben. Diese Freigabe ist **keine** Freigabe zum Start produktiver Worker; dafür gelten weiterhin die vollständigen Abnahmebedingungen dieses Abschnitts.

Alle Phasen prüfen bei Start und vor Seiteneffekten Quellrevision, Indexgeneration und Profil. Bei Änderung der Quelle werden veraltete Jobs als `stale` beendet; ein neuer Lauf wird in Stufe 1 **nur manuell** geplant. Leere, qualitativ verworfene oder nicht lesbare Seiten bekommen unterscheidbare Endzustände und begründete Zähler. Ein einzelner defekter Scan darf andere Seiten nicht endlos blockieren. Gesamtstatus wird nach stabilen Seitenzuständen transaktional berechnet, nicht durch konkurrierende `++`-Updates. Terminalsituationen (`completed`, `skipped`, `failed`, `stale`) sind eindeutig; Timeout ohne PHP-`finally` wird beim Wiederanlauf erkannt. Ausreichend kurze, erneuerbare Locks schützen vor konkurrierenden Koordinatoren; ihr Ablauf ersetzt nicht die Zustandsprüfung.

### Qdrant-Sicherheitsgrenze

Das Löschen aller bisherigen Punkte **vor** dem Neuaufbau entfällt. Neue Punkte werden unter stabilen IDs und der neuen Dokumentrevision vorbereitet. Erst wenn erwartete Seiten-/Chunkzahlen, Modellprofil, Qdrant-Bestätigung und Quellenmetadaten verifiziert sind, wird die Revision als suchbar veröffentlicht. Alte Punkte werden anschließend verzögert und nachvollziehbar bereinigt. Bis zur semantischen Suche muss eine verbindliche Leseregel existieren, die ausschließlich die veröffentlichte Revision berücksichtigt; unveröffentlichte Teilpunkte und veraltete Revisionen dürfen nie als normale Treffer erscheinen. Für vollständige Modellwechsel bleibt die Collection-/Alias-Strategie des Hauptvertrags maßgeblich. Wiederholte Upserts dürfen keine doppelten Punkte erzeugen; ein Crash zwischen Qdrant-Antwort und MySQL-Abschluss wird durch Statusabgleich und Reconciliation geheilt. Löschung einer Ressource, Rechtewechsel, Abbruch einer Generation und fehlgeschlagene Bereinigung dürfen keine unautorisierten Treffer ausliefern.

### Langsame Server, Backpressure und Fehlerklassen

- 480/600 Sekunden sind nur ein konservativer **Messstart**. Die Abnahme umfasst p99 plus Ausreißer, Prozessketten und erzwungene Hänger. Ist eine einzelne Seite zu groß, wird sie mit explizitem Grund zurückgestellt oder durch einen gesondert zu genehmigenden Ressourcenpfad verarbeitet; der globale Job-Timeout wird nicht unbegrenzt erhöht.
- OCR arbeitet zunächst seriell. Eine Queue- und Disk-Obergrenze stoppt Nachfüllen, nicht laufende Arbeit. Arbeitsspeicher und temporäre Bilddateien werden pro Seite begrenzt; 300 DPI werden nach dem bestehenden Pixelbudget abgesenkt. Temp-Dateien sind nach Erfolg und Fehler entfernt; verwaiste Reste werden nur zielgenau und nach Alters-/Referenzprüfung bereinigt.
- Netzwerk-/Überlast-/Timeoutfehler bei Ollama und Qdrant erhalten begrenzte Versuche und gestaffelten Backoff mit Jitter. Digest-, Dimensions-, Authentifizierungs-, Schema- oder Quellrevisionsfehler werden als dauerhaft/konfigurationsbedingt klassifiziert und nicht blind wiederholt. Failover auf den nächsten Ollama-Server ist nur bei identischem verifiziertem Embedding-Profil erlaubt.
- Das Zeitfenster wird erst im späteren Automatisierungsschritt aktiviert, seine spätere Semantik wird bereits hier testbar vorbereitet: Zeitfenster begrenzt **Start**, nicht Fertigstellung; interaktive Jobs müssen autorisiert und von Batcharbeit getrennt sein; Pause, Serverausfall und Deploy verlieren keine Aufträge.

### Abnahme, Rollout und Rückbau

- Unit-/Integrationstests: 1/mehrere/100 Seiten, reiner Text, Misch-PDF, leere Seite, Handschrift/Low-Quality, verschlüsselte/defekte PDF, geänderte oder gelöschte Ressource, Qdrant-/Ollama-Ausfall, Timeout in jeder Phase, Doppelzustellung, parallele Worker, ungültiges Profil und Wiederanlauf nach Prozessabbruch.
- Ein End-to-End-Test mit echten Test-PDFs prüft Seitenzahl, OCR-Methode, Zeichenposition, Quellen-Snippet, Chunk-/Punktzahl, vollständige Publikation und Reconciliation. Die isolierte OCR-, Last- und Kapazitätsauswertung ermittelt reale Laufzeiten, RAM-/Disk-Spitzen, Backlog-Abbau und Einfluss auf Web-/MySQL-/Qdrant-Latenz. Keine Produktions-PDFs oder privaten Texte in Testlogs.
- Vor produktiver Massenindizierung: 1 OCR-Worker und 1 kleiner Embedding-Worker als Start, Beobachtungszeit und dokumentierte Grenzwerte; danach erst gestufte Batchgrößen. Bei Überschreitung greifen Stop/Backpressure und Alarm statt automatischer Konkurrenzsteigerung.
- Rollout in eigener, zunächst nicht aktiver Generation; Veröffentlichung erst nach Vollständigkeits- und Rechteprüfung. Rückbau stoppt neue Dispatches, lässt laufende Jobs auslaufen, schaltet auf die zuvor validierte Generation/Alias zurück und hält neue abgeleitete Artefakte für Diagnose vor. Eine aktive Collection wird nicht durch pauschales Löschen zurückgesetzt.
- Der Schritt ist erst abgeschlossen, wenn Betriebsanleitung, Preflight, README, Konfigurationsbeispiele, gezielte Tests und ein dokumentierter Crash-/Restore-Durchlauf dem implementierten Verhalten entsprechen. Danach folgt ein eigener Commit gemäß Hauptvertrag.

## 6. Offene Entscheidungen vor Umsetzung

| Entscheidung | Empfehlung | Alternative und Nachteil |
| --- | --- | --- |
| Betriebsumschaltung | Einmaliger Cutover nach Schritt 3; bis dahin alter Kontextworker gestoppt, normale Queue unverändert bei 150 Sekunden | Vorzeitiger Zwischenbetrieb spart Wartezeit, schafft aber erneut Timeout-, Migrations- und Doppelverarbeitungsrisiken und ist deshalb ausgeschlossen |
| Zwischenablage für Seitentext | Private, abgeleitete Datei mit MySQL-Status/Hash und begrenzter Aufbewahrung | Volltext in MySQL vereinfacht Transaktionen, belastet aber Datenbank, Backup und 40-GB-Host; erneute OCR ohne Ablage erhöht Laufzeit und Ausfallrisiko |
| Seiten-Timeout und Parallelität | Mit 480/600 Sekunden und einem lokalen OCR-Worker **messen**, nicht blind produktiv festlegen | Größere Zeitlimits/mehr Worker verschieben Hänger und verschärfen CPU-/RAM-/MySQL-Druck |
| Veröffentlichung | Revisionsgebundene Freigabe nach vollständigem Upsert plus späterer Altpunktbereinigung | Sofortiges Löschen/Ersetzen ist einfacher, lässt bei Abbruch jedoch Lücken oder Teiltreffer |

Die drei Umsetzungsschritte autorisieren **nicht** automatisch neue Tabellen, Migrationen, produktive Konfigurationsänderungen, zusätzliche Worker oder eine geänderte Sichtbarkeit. Diese werden vor ihrer jeweiligen Implementierung nach `decision-template.md` konkret freigegeben. Maßgebliche externe Referenz für Timeout, Pause, Worker-Neustart und Supervisor-Stoppfrist ist die [offizielle Laravel-13-Queue-Dokumentation](https://laravel.com/framework/docs/13.x/queues).

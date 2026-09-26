# Änderungsvertrag: belastbare Kontextsuche-Queues

## 1. Status und Geltung

Dieser Vertrag konkretisiert die Schritte 1 bis 3 der Queue-Stabilisierung aus dem [Kontextsuche- und KI-Vertrag](context-search-ai-contract.md). Er ist ein **Ziel- und Abnahmevertrag**, keine bereits aktivierte Queue-Konfiguration. Die bestehende manuelle Stufe 1 bleibt bis zur jeweiligen Implementierung unverändert. Jeder der drei unten genannten Umsetzungsschritte erhält nach Prüfung einen **eigenen Commit**; ein Schritt darf nicht wegen eines erfolgreichen Dokumentations- oder Unit-Tests als produktiv freigegeben gelten. Dieses Dokument und seine Verknüpfung sind ein eigener Vertrags-Commit.

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

## 3. Schritt 1 – Sofortige Timeout-Absicherung des manuellen Betriebs

### Änderung

Bis die getrennte Connection aus Schritt 2 bereitsteht, ist `QUEUE_RETRY_AFTER=240` ein **befristeter Übergangswert**, wenn der bisherige `database/context-search-indexing`-Worker mit maximal 180 Sekunden laufen soll. Dieser Wert betrifft sämtliche Jobs auf der `database`-Connection: Nach hartem Worker-Ausfall verzögert sich die erneute Zustellung auch für Default- und Preview-Jobs von 150 auf höchstens 240 Sekunden. Eine produktive Änderung benötigt daher eine gesonderte Rollout-Freigabe und eine aktualisierte Produktionsbetriebsanweisung. Auf einer Umgebung, in der dies nicht akzeptabel ist, bleibt der Kontextsuche-Worker gestoppt, bis Schritt 2 umgesetzt ist; ein 180-Sekunden-Worker mit `retry_after=150` wird nicht erneut gestartet.

Die Implementierung prüft **aufgelöste** Laravel-Konfiguration statt allein den `.env`-Dateitext, insbesondere bei `config:cache`. Sie prüft alle Job-Timeouts auf dieser Connection einschließlich OCR-Kalibrierung, bestätigt `pcntl` und dokumentiert den tatsächlichen Workeraufruf. Preflight/Start-Schutz verweigert unsichere Timeout-Paare. Schon laufende Jobs dürfen beim Konfigurationswechsel nicht durch einen zweiten Worker derselben Queue mit anderer Reservierungsfrist überholt werden: zuerst neue Dispatches stoppen, Worker auslaufen lassen, Konfiguration deployen, Worker neu starten, erst danach Dispatch freigeben.

### Bewusste Begrenzung

240 Sekunden **verlängern den 180-Sekunden-Job nicht**. Schritt 1 ist nur ein Sicherheitsfix gegen doppelte Reservierung. Längere PDFs und durch Messung als zu langsam erkannte Ressourcen werden bis Schritt 3 nicht im Bulk gestartet; eine Vorprüfung muss sie mit Grund als zurückgestellt kennzeichnen statt drei teure Timeout-Versuche zu erzwingen. OCR-Kalibrierungsseiten dürfen ebenfalls nicht unbemerkt die 180-Sekunden-Grenze überschreiten. Der 120-Sekunden-Timeout gilt derzeit pro externem OCR-Prozess und nicht für die gesamte Seite; die tatsächlich mögliche Kette ist deshalb gesondert zu begrenzen oder als fehlgeschlagen zu melden. Fehlgeschlagene Seiten und Ressourcen bleiben gezielt erneut startbar.

### Abnahme und Rückbau

- Konfigurationstest: `retry_after` ist größer als jeder tatsächlich wirksame Kontext-Job-/Worker-Timeout; unsichere Werte blockieren den Start. Default-Worker-Konfiguration und Queue-Reihenfolge bleiben gleich.
- Integrationstest mit künstlich langsamem Job und **zwei** Workern derselben Queue: keine zweite Ausführung vor dem ersten Timeout; nach Worker-Abbruch genau ein späterer Wiederanlauf. Seiteneffekte bleiben auch bei Wiederholung idempotent.
- Test für lange/defekte PDF, OCR-Hänger und Modell-/Qdrant-Ausfall: begrenzter Abschluss oder sichtbarer Fehler statt endloser Reservierung. Keine automatische Event-Indizierung.
- Operationsnachweis: Konfigurationscache neu aufgebaut, Worker kontrolliert neu gestartet, aktive/fehlgeschlagene Jobs und Queue-Tiefe beobachtet. Ein Rollback auf 150 erfolgt **erst nach** Umstellung aller Kontextjobs auf die getrennte Connection in Schritt 2 oder bei gestopptem Kontextworker.

## 4. Schritt 2 – Getrennte Connection und kontrollierte Worker

### Ziel und Migrationsgrenze

Eine eigene Laravel-**Datenbank-Queue-Connection** für Kontextsuche nutzt zunächst dieselbe MySQL-Instanz und darf dieselbe `jobs`-Tabelle verwenden, erhält aber eigene `retry_after`-Werte. Für die jeweilige Connection werden **exklusive Queue-Namen** verwendet; niemals dürfen Worker mit verschiedenen `retry_after` dieselbe Queue abholen. `database/default,resource-previews-low` bleibt bei 150/120 Sekunden. Bestehende Bundle-Queues werden weder umbenannt noch übernommen.

Indexierungs- und OCR-Kalibrierungsjobs dispatchen erst nach einem kontrollierten Cutover auf die neue Connection. Bereits gespeicherte Jobs tragen ihren alten Queue-Namen: Vor dem Umschalten werden aktive und wartende Jobs inventarisiert, alte Worker auslaufen gelassen und wartende Aufträge kontrolliert abgearbeitet oder fachlich neu disponiert. Es gibt **kein** pauschales `queue:clear`, Löschen aus `jobs` oder stilles Liegenlassen auf einem nicht mehr konsumierten Queue-Namen. Der Cutover ist erst abgeschlossen, wenn alte und neue Queue getrennt sichtbar und der alte Bestand erklärt sind.

### Betriebsprofil

- Zunächst ein dedizierter OCR-/Extraktions-Worker auf dem 2-Kern-Webserver (`numprocs=1`); kein OCR im Default-Worker. Embedding-/Qdrant-Jobs erhalten einen anderen Queue-Namen und eine eigene kleine Worker-Grenze. Eine zweite Embedding-Instanz wird erst nach RAM-, MySQL-, Qdrant- und Ollama-Messung freigegeben. Mehrere Ollama-Server erlauben verteilte Embeddings, aber keine Erhöhung der lokalen OCR-Konkurrenz ohne Hostmessung.
- Als **vorläufige Zielwerte nach Schritt 3**: begrenzter Seitenjob höchstens 480 Sekunden, zugehörige Connection `retry_after` mindestens 600 Sekunden, Supervisor `stopwaitsecs` größer als 480 Sekunden. Diese Zahlen sind keine Produktionsfreigabe; sie werden auf OCR-/Last-Datasets einschließlich langsamer Seiten geprüft. Bis Schritt 3 gelten die realen 180/240-Werte auf der dedizierten Connection. Ein separater Embedding-Worker kann kürzere Werte erhalten, wenn Connection und Queue-Namen eindeutig getrennt sind.
- Jeder externe Aufruf erhält Verbindungs- und Gesamttimeout; sequenzielle Poppler-/Tesseract-/Ollama-/Qdrant-Budgets müssen **zusammen** unter dem Job-Budget bleiben. Subprozesse und temporäre Dateien werden auch nach Fehler, Abbruch und Timeout kontrolliert freigegeben; ein überlebender Kindprozess darf keine zweite OCR-Ausführung verdecken.
- Der normale Worker, interaktive KI-Jobs und die direkte Suche haben Vorrang. Hintergrund-Backfills bleiben manuell; das spätere Zeitfenster startet außerhalb der freigegebenen Zeit **keinen neuen** Hintergrundjob, lässt aber einen laufenden Job auslaufen. Berechtigte interaktive Jobs nutzen eine getrennte Queue mit eigener Obergrenze und serverseitiger Rechteprüfung; sie umgehen weder RAM-/Disk-Schutz noch Pause wegen kritischer Kapazität.
- Warteschlangentiefe, ältester wartender Job, tatsächliche Laufzeiten (p50/p95/p99), Timeout-/Retry-/Fehlerrate, OCR-Seiten/min, Qdrant-/Ollama-Latenz, freier RAM/SSD und Worker-Neustarts werden ohne Nutzdaten erfasst. Bei Disk unter der vertraglichen Stoppschwelle oder Ressourcenüberlast werden neue Hintergrundaufträge angehalten und Alarm ausgelöst, nicht verworfen. Wiederaufnahme erfolgt nach dokumentierter Prüfung.

### Abnahme und Rückbau

- Tests prüfen Connection **und** Queue-Namen jedes Kontextjobs, OCR-Kalibrierung, Preflight, Timeoutordnung, Backoff und konfigurationsgecachten Betrieb. Default-/Preview-/Bundle-Jobs bleiben auf bisherigen Queues.
- Ein gezielter Störungstest zeigt, dass pausierte, wartende und laufende Jobs korrekt behandelt werden; der Deployment-Restart wartet auf den laufenden Job und startet keine zwei OCR-Worker. Bei Worker-Crash bleiben Jobs auffindbar und erneut verarbeitbar.
- Die Produktionsanleitung enthält Supervisor-Definition, Start/Pause/Fortsetzung, Queue-Tiefen- und `failed_jobs`-Kontrolle, `config:cache`-/Deploy-Reihenfolge und einen schonenden Rückbau. Der produktive Cutover wird separat freigegeben. Rückbau bedeutet: neue Dispatches sperren, beide Queue-Bestände inventarisieren, laufende Arbeit auslaufen lassen, erst danach Jobs bewusst neu routen; niemals die Connection bloß zurückbenennen.
- Nach Cutover wird `QUEUE_RETRY_AFTER=150` für den Default-Worker wiederhergestellt. Der Produktionsvertrag, README, Beispielkonfiguration und Preflight spiegeln dann **den tatsächlich implementierten** Zustand wider.

## 5. Schritt 3 – Begrenzte Seitenarbeit und revisionssichere Veröffentlichung

### Zerlegung und Persistenz

Der bisherige Ressourcenjob wird zum kleinen Planungs-/Abschlusskoordinator. Für PDFs wird pro Seite oder für eine anhand **gemessener** Obergrenzen kleine Seitengruppe gearbeitet; Textressourcen erhalten ebenfalls eine begrenzte Arbeitseinheit. Extraktion/OCR, Chunking/Embedding und Qdrant-Upsert sind getrennte Jobtypen oder klar begrenzte, unabhängig wiederanlaufbare Phasen. Die Planung erzeugt höchstens ein konfiguriertes Fenster ausstehender Seitenjobs je Lauf; bei Tausenden Ressourcen werden Jobs cursorbasiert nachgefüllt, nicht auf einmal in MySQL abgelegt. Seitennummern sind einsbasiert und Chunks überschreiten keine Seitengrenze.

Der Implementierungsschritt muss vor Schema-/Storage-Änderungen die **genaue** Zwischenablage freigeben lassen. Bevorzugtes Zielbild: MySQL führt nur revisionsgebundene Job-/Seitenzustände, Zähler, Prüfsummen und begrenzte Metadaten; extrahierter Volltext liegt, soweit ein Phasenübergang ihn erfordert, als privates, atomar geschriebenes und nach Ablauf bereinigbares **abgeleitetes** Artefakt im bestehenden persistenten Storage. Queue-Payloads enthalten nur dessen Referenz und Hash. Vor Verarbeitung wird der Hash geprüft. Eine Alternative ohne Zwischenartefakt ist nur zulässig, wenn sie keine wiederholte teure OCR, keine unbeschränkten Joblaufzeiten und keine ungeklärte Crash-Lücke erzeugt. Neue Tabellen/Spalten und Speicherorte sind eigene Entscheidungspunkte vor Codeänderung.

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
| Übergang in Schritt 1 | Temporär 240 Sekunden für `database`, danach Rückkehr zu 150; Kontextworker bis dahin nur kontrolliert manuell | Sofort Schritt 2 ohne Zwischenwert: sauberer für Default-Wiederanlauf, aber die heutige unsichere Kontext-Queue muss bis dahin stillstehen |
| Zwischenablage für Seitentext | Private, abgeleitete Datei mit MySQL-Status/Hash und begrenzter Aufbewahrung | Volltext in MySQL vereinfacht Transaktionen, belastet aber Datenbank, Backup und 40-GB-Host; erneute OCR ohne Ablage erhöht Laufzeit und Ausfallrisiko |
| Seiten-Timeout und Parallelität | Mit 480/600 Sekunden und einem lokalen OCR-Worker **messen**, nicht blind produktiv festlegen | Größere Zeitlimits/mehr Worker verschieben Hänger und verschärfen CPU-/RAM-/MySQL-Druck |
| Veröffentlichung | Revisionsgebundene Freigabe nach vollständigem Upsert plus späterer Altpunktbereinigung | Sofortiges Löschen/Ersetzen ist einfacher, lässt bei Abbruch jedoch Lücken oder Teiltreffer |

Die drei Umsetzungsschritte autorisieren **nicht** automatisch neue Tabellen, Migrationen, produktive Konfigurationsänderungen, zusätzliche Worker oder eine geänderte Sichtbarkeit. Diese werden vor ihrer jeweiligen Implementierung nach `decision-template.md` konkret freigegeben. Maßgebliche externe Referenz für Timeout, Pause, Worker-Neustart und Supervisor-Stoppfrist ist die [offizielle Laravel-13-Queue-Dokumentation](https://laravel.com/framework/docs/13.x/queues).

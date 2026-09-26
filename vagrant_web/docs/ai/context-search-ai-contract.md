# Planungs- und Arbeitsvertrag: Kontextsuche und KI-Funktionen

## 1. Status, Zweck und Verbindlichkeit

Dieses Dokument ist der verbindliche fachliche, technische und organisatorische Vertrag für die Kontextsuche und die darauf aufbauenden KI-Funktionen des Materialpools. Es ersetzt alle früheren Zielbilder für den Vektorspeicher vollständig.

> [!IMPORTANT]
> Qdrant ist der allein vorgesehene Vektorspeicher. Alle Artefakte der verworfenen Vektorspeicher-Variante werden in einem eigenen, prüfbaren Umsetzungsschritt vollständig entfernt. Es bleiben davon keine Verbindung, Migration, Tabelle, Erweiterung, Containerdefinition, Umgebungsvariable, Abhängigkeit, Implementierung, Testannahme oder Betriebsanweisung im Kontextsuche-Modul zurück.

Dieser Vertrag beschreibt das Zielbild und die verbindliche Reihenfolge. Er allein installiert noch keine Infrastruktur und führt keine Datenmigration aus. Architektur-, Datenbank-, Queue-, Berechtigungs-, Abhängigkeits- und Infrastrukturänderungen werden stufenweise umgesetzt und nach den Regeln in Abschnitt 3 abgenommen.

Maßgeblich bleiben außerdem:

- [Architektur](architecture.md), [Domänen-Invarianten](domain-invariants.md) und [Quality Gates](quality-gates.md);
- [Produktions- und Deploymentvertrag](production-deployment-contract.md);
- die bestehenden API-Verträge `v1` und `v2` sowie die serverseitigen Policies;
- die vorhandene Suchzellen-Semantik: Suchzeilen werden mit `AND`, Begriffe innerhalb einer Zeile mit `OR` verknüpft.

## 2. Verbindliche Entscheidungen

| Thema | Entscheidung |
| --- | --- |
| Fachliche Daten | MySQL bleibt führende Datenbank und alleinige fachliche Wahrheit. |
| Vektorspeicher | Qdrant speichert den abgeleiteten und jederzeit neu aufbaubaren semantischen Suchindex. |
| Qdrant-Anbindung | Laravel spricht Qdrant über dessen REST-API und den Laravel HTTP Client an. Eine zusätzliche PHP-Client-Abhängigkeit wird nur nach gesonderter Begründung eingeführt. |
| Ergebnisobjekt | Primäres Suchergebnis ist ein Material. Darunter werden passende Ressourcen und exakte Fundstellen angezeigt. |
| Suchlogik | Die bestehende Suchzellen-Logik bleibt erhalten. Klassische Treffer aus der vorhandenen Suche und semantische Treffer aus Qdrant werden in Laravel zusammengeführt. |
| Dokumentumfang | Zunächst werden PDF-Dateien und vorhandene Textressourcen berücksichtigt. Bücher werden wie PDFs behandelt. |
| Ausgeklammert | URLs, Bilder, Audio und Video werden vorerst nicht inhaltlich indiziert. URL-Crawling ist eine spätere Stufe. |
| Sprachen | Deutsch und Englisch werden unterstützt. |
| KI-Anbindung | Embeddings und generative Aufgaben laufen über einen oder mehrere Ollama-Server im selben lokalen Netzwerk. |
| Embedding-Konsistenz | Innerhalb einer Indexgeneration verwenden alle Ollama-Server exakt dasselbe Embedding-Modell, denselben Modell-Digest, dieselben Parameter und dieselbe Dimension. |
| Modellwechsel | Ein Modell- oder Dimensionswechsel erzeugt eine neue Qdrant-Collection. Der aktive Index wird niemals in-place umgedeutet. |
| Quellen | Jeder semantische Treffer muss Ressource, Dokumentrevision, PDF-Seite und eine reproduzierbare Textstelle liefern. |
| KI-Vorschläge | Schlagwort- und Bibelstellen-Vorschläge sind sichtbar als KI-Vorschläge markiert und werden erst durch menschliche Bestätigung übernommen. |
| Neue Schlagworte | Die KI darf neue Schlagworte vorschlagen. Angelegt werden sie erst nach Annahme und Dublettenprüfung. |
| Angenommene Vorschläge | Nach Annahme verhalten sich Vorschläge wie reguläre Einträge. Ein interner Audit-Nachweis über die Herkunft bleibt erhalten. |
| Kurzbeschreibung | Die KI-Fassung wird separat gespeichert und nur ersatzweise mit KI-Symbol angezeigt, wenn keine menschliche Beschreibung vorhanden ist. Ziel: zwei bis drei Sätze, maximal ungefähr 300 Zeichen. |
| Queue-Zeitfenster | Für Hintergrund-KI-Jobs gibt es ein optionales Ausführungszeitfenster. Außerhalb bleiben Jobs erhalten. |
| Interaktive Jobs | Berechtigte Benutzer dürfen interaktive KI-Jobs außerhalb dieses Zeitfensters starten. Recht und Lastgrenzen werden serverseitig geprüft. |
| Administration | Fachliche Betriebs- und Modellparameter sind nur für Superadministratoren änderbar. Secrets und Netzwerkzugänge bleiben Serverkonfiguration. |
| OCR | Tesseract ist als seitenweiser Fallback zulässig, wenn eine PDF-Seite keinen hinreichenden nativen Text liefert. |
| Stufe 1 | Die erste Indexierung wird ausschließlich manuell gestartet. Automatische Event-Anbindung folgt erst in einer freigegebenen späteren Stufe. |
| Evaluationsdaten | Produktionsinhalte werden ausschließlich als eingefrorene, hashgebundene und getrennte Datensätze in eine isolierte Evaluationsumgebung überführt. Kalibrierung, Modellvergleich und Abnahme finden niemals auf dem Produktionssystem statt. |
| Private Evaluationsdaten | Private Materialien und Ressourcen dürfen nach ausdrücklicher Auswahl Bestandteil eines Evaluationsdatensatzes sein. Die Auswahl verlangt eine dokumentierte Begründung und bleibt nachvollziehbar. |
| Transfer und Ablage | Evaluationspakete werden auf ausdrückliche Entscheidung unverschlüsselt übertragen und gespeichert. Sie dürfen nur in nicht öffentlich erreichbaren, restriktiv berechtigten Ablagen liegen; Manifest- und Archivprüfsummen sichern ihre Integrität. |
| Produktionsindex | Ausschließlich eine freigegebene Konfiguration wird von der Evaluations- in die Produktionsumgebung übernommen. Vektoren, Qdrant-Collections und Kalibrierungsergebnisse werden nicht kopiert; Produktion baut ihren Index aus ihren eigenen Quellen auf. |
| Dataset-Kuratierung | Ausschließlich Global Admins kuratieren Evaluationsdatensätze über einen eigenen, serverseitig geschützten KI-Modus in der bestehenden Material- und Ressourcenoberfläche. |
| Dataset-Mitgliedschaft | Ein Material und eine Dokumentrevision dürfen innerhalb eines Datensatzes nur einmal vorkommen. Zweckübergreifende Wiederverwendung folgt der expliziten Überlappungsmatrix unten; eine Auswahl bleibt immer der vollständige zusammenhängende Material-/Ressourcenblock. |
| Dataset-Fortschritt | Jeder Entwurf besitzt versionierte Sollmengen. Ein zugänglicher Tooltip zeigt je Dataset Ist, Soll, verbleibende Anzahl und erfüllte Teilquoten; Farbe ist nur ergänzend zu Icon und Text. |

## 3. Arbeits- und Commitvertrag

Die Umsetzung erfolgt ausschließlich in den in Abschnitt 14 definierten Schritten. Für **jeden einzelnen Schritt** gilt verbindlich:

1. Vor der Änderung werden die betroffenen Verträge, Implementierungen, Tests und Betriebsdateien geprüft.
2. Entscheidungspflichtige Abweichungen werden vor ihrer Umsetzung vorgelegt.
3. Der Schritt erhält passende automatisierte Tests und die proportional erforderlichen Quality Gates.
4. Änderungen werden gegen den ausdrücklich vereinbarten Abnahmeumfang geprüft.
5. Nach erfolgreicher Prüfung wird ein eigener, inhaltlich abgeschlossener Git-Commit erstellt.
6. Der nächste Schritt beginnt erst nach dem erfolgreichen Commit des vorherigen Schritts.

Zusätzliche Regeln:

- Keine Sammel-Commits über mehrere Vertragsschritte.
- Keine fremden oder sachfremden Arbeitsverzeichnisänderungen in einen Schritt aufnehmen; Dateien werden gezielt gestaged.
- Ein fehlgeschlagener oder nur teilweise geprüfter Schritt wird nicht als abgeschlossen committed. Der Blocker wird dokumentiert und zuerst behoben oder zur Entscheidung vorgelegt.
- Jeder Abschlussbericht nennt Commit-ID, geänderten Umfang, ausgeführte und ausgelassene Prüfungen sowie verbleibende Risiken.
- Dokumentation und Betriebshinweise gehören zum selben Commit wie die dadurch eingeführte Funktion.
- Eine notwendige Korrektur nach einem bereits abgeschlossenen Schritt erhält einen eigenen Korrektur-Commit; veröffentlichte Commits werden nicht stillschweigend umgeschrieben.
- Auch Änderungen dieses Vertrags sind eigenständige Schritte und erhalten jeweils einen eigenen Commit.

## 4. Größenordnung und Kapazitätsrahmen

Die Architektur muss ungefähr 100.000 bis 200.000 Ressourcen und Materialien tragen. Darunter sind etwa 100 PDFs mit je rund 100 Seiten und 30.000 PDFs mit je 2 bis 7 Seiten. Im Normalbetrieb kommen 1 bis 10 Ressourcen täglich hinzu, bei Bundle-Importen mehrere Tausend.

Bei durchschnittlich ein bis drei Chunks je Seite entstehen voraussichtlich 70.000 bis 660.000 aktive PDF-Chunks. Mit Textressourcen, Wachstum und Sicherheitsreserve wird für mindestens **1,5 Millionen aktive Punkte** geplant. Alte Generationen zählen zusätzlich und dürfen nicht unbegrenzt erhalten bleiben.

Der Webserver besitzt 2 CPU-Kerne, 4 GB RAM und 40 GB SSD. Qdrant teilt sich diesen Host mit der Anwendung und weiteren Diensten. Das ist eine Pilot- und Startkonfiguration, keine bestätigte Zielkapazität. Vor dem vollständigen Backfill ist ein Capacity Gate Pflicht.

Für die SSD gelten zunächst:

- mindestens 8 GB bleiben als freie Notfallreserve erhalten;
- unter 12 GB freiem Speicher werden Superadministratoren gewarnt und keine neuen Massenläufe begonnen;
- unter 8 GB pausieren Indexierungs-, OCR- und Generationswechsel-Jobs automatisch;
- aktive Collection, neue Collection, Segmentoptimierung, temporäre Dateien und Rückrollreserve gehen gemeinsam in die Vorabschätzung ein;
- Qdrant-Snapshots werden nicht dauerhaft auf derselben SSD aufbewahrt;
- alte Collections werden erst nach bestandener Validierung und Ablauf des Rückrollfensters entfernt.

Der reale Bedarf wird in einem Spike mit repräsentativen Dokumenten gemessen. Erfasst werden mindestens Punkt- und Payload-Größe, Collection-Größe, Segmentoptimierung, RAM-Spitze, Indexierungsdurchsatz, p95-Suchlatenz und Einfluss auf die Webanwendung. Werden die Ziele verfehlt, ist die bevorzugte Reihenfolge: Payload reduzieren, Chunking anhand der Qualitätsmessung optimieren, Qdrant auf einen separaten SSD-Host verschieben, RAM beziehungsweise Datenträger erweitern. Quantisierung darf erst nach einem Recall-Vergleich aktiviert werden.

Die erste inhaltsfreie Produktionsinventarisierung vom 26. September 2026 weist 12.917 öffentliche Materialien sowie 12.591 geeignete Ressourcen aus: 117 öffentliche PDFs, 4.741 private PDFs, 5.367 öffentliche Textressourcen und 2.366 private Textressourcen. Sie ist eine Planungsgrundlage, keine Kapazitätsfreigabe und enthält keine private Materialzahl.

## 5. Zielarchitektur

```text
MySQL – fachliche Wahrheit
  Materialien, Ressourcen, Zuordnungen, Rechte, menschliche Daten,
  angenommene Vorschläge, KI-Entwürfe, Audit und Betriebsprofile
             |
             | manuell gestartete, resumierbare Jobs
             v
Extraktion und Chunking
  PDF-Text, OCR-Fallback, Sprache, Seiten- und Positionsmetadaten
             |
             +--------------------> Ollama-Pool im lokalen Netz
             |                       Embeddings / Vorschläge / Kurztexte
             v
Qdrant – abgeleiteter Suchindex
  versionierte Collections, Chunk-Payloads, Vektoren und Payload-Indizes
             |
             v
Laravel-Suchfusion
  bestehende direkte Suche + semantische Kandidaten + Gruppierung
             |
             v
abschließende Autorisierungsprüfung und Materialabbildung in MySQL
             |
             v
Materialtreffer mit Ressource, Seite, Textstelle und Suchart
```

MySQL speichert alle fachlichen Daten, Beziehungen, Rechte, Konfigurationen, Auditdaten und Jobzustände. Qdrant enthält nur abgeleitete Chunks, Payloads und Vektoren. Ollama verarbeitet Text, ist aber kein dauerhaftes Datenlager. Laravel orchestriert Extraktion, Modellprofile, Jobs, Qdrant-Zugriff, Suchfusion, Rechteprüfung und Präsentation. Der Browser entscheidet niemals über Leserechte, Herkunft oder Annahme eines Vorschlags.

Der Suchindex ist eventual consistent. Fachliche Schreibvorgänge dürfen nicht von Ollama oder Qdrant abhängig sein. Indexjobs sind idempotent, unterbrechbar und resumierbar. Ein späterer Reconciliation-Prozess erkennt fehlende, veraltete oder verwaiste Punkte anhand stabiler IDs und Inhalts-Hashes.

## 6. Qdrant-Konzept

### 6.1 Collections, Aliase und Generationen

Jede Indexgeneration erhält eine eigene physische Collection. Der Name enthält einen sicheren technischen Präfix, Profil-Hash und Generationsbezeichner, zum Beispiel `materialpool_chunks_<profile>_<generation>`. Der stabile Alias `materialpool_chunks_active` zeigt auf genau eine validierte Collection.

Ein Modellwechsel erfolgt als Blue-Green-Verfahren:

1. Modellprofil und erwartete Dimension feststellen und sperren.
2. Neue Collection neben dem aktiven Index anlegen.
3. Dokumente vollständig in die neue Collection einbetten.
4. Vollständigkeit, Recall, Quellen, Rechtefilter, Latenz und Ressourcenverbrauch prüfen.
5. Alias atomar auf die neue Collection umschalten.
6. Vorherige Collection für ein begrenztes Rückrollfenster behalten.
7. Nach dokumentierter Freigabe in einem eigenen Betriebsschritt entfernen.

Dimensionsgleiche Modelle gelten nicht als kompatibel. Zu jedem Modellprofil werden mindestens Provider, Modellname, tatsächlicher Modell-Digest, Dimension, Distanzmetrik, Normalisierungsparameter, Chunking-Version und Aktivierungszeitpunkt geführt.

### 6.2 Punkte und Payload

Ein Qdrant-Punkt repräsentiert genau einen Chunk einer Dokumentrevision. Seine ID wird deterministisch aus Indexgeneration, Ressourcen-ID, Inhalts-Hash, Seite und Chunk-Ordnungsnummer erzeugt. Wiederholte Upserts sind dadurch idempotent.

Die Payload enthält mindestens:

| Feld | Zweck |
| --- | --- |
| `resource_id` | stabile Referenz auf die fachliche Ressource |
| `document_revision` | Hash der indizierten Dokumentrevision |
| `source_type` | `pdf` oder `text` |
| `page_number` | einsbasierte PDF-Seite; bei Textressourcen `1` |
| `chunk_ordinal` | stabile Reihenfolge innerhalb der Seite |
| `start_character`, `end_character` | Zeichenbereich im extrahierten Seitentext |
| `chunk_text` | belegbarer Fundtext für Snippet und Quellenanzeige |
| `heading_path` | optionale Abschnittsüberschriften |
| `language` | erkannte beziehungsweise bestätigte Sprache |
| `extraction_method` | nativer Text oder OCR |
| `extraction_quality` | messbare Extraktions- beziehungsweise OCR-Güte, soweit verfügbar |
| `extractor_version` | reproduzierbare Version des Extraktionswegs |
| `chunking_version` | verwendetes Chunking-Profil |
| `embedding_profile` | unveränderlicher Profilbezeichner |
| `indexed_at` | technischer Indizierungszeitpunkt |

Der begrenzte Chunktext wird bewusst in Qdrant gespeichert: So kann jeder Kandidat mit einer exakt zum Vektor passenden Fundstelle angezeigt und geprüft werden. Originaldateien, vollständige doppelte Dokumentkopien, menschliche Beschreibungen und Berechtigungsentscheidungen gehören nicht in Qdrant.

Payload-Indizes werden nur für tatsächlich gefilterte oder gruppierte Felder angelegt, mindestens für `resource_id`, `document_revision`, `source_type`, `language` und `embedding_profile`. Volltextfelder werden nicht pauschal als Filterindex angelegt. Qdrant bleibt zunächst der semantische Index; die bestehende direkte Suche bleibt für Wörter, Phrasen und strukturierte Treffer zuständig.

Materialzuordnungen werden zunächst nicht in jeden Punkt dupliziert. Qdrant liefert Ressourcen- und Chunkkandidaten; Laravel bildet diese über MySQL auf Materialien ab. Erst eine Messung darf eine gezielte, abgeleitete Materialprojektion rechtfertigen.

### 6.3 API, Sicherheit und Wiederaufbau

Der Qdrant-Zugriff wird hinter einem anwendungsinternen Interface gekapselt. Die REST-Implementierung verwendet explizite Verbindungs- und Antwort-Timeouts, strukturierte Fehlerkategorien und Logging ohne Secrets oder Dokumentinhalte. Wiederholungen sind nur für idempotente Aufrufe erlaubt. Authentifizierungsfehler, Dimensionsabweichungen und Schemaabweichungen sind harte Fehler. Ein Circuit Breaker schützt Webanwendung und Queue.

Qdrant wird nicht öffentlich erreichbar betrieben. Netzwerkzugriff ist auf Web-/Queue-Hosts und administrative Betriebswege beschränkt. Produktion verwendet API-Key und verschlüsselte Transportverbindungen oder eine gleichwertig abgesicherte private Verbindung. Zugangsdaten stehen ausschließlich in Server-Secrets. Suchtreffer werden nach der Qdrant-Abfrage immer anhand aktueller MySQL-Rechte gefiltert.

Qdrant muss vollständig aus MySQL und dem bestehenden Dateispeicher wiederaufbaubar sein. Snapshots sind eine optionale Beschleunigung, kein Ersatz für den Wiederaufbau. Sie werden verschlüsselt und außerhalb der 40-GB-Systemplatte aufbewahrt. Wiederherstellung und Aliasumschaltung werden getestet.

## 7. Ollama-Pool und Modellvertrag

Mehrere Ollama-Server dürfen als geordneter Pool konfiguriert werden. Für alle aktiven Server eines Embedding-Profils gilt:

- identischer Modellname und verifizierter Modell-Digest;
- identische Dimension und relevante Inferenzparameter;
- erfolgreicher Start-Selbsttest mit Dimensions- und Probevektorprüfung;
- eigene Parallelitätsgrenze, Timeouts, Gesundheitszustand und Circuit Breaker.

Neue Batches werden deterministisch auf gesunde Server verteilt. Damit können mehrere Queue-Worker parallel arbeiten, ohne denselben Job doppelt zu verarbeiten. Bei Verbindungsfehler, Timeout, Überlastung oder geeignetem Serverfehler wird unmittelbar der nächste gesunde Server versucht. Modell-, Digest- oder Dimensionsabweichungen lösen **keinen** Failover mit gemischten Vektoren aus, sondern stoppen den Lauf als Konfigurationsfehler.

Die Ollama-Hardware ist zunächst mit 4 CPU-Kernen, 8 GB RAM und begrenzter GPU angesetzt. Startwerte sind ein generativer Job gleichzeitig und kleine konfigurierbare Embedding-Batches. Limits werden anhand gemessener Latenz, VRAM/RAM und Fehlerrate angepasst.

Ein konkretes Modell wird vor Freigabe auf Deutsch und Englisch, Reproduzierbarkeit, Dimension, Durchsatz und Qualität geprüft. Embedding- und generative Modelle dürfen getrennt konfiguriert sein. Ein generatives Modell darf niemals stillschweigend Suchvektoren erzeugen.

## 8. Extraktion, OCR, Chunking und Quellen

PDFs werden seitenweise verarbeitet. Zuerst wird die eingebettete Textschicht verwendet. Tesseract läuft nur für Seiten unter konfigurierten Textmengen- oder Qualitätsschwellwerten. OCR-Sprache, Engine-Version und Qualitätswert werden protokolliert. Textressourcen werden als einseitige Quellen mit Zeichenpositionen behandelt. Nicht verarbeitbare Dateien erhalten einen nachvollziehbaren Fehlerstatus.

Für Tesseract beträgt die Zielauflösung 300 DPI. Die Rasterung senkt sie bei großen Seiten anhand eines konfigurierbaren Pixelbudgets (Standard: 12 Millionen Pixel) seitenweise ab; die tatsächlich verwendete DPI-Zahl wird im OCR-Ergebnis protokolliert. Text und TSV-Konfidenzdaten stammen aus demselben Tesseract-Lauf. Änderungen an Ziel-DPI oder Pixelbudget erfordern eine neue OCR-Kalibrierung, bevor ein zuvor freigegebenes Qualitätsprofil weiterverwendet wird.

Die erste kalibrierbare Chunking-Baseline lautet:

- niemals über eine PDF-Seitengrenze hinweg chunken;
- Überschriften, Absätze, Listen und Satzgrenzen bevorzugen;
- Zielgröße 900 bis 1.400 Zeichen;
- Überlappung 120 bis 200 Zeichen;
- kurze zusammengehörige Abschnitte nicht künstlich aufblasen;
- sehr lange Abschnitte satzweise teilen;
- Modell-Tokenlimit als harte zusätzliche Grenze prüfen.

Das Chunking-Profil wird versioniert. Änderungen verlangen eine neue Indexgeneration und einen Vergleich auf dem Evaluationssatz. Zeichenpositionen beziehen sich auf den gespeicherten extrahierten Seitentext vor verlustbehaftender Normalisierung. OCR-Chunks bleiben erkennbar.

Jeder angezeigte Kontexttreffer enthält mindestens Materialtitel, Ressourcentitel, Dokumentrevision, einsbasierte PDF-Seite, begrenztes Textsnippet mit Hervorhebung, technische Zeichenposition und OCR-Kennzeichnung. PDF-Koordinaten für eine direkte Viewer-Markierung können später ergänzt werden. Kann keine belastbare Quelle geliefert werden, darf der Treffer nicht als normaler Kontexttreffer erscheinen.

## 9. Hybride Suche und Relevanz

Die bestehende direkte Suche bleibt unverändert die Basis. Laravel führt ihre Ergebnisse mit den semantischen Qdrant-Kandidaten zusammen. Direkte und semantische Scores werden nicht roh addiert. Als Baseline dient eine rangbasierte Fusion, insbesondere Reciprocal Rank Fusion. Gewichtung, Kandidatenzahl und Mindestgüte werden auf einem kuratierten deutsch-englischen Evaluationssatz kalibriert.

Pro Suchzelle gilt:

1. bestehende direkte Kandidaten ermitteln;
2. Query mit dem aktiven Embedding-Profil einbetten;
3. Qdrant nach Chunks abfragen;
4. Chunks je Ressource gruppieren und Spitzenwerte begrenzen;
5. Ressourcen in MySQL auf sichtbare Materialien abbilden;
6. direkte und semantische Ranglisten fusionieren;
7. die bestehende `OR`-Logik innerhalb der Zelle und `AND`-Logik zwischen Zellen anwenden;
8. Quellen der beitragenden Chunks ausgeben.

Ein späterer Sparse-Vector- oder serverseitiger Hybridmodus in Qdrant ist eine eigene, messpflichtige Ausbaustufe. Er ersetzt nicht automatisch die bestehende direkte Suche.

Der Evaluationssatz enthält genaue Begriffe, Synonyme, Umschreibungen, Deutsch und Englisch, erwartete Nulltreffer, fachlich ähnliche Falschtreffer, Bibelstellenvarianten, OCR-Fälle und Rechtefälle. Gemessen werden mindestens Recall@K, nDCG@K oder MRR, Nulltrefferpräzision, Quellenrichtigkeit, p50/p95-Latenz und Rechteverletzungen. Eine leere Ergebnisliste ist besser als ein Treffer unterhalb der validierten Mindestgüte.

## 10. KI-Vorschläge und Kurzbeschreibungen

Schlagwort- und Bibelstellen-Vorschläge besitzen mindestens `pending`, `accepted`, `rejected` und `stale`. Gespeichert werden Vorschlagswert, normalisierte Zielreferenz, Ressourcen-/Materialrevision, Modellprofil, Promptversion, Relevanz, Begründung, Zeitstempel und prüfender Benutzer.

`pending` ist sichtbar als KI-Vorschlag markiert. Nur eine autorisierte menschliche Aktion erzeugt beziehungsweise verknüpft den fachlichen Eintrag. Annahme ist transaktional und prüft Dubletten. Geänderte Quellinhalte machen offene Vorschläge `stale`.

Bibelstellen werden strukturiert angefordert, gegen das bestehende Modell normalisiert, auf gültige Buch-, Kapitel- und Versgrenzen geprüft und mit vorhandenen Zuordnungen abgeglichen. Semantische Relevanz und formale Gültigkeit sind getrennte Kriterien.

Eine Materialbeschreibung berücksichtigt mehrere sichtbare Ressourcen. Ausgabeziel sind zwei bis drei Sätze und maximal ungefähr 300 Zeichen. Die KI-Beschreibung bleibt ein separates Feld mit Modell-, Prompt- und Revisionsmetadaten. Menschlicher Text hat immer Vorrang.

## 11. Queues, Zeitfenster und manuelle Stufe 1

Die konkrete Stabilisierung des gegenwärtigen Queue-Betriebs ist im [Änderungsvertrag für belastbare Kontextsuche-Queues](context-search-queue-change-contract.md) festgelegt. Dessen Schritte 1 bis 3 werden nacheinander mit jeweils eigenem geprüften Commit umgesetzt; die Beschreibung hier bleibt das fachliche Zielbild.

Getrennte Jobtypen sind mindestens vorgesehen für Extraktion/OCR, Chunking/Embedding, Index-Upsert, Vorschläge und Kurzbeschreibung. Jeder Job ist idempotent, versionsgebunden und besitzt begrenzte Versuche sowie Backoff.

In Stufe 1 wird Indexierung ausschließlich per explizitem Admin-/CLI-Auftrag gestartet. Parameter erlauben mindestens einzelne Ressourcen, begrenzte Batches und Fortsetzung ab einem Cursor. Automatische Listener auf Resource- oder Material-Events werden in Stufe 1 nicht aktiviert.

Das optionale Hintergrund-Zeitfenster definiert erlaubte Startzeiten. Außerhalb werden Hintergrundjobs verzögert, nicht verworfen. Interaktive Jobs dürfen mit eigener serverseitiger Berechtigung außerhalb starten, verwenden aber eine getrennte, kleine Parallelitätsgrenze. Importwellen besitzen Backpressure; Webanfragen und direkte Suche haben Vorrang.

## 11a. Evaluationsdatensätze und Produktionsübernahme

Für Modellwahl, Chunking, Schwellenwerte, hybride Gewichtung und Abnahme existiert eine von Produktion getrennte Evaluationsumgebung mit eigener MySQL-Datenbank, eigener privater Dateiablage und eigenem Qdrant-Alias. Sie ist kein Produktionsspiegel: Importiert werden nur die für einen eingefrorenen Datensatz nötigen Material-, Ressourcen- und Zuordnungsdaten einschließlich der zugehörigen Quelldateien. Benutzerkonten, OAuth-Daten, API-Keys, Sitzungen, Auditprotokolle und sonstige nicht erforderliche Produktionsdaten gehören niemals in ein Evaluationspaket.

Jeder Datensatz besitzt eine stabile Kennung, Zweck, Ersteller, Zeitpunkt, Auswahlregeln, Sollmengen, Inhalts- und Manifest-Hash, Versionsstand sowie einen unveränderlichen Satz aus Materialien, Ressourcen und konkreten Revisionen. Material und vollständige Dokumentrevision bleiben stets gemeinsam. Kalibrierung und Abnahme bleiben strikt disjunkt. OCR darf dieselben vollständigen Blöcke wie Kalibrierung oder Abnahme enthalten. Last und Kapazität sind orthogonale Betriebsprüfungen und dürfen vollständige Blöcke mit allen anderen Zwecken einschließlich einander wiederverwenden; sie dürfen aber nicht zur Anpassung semantischer Relevanz, Prompts oder Schwellenwerte dienen. Mehrere Datensätze desselben Zwecks bleiben disjunkt. Die Zuordnung ist transitiv abgeschlossen: Wird ein Material gewählt, gehören alle seine geeigneten PDF-/Textressourcen dazu; gehört eine dieser Ressourcen einem weiteren Material, gehört auch dieses Material mit seinen geeigneten Ressourcen dazu. Der vollständige zusammenhängende Block wird vor der Übernahme berechnet, angezeigt und atomar reserviert.

- `calibration`: darf für Modell-, Chunking- und Gewichtungsentscheidungen verwendet werden;
- `acceptance`: unveränderlicher Holdout für die endgültige Abnahme; jede Nutzung zur Kalibrierung entwertet ihn und verlangt einen neuen Abnahmesatz;
- `ocr`: repräsentative Seiten mit und ohne native Textschicht zur Prüfung von Tesseract und Quellen;
- `load`: repräsentative Menge für Durchsatz, Backpressure und Lastverhalten;
- `capacity`: skalierte Menge für Speicher-, RAM- und Latenzprojektionen.

Die initialen Sollgrößen sind Startwerte, keine automatische Auswahl: `calibration` 450 Ressourcen, `acceptance` 250 Ressourcen, `ocr` 100 PDFs, `load` 2.000 Ressourcen und `capacity` 8.000 Ressourcen. OCR ist eine überlappende Qualitätsdimension und darf vollständige Blöcke aus Kalibrierung oder Abnahme teilen. Last und Kapazität sind eigenständige Betriebsprüfungen; sie dürfen Blöcke aus allen anderen Zwecken und gegenseitig wiederverwenden. Kalibrierung und Abnahme bleiben strikt disjunkt. Der eindeutige Mindestbedarf wird daher nur aus Kalibrierung plus Abnahme berechnet (anfangs 700 Ressourcen); OCR-, Last- und Kapazitätsziele sind zusätzliche Prüfvolumina, aber kein zusätzlich erforderlicher Bestand eindeutiger Quellen. Das Last-/Kapazitätsset wird bei Bedarf durch unabhängige Duplikation/Mehrfachabfrage desselben Testbestands belastet, nicht durch eine semantische Neukalibrierung. Zusätzliche Quoten werden je Entwurf als nachvollziehbare Mischung gespeichert, zunächst mindestens PDF/Text und öffentliche/private Sichtbarkeit. OCR-spezifische Unterquoten nach nativer Textschicht oder OCR-Ergebnis werden erst nach einer diagnostischen Extraktionsmessung befüllt; die Oberfläche darf keinen unbekannten OCR-Status vortäuschen.

Ein Dataset durchläuft `draft`, `ready`, `frozen` und `exported`. Nur `draft` ist veränderbar. `ready` bedeutet, dass alle Sollmengen und Mindestquoten erfüllt sind, nicht dass eine KI-Qualitätsabnahme erfolgt ist. Ein `frozen`- oder `exported`-Dataset behält seine Mitgliedschaften dauerhaft. Neue exklusive Mitgliedschaften verwenden noch nicht belegte Blöcke; zulässige OCR-, Last- und Kapazitätsüberschneidungen ergänzen vollständige Blöcke ohne deren bestehende Zuordnung zu ändern.

Der KI-Modus zeigt in Material- und Ressourcenlisten ein Dataset-Badge mit festem Icon, Kurztext und kontrastreicher Farbe. Vorgesehen sind: Kalibrierung (Regler), Abnahme (Prüfzeichen), OCR (Scan), Last (Tacho) und Kapazität (Datenbank). Beim Hover, Fokus und auf Touch-Geräten per Aktivierung öffnet sich derselbe zugängliche Tooltip: `Ist / Soll`, verbleibende Materialien und Ressourcen, erfüllte Quoten, Draft-Status sowie ein erklärender Fortschrittsbalken mit Textalternative. Die Oberfläche lädt für diesen Tooltip nur aggregierte Zähler, niemals Dokumentinhalte.

Mehrfachauswahl, Bereichsauswahl und gespeicherte Filter beschleunigen die Kuratierung. Die seitenbezogene Sammelaktion fügt alle vollständigen, auswählbaren Blöcke der aktuell dargestellten Seite hinzu oder entfernt deren Auswahl; sie ändert keine außerhalb der Seite liegenden Häkchen und überspringt gesperrte Blöcke. Vor jeder Übernahme zeigt eine Vorschau zwingend direkte und transitiv hinzukommende Materialien/Ressourcen, bereits belegte Blöcke, Konflikt-Datasets sowie die Auswirkung auf alle Fortschrittsbalken. Die serverseitige Mutation prüft Global-Admin-Berechtigung, erwartete Dataset-Version und atomare Zweckregeln: pro Dataset und Mitglied darf es nur eine Mitgliedschaft geben; OCR darf mit Kalibrierung/Abnahme, Last und Kapazität mit allen anderen Zwecken überlappen; Kalibrierung und Abnahme bleiben disjunkt. Ein Datenbank-Unique-Constraint schützt zusätzlich die Eindeutigkeit pro Dataset; sortierte Row-Locks serialisieren konkurrierende Buchungen derselben Ressourcen. Kandidaten zeigen sämtliche Zugehörigkeiten und erlaubte Überlappungen blockieren die Auswahl nicht. Eine reine UI-Ausblendung genügt nicht.

Die Produktionsbefehle dürfen nur inventarisieren, eine Auswahl vorprüfen, einen Datensatz einfrieren und ein Paket exportieren. Sie starten weder Ollama, noch Qdrant-Indexierung, Kalibrierung oder Bewertung. Ein Paket enthält die minimal erforderlichen Fachdaten und revisionsgebundenen Quelldateien unter technisch neutralen Pfaden; ursprüngliche Serverpfade und Dokumentinhalte erscheinen nicht in Konsolenausgaben, Manifest-Zusammenfassungen oder Logs. Ein Import erzeugt in der Evaluationsumgebung bei Bedarf einen lokalen technischen Importbenutzer statt echter Produktionskonten.

Private Inhalte werden nicht stillschweigend exportiert: Der Produktionsbefehl verlangt dafür eine explizite Option und eine Zweckbegründung. Die Freigabe, enthaltene IDs und Hashes werden in der Datensatzhistorie protokolliert, ohne Inhalte oder Titel in normale Logs zu schreiben. Die unverschlüsselte Übertragung ist bewusst zugelassen; dennoch sind ausschließlich private, nicht durch den Webserver erreichbare Ablagen mit restriktiven Dateirechten zulässig. Vor Import und Auswertung werden Paket- und Manifest-Hash geprüft.

Nach bestandenem Vergleich wird nur ein freigegebenes Konfigurationspaket dokumentiert: Modellname und Digest, Dimension, Embedding- und Chunking-Profil, Extraktionsparameter, Kandidatenzahl, Fusionsgewichtung und Mindestgüte. Die Produktion validiert das Profil erneut gegen ihren Ollama-Pool und erstellt daraus eine eigene, neue Indexgeneration. Die Evaluationsumgebung bleibt von der Produktion netzwerk- und datenseitig getrennt.

## 12. Superadmin-Konfiguration und Betrieb

Über die Superadmin-Oberfläche dürfen später aktives Modellprofil, validiertes Chunking-Profil, Queue-Zeitfenster, Pause, Parallelitätslimits, Suchgewichtung, Kandidatenzahl, freigegebene Schwellwerte, Kurzbeschreibungslänge, Funktionsschalter und manuelle Indexläufe verwaltet werden.

Nicht in die Oberfläche gehören Secrets, API-Keys, private Netzwerkadressen, beliebige Collection-Namen, ungeprüfte Dimensionen oder freie Promptausführung. Jede Änderung wird validiert und auditiert. Gefährliche Änderungen zeigen Reindexierungs- und Kapazitätsfolgen vorab an.

Der Systemadministrator erhält vor Produktivbetrieb eine Betriebsanleitung mit Installation, Netzwerk, Authentifizierung, Healthchecks, Ressourcenlimits, Modellbereitstellung auf allen Ollama-Servern, Collection-/Aliasverwaltung, Capacity Gate, Snapshot-/Restore-Test, Monitoring, Alerting, Queue-Steuerung, Rollback und vollständigem Rebuild.

## 13. Datenschutz, Beobachtbarkeit und Ausfälle

- Keine Dokumentinhalte, Prompts, Vektoren, API-Keys oder vollständigen Modellantworten in normalen Logs.
- Metriken verwenden IDs und Kategorien, keine vertraulichen Texte.
- Healthchecks unterscheiden Netzwerk, Authentifizierung, Modellverfügbarkeit, Digest, Dimension, Collection-Schema und Kapazität.
- Dashboards zeigen Queue-Tiefe, Laufzeit, Fehlerrate, Ollama-Serverzustand, Qdrant-Latenz, Collection-Größe, freien Speicher und Indexabdeckung.
- Abgebrochene Läufe bleiben fortsetzbar und hinterlassen keine als aktiv markierte Teilgeneration.
- Ein Qdrant-Ausfall beeinträchtigt nicht fachliche Schreibvorgänge. Die direkte Suche bleibt verfügbar.

## 14. Verbindlicher Umsetzungsplan

Jeder folgende Schritt endet nach Abschnitt 3 mit einem eigenen Commit.

### Schritt 0 – Vertrag und Bestandsinventar

- diesen Vertrag als neues Zielbild festhalten;
- alle Artefakte der verworfenen Vektorspeicher-Variante inventarisieren;
- fremde Arbeitsverzeichnisänderungen kennzeichnen und schützen;
- Abnahme: Vertrag enthält ausschließlich Qdrant als Vektorspeicher und ist separat committed.

### Schritt 1 – Vollständige Bereinigung der verworfenen Variante

- alte Container, Volumes, Verbindungen, Konfigurationen, Umgebungsvariablen, Migrationen, Services und Tests entfernen;
- bereits begonnene, daran gekoppelte Indexierungsimplementierung entweder neutral auf Qdrant ausrichten oder entfernen;
- Repository-Scan auf bekannte Altbezeichner und technische Annahmen durchführen;
- Framework-generische Unterstützung nur entfernen, wenn nachgewiesen ist, dass sie ausschließlich für dieses Feature eingeführt wurde;
- Abnahme: Das Kontextsuche-Modul besitzt keine ausführbaren, dokumentarischen oder testseitigen Altartefakte; Anwendung und bestehende Tests bleiben funktionsfähig.

### Schritt 2 – Qdrant-Infrastruktur und neutraler Client

- gepinnte Qdrant-Version, persistentes Volume, Healthcheck und private Netzkonfiguration hinzufügen;
- sichere Beispielkonfiguration ohne Secrets dokumentieren;
- Client-Interface, REST-Adapter, Fehlerklassen und Healthcheck implementieren;
- Collection-Schema, Payload-Indizes und Aliasverwaltung als getesteten Provisionierungsdienst anlegen;
- Abnahme: leere Collection kann reproduzierbar provisioniert, geprüft und über Alias angesprochen werden.

### Schritt 3 – Modellprofil und Ollama-Pool

- unveränderliches Embedding-Profil und Dimensionsprüfung umsetzen;
- mehrere Ollama-Server mit Lastverteilung, Failover, Limits und Circuit Breaker unterstützen;
- gemischte Modelle oder Dimensionen hart ablehnen;
- Abnahme: Parallelverteilung und Ausfall eines Servers sind getestet; kein Lauf kann gemischte Vektoren erzeugen.

### Schritt 4 – Manuelle Indexierung für PDF und Text

- Extraktion, optionalen Tesseract-Fallback, seitengebundenes Chunking und deterministische Punkt-IDs umsetzen;
- manuellen, resumierbaren CLI-/Admin-Start ohne automatische Eventkopplung bereitstellen;
- exakte Seiten- und Zeichenquellen in Payload speichern;
- Löschung und Neuindizierung einer Ressource idempotent ausführen;
- Abnahme: repräsentative PDFs und Textressourcen werden vollständig, wiederholbar und mit korrekten Quellen indiziert.

### Schritt 4a – Produktionsdatensätze für Kalibrierung und Abnahme

- lesende Inventarisierung, explizite Auswahl, Einfrieren, Export, Integritätsprüfung und isolierten Import als Artisan-Befehle bereitstellen;
- Datensätze für Kalibrierung, Abnahme, OCR, Last und Kapazität nach Zweckregeln verwalten: OCR darf Kalibrierung/Abnahme ergänzen, Last und Kapazität sind mit allen anderen Zwecken überlappbar, Kalibrierung/Abnahme bleiben exklusiv; Inhalts-/Manifest-Hashes und Auswahlzeitpunkt festhalten;
- private Materialien nur mit expliziter Einschlussoption und begründetem Audit exportieren; keine Inhalte, Titel, Originalpfade oder Secrets in Standardausgaben und Logs schreiben;
- unverschlüsselte, aber private und restriktiv berechtigte Paketablage sowie Hashprüfung vor Import dokumentieren;
- ausschließlich Konfigurationsfreigaben, niemals Produktionsvektoren oder Qdrant-Collections, von Evaluation in Produktion überführen;
- README und Betriebsanleitung für Produktions-Export, vertrauenswürdigen Transfer, isolierten Import und Rückbau der Evaluationsdaten aktualisieren;
- Abnahme: Ein eingefrorener, auch private Inhalte enthaltender Datensatz lässt sich ohne Zugriff auf Produktionsdienste in die Evaluationsumgebung importieren und seine Vollständigkeit anhand der Hashes nachweisen.

### Schritt 4b – Global-Admin-Kuratierung von Evaluationsdatensätzen

- explizites Datenmodell für Dataset-Entwürfe, Sollmengen, Quoten, Material-/Ressourcenmitgliedschaften, Versionsschutz und unveränderliche eingefrorene Snapshots anlegen;
- Zugehörigkeit als vollständige zusammenhängende Material-/Ressourcenblöcke berechnen; Mitgliedschaften pro Dataset eindeutig speichern und die gemeinsame Überlappungspolicy für OCR, Last und Kapazität anwenden;
- vorhandene eingefrorene Datensätze vor dem Mitgliedschaftsabgleich anhand dieser Zweckregeln prüfen; unerlaubte Konflikte nicht automatisch migrieren, sondern mit nachvollziehbarem Bericht blockieren;
- nur für Global Admins einen KI-Modus in die vorhandenen Material- und Ressourcenlisten integrieren: Filter, Mehrfach-/Bereichsauswahl, Vorschau der transitiven Erweiterung, konfliktfreie Übernahme, Entfernen nur im Entwurf und klare Badges;
- Tooltip, Fokusansicht und Touch-Alternative mit Ressourcen-/Materialfortschritt, Restmenge, Quoten und Textalternative zum Fortschrittsbalken umsetzen;
- API, Policies, konkurrierende Änderungen, private Inhalte, Draft/Frozen-Grenzen, Tastaturbedienung, mobile Darstellung und Vue-Produktionsbuild testen;
- README und Systemadministrationsanleitung um den ausschließlich Global-Admin-berechtigten Kuratierungsablauf erweitern;
- Abnahme: Ein Global Admin kann vollständige Blöcke nachvollziehbar zuweisen; OCR ergänzt Kalibrierung/Abnahme, Last/Kapazität dürfen alle Zwecke überlappen, Kalibrierung/Abnahme bleiben disjunkt; eingefrorene Sätze bleiben unveränderlich.

### Schritt 4c – Stabilisierung der Kontextsuche-Queues

- Den [Queue-Änderungsvertrag](context-search-queue-change-contract.md) in genau drei separat geprüften und separat committeten Schritten ohne produktive Zwischenlösung umsetzen: (1) eigene Datenbank-Queue-Connection ohne Aktivierung, (2) endgültige Worker- und Betriebsgrenzen vorbereiten, (3) seitenweise, wiederanlaufbare Verarbeitung mit revisionssicherer Qdrant-Veröffentlichung und einmaligem Cutover.
- Vor jedem Teil-Schritt seine entscheidungspflichtigen Schema-, Storage-, Queue- und Produktionsänderungen konkret freigeben lassen; keine produktiven Queue-Bestände pauschal bereinigen.
- Abnahme: kein Kontextjob kann vor seinem Timeout erneut reserviert werden; lange/defekte Dokumente blockieren nicht dauerhaft; die Default-/Preview-/Bundle-Queues bleiben unverändert; ein Crash erzeugt keinen als vollständig sichtbaren Teilindex.

### Schritt 5 – Capacity Gate und Betriebsanleitung

- repräsentativen Lasttest und Speicherprojektion durchführen;
- Warn-/Stoppschwellen und Parallelität anhand der Messung bestätigen oder zur Entscheidung vorlegen;
- Installations-, Backup-, Restore-, Rebuild-, Monitoring- und Störungsanleitung fertigstellen;
- Abnahme: Voll-Backfill ist anhand Messwerten freigegeben oder bewusst blockiert.

### Schritt 6 – Semantische Suche hinter Funktionsschalter

- Query-Embedding, Qdrant-Abfrage, Ressourcengruppierung und Quellenanzeige integrieren;
- bestehende direkte Suche unverändert verfügbar halten;
- Rechte- und Fehlerfälle testen;
- Abnahme: semantische Suche ist für ausgewählte Superadministratoren messbar, abschaltbar und quellenfest.

### Schritt 7 – Hybride Suche

- rangbasierte Fusion in die bestehende Suchzellenlogik integrieren;
- Evaluationssatz und Schwellwerte dokumentiert kalibrieren;
- Rollout und Rückbau über Funktionsschalter ermöglichen;
- Abnahme: Qualität und Latenz erfüllen die vereinbarten Ziele, direkte Treffer werden nicht verdrängt.

### Schritt 8 – Vorschläge und Kurzbeschreibungen

- KI-Vorschlagsstatus, menschliche Bestätigung, Audit und Stale-Erkennung umsetzen;
- Bibelstellen normalisieren und validieren;
- Kurzbeschreibung mit Vorrang menschlicher Texte und KI-Symbol integrieren;
- Abnahme: kein KI-Vorschlag wird ohne berechtigte menschliche Bestätigung fachlich übernommen.

### Schritt 9 – Automatisierung und Superadmin-Steuerung

- erst nach stabiler manueller Phase automatische Reindexierung über bestehende Events planen und freigeben;
- Queue-Zeitfenster, interaktive Ausnahmeberechtigung, Pause und Admin-Status umsetzen;
- Import-Backpressure und Reconciliation aktivieren;
- Abnahme: Automatisierung ist beobachtbar, pausierbar, resumierbar und beeinträchtigt die Kernanwendung nicht.

## 15. Abnahmekriterien des Gesamtprojekts

Das Projekt ist erst abgeschlossen, wenn:

- Qdrant der einzige Vektorspeicher des Kontextsuche-Moduls ist;
- alle Artefakte und Designannahmen der verworfenen Variante entfernt und per Repository-Scan geprüft sind;
- MySQL die alleinige fachliche Wahrheit bleibt und Qdrant vollständig neu aufgebaut werden kann;
- Modellwechsel über neue Collection und atomare Aliasumschaltung ohne gemischte Vektoren funktionieren;
- mehrere Ollama-Server dasselbe verifizierte Embedding-Profil verteilt bedienen und bei Erreichbarkeitsfehlern geordnet ausfallen können;
- PDF- und Texttreffer mindestens Ressource, Seite und konkrete Textstelle nennen;
- die bestehende direkte Suche sowie Suchzellenlogik erhalten sind;
- hybride Gewichtung mit einem dokumentierten Evaluationssatz kalibriert ist;
- alle KI-Vorschläge bis zur menschlichen Annahme eindeutig gekennzeichnet bleiben;
- menschliche Kurzbeschreibungen immer Vorrang haben;
- Zeitfenster, interaktive Berechtigung, Lastgrenzen und Superadmin-Zugriff serverseitig durchgesetzt werden;
- Capacity Gate, Backup-/Restore-/Rebuild-Probe und Betriebsanleitung bestanden sind;
- Kalibrierung und Abnahme ausschließlich mit getrennten, überprüfbaren Evaluationsdatensätzen erfolgen und Produktion nur freigegebene Konfigurationen übernimmt;
- jeder Umsetzungsschritt einen eigenen geprüften Commit besitzt.

## 16. Primärquellen für die Umsetzung

Bei der Umsetzung sind die jeweils aktuellen offiziellen Dokumentationen gegen die gepinnte Version zu prüfen:

- [Qdrant Collections, Vektorkonfiguration und Aliase](https://qdrant.tech/documentation/manage-data/collections/)
- [Qdrant Payload und Payload-Indizes](https://qdrant.tech/documentation/manage-data/payload/)
- [Qdrant Search und Gruppierung](https://qdrant.tech/documentation/search/search/)
- [Qdrant Hybrid Queries und Fusion](https://qdrant.tech/documentation/search/hybrid-queries/)
- [Qdrant Security](https://qdrant.tech/documentation/operations/security/)
- [Qdrant Installation](https://qdrant.tech/documentation/operations/installation/)
- [Qdrant Snapshots](https://qdrant.tech/documentation/operations/snapshots/)
- [Laravel AI SDK: Embeddings](https://laravel.com/docs/13.x/ai-sdk#embeddings)
- [Laravel AI SDK: Ollama](https://laravel.com/docs/13.x/ai-sdk#ollama)

Beispielwerte aus Dokumentationen werden nicht ungeprüft in Produktion übernommen. Maßgeblich sind die gepinnte Version, der Evaluationssatz und die Messwerte der tatsächlichen Infrastruktur.

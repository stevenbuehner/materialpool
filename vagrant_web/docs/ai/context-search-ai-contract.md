# Planungs- und Entscheidungsvertrag: Kontextsuche und KI-Funktionen

## 1. Status, Zweck und Verbindlichkeit

Dieses Dokument ist der fachliche und technische Vertrag für die geplante Kontextsuche sowie die darauf aufbauenden KI-Funktionen des Materialpools. Es hält bestätigte Entscheidungen, Empfehlungen, offene Entscheidungen, Qualitätsziele und die vorgesehene Umsetzung in Stufen fest.

> [!IMPORTANT]
> Der Vertrag beschreibt einen **Planungsstand**. PostgreSQL, `pgvector`, das Laravel AI SDK, Ollama-Anbindungen, neue Tabellen, Queues, Admin-Oberflächen und Suchänderungen sind dadurch noch nicht installiert oder implementiert. Jede Umsetzung bleibt nach `AGENTS.md` freigabe-, migrations-, sicherheits- und testpflichtig.

Maßgeblich bleiben außerdem:

- [Architektur](architecture.md), [Domänen-Invarianten](domain-invariants.md) und [Quality Gates](quality-gates.md);
- [Produktions- und Deploymentvertrag](production-deployment-contract.md);
- die bestehenden API-Verträge `v1` und `v2` sowie die serverseitigen Policies;
- die vorhandene Suchzellen-Semantik: Suchzeilen werden mit `AND`, Begriffe innerhalb einer Zeile mit `OR` verknüpft.

## 2. Beschlossene Produkt- und Architekturentscheidungen

| Thema | Verbindliche Entscheidung |
| --- | --- |
| Primäre Datenbank | MySQL bleibt führende Datenbank und alleinige fachliche Wahrheit. |
| Suchindex | Eine separate PostgreSQL-Datenbank mit `pgvector` wird als abgeleiteter, vollständig neu aufbaubarer Suchindex verwendet. |
| Ergebnisobjekt | Primäres Suchergebnis ist ein Material. Darunter werden passende Ressourcen und exakte Fundstellen angezeigt. |
| Suchlogik | Die bestehende Suchzellen-Logik bleibt erhalten. Klassische und semantische Treffer werden innerhalb dieser Logik hybrid gewichtet. |
| Dokumentumfang | In der ersten Ausbaustufe werden PDF-Dateien und vorhandene Textressourcen berücksichtigt. Bücher werden wie PDFs behandelt. |
| Ausgeklammert | URLs, Bilder, Audio und Video werden vorerst nicht inhaltlich indiziert. URL-Crawling ist eine spätere, eigene Ausbaustufe; Audio und Video bleiben zunächst außen vor. |
| Sprachen | Deutsch und Englisch werden unterstützt. |
| KI-Anbindung | Embeddings und generative Aufgaben laufen über eine extern gehostete Ollama-Instanz im selben lokalen Netzwerk. |
| Modellwechsel | Modell, Dimension, Modell-Digest, Chunking-Version und Indexgeneration werden versioniert. Ein Modellwechsel überschreibt keinen aktiven Index. |
| KI-Vorschläge | Schlagwort- und Bibelstellen-Vorschläge sind sichtbar als KI-Vorschläge markiert und werden erst durch menschliche Bestätigung übernommen. |
| Neue Schlagworte | Die KI darf neue Schlagworte vorschlagen. Angelegt werden sie erst bei menschlicher Annahme und nach Dublettenprüfung. |
| Übernommene Vorschläge | Nach Annahme verhalten sich Schlagwort oder Bibelstelle wie ein regulärer menschlich bestätigter Eintrag und tragen in der normalen Oberfläche kein KI-Badge mehr. Für Nachvollziehbarkeit bleibt intern ein Audit-Nachweis empfohlen. |
| Kurzbeschreibung | KI-Kurzbeschreibungen werden separat gespeichert. Sie ersetzen keine menschliche Beschreibung. Nur wenn keine menschliche Beschreibung vorhanden ist, wird die KI-Fassung in Suchergebnissen mit kleinem KI-Symbol verwendet. |
| Queue-Zeitfenster | Für KI-Background-Jobs gibt es ein optionales erlaubtes Ausführungszeitfenster. Außerhalb des Fensters bleiben Jobs erhalten und werden später abgearbeitet. |
| Interaktive KI-Jobs | Berechtigte Benutzer dürfen interaktive KI-Jobs auch außerhalb des Background-Zeitfensters starten. Das Recht wird serverseitig geprüft; die Parallelität bleibt begrenzt. |
| Administration | Betriebs- und Modellparameter werden über eine ausschließlich für Superadministratoren zugängliche Admin-Oberfläche verwaltet. Secrets und Netzwerkendpunkte bleiben Serverkonfiguration. |
| PostgreSQL-Betrieb | PostgreSQL läuft auf demselben Server wie die Webanwendung. Der Server besitzt 2 CPUs, 4 GB RAM und nach aktueller Angabe 40 GB verfügbare SSD-Kapazität. |
| Suchtext in PostgreSQL | Abgeleiteter Seiten- und Chunkvolltext darf und soll für Volltextsuche, Snippets und Quellen-Locators in PostgreSQL gespeichert werden. Er bleibt vollständig neu aufbaubar. |
| OCR | Tesseract darf als neue Serverabhängigkeit für einen seitenweisen OCR-Fallback vorgesehen werden. |
| Länge der Kurzbeschreibung | Ziel sind zwei bis drei Sätze mit einer harten Ausgabegrenze von ungefähr 300 Zeichen. |

## 3. Erwartete Größenordnung und Kapazitätsannahmen

Die Planung muss mindestens folgende Last tragen:

- insgesamt ungefähr 100.000 bis 200.000 Ressourcen und Materialien;
- ungefähr 100 PDFs mit je rund 100 Seiten;
- ungefähr 30.000 PDFs mit je 2 bis 7 Seiten;
- damit grob 70.000 bis 220.000 PDF-Seiten vor OCR- oder Versionsduplikaten;
- im Normalbetrieb 1 bis 10 neue Ressourcen pro Tag;
- bei Bundle-Importen oder -Aktualisierungen Lastspitzen von mehreren Tausend Ressourcen.

Bei durchschnittlich ein bis drei Chunks pro PDF-Seite entstehen allein für PDFs voraussichtlich etwa 70.000 bis 660.000 Chunks. Mit Textressourcen, Überlappung, Revisionen und Wachstum soll die erste technische Auslegung mindestens **1,5 Millionen aktive Chunks** ohne Architekturwechsel verkraften. Alte Indexgenerationen werden getrennt gerechnet und dürfen nicht unbegrenzt erhalten bleiben.

Die Ollama-Hardware ist zunächst mit 4 CPU-Kernen, 8 GB RAM und begrenzter GPU-Leistung angesetzt. Daraus folgt als sicherer Startwert:

- höchstens ein gleichzeitig laufender generativer Job;
- Embedding-Batches klein und konfigurierbar beginnen;
- Embedding- und generative Last zunächst nicht parallel erzwingen;
- Timeouts, Wiederholungen und Durchsatz messen, nicht anhand theoretischer Modellwerte festlegen;
- große Modelle nur nach einem realen Qualitäts- und Lasttest freigeben.

PostgreSQL teilt sich den Webserver mit der Anwendung und den vorhandenen Serverdiensten. Der Host besitzt 2 CPUs, 4 GB RAM und nach aktueller Angabe 40 GB verfügbare SSD-Kapazität. Diese Ausstattung ist für einen Pilotbetrieb und den normalen täglichen Zuwachs grundsätzlich nutzbar, aber für den angenommenen Maximalausbau ein klarer Engpass:

- Ein `vector(768)` benötigt nach der pgvector-Speicherformel ungefähr 3.080 Byte pro Vektor. Bei 660.000 Chunks sind das rund 1,9 GiB, bei 1,5 Millionen Chunks rund 4,3 GiB allein für die unkomprimierten Vektorwerte.
- Hinzu kommen HNSW-, GIN- und relationale Indizes, Volltexte, Tabellenmetadaten, PostgreSQL-Caches sowie der Speicherbedarf von Webanwendung, PHP-FPM, MySQL und Betriebssystem.
- Der aktive Suchbestand darf größer als der Arbeitsspeicher sein und von SSD gelesen werden; Suchlatenz und Indexaufbau werden dann jedoch stärker von I/O und Cache-Verdrängung abhängig.
- Zwei vollständige Indexgenerationen und ein paralleler HNSW-Aufbau können den Host deutlich überlasten, auch wenn der SSD-Speicher ausreicht.
- 40 GB reichen voraussichtlich für eine aktive Generation im erwarteten Anfangsumfang. Für 1,5 Millionen Chunks, eine vollständige zweite Generation, HNSW-/GIN-Indizes, PostgreSQL-WAL, temporäre Indexdateien und die übrigen Serverdaten ist diese Kapazität ohne Messung nicht als ausreichend bestätigt.

Folgerung: 4 GB RAM und 40 GB SSD werden als **Pilot- und Startkonfiguration, nicht als bestätigte Zielkapazität** dokumentiert. Vor dem vollständigen Backfill ist ein Capacity Gate verbindlich. Werden die Latenz- oder Speicherziele verfehlt, ist eine RAM-Erweiterung auf mindestens 8 GB die bevorzugte erste Maßnahme; 16 GB oder ein separater PostgreSQL-Host bieten mehr Reserve für Modellwechsel mit zwei Generationen. Reicht der Datenträger nicht für aktive und neue Generation einschließlich Sicherheitsreserve, wird die SSD erweitert oder der Index ausgelagert. Alternativ dürfen `halfvec`, niedrigere Embedding-Dimensionen oder IVFFlat nur nach Qualitätsvergleich eingesetzt werden.

Für die 40-GB-SSD gilt folgender Betriebsvertrag:

- mindestens 20 % beziehungsweise 8 GB, maßgeblich ist der größere Wert, bleiben als freie Notfallreserve erhalten;
- vor Backfill, Modellwechsel und Indexaufbau werden erwartete Tabellen-, Index-, WAL- und temporäre Datenmengen gemeinsam geschätzt;
- aktive Generation, aufzubauende Generation und erwartete temporäre Spitzen dürfen die verfügbare Kapazität abzüglich Reserve nicht überschreiten;
- unterschreitet der freie Speicher eine Warnschwelle von 12 GB, werden Superadministratoren gewarnt und neue Massenjobs nicht mehr begonnen;
- unterschreitet er die harte Schwelle von 8 GB, pausieren AI-Indexierungs-, OCR- und Generationswechsel-Jobs automatisch; normale Webanfragen und lesende Suche bleiben priorisiert;
- PostgreSQL-WAL, Logs, fehlgeschlagene Jobs und alte Generationen erhalten definierte Aufbewahrungsgrenzen;
- Backups werden nicht dauerhaft auf derselben 40-GB-SSD abgelegt.

Die Schwellen sind konservative Startwerte und werden nach dem Spike nur dann geändert, wenn Messwerte und der verbleibende Platzbedarf der übrigen Serverdienste dies belegen.

## 4. Zielarchitektur

```text
MySQL (fachliche Wahrheit)
  Materialien, Ressourcen, Beziehungen, Rechte, menschliche Metadaten
          |
          | Events / resumierbare Jobs / Reconciliation
          v
Extraktion und Chunking
  PDF-Text, OCR-Fallback, Seiten- und Positionsmetadaten
          |
          +---------------------> Ollama im lokalen Netz
          |                         Embeddings / Vorschläge / Kurztexte
          v
PostgreSQL + pgvector (abgeleiteter Suchindex)
  Dokumentrevisionen, Seiten, Chunks, Volltextindex, Vektoren,
  Material-Ressourcen-Projektion und Indexgenerationen
          |
          v
Hybride Suche
  strukturierte Filter + Volltext + Vektorähnlichkeit + Re-Ranking
          |
          v
abschließende Autorisierungsprüfung in MySQL
          |
          v
Materialtreffer mit Ressource, Seite und Fundstelle
```

### 4.1 Verantwortungsgrenzen

- **MySQL** speichert alle fachlichen Beziehungen, Freigaben, menschlichen Inhalte, angenommenen Vorschläge, KI-Entwürfe und deren Auditdaten.
- **PostgreSQL** enthält ausschließlich abgeleitete Suchdaten. Ein Verlust muss durch Reindexierung heilbar sein.
- **Ollama** verarbeitet Text, ist aber kein dauerhaftes Datenlager.
- **Laravel** orchestriert Extraktion, Rechte, Jobs, Modellprofile, Suchfusion und Präsentation.
- **Der Browser** entscheidet niemals über Leserechte oder die Annahme eines Vorschlags.

### 4.2 Konsistenzmodell

Der Index ist bewusst eventual consistent. Fachliche Schreibvorgänge in MySQL dürfen nicht von Ollama oder PostgreSQL abhängig sein. Nach Änderungen werden idempotente Indexjobs ausgelöst. Zusätzlich läuft regelmäßig ein Reconciliation-Prozess, der fehlende, veraltete oder verwaiste Indexeinträge anhand von Revisions- und Inhalts-Hashes erkennt.

Ein Indexjob muss gefahrlos wiederholbar sein. Mindestens folgende Ursachen erzeugen eine neue Revision:

- Dateiinhalt oder Textinhalt geändert;
- Extraktionsverfahren oder OCR-Version geändert;
- Chunking-Profil geändert;
- Embedding-Modell oder relevante Modellparameter geändert;
- Zuordnung zwischen Material und Ressource geändert;
- suchrelevante Sichtbarkeit geändert.

## 5. PostgreSQL- und pgvector-Konzept

### 5.1 Eigene Laravel-Verbindung

PostgreSQL wird als separate Laravel-Datenbankverbindung geführt, beispielsweise `context_search`. Sie ist nicht die Default-Verbindung. Fachliche Eloquent-Modelle dürfen nicht unbemerkt auf diese Verbindung wechseln. Cross-Database-Foreign-Keys sind nicht möglich; referenziert werden stabile MySQL-IDs und zusätzlich Inhalts- beziehungsweise Revisions-Hashes.

### 5.2 Empfohlenes logisches Schema

Die endgültigen Namen werden erst mit den Migrationen festgelegt. Fachlich werden mindestens folgende Strukturen benötigt:

| Struktur | Zweck |
| --- | --- |
| `index_generations` | Modellprofil, exakter Modellbezeichner und Digest, Vektordimension, Chunking-Version, Status, Aktivierungszeitpunkt. |
| `indexed_documents` | Ressource, Typ, aktuelle Inhaltsrevision, Sprache, Extraktionsstatus, Fehlerzustand und Zeitstempel. |
| `document_pages` | PDF-Seite, bereinigter Seitentext, Extraktionsart, optionale OCR-Güte und Layoutmetadaten. |
| `document_chunks` | Chunktext, Seitenbereich, Zeichen-/Tokenbereich, Überschriftenpfad, Locator, Sprachcode, Volltextvektor und Embedding. |
| `material_resource_projection` | Abgeleitete Zuordnung der Chunks/Ressourcen zu Materialien und notwendige Sichtbarkeitsmerkmale für effiziente Kandidatenauswahl. |
| `index_failures` oder Job-Audit | Technisch verwertbare Fehlerkategorie, Versuchszahl und Revisionsbezug; keine Secrets und kein unnötiger Dokumentinhalt. |

Empfehlung: Das Embedding wird pro Chunk nur einmal gespeichert. Materialzuordnungen referenzieren den Chunk relational, statt denselben Vektor für jedes Material zu duplizieren. Ob der HNSW-Index mit dieser Filterung die Latenzziele erreicht, ist in einem Spike mit realistischen Rechte- und Zuordnungsdaten nachzuweisen. Falls nicht, darf erst nach einer dokumentierten Messung eine stärker denormalisierte Projektion erwogen werden.

### 5.3 Einschätzung zur Volltextspeicherung

Die Speicherung des abgeleiteten Seiten- und Chunkvolltexts in PostgreSQL wird empfohlen. Sie ist nicht nur zulässig, sondern für die geplante Hybrid Search fachlich sinnvoll:

- exakte Wörter, Phrasen und Schreibweisen können über PostgreSQL Full Text Search gefunden werden;
- `ts_headline` beziehungsweise eine kontrollierte eigene Snippet-Erzeugung kann den tatsächlichen Fundtext anzeigen;
- Seite, Zeichenbereich und Bounding Boxes bleiben mit demselben Indexstand verbunden;
- lexikalische und semantische Kandidaten können in einer Datenbank fusioniert werden, ohne Volltexte pro Treffer aus MySQL nachzuladen;
- Reindexierung und Rechteprojektion bleiben unabhängig von der fachlichen MySQL-Struktur.

Die empfohlene Speichereinteilung lautet:

1. `document_pages` speichert den normalisierten Seitentext einmal je Dokumentrevision.
2. `document_chunks` speichert den exakt eingebetteten und lexikalisch indizierten Chunktext. Diese begrenzte Doppelung ist erwünscht, weil sie Reproduzierbarkeit, Snippets und schnelle Rechecks ermöglicht.
3. Ein sprachabhängiger `tsvector` wird gespeichert beziehungsweise deterministisch erzeugt und mit GIN indiziert. Die Textkonfiguration wird explizit pro Zeile geführt, damit Deutsch, Englisch und `simple` reproduzierbar bleiben.
4. Original-PDFs werden nicht in PostgreSQL dupliziert. Sie verbleiben auf dem bestehenden Storage.
5. Alte Dokumentrevisionen und aus dem Rückrollfenster gefallene Indexgenerationen werden kontrolliert entfernt, damit Text- und Vektordaten nicht unbegrenzt anwachsen.

Der Volltext ist voraussichtlich nicht der größte Speicherverbraucher; Vektoren und HNSW dominieren bei hohen Dimensionen. Dennoch müssen GIN-Größe, Tabellenwachstum und SSD-Reserve im Capacity Gate gemessen werden. Die Empfehlung ist daher **Volltext speichern, aber nur abgeleitet, normalisiert, revisioniert und ohne unnötige Originaldatei-Duplikate**.

### 5.4 Indexstrategie

Als Startpunkt wird HNSW empfohlen. Es bietet bei Suchabfragen üblicherweise ein besseres Verhältnis aus Geschwindigkeit und Trefferquote als IVFFlat, benötigt aber mehr Speicher und längere Indexerstellung. Für große Erstimporte gilt:

1. Daten in kontrollierten Batches laden;
2. Indexaufbau und Speicherverbrauch messen;
3. HNSW-Parameter nicht blind aus Beispielen übernehmen;
4. produktive Indexneuanlage möglichst parallel beziehungsweise generationenweise durchführen;
5. Recall gegen eine exakte Suche auf einer repräsentativen Stichprobe messen.

Die Vektordimension ist Bestandteil der Indexgeneration. `vector(N)` darf erst festgelegt werden, nachdem das konkrete Embedding-Modell und dessen Dimension bestätigt sind. Ein Dimensionswechsel erfolgt über eine neue Generation beziehungsweise neue physische Spalte/Tabelle, nicht durch Umdeutung vorhandener Vektoren.

`halfvec` ist eine mögliche spätere Speicheroptimierung, aber nicht der Startwert. Die geringere Präzision muss gegen einen kuratierten Evaluationssatz geprüft werden.

Auf dem gemeinsam genutzten 4-GB-Host gelten zunächst konservative Testwerte, die im Spike anhand des gesamten Servers angepasst werden:

- `shared_buffers` nicht nach der Faustregel für einen dedizierten Datenbankserver dimensionieren, sondern zunächst nur ungefähr 256 bis 512 MB vorsehen;
- `work_mem` klein beginnen, beispielsweise 4 bis 8 MB, weil es pro Operation und Worker mehrfach anfallen kann;
- `maintenance_work_mem` für Indexaufbauten begrenzen und nur im Wartungsfenster erhöhen;
- höchstens einen parallelen Maintenance-Worker und einen Indexierungsjob verwenden;
- initiale Massendaten vor dem HNSW-Aufbau laden;
- Suchpläne mit `EXPLAIN (ANALYZE, BUFFERS)`, Cache-Treffer, Swap-/OOM-Ereignisse und Weblatenz messen.

Diese Werte sind keine produktive Konfiguration, sondern sichere Startpunkte für Stufe 0. Ein HNSW-Aufbau darf niemals durch ein so hohes `maintenance_work_mem` beschleunigt werden, dass Webanwendung, MySQL oder Betriebssystem unter Speicherdruck geraten.

### 5.5 Modellwechsel ohne Ausfall

Für jede Indexgeneration gelten die Zustände `draft`, `building`, `validating`, `active`, `retired` und `failed`. Zu einem Zeitpunkt ist genau eine Generation aktiv.

1. Neues Modellprofil als Entwurf anlegen.
2. Neue Generation neben dem aktiven Index in begrenzten Batches aufbauen. Auf dem 4-GB-Host erfolgt der HNSW-Aufbau ausschließlich in einem Wartungsfenster und unter Capacity-Monitoring.
3. Vollständigkeit, Recall, Latenz, Sprache und Rechtefilter prüfen.
4. Aktivierung atomar über eine Generation-ID umschalten.
5. Vorherige Generation für ein begrenztes Rückrollfenster behalten.
6. Erst danach kontrolliert und dokumentiert löschen.

Queries und Dokumente müssen immer mit demselben Embedding-Modell beziehungsweise derselben Generation verarbeitet werden. Eine dimensionsgleiche Modelländerung ist trotzdem inkompatibel und verlangt eine neue Generation.

Auf der 40-GB-SSD darf eine zweite vollständige Generation nur begonnen werden, wenn die Vorabschätzung einschließlich WAL, temporärem Indexaufbau und 8-GB-Reserve passt. Andernfalls wird nicht in-place umgebaut. Bevorzugt wird die SSD vorübergehend oder dauerhaft erweitert beziehungsweise PostgreSQL ausgelagert; ein riskanter Modellwechsel durch Überschreiben des aktiven Index ist ausgeschlossen.

## 6. Extraktion, OCR und Quellenangaben

### 6.1 Dokumenttypen der ersten Stufe

- PDF-Ressourcen, einschließlich als `Book` geführter PDFs;
- bereits als Text gespeicherte Ressourcen;
- Office-Dokumente nur über den bereits vorhandenen, kontrollierten PDF-Konvertierungspfad, sofern sie fachlich in den freigegebenen Umfang aufgenommen werden;
- keine Inhaltsindizierung von URLs, Bildern, Audio oder Video.

Dateianhang, MIME-Type und tatsächlich erkanntes Format müssen vor Verarbeitung plausibilisiert werden. Fehlerhafte oder verschlüsselte PDFs werden als nicht indizierbar protokolliert, ohne den Import abzubrechen.

### 6.2 Textgewinnung

Die Extraktion arbeitet stufenweise:

1. nativen PDF-Text seitenweise extrahieren;
2. Textqualität prüfen, beispielsweise Zeichenmenge, Anteil unbekannter Zeichen, Wortstruktur und leere Seiten;
3. nur bei unzureichendem Ergebnis OCR auslösen;
4. Sprache Deutsch, Englisch oder gemischt erkennen beziehungsweise aus Metadaten übernehmen;
5. Text normalisieren, aber Originalpositionen auf den extrahierten Text rückführbar halten.

Für genaue Fundstellen wird eine layoutbewahrende Extraktion benötigt. Popplers Bounding-Box-Ausgabe oder bei OCR hOCR/TSV liefern Wortpositionen. Der Index muss nicht jede Koordinate in eine eigene Zeile auflösen; ein kompaktes Locator-JSON pro Chunk genügt, sofern daraus Seite und markierbarer Ausschnitt reproduzierbar sind.

Tesseract ist als OCR-Engine freigegeben. Es verarbeitet nur Seiten, deren nativer Text die Qualitätsprüfung nicht besteht. PDF-Seiten werden kontrolliert gerendert und mit den Sprachpaketen Deutsch und Englisch verarbeitet; bei unbekannter Sprache darf ein kombiniertes Profil verwendet werden. Tesseract-Jobs laufen mit Parallelität 1 im Background-Zeitfenster. Sie gehören nicht zum außerhalb des Zeitfensters erlaubten interaktiven KI-Pfad. OCRmyPDF ist für die erste Stufe nicht erforderlich, weil keine neue PDF-Datei erzeugt oder das persistente Original verändert werden soll.

### 6.3 Verbindlicher Quellen-Locator

Jeder Chunk benötigt mindestens:

- Ressourcen-ID und Inhaltsrevision;
- Originaldateiname beziehungsweise stabilen Anzeigenamen;
- PDF-Seite, 1-basiert;
- Seitenbereich, falls ein Chunk mehrere Seiten berühren sollte; empfohlen ist jedoch, Seitengrenzen nicht zu überschreiten;
- Zeichenbereich im normalisierten Seitentext;
- optional Bounding Box oder mehrere Rechtecke;
- einen kurzen, aus dem Chunk gewonnenen Treffer-Ausschnitt;
- Extraktionsart `native_text` oder `ocr` und optional Vertrauenswert.

Die GUI zeigt mindestens: Materialtitel, Ressourcenname, Seite und hervorgehobenen Ausschnitt. Wenn Koordinaten zuverlässig vorliegen, öffnet die Vorschau direkt auf der Seite und markiert die Stelle. Falls nur Seitentext vorliegt, bleibt die Seitenangabe die garantierte Untergrenze.

Quellenangaben dürfen niemals von einem generativen Modell erfunden werden. Sie entstehen ausschließlich aus den gespeicherten Locator-Daten des tatsächlich gefundenen Chunks.

## 7. Chunking-Vertrag

### 7.1 Empfohlener Startwert

- Zielgröße: 300 bis 450 Tokens;
- Überlappung: 50 bis 80 Tokens;
- harte Obergrenze: modellabhängig, deutlich unter dem Embedding-Limit;
- keine Überschreitung von PDF-Seitengrenzen;
- Absätze, Listen, Zwischenüberschriften und Satzgrenzen bevorzugen;
- sehr kurze benachbarte Absätze zusammenführen;
- Tabellen, Fußnoten und Kopf-/Fußzeilen gesondert behandeln.

Dies sind Startwerte, keine unveränderlichen Wahrheiten. Das Chunking-Profil wird versioniert und anhand eines Evaluationssatzes tariert.

### 7.2 Strukturbewusstsein

Vor dem rein tokenbasierten Schnitt werden Dokumentstrukturen verwendet:

1. Seite;
2. Überschrift und Abschnitt;
3. Absatz oder Liste;
4. Satzgrenze;
5. erst zuletzt Token-Obergrenze.

Wiederkehrende Kopf- und Fußzeilen sollen dokumentweit erkannt und aus dem Suchtext entfernt werden, bleiben aber bei Bedarf in der Roh-Extraktion nachvollziehbar. OCR-Trennstriche, Ligaturen und Zeilenumbrüche werden normalisiert. Bibelstellen-Schreibweisen dürfen dabei nicht zerstört werden.

### 7.3 Kleine Dokumente

Sehr kurze Texte erhalten einen einzigen Chunk. Zusätzlich kann ein Dokument- beziehungsweise Ressourcen-Embedding erzeugt werden, wenn Tests zeigen, dass dies die Materialaggregation verbessert. Es ist nicht zwingend für die erste Stufe.

## 8. Hybride Suche und Ranking

### 8.1 Suchbestandteile

Die Ergebnisbildung kombiniert:

- vorhandene strukturierte Filter für Schlagworte, Bibelstellen, Typen und weitere Suchzellen;
- lexikalische Volltextsuche in PostgreSQL für exakte Wörter und Phrasen;
- semantische Vektorsuche über Chunk-Embeddings;
- vorhandene Titel- und fachliche Relevanzsignale;
- Materialaggregation aus den besten zugeordneten Ressourcen-Chunks.

PostgreSQL erhält hierfür neben dem Vektor einen `tsvector`-basierten Volltextindex. Deutsch und Englisch müssen sprachabhängig behandelt werden; bei unklaren oder gemischten Inhalten ist eine robuste `simple`-Repräsentation als zusätzlicher Kanal sinnvoll.

### 8.2 Erhalt der Suchzellen-Semantik

Jede Suchzeile bleibt eine eigenständige Bedingung:

- Begriffe innerhalb einer Zeile bilden Alternativen (`OR`);
- mehrere Zeilen müssen gemeinsam erfüllt sein (`AND`);
- strukturierte Suchzellen bleiben strukturierte Filter und werden nicht allein durch semantische Ähnlichkeit ersetzt;
- eine freie Textzelle kann sowohl lexikalische als auch semantische Kandidaten liefern;
- unklare semantische Treffer dürfen keine zwingende fachliche Filterbedingung umgehen.

### 8.3 Ranking-Empfehlung

Für den Start wird keine einfache Addition unkalibrierter Rohwerte empfohlen. Volltext-Rang und Cosine Similarity haben unterschiedliche Skalen. Robuster ist eine gewichtete Rank Fusion, beispielsweise Reciprocal Rank Fusion, ergänzt um fachliche Boosts.

Vorgeschlagene Startgewichtung für die Evaluation, noch nicht produktiv beschlossen:

- 45 % lexikalischer Inhaltstreffer;
- 35 % semantischer Chunktreffer;
- 10 % Titel beziehungsweise exakte Phrase;
- 10 % vorhandene fachliche Relevanzsignale.

Strukturierte Filter sind überwiegend Zulassungsbedingungen, keine bloßen Punkte. Pro Material sollen nicht beliebig viele ähnliche Chunks das Ranking dominieren; empfohlen werden der beste Chunk plus ein stark abgewerteter Beitrag weniger weiterer Chunks aus unterschiedlichen Ressourcen.

Ein Cosine-Wert ist keine Trefferwahrscheinlichkeit. Die GUI darf ihn nicht als Prozentwert ausgeben. Schwellenwerte werden getrennt nach Modellprofil, Sprache und Suchart aus realen Bewertungen ermittelt.

### 8.4 Rechtefilter

Die PostgreSQL-Projektion darf Sichtbarkeitsmerkmale enthalten, um unzulässige Kandidaten früh auszusortieren. Sie ist jedoch nie die letzte Autorität. Vor Ausgabe werden Materialien und Ressourcen mit den bestehenden Laravel-Policies beziehungsweise Scopes in MySQL geprüft.

Da nachträgliches Filtern die Trefferzahl und HNSW-Nutzung beeinträchtigen kann, muss ein Lasttest eine realistische Mischung aus privaten, öffentlichen und Bundle-basierten Daten enthalten. Kandidaten werden kontrolliert überabgerufen beziehungsweise iterativ erweitert, bis genügend autorisierte Ergebnisse oder ein festgelegtes Limit erreicht ist.

## 9. Ollama und Laravel AI SDK

### 9.1 Einsatz des SDK

Für die spätere Umsetzung wird das offizielle Laravel AI SDK bevorzugt, sofern der Implementierungs-Spike die benötigten Ollama-Funktionen, Fehlerbehandlung und Testbarkeit bestätigt. Es bietet eine Laravel-konforme Abstraktion für strukturierte Ausgaben, Queueing, Embeddings und Fakes. Die Dependency wird erst im Umsetzungsarbeitspaket und nach ausdrücklicher Freigabe installiert. Diese Freigabe liegt für Stufe 0 als Option A vor; installiert ist `laravel/ai` in Version `^0.11.2`. Die package-eigene Conversation-Migration wird nicht veröffentlicht, weil diese Stufe keine Agenten-Konversationen speichert.

Die aktuelle Laravel-13-Dokumentation weist Ollama sowohl für Textaufgaben als auch für Embeddings als unterstützten Provider aus. Diese native Anbindung ist der bevorzugte Weg. In Stufe 0 werden dennoch Dimension, Batchverhalten, Fehlerfälle und Timeouts gegen die konkret installierte SDK-, Ollama- und Modellversion getestet. Ein benannter `openai-compatible`-Provider ist die erste Rückfalloption, sofern der Ollama-Endpoint die erwarteten Request- und Response-Strukturen erfüllt; ein eigener Adapter ist erst die letzte Option.

Die Ollama-Verbindung wird über Serverkonfiguration hergestellt. Endpoint, Zugangsschutz und etwaige Tokens gehören nicht in die Admin-Datenbank oder ins Repository.

### 9.2 Modellprofile

Ein Modellprofil enthält mindestens:

- Aufgabe: `embedding`, `keyword_suggestion`, `bible_reference_suggestion`, `summary`;
- Provider `ollama`;
- Modellname, Tag und nach Möglichkeit unveränderlicher Digest;
- erwartete Vektordimension bei Embeddings;
- Timeout, Retry-Strategie und Batchgröße;
- Generierungsparameter wie Temperatur und maximale Ausgabe;
- Prompt-/Schema-Version;
- Status `draft`, `active` oder `retired`;
- Zeitpunkt und Benutzer der Aktivierung.

Ein frei beweglicher Tag wie `latest` genügt nicht für Reproduzierbarkeit. Vor Aktivierung wird der auf Ollama tatsächlich geladene Digest erfasst.

### 9.3 Modellauswahl

Der Begriff „Gemma 4“ wird nicht als festes Modell in den Vertrag geschrieben, weil Modellnamen und lokale Hardwareunterstützung überprüft werden müssen. Für Embeddings ist ein spezialisiertes Embedding-Modell sinnvoller als ein generatives Gemma-Modell. Als Kandidaten für einen lokalen Vergleich kommen insbesondere die von Ollama dokumentierten Embedding-Modelle `embeddinggemma`, `qwen3-embedding` und `all-minilm` infrage.

Für Zusammenfassungen und strukturierte Vorschläge wird ein separates, auf der vorhandenen Hardware lauffähiges Instruction-Modell ausgewählt. Die endgültige Wahl erfolgt über Qualitätsmessung, Laufzeit, RAM-/VRAM-Bedarf und deutsch-englische Eignung.

## 10. KI-Vorschläge für Schlagworte und Bibelstellen

### 10.1 Zustandsmodell

Vorschläge werden nicht direkt in die bestehenden fachlichen Beziehungen geschrieben. Ein Vorschlag hat mindestens:

- Material- und gegebenenfalls Ressourcenbezug;
- Typ `keyword` oder `bible_reference`;
- Verweis auf einen vorhandenen Datensatz oder normalisierten Vorschlag für einen neuen Begriff;
- Modell-, Prompt- und Dokumentrevision;
- Konfidenz beziehungsweise Rankingwert;
- begründende Quellen-Chunks;
- Status `pending`, `accepted`, `rejected`, `superseded` oder `failed`;
- Entscheider und Entscheidungszeitpunkt.

Nur `accepted` erzeugt oder ergänzt die reguläre fachliche Beziehung. Die Übernahme läuft in einer Transaktion und prüft vorher erneut Dubletten, Gültigkeit und Berechtigung.

### 10.2 Schlagworte

- Zuerst werden vorhandene Schlagworte semantisch und lexikalisch abgeglichen.
- Neue Schlagworte sind erlaubt, werden aber eindeutig als „neu vorgeschlagen“ angezeigt.
- Vor Annahme werden Schreibvarianten, Synonyme und Hierarchie-Dubletten geprüft.
- Die vorgeschlagene Relevanz ist editierbar und wird erst mit der Annahme fachlich wirksam.
- Das Ablehnen eines Vorschlags löscht nicht zwingend den Auditdatensatz, verhindert aber dessen Anzeige als offen.

### 10.3 Bibelstellen

- Ausgabe erfolgt als strikt strukturiertes Schema, nicht als frei interpretierter Text.
- Buch, Kapitel und Versbereich werden gegen die vorhandene Bibelstellen-Domäne validiert.
- Inhaltliche Ähnlichkeit und explizite Nennung sind getrennte Signale.
- Die GUI zeigt die begründenden Textstellen, damit Menschen den Vorschlag prüfen können.
- Ungültige oder nicht eindeutig normalisierbare Angaben werden nicht als Vorschlag angeboten.

### 10.4 Menschliche Annahme und Herkunft

Nach Annahme wird der Eintrag in der normalen Nutzung vollständig wie ein menschlich gepflegter Eintrag behandelt. Das KI-Badge verschwindet dort. Empfohlen bleibt ein interner, nur administrativ sichtbarer Auditverweis auf den Ursprung, weil dies Fehleranalyse, Rückruf eines fehlerhaften Modells und Compliance erleichtert. Diese Auditspur darf die fachliche Gleichstellung des angenommenen Eintrags nicht verändern.

## 11. KI-Kurzbeschreibungen

Eine KI-Kurzbeschreibung wird separat von der menschlichen Beschreibung gespeichert und versioniert. Sie enthält Modell-/Promptversion, Quellrevisionen, Erstellungsstatus und optional die verwendeten Ressourcen.

Darstellungsregel:

1. Ist eine nichtleere menschliche Kurzbeschreibung vorhanden, wird ausschließlich diese verwendet.
2. Andernfalls darf die aktive KI-Kurzbeschreibung angezeigt werden.
3. Die KI-Fassung trägt ein kleines, barrierefrei beschriftetes KI-Symbol.
4. Eine fehlgeschlagene oder veraltete KI-Fassung erzeugt keinen leeren Ersatz und überschreibt nichts.

Die Zusammenfassung eines Materials berücksichtigt mehrere Ressourcen. Um das Kontextfenster klein und die Quellen nachvollziehbar zu halten, wird eine hierarchische Strategie empfohlen: zunächst relevante Chunks pro Ressource verdichten, anschließend eine Materialzusammenfassung aus diesen belegten Teilergebnissen erstellen. Die Zusammenfassung darf keine Quelle behaupten, die nicht in den verwendeten Chunks vorkommt.

Konfigurierbar werden mindestens Ziellänge, Sprache, Modellprofil und automatische beziehungsweise manuelle Auslösung. Verbindlicher Startwert für Suchkarten sind zwei bis drei Sätze mit einer harten Grenze von ungefähr 300 Zeichen. Die Eingabeaufforderung nennt sowohl Satz- als auch Zeichenziel; die Anwendung validiert und begrenzt die gespeicherte Ausgabe unabhängig vom Modell.

## 12. Queue-, Last- und Zeitfensterkonzept

### 12.1 Getrennte Aufgabenklassen

Empfohlen werden logisch getrennte Queues oder eindeutig getrennte Jobklassen:

- `ai-extract`: PDF-Text, OCR und Locator-Erzeugung;
- `ai-embed`: Embedding-Batches und Indexschreiben;
- `ai-generate`: Schlagworte, Bibelstellen und Kurzbeschreibungen;
- `ai-interactive`: optional für ausdrücklich durch Benutzer ausgelöste Vorschauen.

Bestehende Default- und Preview-Queues dürfen durch einen großen Reindex nicht blockiert werden. Alle Jobs müssen idempotent, revisioniert, abbrechbar und nach einem Worker-Neustart fortsetzbar sein.

### 12.2 Bedeutung des optionalen Zeitfensters

Das Zeitfenster ist ein **erlaubtes Verarbeitungsfenster**, beispielsweise täglich von 03:00 bis 04:00 Uhr:

- Ist kein Fenster konfiguriert, dürfen AI-Background-Jobs jederzeit laufen.
- Ist ein Fenster aktiv, nimmt die Anwendung Jobs jederzeit an, startet die rechenintensive Arbeit aber nur innerhalb des Fensters.
- Am Fensterende wird kein laufender Prozess hart beendet. Er beendet den aktuellen kleinen Verarbeitungsschritt und gibt den Rest kontrolliert zurück.
- Pause und Wiederaufnahme verlieren keine Jobs.
- Zeitzone, Wochentage, Start, Ende und Verhalten über Mitternacht sind explizit konfigurierbar.
- Bundle-Importe bündeln gleichartige Arbeit, statt pro Datensatz unkontrolliert Worker zu starten.

### 12.3 Was mit `ai-interactive` gemeint ist

`ai-interactive` ist keine zwingend synchrone Webanfrage. Gemeint ist eine priorisierte Warteschlange für eine Benutzeraktion wie „Vorschläge jetzt erzeugen“, bei der die Oberfläche einen zeitnahen Status erwartet. Sie trennt solche kleinen Aufgaben von stundenlangen Backfills.

Berechtigte Benutzer dürfen diese interaktiven Jobs auch außerhalb des Background-Zeitfensters starten. Dafür gilt ein eigenes, serverseitig geprüftes Recht, beispielsweise fachlich `triggerInteractiveAiOutsideWindow`; der endgültige technische Name richtet sich nach dem bestehenden Berechtigungsmuster. Ohne dieses Recht wird die Aktion für das nächste Zeitfenster vorgemerkt oder in der GUI nicht angeboten. UI-Ausblendung allein genügt nicht.

Außerhalb des Zeitfensters gelten Parallelität 1, ein enges Rate Limit je Benutzer und ein globales Lastlimit. Interaktive Jobs dürfen laufende Backfills nicht vervielfachen und umfassen keine OCR-, Reindexierungs- oder Bundle-Massenjobs. Ein globaler Notfall-Pause-Schalter eines Superadministrators sperrt auch interaktive Jobs.

### 12.4 Schutzmechanismen

- globaler Pause-Schalter;
- getrennte Parallelitäts- und Batchlimits;
- Circuit Breaker bei nicht erreichbarem Ollama;
- exponentieller Retry mit Maximalversuchen;
- Dead-Letter-/Failed-Job-Sicht in der Administration;
- Fortschritt pro Import, Ressource und Indexgeneration;
- Speicherdruck- und Laufzeitmetriken;
- kein Retry für dauerhaft ungültige Dateien ohne Inhaltsänderung.

## 13. Admin-Oberfläche

Die Admin-Oberfläche soll verständliche Fachbegriffe verwenden und gefährliche Änderungen als solche kennzeichnen. Vorgesehene Bereiche:

### 13.1 Betriebsstatus

- Erreichbarkeit von Ollama und PostgreSQL;
- aktive Indexgeneration und Vollständigkeit;
- belegter und freier SSD-Speicher, Wachstumsrate sowie 12-GB-Warn- und 8-GB-Pausenschwelle;
- Queue-Länge, laufende und fehlgeschlagene Jobs;
- letzte erfolgreiche Reconciliation;
- Verarbeitungsgeschwindigkeit und geschätzte Restdauer;
- Pause, Fortsetzen und kontrolliertes erneutes Einreihen.

### 13.2 Konfiguration

- aktives Modellprofil je Aufgabe;
- Chunking-Profil als Entwurf und aktivierte Version;
- Zeitfenster, Zeitzone und Wochentage;
- Batchgrößen, Parallelität und Timeouts innerhalb sicherer Grenzen;
- Ziellänge der Kurzbeschreibung;
- vorläufige Suchgewichte und Schwellenprofile;
- Rate Limits und Lastgrenzen für berechtigte interaktive Jobs außerhalb des Fensters.

Änderungen, die einen Reindex verlangen, werden nicht sofort stillschweigend aktiv. Die Oberfläche zeigt Auswirkung, geschätzten Umfang und Rückrollmöglichkeit und erzeugt eine neue Indexgeneration.

### 13.3 Berechtigungen und Audit

Der Zugriff auf die Admin-Konfiguration ist serverseitig ausschließlich Superadministratoren gestattet. Diese Berechtigung ist nicht delegierbar. Das getrennte Recht zum Start interaktiver KI-Jobs erlaubt keine Konfigurationsänderung. Konfigurationsänderungen, Aktivierungen, Pausen, Rücksetzungen und manuelle Annahmen werden mit Benutzer und Zeitpunkt protokolliert. Secrets, Dokumentvolltexte und rohe Prompts gehören nicht in allgemeine Logs.

## 14. Kalibrierung und Qualitätsmessung

### 14.1 Kuratierter Evaluationssatz

Vor produktiver Aktivierung wird ein versionierter Testsatz aufgebaut, mindestens:

- 100 bis 200 reale Suchanfragen auf Deutsch und Englisch;
- exakte Begriffe, Synonyme, Umschreibungen und Mehrdeutigkeiten;
- kurze und lange PDFs, native Texte und OCR-Dokumente;
- Suchzellen mit mehreren `AND`-/`OR`-Kombinationen;
- private und öffentliche Inhalte;
- erwartete Materialien, Ressourcen, Seiten und relevante Textstellen;
- bewusst negative Beispiele ohne fachlichen Treffer.

Der Testsatz darf keine unzulässig veröffentlichten vertraulichen Inhalte enthalten. Für automatisierte Tests werden synthetische oder freigegebene Fixtures verwendet.

### 14.2 Metriken

- Recall@10 und nDCG@10 auf Materialebene;
- Trefferquote der richtigen Ressource und Seite;
- Anteil unbelegter oder falscher Quellenangaben: Ziel 0;
- Precision/Recall angenommener Schlagwort- und Bibelstellen-Vorschläge;
- Ablehnungsquote neuer Schlagworte;
- p50/p95-Suchlatenz;
- Indexierungsdurchsatz und Fehlerrate;
- Rechteverletzungen: Ziel 0.

Schwellen werden so gewählt, dass ein schwacher semantischer Treffer keine klaren Nichttreffer verdrängt. „Kein hinreichender Kontexttreffer“ ist ein gültiges Ergebnis.

### 14.3 Vergleich von Modellen und Chunking

Jede Variante wird mit identischem Evaluationssatz geprüft. Verglichen werden mindestens:

- zwei geeignete Embedding-Modelle;
- zwei Chunkgrößen beziehungsweise Überlappungsprofile;
- exakte Suche gegen HNSW zur Recall-Messung;
- Deutsch, Englisch und gemischte Dokumente getrennt;
- Ressourcenverbrauch auf realer Hardware.

Ein Modell wird nicht allein wegen höherer Benchmark-Werte gewählt, wenn es die Nachtverarbeitung oder interaktive Latenz auf der vorhandenen Hardware unbrauchbar macht.

## 15. Sicherheit und Datenschutz

- PostgreSQL läuft auf demselben Host wie die Webanwendung und lauscht ausschließlich lokal beziehungsweise auf einem dedizierten Unix-Socket; ein externer PostgreSQL-Port ist nicht erforderlich.
- Ollama liegt im vertrauenswürdigen lokalen Netz, wird aber per Firewall nur für den Webhost freigegeben.
- Wenn möglich TLS oder ein abgesicherter interner Tunnel; keine offen erreichbare Ollama-API.
- Separate Datenbankrolle mit minimalen Rechten für den Index.
- Backups des PostgreSQL-Index sind optional, weil er abgeleitet ist; Konfiguration und Auditdaten in MySQL sind dagegen Teil des regulären Backups.
- Temporäre Dumps oder Backups des abgeleiteten Index dürfen die 40-GB-Produktions-SSD nicht als dauerhaften Zielort verwenden.
- Löschung oder Entzug einer Ressource erzeugt priorisierte Deindexierung. Bis dahin verhindert die abschließende MySQL-Autorisierung eine Ausgabe.
- Prompts, Fehlermeldungen und Telemetrie werden auf personenbezogene und vertrauliche Inhalte minimiert.
- PDF-Inhalte gelten als untrusted input. Eingebettete Anweisungen dürfen Agenten- oder Systemvorgaben nicht verändern.
- KI-Ausgaben werden strikt validiert; strukturierte Ausgabe ersetzt keine fachliche oder Berechtigungsprüfung.

## 16. Vorgeschlagene Umsetzungsstufen

Jede Stufe ist ein eigenes freizugebendes Arbeitspaket mit Migrationen, Tests, Betriebshinweisen und Rückbauplan.

### Stufe 0 – Messbarer Spike

**Umsetzungsstand (22. September 2026):** Option A ist begonnen. Das Repository enthält die Laravel-AI-SDK-Abhängigkeit, eine getrennte `context_search`-PostgreSQL-Verbindung, einen lokalen PostgreSQL-17/pgvector-Compose-Dienst, eine ausschließlich manuell aufzurufende Sidecar-Migration sowie den deterministischen Unicode-fähigen Text-Chunker mit Quellzeichen-Offsets. Es gibt weiterhin keinen Listener, keinen Produktivworker, keinen Backfill und keine Änderung an der bestehenden Suche oder Oberfläche.

- PostgreSQL/pgvector und Ollama in einer isolierten Entwicklungsumgebung;
- 50 bis 100 repräsentative Dokumente;
- native Extraktion, OCR-Probe, zwei Embedding-Modelle;
- Chunking- und HNSW-Vergleich;
- Rechtefilter-Prototyp;
- belastbare Speicher-, Durchsatz- und Latenzwerte.
- Kompatibilität der nativen Ollama-Embedding-Anbindung des Laravel AI SDK; zusätzlich Prüfung des `openai-compatible`-Treibers als Rückfalloption.
- Capacity Gate für den gemeinsam genutzten 2-CPU-/4-GB-Produktionshost einschließlich Web- und MySQL-Latenz.

### Stufe 1 – Abgeleiteter Index

- versionierte Modell- und Chunking-Profile;
- Extraktions-, Chunking- und Embedding-Jobs;
- Reconciliation, Löschung und Neuaufbau;
- Betriebsmetriken und CLI-/Admin-Status;
- noch keine Änderung der öffentlichen Suche.

### Stufe 2 – Hybride Suche hinter Feature Flag

- bestehende Suchzellen an Hybrid Search anbinden;
- Materialaggregation und Quellenanzeige;
- serverseitige finale Rechteprüfung;
- A/B- beziehungsweise Shadow-Auswertung gegen die bestehende Suche;
- Rückschaltung ohne Datenverlust.

### Stufe 3 – KI-Vorschläge

- pending/accepted/rejected-Workflow;
- Schlagwort-Dublettenprüfung und Bibelstellenvalidierung;
- Quellenbegründung, Relevanzbearbeitung und Audit;
- Background-Jobs und optionaler interaktiver Pfad.

### Stufe 4 – KI-Kurzbeschreibung

- separate versionierte Speicherung;
- konfigurierbare Länge und Sprache;
- Fallback in Suchergebnissen mit KI-Symbol;
- Mehrressourcen-Zusammenfassung und Aktualisierungsregeln.

### Stufe 5 – Betrieb und Backfill

- resumierbarer Gesamt-Backfill;
- Lastfenster und Pause;
- generationenweiser Modellwechsel;
- Administratorhandbuch mit Installation, Tuning, Monitoring, Fehlerbehebung und Rückbau.

URL-Crawling ist ausdrücklich kein Teil dieser Stufen und erhält später einen eigenen Sicherheits- und Aktualisierungsvertrag, insbesondere zu SSRF, Robots-Regeln, Aktualisierungsintervallen, Canonicals und Löschung.

## 17. Verifikationsvertrag für die spätere Umsetzung

Zusätzlich zu den allgemeinen [Quality Gates](quality-gates.md) sind erforderlich:

- Unit-Tests für Chunkgrenzen, Locator, Normalisierung, Sprachwahl und Ranking-Fusion;
- Feature-Tests für Suchzellen, Policies, private Inhalte und Quellenanzeige;
- Queue-Tests für Idempotenz, veraltete Revisionen, Retry, Pause und Zeitfenster;
- Contract-Tests mit Laravel AI SDK Fakes, ohne Ollama in der normalen Testsuite;
- separate Integrationstests gegen echte PostgreSQL-/pgvector- und Ollama-Testdienste;
- Migrationstests für Auf- und Rückbau jeder neuen MySQL- und PostgreSQL-Struktur;
- Lasttest für Bundle-Importe mit mehreren Tausend Ressourcen;
- Wiederanlauf nach Worker-, Ollama- und PostgreSQL-Ausfall;
- Test, dass gelöschte oder nicht berechtigte Inhalte nie ausgegeben werden;
- visueller und barrierefreier Test des KI-Badges, der Quellenstellen und aller Lade-/Fehlerzustände.

## 18. Administratorhandbuch als Liefergegenstand

Vor produktiver Freigabe muss die README beziehungsweise ein von ihr verlinktes Betriebshandbuch mindestens erklären:

- Installation und Upgrade von PostgreSQL und `pgvector`;
- Datenbank, Rolle, Netzwerkfreigaben und Backup-Entscheidung;
- Installation, Absicherung und Health Check von Ollama;
- Laden und unveränderliches Identifizieren der freigegebenen Modelle;
- Bedeutung jedes Modell-, Chunking-, Such- und Queue-Parameters;
- Anlegen, Testen und Aktivieren einer neuen Indexgeneration;
- Kalibrierung mit dem Evaluationssatz statt willkürlicher Similarity-Prozente;
- Worker, Supervisor, Scheduler, Zeitfenster und Pause;
- Monitoring, Speicherplanung, Fehlerbilder und Wiederanlauf;
- 40-GB-Disk-Budget, Warn-/Pausenschwellen, WAL-/Log-Retention und Vorgehen bei Platzmangel;
- vollständiger Reindex, Rückrollverfahren und kontrollierte Bereinigung alter Generationen.

## 19. Offene Mess- und Freigabepunkte vor der Umsetzung

Die sechs zuvor offenen Produktentscheidungen sind bestätigt. Vor einer produktiven Umsetzung bleiben folgende mess- beziehungsweise implementierungsabhängige Punkte:

1. **Reales Disk-Budget:** Vor Installation werden die gemeldeten 40 GB gegen tatsächlich freien Speicher, vorhandene MySQL-/Anwendungsdaten und deren Wachstum verifiziert. Das Capacity Gate misst anschließend die reale Größe je Indexgeneration.
2. **Capacity Gate:** Der vollständige Backfill auf 4 GB RAM wird erst nach einem realistischen Spike freigegeben. Bei unzureichender p95-Latenz, Swap, OOM-Risiko oder beeinträchtigter Web-/MySQL-Leistung wird vor dem Backfill RAM erweitert oder PostgreSQL ausgelagert.
3. **Embedding-Profil:** Modell, Dimension sowie `vector`, `halfvec` oder quantisierter Index werden anhand von Recall, deutsch-englischer Qualität, Speicher und Latenz gewählt.
4. **Interaktives Recht:** Der technische Permission-/Policy-Name und die anfänglich berechtigten bestehenden Rollen müssen im Umsetzungsarbeitspaket dem vorhandenen Autorisierungsmuster zugeordnet und ausdrücklich freigegeben werden.
5. **OCR-Grenzwert:** Der konkrete Qualitätswert für den Tesseract-Fallback wird mit nativen, gescannten und gemischten PDFs kalibriert.

## 20. Referenzen

- [Laravel 13 AI SDK](https://laravel.com/docs/13.x/ai-sdk)
- [Laravel 13 Query Builder: Vector Similarity](https://laravel.com/docs/13.x/queries#vector-similarity)
- [pgvector](https://github.com/pgvector/pgvector)
- [Ollama Embeddings](https://github.com/ollama/ollama/blob/main/docs/capabilities/embeddings.mdx)
- [PostgreSQL Full Text Search](https://www.postgresql.org/docs/current/textsearch.html)
- [PostgreSQL Resource Consumption](https://www.postgresql.org/docs/current/runtime-config-resource.html)
- [Poppler `pdftotext`](https://manpages.debian.org/bookworm/poppler-utils/pdftotext.1.en.html)
- [Tesseract hOCR/TSV-Ausgabe](https://tesseract-ocr.github.io/tessdoc/Command-Line-Usage.html)

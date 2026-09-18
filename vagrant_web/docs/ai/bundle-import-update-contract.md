# Updatevertrag: robuster Bundle-Import und bundlebezogene Leserechte

## Status, Auftrag und Freigabegrenze

**Stand:** 18. September 2026
**Status:** Entscheidungs- und Umsetzungsvertrag; noch nicht implementiert
**Zielruntime:** Laravel 13, PHP 8.4, MySQL 8, Database Queue
**Zielgruppe:** Der Vertrag ist so konkret, dass ein GPT-5.6-Terra-Modell die Arbeitspakete ohne zusätzliche Architekturannahmen ausführen kann.

Der Auftrag umfasst zwei zusammenhängende Vorhaben:

1. den bestehenden Bundle-Import, das Update und die Deinstallation fehlertolerant, fortsetzbar, parallel verarbeitbar und beobachtbar zu machen;
2. pro Bundle ein dauerhaftes Leserecht einzuführen, das Bundle-Materialien und die direkt aus diesem Bundle importierten Ressourcen schützt.

Dieser Vertrag enthält noch keinen Produktivcode. Seine Umsetzung verändert Datenbank, Queue-Orchestrierung, API-Antworten, Berechtigungen, Sichtbarkeit und UI-Verhalten. Vor dem ersten Implementierungsarbeitspaket ist deshalb die unter [Entscheidung und benötigte Freigabe](#entscheidung-und-benötigte-freigabe) formulierte Gesamtentscheidung zu bestätigen.

Maßgeblich bleiben außerdem:

- [Architekturkarte](architecture.md)
- [Domänen-Invarianten](domain-invariants.md)
- [Qualitätssicherung](quality-gates.md)
- [Produktions- und Deploymentvertrag](production-deployment-contract.md)
- [Autorisierung und Sichtbarkeit in der Materialsuche](material-search-authorization-plan.md)
- [Entscheidungsvorlage](decision-template.md)

Als Laravel-13-Grundlage gelten insbesondere die offiziellen Dokumentationen zu [Queues und Job Batching](https://laravel.com/framework/docs/13.x/queues), [Atomic Locks](https://laravel.com/framework/docs/13.x/cache#atomic-locks) und [Autorisierung](https://laravel.com/framework/docs/13.x/authorization). Lokal wurde verifiziert, dass Laravel 13 `WithoutOverlapping`, `Bus::batch()`, `then`, `catch`, `finally` und atomare Cache-Locks bereitstellt. `ShouldBeUnique` ist kein Ersatz für den hier benötigten Schutz, weil Laravel Unique-Job-Constraints innerhalb von Batches ausdrücklich nicht anwendet.

## Ziele und verbindliche Erfolgsdefinition

Die Umsetzung ist nur abgeschlossen, wenn alle folgenden Aussagen gelten:

- Pro Bundle existiert höchstens ein aktiver Import-, Update- oder Deinstallationslauf.
- Derselbe logische Entity-Job kann niemals gleichzeitig in zwei Workern laufen.
- Eine sequenzielle Zweitzustellung bleibt unschädlich; jeder mutierende Job ist idempotent.
- Browser und Terminal dürfen dieselbe Bundle-Queue gleichzeitig konsumieren. Unterschiedliche Entity-Jobs derselben freigegebenen Phase dürfen dadurch parallel laufen.
- Das Schließen des Browsers stoppt den serverseitigen Lauf nicht. Ein Terminal-Worker kann ihn ohne Neuinitialisierung fortsetzen; ein später erneut geöffneter Browser zeigt denselben Lauf und kann die browserseitige Verarbeitung wieder aufnehmen.
- Ressourcen werden vollständig und erfolgreich verarbeitet, bevor irgendein Material-Upsert beginnt.
- Ein fehlgeschlagener Job verhindert die Erfolgsfinalisierung. Das Bundle darf dann weder eine neue `installed_version` noch `is_installed=true` erhalten.
- Ein Retry oder erneutes Öffnen der Oberfläche erzeugt keine doppelten Materialien, Ressourcen, Foreign-IDs oder Pivot-Zuordnungen.
- Import, Update und Deinstallation besitzen nachvollziehbare, persistierte Zustände und Fehlerangaben ohne Nutzdaten oder Secrets im Log.
- Ein Bundle kann deinstalliert werden, wenn seine Quell-SQLite-Datei nicht mehr vorhanden ist.
- Material-, Resource-, Pivot-, Event-, Listener-, Cache- und Bereinigungsverträge bleiben erhalten.
- Fehler erreichen das Frontend als abgelehnte HTTP-Anfrage mit stabiler Fehlerstruktur; sie werden nicht als erfolgreicher Stringwert weitergereicht.
- Ein aktiver Benutzer sieht Bundle-Materialien und direkt aus Bundles importierte Ressourcen nur mit passendem Bundle-Leserecht oder einem dokumentierten globalen Override.
- Bundle-Leserechte und ihre Gruppen-/Nutzerzuordnungen bleiben bei Deinstallation erhalten und werden bei erneutem Import derselben Bundle-UUID wiederverwendet.

## Nichtziele

- Kein Wechsel von Database Queue zu Redis und keine Einführung von Horizon.
- Keine neue Composer- oder npm-Abhängigkeit.
- Kein Umbau der allgemeinen Material-, Resource- oder Gruppenverwaltung außerhalb der für Bundle-Rechte notwendigen Erweiterungen.
- Keine automatische Korrektur mehrdeutiger bestehender Bundle-UUIDs oder fachlich widersprüchlicher Foreign-ID-Daten.
- Kein Zugriff auf produktive Daten und keine produktive Migration im Rahmen der Implementierung.
- Kein „Cancel und Rollback aller bereits vorgenommenen Importänderungen“. Ein echtes fachliches Rollback wäre ein separates Projekt. Die bestehende Schaltfläche wird deshalb als lokales Stoppen der Browser-Verarbeitung neu benannt und erklärt.
- Kein Löschen historischer `job_batches`, Importläufe oder Bundle-Permissions durch den normalen Deinstallationsablauf.

## Bestandsaufnahme und zu erhaltende Verträge

### Heutiger Ablauf

1. `BundleImportController@index` entdeckt Unterverzeichnisse auf der privaten `bundles`-Disk und liest deren `database.sqlite`.
2. `initUpdate` beziehungsweise `initUninstall` erzeugt Jobs in `bundle_{id}_queue`.
3. Delete-Jobs werden vor Upsert-Jobs angelegt; Ressourcen-Upserts werden vor Material-Upserts angelegt.
4. `runJobs` startet innerhalb eines HTTP-Requests einen Laravel-Worker für ungefähr fünf Sekunden.
5. `FinishImportAfterUpdate` beziehungsweise `FinishBundleUninstall` betrachtet nur die verbliebene Queuegröße und setzt danach den Bundle-Status.
6. Die Vue-Komponente ruft `run-update` rekursiv auf, bis `open=0` gemeldet wird.

### Positive Bestandsmerkmale

- Die API-Gruppe verlangt `auth:api` und `active`; Verwaltungsaktionen verlangen zusätzlich `bundles.manage`.
- Jedes Bundle besitzt eine eigene Queue `bundle_{id}_queue`. Dieser Name bleibt aus Kompatibilitätsgründen erhalten.
- Resource-Upserts verwenden `ResourceWasCreated` beziehungsweise `ResourceWasChanged`.
- Einzelne Material- und Resource-Upserts besitzen lokale Datenbanktransaktionen.
- Löschjobs berücksichtigen gemeinsame Foreign-IDs und verknüpfte Materialien.
- Bundle-Dateien liegen auf einer privaten Laravel-Disk.

### Zu behebende Defekte

Die Umsetzung muss mindestens folgende nachgewiesene Defekte beheben:

- falsche positionale `WorkerOptions`, wodurch derzeit unter anderem `maxTries=1`, `timeout=0` und `sleep=15` gelten;
- Erfolgsfinalisierung trotz Eintrag in `failed_jobs`;
- keine atomare Sperre gegen parallele Initialisierung oder Update/Uninstall-Kollision;
- direktes Löschen auch reservierter Queue-Einträge;
- keine dauerhafte Laufidentität und kein zuverlässiger Wiederaufnahmezustand;
- fehlende Material-/Attach-/Detach-Events und dadurch mögliche veraltete Preview-Caches;
- Material-Zeitstempel werden aus `material_created`/`material_modified` falsch zugeordnet;
- `icon_of_bundle` wird gegen den Icon-Pfad statt gegen die Bundle-ID verglichen;
- Pagination der Bundle-SQLite ohne deterministisches `ORDER BY`;
- die Bedingung `!$filterUuid !== NULL` liefert unabhängig vom Argument denselben Zweig;
- fehlende Vorabvalidierung des Bundle-Schemas und seiner referenzierten Dateien;
- stille Exception-Catches bei fehlenden Bundle-Dateien und ungültigen Keyword-Typen;
- Deinstallation ist von der noch vorhandenen Quelldatei abhängig;
- Store-Fehler werden in erfüllte Promises umgewandelt;
- „Abbrechen“ stoppt nur das Browser-Polling, nicht den Queue-Lauf;
- Foreign-ID-Lookups dokumentieren beziehungsweise erzwingen den benötigten Eindeutigkeitsraum nicht;
- die vorhandenen Bundle-Tests prüfen überwiegend Queue-Namen und Payload-Metadaten, nicht den fachlichen End-to-End-Vertrag.

## Entscheidung 1: Laufmodell, Batches und Parallelität

**Ausgangslage:** Die Queue selbst verhindert, dass derselbe konkrete Queue-Datensatz zeitgleich von zwei Database-Queue-Workern reserviert wird. Sie verhindert jedoch weder doppelt erzeugte logische Jobs noch konkurrierende Importläufe noch eine sequenzielle erneute Zustellung nach einem Worker-Abbruch. Die bisherige reine Queuegrößenprüfung kann Fehler nicht von Erfolg unterscheiden.

**Empfehlung:** Option A. Ein persistierter `BundleImportRun` koordiniert genau einen aktiven Lauf pro Bundle. Jede mutierende Phase wird als Laravel-Batch ohne `allowFailures()` ausgeführt. `then` startet ausschließlich die nächste Phase, `catch` markiert den Lauf als fehlgeschlagen, und `finally` gleicht Batch- und Laufstatus ab. `WithoutOverlapping` schützt jeden logischen Entity-Job; Idempotenz und Datenbank-Constraints schützen zusätzlich gegen sequenzielle Doppelausführung.

| Option | Vorteile | Nachteile/Risiken | Betroffene Bereiche | Rückbauaufwand |
| --- | --- | --- | --- | --- |
| A – persistierter Lauf, phasenweise Batches, Entity-Locks (Empfehlung) | Laravel-Standardmechanismen; parallele Worker; belastbarer Fortschritt; Fehler blockieren Finalisierung; Browserabbruch unkritisch | neue Tabellen und Orchestrierung; sorgfältige Tests für Callback-/Retry-Verhalten nötig | Migrationen, Jobs, Services, API, CLI, UI, Deployment | Mittel; neue Pfade können entfernt werden, Laufhistorie bleibt als Datenartefakt |
| B – eine einzige lange Job-Chain | einfachere Reihenfolge | praktisch keine Parallelisierung; schlechter Fortschritt; ein großer Engpass | Jobs, API | Niedrig |
| C – bestehende Queue mit zusätzlichem Global-Lock | kleine Änderung | Browser und Terminal könnten nicht gleichzeitig beschleunigen; Fehlerstatus bleibt schwach | Queue-Service, Controller | Niedrig, erfüllt das Ziel aber nicht |

### Warum nicht ausschließlich `ShouldBeUnique`

`ShouldBeUnique` verhindert das Dispatching bestimmter Duplikate, ist laut Laravel aber innerhalb von Batches nicht wirksam. Es darf optional außerhalb eines Batches für Coordinator-Jobs verwendet werden, ist jedoch kein Abnahmekriterium. Verbindlich sind:

1. genau ein aktiver Lauf durch Datenbank-Invariante;
2. `WithoutOverlapping` für gleichzeitig ausgeführte logische Entity-Jobs;
3. idempotente `handle()`-Implementierungen;
4. eindeutige Datenbank-Constraints, wo die Domäne bereits Eindeutigkeit verlangt.

### Parallelitätsvertrag

- Die Lauf-Erstellung verwendet `Cache::lock('bundle-import:start:{bundleId}', 30)->block(5, ...)`.
- Der Default-File-Cache ist auf dem dokumentierten Einzelserver zwischen PHP-FPM und Terminalprozessen geteilt und unterstützt Laravel-Locks. Tests verwenden einen Lock-fähigen Store. Bei einer späteren Mehrserverarchitektur muss vor Skalierung auf einen gemeinsamen zentralen Cache gewechselt werden.
- Innerhalb des Locks läuft eine Datenbanktransaktion mit `lockForUpdate()` auf dem Bundle und der Suche nach einem aktiven Lauf.
- Eine eindeutige Datenbank-Invariante bleibt die letzte Schutzschicht; der Cache-Lock allein genügt nicht.
- Das Lock der Lauf-Erstellung wird nach Dispatch des ersten Batches freigegeben. Es wird niemals für die gesamte Importdauer gehalten.
- Entity-Jobs erhalten `WithoutOverlapping` mit `shared()`, `releaseAfter(5)` und `expireAfter(180)`.
- Lockschlüssel enthalten mindestens Lauf-ID, Operationstyp und stabile Entity-ID, zum Beispiel `bundle-run:{runId}:resource:{foreignUuid}` oder `bundle-run:{runId}:material:{foreignUuid}`.
- Jobs unterschiedlicher Entities dürfen parallel laufen. Zwei Jobs für dieselbe Entity dürfen nicht gleichzeitig laufen.
- Jeder Job besitzt `timeout=120`; `retry_after=150` bleibt länger als das Job-Timeout. Das Overlap-Lock läuft erst nach 180 Sekunden aus und damit nicht vor einem normal beendeten Worker-Timeout.
- Releases durch `WithoutOverlapping` zählen als Versuche. Jobs verwenden deshalb eine zeitbasierte `retryUntil()`-Grenze von mindestens 30 Minuten statt einer zu kleinen festen Versuchszahl.
- `failed(Throwable $exception)` protokolliert ausschließlich Run-ID, Bundle-ID, Phase, Jobklasse, Entity-Schlüssel und einen bereinigten Fehlercode. Keine Materialtexte, Dateiinhalte, absoluten Storage-Pfade oder Secrets loggen.

### Zusätzliche domänenspezifische Locks

Parallele Material-Jobs können dieselben Keywords, Autoren oder Bibelstellen erzeugen. Da Keywords ein Nested Set bilden, gilt zusätzlich:

- Die Erzeugung eines noch nicht vorhandenen Keywords/Autors wird in einem kurzen gemeinsamen Lock `bundle-import:keyword-tree-mutation` ausgeführt.
- Innerhalb des Locks wird erneut gesucht; erst danach darf erstellt werden.
- Das Lock umfasst nicht den gesamten Material-Job und nicht das bloße Attach/Update eines bereits vorhandenen Keywords.
- Die Erzeugung einer Bibelstelle wird mit einem Lock aus ihrem normalisierten Von-/Bis-Schlüssel geschützt und innerhalb des Locks erneut gesucht.
- Eine neue Unique-Constraint für Keywords oder Bibelstellen darf nur ergänzt werden, wenn die Bestandsdaten vorher konfliktfrei nachgewiesen wurden. Dieser Vertrag ordnet keine automatische Dublettenbereinigung an.

## Ziel-Datenmodell

### Standardtabelle `job_batches`

Eine neue, von `php artisan make:queue-batches-table` erzeugte und anschließend projekttypisch formatierte Migration legt Laravels unveränderte Standardtabelle `job_batches` an. Keine selbst erfundene Spaltenvariante verwenden. Bestehende Migrationen bleiben unverändert.

### Tabelle `bundle_import_runs`

Eine getrennte neue Migration legt folgende Tabelle an:

| Spalte | Typ/Constraint | Vertrag |
| --- | --- | --- |
| `id` | UUID, Primary Key | serverseitig erzeugte stabile Lauf-ID |
| `bundle_id` | unsigned integer, FK auf `bundles.id`, `restrictOnDelete` | Bundle wird während vorhandener Historie nicht gelöscht |
| `requested_by` | nullable FK auf `users.id`, `nullOnDelete` | anstoßender Benutzer; CLI ohne Benutzer bleibt `NULL` |
| `operation` | string(20), Index | ausschließlich `install`, `update`, `uninstall` |
| `status` | string(20), Index | ausschließlich `pending`, `running`, `succeeded`, `failed` |
| `phase` | string(32) | siehe Phasenvertrag |
| `active_slot` | nullable unsigned tiny integer | bei aktivem Lauf `1`, bei terminalem Lauf `NULL` |
| `target_version` | nullable string(191) | bei Uninstall die zuletzt installierte/erkannte Version |
| `source_fingerprint` | nullable char(64) | SHA-256 der gelesenen Bundle-SQLite-Datei |
| `queue_name` | string(191) | eingefrorener Name `bundle_{id}_queue` |
| `current_batch_id` | nullable UUID/String gemäß Laravel-Migration | aktuelle Batch-ID |
| `validation_batch_id` | nullable | Batch-Historie |
| `delete_materials_batch_id` | nullable | Batch-Historie |
| `delete_resources_batch_id` | nullable | Batch-Historie |
| `resources_batch_id` | nullable | Batch-Historie |
| `materials_batch_id` | nullable | Batch-Historie |
| `expected_jobs` | unsigned integer, Default 0 | Summe über alle Phasen |
| `processed_jobs` | unsigned integer, Default 0 | aus Batchständen abgeleitet/abgeglichen, nicht blind inkrementiert |
| `failure_code` | nullable string(100) | stabiler maschinenlesbarer Code |
| `failure_message` | nullable string(1000) | bereinigte Bedieninformation |
| `started_at` | nullable timestamp | erster Übergang zu `running` |
| `finished_at` | nullable timestamp | Erfolg oder Fehler |
| `created_at`, `updated_at` | timestamps | Audit/Anzeige |

Verbindliche Indizes:

- unique `['bundle_id', 'active_slot']`; MySQL erlaubt mehrere `NULL`-Werte, aber nur einen aktiven Datensatz mit `active_slot=1` je Bundle;
- Index `['bundle_id', 'created_at']` für Verlauf;
- Index `current_batch_id` für Callback-/Statusauflösung.

Terminale Übergänge setzen in derselben Transaktion `active_slot=NULL` und `finished_at`. Es darf keinen Zustand `status=succeeded|failed` mit `active_slot=1` geben.

### Bundle-UUID

Bundle-Permissions verwenden die unveränderliche Bundle-UUID, nicht die lokale numerische ID. Vor einer Unique-/Not-Null-Verschärfung ist ein read-only Preflight gegen Bestandsdaten auszuführen:

- keine `NULL`- oder leeren UUIDs;
- keine doppelte UUID;
- jede auf der Bundle-Disk entdeckte UUID verweist eindeutig auf einen Bundle-Datensatz.

Bei einem Konflikt stoppt die Umsetzung. Es erfolgt keine automatische Umbenennung oder Zusammenführung. Erst nach konfliktfreiem Nachweis darf eine neue Migration `bundles.uuid` auf `NOT NULL` und `UNIQUE` setzen. Der Down-Pfad entfernt nur Constraint/Index, nicht UUID-Daten.

## Phasen- und Batchvertrag

Ein Lauf durchläuft genau folgende Zustände:

```text
pending
  → validating
  → deleting_materials
  → deleting_resources
  → upserting_resources
  → upserting_materials
  → finalizing
  → succeeded
                  ↘
                    failed (aus jeder nichtterminalen Phase)
```

### Allgemeine Batchregeln

- Jede mutierende Entity-Jobklasse verwendet `Batchable` und bricht am Anfang ab, wenn `$this->batch()?->cancelled()` wahr ist.
- `allowFailures()` ist verboten.
- Alle Jobs eines Batches verwenden `database` und die eingefrorene `queue_name` des Laufs.
- Jeder Batch erhält einen lesbaren Namen: `bundle:{bundleId}:run:{runId}:{phase}`.
- `then` darf nur die nächste Phase über einen fokussierten `BundleImportOrchestrator` dispatchen.
- `catch` ruft eine idempotente Fehlertransition auf. Der erste fachliche Fehler bleibt maßgeblich; spätere Callback-Aufrufe dürfen ihn nicht überschreiben.
- `finally` ruft eine Reconciliation auf: Batch-ID, Batchstatus und Runstatus müssen zusammenpassen. `finally` markiert niemals selbst Erfolg.
- Batch-Callbacks verwenden kein `$this`; sie erfassen nur die skalare Run-ID und lösen den Orchestrator aus dem Container auf.
- Der Orchestrator sperrt den Run-Datensatz per `lockForUpdate()`, bevor er eine Phase wechselt oder einen Folgebatch erzeugt.
- Ein erneut ausgeführter `then`-Callback erkennt die bereits gespeicherte nächste Batch-ID und dispatcht keinen zweiten Batch.
- Die Erfolgsfinalisierung prüft alle gespeicherten Batches über `Bus::findBatch()`: `finished()=true`, `failedJobs=0`, nicht abgebrochen und erwartete Jobanzahl plausibel.

### Phase 1 – `validating`

Vor der ersten Domänenmutation prüft ein Validation-Batch mindestens:

- Bundle-SQLite vorhanden, lesbar und SHA-256 passend zum Lauf;
- erwartete Tabellen und Pflichtspalten vorhanden;
- Bundle-ID, UUID und Zielversion entsprechen dem ausgewählten Bundle;
- Material- und Resource-UUIDs sind innerhalb der Quelle eindeutig und nicht leer;
- alle `material_files`-Referenzen zeigen auf vorhandene Material-/File-Datensätze;
- `file_path` ist relativ, normalisiert, enthält kein `..`, keinen absoluten Pfad und bleibt innerhalb von `{container_root}/files`;
- benötigte lokale Dateien existieren und sind lesbar;
- MIME-Typen lassen sich über den bestehenden `ResourceRecognitionService` abbilden;
- Metadatentypen liegen in `key`, `person`, `place`, `lang`, `bibleverse`;
- Bibelstellen erfüllen das vorhandene Format;
- Bewertungen liegen im gültigen Bereich;
- Foreign-UUID-Kollisionen mit anderen Bundles werden entsprechend dem unten festgelegten Eindeutigkeitsvertrag behandelt.

Validierungsjobs verändern keine Domainmodelle, Pivots, Foreign-IDs oder Bundle-Statusfelder. Ein Validation-Fehler beendet den Lauf vor jeder fachlichen Mutation.

### Phase 2 – `deleting_materials`

- Es werden ausschließlich ForeignMaterialIds des ausgewählten Bundles betrachtet.
- Ein Update löscht die Zuordnung nur, wenn die UUID im validierten Quellsnapshot nicht mehr vorhanden ist.
- Ein Uninstall behandelt jede Zuordnung als entfernt und benötigt dafür keine Bundle-Quelldatei.
- Die vorhandene Regel bleibt erhalten: Ein vom Bot stammendes Material wird nur vollständig gelöscht, wenn keine weitere ForeignMaterialId besteht; andernfalls wird nur die Bundle-Zuordnung entfernt.
- Von Benutzern bearbeitete Materialien bleiben bestehen; nur die ForeignMaterialId des deinstallierten/entfernten Bundles wird entfernt.
- Materiallöschung verwendet weiterhin `MaterialHandlingService`, damit Ressourcen, Keywords, Bibelstellen, Events und Lonely-Checks erhalten bleiben.

### Phase 3 – `deleting_resources`

- Die Phase startet erst nach erfolgreichem Abschluss aller Material-Delete-Jobs.
- Es werden ausschließlich ForeignResourceIds des Bundles betrachtet.
- Eine Resource wird nur vollständig gelöscht, wenn keine Materialzuordnung und keine weitere ForeignResourceId besteht.
- Bundle-Dateien werden bei Deinstallation weiterhin nicht aus der persistenten Bundle-Quelle gelöscht.
- Vollständige Löschung verwendet `FileHandlingService::deleteResourceCompletely()` und behält die bestehende Eventkette.

### Phase 4 – `upserting_resources`

- Jobs adressieren Daten mit Run-ID, Bundle-ID, Foreign-UUID und einem validierten unveränderlichen DTO; keine vorab geladenen Eloquent-Relationen serialisieren.
- Lookup und Speicherung verwenden den vertraglich festgelegten Eindeutigkeitsraum.
- Resource-Typwechsel `file ↔ text` dürfen nicht stillschweigend halb umgesetzt werden. Entweder wird der bestehende Typ sicher ersetzt und vollständig getestet, oder die Validierung beendet den Lauf mit `unsupported_resource_type_change`.
- Dateiinhalt, `local_path`, `filesize`, Hash, Metadaten und Events folgen dem bestehenden Resource-Vertrag.
- `ResourceWasCreated`/`ResourceWasChanged` werden erst nach erfolgreichem Commit ausgelöst, beispielsweise über after-commit-fähige Domain-Events oder explizites `DB::afterCommit()`.
- Fehlende Textdateien dürfen nicht mehr still in leeren Inhalt umgewandelt werden. Sie sind ein Validierungs-/Importfehler.

### Phase 5 – `upserting_materials`

- Diese Phase startet erst, wenn der gesamte Resource-Batch ohne Fehler beendet wurde.
- Jobs sind pro Foreign-Material-UUID überlappungsfrei und idempotent.
- ForeignResourceIds werden bundlebezogen beziehungsweise nach dem freigegebenen globalen UUID-Vertrag aufgelöst.
- Fehlt trotz erfolgreichem Resource-Batch eine referenzierte Resource, schlägt der Material-Job fehl; er synchronisiert niemals still eine unvollständige Resource-Liste.
- `created_at` erhält `material_created`, `updated_at` erhält `material_modified`.
- `icon_of_bundle` wird mit `bundle.id` verglichen und auf `bundle.id` gesetzt.
- Titel, Beschreibung, Bewertung, Autor, Keywords, Bibelstellen, Relevanzen und Resource-Pivots werden innerhalb einer Transaktion aktualisiert.
- Nach Commit werden passend zum tatsächlichen Delta `MaterialWasCreated` oder `MaterialWasChanged` sowie `ResourceWasAttached`/`ResourceWasDetached` ausgelöst. Unveränderte Pivots erzeugen kein Event.
- Preview-Caches werden damit über die vorhandenen Listener invalidiert.
- Die bestehende Produktsemantik, dass der Bundle-Stand Keywords/Bibelstellen/Resource-Zuordnungen eines importierten Materials synchronisiert, bleibt zunächst erhalten. Eine abweichende Merge-Semantik für nachträgliche Benutzerergänzungen ist nicht Bestandteil dieses Vertrags.

### Phase 6 – `finalizing`

Die Finalisierung läuft als eigener idempotenter Coordinator-Job, nicht als „letzter normaler Queue-Eintrag“. Sie darf Erfolg nur setzen, wenn:

- Run und Bundle übereinstimmen;
- der Run noch aktiv und nicht fehlgeschlagen ist;
- alle verpflichtenden Batch-IDs vorhanden sind;
- alle verpflichtenden Batches erfolgreich beendet wurden;
- kein Batch fehlgeschlagene Jobs besitzt;
- Quelle und Zielversion dem validierten Snapshot entsprechen;
- die erwarteten ForeignMaterialId-/ForeignResourceId-Grundinvarianten erfüllt sind.

Bei Install/Update setzt sie `installed_version=target_version`, `update_available=false`, `is_installed=true`. Bei Uninstall setzt sie `installed_version=NULL`, `update_available=true`, `is_installed=false`. Danach wird der Run atomar auf `succeeded`, `active_slot=NULL`, `finished_at=now()` gesetzt.

Kein anderer Job darf diese finalen Bundle-Felder schreiben. Der Zwischenwert `'incomplete'` entfällt; der Runstatus bildet den Fortschritt ab.

## Wiederaufnahme, Browser und Terminal

### Lauf anstoßen

- Wiederholtes `init-update` oder `init-uninstall` für dasselbe Bundle liefert den vorhandenen aktiven Lauf, wenn Operation und Zielversion identisch sind.
- Bei kollidierender Operation, etwa Uninstall während Update, antwortet die API mit `409 Conflict` und dem aktiven Laufstatus.
- Ein terminaler fehlgeschlagener Lauf wird nicht automatisch überschrieben. Ein expliziter Retry erzeugt einen neuen Run; bereits idempotent abgeschlossene Domainänderungen dürfen dabei nicht dupliziert werden.

### Browser-Verarbeitung

- Der vorhandene `run-update`-Endpunkt bleibt zunächst als kompatibler „Pump“-Endpunkt bestehen, verarbeitet aber nur eine begrenzte Anzahl Jobs beziehungsweise höchstens fünf Sekunden.
- `WorkerOptions` werden ausschließlich mit benannten Argumenten erzeugt. Verbindlich: `name='bundle-browser'`, `backoff=5`, `memory=128`, `timeout=120`, `sleep=0`, und eine zu `retryUntil()` passende Versuchsstrategie.
- Nach jedem tatsächlichen `runNextJob` wird der Batch-/Queuezustand neu gelesen. Ein Aufruf ohne verfügbaren Job zählt nicht als erledigter Job.
- Der Endpunkt liefert HTTP `202` solange der Run aktiv ist, `200` bei terminalem Erfolg, und einen passenden Fehlerstatus bei `failed`.
- Das Schließen der Seite hat keine serverseitige Cancel-Wirkung. Die UI benennt die Aktion in „Verarbeitung in diesem Browser pausieren“ um und erklärt, dass Queue und Terminal-Worker weiterlaufen können.

### Terminal-Verarbeitung

Mindestens folgender Laravel-Standardweg wird dokumentiert und getestet:

```bash
php artisan queue:work database --queue=bundle_<ID>_queue --stop-when-empty --timeout=120 --tries=0 --backoff=5
```

Zusätzlich wird ein dünner Komfortbefehl `bundles:work {bundle}` empfohlen. Er darf keine eigene Joblogik enthalten, sondern validiert Bundle/Queue, zeigt den aktiven Run an und startet/delegiert denselben Worker-Vertrag. Er darf niemals eine neue Importinitialisierung implizit auslösen.

Browser-Pump und Terminal-Worker dürfen gleichzeitig laufen. Die Database Queue reserviert verschiedene Queue-Datensätze atomar; `WithoutOverlapping` und Idempotenz schützen logische Duplikate.

### Status und Fortschritt

Ein neuer lesender Endpunkt liefert für den aktiven oder angegebenen Run:

```json
{
  "id": "run-uuid",
  "bundle_id": 7,
  "operation": "update",
  "status": "running",
  "phase": "upserting_resources",
  "target_version": "2.4.1",
  "progress": {
    "total": 420,
    "processed": 180,
    "failed": 0,
    "percentage": 42
  },
  "failure": null
}
```

Der Zähler wird aus den Laravel-Batches abgeleitet und gegen die gespeicherten Run-Summen plausibilisiert. Die UI darf nicht aus lokaler Rekursion ableiten, dass ein Lauf erfolgreich ist.

## API- und Fehlervertrag

Bestehende Routen bleiben in der ersten Umsetzung erhalten. Ergänzungen dürfen versioniert unter `api/v1` erfolgen, ohne vorhandene Erfolgsfelder zu entfernen.

Verbindliche Semantik:

- `POST bundles/{bundle}/init-update`: `202`, neuer oder identischer aktiver Run;
- `POST bundles/{bundle}/init-uninstall`: `202`, neuer oder identischer aktiver Run;
- `POST bundles/{bundle}/run-update`: bounded Pump; `202` aktiv, `200` erfolgreich;
- `GET bundles/{bundle}/runs/{run}` beziehungsweise eindeutig benannter Statusendpunkt: read-only Status;
- fehlende Bundle-Quelle bei Installation/Update: `422 Unprocessable Entity` mit `bundle_source_missing`;
- fehlende Quelle bei Uninstall: kein Fehler, da die persistierten Foreign-IDs genügen;
- kollidierender aktiver Lauf: `409 Conflict`;
- unbekannter/fremder Run: `404 Not Found`;
- unerwarteter interner Fehler: `500`, ohne Pfade oder Payload-Inhalte.

Einheitliche Fehlerform:

```json
{
  "error": {
    "code": "bundle_source_invalid",
    "message": "Das Bundle kann nicht verarbeitet werden.",
    "run_id": "run-uuid"
  }
}
```

Das Frontend darf Fehler nicht mit `.catch(... => string)` in Erfolg umwandeln. Stores speichern optional eine UI-Fehlermeldung, werfen den Fehler aber erneut. Komponenten beenden Polling, zeigen den Runstatus und bieten bei einem aktiven Lauf „Fortsetzen“, bei einem fehlgeschlagenen Lauf einen expliziten neuen Retry an.

## Bundle-Quelle, Abfragen und Eindeutigkeit

### Deterministische SQLite-Abfragen

- Jede paginierte File-Abfrage sortiert mindestens nach `files.id`, danach `files.uuid`.
- Jede paginierte Material-Abfrage sortiert mindestens nach `material.id`, danach `material.uuid`.
- `LIMIT` und Offset sind Integerwerte aus intern kontrollierten Grenzen.
- Die redundante Zuweisung `$start = $start = ...` wird beseitigt.
- Count-Abfragen verwenden gebundene Parameter.
- Unerreichbarer Code nach `return` wird entfernt.
- `getBundles()` prüft explizit `$filterUuid !== null`.

### Foreign-ID-Eindeutigkeitsvertrag

Vor Umsetzung ist mit vorhandenen Daten und mindestens zwei synthetischen Bundles zu verifizieren, ob externe Material-/Resource-UUIDs global eindeutig oder nur innerhalb eines Bundles eindeutig sind.

**Empfehlung:** Eindeutigkeit ist bundlebezogen: `(bundle_id, foreign_id)`. Das ist bei getrennt erzeugten Bundles robuster und entspricht der vorhandenen `bundle_id`-Zuordnung. Alle Import-Lookups verwenden beide Werte. API-Foreign-IDs außerhalb von Bundles behalten ihren bestehenden `(foreign_id, user_id)`-Vertrag.

Da die bestehenden Unique-Constraints diese beiden Welten mischen, gilt ein Stop-Kriterium: Die Migration darf erst entworfen werden, nachdem reale Bestandsformen anonymisiert als reine Counts/Constraint-Konflikte geprüft wurden. Notwendige Constraint-Änderungen erhalten eine eigene reversible Migration und separate Vertragstests. Es findet keine automatische Datenzusammenführung statt.

## Entscheidung 2: Bundlebezogene Leserechte

**Ausgangslage:** Materialien und Ressourcen besitzen allgemeine Sichtbarkeitsregeln. Importierte Bundle-Inhalte benötigen zusätzlich eine pro Bundle delegierbare Leseberechtigung. Die Anwendung verwendet bereits Spatie Permission mit Gruppen/Rollen und direkten Nutzerrechten.

**Empfehlung:** Option A. Für jede unveränderliche Bundle-UUID wird dynamisch eine Spatie-Permission `bundles.view.<uuid>` mit Guard `web` angelegt. Die Permission kann bestehenden Gruppen und bei Bedarf direkt Benutzern zugewiesen werden. Sie wird bei Deinstallation niemals gelöscht.

| Option | Vorteile | Nachteile/Risiken | Betroffene Bereiche | Rückbauaufwand |
| --- | --- | --- | --- | --- |
| A – dynamische Spatie-Permission pro Bundle-UUID (Empfehlung) | nutzt vorhandene Rollenverwaltung; UUID über Reimport stabil; keine zweite ACL; Zuordnungen bleiben automatisch erhalten | dynamischer Permission-Katalog und komplexere Sichtbarkeitsqueries | Permissions, Gruppen-API/UI, Policies, Scopes, Suche, Tests | Mittel |
| B – neue `bundle_role`-/`bundle_user`-Pivots | sehr explizites relationales Modell | zweite parallele Berechtigungsengine; eigenes Admin-UI und Caching | neue Tabellen, Modelle, APIs, UI | Hoch |
| C – eine globale `bundles.view`-Permission | sehr einfach | keine Rechte je Bundle; erfüllt den Auftrag nicht | Permission-Katalog | Niedrig, fachlich ungeeignet |

### Benennung und Lebenszyklus

- Format: `bundles.view.{bundleUuid}`.
- Ein zentraler `BundlePermissionName`-Helper erzeugt und validiert Namen. Keine verteilte Stringkonkatenation.
- Die Permission wird bei erstmaliger Bundle-Discovery oder spätestens vor Import mit `Permission::findOrCreate(..., 'web')` angelegt.
- Bundle-Deinstallation, fehlende Quelldatei oder `is_installed=false` löschen weder Permission noch `role_has_permissions` noch `model_has_permissions`.
- Ein erneuter Import derselben UUID verwendet dieselbe Permission.
- Eine endgültige Bundle-/Permission-Bereinigung ist ausdrücklich kein Bestandteil dieses Vertrags.

### Verbindliche Sichtbarkeitssemantik

Für aktive Benutzer gilt:

#### Bundle-Material

Ein Material ist bundlegebunden, sobald mindestens eine `ForeignMaterialId` mit nicht-leerer `bundle_id` existiert.

Ein bundlegebundenes Material ist sichtbar, wenn mindestens eine Bedingung erfüllt ist:

1. der Benutzer ist Global-Admin;
2. der Benutzer besitzt `materials.view-all`;
3. der Benutzer besitzt für mindestens eines der zugeordneten Bundles `bundles.view.<uuid>`.

`materials.created_by`, `materials.is_public` und `materials.view-public` gewähren bei einem bundlegebundenen Material **keinen** zusätzlichen Zugriff. Die Bundle-Grenze überschreibt diese allgemeinen Regeln. Das ist notwendig, weil importierte Datensätze derzeit häufig dem technischen Benutzer `1` gehören und öffentliche Flags den Bundle-Schutz sonst umgehen würden.

Besitzt ein Material mehrere Bundle-Zuordnungen, genügt die Permission für eines dieser Bundles. Der Inhalt ist dann über die erlaubte Quelle legitim sichtbar.

#### Direkt importierte Bundle-Resource

Eine Resource ist bundlegebunden, sobald mindestens eine `ForeignResourceId` mit nicht-leerer `bundle_id` existiert.

Eine bundlegebundene Resource ist sichtbar, wenn mindestens eine Bedingung erfüllt ist:

1. der Benutzer ist Global-Admin;
2. der Benutzer besitzt `resources.view-all`;
3. der Benutzer besitzt für mindestens eines der zugeordneten Bundles `bundles.view.<uuid>`.

`resources.created_by` und `resources.is_public` überschreiben die Bundle-Grenze nicht. Besitzt eine Resource mehrere Bundle-Zuordnungen, genügt die Permission für eines dieser Bundles.

Eine lediglich manuell an ein Bundle-Material angehängte, aber nicht über `ForeignResourceId.bundle_id` importierte Resource bleibt eine normale Resource und folgt dem bestehenden Resource-Vertrag. Beim Laden eines Materials wird sie zusätzlich durch `Resource::visibleTo($user)` gefiltert.

#### Nicht bundlegebundene Datensätze

Für Materialien und Ressourcen ohne Bundle-Foreign-ID bleiben die vorhandenen Regeln aus `MaterialPolicy`, `ResourcePolicy` und den `visibleTo`-Scopes unverändert.

#### Inaktive Benutzer und Nichtoffenlegung

- `invited` und `suspended` sehen keine Inhalte, auch wenn Rollen oder direkte Permissions vorhanden sind.
- Direkte nicht erlaubte Zugriffe antworten wie bisher mit `404`, nicht `403`, damit die Existenz nicht offengelegt wird.
- UI-Ausblendungen sind nur Bedienhilfe; Policies und Query-Scopes bleiben maßgeblich.

### Query-Architektur

- `MaterialPolicy::view()` und `ResourcePolicy::view()` bleiben für Einzelobjekte maßgeblich.
- `Material::visibleTo(User)` und `Resource::visibleTo(User)` setzen dieselbe Semantik für Collections um.
- Kein auth-abhängiger Global Scope.
- Kein Laden aller Bundle-Relationen und anschließendes Filtern in PHP.
- Ein fokussierter `BundleVisibilityService` oder Query-Helper löst die für den Benutzer erlaubten Bundle-IDs aus seinen effektiven Spatie-Permissions auf.
- Permission-Namen werden serverseitig anhand des festen Präfixes und tatsächlich vorhandener Bundles interpretiert; keine Requestwerte werden als Permission-Namen oder SQL-Identifier übernommen.
- SQL verwendet `EXISTS`/`whereHas` auf Foreign-ID und Bundle, qualifizierte Spaltennamen und gebundene Werte.
- Policies und Scopes erhalten einen gemeinsamen Matrix-Test, damit sie nicht auseinanderlaufen.
- Suche, Pagination, Counts und Eager Loads wenden die Scopes vor `paginate()` an.
- Material-Payloads laden nur `resources.visibleTo($user)`; Resource-Payloads laden nur `materials.visibleTo($user)`.

### Rechtekatalog und Gruppenverwaltung

`SystemPermissions::all()` bleibt der Katalog statischer Systemrechte. Dynamische Bundle-Permissions werden nicht als Konstanten ergänzt.

`AdminPermissionController@index` liefert zusätzlich dynamische Einträge mit stabiler Struktur:

```json
{
  "code": "bundles.view.<uuid>",
  "area": "bundle-read",
  "bundle": {
    "id": 7,
    "uuid": "<uuid>",
    "name": "Basis-Paket",
    "is_installed": true
  }
}
```

`AdminGroupController` akzeptiert:

- statische Werte aus `SystemPermissions::all()`;
- dynamische Werte nur, wenn eine Permission mit Guard `web`, Präfix `bundles.view.` und passendem Bundle-Datensatz existiert.

Beliebige Datenbank-Permissions oder fremde Guards dürfen nicht über den Request zugewiesen werden.

Die bestehende Admin-UI zeigt Bundle-Leserechte in einem eigenen Fieldset „Bundle-Leserechte“, beschriftet mit Bundle-Name, Version und installiert/deinstalliert. Technische Permission-Codes dürfen ergänzend, aber nicht als einzige verständliche Beschriftung erscheinen. Das ist eine lokale Erweiterung des vorhandenen Gruppenformulars; Navigation, Farben und Layoutgrundsätze bleiben unverändert.

### Bestandskompatibilität beim Rollout

**Entscheidung vom 18. September 2026: Option B – deny by default.** Für bereits bekannte Bundles werden keine Rollen oder Benutzer automatisch mit einer neuen Bundle-Permission ausgestattet. Nach dem Deployment sind Bundle-Inhalte für Nicht-Administratoren erst sichtbar, nachdem ein Global-Admin das jeweilige Leserecht ausdrücklich einer Gruppe oder einem Benutzer zugewiesen hat. Die Permission selbst wird bei Discovery angelegt und bei Deinstallation erhalten.

**Empfehlung:** Für bei Migration bereits bekannte Bundles wird die neue Permission einmalig folgenden Empfängern zugeordnet:

- Rollen mit `materials.view-public` oder `materials.view-all`;
- Benutzern mit einer entsprechenden direkten Permission;
- `resources.view-all` benötigt keine Bundle-Permission, weil es als dokumentierter globaler Override bestehen bleibt.

Damit bleibt der bisherige Materialzugriff weitgehend erhalten; Administratoren können Bundle-Rechte anschließend gezielt entziehen. Neu entdeckte Bundles erhalten die Permission sicherheitsorientiert ohne automatische Gruppenzuordnung.

Alternative B wäre „deny by default auch für den Bestand“. Das ist sicherer, blendet aber nach Deployment sofort alle Bundle-Inhalte für Nicht-Admins aus. Vor Implementierung ist ausdrücklich Option A oder B zu bestätigen.

Der Backfill läuft als idempotenter, separat testbarer Command/Service nach der Schema-Migration und nicht als umfangreiche Datenmanipulation innerhalb der Migration. Er bietet `--dry-run`, meldet ausschließlich Counts und Bundle-UUIDs, keine Nutzer- oder Materialdaten. Produktion verlangt Backup, Dry-Run und dokumentierte Freigabe vor dem schreibenden Lauf.

## Weitere verpflichtende Korrekturen

### Services und Controller

- `BundleImportController` wird auf HTTP-Koordination reduziert.
- Lauf-Erstellung, Phasenwechsel, Batch-Erzeugung, Reconciliation und Retry liegen in fokussierten Services/Actions.
- `resolve(BundlesService::class)` innerhalb von `BundlesService` entfällt; Abhängigkeiten werden injiziert oder interne Methoden direkt verwendet.
- Leere `catch`-Blöcke entfallen. Erwartbare Fehler werden als domänenspezifische Exceptions mit stabilen Codes behandelt.
- Fehlende Bundle-Icons antworten explizit mit `404`.
- Read-only Index-/Statusaktionen verändern nicht beiläufig Importstatus. Discovery-Synchronisierung wird als benannte Operation gekapselt und getestet.

### Modelle und Beziehungen

- Beziehungen erhalten konkrete Laravel-Returntypes.
- Der Tippfehler `foreignMaterialds()` wird durch `foreignMaterialIds()` ergänzt. Vor Entfernen des alten Namens ist ein Repositoryscan erforderlich; falls externe Nutzung nicht ausgeschlossen werden kann, bleibt eine temporäre deprecated Alias-Methode.
- Geschützte Attribute und bestehende STI-Typcodes bleiben unverändert.
- `BundleImportRun` verwendet Enums oder zentrale Konstanten für Operation, Status und Phase; keine freien Strings in Controllern/Jobs.

### Events, Transaktionen und Caches

- Events, deren Listener committed Daten erwarten, werden nach Commit ausgelöst.
- Attach-/Detach-Deltas werden vor `sync()` ermittelt und danach gezielt als Events veröffentlicht.
- Queued Lonely-Checks und Duplicate-Checks werden erst nach erfolgreichem Commit sichtbar.
- Ein Rollback darf keine Erfolgs- oder Cachezustände hinterlassen, die committed Daten vortäuschen.

### Beobachtbarkeit und Aufbewahrung

- Logs enthalten `run_id`, `bundle_id`, `operation`, `phase`, `batch_id`, `job_class`, `entity_key` und `failure_code`.
- Keine absoluten Pfade, SQLite-Inhalte, Materialbeschreibungen, Dateiinhalte oder Benutzer-E-Mails loggen.
- Ein lesender Admin-Endpunkt darf den Laufverlauf eines Bundles liefern.
- `job_batches` werden nicht während eines aktiven oder referenzierten Runs bereinigt.
- Ein geplanter Prune-Befehl darf erst später ergänzt werden. Dann muss er nur Batches entfernen, deren referenzierende Runs terminal und älter als die festgelegte Aufbewahrung sind.

## Öffentliche Verträge und Kompatibilität

Folgende Verträge bleiben erhalten:

- API-Präfixe `v1`/`v2`;
- Passport als `auth:api`-Guard;
- `bundles.manage` für Bundle-Verwaltungsaktionen;
- Queue-Namensformat `bundle_{id}_queue`;
- Resource-STI-Typcodes;
- Foreign-ID-Grundidee und persistente Bundle-Dateipfade;
- Material-Pivots `relevance` und `limitation`;
- vorhandene Eventklassen und Preview-Invalidierung;
- Bundle-Permissions verwenden Guard `web`, weil dies dem vorhandenen User-/Rollenmodell entspricht; Passport-authentifizierte Requests autorisieren weiterhin denselben User mit dessen Web-Guard-Permissions.

Kompatibel erweitert werden die Bundle-API-Antworten um Runstatus und Fehlerstruktur. Entfernen oder Umbenennen vorhandener Erfolgsfelder ist nicht erlaubt, bis Frontend und externe Verbraucher inventarisiert und separat freigegeben wurden.

## Geplante Datei- und Klassenstruktur

Die konkrete Benennung darf nur angepasst werden, wenn ein bereits etabliertes gleichwertiges Projektmuster gefunden wird. Erwartete Struktur:

```text
app/
├── Console/Commands/WorkBundleQueue.php
├── Enums/BundleImportOperation.php
├── Enums/BundleImportPhase.php
├── Enums/BundleImportStatus.php
├── Exceptions/BundleImport/*.php
├── Jobs/Bundle/
│   ├── ValidateBundleChunk.php
│   ├── DeleteMaterialIfNeeded.php
│   ├── DeleteResourceIfNeeded.php
│   ├── InsertOrUpdateResource.php
│   ├── InsertOrUpdateMaterial.php
│   └── FinalizeBundleImport.php
├── Models/BundleImportRun.php
├── Services/Bundles/
│   ├── BundleImportOrchestrator.php
│   ├── BundleImportRunService.php
│   ├── BundleImportStatusService.php
│   ├── BundleQueueRunner.php
│   ├── BundleSourceValidator.php
│   ├── BundlePermissionService.php
│   └── BundleVisibilityService.php
└── Support/Authorization/BundlePermissionName.php
```

DTOs dürfen unter einem vorhandenen Projektpfad oder `app/Data/Bundles/` liegen. Sie enthalten nur serialisierbare Skalare/Arrays und validieren ihre eigene Form nicht erneut; die Validierung gehört in den Source Validator.

## Ausführungspakete für ein Terra-Modell

Jedes Arbeitspaket beginnt mit `git status --short`, einer erneuten Lektüre der berührten Dateien und der Prüfung auf fremde Änderungen. Es endet mit kleinstmöglichen Tests. Kein Paket darf bei einem Stop-Kriterium eigenmächtig eine Alternative wählen.

### AP 0 – Vertrag bestätigen und Baseline sichern

**Ziel:** Entscheidungen und Ausgangszustand fixieren.
**Änderungen:** nur Dokumentation/Tests zur Charakterisierung, noch keine Produktionslogik.
**Aktionen:**

1. Option A/B für Rechte-Backfill bestätigen.
2. Foreign-ID-Eindeutigkeitsraum per read-only Counts und synthetischen Fixtures prüfen.
3. Bundle-UUID-Bestand auf null/leer/doppelt prüfen.
4. Bestehende API-Payloads, Routen und Browserabläufe als Charakterisierungstests festhalten.
5. Produktionsvertrag für gleichzeitige Browser-/Terminal-Worker als geplante Abweichung markieren.

**Stop:** UUID- oder Foreign-ID-Konflikte, die Datenkorrektur benötigen.
**Abnahme:** keine Datenmutation; Bericht mit Counts ohne Nutzdaten.

### AP 1 – Lauf- und Batchschema

**Ziel:** persistente Grundlage ohne Umschalten der alten Runtime.
**Änderungen:** neue `job_batches`- und `bundle_import_runs`-Migrationen, Model, Enums, Factory.
**Aktionen:** reversible Migrationen; Indizes und Active-Slot-Invariante; keine bestehende Migration ändern.
**Tests:** Fresh-Migration, bestehendes Schema, Rollback in isolierter Testdatenbank, zweiter aktiver Run wird von DB abgelehnt, mehrere terminale Runs sind erlaubt.
**Stop:** inkompatibler Bundle-ID-Typ oder bestehende kollidierende Tabelle.

### AP 2 – Source Validator und deterministische Queries

**Ziel:** vollständige Vorabvalidierung vor Domänenmutation.
**Änderungen:** `BundlesService`, Validator, DTOs, isolierte SQLite-Fixtures.
**Tests:** gültiges Bundle; fehlende Tabelle/Spalte/Datei; Traversal-Pfad; unbekannter MIME-/Metadatentyp; kaputte Relation; stabile Pagination über mehr als 100 Datensätze; unveränderter Fingerprint.
**Stop:** reales Bundleformat enthält absichtlich derzeit als ungültig definierte Werte; dann Vertrag ergänzen statt still tolerieren.

### AP 3 – Orchestrator und Laravel-Batches

**Ziel:** phasenweise, beobachtbare Orchestrierung.
**Änderungen:** Run-Service, Orchestrator, Batch-Callbacks, Reconciliation.
**Tests:** exakte Phasenreihenfolge; `then` nur bei Erfolg; `catch` bei erstem terminalen Fehler; `finally` markiert nie Erfolg; doppelte Callbacks dispatchen keine Doppelbatches; leerer Batch wechselt korrekt weiter.
**Stop:** wenn das lokal installierte Laravel-Verhalten bei Batches in Chains/Callbacks vom Vertrag abweicht; dann zuerst mit minimalem Framework-Integrationstest belegen und Vertrag anpassen.

### AP 4 – Idempotente und überlappungsfreie Jobs

**Ziel:** sichere parallele Worker.
**Änderungen:** alle Bundle-Jobs, Entity-Locks, Retry/Timeout/Failure-Kontext.
**Tests:** zwei Worker/Jobinstanzen für dieselbe Entity; unterschiedliche Entities parallel; Job zweimal nacheinander; Worker-Abbruch nach Commit vor Acknowledge; Retry nach transientem Fehler; finaler Fehler.
**Stop:** notwendige automatische Bestands-Deduplizierung.

### AP 5 – Domain-Events, Zeitstempel und Pivots

**Ziel:** vollständiger bestehender Lebenszyklusvertrag.
**Änderungen:** Material-/Resource-Upserts, Attach-/Detach-Deltas, after-commit Events, Zeitstempel, Icon-ID.
**Tests:** Create/Update/no-op; Relevanz und Limitation; Cache-Invalidierung; Hash/Filesize/Medienmetadaten; Lonely-Checks; Transaktionsrollback erzeugt keine committed Side Effects.
**Stop:** wenn das gewünschte Eventverhalten bestehende fachliche Daten löscht oder neue Merge-Semantik verlangt.

### AP 6 – API, Browser-Pump und Terminal-Worker

**Ziel:** fortsetzbarer Lauf mit zwei gleichzeitigen Konsumenten.
**Änderungen:** dünner Controller, Statusendpunkt, Queue Runner, Console Command, Routes.
**Tests:** Authentifizierung, Aktivstatus, `bundles.manage`, 202/200/409/422/404, Browser-Pump plus Terminal-Runner, Browser-Pause, spätere Wiederaufnahme, keine falschen Done-Zähler.
**Stop:** benötigte Änderung vorhandener Route oder Entfernung eines Responsefelds.

### AP 7 – Frontend-Status und Fehlerbehandlung

**Ziel:** Serverstatus statt lokaler Erfolgsschätzung.
**Änderungen:** Bundle-Pinia-Store, Bundle-Komponente, Übersetzungen, vorhandene Browserfixtures.
**Tests:** Store rethrowt Fehler; Polling endet bei `failed`; Reopen zeigt aktiven Lauf; Pause stoppt nur Browser-Pump; Terminalfortschritt erscheint; responsive/Keyboard-/Loading-/Empty-/Error-Zustände.
**Stop:** komponentenübergreifendes Redesign.

### AP 8 – Deinstallation ohne Quelle und Recovery

**Ziel:** vollständige Entfernung der Bundle-Zuordnungen auch bei fehlender SQLite-Datei.
**Änderungen:** Uninstall-Erzeugung ausschließlich aus persistierten Foreign-IDs und Bundle-Metadaten; fehlende Quellen bleiben in Adminliste sichtbar.
**Tests:** Quelle vorhanden/fehlend/beschädigt; geteiltes und bearbeitetes Material; geteilte/angehängte Resource; Permission bleibt erhalten.
**Stop:** unklare Eigentümerschaft einer lokalen Datei außerhalb der Bundle-Disk.

### AP 9 – Bundle-Permissions Backend

**Ziel:** sichere Sichtbarkeit in Policy und Query.
**Änderungen:** Permission Helper/Service, dynamischer Katalog, Group-Validierung, Material-/Resource-Policies und Scopes, alle lesenden Queries.
**Tests:** vollständige Berechtigungsmatrix, Multi-Bundle-OR, Global Overrides, inaktive User, 404, Suche/Counts/Pagination, Relationen, Downloads, Streams, Previews, API und klassische Seiten.
**Stop:** UUID-Konflikt oder ein nicht autorisierter Read-Pfad ohne zentrale Policy/Scope-Grenze.

### AP 10 – Rechte-UI und Bestandsbackfill

**Ziel:** Rechte verständlich vergeben und bestehende Zuordnungen erhalten.
**Änderungen:** Admin-Permission-Payload, Gruppenformular, Übersetzungen, Backfill-Command mit Dry-Run.
**Tests:** nur gültige dynamische Permissions akzeptiert; deaktiviertes Bundle sichtbar; Reimport gleiche Permission; Deinstallation löscht keine Zuweisung; gewählte A/B-Backfill-Semantik.
**Stop:** Backfill würde Rechte erweitern, die vorher nicht bestanden.

### AP 11 – Produktionsvertrag und vollständige Abnahme

**Ziel:** dokumentierter sicherer Betrieb.
**Änderungen:** Architektur-, Domain-, Quality- und Production-Dokumentation; gegebenenfalls Supervisor-/Betriebshinweis, aber kein zusätzlicher permanenter Worker ohne eigene Freigabe.
**Tests:** vollständige Gates unten; Restore-/Rollbackplan; `job_batches`-Aufbewahrung.
**Stop:** produktionsnahe Queue-/Lock-Konfiguration weicht von den verifizierten Annahmen ab.

## Verbindlicher Testplan

### Queue- und Batchtests

- Ein Init-Request erzeugt genau einen aktiven Run und genau einen Validation-Batch.
- Zwei gleichzeitige Init-Requests liefern denselben Run; die Datenbank enthält keinen zweiten aktiven Run.
- Update und Uninstall können nicht gleichzeitig aktiv sein.
- Zwei Worker reservieren nicht denselben Queue-Datensatz.
- Zwei logisch doppelte Jobs derselben Entity laufen durch `WithoutOverlapping` nicht gleichzeitig.
- Eine sequenzielle Zweitausführung verändert Counts und Pivots nicht erneut.
- Ein Resource-Job-Fehler verhindert jeden Material-Batch.
- Ein Material-Job-Fehler verhindert die Finalisierung.
- `failed_jobs` beziehungsweise `failedJobs>0` kann nie zu `succeeded` führen.
- Browser-Pump und Terminal-Worker verarbeiten verschiedene Jobs desselben Batches und schließen korrekt ab.
- Nach Browserabbruch bleiben Run, Batch und Queue erhalten; Terminal oder späterer Browser schließen ab.
- Retry nach Worker-Abbruch zwischen Commit und Acknowledge bleibt idempotent.
- Ein verzögerter Overlap-Job überschreitet nicht seine Retry-Grenze.
- Leere Delete- oder Upsert-Phasen wechseln korrekt weiter.
- Doppelte `then`-, `catch`- und `finally`-Callbacks sind idempotent.

### Import-Funktionstests

- Erstinstallation eines repräsentativen Bundles mit File-, Text- und mindestens einem medienbezogenen Resource-Typ.
- Update mit neuem, geändertem, unverändertem und entferntem Material/Resource.
- korrekte Foreign-ID-, Bundle-ID- und Zeitstempelwerte.
- Keywords aller vier Typen, Autor und Bibelstelle inklusive Relevanz.
- Material-Resource-Pivot inklusive `limitation` bleibt vertragsgemäß.
- Resource-Hash, Filesize, Seitenzahl/Dauer soweit Typ zutrifft.
- Resource-Typwechsel liefert dokumentierten Fehler oder vollständig getesteten Ersatz.
- fehlende Datei, unbekannter MIME-Typ, falsches Schema und Path Traversal verändern keine Domainmodelle.
- Update mit unverändertem Bundle ist no-op und erzeugt keinen neuen Lauf.
- Preview-Caches werden bei Änderungen invalidiert, bei no-op nicht unnötig.

### Deinstallationstests

- normale Deinstallation;
- Deinstallation ohne Bundle-Verzeichnis und ohne SQLite-Datei;
- bearbeitetes Material bleibt, Bundle-Foreign-ID verschwindet;
- ausschließlich bundleeigenes Bot-Material wird gelöscht;
- geteiltes Material beziehungsweise geteilte Resource bleibt;
- lokale Bundle-Quelldateien bleiben unberührt;
- Permission und Rollen-/Nutzerzuordnung bleiben erhalten;
- Reimport derselben UUID reaktiviert dieselbe Permission.

### Autorisierungs- und Leckagetests

Für Material und Resource ist jeweils die Matrix auszuführen:

| Benutzer | Bundle-Permission | Global Override | Erwartung |
| --- | --- | --- | --- |
| aktiv, normale Rolle | nein | nein | nicht in Collection; direkter Zugriff 404 |
| aktiv, normale Rolle | richtiges Bundle | nein | sichtbar |
| aktiv, normale Rolle | anderes Bundle | nein | nicht sichtbar |
| aktiv, mehrere Bundle-Zuordnungen | eine passende | nein | sichtbar |
| aktiv | nein | `materials.view-all` bzw. `resources.view-all` | sichtbar |
| Global-Admin aktiv | nein | Admin | sichtbar |
| Global-Admin suspendiert | ja | Admin | nicht sichtbar |

Zusätzlich:

- `is_public=true` umgeht Bundle-Recht nicht;
- technischer Eigentümer `created_by=1` gewährt keinem anderen Benutzer Zugriff;
- Suche, Pagination-Total, Filter, Sortierung und Counts verraten keinen unsichtbaren Datensatz;
- Material-Payload enthält keine unsichtbare Resource;
- Resource-Payload enthält kein unsichtbares Material;
- Classic Show/Download, MediaStream, PDF-Seiten-Download, Resource-Preview, Material-Preview und API-Endpunkte prüfen dieselbe Policy;
- ungültiger dynamischer Permission-Code wird mit Validierungsfehler abgelehnt;
- Permission eines deinstallierten Bundles bleibt im Admin-Katalog auswählbar;
- direkte Permission und rollenbasierte Permission funktionieren gleich;
- Spatie-Permission-Cache wird nach Zuweisungsänderung korrekt invalidiert.

### Frontendtests

- Store-Methoden lehnen bei HTTP-Fehlern ab und bewahren die strukturierte Meldung.
- Ein aktiver Run wird nach Reload wieder angezeigt.
- Fortschritt stammt aus dem Statusendpunkt.
- `failed` zeigt Fehler und keinen 100%-Erfolg.
- Browser-Pause erzeugt keinen Cancel-Request und behauptet keine serverseitige Stornierung.
- Gruppenformular gruppiert Bundle-Rechte verständlich und speichert sie.
- deinstalliertes Bundle bleibt als Recht erkennbar.
- Desktop und Mobile, Tastaturfokus, Lade-, Leer- und Fehlerzustand werden geprüft.

## Verifikationsbefehle

Vor jedem destruktiven Test wird die dedizierte Testdatenbank wie in [Qualitätssicherung](quality-gates.md) verifiziert. Erwartete Gates nach vollständiger Umsetzung:

```bash
./vendor/bin/sail php -v
./vendor/bin/sail artisan --version
./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan config:show database --env=testing

./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan migrate:fresh --env=testing --force
./vendor/bin/sail test --filter BundleImport
./vendor/bin/sail test --filter BundleVisibility
./vendor/bin/sail test --filter MaterialResourcePolicyTest
./vendor/bin/sail test --filter MaterialVisibilityEndpointsTest
./vendor/bin/sail test --filter BundleBibleBackupContractTest
./vendor/bin/sail test

./vendor/bin/sail composer validate --strict
./vendor/bin/sail composer audit --locked

npm run lint
npm run test:unit
npm run build
npm run test:e2e
npm run test:visual
```

Zusätzlich sind auszuführen:

- PHP-Syntax aller geänderten PHP-Dateien im PHP-8.4-Container;
- `php artisan route:list --json` und Vergleich der bestehenden Routensignaturen;
- `php artisan event:list` beziehungsweise bestehende Event-Vertragstests;
- isolierter Test mit zwei gleichzeitig laufenden Database-Queue-Workern;
- `EXPLAIN` für Material-/Resource-Listen und Suche mit bundlebezogener Sichtbarkeit auf repräsentativen synthetischen Daten;
- Production-Preflight und Queue-Smoke-Test gemäß Produktionsvertrag;
- Rollback der neuen Migrationen ausschließlich in einer isolierten Testdatenbank.

## Deployment- und Rollbackvertrag

### Deploymentreihenfolge

1. vollständiges verschlüsseltes Backup und isolierter Restore-Nachweis;
2. read-only Preflight für Bundle-UUIDs und Foreign-ID-Constraints;
3. Code bereitstellen, der altes und neues Schema während der Migrationsphase sicher erkennt;
4. neue Migrationen für `job_batches`, `bundle_import_runs` und freigegebene Constraints ausführen;
5. Permission-Dry-Run protokollieren;
6. gewählten Permission-Backfill explizit starten;
7. Cache/Config/Event/Route optimieren und Queue-Worker neu starten;
8. Import eines isolierten Testbundles, Browser-/Terminal-Paralleltest, Rechte-Smoke-Test;
9. erst danach normale Bundle-Updates freigeben.

Während Migration oder Rollback dürfen keine Bundle-Importe laufen. Vor Schemawechsel werden aktive `bundle_{id}_queue`-Jobs inventarisiert. Sie dürfen nicht per pauschalem `DELETE FROM jobs` entfernt werden. Entweder werden sie unter dem alten Release beendet oder das Deployment wird verschoben.

### Rollback

- Anwendung in Wartungsmodus, Browser-Pumps stoppen und alle Bundle-/Default-Worker kontrolliert beenden.
- Bei bereits geschriebenen Domainänderungen ist Code-Rollback allein nicht ausreichend. Datenbank und persistente Dateien werden aus dem verifizierten Backup wiederhergestellt.
- `job_batches` und `bundle_import_runs` dürfen nur in der isolierten Rollback-Probe durch Migration-Down entfernt werden.
- Dynamische Bundle-Permissions werden bei normalem Code-Rollback nicht automatisch gelöscht; ihre Zuordnungen bleiben sicher erhalten. Eine spätere Bereinigung benötigt eigenen Auftrag.
- Vorheriges Release aktivieren, `optimize`, Worker neu starten und Sichtbarkeits-/Import-Smoke-Tests ausführen.

## Stop-Kriterien für die Umsetzung

Das ausführende Modell stoppt und berichtet mit konkreten Datenformen, aber ohne Nutzdaten, wenn mindestens einer dieser Fälle eintritt:

- Bundle-UUIDs sind leer oder doppelt;
- Foreign-UUIDs kollidieren entgegen dem angenommenen Eindeutigkeitsraum;
- eine notwendige Datenkorrektur oder Zusammenführung wird sichtbar;
- ein neues Package oder Queue-Backend wäre erforderlich;
- ein bestehender v1-/v2-Payload müsste inkompatibel geändert werden;
- der importierte Bundle-Datensatz enthält absichtlich bislang unbekannte Typen;
- eine bestehende Resource müsste mit möglichem Dateiverlust ersetzt werden;
- ein Queue-/Event-Test zeigt, dass eine Nebenwirkung vor Commit erforderlich ist;
- die neue Sichtbarkeitsregel würde einen nicht inventarisierten Download-, Stream-, Preview- oder Exportpfad offenlassen;
- der Permission-Backfill würde Rechte erweitern statt lediglich bisherigen Zugriff abzubilden;
- Produktion verwendet keinen zwischen Browser und Terminal geteilten Lock-fähigen Cachepfad;
- fremde Änderungen überschneiden sich mit den zu ändernden Dateien.

## Entscheidung und benötigte Freigabe

### Bereits durch den Auftrag vorgegeben

- Laravel-Batches mit `then`/`catch`/`finally`, wo fachlich möglich;
- gleichzeitige Verarbeitung durch Browser und Terminal;
- keine gleichzeitige Doppelausführung desselben logischen Jobs;
- Wiederaufnahme nach geschlossenem Browser;
- vollständige Umsetzung der übrigen im Review genannten Korrekturen;
- betriebsrelevante Tests;
- Bundle-Permissions bleiben nach Deinstallation erhalten.

### Noch zu bestätigen

1. **Bundle-Rechtemodell:** Option A, dynamische Spatie-Permission `bundles.view.<uuid>`.
2. **Strikte Sichtbarkeit:** Bundle-Recht überschreibt Eigentümer- und Public-Regeln; `materials.view-all`/`resources.view-all` und aktive Global-Admins bleiben globale Overrides.
3. **Bestandsbackfill:** **Entschieden: Alternative B** – deny by default auch für den Bestand; es findet kein automatischer Berechtigungs-Backfill statt.
4. **Foreign-ID-Scope:** Empfehlung bundlebezogene Eindeutigkeit `(bundle_id, foreign_id)`, vorbehaltlich des AP-0-Nachweises.
5. **Datenmodell und Migrationen:** `job_batches`, `bundle_import_runs`, Bundle-UUID-Constraint und gegebenenfalls Foreign-ID-Constraints wie beschrieben.

Nach Bestätigung dieser fünf Punkte gilt der Vertrag als freigegeben. Jede Abweichung während der Umsetzung wird als neue Entscheidung vorgelegt; sie darf nicht stillschweigend implementiert werden.

## Abschlusscheckliste

- [ ] Gesamtentscheidung freigegeben.
- [ ] UUID- und Foreign-ID-Preflight konfliktfrei.
- [ ] Standard-`job_batches` und `bundle_import_runs` migriert und rollback-geprüft.
- [ ] exakt ein aktiver Run pro Bundle auf Cache- und DB-Ebene abgesichert.
- [ ] Validation-, Delete-, Resource-, Material- und Finalisierungsphasen als Batches umgesetzt.
- [ ] `then`/`catch`/`finally` idempotent getestet.
- [ ] Entity-Jobs überlappungsfrei und sequenziell idempotent.
- [ ] Browser- und Terminal-Worker parallel getestet.
- [ ] Browserabbruch und Wiederaufnahme getestet.
- [ ] kein Teilfehler kann Erfolgsstatus setzen.
- [ ] Deinstallation ohne Bundle-Quelle möglich.
- [ ] Events, Caches, Dateien, Foreign-IDs und Pivots geprüft.
- [ ] Zeitstempel, Icon-Vergleich, Pagination und Fehlerstatus korrigiert.
- [ ] dynamische Bundle-Permissions erstellt und dauerhaft erhalten.
- [ ] Policies und Scopes besitzen identische Berechtigungsmatrix.
- [ ] alle Read-/Download-/Stream-/Preview-/Suchpfade abgesichert.
- [ ] Admin-UI kann Bundle-Rechte verständlich vergeben.
- [ ] gewählter Bestandsbackfill per Dry-Run und Test bestätigt.
- [ ] gezielte und vollständige Backend-/Frontend-Gates grün.
- [ ] Produktionsvertrag, Betriebshandbuch und Rollback aktualisiert.

## Abschlussbedingung

Ein grüner Happy-Path-Import genügt nicht. Das Vorhaben ist erst abgeschlossen, wenn Parallelität, Retry, Teilfehler, Browserabbruch, fehlende Bundle-Quelle, Deinstallation, Reimport und die vollständige Berechtigungsmatrix automatisiert nachgewiesen sind und keine offene Stop-Bedingung besteht.

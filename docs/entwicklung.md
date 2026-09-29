# Entwicklung

[Zur Übersicht](../README.md)

## Inhalt

- [Lokaler Start](#lokale-voraussetzungen-und-start) und [Vue-3-Entwicklung](#vue-3-entwicklung)
- [Kontextsuche](#kontextsuche), [Indexierung](#manuelle-indexierung-von-pdf--und-textressourcen-stufe-1) und [Evaluationsdatensätze](#evaluationsdatensätze-aus-produktion)
- [Tinker, Shell und Artisan](#tinker-shell-und-artisan)
- [Tests](#tests-und-isolierte-datenbank) und [Fehlersuche](#troubleshooting)

## Lokale Voraussetzungen und Start

- Docker mit Compose-Unterstützung;
- Composer zum initialen Aufbau von `vendor/` oder ein kontrollierter Composer-Container;
- Node.js gemäß [`.node-version`](../.node-version), aktuell 24.21.0;
- npm gemäß `package.json`, aktuell 11.19.0.

Abhängigkeiten werden aus `composer.lock` und `package-lock.json` installiert. Keine Updates als Nebeneffekt eines lokalen Starts durchführen.

```sh
composer install
test -f .env || cp .env.example .env
npm ci --ignore-scripts
./vendor/bin/sail up -d
```

Nur bei einer frisch angelegten `.env` ohne `APP_KEY` einmal `./vendor/bin/sail artisan key:generate` ausführen. Bei bestehenden verschlüsselten Daten den vorhandenen Schlüssel übernehmen und nicht ersetzen. `composer install` setzt PHP 8.4 mit den benötigten Erweiterungen voraus; ist das lokal nicht verfügbar, Composer in einem passenden PHP-8.4-Container ausführen. Das ältere Host-PHP ist keine gültige Referenz für das Projekt.

Die lokale `.env` wird nicht versioniert. Docker Compose liest aus ihr die Werte für MySQL und Qdrant; Laravel verwendet dieselbe Datei über das eingebundene Projektverzeichnis. `DB_HOST=mysql`, `DB_DATABASE`, `DB_USERNAME` und `DB_PASSWORD` müssen zur lokalen MySQL-Instanz passen. Eine zusätzliche `.env.local` ist dafür nicht erforderlich. Entwicklungsdaten und Testdaten bleiben getrennt. Anschließend das Entwicklungssystem starten:

```sh
./vendor/bin/sail artisan dev
```

Laravel 13 startet damit standardmäßig Server, Queue-Listener, Logansicht und `npm run dev` für Vite. `npm run dev` generiert zuerst die JavaScript-Übersetzungen und startet danach Vite mit HMR.

## Kontextsuche

Die Kontextsuche wird gemäß dem [Planungs- und Arbeitsvertrag](ai/context-search-ai-contract.md) auf Qdrant aufgebaut. Der lokale Compose-Stack verwendet die fest gepinnte Qdrant-Version 1.19.1. Die REST-Schnittstelle wird nur an `127.0.0.1` veröffentlicht und intern mit `QDRANT_API_KEY` geschützt. Der mitgelieferte Schlüssel ist ausschließlich für lokale Entwicklung bestimmt; produktive Schlüssel werden über die Server-Secret-Konfiguration gesetzt und Qdrant wird dort nur über ein abgesichertes privates Netz beziehungsweise TLS erreicht.

Bei einer bereits vorhandenen lokalen `.env` müssen die `QDRANT_*`, `CONTEXT_SEARCH_*`- und `FORWARD_QDRANT_PORT`-Werte einmal aus `.env.example` übernommen werden. Danach Qdrant und die Anwendung starten und den Healthcheck prüfen:

```sh
docker compose up -d qdrant laravel.test
docker compose ps qdrant
```

Eine leere, versionierte Collection samt Payload-Indizes wird bewusst manuell provisioniert. `profile` ist ein unveränderlicher, kleingeschriebener Profil-Hash; `generation` ist ein eindeutiger technischer Generationsbezeichner. `--activate` schaltet den stabilen Alias atomar auf die neue Collection um:

```sh
./vendor/bin/sail artisan context-search:qdrant:provision \
  a1b2c3d4 20260922t120000z --activate
```

Der Befehl speichert noch keine Ressourcen oder Vektoren. Vor dem Aktivieren einer später befüllten Generation sind die im Vertrag vorgesehenen Qualitäts- und Kapazitätsprüfungen Pflicht. Bis zur Suchintegration bleibt `CONTEXT_SEARCH_ENABLED=false`; die bestehende direkte Suche arbeitet unverändert weiter.

### Ollama-Modellprofil und Pool

Der Kontextsuche-Pool verwendet ausschließlich `CONTEXT_SEARCH_EMBEDDING_MODEL`; ein generatives Modell kann daher nicht versehentlich Suchvektoren erzeugen. Jeder Poolserver muss exakt dieses Modell mit demselben Modell-Digest bereitstellen. Die Serverliste folgt dem Format `name=url|max_parallel_jobs`, mehrere Server werden durch Komma getrennt. Zugangsdaten stehen getrennt in `CONTEXT_SEARCH_OLLAMA_API_KEYS` als `name=secret`-Einträge und gehören ausschließlich in Server-Secrets, nie ins Repository.

Vor einer Indexgeneration ist auf jedem Ollama-Server der Modell-Digest über `GET /api/tags` zu ermitteln und als `CONTEXT_SEARCH_EMBEDDING_DIGEST` zu setzen. Danach prüft der folgende lesende Selbsttest Modellname, Digest und die tatsächlich gelieferte Vektordimension auf allen konfigurierten Servern:

```sh
./vendor/bin/sail artisan context-search:ollama:verify
```

## Manuelle Indexierung von PDF- und Textressourcen (Stufe 1)

Die erste Indexierung ist technisch nur per bewusstem Kommando vorgesehen; Änderungen an Materialien oder Ressourcen lösen keinen Indexlauf aus. **Der bisherige manuelle Worker darf derzeit nicht gestartet werden:** Sein 180-Sekunden-Timeout überschreitet die 150-Sekunden-Reservierungsfrist der Datenbank-Queue. Neue manuelle Index- und OCR-Kalibrierungsläufe dürfen bis zum einmaligen Cutover ebenfalls nicht gestartet werden. Der [Queue-Änderungsvertrag](ai/context-search-queue-change-contract.md) beschreibt die getrennte Connection, begrenzte Seitenjobs und die Abnahme vor Wiederfreigabe. Der frühere Workeraufruf wird deshalb hier nicht mehr als ausführbare Anleitung angeboten.

Die vorbereitete Connection `context_search` verwendet eigene Queue-Namen und `CONTEXT_SEARCH_QUEUE_RETRY_AFTER` (Beispielwert 600 Sekunden); `QUEUE_RETRY_AFTER=150` für normale Jobs bleibt unverändert. Neue Index- und OCR-Kalibrierungsläufe werden jetzt **vor dem Anlegen eines Laufdatensatzes technisch abgewiesen**. Der folgende Befehl prüft die aufgelöste Konfiguration und inventarisiert nur die Anzahl wartender, reservierter und fehlgeschlagener Altaufträge; er startet oder löscht nichts:

Für Schritt 3 ist eine seitenweise Pipeline in Arbeit: Extrahierter Text wird vorübergehend privat unter `storage/app/context-search-ocr-artifacts` abgelegt. Die Dateien sind abgeleitet und vom Backup ausgenommen; fehlen sie nach Bereinigung oder Restore, wird nur die betroffene Seite aus der Originalressource neu extrahiert. Bis die vollständigen OCR-/Last-/Restore-Gates bestanden sind, bleibt die neue Queue weiterhin gesperrt und die Worker-Vorlage deaktiviert.

Zwei additive Folgemigrationen gleichen frühe Dev-Datenbanken an, in denen die Seitenpipeline-Migration bereits als ausgeführt vermerkt war, aber noch `index_revision` beziehungsweise `skip_reasons` fehlten. Frische Installationen besitzen diese Spalten schon; die Folgemigrationen prüfen ihren Bestand und verändern dort nichts. Vor einem freigegebenen Upgrade wie üblich `migrate:status`, Backup/Restore-Nachweis und den tatsächlichen Schemazustand prüfen. Ein lokaler Einzelressourcen-Probelauf ist kein Ersatz für die ausstehende Produktionsabnahme.

```sh
./vendor/bin/sail artisan context-search:queue:check
```

Die Option `--configuration-only` verzichtet auf die lesende Datenbankinventarisierung. Auch eine erfolgreiche Prüfung ist **keine Startfreigabe** für einen Worker. Der Cutover erfolgt erst nach Schritt 3 des Queue-Vertrags.

Die zukünftigen, derzeit deaktivierten Worker-Definitionen und der störungssichere Cutover sind in der [Betriebsanleitung zur Kontextsuche-Queue](ai/context-search-queue-operations.md) beschrieben. Die Vorlage unter `ops/production/materialpool-context-search-workers.conf.example` darf vor der Abnahme von Schritt 3 nicht installiert oder gestartet werden.

Der Laufzustand wird in MySQL gespeichert und ein fehlgeschlagener Lauf kann anhand seiner UUID fortgesetzt werden. Jeder Qdrant-Punkt enthält die Ressourcen-ID, die Dokumentrevision, die PDF-Seite beziehungsweise Textseite sowie Zeichenpositionen; die Originaldatei bleibt außerhalb von Qdrant. Für PDF-Seiten mit zu wenig eingebettetem Text wird Tesseract mit den Sprachpaketen `deu` und `eng` verwendet. Die OCR-Rasterung zielt auf 300 DPI und reduziert die Auflösung bei großen Seiten so, dass das konfigurierte Budget von standardmäßig 12 Millionen Pixeln eingehalten wird. Die tatsächlich verwendete DPI-Zahl steht in den OCR-Metriken; Text und TSV-Konfidenzen entstehen in einem Tesseract-Lauf. Das Sail-Image installiert diese Werkzeuge beim Neuaufbau automatisch; auf Produktionsservern müssen `pdftotext`, `pdfinfo`, `pdftoppm`, `tesseract`, `tesseract-ocr-deu` und `tesseract-ocr-eng` vor dem Start eines Indexworkers verfügbar sein.

Ein abweichender Digest, Modellname oder eine andere Dimension ist ein Konfigurationsfehler: Der Pool stoppt dann, statt Vektoren verschiedener Modelle zu mischen. Bei Netzwerkfehlern, Timeouts, Überlastung oder 5xx-Antworten verteilt er eine Anfrage deterministisch auf den nächsten gesunden Server. Nach den konfigurierbaren Fehlschlägen öffnet der serverbezogene Circuit Breaker zeitweise; jede Serverdefinition besitzt zudem ihr eigenes gemeinsames Parallelitätslimit. Erst der erfolgreiche Selbsttest berechtigt zum Provisionieren und Befüllen einer Indexgeneration.

Die praktische Reihenfolge für OCR-Test, Bewertung und Parameterübernahme steht nach dem Export-/Importablauf unter [OCR testen und Schwellenwerte einstellen](#ocr-testen-und-schwellenwerte-einstellen).

## Evaluationsdatensätze aus Produktion

Kalibrierung, Modellvergleich und Abnahme erfolgen ausschließlich in einer isolierten Evaluationsumgebung. Produktion darf hierfür nur einen eingefrorenen Datensatz erzeugen und als Archiv exportieren; die Befehle rufen weder Ollama noch Qdrant auf. Sie berücksichtigen ausschließlich PDF- und Textressourcen. Die Produktionsdatenbank wird nicht kopiert.

Die Ablage `CONTEXT_SEARCH_EVALUATION_PATH` muss auf beiden Systemen ein privater, nicht durch Nginx erreichbarer Pfad mit restriktiven Rechten sein. Standardmäßig liegt sie unter `storage/app/context-search-evaluation`. Die Übertragung des Archivs ist nach der getroffenen Entscheidung unverschlüsselt zulässig; Archiv- und Manifest-Prüfsumme sind vor dem Import zwingend zu prüfen. Private Inhalte verlangen die sichtbare Freigabe `--include-private` und eine Begründung. Keine Titel oder Inhalte in Shell-Historien, Tickets oder Logs übernehmen.

```sh
# Produktion: ausschließlich inhaltsfreie Größenordnung vor der Auswahl prüfen.
php artisan context-search:dataset:inventory --json
# Produktion: Auswahl anhand bekannter IDs einfrieren.
php artisan context-search:dataset:freeze calibration \
  --materials=101,102,103 --include-private --reason='Kuratiertes Kalibrierungsset'
```

Der `acceptance`-Datensatz ist ein unveränderlicher Holdout: Wird er zur Kalibrierung verwendet, muss ein neuer Abnahmedatensatz erzeugt werden.

Global Admins können die Auswahl außerdem über **KI-Datensätze** im persönlichen Benutzermenü kuratieren. Eine ganze Datensatzkarte ist anklickbar und per Tastatur bedienbar; Icons und Beschriftungen haben einheitliche Abstände. Eine seitenbezogene Sammelaktion fügt alle vollständig auswählbaren Material-/Ressourcenblöcke der sichtbaren Seite hinzu oder entfernt – als jeweils einzige angezeigte Aktion – deren lokale Auswahl. Sie verändert keine Auswahl anderer Seiten und überspringt gesperrte Blöcke. Der Server ergänzt und prüft den vollständigen zusammenhängenden Block aus Materialien und PDF-/Textressourcen verbindlich. OCR darf dieselben vollständigen Blöcke wie Kalibrierung oder Abnahme enthalten; Last und Kapazität dürfen als unabhängige Betriebsprüfungen mit allen anderen Zwecken einschließlich einander überlappen. Kalibrierung und Abnahme bleiben strikt voneinander getrennt; Last-/Kapazitätsmessungen dürfen nicht zur Anpassung semantischer Relevanz oder Schwellenwerte verwendet werden. Der linke Vorschaubereich bleibt beim Scrollen sichtbar, die Aktionen liegen auf dem Bild, und beim Wechsel des Materials wird die vorherige Grafik bis zum Laden der neuen Vorschau durch einen Ladeindikator ersetzt; falls keine Vorschau verfügbar ist, erscheint ein entsprechender Hinweis. Modalvorschauen erlauben das Durchblättern aller bekannten PDF-Seiten; Material- und Ressourcendetails öffnen jeweils in einem neuen Tab. Zugehörigkeiten und Konflikte sind sichtbar. Aus dem aktiven, noch veränderbaren Entwurf können Blöcke nach Bestätigung wieder als ganzer Block entfernt werden. Die Filter einschließlich Bundle beziehungsweise eigene Materialien ohne Bundle sowie die Seitennummer sind in der Adresse enthalten; die Seitennavigation erlaubt Einzelschritte und Zehnersprünge. Ein Server prüft vor dem Speichern die Versionsnummer und die Zweckregeln für Überschneidungen; die Vorschau im Browser ist keine Sicherheitsentscheidung. Die Zweck-Icons zeigen einen zugänglichen Fortschrittsdialog mit Ist-/Sollmengen und Teilquoten. Private Quellen benötigen eine ausdrückliche Auswahl samt Begründung. Erst ein vollständiger Entwurf kann eingefroren und danach exportiert werden.

Ein bereits im Browser vollständig kuratierter Datensatz mit Status `ready` kann bei einem Browser-Timeout über die CLI eingefroren werden. Nach einem Timeout zuerst die Browseransicht neu laden: Der Vorgang könnte trotz unterbrochener Antwort abgeschlossen worden sein. Ohne UUID zeigt der Befehl alle offenen und geschlossenen Datensätze mit Ist-/Sollmengen und Bewertung von Status und Quoten. Im interaktiven Terminal stehen nur formal einfrierbare Datensätze zur Auswahl; die Verfügbarkeit der Quelldateien wird erst beim Einfrieren geprüft. In Skripten oder ohne interaktives Terminal die UUID ausdrücklich angeben. Sie steht nach Auswahl der Datensatzkarte im URL-Parameter `dataset`. `DATENSATZ_UUID` durch die tatsächliche UUID ersetzen:

```sh
# Lokale Sail-Umgebung
./vendor/bin/sail artisan context-search:dataset:freeze-curated
./vendor/bin/sail artisan context-search:dataset:freeze-curated DATENSATZ_UUID
# Produktionsserver ohne Sail, im Anwendungsverzeichnis
php artisan context-search:dataset:freeze-curated
php artisan context-search:dataset:freeze-curated DATENSATZ_UUID
```

Der Befehl verwendet genau die gespeicherten Mitgliedschaften und friert denselben Datensatz mit derselben UUID ein. Er läuft synchron im CLI-Prozess und zeigt während der Verarbeitung der Materialien und Ressourcen einen Fortschrittsbalken; bei vielen PDF-Dateien kann er dennoch längere Zeit benötigen. Er ändert einen `ready`-Datensatz dauerhaft zu `frozen`; ein bereits eingefrorener oder unvollständiger Datensatz wird abgewiesen. Danach UUID und Mengen in der Ausgabe sowie den Status nach Neuladen der Browseransicht kontrollieren. Ein Archiv entsteht erst durch den separaten `context-search:dataset:export`-Befehl. `reconcile-memberships --apply` dient ausschließlich dem Nachtragen fehlender Mitgliedschaften in bereits eingefrorenen Alt-Datensätzen und friert keine Browser-Vorauswahl ein.

### Nach dem Freeze: Archiv exportieren und prüfen

Die folgenden Befehle im Anwendungsverzeichnis auf dem System ausführen, auf dem der Datensatz eingefroren wurde. `UUID_HIER_EINTRAGEN` einmal durch die UUID aus der Freeze-Ausgabe ersetzen. **Entweder** den Sail-Block für die lokale Umgebung **oder** den `php artisan`-Block auf einem Server ohne Sail verwenden. Der Export schreibt das Archiv in die private Evaluationsablage und setzt den Datensatzstatus auf `exported`; bei großen Datensätzen benötigt er Zeit und ausreichend freien Speicherplatz. Er erzeugt keine KI-Auswertung. Export, Prüfung und Import zeigen für ihre Verarbeitungsschritte Fortschrittsbalken; beim Schreiben des ZIP-Archivs kann ein einzelner Schritt länger dauern.

Wer die UUID nicht zur Hand hat, kann den Export stattdessen interaktiv ohne Argument starten. Es werden eingefrorene, noch nicht exportierte Datensätze mit UUID, Zweck und Mengen angezeigt. Ohne interaktives Terminal muss die UUID angegeben werden. Nach der Auswahl die ausgegebene UUID für `verify` verwenden:

```sh
# Lokale Sail-Umgebung
./vendor/bin/sail artisan context-search:dataset:export
```

```sh
# Server ohne Sail
php artisan context-search:dataset:export
```

```sh
# Lokale Sail-Umgebung
DATASET_UUID='UUID_HIER_EINTRAGEN'
./vendor/bin/sail artisan context-search:dataset:export "$DATASET_UUID"
ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
./vendor/bin/sail artisan context-search:dataset:verify "exports/$ARCHIVE_NAME"
```

```sh
# Server ohne Sail
DATASET_UUID='UUID_HIER_EINTRAGEN'
php artisan context-search:dataset:export "$DATASET_UUID"
ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
php artisan context-search:dataset:verify "exports/$ARCHIVE_NAME"
```

Der Export meldet den relativen Archivpfad und die Archiv-Prüfsumme. `ARCHIVE_NAME` ist der Dateiname aus dieser Ausgabe, beispielsweise `calibration-v3-<UUID>.zip`: `v3` bezeichnet die Bearbeitungsrevision des Datensatzes, nicht die Archivformat-Version. `verify` meldet Manifest- und Archiv-Prüfsumme. Die Archiv-Prüfsumme beider Ausgaben muss übereinstimmen. Der relative Pfad `exports/<Zweck>-v<Version>-<UUID>.zip` liegt auf dem Disk `context_search_evaluation`, standardmäßig unter `storage/app/context-search-evaluation/exports/` oder unter dem konfigurierten `CONTEXT_SEARCH_EVALUATION_PATH`. Das Archiv enthält Quelldaten und bleibt in einer privaten, nicht öffentlich erreichbaren Ablage. Die UUID und beide Prüfsummen für die Übergabe festhalten, ohne Dokumenttitel oder Inhalte in Logs oder Tickets zu kopieren.

Ein vertrauenswürdiger Administrator überträgt genau dieses Archiv in den privaten Ordner `incoming/` des Evaluationssystems. Vor dem Import müssen dort **beide** von `verify` ausgegebenen Prüfsummen mit den Werten des Quellsystems übereinstimmen. Der Import ist nur in einer isolierten, ausdrücklich freigegebenen Evaluationsumgebung mit `CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED=true` zulässig; in Produktion ist er gesperrt. Auf einer Evaluationsumgebung mit Sail:

```sh
ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
./vendor/bin/sail artisan context-search:dataset:verify "incoming/$ARCHIVE_NAME"
```

Erst nach dem Vergleich beider Prüfsummen importieren. Die Archiv-Prüfsumme aus dem Export kann optional als zweites Argument übergeben werden; dann vergleicht der Import sie vor dem Schreiben mit der importierten Datei. Ohne zweites Argument importiert er ebenfalls und gibt die berechnete Archiv-Prüfsumme zur nachträglichen Kontrolle aus:

```sh
ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
./vendor/bin/sail artisan context-search:dataset:import "incoming/$ARCHIVE_NAME"

```

Alternativ kann die beim Export notierte Prüfsumme direkt beim Import abgeglichen werden:

```sh
ARCHIVE_SHA256='ARCHIV-PRUEFSUMME_AUS_EXPORTAUSGABE'
./vendor/bin/sail artisan context-search:dataset:import "incoming/$ARCHIVE_NAME" "$ARCHIVE_SHA256"
```

Nach dem Import die ausgegebene UUID und den Datensatzstatus in der Evaluationsumgebung kontrollieren. Der Import verwendet einen lokalen technischen Benutzer und legt die PDF-Dateien in der Evaluationsablage ab. Bei identischem Manifest kann derselbe Import erneut ausgeführt werden, ohne Materialien oder Ressourcen zu duplizieren.

Nach dem Upgrade prüft ein Administrator vorhandene eingefrorene Datensätze zuerst lesend. Konflikte werden nie automatisch aufgelöst. Nur wenn die Ausgabe konfliktfrei ist, darf die explizite Übernahme erfolgen:

```sh
./vendor/bin/sail artisan context-search:dataset:reconcile-memberships
./vendor/bin/sail artisan context-search:dataset:reconcile-memberships --apply
```

Der lesende Abgleich zeigt zusätzlich eine grafische, inhaltsfreie Terminalübersicht der vertraglich empfohlenen Sollmengen für Kalibrierung, Abnahme, OCR, Last und Kapazität. Sie enthält die jeweilige Ressourcen- und Materialmenge, Fortschrittsbalken, verbleibende Mengen sowie den eindeutigen Mindestbedarf und die Reserve geeigneter PDF-/Textressourcen gegenüber den exklusiven Zielen für Kalibrierung und Abnahme; OCR, Last und Kapazität sind als überlappende Prüfvolumina ausgewiesen. Die Übersicht ist eine Kuratierungs- und Kapazitätshilfe; sie ändert weder Auswahl noch Sollmengen.

Der Abgleich verarbeitet fehlende Mitgliedschaften in begrenzten Blöcken nach Dataset-UUID. Die Reihenfolge der gemeldeten Datensätze entspricht daher nicht zwingend ihrer Erstellungszeit. Auch bei großen Datensätzen bleibt die Konfliktprüfung vollständig: Ein Konflikt verhindert die Übernahme sämtlicher Mitgliedschaften dieses Datensatzes. Ein erneuter Lauf überspringt bereits abgeglichene Datensätze.

### OCR testen und Schwellenwerte einstellen

Diese Anleitung gilt für ein **isoliertes Dev-/Evaluationssystem**, nicht für Produktion. Produktionsinhalte, auch private, dürfen nur über den oben beschriebenen eingefrorenen OCR-Datensatz übertragen werden. In Produktion sind OCR-Kalibrierung und Profilfreigabe serverseitig gesperrt. **Aktueller Stand:** Neue OCR-Kalibrierungsläufe und Kontextsuche-Worker sind bis zur vollständigen Abnahme von Schritt 3 des [Queue-Änderungsvertrags](ai/context-search-queue-change-contract.md) weiterhin technisch gesperrt. Die Schritte 1 bis 4 bereiten Daten und Umgebung vor; **Schritt 5 und folgende erst nach dokumentierter Worker-Freigabe ausführen**. Die Sperre nicht mit Tinker, einer geänderten Konfiguration oder einem alten Worker umgehen.

1. **OCR-Daten in Produktion auswählen und einfrieren.** Als Global Admin im Benutzermenü **KI-Datensätze** öffnen, einen Datensatz vom Typ **OCR** mit repräsentativen PDFs füllen und einfrieren. Er sollte Scans mit gutem/schlechtem Druck, Handschrift, leere Seiten sowie PDFs mit vorhandener Textschicht enthalten. Die vertragliche Anfangsgröße beträgt 100 PDFs; für den technischen Kalibrierungslauf sind mindestens zwei verschiedene lesbare PDFs nötig. OCR darf vollständige Material-/Ressourcenblöcke mit Kalibrierung oder Abnahme teilen; die spätere OCR-Schwellenwertwahl darf aber nicht anhand des semantischen Abnahme-Datensatzes optimiert werden. Die UUID des eingefrorenen OCR-Datensatzes in den folgenden Befehlen einsetzen. Export und Prüfen verändern keinen Suchindex, erzeugen aber ein privates Archiv. Auf dem **Produktionsserver ohne Sail**:

   ```sh
   DATASET_UUID='UUID_HIER_EINTRAGEN'
   php artisan context-search:dataset:export "$DATASET_UUID"
   ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
   php artisan context-search:dataset:verify "exports/$ARCHIVE_NAME"
   ```

   Archiv und **beide** ausgegebenen Prüfsummen auf die Evaluationsmaschine übertragen. Der genaue Dateiweg steht in Schritt 2; es gibt dafür derzeit **keinen Upload-Button im Browser**. Keine Dokumenttitel oder Texte in Tickets, Konsolenprotokolle oder Commits kopieren. Falls derselbe Rechner beide Rollen übernimmt, müssen Datenbank, private Dateiablage und Qdrant-Alias trotzdem getrennt sein.

2. **ZIP auf die Dev-Maschine laden und dort importieren.** Der Export aus Schritt 1 liegt auf dem Produktionsserver im privaten `exports/`-Ordner. Das ZIP muss **als Datei** auf den Dev-Rechner in den privaten `incoming/`-Ordner kopiert werden; erst danach kann Laravel es importieren. Ein Browser-Upload ist nicht implementiert. Das Archiv kann private Dokumente enthalten: nicht nach `public/`, in einen Web-Upload-Ordner, in Git oder in einen allgemein freigegebenen Cloud-Ordner legen.

   Zuerst auf **beiden** Rechnern den tatsächlich eingestellten Grundordner prüfen. Der Befehl zeigt nur den Speicherpfad, keine Dokumentinhalte. Ohne eigene Einstellung `CONTEXT_SEARCH_EVALUATION_PATH` ist es auf dem Dev-Rechner `storage/app/context-search-evaluation` (im Sail-Container `/var/www/html/storage/app/context-search-evaluation`); in der Standard-Produktionsinstallation liegt der entsprechende Ordner unter `/srv/materialpool/shared/storage/app/context-search-evaluation`. Ist ein anderer Pfad eingestellt, die nachfolgenden Beispielpfade entsprechend ersetzen und sicherstellen, dass der Dev-Pfad auch **im Container** erreichbar ist.

   ```sh
   # Auf Produktion, im Anwendungsverzeichnis:
   php artisan tinker --execute='echo config("filesystems.disks.context_search_evaluation.root"), PHP_EOL;'

   # Auf Dev, im Projektverzeichnis:
   ./vendor/bin/sail artisan tinker --execute='echo config("filesystems.disks.context_search_evaluation.root"), PHP_EOL;'
   ```

   Vor dem Kopieren sicherstellen, dass die Dev-Umgebung wirklich von Produktion getrennt ist. Die folgenden lesenden Prüfungen im Projektverzeichnis auf der **Dev-Maschine mit Sail** ausführen. `artisan env` muss `local` oder eine andere ausdrücklich freigegebene Nicht-Produktionsumgebung melden. `migrate:status` darf keine für den Import erforderlichen Migrationen als offen zeigen. Bei Abweichungen stoppen und die Zielverbindung klären; niemals `migrate:fresh` oder `db:seed` auf importierten Daten ausführen.

   ```sh
   ./vendor/bin/sail ps
   ./vendor/bin/sail artisan env
   ./vendor/bin/sail artisan migrate:status
   ```

   Für den **Standardpfad** jetzt auf der **Dev-Maschine**, weiterhin im Projektverzeichnis, das Verzeichnis anlegen und das ZIP mit SCP vom Produktionsserver holen. `DATEINAME_AUS_EXPORTAUSGABE.zip` durch den gemeldeten Dateinamen und `SSH_BENUTZER@PROD_HOST` durch den SSH-Zugang ersetzen. Für einen abweichenden Produktions- oder Dev-Speicherpfad die beiden Pfade im `scp`-Befehl anhand der gerade geprüften Ordner anpassen. SCP überträgt verschlüsselt; das ist erlaubt, aber für dieses Evaluationsarchiv keine vertragliche Voraussetzung. Statt SCP ist auch SFTP oder eine manuelle Übertragung möglich, solange am Ende **genau dieselbe ZIP-Datei** im Dev-`incoming/` liegt. Falls der SSH-Benutzer das private Archiv nicht lesen darf, die Berechtigung gezielt mit dem Administrator klären – nicht den Ordner öffentlich machen.

   ```sh
   ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
   PROD_SSH='SSH_BENUTZER@PROD_HOST'
   umask 077
   mkdir -p storage/app/context-search-evaluation/incoming
   chmod 700 storage/app/context-search-evaluation/incoming
   scp "$PROD_SSH:/srv/materialpool/shared/storage/app/context-search-evaluation/exports/$ARCHIVE_NAME" \
     "storage/app/context-search-evaluation/incoming/$ARCHIVE_NAME"
   chmod 600 "storage/app/context-search-evaluation/incoming/$ARCHIVE_NAME"
   ./vendor/bin/sail exec laravel.test test -r "/var/www/html/storage/app/context-search-evaluation/incoming/$ARCHIVE_NAME"
   ```

   Der letzte Befehl muss erfolgreich enden: Er zeigt, dass **der Container** das hochgeladene ZIP lesen kann. Bei einer eigenen `CONTEXT_SEARCH_EVALUATION_PATH`-Einstellung auch diesen Prüfpfad anpassen. `docker-compose.yml` bindet standardmäßig das gesamte Projektverzeichnis nach `/var/www/html` ein; deshalb erscheint eine Datei im Dev-Projektordner unmittelbar im Container. Wenn die Datei nur auf dem Host, aber nicht im Container sichtbar ist, vor dem Import den Mount beziehungsweise die Rechte korrigieren.

   Erst jetzt auf Dev prüfen und importieren. Vorher muss `CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED=true` **nur in der Dev-`.env`** aktiv sein. Nach einer gerade vorgenommenen `.env`-Änderung den Dev-Konfigurationscache mit `./vendor/bin/sail artisan config:clear` erneuern. `verify` ist lesend; **vor** `import` müssen Archiv- und Manifest-Prüfsumme mit den in Schritt 1 auf Produktion notierten Werten übereinstimmen. Bei Abweichung stoppen und die Übertragung wiederholen, nicht trotzdem importieren. `import` schreibt Materialien, Ressourcen und Quelldateien ausschließlich in die freigegebene Evaluationsumgebung. Danach die gemeldete Datensatz-UUID und den Status kontrollieren.

   ```sh
   ARCHIVE_NAME='DATEINAME_AUS_EXPORTAUSGABE.zip'
   ./vendor/bin/sail artisan context-search:dataset:verify "incoming/$ARCHIVE_NAME"
   ./vendor/bin/sail artisan context-search:dataset:import "incoming/$ARCHIVE_NAME"

   ```

   Statt des letzten Befehls kann die beim Export notierte Prüfsumme als optionales zweites Argument automatisch abgeglichen werden:

   ```sh
   ARCHIVE_SHA256='ARCHIV-PRUEFSUMME_AUS_EXPORTAUSGABE'
   ./vendor/bin/sail artisan context-search:dataset:import "incoming/$ARCHIVE_NAME" "$ARCHIVE_SHA256" --env=local
   ```

3. **OCR-Werkzeuge und Queue lesend vorprüfen.** Im Dev-Container müssen Poppler und Tesseract verfügbar sein; `tesseract --list-langs` muss `deu` und `eng` enthalten. Die vorhandene Feature-Prüfung verarbeitet eine isolierte Test-PDF und verwendet explizit die entbehrliche Datenbank `testing`, nicht die importierten Dev-Daten. Der Queue-Check darf keine ungeprüften Altaufträge melden. Nur bei `APP_ENV=local` ist die OCR-Kalibrierung zum Test freigegeben; manuelle Indexläufe, andere Kontextsuche-Worker und Produktion bleiben gesperrt.

   ```sh
   ./vendor/bin/sail exec laravel.test sh -lc 'for tool in pdfinfo pdftotext pdftoppm tesseract; do command -v "$tool" || exit 1; done'
   ./vendor/bin/sail exec laravel.test tesseract --list-langs
   ./vendor/bin/sail artisan context-search:queue:check
   ./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan test tests/Feature/ContextSearchTesseractOcrTest.php
   ```

4. **Testaufbau festhalten.** Vor dem ersten Lauf für jedes PDF die erwarteten Seitenarten und eine grobe Qualitätsbewertung notieren. Mindestens 15, besser etwa 50 Seiten für den ersten Lauf vorsehen; die Oberfläche akzeptiert 15 bis 500. Es müssen genügend Seiten aus **verschiedenen** PDFs vorhanden sein, damit der Holdout nach Dokument getrennt werden kann. Für die spätere Auswertung werden mindestens zehn bewertete Kalibrierungsseiten benötigt, darunter je mindestens drei brauchbare und drei unbrauchbare/Handschrift/leere Seiten. Zusätzlich braucht es wortgetreue Referenztranskripte für mindestens drei brauchbare Kalibrierungsseiten und eine brauchbare Holdout-Seite. Private Transkripte nur in der geschützten Oberfläche speichern.

5. **Lokalen OCR-Testlauf starten.** Als Global Admin in der lokalen Docker-Umgebung im Benutzermenü **OCR-Schwellenwerte kalibrieren** öffnen. Den eingefrorenen OCR-Datensatz wählen, Stichprobengröße (zunächst `15`, später etwa `50`) und einen eindeutigen Lauftitel eingeben, dann **Starten**. Ein Lauf speichert Seitenstichprobe, Profil, Quelldokument-Revision und Aufteilung in Kalibrierung/Holdout. Die Kalibrierungsjobs führen Tesseract mit einem CPU-Thread pro Unterprozess aus; sie rufen weder Ollama noch Qdrant auf. Wenn keine fremden Läufe auf der dedizierten Kalibrierungsqueue warten, den folgenden **einmaligen lokalen OCR-Worker** in einem Terminal starten. Er arbeitet seriell und endet, sobald die Queue leer ist; keinen alten `context-search-indexing`-Worker starten.

   ```sh
   ./vendor/bin/sail artisan queue:work context_search --queue=context-search-calibration-ocr --sleep=3 --tries=3 --timeout=480 --stop-when-empty
   ```

   In der Oberfläche **Aktualisieren** wählen, bis alle Seiten verarbeitet sind. Bei fehlgeschlagenen Seiten nicht blind erneut starten: zuerst Quelle, Tesseract, freien Speicher und Fehlerstatus prüfen. Der angezeigte OCR-Text ist vertraulich und gehört nicht in normale Logs.

   Wurde ein Lauf während des Einreihens unterbrochen, können nach der Worker-Freigabe ausschließlich seine noch wartenden Seiten erneut eingeplant werden. Zuerst den Worker beenden und prüfen, dass die dedizierte Kalibrierungsqueue leer ist; dann die UUID des betroffenen Laufs einsetzen. Der Befehl ist in Produktion und bei weiterhin aktiver Dispatch-Sperre gesperrt. Er verändert keine Quelldateien oder bereits verarbeiteten Seiten:

   ```sh
   RUN_UUID='UUID_DES_KALIBRIERUNGSLAUFS'
   ./vendor/bin/sail artisan context-search:ocr-calibration:resume "$RUN_UUID"
   ```

6. **Seiten beurteilen, auswerten, freigeben.** Die OCR rastert den sichtbaren PDF-Ausschnitt (`CropBox`). Nach einer Änderung dieses Ausschnitts müssen vorhandene OCR-Texte und Qualitätsurteile erneut geprüft werden; Referenztranskripte bleiben erhalten. Jede verarbeitete Seite mit der PDF-Vorschau vergleichen und als **brauchbar**, **unbrauchbar**, **unsicher**, **Handschrift** oder **leer** speichern. Referenztext exakt von Hand transkribieren; „unsicher“ zählt nicht zur Wertung. **Schwellenwerte auswerten** öffnet einen Dialog mit dem für den ausgewählten Lauf kopierbaren Terminalbefehl. Im Projektverzeichnis der isolierten Dev-/Evaluationsumgebung ausführen und nach dessen Ende in der Oberfläche **Aktualisieren** wählen:

   ```sh
   RUN_UUID='UUID_DES_KALIBRIERUNGSLAUFS'
   ./vendor/bin/sail artisan context-search:ocr-calibration:evaluate "$RUN_UUID"
   ```

   Der Befehl benötigt einen vollständig verarbeiteten, noch nicht freigegebenen Lauf. Er kann bei großen Stichproben mehrere Minuten dauern und zeigt den Fortschritt seitenweise für den 21-stufigen Schwellenvergleich und den Holdout. Anschließend gibt er eine Tabelle aller Schwellenwerte mit Abdeckung, Präzision, CER/WER und Zahl der gemessenen Referenzseiten aus. Er schreibt Auswertung und Status in den Datenbankeintrag; OCR-Texte, Referenzen und Bewertungen bleiben erhalten. Er ist in Produktion gesperrt. Die Auswertung testet mittlere Tesseract-Konfidenz von `0,00` bis `1,00` in `0,05`-Schritten und empfiehlt die größte Abdeckung mit mindestens 95 % Präzision auf den brauchbaren Kalibrierungsseiten. Den getrennten Holdout prüfen: Für die technische Freigabe sind mindestens 90 % Präzision und messbare Zeichen-/Wortfehlerraten (CER/WER) nötig. Das ist ein Mindest-Gate, keine Garantie guter Transkriptionsqualität; Seitenbeispiele und Fehlerarten zusätzlich fachlich prüfen. Ist kein geeigneter Grenzwert vorhanden oder der Holdout schlecht, **nicht freigeben**: Auswahl/Bewertungen prüfen oder mit geändertem OCR-Profil einen neuen Lauf erstellen. Gute Holdout-Werte nicht durch nachträgliches Tuning an genau diesem Holdout „optimieren“.

   „Kein Text erkannt.“ bedeutet, dass der OCR-Ergebnistext leer ist; `processed` bezeichnet ausschließlich den abgeschlossenen Seitenjob. Auch bebilderte Seiten können dieses Ergebnis liefern, wenn Tesseract keine Schrift findet.

7. **Genehmigtes Profil bewusst parametrisieren.** **Freigeben** zeigt ein Profil mit SHA-256 und einen Block mit `.env`-Zeilen. Diesen Block zunächst **nur in die `.env` der Evaluationsmaschine** übernehmen; die Freigabe selbst ändert die Laufzeitkonfiguration nicht. `CONTEXT_SEARCH_OCR_MINIMUM_MEAN_CONFIDENCE` ist ein Wert zwischen `0` und `1` (`0.75` bedeutet 75 %). Der Standard `0` ist permissiv und keine Qualitätsfreigabe. Die Auswertung optimiert derzeit **nur diesen Konfidenzwert**; `CONTEXT_SEARCH_OCR_MINIMUM_RECOGNIZED_WORDS`, `CONTEXT_SEARCH_OCR_MINIMUM_ALPHANUMERIC_RATIO` und `CONTEXT_SEARCH_OCR_MAXIMUM_REPLACEMENT_CHARACTER_RATIO` bleiben bei den für den Lauf geltenden Werten und müssen anhand der Fehlfälle bewusst beurteilt werden. Die native PDF-Textschicht wird beim späteren Indexieren vor OCR verwendet, sobald sie mindestens `CONTEXT_SEARCH_PDF_NATIVE_TEXT_MINIMUM_CHARACTERS` Zeichen liefert (Standard `80`); diese Grenze wird von der OCR-Kalibrierung **nicht** automatisch optimiert. Jede Änderung an Sprache, PSM, Tesseract-Version, Ziel-DPI (Standard `300`), Pixelbudget (Standard `12000000`) oder den übrigen Qualitätsgrenzen verlangt einen neuen Lauf mit eigenem Profil. Nach Änderung der Dev-`.env` die aufgelöste Konfiguration erneuern und einen bereits laufenden dedizierten Worker kontrolliert beenden und neu starten:

   ```sh
   ./vendor/bin/sail artisan config:clear
   ./vendor/bin/sail artisan context-search:queue:check
   ```

   Nach dem Wechsel auf CropBox-Rendering muss ein explizit gesetztes, noch nicht kalibriertes `CONTEXT_SEARCH_OCR_QUALITY_PROFILE` auf `tesseract-de-en-300dpi-cropbox-v3` aktualisiert werden. Ein bereits freigegebenes Kalibrierprofil verlangt stattdessen eine neue fachliche Kalibrierung. Der feste Render-Ausschnitt fließt unabhängig vom Profilnamen in die Indexrevision ein; ein alter `.env`-Wert würde das verwendete OCR-Profil falsch benennen.

   Erst nach bestandener OCR-, Last-, Wiederanlauf- und Quellenabnahme darf das freigegebene Profil in die Produktionskonfiguration übernommen werden. Profiländerungen erzeugen eine neue Indexrevision; betroffene PDFs müssen später manuell neu indiziert und die Quellen/Seiten gegen das genehmigte Profil geprüft werden. Ein Kalibrierungslauf allein indiziert **keine** Ressource und aktiviert **keinen** Produktionsworker.

8. **Erst nach Freigabe der gesamten Index-Queue: Profil an einem PDF prüfen.** Auf der Evaluationsmaschine eine bekannte PDF-Ressourcen-ID mit schwieriger Scan-Seite auswählen und `PDF_RESSOURCEN_ID` ersetzen. Vorher müssen der aktive Qdrant-Alias und das identische Embedding-Profil aller Ollama-Server geprüft sein. Nur wenn keine anderen Kontextsuche-Läufe auf den dedizierten Queues liegen, den einen manuellen Indexlauf starten. Der Befehl nennt die Lauf-UUID:

   ```sh
   ./vendor/bin/sail artisan context-search:ollama:verify
   ./vendor/bin/sail artisan context-search:queue:check
   ./vendor/bin/sail artisan context-search:index PDF_RESSOURCEN_ID
   ```

   Für mehrseitige PDFs müssen Extraktion und Embedding einander Jobs nachliefern können. Daher nach **gesonderter** Betriebsfreigabe je einen Worker in **zwei Terminals** starten, nicht auf der normalen Queue. Beide mit `Ctrl+C` beenden, sobald der eine Lauf abgeschlossen und seine Queues leer sind; nicht als unbeaufsichtigten Dauerbetrieb stehen lassen.

   ```sh
   # Terminal 1: genau ein Extraktions-/OCR-Worker
   ./vendor/bin/sail artisan queue:work context_search --queue=context-search-extraction --sleep=3 --tries=3 --timeout=480
   ```

   ```sh
   # Terminal 2: genau ein Embedding-/Qdrant-Worker
   ./vendor/bin/sail artisan queue:work context_search --queue=context-search-upsert,context-search-embedding --sleep=3 --tries=3 --timeout=480
   ```

   Die PDF-Seite, Extraktionsart (`native` oder `ocr`), sichtbaren Quellenbeleg und den erwarteten Text fachlich vergleichen. Bei Fehlstatus oder falscher Seite nicht weitere PDFs einplanen. Dieser einzelne Praxistest ersetzt weder die getrennte OCR-Abnahme noch Last-, Crash-/Restore- und Rechteprüfungen.

Für gezielte Diagnose können die Prozesse einzeln laufen:

```sh
./vendor/bin/sail artisan serve
./vendor/bin/sail artisan queue:listen --tries=1 --timeout=0
npm run dev
```

## Tinker, Shell und Artisan

```sh
./vendor/bin/sail shell
./vendor/bin/sail artisan tinker
```

Sichere, lesende Tinker-Beispiele:

```php
app()->version();
config('database.default');
\App\Models\Material::query()->count();
\App\Models\Resource::query()->whereNull('filesize')->count();
```

> [!WARNING]
> Tinker ist kein read-only Werkzeug. `save()`, `delete()`, Service-/Controlleraufrufe, Events und Jobs können Daten, Dateien, Queues und Caches verändern. Vor jeder Mutation Datenbank und Umgebung prüfen; Tinker niemals beiläufig gegen Produktion verwenden.

Nützliche Entwicklungsbefehle:

| Befehl | Zweck |
| --- | --- |
| `./vendor/bin/sail artisan route:list` | Web- und API-Routen anzeigen. |
| `./vendor/bin/sail artisan config:show database` | Tatsächlich aufgelöste Datenbankkonfiguration prüfen. |
| `./vendor/bin/sail artisan event:list` | Registrierte Events und Listener untersuchen. |
| `./vendor/bin/sail artisan queue:failed` | Fehlgeschlagene Queue-Jobs anzeigen. |
| `./vendor/bin/sail artisan optimize:clear` | Lokale Laravel-Caches bei einem nachgewiesenen Cacheproblem leeren. |
| `./vendor/bin/sail test --filter <Testklasse>` | Einen gezielten Backend-Test ausführen. |
| `npm run test:unit` | Vitest-Unit-Tests ausführen. |
| `npm run lint` | JavaScript, Vue, Skripte und Browsertests ohne Warnung linten. |
| `npm run build` | Übersetzungen und Vite-Produktionsbundle erzeugen und prüfen. |
| `npm run test:e2e` | Funktionale Playwright-Reisen auf Desktop und Mobile ausführen. |
| `npm run test:visual` | Visual-Regression-Baselines vergleichen. |
| `npm run docs:screenshots` | Bilder für die Anwenderdokumentation aus synthetischen Daten neu erzeugen. |
| `npm run docs:check` | Einstieg, lokale Dokumentationslinks und Screenshot-Metadaten prüfen. |

## Vue-3-Entwicklung

- Einstieg: `resources/js/apps/main/index.js`; Seiten und Router liegen unter `resources/js/apps/main/`.
- Gemeinsamer Zustand liegt in Pinia-Stores unter `resources/js/apps/main/stores/`.
- Wiederverwendbare Fachkomponenten liegen unter `resources/js/components/`.
- Bootstrap 5, BootstrapVueNext und die Materialpool-Adapter bilden das bestehende UI-System.
- Sichtbare Texte werden über `resources/lang/`, insbesondere `resources/lang/de/pool.php`, gepflegt.
- API v1/v2, CSRF, Session, Passport, Payloads und Fehlerbehandlung sind bestehende Verträge.
- Responsive Verhalten, Tastaturzugang, Fokus, Lade-, Leer- und Fehlerzustände gehören zu jeder UI-Prüfung.

`@vue/compat`, Vuex, BootstrapVue, Webpack und Laravel Mix sind entfernt und dürfen nicht wieder eingeführt werden. Bestehende Options-API-Komponenten müssen nicht aus Stilgründen umgeschrieben werden. Für neue komplexe oder wiederverwendbare Zustandslogik ist die Composition API sinnvoll; die Entscheidung richtet sich nach dem konkreten Nutzen.

## Tests und isolierte Datenbank

Die verbindliche Backend-Referenz ist der Sail-PHP-8.4-Container. Die GitHub-PHP-Jobs legen `storage/framework/views` in jedem frischen Checkout vor den Tests an. Laravel benötigt diesen Pfad zum Kompilieren von Blade-Views; das leere Verzeichnis ist nicht versioniert. Vor destruktiven Testbefehlen muss die aufgelöste Verbindung ausdrücklich geprüft werden:

```sh
./vendor/bin/sail exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_DATABASE=testing \
  laravel.test php artisan config:show database --env=testing
```

Nur wenn die Ausgabe zweifelsfrei die dedizierte, entbehrliche Datenbank `testing` zeigt:

```sh
./vendor/bin/sail exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_DATABASE=testing \
  laravel.test php artisan migrate:fresh --env=testing --force

./vendor/bin/sail exec \
  -e APP_ENV=testing \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql \
  -e DB_DATABASE=testing \
  laravel.test php artisan db:seed --env=testing --force
```

`--env=testing` allein ist kein Isolationsnachweis, weil Laravel bei fehlender `.env.testing` auf andere Werte zurückfallen kann. Seed-Ausgaben mit Test-Client-Secrets dürfen nicht gespeichert oder weitergegeben werden.

Die Migrationen erstellen nur die Bibel- und Cross-Reference-Tabellen sowie den Importstatus. Der produktive Import erfolgt ausschließlich mit `bible:import`; ein allgemeines `db:seed` bleibt in Produktion verboten. Bei der Erstinstallation benötigt die Auswahl eine TTY und ausgehendes HTTPS zum [Scrollmapper-Katalog](https://github.com/scrollmapper/bible_databases). Ein textfreier Prüfsnapshot zeigt bei der bekannten Quellrevision Umfang und nicht installierbare Ausgaben; aktuell sind 25 Ausgaben mit zusätzlichen Büchern und eine leere Ausgabe vom Import ausgeschlossen. Bei einer neuen Quellrevision prüft der Befehl den Umfang der gewählten Datei vor dem Import. Cross References stammen aus dem geprüften Release und können nicht interaktiv offline importiert werden. `--update-translations` und `--update-cross-references` installieren keine neuen Datensätze. Ohne Aktionsoptionen gibt `--no-interaction` nur den lokalen Status aus. Quelle, Versionierung, Rechtehinweis, Fehlergrenzen und Bestandseinführung stehen im [Bibel-Installationsvertrag](ai/bible-data-installation-contract.md).

| Änderung | Mindestprüfung |
| --- | --- |
| PHP/Backend | PHP-Syntax und betroffene PHPUnit-Tests im Sail-Container. |
| API/Policy | Erfolg, Validierung, 401/403/404/422/500 und unveränderte Payloads. |
| Resource/Datei | Storage-Disk, Eventfolge, Cache-Invalidation und repräsentativer Dateityp. |
| Bundle/Queue | Queue-Name, Reihenfolge, Wiederholung und Fehlerpfad mit Testdaten. |
| Vue/Sass | Lint, Unit-Test, Production-Build sowie Desktop-/Mobile-Prüfung. |
| Dokumentation | Diff-, Link- und Konsistenzprüfung; bei UI-Bildern zusätzlich Screenshotlauf. |

Die vollständigen und jeweils aktuellen Befehle stehen in den [Quality Gates](ai/quality-gates.md).

## Troubleshooting

<details>
<summary>Sail meldet, Docker sei nicht verfügbar</summary>

Docker Desktop beziehungsweise den Docker-Daemon, den aktiven Docker-Kontext und die Berechtigung auf den Docker-Socket prüfen. Ein laufender Container in einer anderen Host-Sitzung beweist nicht, dass der aktuelle Prozess auf den Socket zugreifen darf.

</details>

<details>
<summary>Vite-Assets fehlen oder sind veraltet</summary>

`npm ci --ignore-scripts`, anschließend `npm run build` ausführen. In Produktion darf keine `public/hot`-Datei vorhanden sein. Fehler in `php artisan lang:js -c --no-lib` müssen den Build abbrechen und dürfen nicht übersprungen werden.

</details>

<details>
<summary>Queue-Jobs laufen lokal nicht</summary>

`QUEUE_CONNECTION`, laufenden Queue-Prozess und `./vendor/bin/sail artisan queue:failed` prüfen. Bundle-Queues besitzen dynamische Namen und werden nicht vom normalen Default-Worker übernommen.

</details>

<details>
<summary>Playwright/WebKit endet unter macOS mit `Abort trap: 6`</summary>

WebKit benötigt AppKit-/Mach-Port-Zugriff. Der Fehler bei `RegisterApplication` weist auf eine zu restriktive Ausführungssandbox hin, nicht automatisch auf einen defekten Browserdownload. Den Test in einem passenden lokalen Prozesskontext wiederholen.

</details>

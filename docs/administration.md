# Administration

[Zur Übersicht](../README.md)

## Inhalt

- [Neuer Proxmox-Betrieb](#neuer-proxmox-betrieb)
- [Installation im Altbetrieb](#installation)
- [Updates im Altbetrieb](#updates)
- [Wartung im Altbetrieb](#wartung)
- [OCR-Evaluationsdatensatz](#ocr-evaluationsdatensatz)
- [Jobs und Queues](#jobs-und-queues)

## Jobs und Queues

Globale Administratoren öffnen **Jobs und Queues** im Benutzermenü. Die Seite zeigt die vorhandenen Datensätze aus `jobs`, `failed_jobs` und `job_batches` in getrennten Tabs, nach Queue-Namen gruppiert. Filter und Seitenwechsel begrenzen die Listen. Alle fünf Sekunden wird die sichtbare Ansicht aktualisiert, solange der Browser-Tab aktiv ist; der Zeitpunkt der letzten erfolgreichen Aktualisierung bleibt sichtbar.

„Reserviert“ bedeutet, dass ein Worker den Job übernommen hat. Nach einem Worker-Abbruch kann diese Markierung vorübergehend bestehen bleiben und bestätigt deshalb keinen aktuell laufenden Prozess. Erfolgreiche Einzeljobs werden nicht historisch aufbewahrt. Die Seite kann Jobs weder neu priorisieren noch verschieben, wiederholen oder löschen. Fehlermeldungen und Job-Payloads werden nicht angezeigt. Ein Batch erhält nur dann einen Queue-Namen, wenn ein vorhandener Bundle-Importlauf ihn eindeutig zuordnet.

## OCR-Evaluationsdatensatz

Globale Administratoren öffnen in der Anwendung **KI-Datensätze** und wählen den OCR-Datensatz oder legen einen neuen an. Neue OCR-Entwürfe verwenden 300 bekannte PDF-Seiten sowie mindestens je ein Buch, Arbeitsblatt und eine Präsentation als Ziel. Nach dem Hinzufügen eines PDF-Blocks wird die Dokumentart an jeder PDF-Ressource im Datensatz gewählt. PDF-Seiten ohne bekannte Seitenzahl werden angezeigt, zählen aber nicht zum Seitenziel. Materialien und Ressourcen werden weiter als zusammenhängende Blöcke zugeordnet; ihre Anzahl ist für neue OCR-Entwürfe keine Zielgröße. Eingefrorene und ältere OCR-Datensätze behalten ihre gespeicherten Ziele.

## Neuer Proxmox-Betrieb

Bei der optionalen Einrichtung mit `php /srv/materialpool/current/artisan context-search:configure` wird der SHA-256-Digest des gewählten Embedding-Modells vom ersten eingetragenen Ollama-Server gelesen und als `CONTEXT_SEARCH_EMBEDDING_DIGEST` gespeichert. Vor der Aktivierung prüft der Command Modell-Digest, Dimension und Probevektor auf allen eingetragenen Servern. Fehlt das Modell oder weichen die Server ab, bleibt die Kontextsuche deaktiviert. Der Command benötigt im LXC root-Rechte für die persistente `.env`; die produktiven Index-Worker bleiben gesondert gesperrt.

**Neuer Installationsweg:** Für neue Proxmox-Installationen gilt [Materialpool auf Proxmox VE](../deployment/README.md) mit [Installation](../deployment/docs/installation.md), [Update](../deployment/docs/update.md) und [vollständiger Testanleitung](../deployment/docs/testing.md). GitHub Actions testet und baut versionierte Releases als Archiv mit SHA-256-Datei; Version und Commit stehen in der `release.json` im Archiv. Der unprivilegierte Debian-LXC betreibt Laravel mit Nginx, PHP-FPM 8.4 und MariaDB ohne Docker oder Node. Qdrant läuft in einem separaten LXC. Im Laravel-LXC startet `update` nach Veröffentlichung eines stabilen GitHub-Releases den geprüften Updateablauf. Ein bestehender Datenbestand benötigt einen gesondert verifizierten Restore; der Fresh-Installer verweigert die Übernahme.

Bei einer frischen Proxmox-Installation erzeugt der Release-Updater mit `passport:keys` ein Passport-Schlüsselpaar im persistenten Shared-Storage. Bei Updates und beim Wiederanlauf nach einer unterbrochenen Erstinstallation bleibt ein vollständiges vorhandenes Paar erhalten; ein unvollständiger Bestand stoppt die Installation. Die Schlüssel werden weder dem Release-Archiv hinzugefügt noch bei Updates ersetzt.

Der Proxmox-Installer führt die Datenbankmigrationen beim ersten Release aus und fragt anschließend den ersten aktiven Global-Admin ab. Im LXC kann ein vorhandener Benutzer interaktiv mit `runuser -u www-data -- php /srv/materialpool/current/artisan users:manage update` bearbeitet werden; dabei lassen sich insbesondere E-Mail und Passwort ändern, ohne das Passwort als Befehlsargument oder im Terminalprotokoll auszugeben. Der produktive Datenbankzugang und `APP_KEY` liegen dauerhaft in `/srv/materialpool/shared/.env`, auf die jedes Release verweist. Weitere Einzelheiten und der Wiederanlauf bei abgebrochener Admin-Eingabe stehen in der [Installationsanleitung](../deployment/docs/installation.md).

**Altbetrieb:** Die Abschnitte „Installation“, „Updates“ und „Wartung“ unten beschreiben weiterhin den vorhandenen Ubuntu-24.04-/MySQL-8-Server und `ops/production/`. Diese Befehle gelten nicht für den neuen Proxmox-LXC. Der neue Betrieb ist erst nach den in der Proxmox-Testanleitung beschriebenen externen Tests freigegeben.

Die Produktion läuft auf einem einzelnen Ubuntu-24.04-LTS-Server mit Nginx, PHP-FPM 8.4 und MySQL 8 hinter einem externen TLS-Reverse-Proxy. Supervisor betreibt den Default-Queue-Worker; Cron startet jede Minute Laravels Scheduler. Node.js wird auf dem Produktionsserver nicht benötigt.

## Installation

### Voraussetzungen

Vor Beginn müssen extern feststehen:

- Domain, SSH-Ziel und konkrete Proxy-IP beziehungsweise Proxy-CIDR;
- MySQL-Datenbank, dedizierter Datenbankbenutzer und starkes Passwort;
- SMTP-Konfiguration und reale Benachrichtigungsadresse;
- S3-kompatibler Endpoint, Region, Bucket und Zugangsdaten für Offsite-Backups;
- bestehender `APP_KEY` und bestehende Passport-Schlüssel bei einer Übernahme;
- ein sauberer, exakter Git-Commit, der installiert werden soll.

Das Provisioning installiert `qpdf` für den Download ausgewählter Seiten aus PDFs mit komprimierten Querverweisen. `production:preflight` prüft, ob das Programm verfügbar ist. Die Original-PDF wird dabei nicht verändert.

Die persistenten Pfade sind Teil des Datenvertrags:

```text
/srv/materialpool/releases/<UTC-Zeitstempel>-<Commit>/
/srv/materialpool/current -> releases/<aktives-release>/
/srv/materialpool/shared/.env
/srv/materialpool/shared/storage/
/srv/materialpool/shared/public-uploads/
/srv/materialpool/shared/backups/
/srv/materialpool/shared/incoming/
```

### Server vorbereiten

Das Provisioning-Skript installiert die Laufzeitpakete und legt die Shared-Verzeichnisse an:

```sh
sudo ops/production/provision-ubuntu.sh
```

Danach müssen die vorhandenen Vorlagen kontrolliert installiert werden:

1. `ops/production/nginx.conf` mit realem `server_name` nach `/etc/nginx/sites-available/materialpool` übernehmen und aktivieren.
2. `ops/production/materialpool-worker.conf` nach `/etc/supervisor/conf.d/` übernehmen.
3. `ops/production/materialpool.cron` nach `/etc/cron.d/materialpool` übernehmen.
4. `ops/production/activate-release.sh` root-owned als `/usr/local/sbin/materialpool-activate-release` installieren.
5. `ops/production/materialpool.sudoers` mit `visudo -cf` prüfen und anschließend root-owned mit Modus `0440` installieren.
6. MySQL nur lokal lauschen lassen und den Materialpool-Benutzer auf genau eine Datenbank begrenzen.
7. Firewall beziehungsweise vorgelagerte Netzwerkregeln erst nach verifiziertem SSH-Zugang aktivieren.

Die Produktionsdatei `/srv/materialpool/shared/.env` enthält mindestens die im [Produktionsvertrag](ai/production-deployment-contract.md#zwingende-produktionskonfiguration) genannten Werte. Secrets gehören weder in Git noch in Terminalprotokolle. `TRUSTED_PROXIES` darf niemals `*`, `**` oder `REMOTE_ADDR` enthalten. Die vorhandenen Passport-Schlüssel liegen unter dem Shared-Storage; der private Schlüssel muss Modus `0600` haben.

Vor dem ersten Release prüfen:

```sh
sudo nginx -t
sudo php-fpm8.4 -t
sudo supervisord -t
sudo systemctl status nginx php8.4-fpm mysql supervisor cron
```

### Release bauen

Der Build akzeptiert ausschließlich einen exakten Commit und bricht bei nicht committeten Änderungen im Materialpool-Arbeitsbaum ab:

```sh
ops/production/build-release.sh <40-stellige-commit-id> /absoluter/ausgabeordner
```

Das Skript baut im festgelegten `sail-8.4/app`-Image, installiert Composer- und npm-Abhängigkeiten aus den Lockfiles, erzeugt die Vite-Assets und schreibt Archiv, Release-Manifest und SHA-256-Datei. Das Archiv enthält weder `.env` noch `node_modules` oder `vendor`.

### Release hochladen und aktivieren

Der Upload aktiviert noch nichts:

```sh
ops/production/deploy-release.sh \
  /pfad/materialpool-<commit>.tar.gz \
  materialpool@server
```

Anschließend auf dem Server bewusst aktivieren:

```sh
sudo /usr/local/sbin/materialpool-activate-release \
  /srv/materialpool/shared/incoming/materialpool-<commit>.tar.gz
```

Die Aktivierung prüft Archiv und Manifest, verbindet die Shared-Pfade, setzt Rechte, installiert Composer-Pakete aus `composer.lock`, führt `production:preflight` und `optimize` aus, schaltet `current` atomar um und lädt PHP-FPM sowie den Default-Worker neu.

### Erfolg prüfen

```sh
curl --fail --silent https://<domain>/up
sudo supervisorctl status 'materialpool-default:*'
sudo -u www-data php /srv/materialpool/current/artisan schedule:list
```

`/up` muss mit HTTP 200, `text/plain` und `OK` antworten. Zusätzlich manuell prüfen: Anmeldung, eine geschützte Seite, eine repräsentative Resource-Vorschau, Download, Schreibzugriff auf Shared-Storage und Verarbeitung eines ungefährlichen Testjobs.

> [!IMPORTANT]
> Eine wirklich frische Produktionsinstallation und die Übernahme eines Bestands sind nicht derselbe Ablauf. Datenbankschema, Passport-Clients und persistente Dateien müssen vor der Aktivierung bewusst geklärt sein. Der vorhandene Aktivierungsweg schützt Migrationen absichtlich durch Wartungsmodus und Restore-Nachweis; fehlende Erstinstallationswerte dürfen nicht improvisiert werden.

> [!CAUTION]
> In Produktion verboten sind `composer update`, `npm install`, `npm update`, `migrate:fresh`, `db:seed`, `key:generate` und `passport:install`. Sie verletzen den reproduzierbaren Release- beziehungsweise Datenvertrag.

## Updates

### Release ohne Datenbankmigration

**Vorher prüfen**

- Der gewünschte Commit ist vollständig geprüft und der Arbeitsbaum sauber.
- Das letzte unverschlüsselte lokale und externe Backup ist gesund und beide Ziele sind ausschließlich für berechtigte Administratoren erreichbar.
- Das unmittelbar vorherige Release bleibt für einen Rücksprung erhalten.

**Ausführen**

```sh
ops/production/build-release.sh <40-stellige-commit-id> /absoluter/ausgabeordner
ops/production/deploy-release.sh \
  /pfad/materialpool-<commit>.tar.gz \
  materialpool@server
```

Auf dem Server:

```sh
sudo /usr/local/sbin/materialpool-activate-release \
  /srv/materialpool/shared/incoming/materialpool-<commit>.tar.gz
```

**Erfolg erkennen**

- `/up` antwortet erfolgreich.
- Login, API, Storage und Queue funktionieren.
- Supervisor meldet den Default-Worker als `RUNNING`.
- Nginx- und Anwendungslogs zeigen keine neuen Fehler.

**Rollback**

Bei einem reinen Code-Release kann `current` kontrolliert auf das unmittelbar vorherige Release zurückgeschaltet werden. Danach `optimize`, `reload`, PHP-FPM und Supervisor neu starten und die Smoke-Tests wiederholen. Persistente Dateien werden dabei nicht gelöscht oder kopiert.

### Release mit Datenbankmigration

Eine Migration ist erst erlaubt, wenn ein unverschlüsseltes Backup lokal und auf S3 erzeugt, heruntergeladen und auf einer isolierten MySQL-Instanz erfolgreich wiederhergestellt wurde. Der Nachweis umfasst Datenbank und repräsentative persistente Dateien.

**Vorher prüfen**

```sh
sudo -u www-data php /srv/materialpool/current/artisan migrate:status
sudo -u www-data php /srv/materialpool/current/artisan backup:monitor
```

Zusätzlich Release bauen und hochladen, Client-/Schema-Auswirkungen prüfen und den Restore-Nachweis protokollieren, ohne Secrets oder Nutzdaten festzuhalten.

**Wartungsfenster beginnen**

```sh
sudo -u www-data php /srv/materialpool/current/artisan down
sudo supervisorctl stop 'materialpool-default:*'
```

**Neues Release inklusive Migration aktivieren**

```sh
sudo MATERIALPOOL_RESTORE_PROOF_CONFIRMED=yes \
  /usr/local/sbin/materialpool-activate-release \
  /srv/materialpool/shared/incoming/materialpool-<commit>.tar.gz \
  --migrate
```

Das Aktivierungsskript führt `php artisan migrate --force` aus dem neuen, noch nicht aktiven Release aus. Die Schutzvariable bestätigt nur einen bereits erfolgten Restore-Test; sie erzeugt selbst keinen Nachweis.

**Nachkontrolle und Freigabe**

```sh
sudo -u www-data php /srv/materialpool/current/artisan migrate:status
sudo supervisorctl status 'materialpool-default:*'
curl --fail --silent https://<domain>/up
```

Erst nach erfolgreichen Login-, API-, Storage-, Queue- und fachlichen Smoke-Tests:

```sh
sudo -u www-data php /srv/materialpool/current/artisan up
```

Falls die freigegebene Migration `resources.filesize` ergänzt, wird der Altbestand danach separat eingeplant:

```sh
sudo -u www-data php /srv/materialpool/current/artisan resources:backfill-filesizes
```

Der Befehl ist idempotent, plant Batch-Jobs auf `database/default` ein und ist kein allgemeiner Bestandteil jedes Updates. Mit `--force` werden auch bereits gesetzte Werte neu berechnet.

**Rollback nach einer Migration**

Kein blindes `migrate:rollback` ausführen. Anwendung wieder sperren, Worker stoppen, Datenbank und persistente Dateien aus dem unmittelbar vorherigen geprüften Backup wiederherstellen, `current` auf das vorherige Release setzen, Caches optimieren, Dienste neu starten und Smoke-Tests ausführen.

Der erste Passport-13-Cutover folgt zusätzlich vollständig [`docs/ai/passport-13-client-migration.md`](ai/passport-13-client-migration.md).

## Wartung

In den Tabellen bedeutet **niedrig** „lesend oder regulärer Healthcheck“, **mittel** „ändert Laufzeitzustand“ und **hoch** „verändert Daten oder löscht abgeleitete beziehungsweise persistente Inhalte“.

### Zustand und Diagnose

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `curl --fail --silent https://<domain>/up` | Extern | Monitoring und nach Deployments | Prüft, ob Laravel bootet; gibt nur `OK` aus. | niedrig |
| `php artisan about` | Produktion als `www-data` oder Sail | Versions- und Konfigurationsüberblick | Zeigt Laufzeitmetadaten; Ausgabe vor Weitergabe auf sensible Angaben prüfen. | niedrig |
| `php artisan migrate:status` | Produktion als `www-data` | Vor und nach einem Update | Zeigt ausgeführte und offene Migrationen. | niedrig |
| `php artisan schedule:list` | Produktion als `www-data` | Scheduler prüfen | Zeigt geplante Aufgaben und nächste Läufe. | niedrig |
| `php artisan production:preflight` | Produktion als `www-data` | Vor Aktivierung und bei Betriebsfehlern | Prüft Pflichtkonfiguration, Pfade, Programme, Schlüssel und MySQL, ohne Secrets auszugeben. | niedrig |
| `php artisan production:preflight --configuration-only` | Kontrollierte Prüfung | Konfiguration ohne Runtimezugriffe untersuchen | Überspringt Dateisystem-, Programm- und Datenbankchecks; ersetzt keinen echten Preflight. | niedrig |

In Produktion steht `php artisan` in den Beispielen für:

```sh
sudo -u www-data php /srv/materialpool/current/artisan <befehl>
```

### Wartungsmodus und Caches

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan down` | Produktion, `www-data` | Vor einer freigegebenen Migration oder Restore-Aktion | Sperrt reguläre Zugriffe. | mittel |
| `php artisan up` | Produktion, `www-data` | Erst nach erfolgreichen Smoke-Tests | Hebt den Wartungsmodus auf. | mittel |
| `php artisan optimize` | Produktion, `www-data` | Aktivierung oder kontrollierte Cache-Neuerzeugung | Baut Laravel-Laufzeitcaches neu auf. | mittel |
| `php artisan optimize:clear` | Produktion, `www-data` | Diagnose eines nachgewiesenen Cacheproblems | Entfernt abgeleitete Laravel-Caches; kann Leistung vorübergehend verschlechtern. | mittel |
| `php artisan reload` | Produktion, `www-data` | Nach Releasewechseln | Signalisiert lang laufenden Laravel-Prozessen einen Reload. | mittel |

### Resource-Vorschauen

Geänderte oder neu angelegte Resources planen ihre Vorschauen nach dem Datenbank-Commit automatisch auf der nachrangigen Queue `resource-previews-low` ein. Für Resources, Dokumentseiten und Materialkarten wird dabei ausschließlich die kleine Variante (maximal 640 × 640 Pixel, JPEG-Qualität 80) vorbereitet. Die große Variante (maximal 1536 × 1536 Pixel, ebenfalls JPEG-Qualität 80) entsteht erst beim Öffnen einer Detail- oder Zoomansicht und wird anschließend regulär gecacht. Ändert sich die Resource, aus der ein Material seine Vorschau bezieht, wird auch dessen kleine Materialvorschau auf dieser Queue neu erzeugt. Der Worker verarbeitet weiterhin `default` zuerst; Vorschauen dürfen deshalb bei regulärer Last warten.

Geschützte Vorschauantworten werden vom Browser sieben Tage ausschließlich privat (`private, max-age=604800`) gespeichert. Ein ETag verhindert nach Ablauf unnötige Bildübertragungen. Bereits vorhandene große Cache-Varianten bleiben erhalten; der Backfill plant nur fehlende kleine Varianten.

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan resources:queue-previews [--chunk=100]` | Produktion, `www-data` | Einmalig nach Einführung der Funktion oder gezielt zum Nachziehen bestehender Resources | Plant fehlende kleine Vorschauen in Batches mit 1–1000 Resources auf `resource-previews-low` ein; bei mehrseitigen Dokumenten eine Variante je Seite sowie die zugehörige kleine Materialvorschau. Der Befehl ändert keine Resource-Daten. | mittel; kann Queue und Dateiverarbeitung deutlich auslasten |
| `php artisan resources:clear-preview-cache --force` | Produktion, `www-data` | Nur gezielt bei einem bestätigten Vorschau-Cacheproblem oder vor einem bewusst geplanten vollständigen Neuaufbau | Löscht ausschließlich abgeleitete Resource-Vorschaubilder und temporäre Dokument-PDFs. Anschließend müssen Vorschauen erneut erzeugt werden. Ohne `--force` führt der Befehl keine Löschung aus. | **hoch; löscht abgeleitete Inhalte und kann Folgelast erzeugen** |

Vor einem vollständigen Neuaufbau den Workerzustand prüfen und ausreichend freien Speicher sowie Queue-Kapazität sicherstellen. Nach `resources:clear-preview-cache --force` bei Bedarf `resources:queue-previews --chunk=100` ausführen und `supervisorctl status 'materialpool-default:*'` sowie `php artisan queue:failed` kontrollieren.

### Queue und Scheduler

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `supervisorctl reread && supervisorctl update` | Produktion, `root` | Nach Änderung von `materialpool-worker.conf` | Liest die Supervisor-Konfiguration neu ein und übernimmt Programmänderungen; kann betroffene Worker starten oder stoppen. | mittel |
| `supervisorctl status 'materialpool-default:*'` | Produktion, `root` | Regelmäßige Kontrolle | Zeigt Zustand des Default-Workers. | niedrig |
| `supervisorctl restart 'materialpool-default:*'` | Produktion, `root` | Nach Deployments oder hängendem Worker | Startet den überwachten Default-Worker neu. | mittel |
| `php artisan queue:restart` | Produktion, `www-data` | Kontrolliertes Auslaufen bestehender Worker | Fordert Worker zum Neustart nach ihrem aktuellen Job auf. | mittel |
| `php artisan schedule:run` | Produktion, `www-data` | Scheduler gezielt diagnostizieren | Führt alle aktuell fälligen Tasks einmal aus; kann Backup/Cleanup starten. | mittel |
| `php artisan queue:work database --queue=default,resource-previews-low --sleep=3 --tries=50 --timeout=120 --max-time=3600` | Normalerweise nur Supervisor | Workerdefinition prüfen oder isoliert diagnostizieren | Verarbeitet normale Jobs vor nachrangigen Resource-Vorschauen. Nicht parallel zum regulären Worker starten. | hoch |

Dynamische `bundle_<id>_queue`-Queues werden nicht vom Default-Worker konsumiert. Sie werden im normalen Ablauf über die Bundle-API schrittweise verarbeitet.

### Backups

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan backup:list` | Produktion, `www-data` | Bestand und Alter prüfen | Listet lokale und externe Backups. | niedrig |
| `php artisan backup:monitor` | Produktion, `www-data` | Healthcheck und nach Backupfehlern | Prüft Erreichbarkeit, Alter und Speichergrenzen und verschickt konfigurierte Meldungen. | niedrig |
| `php artisan backup:run` | Produktion, `www-data` | Vor Migrationen oder manuell angefordert | Erstellt ein unverschlüsseltes Datenbank-/Dateibackup auf `backup` und `backup_s3`. | mittel |
| `php artisan backup:clean` | Produktion, `www-data` | Nur nach Prüfung der Aufbewahrungsregeln | Löscht alte Backups gemäß Konfiguration. | hoch |

Ein erfolgreich erzeugtes oder hochgeladenes Archiv ist noch kein verifiziertes Backup. Erst Download und Restore auf einer isolierten MySQL-Instanz belegen die Wiederherstellbarkeit. Die Archive enthalten lesbare Daten und dürfen daher ausschließlich in privaten, restriktiv berechtigten Ablagen liegen.

### Fachbefehle

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan resources:backfill-filesizes [--chunk=100] [--force]` | Nach freigegebener Migration | Fehlende persistierte Dateigrößen ergänzen | Plant Batches mit 1–1000 Resources auf `database/default` ein. | mittel; `--force` berechnet alle geeigneten Werte neu |
| `php artisan resources:check:duplicates` | Nur nach Backup und fachlicher Prüfung | Nachgewiesene Hash-Dubletten bereinigen | Führt Resource-Dubletten und Materialzuordnungen zusammen. | **hoch; Datenmutation** |
| `php artisan import:zefaniabible <absoluter-xml-pfad>` | Nur kontrollierte Administration | Eine geprüfte Zefania-Bibel importieren | Importiert Bibelinhalt aus XML in die Datenbank. | **hoch; Datenmutation** |
| `php artisan bible:import` | Erstinstallation mit TTY, als `www-data` | Bibeltexte und Cross References auswählen | Zwei getrennte Fragen; Übersetzungen direkt von Scrollmapper, Cross References aus dem Release. Rechtehinweis ohne Zustimmungsfrage. | **hoch; Datenmutation** |
| `php artisan bible:import --update-translations --update-cross-references --no-interaction` | Proxmox-Update, als `www-data` | Bereits installierte Daten prüfen | Aktualisiert nur zuvor installierte Datensätze bei geändertem Inhalt. Ohne ausgewählte Übersetzung ist keine GitHub-Verbindung nötig. | **hoch; Datenmutation** |
| `php artisan bible:import --translation=scrollmapper:GerElb1905 --cross-references --no-interaction` | Gezielte Administration, nach Backup | Konkrete Übersetzung und Cross References installieren | `--translation` ist wiederholbar; `--cross-references` installiert ausdrücklich auch ohne Vorbestand. | **hoch; Datenmutation** |
| `php artisan bible:prepare:cross-references` | GitHub-Release-Build | Vor Paketierung | Lädt OpenBible direkt, prüft und erstellt Payload samt Manifest. | mittel; lokale Release-Daten |

### Bundle-Störung beheben

Vor einer Bundle-UUID- oder Foreign-ID-Constraint-Migration ist ausschließlich folgender read-only Preflight zulässig. Er gibt nur Konfliktzählwerte aus und verändert keine Daten:

```sh
sudo -u www-data php /srv/materialpool/current/artisan bundles:preflight-identifiers
```

Bei einem Fehler keine Migration und keine Datenkorrektur starten; zuerst die Konfliktform fachlich klären.

<details>
<summary>Manuelle Wiederaufnahme eines Bundle-Imports</summary>

Dieser Notfallweg verarbeitet ausschließlich einen bereits gestarteten, aktiven Lauf. Er erzeugt keinen Import und kann daher parallel zum Browser genutzt werden. Vorher Bundle-ID, aktiven Lauf und Fehlerursache prüfen. Nie eine Beispiel-ID ungeprüft übernehmen.

Lauf und Queue zunächst nur lesend prüfen:

```sh
sudo -u www-data php /srv/materialpool/current/artisan bundles:work <bundle-id> --dry-run
```

Anschließend denselben Laravel-Database-Worker starten:

```sh
sudo -u www-data php /srv/materialpool/current/artisan bundles:work <bundle-id>
```

Nachkontrolle: Bundle-Fortschritt im UI, aktiver Run und Anwendungslog. Der Browser darf geschlossen werden; die Queue und ein Terminal-Worker laufen unabhängig weiter. Browser und Terminal dürfen denselben Lauf gleichzeitig verarbeiten, weil sie verschiedene Queue-Nachrichten atomar reservieren; die Entity-Locks verhindern parallele Bearbeitung desselben Datensatzes.

</details>

### System- und Logkontrolle

```sh
sudo systemctl status nginx php8.4-fpm mysql supervisor cron
sudo journalctl -u nginx -u php8.4-fpm -u mysql --since today
sudo supervisorctl status 'materialpool-default:*'
sudo tail -n 200 /var/log/supervisor/materialpool-worker.log
sudo tail -n 200 /srv/materialpool/shared/storage/logs/laravel.log
df -h /srv/materialpool
sudo nginx -t
sudo php-fpm8.4 -t
```

Logs können Nutzdaten, URLs oder interne Pfade enthalten. Vor Weitergabe immer prüfen und sensible Werte entfernen. Rechte nicht pauschal mit `chmod -R 777` reparieren; die vorgesehenen Eigentümer sind `materialpool:www-data`, und nur Shared-Storage, `public/uploads` sowie `bootstrap/cache` sind zur Laufzeit beschreibbar.

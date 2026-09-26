# Materialpool

Materialpool ist eine geschützte Laravel-Anwendung zur Verwaltung von Materialien, Dateien und anderen Ressourcen, Schlagwörtern, Bibelstellen, Nutzungen und importierten Bundles. Laravel stellt die Weboberfläche und die versionierten APIs bereit; die Hauptoberfläche läuft als Vue-3-Single-Page-App unter `/vue`.

Diese README richtet sich an drei Zielgruppen:

- **Administration** betreibt, installiert und aktualisiert die Produktionsinstanz.
- **Entwicklung** arbeitet lokal mit Laravel Sail, Vue 3, Pinia und Vite.
- **Anwendung** erklärt die Bedienung für angemeldete Benutzer und Administratoren.

> [!CAUTION]
> Befehle sind immer an einen Kontext gebunden. Produktions-, Entwicklungs- und Testbefehle dürfen nicht ungeprüft gegeneinander ausgetauscht werden. Insbesondere `migrate:fresh` und `db:seed` löschen beziehungsweise verändern Daten und sind ausschließlich für die verifizierte, entbehrliche Testdatenbank bestimmt.

## Inhaltsverzeichnis

- [1. Administration](#1-administration)
  - [1.1 Installation](#11-installation)
  - [1.2 Updates](#12-updates)
  - [1.3 Wartung](#13-wartung)
- [2. Entwicklung](#2-entwicklung)
- [3. Anwendung](#3-anwendung)

### Umgebungen und maßgebliche Dokumentation

| Kennzeichnung | Bedeutung |
| --- | --- |
| **Produktion** | Ubuntu-Server unter `/srv/materialpool`; PHP läuft als `www-data`, Deployments als `materialpool`. |
| **Entwicklung** | Lokale Docker-/Sail-Umgebung; nur lokale Entwicklungsdaten. |
| **Test** | Dedizierte, jederzeit entbehrliche MySQL-Datenbank `testing`; niemals Entwicklungs- oder Produktionsdaten. |

Diese README ist der zentrale Einstieg. Bei Abweichungen gelten die spezielleren Verträge unter [`docs/ai/`](docs/ai/) und insbesondere der [Produktions- und Deploymentvertrag](docs/ai/production-deployment-contract.md), die [Domänen-Invarianten](docs/ai/domain-invariants.md) und die [Quality Gates](docs/ai/quality-gates.md). Die geplante hybride Kontextsuche mit Qdrant, Ollama und KI-Funktionen ist im [Planungs- und Arbeitsvertrag zur Kontextsuche](docs/ai/context-search-ai-contract.md) festgehalten. `AGENTS.md` regelt zusätzlich die Arbeit von KI-Agenten. Die Pflege dieser README und ihrer Bilder ist in [`docs/ai/readme-maintenance.md`](docs/ai/readme-maintenance.md) festgelegt.

## 1. Administration

Die Produktion läuft auf einem einzelnen Ubuntu-24.04-LTS-Server mit Nginx, PHP-FPM 8.4 und MySQL 8 hinter einem externen TLS-Reverse-Proxy. Supervisor betreibt den Default-Queue-Worker; Cron startet jede Minute Laravels Scheduler. Node.js wird auf dem Produktionsserver nicht benötigt.

### 1.1 Installation

#### Voraussetzungen

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

#### Server vorbereiten

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

Die Produktionsdatei `/srv/materialpool/shared/.env` enthält mindestens die im [Produktionsvertrag](docs/ai/production-deployment-contract.md#zwingende-produktionskonfiguration) genannten Werte. Secrets gehören weder in Git noch in Terminalprotokolle. `TRUSTED_PROXIES` darf niemals `*`, `**` oder `REMOTE_ADDR` enthalten. Die vorhandenen Passport-Schlüssel liegen unter dem Shared-Storage; der private Schlüssel muss Modus `0600` haben.

Vor dem ersten Release prüfen:

```sh
sudo nginx -t
sudo php-fpm8.4 -t
sudo supervisord -t
sudo systemctl status nginx php8.4-fpm mysql supervisor cron
```

#### Release bauen

Der Build akzeptiert ausschließlich einen exakten Commit und bricht bei nicht committeten Änderungen im Materialpool-Arbeitsbaum ab:

```sh
ops/production/build-release.sh <40-stellige-commit-id> /absoluter/ausgabeordner
```

Das Skript baut im festgelegten `sail-8.4/app`-Image, installiert Composer- und npm-Abhängigkeiten aus den Lockfiles, erzeugt die Vite-Assets und schreibt Archiv, Release-Manifest und SHA-256-Datei. Das Archiv enthält weder `.env` noch `node_modules` oder `vendor`.

#### Release hochladen und aktivieren

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

#### Erfolg prüfen

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

### 1.2 Updates

#### Release ohne Datenbankmigration

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

#### Release mit Datenbankmigration

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

Der erste Passport-13-Cutover folgt zusätzlich vollständig [`docs/ai/passport-13-client-migration.md`](docs/ai/passport-13-client-migration.md).

### 1.3 Wartung

In den Tabellen bedeutet **niedrig** „lesend oder regulärer Healthcheck“, **mittel** „ändert Laufzeitzustand“ und **hoch** „verändert Daten oder löscht abgeleitete beziehungsweise persistente Inhalte“.

#### Zustand und Diagnose

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

#### Wartungsmodus und Caches

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan down` | Produktion, `www-data` | Vor einer freigegebenen Migration oder Restore-Aktion | Sperrt reguläre Zugriffe. | mittel |
| `php artisan up` | Produktion, `www-data` | Erst nach erfolgreichen Smoke-Tests | Hebt den Wartungsmodus auf. | mittel |
| `php artisan optimize` | Produktion, `www-data` | Aktivierung oder kontrollierte Cache-Neuerzeugung | Baut Laravel-Laufzeitcaches neu auf. | mittel |
| `php artisan optimize:clear` | Produktion, `www-data` | Diagnose eines nachgewiesenen Cacheproblems | Entfernt abgeleitete Laravel-Caches; kann Leistung vorübergehend verschlechtern. | mittel |
| `php artisan reload` | Produktion, `www-data` | Nach Releasewechseln | Signalisiert lang laufenden Laravel-Prozessen einen Reload. | mittel |

#### Resource-Vorschauen

Geänderte oder neu angelegte Resources planen ihre Vorschauen nach dem Datenbank-Commit automatisch auf der nachrangigen Queue `resource-previews-low` ein. Für Resources, Dokumentseiten und Materialkarten wird dabei ausschließlich die kleine Variante (maximal 640 × 640 Pixel, JPEG-Qualität 80) vorbereitet. Die große Variante (maximal 1536 × 1536 Pixel, ebenfalls JPEG-Qualität 80) entsteht erst beim Öffnen einer Detail- oder Zoomansicht und wird anschließend regulär gecacht. Ändert sich die Resource, aus der ein Material seine Vorschau bezieht, wird auch dessen kleine Materialvorschau auf dieser Queue neu erzeugt. Der Worker verarbeitet weiterhin `default` zuerst; Vorschauen dürfen deshalb bei regulärer Last warten.

Geschützte Vorschauantworten werden vom Browser sieben Tage ausschließlich privat (`private, max-age=604800`) gespeichert. Ein ETag verhindert nach Ablauf unnötige Bildübertragungen. Bereits vorhandene große Cache-Varianten bleiben erhalten; der Backfill plant nur fehlende kleine Varianten.

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan resources:queue-previews [--chunk=100]` | Produktion, `www-data` | Einmalig nach Einführung der Funktion oder gezielt zum Nachziehen bestehender Resources | Plant fehlende kleine Vorschauen in Batches mit 1–1000 Resources auf `resource-previews-low` ein; bei mehrseitigen Dokumenten eine Variante je Seite sowie die zugehörige kleine Materialvorschau. Der Befehl ändert keine Resource-Daten. | mittel; kann Queue und Dateiverarbeitung deutlich auslasten |
| `php artisan resources:clear-preview-cache --force` | Produktion, `www-data` | Nur gezielt bei einem bestätigten Vorschau-Cacheproblem oder vor einem bewusst geplanten vollständigen Neuaufbau | Löscht ausschließlich abgeleitete Resource-Vorschaubilder und temporäre Dokument-PDFs. Anschließend müssen Vorschauen erneut erzeugt werden. Ohne `--force` führt der Befehl keine Löschung aus. | **hoch; löscht abgeleitete Inhalte und kann Folgelast erzeugen** |

Vor einem vollständigen Neuaufbau den Workerzustand prüfen und ausreichend freien Speicher sowie Queue-Kapazität sicherstellen. Nach `resources:clear-preview-cache --force` bei Bedarf `resources:queue-previews --chunk=100` ausführen und `supervisorctl status 'materialpool-default:*'` sowie `php artisan queue:failed` kontrollieren.

#### Queue und Scheduler

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `supervisorctl reread && supervisorctl update` | Produktion, `root` | Nach Änderung von `materialpool-worker.conf` | Liest die Supervisor-Konfiguration neu ein und übernimmt Programmänderungen; kann betroffene Worker starten oder stoppen. | mittel |
| `supervisorctl status 'materialpool-default:*'` | Produktion, `root` | Regelmäßige Kontrolle | Zeigt Zustand des Default-Workers. | niedrig |
| `supervisorctl restart 'materialpool-default:*'` | Produktion, `root` | Nach Deployments oder hängendem Worker | Startet den überwachten Default-Worker neu. | mittel |
| `php artisan queue:restart` | Produktion, `www-data` | Kontrolliertes Auslaufen bestehender Worker | Fordert Worker zum Neustart nach ihrem aktuellen Job auf. | mittel |
| `php artisan schedule:run` | Produktion, `www-data` | Scheduler gezielt diagnostizieren | Führt alle aktuell fälligen Tasks einmal aus; kann Backup/Cleanup starten. | mittel |
| `php artisan queue:work database --queue=default,resource-previews-low --sleep=3 --tries=50 --timeout=120 --max-time=3600` | Normalerweise nur Supervisor | Workerdefinition prüfen oder isoliert diagnostizieren | Verarbeitet normale Jobs vor nachrangigen Resource-Vorschauen. Nicht parallel zum regulären Worker starten. | hoch |

Dynamische `bundle_<id>_queue`-Queues werden nicht vom Default-Worker konsumiert. Sie werden im normalen Ablauf über die Bundle-API schrittweise verarbeitet.

#### Backups

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan backup:list` | Produktion, `www-data` | Bestand und Alter prüfen | Listet lokale und externe Backups. | niedrig |
| `php artisan backup:monitor` | Produktion, `www-data` | Healthcheck und nach Backupfehlern | Prüft Erreichbarkeit, Alter und Speichergrenzen und verschickt konfigurierte Meldungen. | niedrig |
| `php artisan backup:run` | Produktion, `www-data` | Vor Migrationen oder manuell angefordert | Erstellt ein unverschlüsseltes Datenbank-/Dateibackup auf `backup` und `backup_s3`. | mittel |
| `php artisan backup:clean` | Produktion, `www-data` | Nur nach Prüfung der Aufbewahrungsregeln | Löscht alte Backups gemäß Konfiguration. | hoch |

Ein erfolgreich erzeugtes oder hochgeladenes Archiv ist noch kein verifiziertes Backup. Erst Download und Restore auf einer isolierten MySQL-Instanz belegen die Wiederherstellbarkeit. Die Archive enthalten lesbare Daten und dürfen daher ausschließlich in privaten, restriktiv berechtigten Ablagen liegen.

#### Fachbefehle

| Befehl | Umgebung/Benutzer | Wann verwenden? | Wirkung | Risiko |
| --- | --- | --- | --- | --- |
| `php artisan resources:backfill-filesizes [--chunk=100] [--force]` | Nach freigegebener Migration | Fehlende persistierte Dateigrößen ergänzen | Plant Batches mit 1–1000 Resources auf `database/default` ein. | mittel; `--force` berechnet alle geeigneten Werte neu |
| `php artisan resources:check:duplicates` | Nur nach Backup und fachlicher Prüfung | Nachgewiesene Hash-Dubletten bereinigen | Führt Resource-Dubletten und Materialzuordnungen zusammen. | **hoch; Datenmutation** |
| `php artisan import:zefaniabible <absoluter-xml-pfad>` | Nur kontrollierte Administration | Eine geprüfte Zefania-Bibel importieren | Importiert Bibelinhalt aus XML in die Datenbank. | **hoch; Datenmutation** |

#### Bundle-Störung beheben

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

#### System- und Logkontrolle

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

## 2. Entwicklung

### Lokale Voraussetzungen und Start

- Docker mit Compose-Unterstützung;
- Composer zum initialen Aufbau von `vendor/` oder ein kontrollierter Composer-Container;
- Node.js gemäß [`.node-version`](.node-version), aktuell 24.21.0;
- npm gemäß `package.json`, aktuell 11.19.0.

Abhängigkeiten werden aus `composer.lock` und `package-lock.json` installiert. Keine Updates als Nebeneffekt eines lokalen Starts durchführen.

```sh
composer install
test -f .env || cp .env.example .env
php artisan key:generate
npm ci --ignore-scripts
./vendor/bin/sail up -d
```

`composer install` und die beiden Host-PHP-Befehle setzen PHP 8.4 mit den benötigten Erweiterungen voraus. Ist das lokal nicht verfügbar, Composer und Artisan in einem passenden PHP-8.4-Container ausführen. Das ältere Host-PHP ist keine gültige Referenz für das Projekt.

Die lokale `.env` muss auf den Sail-MySQL-Dienst zeigen (`DB_HOST=mysql`). Entwicklungsdaten und Testdaten bleiben getrennt. Anschließend das Entwicklungssystem starten:

```sh
./vendor/bin/sail artisan dev
```

Laravel 13 startet damit standardmäßig Server, Queue-Listener, Logansicht und `npm run dev` für Vite. `npm run dev` generiert zuerst die JavaScript-Übersetzungen und startet danach Vite mit HMR.

### Kontextsuche

Die Kontextsuche wird gemäß dem [Planungs- und Arbeitsvertrag](docs/ai/context-search-ai-contract.md) auf Qdrant aufgebaut. Der lokale Compose-Stack verwendet die fest gepinnte Qdrant-Version 1.19.1. Die REST-Schnittstelle wird nur an `127.0.0.1` veröffentlicht und intern mit `QDRANT_API_KEY` geschützt. Der mitgelieferte Schlüssel ist ausschließlich für lokale Entwicklung bestimmt; produktive Schlüssel werden über die Server-Secret-Konfiguration gesetzt und Qdrant wird dort nur über ein abgesichertes privates Netz beziehungsweise TLS erreicht.

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

#### Ollama-Modellprofil und Pool

Der Kontextsuche-Pool verwendet ausschließlich `CONTEXT_SEARCH_EMBEDDING_MODEL`; ein generatives Modell kann daher nicht versehentlich Suchvektoren erzeugen. Jeder Poolserver muss exakt dieses Modell mit demselben Modell-Digest bereitstellen. Die Serverliste folgt dem Format `name=url|max_parallel_jobs`, mehrere Server werden durch Komma getrennt. Zugangsdaten stehen getrennt in `CONTEXT_SEARCH_OLLAMA_API_KEYS` als `name=secret`-Einträge und gehören ausschließlich in Server-Secrets, nie ins Repository.

Vor einer Indexgeneration ist auf jedem Ollama-Server der Modell-Digest über `GET /api/tags` zu ermitteln und als `CONTEXT_SEARCH_EMBEDDING_DIGEST` zu setzen. Danach prüft der folgende lesende Selbsttest Modellname, Digest und die tatsächlich gelieferte Vektordimension auf allen konfigurierten Servern:

```sh
./vendor/bin/sail artisan context-search:ollama:verify
```

### Manuelle Indexierung von PDF- und Textressourcen (Stufe 1)

Die erste Indexierung wird ausschließlich bewusst per Kommando gestartet; Änderungen an Materialien oder Ressourcen lösen noch keinen Indexlauf aus. Voraussetzung sind eine aktivierte, provisionierte Qdrant-Collection, ein erfolgreich verifiziertes Embedding-Profil und ein Datenbank-Queue-Worker für `context-search-indexing`.

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan queue:work database --queue=context-search-indexing --tries=3 --timeout=180
./vendor/bin/sail artisan context-search:index 123
./vendor/bin/sail artisan context-search:index --after=123 --limit=100
./vendor/bin/sail artisan context-search:index --resume=<indexlauf-uuid>
```

Der Laufzustand wird in MySQL gespeichert und ein fehlgeschlagener Lauf kann anhand seiner UUID fortgesetzt werden. Jeder Qdrant-Punkt enthält die Ressourcen-ID, die Dokumentrevision, die PDF-Seite beziehungsweise Textseite sowie Zeichenpositionen; die Originaldatei bleibt außerhalb von Qdrant. Für PDF-Seiten mit zu wenig eingebettetem Text wird Tesseract mit den Sprachpaketen `deu` und `eng` verwendet. Das Sail-Image installiert diese Werkzeuge beim Neuaufbau automatisch; auf Produktionsservern müssen `pdftotext`, `pdfinfo`, `pdftoppm`, `tesseract`, `tesseract-ocr-deu` und `tesseract-ocr-eng` vor dem Start eines Indexworkers verfügbar sein.

Ein abweichender Digest, Modellname oder eine andere Dimension ist ein Konfigurationsfehler: Der Pool stoppt dann, statt Vektoren verschiedener Modelle zu mischen. Bei Netzwerkfehlern, Timeouts, Überlastung oder 5xx-Antworten verteilt er eine Anfrage deterministisch auf den nächsten gesunden Server. Nach den konfigurierbaren Fehlschlägen öffnet der serverbezogene Circuit Breaker zeitweise; jede Serverdefinition besitzt zudem ihr eigenes gemeinsames Parallelitätslimit. Erst der erfolgreiche Selbsttest berechtigt zum Provisionieren und Befüllen einer Indexgeneration.

### Evaluationsdatensätze aus Produktion

Kalibrierung, Modellvergleich und Abnahme erfolgen ausschließlich in einer isolierten Evaluationsumgebung. Produktion darf hierfür nur einen eingefrorenen Datensatz erzeugen und als Archiv exportieren; die Befehle rufen weder Ollama noch Qdrant auf. Sie berücksichtigen ausschließlich PDF- und Textressourcen. Die Produktionsdatenbank wird nicht kopiert.

Die Ablage `CONTEXT_SEARCH_EVALUATION_PATH` muss auf beiden Systemen ein privater, nicht durch Nginx erreichbarer Pfad mit restriktiven Rechten sein. Standardmäßig liegt sie unter `storage/app/context-search-evaluation`. Die Übertragung des Archivs ist nach der getroffenen Entscheidung unverschlüsselt zulässig; Archiv- und Manifest-Prüfsumme sind vor dem Import zwingend zu prüfen. Private Inhalte verlangen die sichtbare Freigabe `--include-private` und eine Begründung. Keine Titel oder Inhalte in Shell-Historien, Tickets oder Logs übernehmen.

```sh
# Produktion: ausschließlich inhaltsfreie Größenordnung vor der Auswahl prüfen.
./vendor/bin/sail artisan context-search:dataset:inventory --json
# Produktion: Auswahl anhand bekannter IDs einfrieren und in den privaten Exportordner schreiben.
./vendor/bin/sail artisan context-search:dataset:freeze calibration \
  --materials=101,102,103 --include-private --reason='Kuratiertes Kalibrierungsset'
./vendor/bin/sail artisan context-search:dataset:export <datensatz-uuid>
./vendor/bin/sail artisan context-search:dataset:verify exports/<datensatz-uuid>.zip
```

Die UUID und die beiden Prüfsummen sind die Übergabedaten. Das Archiv wird durch einen vertrauenswürdigen Administrator in die private Evaluationsablage übertragen; eine spätere Importstufe prüft dieselben Werte erneut. Der `acceptance`-Datensatz ist ein unveränderlicher Holdout: Wird er zur Kalibrierung verwendet, muss ein neuer Abnahmedatensatz erzeugt werden.

Global Admins können die Auswahl außerdem über **KI-Datensätze** im persönlichen Benutzermenü kuratieren. Eine ganze Datensatzkarte ist anklickbar und per Tastatur bedienbar; Icons und Beschriftungen haben einheitliche Abstände. Eine seitenbezogene Sammelaktion fügt alle vollständig auswählbaren Material-/Ressourcenblöcke der sichtbaren Seite hinzu oder entfernt – als jeweils einzige angezeigte Aktion – deren lokale Auswahl. Sie verändert keine Auswahl anderer Seiten und überspringt gesperrte Blöcke. Der Server ergänzt und prüft den vollständigen zusammenhängenden Block aus Materialien und PDF-/Textressourcen verbindlich. OCR darf dieselben vollständigen Blöcke wie Kalibrierung oder Abnahme enthalten; Last und Kapazität dürfen als unabhängige Betriebsprüfungen mit allen anderen Zwecken einschließlich einander überlappen. Kalibrierung und Abnahme bleiben strikt voneinander getrennt; Last-/Kapazitätsmessungen dürfen nicht zur Anpassung semantischer Relevanz oder Schwellenwerte verwendet werden. Der linke Vorschaubereich bleibt beim Scrollen sichtbar, die Aktionen liegen auf dem Bild, und beim Wechsel des Materials wird die vorherige Grafik bis zum Laden der neuen Vorschau durch einen Ladeindikator ersetzt; falls keine Vorschau verfügbar ist, erscheint ein entsprechender Hinweis. Modalvorschauen erlauben das Durchblättern aller bekannten PDF-Seiten; Material- und Ressourcendetails öffnen jeweils in einem neuen Tab. Zugehörigkeiten und Konflikte sind sichtbar. Aus dem aktiven, noch veränderbaren Entwurf können Blöcke nach Bestätigung wieder als ganzer Block entfernt werden. Die Filter einschließlich Bundle beziehungsweise eigene Materialien ohne Bundle sowie die Seitennummer sind in der Adresse enthalten; die Seitennavigation erlaubt Einzelschritte und Zehnersprünge. Ein Server prüft vor dem Speichern die Versionsnummer und die Zweckregeln für Überschneidungen; die Vorschau im Browser ist keine Sicherheitsentscheidung. Die Zweck-Icons zeigen einen zugänglichen Fortschrittsdialog mit Ist-/Sollmengen und Teilquoten. Private Quellen benötigen eine ausdrückliche Auswahl samt Begründung. Erst ein vollständiger Entwurf kann eingefroren und danach exportiert werden.

Nach dem Upgrade prüft ein Administrator vorhandene eingefrorene Datensätze zuerst lesend. Konflikte werden nie automatisch aufgelöst. Nur wenn die Ausgabe konfliktfrei ist, darf die explizite Übernahme erfolgen:

```sh
./vendor/bin/sail artisan context-search:dataset:reconcile-memberships
./vendor/bin/sail artisan context-search:dataset:reconcile-memberships --apply
```

Der lesende Abgleich zeigt zusätzlich eine grafische, inhaltsfreie Terminalübersicht der vertraglich empfohlenen Sollmengen für Kalibrierung, Abnahme, OCR, Last und Kapazität. Sie enthält die jeweilige Ressourcen- und Materialmenge, Fortschrittsbalken, verbleibende Mengen sowie den eindeutigen Mindestbedarf und die Reserve geeigneter PDF-/Textressourcen gegenüber den exklusiven Zielen für Kalibrierung und Abnahme; OCR, Last und Kapazität sind als überlappende Prüfvolumina ausgewiesen. Die Übersicht ist eine Kuratierungs- und Kapazitätshilfe; sie ändert weder Auswahl noch Sollmengen.

Auf der isolierten Evaluationsmaschine wird `CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED=true` gesetzt. Diese Einstellung ist auf Produktion verboten. Nach dem Transfer in `incoming/` wird erst geprüft und dann importiert:

```sh
./vendor/bin/sail artisan context-search:dataset:verify incoming/<datensatz-uuid>.zip
./vendor/bin/sail artisan context-search:dataset:import incoming/<datensatz-uuid>.zip
```

Der Import legt keinen Produktionsbenutzer an: Er verwendet ausschließlich einen lokalen technischen Importbenutzer und schreibt PDF-Dateien in die Evaluationsablage. Derselbe Datensatz kann mit identischem Manifest erneut ausgeführt werden, ohne Materialien oder Ressourcen zu duplizieren.

Für gezielte Diagnose können die Prozesse einzeln laufen:

```sh
./vendor/bin/sail artisan serve
./vendor/bin/sail artisan queue:listen --tries=1 --timeout=0
npm run dev
```

### Tinker, Shell und Artisan

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
| `npm run docs:screenshots` | README-Bilder aus synthetischen Daten neu erzeugen. |
| `npm run docs:check` | README-Struktur, Links und Screenshot-Metadaten prüfen. |

### Vue-3-Entwicklung

- Einstieg: `resources/js/apps/main/index.js`; Seiten und Router liegen unter `resources/js/apps/main/`.
- Gemeinsamer Zustand liegt in Pinia-Stores unter `resources/js/apps/main/stores/`.
- Wiederverwendbare Fachkomponenten liegen unter `resources/js/components/`.
- Bootstrap 5, BootstrapVueNext und die Materialpool-Adapter bilden das bestehende UI-System.
- Sichtbare Texte werden über `resources/lang/`, insbesondere `resources/lang/de/pool.php`, gepflegt.
- API v1/v2, CSRF, Session, Passport, Payloads und Fehlerbehandlung sind bestehende Verträge.
- Responsive Verhalten, Tastaturzugang, Fokus, Lade-, Leer- und Fehlerzustände gehören zu jeder UI-Prüfung.

`@vue/compat`, Vuex, BootstrapVue, Webpack und Laravel Mix sind entfernt und dürfen nicht wieder eingeführt werden. Bestehende Options-API-Komponenten müssen nicht aus Stilgründen umgeschrieben werden. Für neue komplexe oder wiederverwendbare Zustandslogik ist die Composition API sinnvoll; die Entscheidung richtet sich nach dem konkreten Nutzen.

### Tests und isolierte Datenbank

Die verbindliche Backend-Referenz ist der Sail-PHP-8.4-Container. Vor destruktiven Testbefehlen muss die aufgelöste Verbindung ausdrücklich geprüft werden:

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

| Änderung | Mindestprüfung |
| --- | --- |
| PHP/Backend | PHP-Syntax und betroffene PHPUnit-Tests im Sail-Container. |
| API/Policy | Erfolg, Validierung, 401/403/404/422/500 und unveränderte Payloads. |
| Resource/Datei | Storage-Disk, Eventfolge, Cache-Invalidation und repräsentativer Dateityp. |
| Bundle/Queue | Queue-Name, Reihenfolge, Wiederholung und Fehlerpfad mit Testdaten. |
| Vue/Sass | Lint, Unit-Test, Production-Build sowie Desktop-/Mobile-Prüfung. |
| Dokumentation | Diff-, Link- und Konsistenzprüfung; bei UI-Bildern zusätzlich Screenshotlauf. |

Die vollständigen und jeweils aktuellen Befehle stehen in den [Quality Gates](docs/ai/quality-gates.md).

### Troubleshooting

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

## 3. Anwendung

### Anmeldung und Navigation

Materialpool ist geschützt. Nach erfolgreicher Anmeldung öffnet `/vue` die Startseite. Oben stehen – abhängig von den zugewiesenen Berechtigungen – Upload, neue Textressource, Bibel, Suchmaske, Schnellsuche und das Benutzerkonto zur Verfügung. Das Menü **Bearbeiten** erscheint für Global-Admins und Benutzer mit passenden Verwaltungsrechten. Auf kleinen Bildschirmen wird die Navigation über den Menüschalter geöffnet.

<!-- README-SCREENSHOT
id: login
route: /login
state: leeres Anmeldeformular ohne Debugleiste
role: nicht angemeldet
viewport: desktop-webkit (1440x900)
fixture: synthetisch, keine Datenbank
source: tests/browser/login.spec.js#@visual-login-page-baseline
refresh: Blade-Layout, Loginformular, Auth-Texte oder globale Styles geändert
-->
![Anmeldeseite mit Feldern für E-Mail-Adresse und Passwort](docs/readme/screenshots/login-desktop.png)

*Anmeldung: Der Zugang zur Anwendung erfolgt mit dem eingerichteten Benutzerkonto.*

<!-- README-SCREENSHOT
id: start-navigation
route: /vue/
state: Startseite mit sichtbarer Desktop-Hauptnavigation
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-application-mounts-with-synthetic-bootstrap-data
refresh: Hauptnavigation, Startseite, Rollenanzeige oder globale Styles geändert
-->
![Materialpool-Startseite mit Hauptnavigation und Schnellzugriffen](docs/readme/screenshots/start-navigation-desktop.png)

*Startseite: Die Hauptfunktionen sind sowohl in der oberen Navigation als auch als Schnellzugriffe erreichbar.*

<!-- README-SCREENSHOT
id: start-navigation-mobile
route: /vue/
state: Startseite mit ausgeklappter mobiler Navigation
role: Benutzer
viewport: mobile-webkit (390x844)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-application-mounts-with-synthetic-bootstrap-data
refresh: Mobile Navigation, Breakpoints, Startseite oder globale Styles geändert
-->
![Mobile Materialpool-Startseite mit ausgeklappter Navigation](docs/readme/screenshots/start-navigation-mobile.png)

*Mobil: Der Menüschalter blendet Navigation, Suche und Benutzerkonto ein.*

Im Benutzermenü befinden sich für Global-Admins **Benutzerverwaltung** und **Abmelden**. **Einstellungen** ist derzeit sichtbar, aber deaktiviert. Der System-Shutdown erscheint nur mit dem Recht `system.shutdown`; Global-Admins besitzen dieses Recht stets.

### Begriffe

| Begriff | Bedeutung |
| --- | --- |
| **Material** | Inhaltlicher Eintrag mit Titel, Beschreibung, Bewertung und Zuordnungen. |
| **Resource** | Wiederverwendbarer Träger eines Inhalts: Datei, Bild, Audio, Video, PDF, URL, Text oder Buch. |
| **Schlagwort** | Hierarchisch organisiertes Tag; Typen sind Thema, Person, Ort und Sprache. |
| **Bibelstelle** | Vers oder Versbereich, der einem Material mit einer Relevanz zugeordnet wird. |
| **Bundle** | Importierbarer externer Bestand aus Materialien und Resources. |
| **Nutzung** | Dokumentierter Einsatz eines Materials mit Zeitpunkt, Ort, Anlass und Benutzer. |

Ein Material kann mehrere Resources enthalten. Dieselbe Resource kann mehreren Materialien zugeordnet sein; bei begrenzbaren Medien kann jede Zuordnung zusätzlich Seiten oder Zeitbereiche festlegen.

### Suchen und Finden

Die **Schnellsuche** in der Navigation sucht direkt nach einem eingegebenen Begriff. Die **Suchmaske** bietet strukturierte Suchzeilen:

1. Begriff eingeben und einen Vorschlag auswählen.
2. Mehrere Werte innerhalb einer Zeile kombinieren.
3. Mit **+** eine weitere UND-Zeile ergänzen oder mit **−** entfernen.
4. Die Optimierungsfunktion kann verwandte Schlagwörter beziehungsweise Bibelstellen vorschlagen.
5. Ergebnisse öffnen oder mit der Pagination durch weitere Seiten wechseln.

<!-- README-SCREENSHOT
id: search-results
route: /vue/search/1*Jugendarbeit
state: strukturierte Suche mit zwei Ergebnissen
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/readme-screenshots.spec.js#search-results
refresh: Suchmaske, Ergebnisliste, Pagination oder Suchtexte geändert
-->
![Suchmaske mit Suchbegriff, zwei Materialergebnissen und Seitennavigation](docs/readme/screenshots/search-results-desktop.png)

*Suchergebnisse: Karten zeigen Titel, Beschreibung, Resource-Typen, Schlagwörter und Bibelstellen.*

### Materialien verwalten

Die Materialliste zeigt vorhandene Materialien seitenweise. Ein Klick auf eine Karte öffnet das Materialdetail. Links erscheinen die zugeordneten Resources, rechts die Bearbeitungsseitenleiste mit den Tabs **Material**, **Zuordnungen** und **Meta**. Bei einem noch ressourcenlosen Material stehen berechtigten Benutzern die Upload-, Zuordnungs- und Textressourcen-Aktionen zusätzlich direkt im Inhaltsbereich zur Verfügung; ohne Änderungsrecht ist der Tab **Zuordnungen** ausgeblendet.

<!-- README-SCREENSHOT
id: material-detail
route: /vue/material/1
state: Materialdetail ohne Resource mit Bearbeitungsseitenleiste
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-3-datepicker-keeps-the-german-input-and-calendar-interaction
refresh: Materialdetail, Sidebar, Resource-Karte, Berechtigungen oder Materialaktionen geändert
-->
![Materialdetail mit leerem Resource-Bereich und Feldern für Titel, Beschreibung und Zuordnungen](docs/readme/screenshots/material-detail-desktop.png)

*Materialdetail: Der Inhaltsbereich und die direkt bearbeitbaren Metadaten stehen nebeneinander; hier ist noch keine Resource zugeordnet.*

Im Tab **Material** lassen sich Titel, Datum, Autor, Beschreibung, Bibelstellen, Themen, Personen, Orte, Sprachen und Bewertung pflegen. Änderungen werden über die vorhandenen Feldaktionen gespeichert; ungespeicherte Werte sind farblich markiert.

Welche Aktionen verfügbar sind, wird serverseitig aus den Gruppenrechten ermittelt. Erstellen, Metadatenpflege, Resource-Zuordnungen und Löschen sind getrennte Rechte; `*-own` gilt nur für selbst erstellte Datensätze, `*-all` zusätzlich für fremde. Ausgeblendete oder deaktivierte UI-Aktionen ersetzen nie die serverseitige Prüfung.

Im Tab **Zuordnungen** werden unter anderem Nutzungen und Resource-Beziehungen verwaltet. Relevanzen von Schlagwörtern und Bibelstellen beeinflussen ihre Gewichtung. Bei einer Resource-Zuordnung kann **Resource zuordnen** eine vorhandene Resource suchen und verbinden. Begrenzbare PDFs, Videos und Audios können pro Material auf Seiten oder Zeiträume eingeschränkt werden.

Im Tab **Meta** stehen technische Informationen und Herkunftsdaten. Die obere Aktionsleiste bietet – abhängig vom Zustand – Download/Export, Duplizieren und Löschen.

> [!WARNING]
> Das Lösen einer Resource entfernt die Zuordnung, nicht zwingend die Resource selbst. Löschen kann Beziehungen, Foreign-IDs, lokale Dateien, Caches und Bereinigungsjobs betreffen. Bestätigungsdialoge und sichtbare Warnungen sorgfältig lesen.

### Resources anlegen und bearbeiten

Über das Upload-Symbol können eine oder mehrere Dateien ausgewählt oder in die Uploadfläche gezogen werden. Die Option **automatisch Material erzeugen** erstellt nach dem Upload zu jeder neuen Resource ein Material.

<!-- README-SCREENSHOT
id: resource-upload
route: /vue/resource/create
state: leere Uploadfläche mit aktivierter Materialoption
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-3-uploader-keeps-multipart-success-and-error-handling
refresh: Uploader, Uploadoptionen, Validierung oder Texte geändert
-->
![Seite zum Hochladen neuer Resources mit Dropzone und Materialoption](docs/readme/screenshots/resource-upload-desktop.png)

*Upload: Dateien können gewählt oder per Drag-and-drop hinzugefügt werden.*

Über **Text erstellen** entsteht eine Textresource. Metadaten werden separat eingegeben; der Textbereich zeigt eine Markdown-Vorschau. Erst valide Eingaben können gespeichert werden.

Das Resource-Detail besitzt drei Tabs:

- **Vorschau** zeigt den Inhalt passend zum Resource-Typ.
- **Materialien** zeigt alle Zuordnungen, Limitierungen und Aktionen zum Öffnen, Kopieren oder Entfernen.
- **MetaInfo** enthält ID, Ersteller, Notizen, Zeitstempel, Hash, Dateigröße, Web-URL, Öffentlichkeit und Dateiinformationen.

<!-- README-SCREENSHOT
id: resource-detail
route: /vue/resource/42
state: Resource-Detail im Tab MetaInfo
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#resource-detail-cards-and-multi-page-pagination-keep-their-application-contracts
refresh: Resource-Detail, Tabs, MetaInfo oder Resource-Aktionen geändert
-->
![Resource-Detail mit geöffnetem MetaInfo-Tab und technischen Angaben](docs/readme/screenshots/resource-detail-desktop.png)

*Resource-Detail: Metadaten und Sichtbarkeit können unabhängig von den Materialzuordnungen gepflegt werden.*

Bei mehrseitigen PDFs öffnet **Seiten zuordnen** eine Übersicht. Seiten auswählen und anschließend entweder ein neues Material erzeugen oder die Auswahl einem vorhandenen Material hinzufügen. `Strg`+`N` löst ebenfalls **Neu** aus, wenn Seiten markiert sind.

<!-- README-SCREENSHOT
id: pdf-page-assignment
route: /vue/resource/42/assign
state: PDF-Seitenübersicht mit markierten Seiten
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#assign-app-keeps-page-selection-attachment-and-nested-image-dialogs
refresh: AssignApp, Seitenkarten, Auswahlaktionen oder PDF-Vorschau geändert
-->
![PDF-Seitenübersicht mit Seitenauswahl und Aktionen für Materialzuordnungen](docs/readme/screenshots/pdf-page-assignment-desktop.png)

*PDF-Zuordnung: Ausgewählte Seiten können ein neues Material bilden oder einem vorhandenen Material hinzugefügt werden.*

### Bibel lesen

Unter **Bibel** wird eine Stelle wie `Johannes 3,16` eingegeben. Die Auswahl rechts wechselt zwischen den installierten Übersetzungen. Die Route speichert den ausgewählten Versbereich, sodass die Ansicht direkt verlinkt oder neu geladen werden kann. Verknüpfte Bibelstellen sind außerdem in Suche und Materialdetail interaktiv.

<!-- README-SCREENSHOT
id: bible-reader
route: /vue/readbible/1001001-1001002
state: zwei Verse aus 1. Mose mit ausgewählter deutscher Übersetzung
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#bible-reader-loads-and-selects-cached-translations
refresh: Bibelleser, Übersetzungsauswahl, Versdarstellung oder Bibelsuche geändert
-->
![Bibelleser mit Eingabefeld, Übersetzungsauswahl und zwei angezeigten Versen](docs/readme/screenshots/bible-reader-desktop.png)

*Bibelleser: Versbereich und Übersetzung können unabhängig voneinander gewählt werden.*

### Verwaltung und administrative Funktionen

> [!IMPORTANT]
> Die Benutzer- und Gruppenverwaltung ist ausschließlich für Global-Admins sichtbar. Schlagwort-, Bundle- und Shutdown-Funktionen können über eigene Gruppenrechte freigegeben werden. Die serverseitige Autorisierung bleibt maßgeblich; ein verborgenes Menü ist kein Sicherheitsmechanismus.

#### Benutzer und Gruppen

Global-Admins öffnen unter **Benutzerkonto → Benutzerverwaltung** die Seite `/vue/admin/users`. Dort können sie Benutzer suchen, nach Status filtern, einladen, Gruppen zuweisen, sperren oder wieder aktivieren sowie Einladungen erneut senden. Neue Konten bleiben bis zum erfolgreichen Festlegen eines Passworts im Status **Eingeladen**; der Einladungslink ist 60 Minuten gültig. Gesperrte Konten können sich weder über Web noch Passport anmelden, und bestehende Access-/Refresh-Tokens werden beim Sperren widerrufen.

Im Bereich **Gruppen** werden ausschließlich die fest definierten Systemrechte zugeordnet. Direkte Benutzerrechte sind nicht vorgesehen. Die Gruppe **Standardnutzer** schützt den bisherigen Arbeitsablauf für eigene Materialien und Resources. Eine noch verwendete Gruppe kann nicht gelöscht werden; der letzte aktive Global-Admin kann weder gesperrt noch herabgestuft werden.

#### Schlagwörter

Der Schlagwortbaum kann gefiltert, geöffnet und hierarchisch bearbeitet werden. Verschieben, Zusammenführen oder Löschen kann ganze Teilbäume und Materialzuordnungen betreffen.

<!-- README-SCREENSHOT
id: keyword-management
route: /vue/keyword
state: gefilterter Schlagwortbaum
role: Administrator
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#keyword-tree-loads-filters-and-force-refreshes-through-pinia
refresh: KeywordList, Baumdarstellung, Filter oder Adminnavigation geändert
-->
![Administration des hierarchischen Schlagwortbaums mit Suchfeld](docs/readme/screenshots/keyword-management-desktop.png)

*Schlagwortverwaltung: Der Baum strukturiert Themen, Personen, Orte und Sprachen.*

#### Bundles

Die Bundleübersicht zeigt installierte Version, verfügbare Version, Inhalt und Fortschritt. **Installieren**, **Aktualisieren** und **Deinstallieren** erzeugen eine Folge von Jobs. Den Browser während eines laufenden Vorgangs nicht unnötig schließen und einen Abbruch nur bei geklärter Auswirkung anfordern.

<!-- README-SCREENSHOT
id: bundle-management
route: /vue/bundle
state: installiertes Bundle mit verfügbarem Update
role: Administrator
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#bundle-overview-loads-and-completes-an-update-through-pinia
refresh: BundleList, Bundlekarte, Fortschritt oder Adminnavigation geändert
-->
![Bundleverwaltung mit installierter Version, verfügbarem Update und Verwaltungsaktionen](docs/readme/screenshots/bundle-management-desktop.png)

*Bundleverwaltung: Versionsstand, Umfang und verfügbare Aktionen stehen direkt auf der Bundlekarte.*

#### Weitere Werkzeuge

- **Verwaiste Resources** zeigt Resources ohne Materialzuordnung; vor dem Löschen prüfen, ob die fehlende Zuordnung beabsichtigt ist.
- **Neueste Resources** hilft bei der Kontrolle kürzlich angelegter Inhalte.
- **Resource ersetzen** überführt Beziehungen von einer Resource auf eine andere und ist eine weitreichende Datenoperation.
- **System herunterfahren** zeigt einen Bestätigungsdialog und darf nur im vorgesehenen lokalen Betriebsmodell verwendet werden. Die Aktion kann den Host betreffen.

### Lade-, Leer- und Fehlerzustände

- Spinner oder Informationsmeldungen zeigen einen laufenden Abruf beziehungsweise Import.
- Leere Listen bedeuten nicht automatisch einen Fehler; Filter, Berechtigung und Pagination prüfen.
- Rote Alerts enthalten Validierungs- oder Serverfehler. Eingaben erhalten und Meldung lesen, bevor die Seite neu geladen wird.
- Eine fehlende Aktion kann an Rolle, Resource-Typ, Materialstatus oder laufendem Hintergrundprozess liegen.
- Bei einem 401/403 erneut anmelden beziehungsweise Berechtigung klären; UI-Ausblendung nicht umgehen.

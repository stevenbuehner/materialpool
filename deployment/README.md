# Materialpool auf Proxmox VE

Lokale Entwicklung verwendet Laravel Sail. GitHub Actions prüft PHP und JavaScript, baut Vite mit Node 24 und veröffentlicht bei einem Tag im Format `MAJOR.MINOR.PATCH([a-z]?)` ein geprüftes GitHub Release. Ein Debian-13-LXC betreibt Nginx, PHP-FPM 8.4, MariaDB, Composer, Laravel, einen systemd-Queue-Worker und einen systemd-Scheduler-Timer. Qdrant läuft in einem eigenen nativen LXC. Im Laravel-LXC werden weder Docker noch Node noch Git für Deployments benötigt.

Die [Neuinstallation](docs/installation.md) startet über einen einzigen Skript-Link in der Proxmox-VE-Shell. Ein Repository-Klon ist weder auf dem Host noch im LXC nötig. Anwendung und Updater werden aus einem versionierten, prüfsummengeschützten Release-Archiv installiert; `update` lädt spätere Release-Archive nach Veröffentlichung eines neuen Tags.

| Bereich | Wert |
| --- | --- |
| Web | TCP 80, nur vom TLS-Reverse-Proxy erreichbar |
| MariaDB | lokal, TCP 3306/Socket; nicht von außen |
| Qdrant | separater LXC, TCP 6333 nur vom Laravel-Netz |
| Anwendung | `/srv/materialpool/current` → `/srv/materialpool/releases/X.Y.Z` |
| Dauerhafte Daten | `/srv/materialpool/shared/.env`, `storage`, `public-uploads` |
| Erster Admin | interaktiv nach den Fresh-Migrationen über `users:manage create --first-admin --short-password` |
| Status | `runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status` |
| Deployment-Backups | `/srv/materialpool/shared/backups` |
| SMTP einrichten | im LXC als root `php /srv/materialpool/current/artisan mail:configure` |
| Backup einrichten | im LXC als root `php /srv/materialpool/current/artisan backup:configure` |
| Qdrant einrichten | im LXC als root `php /srv/materialpool/current/artisan context-search:qdrant:configure` |
| Kontextsuche einrichten | im LXC als root `php /srv/materialpool/current/artisan context-search:configure` |
| Update | im LXC `update` |
| Queue | `materialpool-queue.service` für `default`; `materialpool-background.service` für aktive Bundle-Queues, freigegebene Kontextsuche und `resource-previews-low` |
| Scheduler | `materialpool-schedule.timer`, jede Minute |
| Logs | `journalctl -u materialpool-queue -u materialpool-background`, `/var/log/nginx`, `storage/logs`, MariaDB-Journal |

Der Hintergrunddienst wählt vor jedem Job die erste **bereite** Stufe: `default` blockiert niedrigere Starts, danach folgen aktive `bundle_<id>_queue`, die separat freigegebenen Kontextsuche-Queues und `resource-previews-low`. Laufende Jobs werden nicht unterbrochen. Kontextsuche-Indexjobs bleiben bis zur gesonderten Produktionsabnahme gesperrt. Der Scheduler startet keinen Queue-Worker.

Der Installer setzt `PRIORITIZED_BACKGROUND_QUEUE=true` in der geschützten Shared-`.env`; der Updater ergänzt den Wert bei älteren Proxmox-Installationen. Auf dem bisherigen Ubuntu-Betrieb bleibt er aus und die Bundle-API verarbeitet die Bundle-Queue wie zuvor. Ein gezielt angehaltener oder verzögerter höher priorisierter Job blockiert niedrigere bereite Queues nicht.

## Pfade und Zuständigkeiten im Laravel-LXC

Die Pfade beziehen sich auf den **Laravel-LXC**, nicht auf den Proxmox-Host. „Bearbeitbar“ bedeutet: gezielt durch einen Administrator mit passenden Rechten und anschließendem Funktionstest. Vor manuellen Änderungen an Konfigurationen und Schlüsseln ein gesichertes Backup anfertigen. Der reguläre Befehl `update` führt den Fresh-Installer nicht erneut aus.

| Pfad | Inhalt und Sicherung | Admin-Bearbeitung | Verhalten bei `update` |
| --- | --- | --- | --- |
| `/srv/materialpool/current` | Symlink auf das aktive Release | Nicht direkt bearbeiten; nur über den dokumentierten Release-/Rollback-Ablauf wechseln | Wird atomar auf das neue Release gesetzt |
| `/srv/materialpool/releases/X.Y.Z/` | Anwendungscode, `config/`, `routes/`, `public/build/`, `release.json`, `vendor/` nach Composer-Installation; `storage`, `.env` und `public/uploads` sind Links auf Shared-Pfade | **Nicht bearbeiten**; Änderungen gehören in ein neues Release | Neues Verzeichnis wird angelegt; die vier jüngsten und das aktive Release bleiben erhalten |
| `/srv/materialpool/shared/storage/app/material-downloads/` | Kurzlebige Material-ZIPs und interne Statusdateien | Nur über Anwendung und Löschjobs bearbeiten; `ready/` wird von Nginx direkt ausgeliefert | Bleibt bei Releases erhalten; abgelaufene Dateien werden gelöscht, reguläre Dateibackups schließen sie aus |
| `/srv/materialpool/shared/.env` | Produktive Werte einschließlich `APP_KEY`, DB-, Proxy-, Mail-, Backup- und Suchkonfiguration; separat geschützt sichern | Ja, bevorzugt über `mail:configure`, `backup:configure` und die Kontextsuche-Commands; manuelle Änderungen nur mit anschließendem `config:clear` und Prüfung | Bleibt erhalten; jedes Release verlinkt dieselbe Datei |
| `/srv/materialpool/shared/storage/app/` | Originale und Fachdaten auf lokalen Disks, insbesondere `resources/`, `archived/` und `bundles/` | Nicht als Konfigurationsdateien bearbeiten oder bereinigen; über die Anwendung verwalten | Bleibt erhalten; reguläres Laravel-Backup enthält `storage/app`, außer temporären und ausdrücklich ausgeschlossenen Zwischenständen |
| `/srv/materialpool/shared/storage/oauth-private.key`, `oauth-public.key` | Passport-Schlüsselpaar; separat geschützt sichern | Nur bei geplantem Schlüsselwechsel nach eigenem Migrationsablauf | Bleibt erhalten; ein unvollständiges Paar stoppt die Erstinstallation |
| `/srv/materialpool/shared/storage/framework/`, `logs/` | Sessions, Cache, Views, Backup-Zwischendaten und Laravel-Logs | Nur gezielte Betriebsmaßnahmen; keine Originaldateien hier vermuten | Shared-Verzeichnis bleibt erhalten; einzelne Cache-Inhalte können neu erzeugt werden |
| `/srv/materialpool/shared/public-uploads/` | Öffentliche Uploads, im Release unter `public/uploads` verlinkt | Über die Anwendung verwalten; Originale sichern | Bleibt erhalten; reguläres Laravel-Backup enthält `public/uploads` |
| `/srv/materialpool/shared/backups/` | `pre-X.Y.Z-<UTC>.sql.gz` vor Migrationen **nur für MariaDB**; bei aktiviertem Backup zusätzlich lokale Spatie-Archive | Backups prüfen und isoliert wiederherstellen; nicht als Arbeitsdateien bearbeiten | Die letzten zehn `pre-…`-Dumps bleiben; Spatie-Archive folgen ihrer eigenen Aufbewahrung |
| `/etc/materialpool/release.conf` | Root-only Repository-Angabe und optionaler Pfad zu `github.token` | Bei bewusstem Wechsel der Release-Quelle durch root; Rechte `0600` erhalten | Bleibt erhalten |
| `/etc/materialpool/github.token` | Optionales Token für private Release-Quellen; beim öffentlichen Standardrepository nicht angelegt | Nur root, etwa zur Tokenrotation; Rechte `0600` | Bleibt erhalten |
| `/etc/mysql/mariadb.conf.d/90-materialpool.cnf` | Lokale MariaDB-Bind-Adresse; Datenbank selbst wird von MariaDB verwaltet | Nur bei bewusst geänderter Netz-/DB-Architektur | Bleibt erhalten |
| `/etc/nginx/sites-available/materialpool`, `/etc/systemd/system/materialpool-queue.service`, `materialpool-background.service`, `materialpool-schedule.service`, `materialpool-schedule.timer` | Webkonfiguration und versionierte systemd-Dienste | Dienstanpassungen als systemd-Drop-ins vornehmen und prüfen; die Haupt-Units werden vom Release verwaltet | Installation und jedes Update gleichen die Haupt-Units mit dem aktiven Release ab, laden systemd neu und starten beide Queue-Dienste; Rollback übernimmt die Units des Zielrelease |
| `/usr/local/sbin/materialpool-update`, `/usr/bin/update` | Updater bzw. Kurzaufruf | **Nicht bearbeiten**; Änderungen am Updater gehören ins Release | Updater wird nach erfolgreichem Healthcheck aus dem neuen Release ersetzt; Kurzaufruf bleibt bestehen |

Das Verzeichnis `/var/lib/materialpool` ist das Home des Deploy-Benutzers. Die MariaDB-Datendateien liegen in der vom installierten MariaDB-Paket konfigurierten Datenablage; deren effektiven Wert im LXC prüfen, nicht aus einem Beispielpfad ableiten. Ein Spatie-Archiv enthält Datenbank, `storage/app` und `public/uploads`, aber **nicht** die Shared-`.env` oder das Passport-Schlüsselpaar. Der reine `pre-…`-Dump enthält auch keine Dateien. Für einen vollständigen Wiederanlauf deshalb Datenbank, Originaldateien, `.env`, Passport-Schlüssel sowie einen getesteten LXC-/Proxmox-Backup- und Restore-Ablauf gemeinsam berücksichtigen. Das externe S3-Ziel wird über `BACKUP_S3_BUCKET` und `BACKUP_S3_PREFIX` in der Shared-`.env` bestimmt.

Im **separaten Qdrant-LXC** liegen Dienstkonfiguration und Daten laut der [Qdrant-Anleitung](docs/qdrant.md) unter `/etc/qdrant/config.yaml`, `/var/lib/qdrant/storage` und `/var/lib/qdrant/snapshots`. Diese Pfade nach der tatsächlichen Community-Script-Installation prüfen. Qdrant ist ein wiederaufbaubarer Index; seine LXC-Sicherung ersetzt die Sicherung der Materialpool-Originaldaten nicht.

Der alte Ubuntu-/MySQL-Pfad unter `ops/production/` bleibt ausschließlich für bestehende Installationen erhalten. Es gibt keine automatische Datenübernahme in den neuen LXC. Für Bestandsdaten sind ein geprüfter Restore, bestehender `APP_KEY`, Passport-Schlüssel und Storage-Dateien erforderlich.

- [Installation](docs/installation.md)
- [Update und Rollback](docs/update.md)
- [Release](docs/release.md)
- [Qdrant](docs/qdrant.md)
- [Fehlersuche](docs/troubleshooting.md)
- [Vollständige Testanleitung](docs/testing.md)
- [Geführte Abnahme mit Ergebnissen](docs/abnahme.md)

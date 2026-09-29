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
| Erster Admin | interaktiv nach den Fresh-Migrationen über `users:manage create --first-admin` |
| Deployment-Backups | `/srv/materialpool/shared/backups` |
| SMTP einrichten | im LXC als root `php /srv/materialpool/current/artisan mail:configure` |
| Backup einrichten | im LXC als root `php /srv/materialpool/current/artisan backup:configure` |
| Qdrant einrichten | im LXC als root `php /srv/materialpool/current/artisan context-search:qdrant:configure` |
| Kontextsuche einrichten | im LXC als root `php /srv/materialpool/current/artisan context-search:configure` |
| Update | im LXC `update` |
| Queue | `materialpool-queue.service`, `default,resource-previews-low` |
| Scheduler | `materialpool-schedule.timer`, jede Minute |
| Logs | `journalctl -u materialpool-queue`, `/var/log/nginx`, `storage/logs`, MariaDB-Journal |

Der alte Ubuntu-/MySQL-Pfad unter `ops/production/` bleibt ausschließlich für bestehende Installationen erhalten. Es gibt keine automatische Datenübernahme in den neuen LXC. Für Bestandsdaten sind ein geprüfter Restore, bestehender `APP_KEY`, Passport-Schlüssel und Storage-Dateien erforderlich.

- [Installation](docs/installation.md)
- [Update und Rollback](docs/update.md)
- [Release](docs/release.md)
- [Qdrant](docs/qdrant.md)
- [Fehlersuche](docs/troubleshooting.md)
- [Vollständige Testanleitung](docs/testing.md)

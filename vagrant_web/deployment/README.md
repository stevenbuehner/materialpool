# Materialpool auf Proxmox VE

Lokale Entwicklung verwendet Laravel Sail. GitHub Actions prüft PHP und JavaScript, baut Vite mit Node 24 und veröffentlicht bei einem SemVer-Tag ein geprüftes GitHub Release. Ein Debian-13-LXC betreibt Nginx, PHP-FPM 8.4, MariaDB, Composer, Laravel, einen systemd-Queue-Worker und einen systemd-Scheduler-Timer. Qdrant läuft in einem eigenen nativen LXC. Im Laravel-LXC werden weder Docker noch Node noch Git für Deployments benötigt.

| Bereich | Wert |
| --- | --- |
| Web | TCP 80, nur vom TLS-Reverse-Proxy erreichbar |
| MariaDB | lokal, TCP 3306/Socket; nicht von außen |
| Qdrant | separater LXC, TCP 6333 nur vom Laravel-Netz |
| Anwendung | `/srv/materialpool/current` → `/srv/materialpool/releases/vX.Y.Z` |
| Dauerhafte Daten | `/srv/materialpool/shared/.env`, `storage`, `public-uploads` |
| Deployment-Backups | `/srv/materialpool/shared/backups` |
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

# Fehlersuche

## Installation bleibt nach „Updated Container OS“ stehen

Der Community-Helper lädt nach dieser Erfolgsmeldung noch `lib/tools.func`. Erst danach meldet Materialpool für jedes direkt installierte Laufzeitpaket den Beginn und die installierte Version; anschließend folgen PHP, Composer und MariaDB. Bleibt die Ausgabe davor oder während eines Schritts stehen, in einer zweiten Proxmox-Shell die Container-ID der laufenden Installation einsetzen und den Zustand nur lesend prüfen:

```bash
pct exec <CTID> -- ps -eo pid,etime,stat,args
pct exec <CTID> -- tail -n 50 /var/log/apt/term.log
pct exec <CTID> -- tail -n 50 /var/log/dpkg.log
```

Die APT-Protokolle erscheinen erst, wenn die Paketinstallation begonnen hat. Fehlen sie noch, Netzwerk und Download des Community-Helpers prüfen. Die Prozessliste zeigt, ob `curl`, `apt`, `dpkg`, PHP-, Composer- oder MariaDB-Einrichtung läuft. Protokolle können vertrauliche Angaben enthalten und dürfen vor einer Weitergabe nur bereinigt werden. Einen laufenden Paketmanager nicht parallel starten.

## GitHub-Release wird nicht gefunden

Der Installer fragt `https://api.github.com/repos/stevenbuehner/materialpool/releases/latest` ab. HTTP 404 bedeutet bei einem öffentlichen Repository ohne veröffentlichte stabile Releases, dass noch kein installierbares Archiv vorliegt. Ein Git-Tag allein ist kein GitHub-Release. Der Release-Workflow läuft nur bei einem neu gepushten Tag `vMAJOR.MINOR.PATCH`; Tags ohne `v` und Beta-Tags starten ihn nicht. Unter [GitHub Actions → Release](https://github.com/stevenbuehner/materialpool/actions/workflows/release.yml) den Lauf und unter [GitHub Releases](https://github.com/stevenbuehner/materialpool/releases) die beiden Assets prüfen. Erst nach erfolgreichem `verify`- und `publish`-Job mit Archiv und `.sha256` die Neuinstallation starten. Die [Release-Anleitung](release.md) beschreibt den Tag-Schritt.

Im Laravel-LXC:

```bash
cat /srv/materialpool/current/release.json
readlink -f /srv/materialpool/current
curl -i http://127.0.0.1/up
systemctl status nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer
journalctl -u materialpool-queue.service -n 100 --no-pager
journalctl -u materialpool-schedule.service -n 100 --no-pager
journalctl -u php8.4-fpm -u mariadb -n 100 --no-pager
tail -n 100 /var/log/nginx/error.log
tail -n 100 /srv/materialpool/shared/storage/logs/laravel.log
```

Die Logs können personenbezogene Daten enthalten und bleiben auf dem Server. Bei fehlgeschlagenem Update zuerst Fehlermeldung, `current`, Backup und Migrationstatus prüfen; keine blinde Wiederholung und kein `migrate:fresh`. Ein nicht erreichbarer Qdrant-LXC betrifft die optionale Kontextsuche; fachliche Daten liegen weiter in MariaDB. Prüfe Qdrant-Dienst und Firewall im Qdrant-LXC sowie die Materialpool-Logs. Für die Queue sind `systemctl status materialpool-queue.service` und dessen Journal maßgeblich.

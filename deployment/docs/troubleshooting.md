# Fehlersuche

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

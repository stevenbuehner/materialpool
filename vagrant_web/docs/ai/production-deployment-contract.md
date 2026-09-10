# Verbindlicher Produktions- und Deploymentvertrag

## Status, Ziel und Grenzen

Dieser Vertrag ist vor jeder Änderung an Produktion, Deployment, Queue, Scheduler, Proxy, Backups oder produktiver Konfiguration vollständig zu lesen. Er gilt für Materialpool auf Laravel 13 und PHP 8.4. Grundlage sind die am 9. September 2026 erneut geprüften offiziellen Laravel-13-Dokumentationen zu [Deployment](https://laravel.com/framework/docs/13.x/deployment), [Queues](https://laravel.com/framework/docs/13.x/queues) und [Task Scheduling](https://laravel.com/framework/docs/13.x/scheduling).

Produktion ist ein einzelner Ubuntu-24.04-LTS-Server mit Nginx, PHP-FPM 8.4 und MySQL 8. Ein externer Reverse Proxy terminiert TLS. Sail ist ausschließlich Entwicklungs- und Testwerkzeug. Das Vue-2-Frontend wird in einer kontrollierten Node-16.20.2-Umgebung gebaut; auf dem Produktionsserver wird kein Node installiert.

Dieser Vertrag autorisiert die versionierten Betriebsartefakte, aber weder einen Zugriff auf einen realen Server noch das Erfinden fehlender Betriebswerte. Domain, Proxy-IP/CIDR, SSH-Ziel, SMTP-Zugang und S3-Endpunkt/Region/Bucket/Zugang müssen vor dem Rollout extern bereitgestellt werden. Produktive Secrets, Schlüssel, Daten und Dateien bleiben außerhalb von Git.

## Unveränderliche Betriebsverträge

- Nginx darf nur `/srv/materialpool/current/public` ausliefern und ist von außen ausschließlich für den festgelegten Reverse Proxy erreichbar.
- Der Proxy setzt `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Port` und `X-Forwarded-Proto=https`. `TRUSTED_PROXIES` enthält ausschließlich konkrete IP-Adressen oder CIDR-Netze; `*`, `**` und `REMOTE_ADDR` sind verboten.
- `GET /up` ist ohne Anmeldung erreichbar, antwortet nach erfolgreichem Boot ausschließlich mit `200`, `text/plain` und `OK`. Bootfehler ergeben `500`; Details bleiben wegen `APP_DEBUG=false` verborgen.
- Der `default`-Queue-Worker läuft permanent unter Supervisor. `--timeout=120`, `--tries=50`, `--max-time=3600` und `QUEUE_RETRY_AFTER=150` sind aufeinander abgestimmt. Dynamische `bundle_{id}_queue`-Queues werden nicht vom Default-Worker konsumiert und bleiben API-gesteuert.
- Cron führt jede Minute `schedule:run` aus. Der Scheduler enthält tägliches Backup und Cleanup, aber keinen `queue:work`-Aufruf.
- Ressourcen, Archive und Bundles in `storage/app` sowie `public/uploads` sind persistente Verträge. Beide Bereiche werden gesichert und bei Releasewechseln nicht kopiert, gelöscht oder neu erzeugt.
- Der bestehende `APP_KEY`, der private Passport-Schlüssel und der öffentliche Passport-Schlüssel werden übernommen. `key:generate` und `passport:install` sind in Produktion verboten. Der private Schlüssel hat Modus `0600`.
- `php-http/discovery` bleibt als einziges für Passport benötigtes Composer-Plugin explizit freigegeben. Andere Plugins werden nicht pauschal erlaubt.

## Verbindliche Pfade und Rechte

```text
/srv/materialpool/releases/<UTC-Zeitstempel>-<Commit>/
/srv/materialpool/current -> releases/<aktives-release>/
/srv/materialpool/shared/.env
/srv/materialpool/shared/storage/
/srv/materialpool/shared/public-uploads/
/srv/materialpool/shared/backups/
/srv/materialpool/shared/incoming/
```

Deploy-Nutzer ist `materialpool`, Web-/PHP-FPM-Nutzer ist `www-data`. Release-Code ist für `www-data` nur lesbar. Nur `shared/storage`, `shared/public-uploads` und das jeweilige `bootstrap/cache` sind für die Laufzeit kontrolliert beschreibbar. `.env`, `storage` und `public/uploads` werden verlinkt. Mindestens das unmittelbar vorherige Release bleibt erhalten.

MySQL lauscht nur lokal. Materialpool erhält eine eigene Datenbank und einen dedizierten Benutzer mit Rechten ausschließlich auf dieser Datenbank. Nginx, PHP-FPM, MySQL, Supervisor und Cron werden durch systemd gestartet und überwacht. Firewall oder vorgelagerte Netzwerkregeln erlauben Nginx nur vom festgelegten Proxy-Netz; SSH-Regeln werden vor Aktivierung der Firewall separat verifiziert.

## Zwingende Produktionskonfiguration

Mindestens folgende Werte liegen ausschließlich in `/srv/materialpool/shared/.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://materialpool.example.org
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=150
TRUSTED_PROXIES=192.0.2.10/32

BACKUP_PATH=/srv/materialpool/shared/backups
BACKUP_TEMPORARY_DIRECTORY=/srv/materialpool/shared/storage/framework/backup-temp
BACKUP_ARCHIVE_PASSWORD=<externes-starkes-secret>
BACKUP_NOTIFICATION_EMAIL=<reale-empfaengeradresse>
BACKUP_S3_KEY=<extern>
BACKUP_S3_SECRET=<extern>
BACKUP_S3_REGION=<region>
BACKUP_S3_BUCKET=<bucket>
BACKUP_S3_ENDPOINT=<https-endpoint>
BACKUP_S3_PREFIX=materialpool
BACKUP_S3_USE_PATH_STYLE_ENDPOINT=false
```

SMTP-Werte (`MAIL_MAILER=smtp`, Host, Port, Benutzer, Passwort, Verschlüsselung, From-Adresse) sind ebenfalls verpflichtend. Platzhalter sind vor Produktion zu ersetzen. `php artisan production:preflight` prüft die Konfiguration, Shared-Pfade, Passport-Schlüssel, MySQL-Verbindung und ausführbare Abhängigkeiten, gibt aber keine Secretwerte aus.

## Servervorbereitung

`ops/production/provision-ubuntu.sh` installiert die Laufzeitpakete und legt Shared-Verzeichnisse an. Das Skript ersetzt keine Prüfung der Paketquelle, Firewall, MySQL-Härtung oder realen Konfiguration. Zusätzlich sind verbindlich:

1. `ops/production/nginx.conf` mit realem `server_name` nach `/etc/nginx/sites-available/materialpool` übernehmen und aktivieren.
2. `ops/production/materialpool-worker.conf` nach `/etc/supervisor/conf.d/` übernehmen.
3. `ops/production/materialpool.cron` nach `/etc/cron.d/materialpool` übernehmen.
4. `ops/production/activate-release.sh` root-owned als `/usr/local/sbin/materialpool-activate-release` installieren.
5. `ops/production/materialpool.sudoers` mit `visudo -cf` prüfen und anschließend root-owned mit Modus `0440` installieren.
6. Nginx mit `nginx -t`, PHP-FPM mit `php-fpm8.4 -t`, Supervisor mit `supervisord -t` und Cron-/systemd-Status prüfen.
7. MySQL-Bind-Adresse, Datenbankbenutzer und Firewall anhand der realen Werte prüfen. Keine Beispieladresse darf aktiv bleiben.

Laufzeitabhängigkeiten sind PHP 8.4 mit den in `composer.json` verlangten Erweiterungen, Imagick mit Ghostscript/PDF-Unterstützung, MySQL-Client inklusive `mysqldump`, Poppler (`pdfinfo`, `pdftotext`), LibreOffice sowie die versionierten Linux-x86_64-Binaries `resources/bin/ffmpeg` und `resources/bin/ffprobe`.

## Reproduzierbarer Build und Deployment

Der Build akzeptiert einen exakten Commit und bricht bei Änderungen im Materialpool-Arbeitsbaum ab:

```sh
ops/production/build-release.sh <40-stellige-commit-id> /absoluter/ausgabeordner
```

Das Skript exportiert ausschließlich diesen Commit, prüft PHP 8.4 und Node 16.20.2 im Image `sail-8.4/app`, führt `composer install`, `npm ci --ignore-scripts` und `npm run build` aus, entfernt `.env`, `vendor` und `node_modules` und erzeugt Release-Manifest, Archiv und SHA-256-Datei. Installationsskripte sind vorübergehend deaktiviert, weil das nur indirekt über `svg-icon` eingebrachte, im Produktionsbuild nicht verwendete PhantomJS-Paket kein Linux-arm64-Binary besitzt. Der danach zwingend erfolgreiche Webpack-Build ist das Verhaltensgate. Dependencies, Lockfile und Frontendquellen bleiben unverändert; die Beseitigung dieser Ausnahme gehört zum separaten Frontend-Upgrade.

Der unveränderte Lockstand meldet beim Build 129 npm-Audit-Funde insgesamt. Ein gesondertes `npm audit --omit=dev` weist davon 28 dem Production-Abhängigkeitsgraphen zu: 10 low, 6 moderate, 10 high und 2 critical. Betroffen sind unter anderem die direkten Abhängigkeiten `axios` und `dompurify`; die Audit-Fixvorschläge enthalten teilweise Major-Upgrades. Der Produktionsvertrag autorisiert deshalb keine automatische Paketänderung. Vor dem ersten Go-live muss der Befund mit Datum und Lockstand ausdrücklich bewertet und entweder als zeitlich begrenztes Risiko freigegeben oder in einem gesondert freigegebenen Frontend-Security-Schritt behoben werden.

Der Upload aktiviert nichts:

```sh
ops/production/deploy-release.sh /pfad/materialpool-<commit>.tar.gz materialpool@server
```

Danach wird auf dem Server bewusst aktiviert:

```sh
sudo /usr/local/sbin/materialpool-activate-release /srv/materialpool/shared/incoming/materialpool-<commit>.tar.gz
```

Die Aktivierung prüft Archiv und Manifest, verbindet Shared-Pfade, setzt Rechte, installiert ausschließlich aus `composer.lock`, führt den Preflight und `php artisan optimize` aus und schaltet `current` atomar um. Danach werden Laravels `php artisan reload`, PHP-FPM-Reload und Supervisor-Neustart ausgeführt.

Verboten sind im Produktions-Deploy `composer update`, `npm install`, `npm update`, `migrate:fresh`, `db:seed`, `key:generate` und `passport:install`. Der bestehende `DatabaseSeeder` enthält löschende und synthetische Testdaten erzeugende Schritte und ist ausschließlich ein Gate für isolierte Testdatenbanken. Das Aktivierungsskript führt Migrationen nur mit `--migrate` und zusätzlich gesetztem `MATERIALPOOL_RESTORE_PROOF_CONFIRMED=yes` aus. Diese Variable bestätigt ausschließlich den zuvor protokollierten Restore-Test; sie erzeugt keinen Nachweis.

## Backup- und Restore-Gate

Jedes Backup ist AES-256-verschlüsselt und wird auf `backup` sowie `backup_s3` geschrieben. `backup_s3` ist ein separater generischer S3-Disk mit konfigurierbarem Endpoint, Region, Bucket, Prefix und Path-Style; Ressourcen-Storage bleibt lokal. Erfolg und Fehler werden per SMTP an den konfigurierten Empfänger gemeldet.

Produktion ist gesperrt, bis ein verschlüsseltes S3-Archiv erzeugt, heruntergeladen, mit dem externen Passwort entschlüsselt und auf einer isolierten MySQL-Instanz wiederhergestellt wurde. Der Nachweis umfasst Datenbank, repräsentative Resource-/Archiv-/Bundle-Dateien und `public/uploads`, Zeitstempel, Release-Commit und Prüfsummen, aber keine Secrets oder Nutzdaten. Ein bloßer erfolgreicher Upload ist kein Restore-Nachweis.

## Erster Passport-13-Cutover

Zusätzlich gilt vollständig `docs/ai/passport-13-client-migration.md`. Reihenfolge:

1. Clientinventar, numerische IDs, Grants und entfernte JSON-Endpunkte prüfen.
2. Verschlüsseltes Backup lokal und auf S3 erzeugen und den isolierten Restore nachweisen.
3. Anwendung mit dem aktuell aktiven Release in Wartungsmodus setzen und Worker stoppen.
4. Neues Release hochladen und vollständig vorbereiten, ohne `current` umzuschalten.
5. Mit nachgewiesenem Restore-Gate `MATERIALPOOL_RESTORE_PROOF_CONFIRMED=yes .../materialpool-activate-release <archiv> --migrate` ausführen. Die Migration läuft aus dem neuen, noch nicht aktiven Release.
6. Nach atomarer Aktivierung bestehenden Password-Grant-Client mit unverändertem Secret, neuen UUID-Client, Login, API, Storage, Bundle-Verarbeitung und Queue prüfen.
7. Erst danach `php artisan up` ausführen.

Rollback ist kein `migrate:rollback`. Anwendung sperren, Worker stoppen, MySQL und persistente Dateien aus dem unmittelbar vorherigen Backup wiederherstellen, `current` atomar auf das vorherige Release setzen, `php artisan optimize`, `php artisan reload`, PHP-FPM und Supervisor neu starten und Smoke-Tests ausführen.

## Abnahme und noch offene externe Nachweise

Vor Freigabe sind auszuführen und zu protokollieren:

- vollständige PHPUnit-Suite sowie gezielt Passport-Migration/Tokenaustausch, Nested Sets, Bundle-Queues, Backup/Restore, Storage und Proxy-Spoofing;
- isolierter, verschlüsselter lokaler Backup-Restore und echter S3-Download-/Restore-Test;
- `composer validate --strict`, `composer audit --locked`, PHP-Syntax und Shell-Syntax;
- `nginx -t`, PHP-FPM-, Supervisor-, Cron-, systemd-, Rechte-, MySQL-Bind- und Firewall-Prüfung auf der produktionsnahen Zielplattform;
- Release-Installation ohne Dev-Abhängigkeiten, atomarer Wechsel, `/up`, Login/API/Storage/Queue-Smoke-Tests und vollständiger Restore-Rollback.

Ohne reale Domain, Proxy-CIDR, SSH-Ziel, SMTP- und S3-Zugang können nur Repository- und lokale Integrationsgates abgeschlossen werden. Ein produktives Deployment bleibt bis zu den externen Nachweisen ausdrücklich blockiert.

## Nachgelagerte P1-Arbeiten

Erst nach stabilem Produktionsbetrieb: FPDI-/FPDF-Metapaket verhaltensneutral durch direkte Abhängigkeiten ersetzen, Session kontrolliert auf JSON umstellen, GET-Logout beim Frontend-Upgrade durch POST ersetzen, historische Passport-Hilfstabelle nach Beobachtungszeit entfernen und OAuth-Consent-/Device-UI nur bei echtem Bedarf entscheiden. Vue-2-/Webpack-4-/Node-16-Modernisierung ist ein eigenes Projekt.

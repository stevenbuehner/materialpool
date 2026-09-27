# Manuelle Abnahme: Proxmox-Releaseweg

Diese Schritte gelten für einen isolierten Test-LXC und ein Test-Repository beziehungsweise Test-Releases. Die vorhandene Produktionsdatenbank und ihre Dateien dürfen durch keine Fehlersimulation verändert werden. `v0.0.1` und `v0.0.2` sind nur zu verwenden, solange diese Tags im Zielrepository noch nicht existieren. Das konfigurierte Git-Remote heißt in diesem Checkout `github`; der Default-Branch ist lokal nicht nachweisbar, weshalb CI ohne Branchfilter läuft.

## A. Lokale Vorprüfung

```bash
git branch --show-current
git status --short
./vendor/bin/sail up -d
./vendor/bin/sail test
npm run test:ci
npm run build
bash -n ct/materialpool.sh install/materialpool-install.sh deployment/release/*.sh
shellcheck ct/materialpool.sh install/materialpool-install.sh deployment/release/*.sh
npm run docs:check
bash deployment/release/package.sh v0.0.1 /tmp/materialpool-test-release
bash deployment/release/check-package.sh /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz
tar -tzf /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz | less
tar -tzf /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz | grep -E '(node_modules|^\./\.env|public/uploads)' && exit 1 || true
tar -tzf /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz | grep -F './public/build/manifest.json'
cd /tmp/materialpool-test-release && sha256sum -c materialpool-v0.0.1.tar.gz.sha256
```

Workflow-YAML mit einem lokal verfügbaren YAML-Parser prüfen (zum Beispiel `ruby -e 'require "yaml"; ARGV.each { |p| YAML.load_file(p) }' .github/workflows/{ci,release}.yml`) und GitHub Actions als maßgeblichen Validator beobachten. Das lokal erzeugte Paket ist nur eine Inhaltsprobe; für ein Release zählt ausschließlich der saubere, getaggte Actions-Build.

## B. GitHub CI testen

Nach Sicherung fremder Änderungen einen eigenen Testbranch vom vorgesehenen Integrationsstand erstellen: `git switch -c codex/proxmox-ci-test`. Nur die geprüften Deployment-Dateien committen, `git push github codex/proxmox-ci-test` ausführen und auf GitHub **Actions → CI** die Jobs `php` und `frontend` prüfen. Erwartet werden Composer-Validierung, MySQL-Testschema, Laravel-Tests, `npm ci`, Lint/Unit-Tests, Vite-Build und Manifest-Prüfung. Für einen negativen Test **nur in diesem Branch** `printf "import {test, expect} from 'vitest'; test('CI stoppt bei Fehlern', () => expect(1).toBe(2));\n" > tests/js/ci-intentional-failure.spec.js`, `git add tests/js/ci-intentional-failure.spec.js`, `git commit -m 'Test CI failure gate'` und `git push github codex/proxmox-ci-test` ausführen: CI muss rot werden. Dann `git rm tests/js/ci-intentional-failure.spec.js`, `git commit -m 'Remove intentional CI failure'` und erneut pushen; beide Jobs müssen grün sein. Kein Release-Tag für den fehlerhaften Stand erstellen.

## C. Release Workflow testen

Nach grünem CI und nur falls noch frei: `git tag v0.0.1 && git push github v0.0.1`. **Actions → Release** muss `verify` vor `publish` erfolgreich ausführen. Im GitHub Release müssen `materialpool-v0.0.1.tar.gz`, `.sha256` und `release.json` liegen. Assets lokal in ein leeres Testverzeichnis laden und `sha256sum -c materialpool-v0.0.1.tar.gz.sha256` sowie `tar -xOf materialpool-v0.0.1.tar.gz ./release.json | jq .` ausführen. `tar -tzf` muss `public/build/manifest.json` zeigen und darf keine `.env`, `node_modules`, `vendor` oder Uploads enthalten. Ein Testrelease nur nach Prüfung löschen: `gh release delete v0.0.1 --repo stevenbuehner/materialpool --yes` und anschließend den Testtag gezielt entfernen; produktive Tags niemals pauschal löschen. Lokales `gh` muss dafür separat bereitgestellt und authentifiziert sein.

## D. Qdrant-LXC installieren

Proxmox-Shell öffnen, **vorher ein isoliertes privates Netz und blockierende Firewall für den neuen LXC bereitstellen** und das [aktuelle Qdrant-Community-Script](https://github.com/community-scripts/ProxmoxVE/blob/main/ct/qdrant.sh) nach Einsicht ausführen: `bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/qdrant.sh)"`. Das aktuelle Script bindet zunächst `0.0.0.0` ohne API-Key; es darf deshalb nie direkt ein öffentliches Netz sehen. Default/Advanced Setup, unprivilegierten LXC, CPU/RAM/Disk und private statische Adresse festlegen. Im Qdrant-LXC `systemctl status qdrant` und `/etc/qdrant/config.yaml` prüfen, API-Key gemäß aktueller Qdrant-Anleitung setzen, Firewall für 6333 nur vom Laravel-LXC erlauben und andere Netze sperren. `curl -fsS -H "api-key: $QDRANT_API_KEY" http://127.0.0.1:6333/healthz` im Qdrant-LXC und denselben Test vom Laravel-LXC über die private IP ausführen. `/var/lib/qdrant/storage` und `/var/lib/qdrant/snapshots` per Proxmox-Backup samt Restore sichern. Auf untrusted Netzen TLS einschalten.

## E. Laravel-LXC neu installieren

Proxmox-Shell und Materialpool-Checkout öffnen; `COMMUNITY_SCRIPTS_ROOT="$PWD" bash ct/materialpool.sh` ausführen. Default oder Advanced wählen, dann Container-ID, Storage, Netzwerk, 2 CPU/3072 MiB/24 GiB oder gemessene Werte festlegen. Reale HTTPS-, Proxy-, SMTP-, S3- und Qdrant-Werte interaktiv eingeben. Im LXC prüfen:

```bash
cat /srv/materialpool/current/release.json
readlink -f /srv/materialpool/current
ls -la /srv/materialpool/releases
stat -c '%a %U:%G' /srv/materialpool/shared/.env /srv/materialpool/shared/storage/oauth-private.key
grep -E '^(APP_ENV|APP_DEBUG|DB_HOST|QUEUE_CONNECTION|QDRANT_URL)=' /srv/materialpool/shared/.env
mariadb -e 'SHOW DATABASES LIKE "materialpool";'
php /srv/materialpool/current/artisan migrate:status
nginx -t && php-fpm8.4 -t
systemctl status nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer
curl -i http://127.0.0.1/up
```

Danach im Browser über den TLS-Reverse-Proxy Anmeldung, geschützte Seite, Resource-Download, Upload/Vorschau und API testen. Qdrant nur mit eingerichtetem Schlüssel aus dem Laravel-LXC testen. Der Fresh-Installer erzeugt keine fachlichen Admin-Konten oder Passport-Clients; deren Einrichtung muss separat autorisiert und getestet werden.

## F. Kein Node im Produktions-LXC

`command -v node; command -v npm; command -v docker` darf jeweils keinen Pfad liefern. Gleichzeitig müssen `curl -fsS http://127.0.0.1/up` und der Browser-Test erfolgreich sein. `test -s /srv/materialpool/current/public/build/manifest.json` belegt die gebauten Vite-Assets.

## G. Update testen

In einem eigenen Testbranch eine harmlose, sichtbare Textänderung mit bestehendem Übersetzungsmechanismus vornehmen, CI vollständig abwarten, `git tag v0.0.2 && git push github v0.0.2` ausführen und Assets/Checksumme wie in C prüfen. Im **Test-LXC** vor `update` die Werte aus `current/release.json`, `readlink -f current`, `.env`-Prüfsumme und einen Testdatensatz/Testupload notieren. `update` ausführen. Danach neues Release, Backup unter `/srv/materialpool/shared/backups`, Migrationstatus, `systemctl is-active` aller Dienste, `/up`, Browser, Queue und persistente Daten prüfen. Nochmaliges `update` muss „bereits aktuell“ melden.

## H. Persistenztest

Vor G einen isolierten Testdatensatz und einen ungefährlichen Upload in der Testinstanz anlegen; `.env`-Prüfsumme mit `sha256sum /srv/materialpool/shared/.env` notieren. Nach G Datensatz, Datei, `APP_KEY`, DB-Zugang, Qdrant-URL und Prüfsumme vergleichen. Geheimwerte nicht in den Testbericht kopieren. `readlink` der neuen Release-Links zu `shared/storage`, `shared/public-uploads` und `shared/.env` prüfen.

## I. Falsche Checksumme

Lokal ein ausschließlich für Tests erzeugtes Archiv kopieren und ein Byte verändern: `cp /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz /tmp/materialpool-bad.tar.gz; printf x >> /tmp/materialpool-bad.tar.gz; sed 's/materialpool-v0.0.1.tar.gz/materialpool-bad.tar.gz/' /tmp/materialpool-test-release/materialpool-v0.0.1.tar.gz.sha256 > /tmp/materialpool-bad.tar.gz.sha256`. `bash deployment/release/check-package.sh /tmp/materialpool-bad.tar.gz` muss fehlschlagen. Für den echten Updater ein isoliertes Test-Repository mit absichtlich falschem Checksum-Asset verwenden; vor/nach `update` `readlink -f /srv/materialpool/current`, `migrate:status` und `/up` vergleichen. Keine Änderung und kein Maintenance Mode sind erwartet. Nie ein fehlerhaftes Asset in einem produktiven Repository veröffentlichen.

## J. Fehlgeschlagenes Deployment

Im isolierten Test-Repository ein Release-Asset mit gültiger SHA256, aber inkonsistentem `composer.lock` bereitstellen. `update` muss vor dem Wartungsmodus beim `composer install` abbrechen. `readlink -f current`, `/up` und Queue müssen unverändert sein. Der normale Release-Workflow darf ein solches Paket wegen seiner Tests und Composer-Prüfung nicht veröffentlichen.

## K. Code-Rollback

Im Test-LXC aktive und vorherige Version mit `cat current/release.json` und `ls /srv/materialpool/releases` bestimmen. Dann `sudo /usr/local/sbin/materialpool-update rollback v0.0.1`, `readlink -f current`, `systemctl status materialpool-queue.service`, `curl -fsS http://127.0.0.1/up` und Browser prüfen. Dieser Test betrifft **nur Code**. Eine nicht abwärtskompatible Migration verlangt gegebenenfalls den Restore des zugehörigen DB-Backups.

## L. Datenbank-Recovery, nur Testinstanz

Zunächst aktuelle Testdatenbank separat mit `mariadb-dump --single-transaction materialpool | gzip > /root/materialpool-before-recovery.sql.gz` sichern. Gewünschtes `pre-v*.sql.gz` unter `/srv/materialpool/shared/backups` bewusst auswählen. Testanwendung in Maintenance Mode setzen, Queue stoppen und `gzip -dc /pfad/zum/geprüften-backup.sql.gz | mariadb materialpool` ausführen. Danach `php /srv/materialpool/current/artisan migrate:status`, Datenintegrität und Uploads prüfen; Queue und Anwendung kontrolliert starten. **Diesen Restore niemals ungeprüft auf Produktion ausführen.** Proxmox-Snapshot/Storage-Backup bleibt eine zusätzliche Ebene.

## M. Logs und Diagnose

`systemctl status nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer`; `journalctl -u materialpool-queue.service -u materialpool-schedule.service -n 100 --no-pager`; `journalctl -u mariadb -u php8.4-fpm -n 100 --no-pager`; `tail -n 100 /var/log/nginx/error.log`; `tail -n 100 /srv/materialpool/shared/storage/logs/laravel.log`. Im Qdrant-LXC: `systemctl status qdrant` und `journalctl -u qdrant -n 100 --no-pager`. Secrets/Nutzdaten vor Weitergabe aus Logs entfernen.

## N. Backup-Check

`ls -lh /srv/materialpool/shared/backups` zeigt Deployment-DB-Backups. Scheduler und `php artisan backup:monitor` prüfen die regulären lokalen/S3-Backups einschließlich `storage/app` und `public/uploads`; ein S3-Download mit isoliertem Restore ist Pflicht. Auf dem Proxmox-Host `pct config <CTID>` und den eingerichteten `vzdump`-Job prüfen und einen Restore des Laravel-LXC testen. Den getrennten Qdrant-LXC samt seinem Datenpfad ebenfalls sichern und isoliert zurückspielen. Qdrant ist nur ein ableitbarer Index; die MariaDB- und Originaldatei-Backups sind vorrangig.

## O. Produktions-Checkliste

- [ ] CI und Release-Workflow grün; Assets und SHA256 korrekt
- [ ] Laravel- und Qdrant-LXC unprivilegiert; Firewall und Qdrant-API-Key aktiv
- [ ] Im Laravel-LXC kein Docker, Node oder npm
- [ ] Nginx, PHP-FPM, MariaDB, Queue und Scheduler aktiv
- [ ] `/up`, Login, API, Upload und Browser geprüft
- [ ] `APP_DEBUG=false`, `.env` nicht öffentlich, Proxy-CIDR konkret
- [ ] Qdrant aus Laravel erreichbar und nur intern zugänglich
- [ ] Lokaler/S3-Backup-Restore und Proxmox-Backup geprüft
- [ ] Update, Persistenz, Fehlerfall und Code-Rollback auf Testinstanz geprüft

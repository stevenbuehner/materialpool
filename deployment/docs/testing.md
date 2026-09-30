# Manuelle Abnahme: Proxmox-Releaseweg

Diese Schritte gelten für einen isolierten Test-LXC und, bei GitHub-Fehlersimulationen, ein separates Test-Repository. Die vorhandene Produktionsdatenbank und ihre Dateien dürfen durch keine Fehlersimulation verändert werden. `0.0.1` und `0.0.2` sind nur zu verwenden, solange diese Tags im Zielrepository noch nicht existieren. Vor den GitHub-Tests ein separates **öffentliches** Test-Repository mit denselben Actions einrichten und als Git-Remote `test` hinzufügen; dessen Ziel-URL vor jedem Push prüfen. Das produktive Remote `github` darf für absichtlich fehlerhafte Teststände und Test-Tags nicht verwendet werden. Der öffentliche Einstieg lädt das Start- und Installationsskript aus `master`; die CI läuft unabhängig davon ohne Branchfilter. **Wichtig:** Der unveränderte Fresh-Installer bezieht Releases fest aus `stevenbuehner/materialpool`. Für Tests gegen das separate Repository dort in einem eigenen Testbranch `ct/materialpool.sh` mit `COMMUNITY_SCRIPTS_URL` auf dessen öffentlichen Raw-`master`-Pfad und `install/materialpool-install.sh` mit `repository` auf dessen `OWNER/REPO` anpassen, diese Testfassung nach `master` übernehmen und ausschließlich deren Raw-Startskript im entbehrlichen Test-LXC aufrufen. Die produktiven Skripte und den öffentlichen Einzeiler dafür nicht ändern. Ein normales Fresh-Install-Gate mit dem unveränderten Einzeiler verwendet ein tatsächlich veröffentlichtes stabiles Materialpool-Release.

## A. Lokale Vorprüfung

```bash
git branch --show-current
git status --short
./vendor/bin/sail up -d
./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan test tests/Feature/Admin/ManageUsersTest.php
./vendor/bin/sail test
npm run test:ci
npm run build
bash -n ct/materialpool.sh install/materialpool-install.sh deployment/release/*.sh
shellcheck ct/materialpool.sh install/materialpool-install.sh deployment/release/*.sh
npm run docs:check
./vendor/bin/sail artisan bible:prepare:cross-references
bash deployment/release/package.sh 0.0.1 /tmp/materialpool-test-release
bash deployment/release/check-package.sh /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz
tar -tzf /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz | less
if tar -tzf /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz | grep -E '^\./(\.agents|\.ai|\.codex|\.github|\.git|\.idea|\.vscode|docs|ops|tests|node_modules|vendor|\.env($|\.[^/]+)|public/uploads|public/hot)(/|$)'; then
  echo 'Unerlaubter Paketinhalt' >&2
  exit 1
fi
tar -tzf /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz | grep -F './public/build/manifest.json'
cd /tmp/materialpool-test-release && sha256sum -c materialpool-0.0.1.tar.gz.sha256
```

Workflow-YAML mit einem lokal verfügbaren YAML-Parser prüfen (zum Beispiel `ruby -e 'require "yaml"; ARGV.each { |p| YAML.load_file(p) }' .github/workflows/{ci,release}.yml`) und GitHub Actions als maßgeblichen Validator beobachten. Das lokal erzeugte Paket ist nur eine Inhaltsprobe; für ein Release zählt ausschließlich der saubere, getaggte Actions-Build.

## B. GitHub CI testen

Nach Sicherung fremder Änderungen einen eigenen Testbranch vom vorgesehenen Integrationsstand erstellen: `git switch -c codex/proxmox-ci-test`. Nur die geprüften Deployment-Dateien committen, `git push test codex/proxmox-ci-test` ausführen und auf GitHub **Actions → CI** die Jobs `php` und `frontend` prüfen. Erwartet werden Composer-Validierung, MySQL-Testschema, Laravel-Tests, `npm ci`, Lint/Unit-Tests, Dokumentationslinks, Vite-Build und Manifest-Prüfung. Für einen negativen Test **nur in diesem Branch** `printf "import {test, expect} from 'vitest'; test('CI stoppt bei Fehlern', () => expect(1).toBe(2));\n" > tests/js/ci-intentional-failure.spec.js`, `git add tests/js/ci-intentional-failure.spec.js`, `git commit -m 'Test CI failure gate'` und `git push test codex/proxmox-ci-test` ausführen: CI muss rot werden. Dann `git rm tests/js/ci-intentional-failure.spec.js`, `git commit -m 'Remove intentional CI failure'` und erneut pushen; beide Jobs müssen grün sein. Kein Release-Tag für den fehlerhaften Stand erstellen.

## C. Release Workflow testen

Nach dem Publish-Job muss auch „Veröffentlichtes Release und Assets prüfen“ erfolgreich sein. Ein Tag im Format `MAJOR.MINOR.PATCH([a-z]?)` muss den Release-Lauf starten. Einen zusätzlichen freien Tag mit Buchstaben, etwa `0.0.1b`, im isolierten Test-Repository prüfen: Er muss als Prerelease mit beiden Assets erscheinen und darf `/releases/latest` nicht ersetzen.

Nach grünem CI und nur falls noch frei: `git tag 0.0.1 && git push test 0.0.1`. **Actions → Release** muss `verify` vor `publish` erfolgreich ausführen. Im GitHub Release müssen `materialpool-0.0.1.tar.gz` und `.sha256` liegen. Beide Assets lokal in ein leeres Testverzeichnis laden und `sha256sum -c materialpool-0.0.1.tar.gz.sha256` sowie `tar -xOf materialpool-0.0.1.tar.gz ./release.json | jq .` ausführen. Die `release.json` im Archiv enthält Version und Commit. `tar -tzf` muss `public/build/manifest.json` und `database/bible-data/{manifest.json,cross-references.tsv}` zeigen, aber keine Bibelübersetzungsdateien, `.env`, `node_modules`, `vendor` oder Uploads enthalten. Ein Testrelease nur nach Prüfung gezielt im Test-Repository löschen und anschließend den Testtag dort entfernen; produktive Tags niemals pauschal löschen.

## D. Qdrant-LXC installieren

Proxmox-Shell öffnen, **vorher ein isoliertes privates Netz und blockierende Firewall für den neuen LXC bereitstellen** und das [Qdrant-Community-Script](https://github.com/community-scripts/ProxmoxVE/blob/main/ct/qdrant.sh) nach erneuter Prüfung ausführen: `bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/qdrant.sh)"`. Der bisher dokumentierte Stand bindet zunächst `0.0.0.0` ohne API-Key; der LXC darf deshalb nie direkt ein öffentliches Netz sehen. Default/Advanced Setup, unprivilegierten LXC, CPU/RAM/Disk und private statische Adresse festlegen. Im Qdrant-LXC `systemctl status qdrant` und `/etc/qdrant/config.yaml` prüfen, API-Key gemäß aktueller Qdrant-Anleitung setzen, Firewall für 6333 nur vom Laravel-LXC erlauben und andere Netze sperren. `curl -fsS -H "api-key: $QDRANT_API_KEY" http://127.0.0.1:6333/healthz` im Qdrant-LXC und denselben Test vom Laravel-LXC über die private IP ausführen. Die tatsächlichen Datenpfade gegen Dienstkonfiguration und Dateisystem prüfen; `/var/lib/qdrant/storage` und `/var/lib/qdrant/snapshots` sind die bisher dokumentierten Werte. Daten per Proxmox-Backup samt Restore sichern. Auf untrusted Netzen TLS einschalten.

## E. Laravel-LXC neu installieren

Während des isolierten Testlaufs prüfen: Das stabile GitHub-Release wird vor dem OS-Update abgefragt. Nach „Updated Container OS“ erhält jedes direkt installierte Laufzeitpaket eine Beginn- und eine Erfolgsmeldung mit der installierten Debian-Paketversion; PHP, Composer und MariaDB melden Einrichtung und Programmversion. Composer installiert vor dem Wartungsmodus ohne Skriptausführung; anschließend muss `php artisan package:discover` als `www-data` ohne Schreibfehler durchlaufen. Insbesondere `storage/framework/cache` und `storage/framework/cache/previewimages` müssen `www-data:www-data` gehören und beschreibbar sein. Ein HTTP-404-Test darf nur mit einem isolierten Test-Repository oder einer anderweitig verifizierten Testumgebung stattfinden und muss vor dem OS-Update abbrechen.

In der Proxmox-Shell ohne Materialpool-Checkout für den unveränderten öffentlichen Releaseweg `bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"` ausführen. Für den isolierten Test gegebenenfalls `var_ctid`, `var_cpu`, `var_ram` und `var_disk` voranstellen. Default oder Advanced wählen, dann Container-ID, Storage, Netzwerk, 2 CPU/3072 MiB/24 GiB oder gemessene Werte festlegen. In der Installation URL und Proxy leer lassen; es darf keine Repository-, Token- oder direkte Qdrant-Frage vor dem Release erscheinen. Die Fragen zu Kontextsuche, E-Mail und Backup jeweils mit Nein beantworten; der erste Admin muss danach angelegt werden können. In einer separaten Testinstallation `context-search:qdrant:configure` mit richtigem und falschem API-Key prüfen: Nur die erfolgreiche Prüfung darf die Qdrant-Werte speichern. `context-search:configure` mit null, einem und zwei Ollama-Servern sowie unerreichbarem Server prüfen; vor der Aktivierung müssen Modell-Digest und Probevektor auf allen Servern passen. Abbruch muss den Funktionsschalter deaktivieren. Beim folgenden `update` darf kein Konfigurationsdialog starten und die Shared-`.env` muss erhalten bleiben. SMTP mit `mail:configure` testen; fehlerhafte Verbindungsdaten dürfen `.env` nicht ändern. `backup:configure` muss ein Testarchiv lokal und im isolierten Test-S3-Bucket erzeugen, bevor die Einstellungen gespeichert werden. Danach den ersten Global-Admin mit Name, E-Mail und verdeckt eingegebenem Passwort anlegen. Im LXC prüfen:

```bash
cat /srv/materialpool/current/release.json
cmp /usr/local/sbin/materialpool-update /srv/materialpool/current/deployment/release/update.sh
readlink -f /srv/materialpool/current
ls -la /srv/materialpool/releases
stat -c '%a %U:%G' /srv/materialpool/shared/.env /srv/materialpool/shared/storage/oauth-private.key /srv/materialpool/shared/storage/oauth-public.key
runuser -u www-data -- openssl pkey -in /srv/materialpool/shared/storage/oauth-private.key -noout
runuser -u www-data -- openssl pkey -pubin -in /srv/materialpool/shared/storage/oauth-public.key -noout
grep -E '^(APP_ENV|APP_DEBUG|DB_HOST|QUEUE_CONNECTION|QDRANT_URL)=' /srv/materialpool/shared/.env
mariadb -e 'SHOW DATABASES LIKE "materialpool";'
php /srv/materialpool/current/artisan migrate:status
nginx -t && php-fpm8.4 -t
systemctl status nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer
curl -i http://127.0.0.1/up
```

`migrate:status` muss alle für das Release vorgesehenen Migrationen als ausgeführt anzeigen. Beide Passport-Schlüssel müssen lesbar sein, der private mit Modus `600`; die OpenSSL-Prüfungen dürfen keinen Fehler melden und geben keinen Schlüsselinhalt aus. Danach im Browser über den TLS-Reverse-Proxy die Anmeldung des gerade angelegten Global-Admins, geschützte Seite, Resource-Download, Upload/Vorschau und API testen. Den externen HTTPS-Login samt `Secure`-Attribut des Session-Cookies gemäß [geführter Abnahme](abnahme.md#3-zugriff-und-schutzgrenzen) prüfen. Im Test-LXC zusätzlich `runuser -u www-data -- php /srv/materialpool/current/artisan users:manage update` für diesen Testbenutzer ausführen und den Login mit der geänderten E-Mail und dem neuen Passwort prüfen; alte Zugangsdaten dürfen nicht mehr funktionieren. Qdrant nur mit eingerichtetem Schlüssel aus dem Laravel-LXC testen. Passport-Clients für eine frische Instanz benötigen einen gesondert geprüften Einrichtungsschritt.

Im isolierten Test-LXC bei der ersten Admin-Eingabe prüfen, dass nach der Statusmeldung Name, E-Mail und verdecktes Passwort ohne überlagernde gelbe Statusanzeige eingegeben werden können. Zunächst ein zu kurzes Passwort mit weniger als vier Zeichen eingeben: Der Dialog muss bei Name, E-Mail und Passwort neu beginnen, ohne dass der Installer abbricht oder ein Benutzer angelegt wird. Danach gültige Angaben eingeben und den erfolgreichen Installationsabschluss prüfen. In einem separaten isolierten Testlauf Ctrl+C während der Admin-Eingabe drücken: Der Installer muss mit Fehlerstatus enden; anschließend den ersten Admin mit `users:manage create --first-admin --short-password` im LXC nachholen.

Am Ende der Installation, nach einem erfolgreichen `update` und bei „bereits aktuell“ den Block von `materialpool:status` prüfen: Release-Version, `APP_URL`, nur bei Konfiguration Trusted Proxy und Qdrant-Parameter ohne Schlüssel, vier Datenzähler, Querverweis- und Übersetzungsstatus sowie `backup:list` beziehungsweise „Nicht konfiguriert“. Die ausgeschriebene URL aus der Proxmox-Web-Shell versuchsweise im Browser öffnen und die tatsächlich verwendete Konsole samt Klickverhalten notieren; kopierbar muss sie immer sein. Den Command im LXC als `www-data` separat wiederholen. Einen GitHub-Ausfall nur in einer isolierten Testumgebung simulieren: Der Update-Status muss „nicht abrufbar“ lauten und die übrigen lokalen Werte anzeigen. Bei ausgefallenem Backup-Ziel darf der Status keine Erreichbarkeit behaupten.

## F. Kein Node im Produktions-LXC

`command -v node; command -v npm; command -v docker` darf jeweils keinen Pfad liefern. Gleichzeitig müssen `curl -fsS http://127.0.0.1/up` und der Browser-Test erfolgreich sein. `test -s /srv/materialpool/current/public/build/manifest.json` belegt die gebauten Vite-Assets.

## G. Update testen

In einem eigenen Testbranch eine harmlose, sichtbare Textänderung mit bestehendem Übersetzungsmechanismus vornehmen, CI vollständig abwarten, `git tag 0.0.2 && git push test 0.0.2` ausführen und Assets/Checksumme wie in C prüfen. Für diesen Test muss der **isolierte Test-LXC** bereits mit einem Release aus dem Test-Repository installiert sein und `/etc/materialpool/release.conf` dort auf genau dieses Repository zeigen; der öffentliche Fresh-Installer richtet das nicht ein. Vor `update` die Werte aus `current/release.json`, `readlink -f current`, `.env`-Prüfsumme, Prüfsummen beider Passport-Schlüssel, Bibeldatenstatus und einen Testdatensatz/Testupload notieren. `update` ausführen. Der Updater muss `bible:import --update-translations --update-cross-references --no-interaction` ohne neue Auswahl ausführen; bei unverändertem Inhalt sind Verse und Referenzen nicht neu zu schreiben. Danach neues Release, Backup unter `/srv/materialpool/shared/backups`, `migrate:status` mit allen Migrationen des neuen Release, Bibeldatenstatus, `systemctl is-active` aller Dienste, `/up`, Browser, Queue, Admin-Login und persistente Daten prüfen. `cmp /usr/local/sbin/materialpool-update /srv/materialpool/current/deployment/release/update.sh` muss auch nach dem Upgrade erfolgreich sein. Die Prüfsummen beider Passport-Schlüssel müssen unverändert bleiben. Das Update darf keinen weiteren ersten Admin anlegen und bestehende Zugangsdaten nicht ersetzen. Nochmaliges `update` muss „bereits aktuell“ melden.

## H. Persistenztest

### Bibeldaten und Fehlerfälle

Im isolierten Test-LXC zusätzlich den Bibeldatenpfad prüfen: `runuser -u www-data -- php /srv/materialpool/current/artisan bible:import --no-interaction` darf keine Netzwerkverbindung auslösen und nur Status ausgeben. Ein zweiter Aufruf mit `--update-translations --update-cross-references --no-interaction` muss die bereits gewählten Datensätze als unverändert melden. Eine weitere Übersetzung darf dabei nicht installiert werden. Für einen negativen Test ausgehendes GitHub-HTTPS im Test-LXC vorübergehend sperren und denselben Update-Aufruf wiederholen: Bei installierter Übersetzung muss er fehlschlagen, der bisherige Hash erhalten bleiben und der Updater im Wartungsmodus bleiben. HTTPS danach wieder freigeben; das vorbereitete, nicht aktivierte Release darf einen erneuten Versuch nicht blockieren. Beschädigte Manifestdaten und eine absichtlich fehlgeschlagene Datenbankeinfügung ausschließlich mit Test-Releases beziehungsweise isolierter Testdatenbank prüfen; alte Verse, Referenzen und Importstatus müssen erhalten bleiben. Einen Restore aus dem verifizierten Test-Backup und einen Code-Rollback ohne rückwärts gerichteten Bibelimport nachweisen.

Vor G einen isolierten Testdatensatz und einen ungefährlichen Upload in der Testinstanz anlegen; `.env`- und Passport-Schlüssel-Prüfsummen mit `sha256sum /srv/materialpool/shared/.env /srv/materialpool/shared/storage/oauth-private.key /srv/materialpool/shared/storage/oauth-public.key` notieren. Nach G Datensatz, Datei, `APP_KEY`, DB-Zugang, Qdrant-URL und Prüfsummen vergleichen. Geheimwerte nicht in den Testbericht kopieren. `readlink` der neuen Release-Links zu `shared/storage`, `shared/public-uploads` und `shared/.env` prüfen.

## I. Falsche Checksumme

Lokal ein ausschließlich für Tests erzeugtes Archiv kopieren und ein Byte verändern: `cp /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz /tmp/materialpool-bad.tar.gz; printf x >> /tmp/materialpool-bad.tar.gz; sed 's/materialpool-0.0.1.tar.gz/materialpool-bad.tar.gz/' /tmp/materialpool-test-release/materialpool-0.0.1.tar.gz.sha256 > /tmp/materialpool-bad.tar.gz.sha256`. `bash deployment/release/check-package.sh /tmp/materialpool-bad.tar.gz` muss fehlschlagen. Für den echten Updater ein isoliertes Test-Repository mit absichtlich falschem Checksum-Asset verwenden; vor/nach `update` `readlink -f /srv/materialpool/current`, `migrate:status` und `/up` vergleichen. Keine Änderung und kein Maintenance Mode sind erwartet. Nie ein fehlerhaftes Asset in einem produktiven Repository veröffentlichen.

## J. Fehlgeschlagenes Deployment

Im isolierten Test-Repository ein Release-Asset mit gültiger SHA256, aber inkonsistentem `composer.lock` bereitstellen. `update` muss vor dem Wartungsmodus beim `composer install` abbrechen. `readlink -f current`, `/up` und Queue müssen unverändert sein. Der normale Release-Workflow darf ein solches Paket wegen seiner Tests und Composer-Prüfung nicht veröffentlichen.

## K. Code-Rollback

Im Test-LXC aktive und vorherige Version mit `cat current/release.json` und `ls /srv/materialpool/releases` bestimmen. Dann `sudo /usr/local/sbin/materialpool-update rollback 0.0.1`, `readlink -f current`, `systemctl status materialpool-queue.service`, `curl -fsS http://127.0.0.1/up` und Browser prüfen. Dieser Test betrifft **nur Code**. Eine nicht abwärtskompatible Migration verlangt gegebenenfalls den Restore des zugehörigen DB-Backups.

## L. Datenbank-Recovery, nur Testinstanz

Zunächst aktuelle Testdatenbank separat mit `mariadb-dump --single-transaction materialpool | gzip > /root/materialpool-before-recovery.sql.gz` sichern. Gewünschtes `pre-*.sql.gz` unter `/srv/materialpool/shared/backups` bewusst auswählen. Testanwendung in Maintenance Mode setzen, Queue stoppen und `gzip -dc /pfad/zum/geprüften-backup.sql.gz | mariadb materialpool` ausführen. Danach `php /srv/materialpool/current/artisan migrate:status`, Datenintegrität und Uploads prüfen; Queue und Anwendung kontrolliert starten. **Diesen Restore niemals ungeprüft auf Produktion ausführen.** Proxmox-Snapshot/Storage-Backup bleibt eine zusätzliche Ebene.

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
- [ ] Migrationen und interaktiver erster Global-Admin im Test-LXC geprüft; Admin-Login bleibt nach Update möglich
- [ ] `APP_DEBUG=false`, `.env` nicht öffentlich, Proxy-CIDR konkret
- [ ] Qdrant aus Laravel erreichbar und nur intern zugänglich
- [ ] Lokaler/S3-Backup-Restore und Proxmox-Backup geprüft
- [ ] Update, Persistenz, Fehlerfall und Code-Rollback auf Testinstanz geprüft

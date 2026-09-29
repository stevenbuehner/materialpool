# Installation

## Vorbedingungen

Ein neuer, unprivilegierter Debian-13-LXC auf Proxmox VE 9 wird über das [Materialpool-Startskript](../../ct/materialpool.sh) erstellt. Es setzt die Skriptquelle für den Community-Core auf den öffentlichen `master`-Branch des [Materialpool-Repositories](https://github.com/stevenbuehner/materialpool). Der Core lädt anschließend das [Installationsskript für den LXC](../../install/materialpool-install.sh). Auf dem Proxmox-Host und im LXC ist kein Repository-Klon erforderlich; im Laravel-LXC wird kein Git installiert. Das Installationsskript lädt ein versioniertes GitHub-Release-Archiv samt SHA-256-Datei. Anwendungscode und gebaute Frontend-Dateien stammen aus diesem Archiv; PHP-Abhängigkeiten installiert Composer nach `composer.lock`.

Vor der Installation muss ein [stabiles versioniertes GitHub-Release](release.md) mit `materialpool-vX.Y.Z.tar.gz` und passender `.sha256`-Datei veröffentlicht sein. Außerdem müssen reale Werte für HTTPS-Domain, Reverse-Proxy-IP/CIDR, SMTP und S3-Offsite-Backup bereitstehen. Diese Werte fragt der Installer interaktiv ab. Er akzeptiert für eingegebene Konfigurationswerte nur einfache druckbare Zeichen ohne Leerzeichen, `$`, `#` oder Anführungszeichen; abweichende Secrets müssen vorab sicher neu vergeben werden. Eingaben für Passwörter und Tokens erfolgen ohne Echo. Das Repository `stevenbuehner/materialpool` ist vorbelegt. Für ein künftig privates Repository reicht der öffentliche Ein-Link-Einstieg nicht aus und muss neu geplant werden.

1. Zuerst den [Qdrant-LXC](qdrant.md) installieren und absichern, falls die Kontextsuche genutzt werden soll.
2. In der Proxmox-VE-Shell den folgenden Befehl ausführen. Der [Skript-Link](https://github.com/stevenbuehner/materialpool/blob/master/ct/materialpool.sh) verweist auf `ct/materialpool.sh` im öffentlichen `master`-Branch. Default oder Advanced Setup, Container-ID, Storage, Netzwerk und Ressourcen wählen. Default: 2 CPU, 3072 MiB RAM, 24 GiB Disk, Debian 13, amd64, unprivilegiert.

   ```bash
   bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"
   ```

   Optional können Containerwerte vorangestellt werden; `var_ram` ist in MiB und `var_disk` in GiB. Die gewählte Container-ID muss frei sein:

   ```bash
   var_cpu=4 var_ram=4096 var_disk=32 var_ctid=123 bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"
   ```

3. Die abgefragten Produktionswerte eingeben. Der Installer nutzt Community-Helper für PHP, Composer, MariaDB, Datenbank und Nginx, lädt das neueste stabile Release samt SHA256, installiert systemd-Units und führt den Release-Updater aus. Dieser führt `php artisan migrate --force` aus. Anschließend Name, E-Mail und ein Passwort mit mindestens zwölf Zeichen für den ersten aktiven Global-Admin interaktiv eingeben. Das Passwort wird verdeckt abgefragt und erscheint nicht als Befehlsargument.
4. Nach Erfolg im LXC `cat /srv/materialpool/current/release.json`, `readlink /srv/materialpool/current`, `php /srv/materialpool/current/artisan migrate:status`, `systemctl status nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer` und `curl -fsS http://127.0.0.1/up` prüfen. Die Anmeldung des ersten Admins über den TLS-Reverse-Proxy testen.
5. Reverse Proxy mit TLS und enger Firewallregel für Port 80 konfigurieren. `TRUSTED_PROXIES` enthält nur dessen tatsächliche IP/CIDR. Den LXC nicht direkt öffentlich exponieren.

Der neue Installer legt eine leere MariaDB-Datenbank und neue Schlüssel nur für eine wirklich frische Installation an. Er schreibt den erzeugten Datenbankzugang und `APP_KEY` in `/srv/materialpool/shared/.env` mit restriktiven Rechten; jedes Release verlinkt diese Datei als `.env`. Beim ersten Release erzeugt der Updater mit `passport:keys` den privaten und öffentlichen Passport-Schlüssel unter `/srv/materialpool/shared/storage`, falls beide fehlen. Er setzt den privaten Schlüssel auf Modus `0600`. Ein vollständiges Schlüsselpaar bleibt bei einem Wiederanlauf erhalten; ein unvollständiger Bestand stoppt die Installation zur manuellen Prüfung. Eine `.env.local` wird im LXC nicht verwendet. Eine Übernahme bestehender Materialpool-Daten darf den Fresh-Installer nicht ausführen: Dafür müssen zuerst Datenbank, `/srv/materialpool/shared/storage`, `public-uploads`, `.env` und Passport-Schlüssel aus einem verifizierten Backup übertragen werden. Die bisherige Ubuntu-/MySQL-Installation wird nicht automatisch migriert. Es werden weder `db:seed` noch `passport:install` ausgeführt. Passport-Clients für eine frische Instanz benötigen einen gesondert geprüften fachlichen Einrichtungsschritt.

Bricht die Admin-Eingabe nach der technischen Installation ab, bleibt das Release installiert. Im LXC als root den ersten Admin mit `runuser -u www-data -- php /srv/materialpool/current/artisan users:manage create --first-admin` nachholen. Der Befehl akzeptiert `--first-admin` nur bei leerer Benutzertabelle. Weitere Benutzer können mit `users:manage create` angelegt werden; sie erhalten die Standardgruppe und keine globalen Adminrechte. Name, E-Mail und Passwort eines vorhandenen Benutzers werden mit `users:manage update` bearbeitet. Ein leeres neues Passwort lässt das bisherige Passwort unverändert; Status und Rollen bleiben bei der Bearbeitung erhalten. Alle Aufrufe sind interaktiv und gehören nur in ein vertrauenswürdiges Terminal, niemals in Shell-History mit einem Passwortargument.

Die Anwendung bleibt ohne echte SMTP- und S3-Werte nicht produktionsbereit, da `production:preflight` beide prüft. Das lokale Deployment-Backup ergänzt den im Scheduler vorhandenen lokalen und S3-Backupauftrag; vor Produktionsfreigabe muss ein isolierter Restore erfolgreich gewesen sein.

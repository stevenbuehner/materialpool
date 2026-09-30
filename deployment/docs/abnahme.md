# Geführte Proxmox-Abnahme

**Stand:** 30. September 2026. Der Betreiber meldet, dass Neuinstallation und `update` funktionieren. Das ist eine Betriebsmeldung, noch kein Nachweis für die folgenden Prüfungen. Der ausführliche Ablauf mit Fehlersimulationen steht in [testing.md](testing.md).

Wir gehen die Aufgaben in dieser Reihenfolge gemeinsam durch. Für jede Aufgabe wird `offen`, `bestanden`, `fehlgeschlagen` oder `nicht anwendbar` mit Datum, Zielumgebung und knappem Nachweis festgehalten. Keine Zugangsdaten, privaten Schlüssel, `.env`-Inhalte, Nutzdaten oder vollständigen Logs in Chat, Screenshots oder Commits übertragen. Wo Befehle sensible Werte berühren, nur das Prüfergebnis mitteilen.

**Umgebungsgrenze:** Aufgaben 1–4 sind auf dem laufenden LXC lesend beziehungsweise gewöhnliche Browser-Nutzung. Aufgabe 5 verwendet ausschließlich bewusst angelegte Testdaten. Aufgaben 6–8 verändern oder restaurieren Daten und gehören nur auf einen verifizierten, entbehrlichen Test-LXC mit eigenem Backupziel. Eine bestandene Aufgabe ersetzt nicht die anderen Gates.

## 1. Release und aktive Version

**Auf dem Laravel-LXC, lesend:**

```bash
runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status
readlink -f /srv/materialpool/current
jq '{version,commit}' /srv/materialpool/current/release.json
cmp -s /usr/local/sbin/materialpool-update /srv/materialpool/current/deployment/release/update.sh; echo "Updater-Abgleich: $?"
```

**Bestanden:** Status und `release.json` nennen dieselbe aktive Version; `current` zeigt auf das entsprechende Release; Updater-Abgleich meldet `0`. Den Commit gegen das veröffentlichte GitHub Release prüfen; das Vite-Manifest folgt in Aufgabe 2. **Rückmeldung:** Version, Commit-Kürzel und drei Ja/Nein-Ergebnisse; URL und Zähler bei Bedarf entfernen.

## 2. Laufzeit und Dienste

**Auf dem Laravel-LXC, lesend:**

```bash
systemctl is-active nginx php8.4-fpm mariadb materialpool-queue.service materialpool-schedule.timer
curl -fsS http://127.0.0.1/up
test -s /srv/materialpool/current/public/build/manifest.json; echo "Vite-Manifest: $?"
for tool in node npm docker; do if command -v "$tool" >/dev/null; then echo "$tool: vorhanden"; else echo "$tool: fehlt"; fi; done
php /srv/materialpool/current/artisan migrate:status
```

**Bestanden:** Alle fünf Dienste sind `active`, `/up` liefert `OK`, das Manifest existiert, Node/npm/Docker fehlen und keine Migration des aktiven Releases ist offen. **Rückmeldung:** Nur Abweichungen oder „alles bestanden“.

## 3. Zugriff und Schutzgrenzen

**Lesend prüfen:** HTTPS über den echten Reverse Proxy, Login eines berechtigten Benutzers, Abweisung eines nicht berechtigten Kontos, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, konkreter `TRUSTED_PROXIES`-Wert, fehlender externer Zugriff auf MariaDB und `.env`. Die vollständige `.env` nicht ausgeben. Vorhandene Konfiguration kann lokal gezielt mit `grep -E '^(APP_DEBUG|SESSION_SECURE_COOKIE)=' /srv/materialpool/shared/.env` geprüft werden. Die Proxyadresse nur vor Ort bewerten, nicht im Chat veröffentlichen.

Den HTTPS- und Session-Test in einem frischen privaten Browserfenster von außerhalb des LXC durchführen: Die öffentliche `https://`-Adresse mit `/login` öffnen und Zertifikat sowie Adressleiste prüfen. Mit einem berechtigten Testkonto anmelden, `/vue` aufrufen und die geschützte Seite neu laden; die Anmeldung muss erhalten bleiben. In den Browser-Entwicklertools beim Cookie `materialpool_session` für die öffentliche Domain das Attribut `Secure` prüfen. Nur das Prüfergebnis festhalten, weder Cookie-Wert noch Zugangsdaten kopieren. Danach abmelden. Der interne Aufruf von `http://127.0.0.1/up` und die Konfigurationsanzeige von `materialpool:status` ersetzen diesen Browser-Test nicht.

**Bestanden:** HTTPS und sichere Cookies funktionieren; unberechtigter Zugriff scheitert serverseitig; `.env` wird nicht ausgeliefert; MariaDB bindet ausschließlich lokal. **Rückmeldung:** Je Grenze Ja/Nein und Fehlersymptom, ohne echte Adressen oder Konten.

## 4. Qdrant und Hintergrundbetrieb

**Auf beiden LXC, lesend:** Qdrant-Dienst, API-Key und Firewallregel für TCP 6333 prüfen. Einen authentifizierten Healthcheck vom Laravel-LXC ausführen, ohne den Key in die Rückmeldung zu kopieren. Queue und Scheduler mit `systemctl is-active` prüfen; bei Fehler `journalctl -u materialpool-queue.service -u materialpool-schedule.service -n 50 --no-pager` lokal sichten. Die produktiven Kontextsuche-Indexworker und der Indexdispatch bleiben bis zur gesonderten Abnahme nach [Kontextsuche-Queue-Vertrag](../../docs/ai/context-search-queue-change-contract.md) gesperrt.

**Bestanden:** Bei aktivierter Kontextsuche antwortet Qdrant nur authentifiziert aus dem erlaubten Netz und fremde Netze sind blockiert; andernfalls ist die Qdrant-Verbindung als `nicht anwendbar` markiert. Default-Queue und Scheduler laufen. **Rückmeldung:** Aktiv/inaktiv für Kontextsuche, gegebenenfalls Verbindungs- und Firewall-Ergebnis, Dienststatus.

## 5. Fachlicher Smoke-Test und Persistenz

**Nur mit eindeutig gekennzeichneten Testdaten:** Material und Resource anlegen, eine kleine Testdatei hochladen, Download und Vorschau prüfen, Keyword/Bibelstelle zuordnen und eine zulässige Änderung speichern. Die zugehörige Queue-Verarbeitung abwarten. Auf einer Testinstanz denselben Datensatz und Upload vor und nach einem regulären `update` vergleichen; `.env` und Passport-Schlüssel nur über Prüfsummen vergleichen, ihre Inhalte nicht ausgeben.

**Bestanden:** Daten, Pivots, Datei und Vorschau bleiben korrekt; Queuefehler fehlen; Shared-Daten und Schlüssel sind nach dem Update unverändert. **Rückmeldung:** Testumgebung, Ablauf und Ja/Nein pro Ergebnis. Auf einer produktiven Instanz nur fachlich erlaubte, später gezielt entfernbaren Testdaten verwenden; kein erneutes Update allein für diesen Punkt auslösen.

## 6. Backup und isolierter Restore

**Nur auf verifiziertem Test-LXC und getrenntem Test-S3-Ziel:** Lokalen Deployment-Dump, reguläres Backup, S3-Archiv und Proxmox-LXC-Backup inventarisieren. Ein S3-Archiv herunterladen und in eine **andere**, isolierte MariaDB und Dateiablage wiederherstellen. Stichproben für Datenbank, `storage/app` und `public/uploads` prüfen. `.env` und Passport-Schlüssel sind nicht im S3-Archiv: Ihr gesondertes geschütztes Backup und die Wiederherstellung in der Testkopie müssen ebenfalls nachgewiesen werden. Qdrant-LXC-Backup separat zurückspielen. Produktionsdaten oder produktive Backupziele nicht überschreiben.

**Bestanden:** Download, Entpacken, Restore und Stichproben gelingen; Wiederanlauf der Testkopie ist möglich. **Rückmeldung:** Datum, Backupart, isoliertes Restore-Ziel und Ergebnis ohne Dateiinhalte oder Secrets.

## 7. Fehlergrenzen des Updaters

**Nur isolierter Test-LXC mit separatem Test-Repository:** Die Fälle aus [testing.md](testing.md#i-falsche-checksumme) nacheinander prüfen: fehlendes Asset, falsche SHA256, Composer-Fehler, Migrationsfehler und Healthcheck-Fehler. Vor jedem Versuch aktive Version, Wartungsmodus und Datenbankstand notieren. Bei Download-, Prüfsummen- und Composer-Fehler muss die bisherige Anwendung verfügbar bleiben. Bei späteren Fehlern Wartungsmodus und Restore-/Rollbackweg kontrollieren. Keine absichtlich defekten Assets im produktiven Release-Repository veröffentlichen.

**Bestanden:** Jeder Fehler stoppt an der erwarteten Grenze; kein stiller Datenverlust und keine falsche Erfolgsmeldung. **Rückmeldung:** Fall, beobachteter Stopppunkt und Zustand von `current`/Wartungsmodus.

## 8. Code-Rollback und Wiederanlauf

**Nur isolierter Test-LXC:** Nach Aufgabe 6 eine vorhandene vorige Release-Version auswählen und den dokumentierten `materialpool-update rollback <version>` ausführen. Symlink, Dienste, `/up`, Login und Testdaten prüfen. Bei nicht abwärtskompatibler Migration nur mit dem in Aufgabe 6 getesteten Datenbankbackup wiederherstellen. Danach erneut auf die neuere Version aktualisieren.

**Bestanden:** Alter und neuer Code laufen mit dem jeweils passenden Datenstand; Shared-Dateien bleiben erhalten. **Rückmeldung:** Versionsfolge und Prüfergebnisse ohne Nutzdaten.

## Abschlussentscheidung

Die öffentliche Freigabe setzt bestandene Aufgaben 1–6 und eine dokumentierte Bewertung der Aufgaben 7–8 voraus. Fehlerfälle und Rollback sind vor einer Änderung des produktiven Datenbestands auf einer isolierten Umgebung durchzuführen. Offene Punkte erhalten Verantwortliche, Termin und ein klares Go-/No-Go-Ergebnis. „Installation und Update funktionieren“ allein ist noch keine Freigabe für Backup, Sicherheit oder Wiederherstellung.

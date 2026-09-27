# Upgradebericht Stufe 2: Laravel 10

## Ergebnis und Gültigkeit

Stufe 2 wurde am 9. September 2026 mit Laravel 10.50.3 auf PHP 8.1.34 abgeschlossen. Wie Stufe 1 ist dieser EOL-Kompatibilitäts-Checkpoint ausschließlich lokal verwendbar und weder Release- noch Deployment-Kandidat. Die vollständige Suite ist mit 141 Tests und 1.469 Assertions gegen MySQL `testing` grün.

Es wurden weder Schema noch Daten, API, Authentifizierungssemantik, Berechtigungen, Storage-Verträge oder Frontenddefinitionen verändert. Die klassische Laravel-Anwendungsstruktur bleibt gemäß Upgradevertrag erhalten.

## Paket- und Standardmigration

- `laravel/framework`: 9.52.22 auf 10.50.3
- PHP-Constraint: `^8.0.2` auf `^8.1`
- Monolog: 2.11.1 auf 3.11.0
- PHPUnit: 9.6.36 auf 10.5.64
- Collision: 6.4.0 auf 7.12.0
- Spatie Laravel Ignition: 1.7.2 auf 2.9.1
- Spatie Laravel Backup: 8.2.0 auf 8.8.2

## Nachgetragener Online-Leitfadenabgleich

Im Rahmen der Vertragsrevision vom 9. September 2026 wurde der bereits abgeschlossene Checkpoint nochmals online mit dem offiziellen [Laravel-10-Upgradeleitfaden](https://laravel.com/framework/docs/10.x/upgrade) abgeglichen. Die anwendbaren Punkte umfassten PHP 8.1, Composer 2.2, Framework-, Passport-, UI-, Ignition- und Collision-Kompatibilität, `minimum-stability`, Monolog 3, Policy-Registrierung, PHPUnit-/Mocking-Anpassungen und die Suche nach geänderten DB-Expression-, Redis-Tag-, `$dates`-, Form-Request- und Routing-APIs. Ergebnis und Umsetzung sind in den folgenden Abschnitten dokumentiert. Ab Stufe 3 erfolgt dieser Online-Abgleich verbindlich vor dem jeweiligen Dependency-Wechsel und mit einer expliziten Punkt-für-Punkt-Einordnung.

Die in der offiziellen Laravel-10-Anleitung aufgeführten Anwendungsrisiken wurden vollständig durchsucht. Es gibt keine betroffenen `$dates`-Properties, manuellen DB-Expression-Casts, Redis-Cache-Tags, `dispatchNow`-/`dispatch_now`-Aufrufe, `Redirect::home`-Aufrufe, manuellen `QueryException`-Konstruktoren oder kollidierenden Form-Request-`after()`-Methoden.

Folgende aktuelle Konventionen wurden als zusammenhängende, verhaltensneutrale Migration übernommen:

- `$routeMiddleware` wurde entsprechend der Laravel-10-Konvention vollständig in `$middlewareAliases` umbenannt.
- Der nun automatisch ausgeführte Aufruf `registerPolicies()` wurde aus `AuthServiceProvider::boot()` entfernt; der Route-/Policy-Vertrag bleibt grün.
- `AuthServiceProvider::boot()` besitzt den aktuellen nativen Rückgabetyp `void`.
- Der entfernte PHPUnit-Helfer `expectsJobs()` wurde vollständig durch `Bus::fake()` und explizite `Bus::assertDispatched()`-Prüfungen ersetzt.
- `phpunit.xml` wurde mit dem PHPUnit-10-Migrator auf das 10.5-Schema und die aktuelle `<source>`-Struktur überführt. Alle drei Testsuites und sämtliche isolierenden Test-Environment-Werte bleiben erhalten.

Breite, rein kosmetische Native-Type-Ergänzungen aus dem Laravel-Skeleton wurden nicht teilweise übernommen. Sie werden pro zusammenhängendem Bereich in einer späteren Stufe geprüft.

## Ausgeführte Gates

- `composer validate --strict`: erfolgreich.
- Stabiler Composer-Lockfile-Aufbau mit einmaligem `--no-security-blocking`: erfolgreich.
- PHP-Syntax der geänderten PHP-Dateien: erfolgreich.
- PHPUnit-Konfiguration wird von PHPUnit 10 ohne Schemawarnung geladen.
- Vollständige Suite: 141 Tests, 1.469 Assertions, bestanden in 46,83 Sekunden.
- Nested-Set-Vertragsgruppe separat: 15 Tests und 73 Assertions bestanden.
- Nested Sets, Keyword-API, Passport/Routen, Resource-STI, Serialisierung, Pivots, Storage/Archiv und Handler: innerhalb der vollständigen Suite bestanden.
- Frische temporäre Datenbank aus allen 37 historischen Migrationen: erfolgreich.
- Datenbankbackup von `testing` und Restore in eine zweite temporäre Datenbank: erfolgreich; 24 Tabellen und 37 Migrationseinträge verifiziert.
- Temporäre Datenbanken und Backup-Artefakte anschließend vollständig entfernt.
- Bestehender Frontend-Production-Build: erfolgreich. `package.json`, `package-lock.json` und `resources/js/lang-js-translation.js` blieben byteidentisch; Build-Artefakte wurden nicht übernommen.

## Audit und bekannte Grenzen

`composer audit --locked` meldet noch vier Advisories:

- `laravel/framework`: Signed-URL-Pfadverwechslung (mittel) sowie die EOL-bedingte CRLF-Injection der Standard-E-Mail-Regel (hoch und zusätzlich als CVE-2026-48019 erfasst).
- `firebase/php-jwt` 6.11.1: schwache Verschlüsselung (niedrig), innerhalb Passport 11 nicht auf Hauptversion 7 anhebbar.

Der Laravel-9-Befund zum File-Validation-Bypass ist mit Laravel 10.50.3 geschlossen. Die verbleibenden Befunde fallen unter die freigegebene Ausnahme für lokale EOL-Checkpoints. Der Audit bleibt aktiv und der Stand darf nicht produktiv oder öffentlich erreichbar betrieben werden.

`setasign/fpdi-fpdf` bleibt als aufgegebenes Paket dokumentiert. Der unveränderte `npm ci`-Lauf bleibt auf ARM64 wegen `phantomjs-prebuilt` blockiert; hierzu gelten die Verbesserungsvorschläge aus `upgrade-stage-1-report.md` unverändert.

## Ausblick auf Stufe 3

- Laravel 11 erfordert PHP 8.2; das Sail-Laufzeitimage muss vor dem Dependency-Schritt kontrolliert aktualisiert werden.
- Die klassische Kernel-/Provider-/Config-Struktur bleibt bestehen und wird nicht auf das schlanke Laravel-11-Skeleton umgebaut.
- Laravel-11-Änderungen an Authentifizierung, Queues, Scheduling, Mail, Carbon und Anwendungssignaturen werden gegen die bestehenden Vertragsgruppen geprüft.
- Der EOL-Audit wird voraussichtlich weiterhin Framework-Advisories melden; diese bleiben dokumentationspflichtig und ausschließlich lokal toleriert.

# Upgradebericht Stufe 4: Laravel 12.0.0

## Ergebnis und Gültigkeit

Stufe 4 wurde am 9. September 2026 als bewusst kurzer Kompatibilitäts-Checkpoint mit exakt Laravel 12.0.0 auf PHP 8.4.25 abgeschlossen. Die vollständige Suite ist mit 151 Tests und 1.528 Assertions gegen die dedizierte MySQL-Datenbank `testing` grün. Dieser Stand ist wegen bekannter Sicherheitslücken und notwendiger temporärer Paket-Pins ausdrücklich weder Release- noch Deployment-Kandidat. Er muss unmittelbar in Stufe 5 auf einen abgesicherten Laravel-13-Stand überführt werden.

Öffentliche API-, OAuth-, Berechtigungs-, Datenmodell-, Queue-, Storage-, Nested-Set- und Frontendverträge wurden nicht geändert. Die klassische Anwendungsstruktur bleibt erhalten; innerhalb dieser Struktur wurden aktuelle, vollständig verhaltensneutrale Laravel-Konventionen übernommen.

## Verbindlicher Online-Leitfadenabgleich

Der aktuelle offizielle [Laravel-12-Upgradeleitfaden](https://laravel.com/framework/docs/12.x/upgrade) wurde am 9. September 2026 unmittelbar vor Abschluss erneut online vollständig geprüft. Die folgende Matrix bildet jeden dort genannten Änderungspunkt auf das Repository ab.

| Leitfadenpunkt | Status | Umsetzung oder Nachweis |
| --- | --- | --- |
| Framework auf `^12.0` | verhaltensneutral umgesetzt | Für den vereinbarten kurzen Checkpoint ist bewusst exakt `12.0.0` gelockt. Der engere Constraint verhindert, dass ein späterer 12.x-Patch die für Nestedset 6 geltende Obergrenze überschreitet. |
| PHPUnit auf `^11.0` | verhaltensneutral umgesetzt | PHPUnit 11.5.56 und das 11.5-XML-Schema sind aktiv. |
| Pest 3 | nicht betroffen | Pest ist nicht installiert. |
| Carbon 3 | durch Test abgedeckt | Carbon 3.13.2 war bereits in Stufe 3 eingeführt; keine betroffenen `diffIn*`-Verwendungen, vollständige Suite grün. |
| Laravel Installer aktualisieren | nicht betroffen | Das Projekt wird mit Composer/Sail betrieben und verwendet den globalen Laravel Installer nicht. |
| `DatabaseTokenRepository` erhält Ablaufzeit in Sekunden | nicht betroffen | Keine eigene Instanziierung oder Überschreibung des Repositories; Passwort-Reset nutzt die Framework-Bindings. |
| assoziative Ergebnisindizes bei `Concurrency::run` | nicht betroffen | Weder Facade noch Helper werden im Anwendungscode verwendet. |
| Container respektiert nullable/default Klassenparameter | nicht betroffen | Statische Suche und Suite zeigen keine Anwendungsklasse, die für einen nullable Klassenparameter mit Defaultwert auf die alte implizite Auflösung angewiesen ist. |
| Multi-Schema-Inspektion | nicht betroffen | Keine Verwendung von `Schema::getTables()`, `getViews()`, `getTypes()` oder `getTableListing()` im Anwendungscode; Vertragsdatenbank bleibt MySQL mit einem Schema. |
| neue Konstruktoren für Blueprint/Grammar und entfernte Prefix-APIs | nicht betroffen | Keine direkte Instanziierung, eigene Grammar/Schema-Implementierung oder Verwendung der entfernten Methoden. |
| `HasUuids` erzeugt UUIDv7 | nicht betroffen | Kein Anwendungsmodell verwendet `HasUuids` oder `HasVersion7Uuids`; bestehende IDs bleiben unverändert. |
| `mergeIfMissing` interpretiert Punktnotation verschachtelt | nicht betroffen | Die Methode wird nicht verwendet. |
| Präzedenz doppelter Routennamen | durch Test abgedeckt | Die bestehenden doppelten Namen werden nicht bereinigt, weil dies ein öffentlicher Vertrag sein kann. Ein Charakterisierungstest fixiert die weiterhin beobachtete URL-Auflösung für Materialkopie und Bundle. |
| Standard-Root der lokalen Disk wechselt auf `storage/app/private` | durch Test abgedeckt | `filesystems.disks.local.root` bleibt explizit `storage_path('app')`; ein Checkpoint-Test schützt den bisherigen Pfadvertrag. |
| `image` schließt SVG standardmäßig aus | verhaltensneutral umgesetzt | Der bestehende SVG-Uploadvertrag wird mit `image:allow_svg` explizit erhalten und durch einen echten SVG-Uploadtest geprüft. |
| Abgleich mit dem aktuellen Application Skeleton | verhaltensneutral umgesetzt | Provider- und Console-Hooks besitzen `void`; `RouteServiceProvider` verwendet vollständig `$this->routes(...)`. API-vor-Web-Reihenfolge, Prefix, Middleware, Namespace und Routendateien bleiben identisch. Die vertraglich gewählte klassische Struktur wird nicht auf das schlanke Skeleton umgebaut. |

## Paketstand und begründete Kurzzeit-Pins

| Paket | Gelockter Stand | Einordnung |
| --- | --- | --- |
| PHP / Sail | 8.4.25 | Zielruntime bereits hergestellt; eigenes `docker/8.4`-Image. |
| `laravel/framework` | 12.0.0 exakt | Nicht deploybarer Messpunkt zwischen Laravel 11 und 13. |
| `phpunit/phpunit` | 11.5.56 | Vorgabe des Laravel-12-Leitfadens. |
| `kalnoy/nestedset` | 6.0.7 | Letzte 6.x-Version; unterstützt Illuminate nur bis einschließlich 12.0. Nestedset 7 folgt gemeinsam mit Laravel 13. |
| `laravel/passport` | 12.4.3 | Kein Major-Wechsel in dieser Stufe; Passport 13 benötigt die noch ausstehenden Produktentscheidungen. |
| `spatie/laravel-backup` | 9.3.6 | Version 10 verlangt eine spätere Laravel-12-Version als 12.0.0 und folgt in Stufe 5. Konfiguration, Backup und Restore wurden trotzdem vollständig geprüft. |
| `laravel/boost` | 1.0.21 | Boost 2.8 verlangt Laravel mindestens 12.41.1. Für exakt 12.0.0 daher stabiler temporärer Downgrade; Rückkehr auf Boost 2 in Stufe 5. |
| Collision / Ignition | 8.6.1 / 2.9.1 | Neuere Kombinationen ziehen Symfony Console 7.4 ein und führen mit Laravel 12.0.0 zu einem Bootstrap-Fehler. |
| `symfony/console` | 7.2.9 | Expliziter temporärer Pin zur letzten mit dem exakten Framework-Checkpoint funktionierenden Linie; wird in Stufe 5 entfernt. |
| Carbon | 3.13.2 | Laravel-12-Pflicht bereits erfüllt. |

Boost 1 stellt ältere Werkzeuge mit schwächeren Sicherheitsgrenzen bereit. Für diesen Checkpoint werden daher zusätzlich `DatabaseQuery`, `GetConfig`, `ListAvailableEnvVars` und `ReportFeedback` ausgeschlossen. Ein echter MCP-Handshake veröffentlichte nur die freigegebenen lesenden Metadaten-/Dokumentationswerkzeuge. Boost 1 registriert lokal weiterhin seine Browser-Log-POST-Route; der Watcher und damit die Frontend-Instrumentierung sind deaktiviert, das Paket ist `require-dev`, und der Provider läuft nur lokal bei Debug-Betrieb. Dieser temporäre Rest ist ein weiterer Grund gegen ein Deployment.

## Datenbank-, Backup- und Storage-Nachweis

- Fresh-Migration in `materialpool_stage4_fresh`: alle 37 Migrationen erfolgreich, 24 Tabellen; `resources.local_path` bleibt `varchar(512) NULL`, `remote_path` bleibt `varchar(255) NULL`.
- Bestandsupgrade einer isolierten Kopie in `materialpool_stage4_upgrade`: keine ausstehenden Migrationen, weiterhin 24 Tabellen und 37 Migrationseinträge.
- Backup ausschließlich der isolierten Fresh-Datenbank mit Backup 9 und Restore in `materialpool_stage4_restore`: Quelle und Ziel jeweils 24 Tabellen, 37 Migrationen und 343.600 `bibleverses_cross_ref`-Zeilen.
- Backup-, Arbeits- und Restore-Dateien lagen nur in expliziten `/tmp/materialpool_stage4_*`-Verzeichnissen.
- Die drei temporären Datenbanken und Verzeichnisse wurden nach erfolgreicher Prüfung entfernt. Die Entwicklungsdatenbank war in keinem Schreibbefehl Ziel; PHPUnit verwendete ausschließlich `testing`.
- Der Metadaten-Fingerprint der regulären Resource-/Archive-Dateien blieb vor und nach den Gates identisch. Alle Dateitests verwendeten Fakes oder isolierte temporäre Disks.

## Ausgeführte Gates

- Online-Leitfadenabgleich: vollständig, siehe Matrix.
- `composer install --dry-run --no-interaction`: keine Änderungen; Lockfile reproduzierbar.
- `composer validate --strict`: ausschließlich die erwartete Warnung gegen den absichtlich exakten Framework-Constraint. Der Checkpoint-Test stellt sicher, dass dieser Pin nicht versehentlich beweglich wird.
- `composer audit --locked`: vier Advisories, ausschließlich für Laravel 12.0.0; Details unten.
- PHP-Syntax aller geänderten PHP-Dateien: erfolgreich unter PHP 8.4.
- Laravel-Bootstrap und `route:list --json`: erfolgreich; Routenverträge grün.
- Vollständige PHPUnit-Suite: 151 Tests, 1.528 Assertions, keine PHPUnit-Issues, 59,66 Sekunden.
- Nested-Set-Vertragsgruppe separat: 24 Tests bestanden; Forest, Grenzen, Tiefe, Reihenfolge, Moves, ungültige Moves, Merge, beide Löschvarianten, Suche und Pivotübernahme stabil.
- Handler-/Resource-/Foreign-Resource-/Material-/Upgrade-Verträge separat: 99 Tests und 1.003 Assertions bestanden, einschließlich Upload, Dateilöschung, Autorisierung, STI, Pivotdaten, Storage, Queue und Bundle.
- Fresh-/Bestandsmigration sowie Backup/Restore: erfolgreich, siehe oben.
- Frontend: reguläres `npm ci` reproduziert ausschließlich den bekannten ARM64-Fehler von `phantomjs-prebuilt`; `npm ci --ignore-scripts` und `npm run build` sind erfolgreich. `package.json`, `package-lock.json` und `resources/js/lang-js-translation.js` blieben byteidentisch. Erzeugte Build-Artefakte wurden entfernt.
- Entwicklungsdatenbank und regulärer Storage: keine Schreibziele der Prüfung; die vor und nach den Gates geprüften Storage-Metadaten sind identisch.

## Audit und bekannte Grenzen

`composer audit --locked` meldet vier Advisories, die alle ausschließlich `laravel/framework` 12.0.0 betreffen:

1. Temporary Signed URL Path Confusion, mittel (`GHSA-crmm-hgp2-wgrp`).
2. CRLF-Injection in der Standard-E-Mail-Regel, hoch (`GHSA-5vg9-5847-vvmq`).
3. derselbe CRLF-Befund zusätzlich unter `CVE-2026-48019`.
4. File-Validation-Bypass, mittel (`CVE-2025-27515`).

Diese bekannten Lücken dürfen nur für den ausdrücklich vereinbarten lokalen Messpunkt toleriert werden. Sie blockieren jedes Deployment, aber nicht den unmittelbaren Übergang zu Laravel 13. `setasign/fpdi-fpdf` bleibt als aufgegebenes Paket ohne vorgeschlagenen Ersatz dokumentiert.

Der unveränderte Legacy-Frontend-Lock-Stand meldet 129 Findings (19 niedrig, 38 mittel, 54 hoch, 18 kritisch). Ihre Behebung gehört zum separaten Frontend-Upgrade und würde den in P1 unveränderlichen npm-Vertrag überschreiten.

## Nicht umgesetzte Verbesserungsvorschläge

1. **Doppelte Routennamen bereinigen.** Empfehlung: nach Laravel 13 als eigener API-/Routing-Entscheid eineindeutige Namen vergeben und alle internen Nutzer migrieren. Alternative: dauerhaft durch den bestehenden Charakterisierungstest schützen. Nutzen: keine versionsabhängige Präzedenz. Risiko: Änderung erzeugter URLs und externer Aufrufer. Rückbau: alte Namen und Reihenfolge wiederherstellen.
2. **SVG-Uploads härter absichern.** Empfehlung: separat prüfen, ob SVG fachlich weiterhin nötig ist und gegebenenfalls Sanitizing plus Download-Header einführen. Alternative: aktuellen expliziten Vertrag beibehalten. Nutzen: geringere aktive Inhaltsrisiken. Risiko: bestehende SVG-Ressourcen oder Uploads könnten abgewiesen oder verändert werden. Rückbau: Sanitizer/zusätzliche Regeln entfernen.
3. **Aufgegebenes PDF-Paket ersetzen.** Empfehlung: nach dem Framework-Upgrade tatsächliche Nutzung und Ersatz durch gepflegte FPDI-/FPDF-Pakete analysieren. Alternative: vorerst unverändert lassen. Risiko und Rückbau hängen von den erzeugten PDF-Verträgen ab; daher keine stillschweigende Entfernung.
4. **Legacy-Frontend modernisieren.** Empfehlung: Node, PhantomJS-Abhängigkeiten und npm-Audit gemeinsam im bereits getrennt geplanten Frontend-Projekt bearbeiten. Eine Einzelreparatur im Backend-Upgrade würde Lockfile und Buildvertrag verletzen.

## Übergabe an Stufe 5

Stufe 5 muss unmittelbar von diesem Commit ausgehen. Dort werden Laravel 13, Nestedset 7, Backup 10 und Boost 2 gemeinsam aufgelöst; die temporären Pins für Boost 1, Collision, Ignition und Symfony Console entfallen. Vor Passport 13 bleiben die im Upgradevertrag benannten Entscheidungen zu Client-Secret-Hashing, dem neuen `oauth_clients`-Schema mit Integer-ID-Kollision und der entfallenen Authorization-View zwingend offen. Sie dürfen nicht durch eine technische Standardwahl vorweggenommen werden.

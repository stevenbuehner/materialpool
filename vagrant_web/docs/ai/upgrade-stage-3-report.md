# Upgradebericht Stufe 3: Laravel 11

## Ergebnis und Gültigkeit

Stufe 3 wurde am 9. September 2026 mit Laravel 11.56.1 und Passport 12.4.3 auf PHP 8.2.33 abgeschlossen. Dieser EOL-Kompatibilitäts-Checkpoint ist ausschließlich lokal verwendbar und weder Release- noch Deployment-Kandidat. Die vollständige Suite ist mit 144 Tests und 1.475 Assertions gegen die dedizierte MySQL-Datenbank `testing` grün.

Die Anwendung behält ihre klassische Laravel-Struktur, ihre API-, OAuth-, Berechtigungs-, Queue-, Storage- und Serialisierungsverträge sowie das unveränderte Vue-2-Frontend. Die einzige Änderung an einer anwendungseigenen historischen Migration ist die im Upgradevertrag ausdrücklich freigegebene, schemaäquivalente Wiederholung von `nullable()` für `resources.remote_path` und `resources.local_path`.

## Verbindlicher Online-Leitfadenabgleich

Vor dem Dependency-Wechsel wurden am 9. September 2026 der offizielle [Laravel-11-Upgradeleitfaden](https://laravel.com/framework/docs/11.x/upgrade) und der offizielle [Passport-12-Upgradeleitfaden](https://github.com/laravel/passport/blob/12.x/UPGRADE.md) online geprüft. Die folgende Zuordnung bildet alle Leitfadenbereiche auf dieses Repository ab.

| Leitfadenpunkt | Status | Umsetzung oder Nachweis |
| --- | --- | --- |
| PHP mindestens 8.2 und curl mindestens 7.34 | verhaltensneutral umgesetzt | Sail verwendet PHP 8.2.33 und curl 7.81.0. |
| Framework `^11.0`, Collision `^8.1`, Passport `^12.0` | verhaltensneutral umgesetzt | Gelockt sind Laravel 11.56.1, Collision 8.5.0 und Passport 12.4.3. |
| Migrationen von Laravel-Erstpaketen veröffentlichen | verhaltensneutral umgesetzt | Die fünf Passport-Migrationen wurden mit unveränderten Dateinamen und byteidentischem Inhalt aus Passport 12 übernommen. Ein Vertragstest vergleicht Liste und Inhalt mit dem Lock-Stand. Cashier, Sanctum, Spark und Telescope sind nicht installiert. |
| Anwendungsstruktur | nicht betroffen | Entsprechend der ausdrücklichen Laravel-Empfehlung für Upgrades bleibt die klassische Laravel-10-Struktur erhalten. |
| Password Rehashing | durch Test abgedeckt | `hashing.rehash_on_login` ist explizit `false`; damit bleibt das bisherige Login-Verhalten erhalten. Eine Aktivierung ist eine gesonderte Sicherheits-/Verhaltensentscheidung. |
| `UserProvider`-/`Authenticatable`-Contracts | nicht betroffen | Die Anwendung verwendet Laravels Eloquent-Implementierung und den Framework-Trait; keine eigenen Contract-Implementierungen. |
| `AuthenticationException::redirectTo()` | nicht betroffen | Keine manuellen Aufrufe. Der anwendungseigene Authenticate-Middleware-Hook verwendet nun vollständig die aktuelle `Request`-/`?string`-Signatur. |
| automatische E-Mail-Verifikation | nicht betroffen | `User` implementiert `MustVerifyEmail` nicht; die Anwendung registriert keinen entsprechenden eigenen Listener. |
| Cache-Prefix ohne automatisch angehängten Doppelpunkt | durch Test abgedeckt | Das bisher effektiv verwendete Präfix bleibt mit `laravel:` explizit erhalten. |
| `Enumerable::dump()` | nicht betroffen | Keine eigene Implementierung des Contracts. |
| SQLite mindestens 3.26 | nicht betroffen | Vertrags- und Laufzeitdatenbank ist MySQL 8. |
| Eloquent-Beziehung namens `casts` | nicht betroffen | Keine solche Beziehung vorhanden. |
| alle Modifikatoren bei `change()` wiederholen | verhaltensneutral umgesetzt | Sämtliche Vorkommen wurden geprüft. Die beiden zuvor implizit erhaltenen `nullable`-Attribute der Resource-Pfade wurden gemäß ausdrücklicher Vertragsfreigabe in Vorwärts- und Rückwärtsdefinition ergänzt; Fresh- und Bestandsschema sind gleich. |
| Float-/Double-Migrationstypen | nicht betroffen | Keine betroffenen Definitionen. |
| eigener MariaDB-Treiber | nicht betroffen | MySQL 8 bleibt verbindlich; kein Treiberwechsel. |
| Spatial Types | nicht betroffen | Keine räumlichen Spalten oder Schema-Aufrufe. |
| Doctrine-DBAL-Entfernung und native Schema-APIs | nicht betroffen | Keine direkte Doctrine-DBAL-Abhängigkeit, keine entfernten Methoden und keine eigenen Schema-Contracts. |
| Carbon 3 | durch Test abgedeckt | Carbon 3.13.2 ist gelockt. Anwendung und Bible-Paket verwenden keine semantisch geänderten `diffIn*`-Aufrufe; vollständige Suite und Datumsverträge sind grün. |
| `Mailer::sendNow()` | nicht betroffen | Keine eigene Implementierung des Mailer-Contracts. |
| Package-Service-Provider-Veröffentlichung | nicht betroffen | Keine betroffene eigene Paketveröffentlichung. |
| synchrone Queue nach Commit | durch Test abgedeckt | `after_commit` bleibt für `sync` und `database` explizit `false`; Queue-Namen, Events und Jobs bleiben unverändert. |
| sekundengenaues Rate Limiting | nicht betroffen | Keine betroffenen Limiter-Definitionen. |
| `spatie/once` | nicht betroffen | Paket und Helfer werden nicht verwendet. |
| Passport 12: Migration Loading | verhaltensneutral umgesetzt | Automatisches Laden wurde nicht nachgebaut; stattdessen liegen exakt die fünf offiziell zu veröffentlichenden Migrationen in der Anwendung. |
| Passport 12: Password Grant standardmäßig deaktiviert | verhaltensneutral umgesetzt | `Passport::enablePasswordGrant()` erhält die bisherige Grant-Fähigkeit; der Aktivierungszustand wird getestet. |

## Paket- und Standardmigration

- `laravel/framework`: 10.50.3 auf 11.56.1
- PHP-Constraint und Sail-Laufzeit: `^8.1`/PHP 8.1 auf `^8.2`/PHP 8.2.33
- `laravel/passport`: 11.x auf 12.4.3
- `nunomaduro/collision`: 7.12.0 auf 8.5.0
- `spatie/laravel-backup`: 8.8.2 auf 9.3.6
- `barryvdh/laravel-ide-helper`: 2.x auf 3.7.0
- `mariuzzo/laravel-js-localization`: 1.x auf 2.1.0
- Carbon: 2.x auf 3.13.2

Zusammenhängende aktuelle Laravel-Konventionen wurden übernommen, wo dies vollständig und verhaltensneutral möglich war: Provider-Hooks besitzen den nativen Rückgabetyp `void`, der Authenticate-Hook die aktuelle Request-/Nullable-Return-Signatur, und redundante Exception-Handler-Methoden ohne eigene Logik wurden entfernt. Die klassische Verzeichnis- und Bootstrap-Struktur bleibt bewusst erhalten.

## Datenbank- und Backup-Nachweis

- Eine isolierte leere Datenbank wurde aus allen 37 Migrationen aufgebaut: 24 Tabellen; `remote_path` bleibt `varchar(255) NULL`, `local_path` bleibt `varchar(512) NULL`.
- Eine Kopie der strukturell realistischen Testdatenbank wurde auf dem neuen Stand migriert: keine ausstehenden Migrationen, 24 Tabellen und 37 Migrationseinträge.
- Ein Backup der isolierten Fresh-Datenbank wurde mit `spatie/laravel-backup` 9 erstellt und in eine dritte temporäre Datenbank restauriert.
- Quelle und Restore enthielten jeweils 343.600 Einträge in `bibleverses_cross_ref`; Tabellen- und Migrationsanzahl waren identisch.
- Backup-Ziel und Arbeitsverzeichnis lagen ausschließlich unter eigens gesetzten `/tmp/materialpool_stage3_*`-Pfaden. Regulärer Ressourcen- und Archiv-Storage wurde nicht verwendet.

## Ausgeführte Gates

- Online-Leitfadenabgleich Laravel 11 und Passport 12: vollständig, siehe Matrix oben.
- `composer validate --strict`: erfolgreich.
- PHP-Syntax der geänderten PHP-Dateien: erfolgreich.
- Vollständige PHPUnit-Suite: 144 Tests, 1.475 Assertions, bestanden in 65,30 Sekunden.
- Upgrade-Baseline und Nested-Set-Gruppen gemeinsam: 49 Tests, 341 Assertions, bestanden.
- Nested-Set-Vertragsgruppe separat sowie Handler-, Resource-/Foreign-Resource- und Material-Relationstests: bestanden.
- Fresh-/Bestandsmigration sowie Backup/Restore: erfolgreich, siehe oben.
- Frontend-Production-Build: erfolgreich. `package.json`, `package-lock.json` und `resources/js/lang-js-translation.js` blieben byteidentisch; generierte Build-Artefakte wurden nicht übernommen.
- `npm ci --ignore-scripts`: erfolgreich. Der reguläre `npm ci`-Lauf reproduziert weiterhin ausschließlich den bekannten ARM64-Fehler von `phantomjs-prebuilt`.

## Audit und bekannte Grenzen

`composer audit --locked` meldet drei Einträge zu zwei Laravel-Framework-Sicherheitsproblemen: Signed-URL-Pfadverwechslung (mittel) und CRLF-Injection der Standard-E-Mail-Regel (hoch, zusätzlich als CVE erfasst). Diese Findings sind durch den EOL-Zwischenstand unvermeidbar, fallen ausschließlich unter die vertragliche lokale Checkpoint-Ausnahme und machen Stufe 3 nicht deploybar. `setasign/fpdi-fpdf` bleibt als aufgegebenes Paket dokumentiert.

Der unveränderte Legacy-Frontend-Lock-Stand enthält laut npm 129 bekannte Findings (19 niedrig, 38 mittel, 54 hoch, 18 kritisch). Ihre Behebung würde das separat geplante Frontend-Upgrade erfordern und ist nicht durch P1 autorisiert.

## Nicht umgesetzte Verbesserungsvorschläge

1. **Password-Rehashing aktivieren.** Empfehlung: nach Laravel 13 als eigener Security-Cutover mit Login-, Hash- und Rückbauprüfung aktivieren. Nutzen: veraltete Work Factors werden bei erfolgreichem Login angehoben. Risiko: zusätzlicher DB-Schreibzugriff und verändertes Authentifizierungsverhalten. Alternative: `false` beibehalten. Rückbau: Konfigurationswert zurücksetzen; bereits erneuerte Hashes bleiben gültig.
2. **Legacy-Frontend-Laufzeit erneuern.** Empfehlung: Node, PhantomJS-Abhängigkeiten und npm-Audit im separaten Frontend-Projekt gemeinsam modernisieren. Eine isolierte Änderung im Backend-Upgrade würde Lockfile und Buildvertrag verletzen. Rückbau einer späteren Umstellung: letzter Frontend-Lockfile-Checkpoint.
3. **Aufgegebenes PDF-Metapaket ersetzen.** Empfehlung: Nutzung und direkte FPDI-/FPDF-Constraints nach dem Laravel-13-Checkpoint separat analysieren. Alternative: unverändert lassen. Eine Entfernung oder API-Änderung ist derzeit nicht autorisiert.

## Ausblick auf Stufe 4

Vor Laravel 12 werden der dann aktuelle offizielle Leitfaden erneut online geprüft, PHP 8.4 und PHPUnit 11 hergestellt und alle Laravel-12-Punkte einzeln auf das Repository abgebildet. Verhaltensändernde Defaults – insbesondere lokale Disk-Roots, SVG-Validierung und Routing-Präzedenz – werden nicht ohne ausdrückliche Entscheidung übernommen.

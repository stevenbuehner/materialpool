# Upgradebericht Stufe 5: Laravel 13

## Ergebnis

Stufe 5 wurde am 9. September 2026 auf Laravel 13.31.0, PHP 8.4.25 und PHPUnit 12.5.35 abgeschlossen. Die vollständige Suite ist mit 156 Tests und 1.588 Assertions gegen die dedizierte MySQL-Datenbank `testing` grün. Nestedset 7, Resource-STI, Passport 13, Backup 10, Handler, API-Autorisierung, Pivots, Storage und Queues sind einbezogen.

Das Vue-2-Frontend, seine Quellen, `package.json` und `package-lock.json` wurden nicht verändert. Ein Production-Build wurde in einer isolierten Kopie mit der im Sail-Image vorhandenen Node-16-Laufzeit erfolgreich erzeugt. Der Host-Build mit Node 26 ist erwartungsgemäß nicht kompatibel mit Webpack 4; seine Behebung bleibt Teil des separaten Frontend-Upgrades.

## Abhängigkeiten

| Paket | Stand | Einordnung |
| --- | --- | --- |
| `laravel/framework` | 13.31.0 | aktueller Laravel-13-Stand des Lockfiles |
| `laravel/passport` | 13.8.0 | Standard-UUIDs, gehashte Secrets, neues Client-Schema |
| `kalnoy/nestedset` | 7.0.0 | Laravel-13-kompatible Hauptversion |
| `tightenco/parental` | 1.6.0 | ersetzt das aufgegebene Nanigans-STI-Paket |
| `spatie/laravel-backup` | 10.3.2 | vorhandene Backup-Verträge bleiben wirksam |
| `laravel/boost` | 2.8.0 | nur `require-dev`, schreibende Werkzeuge und Browser-Watcher deaktiviert |
| `laravel/tinker` | 3.0.2 | Laravel-13-Zielversion |
| `phpunit/phpunit` | 12.5.35 | XML-Schema auf 12.5 aktualisiert |
| `nunomaduro/collision` | 8.9.5 | kompatibler stabiler Stand |
| `fruitcake/laravel-debugbar` | 4.4.3 | ersetzt den alten Debugbar-Fork; Standard-Autodiscovery |

`minimum-stability` ist `stable`. Das separat verwaltete `stevenbuehner/bible-verse-bundle` ist als einzige dokumentierte Ausnahme unveränderlich auf Commit `ff33d614541f5cfba69dcd121d6c04cc8da921c4` fixiert. Composers Warnung gegen Commit-Referenzen bleibt deshalb bestehen, bis der Auftraggeber denselben Stand stabil taggt. Der PHP-/JavaScript-Exportvertrag des Pakets ist getestet.

`composer outdated --direct --strict` nennt nur zwei nicht automatisch auszuführende Major-Wechsel: Intervention Image 2 auf 4 und PHPUnit 12 auf 13. Intervention Image berührt die Medien-API und gehört in ein separates Backend-Teilprojekt; Laravel 13 empfiehlt PHPUnit 12, weshalb PHPUnit 13 nicht übernommen wurde.

## Laravel-13-Leitfadenmatrix

Der offizielle [Laravel-13-Upgradeleitfaden](https://laravel.com/framework/docs/13.x/upgrade) wurde unmittelbar vor und während der Umsetzung am 9. September 2026 vollständig geprüft.

| Leitfadenpunkt | Einordnung und Umsetzung |
| --- | --- |
| Abhängigkeiten | Framework `^13.0`, Boost `^2.8`, Tinker `^3.0`, PHPUnit `^12.5`; temporäre Laravel-12-Pins und Ignition entfernt. |
| Laravel Installer | Nicht betroffen; das Projekt wird nicht mit einem globalen Installer erzeugt. |
| Upgrade mit AI / Boost 2 | Boost 2.8 ist lokal integriert. Die vorhandenen Sicherheitsgrenzen bleiben aktiv; kein Produkt-MCP und keine Produkt-KI. |
| Cache-/Redis-/Session-Präfixe | Bestehender Cache-Präfix und Session-Cookie bleiben explizit. Der bisherige underscore-basierte Redis-Fallback ist nun ebenfalls explizit. |
| Cache-Verträge `touch` | Keine eigenen Cache-Store- oder Repository-Implementierungen vorhanden. |
| Cache `serializable_classes` | Auf `false` gehärtet. Anwendungscaches speichern nur String-/Arraydaten; Preview-Cache-Vertrag ist getestet. |
| `Container::call` und nullable Defaults | Keine betroffenen Aufrufe gefunden. |
| neue Methoden in Dispatcher-, ResponseFactory- und MustVerifyEmail-Verträgen | Keine eigenen Implementierungen vorhanden. |
| MySQL `upsert` mit leerem `uniqueBy` | Keine `upsert`-Aufrufe im Anwendungscode. |
| MySQL-DELETE mit Join/Order/Limit | Keine betroffene kombinierte Delete-Query; vorhandene Joins und Deletes treten getrennt auf. |
| Model-Booting und verschachtelte Instanziierung | Parental-Konfiguration und anwendungseigene Boot-Callbacks instanziieren Modelle nur in später ausgeführten Closures. Alle STI-Typen erzeugen und hydratisieren erfolgreich. |
| polymorphe Pivot-Tabellennamen | Keine implizit benannten polymorphen Custom-Pivots. Passport verwendet sein explizites Zielschema. |
| Collection-/Model-Serialisierung mit eager Relations | Bundle-Queue-Payload und Jobreihenfolge bleiben durch Serialisierungs- und Queue-Verträge geschützt. Keine Logik setzt fehlende eager Relations voraus. |
| HTTP-Client `Response::throw` / `throwIf` | Keine eigenen Response-Unterklassen oder überschriebenen Methoden. |
| Password-Reset-Betreff | Keine anwendungseigene Assertion oder Überschreibung des Framework-Standardbetreffs. Der neue Laravel-Standard wird übernommen. |
| Queued Notifications bei fehlenden Modellen | Keine betroffenen queued Notification-Klassen. |
| `JobAttempted::$exception` | Keine Listener auf `JobAttempted`. |
| `QueueBusy::$connectionName` | Keine Listener auf `QueueBusy`. |
| zusätzliche Queue-Contract-Methoden | Kein eigener Queue-Treiber. |
| Domain-Route-Präzedenz | Keine Domain-Routen vorhanden. Doppelte Routennamen wurden vollständig beseitigt, damit URL-Erzeugung nicht mehr von Registrierungspräzedenz abhängt. |
| Session-Serialisierung | Explizit `php`, damit der Upgrade-Deploy aktive Sessions nicht invalidiert. JSON bleibt ein eigener Security-Cutover. |
| `withScheduling`-Zeitpunkt | Klassischer Console Kernel; `ApplicationBuilder::withScheduling()` wird nicht verwendet. Scheduler-Verträge sind grün. |
| Request-Forgery-Schutz | Anwendungsmiddleware und Kernel verwenden `PreventRequestForgery`. Same-Origin wird akzeptiert, Cross-Site ohne gültiges Token abgelehnt. |
| Manager-`extend`-Closure-Binding | Keine betroffenen Manager-Extensions. |
| Reset von `Str`-Factories zwischen Tests | Keine testübergreifend vorausgesetzten String-Factories. |
| `Js::from` und Unicode | Keine betroffenen `Js::from`-Ausgabeassertions oder Aufrufe. |
| PHP-8.5-Polyfill / `array_first` und `array_last` | Keine kollidierenden globalen Helfer im Anwendungscode. |
| Bootstrap-3-Pagination-Views | Keine Verwendung der umbenannten View-Namen. |

## Passport-13-Leitfadenmatrix

Der offizielle [Passport-13-Upgradeleitfaden](https://github.com/laravel/passport/blob/13.x/UPGRADE.md) wurde am 9. September 2026 vollständig geprüft. Die konkrete Client- und Deployment-Anleitung steht in `docs/ai/passport-13-client-migration.md`.

| Leitfadenpunkt | Einordnung und Umsetzung |
| --- | --- |
| PHP mindestens 8.2 / OAuth2 Server 9 | PHP 8.4.25 und Passport 13.8.0 sind gelockt. |
| Headless Views | Paketdefault bleibt bestehen. Keine unfreigegebene Authorization-/Device-Oberfläche wird eingeführt; browserbasierte Flows benötigen später eine eigene UI-Entscheidung. |
| `OAuthenticatable` | `App\Models\User` implementiert den Vertrag zusätzlich zu `HasApiTokens`. |
| UUID-Client-IDs | Standard bleibt aktiv. Bestehende Integerwerte werden als Strings erhalten, neue Clients erhalten UUIDs. |
| gehashte Client-Secrets | Upgrade-Migration hasht vorhandene Klartexte; neue Clients werden durch das Passport-Modell automatisch gehasht. Klartext wird nur einmal bei Erstellung ausgegeben. |
| `User::token()` liefert `AccessToken` | Keine Anwendungscode-Annahme über den früheren Token-Typ gefunden. Bestehende `Passport::actingAs`-Tests sind grün. |
| Middleware-Umbenennungen | Keine alten Passport-Scope-/Client-Credentials-Middlewareklassen verwendet. |
| Personal-Access-Client-Tabelle | Passport 13 nutzt sie nicht mehr. Die historische Tabelle wird entsprechend der offiziellen optionalen Entfernung zunächst nicht destruktiv gelöscht. |
| veraltete JSON-API-Routen | Paketdefault `false` bleibt bestehen; die drei alten Verwaltungsbereiche sind nicht registriert und explizit getestet. |
| Key-Dateirechte | Lokale Public- und Private-Key-Dateien haben Modus `0600`; echter Token-Austausch ist grün. Schlüssel sind nicht in Git erfasst. |
| neues `oauth_clients`-Schema | `owner`, `redirect_uris`, `grant_types` sowie 36-stellige IDs werden durch eine neue, versionierte Migration hergestellt. Historische Migrationen bleiben unverändert. |
| Device Codes | Offizielle Migration wurde bytegleich veröffentlicht; Device-Routen sind registriert. |
| Password Grant | Als einzige produktbedingte Abweichung ausdrücklich aktiviert. Ein aus dem Altschema migrierter Client erhält mit unverändertem Credential ein Access- und Refresh-Token. |

## Weitere Paket- und Konventionsanpassungen

- Nanigans STI wurde vollständig durch Parental ersetzt. `Resource` hydratisiert alle zehn gespeicherten Typen; `File` begrenzt Queries weiterhin auf die sechs Dateitypen.
- Debugbar nutzt Provider-Autodiscovery statt manueller Registrierung eines alten Namespaces.
- Der überholte Passport-6-Aufruf `withoutCookieSerialization()` wurde entfernt, weil `false` in Passport 13 bereits der Paketdefault ist und getestet wird.
- Die beiden doppelten API-Routennamen heißen nun eindeutig `api.v1.materials.copy` und `api.v1.bundles.index`. Pfade und HTTP-Methoden bleiben unverändert.
- Laravel UI behält den Standardnamen `logout` für POST. Der für das unveränderte Vue-2-Frontend noch nötige GET-Pfad bleibt als `logout.legacy` erreichbar.
- PHPUnit verwendet das 12.5-Schema. Boost-1-Kompatibilitätszweige wurden vollständig entfernt.

## Ausgeführte Prüfungen

- `composer update --lock --no-interaction`: reproduzierbarer Lock-Stand, Paket-Discovery erfolgreich.
- `composer validate --strict`: gültig; ausschließlich die dokumentierte Commit-Referenz-Warnung des separat verwalteten Bible-Pakets.
- `composer audit --locked`: keine Security-Advisories; ein aufgegebenes Paket ohne vorgeschlagenen Ersatz (`setasign/fpdi-fpdf`).
- PHP-Syntaxprüfung aller 29 geänderten beziehungsweise neuen PHP-Dateien im PHP-8.4-Container: bestanden.
- `php artisan migrate:fresh --env=testing --force`: vollständiges Fresh-Schema einschließlich Passport-13-Migrationen: bestanden.
- Befülltes Passport-Altschema: Secret-Hashing, Owner-/Grant-/Redirect-Übernahme, ID-/Tokenreferenz-Erhalt und echter Password-Grant-Tokenaustausch: bestanden.
- Vollständige PHPUnit-Suite: 156 Tests, 1.588 Assertions, bestanden.
- Nestedset-Vertragsgruppe: alle Forest-, Boundary-, Depth-, Move-, Merge-, Delete-, Search-, Pivot- und JSON-Fälle innerhalb des Gesamtlaufs bestanden; `isBroken()` bleibt false.
- Handler-Gruppe separat: 7 Tests, 40 Assertions; gemeinsam mit Boost 10 Tests und 46 Assertions, bestanden.
- Backup 10: isoliertes Datei-Backup erzeugt, entpackt und per SHA-256 gegen die Fixture geprüft.
- Production-Webpack-Build: in isolierter Kopie im Sail-Image mit Node 16 erfolgreich; Repository-Ausgaben wurden nicht überschrieben.
- `route:list --json`: erfolgreich; Passport-Core-/Device-Routen vorhanden, alte JSON-Verwaltungsrouten abwesend.
- Frontend-Manifeste/-Quellen und reguläre Storage-Pfade zeigen keine Git-Änderungen. Alle Dateioperationstests verwenden Fakes; nur `testing` wurde migriert.

## Offene Punkte und Empfehlungen

### P0 vor produktivem Deployment

1. **Bible-Paket stabil taggen.** Empfehlung: Commit `ff33d614…` im separaten Projekt unverändert als semantischen Stable-Release veröffentlichen und hier nur den Constraint ersetzen. Alternative: Commit-Pin vorübergehend behalten; dann bleibt Composers Warnung bestehen. Rückbau: zurück auf den Commit-Pin.
2. **Passport-Cutover proben.** Empfehlung: Clientinventar, verifiziertes Backup, Wartungsfenster und den Ablauf aus `passport-13-client-migration.md` in einer produktionsnahen Kopie durchspielen. Alternative: kein Deployment. Rückbau: ausschließlich Code plus Datenbankbackup.
3. **Runtime bereitstellen.** Produktion muss PHP 8.4.x, passende Erweiterungen, Composer 2 und die geprüfte MySQL-8-Semantik verwenden. Ein Deployment auf der lokalen Host-PHP-8.0-Laufzeit ist unmöglich.

### P1 nach stabilem Laravel-13-Deployment

1. **Session auf JSON migrieren.** Empfehlung: gespeicherte Sessionwerte prüfen, geplanten Re-Login kommunizieren und danach `serialization=json` setzen. Nutzen: zusätzliche Deserialisierungshärtung. Alternative: `php` behalten. Rückbau: Konfiguration zurück; bereits invalidierte Sessions bleiben verloren.
2. **OAuth-Consent-UI entscheiden.** Empfehlung: nur bei tatsächlich benötigtem Authorization-Code- oder Device-Flow eine kleine anwendungseigene, sicherheitsgeprüfte View registrieren. Alternative: ausschließlich headless Password-/Client-Credentials-Flows nutzen. Rückbau: View-Bindings entfernen.
3. **Logout auf POST vereinheitlichen.** Empfehlung: im separaten Frontend-Upgrade den GET-Link durch einen CSRF-geschützten POST ersetzen und danach `logout.legacy` entfernen. Alternative: Legacy-Route beibehalten. Nutzen: Laravel-Standard und weniger CSRF-Risiko.
4. **Historische Passport-Hilfstabelle entfernen.** Empfehlung: erst nach Produktionsbeobachtung und Backup die ungenutzte `oauth_personal_access_clients`-Tabelle per eigener Migration entfernen. Alternative: harmlos bestehen lassen. Rückbau: Tabelle aus Backup/Migration wiederherstellen.
5. **Altes Standard-String-Limit bewerten.** Empfehlung: nach Schemavergleich `Schema::defaultStringLength(191)` in einem eigenen Schema-Baseline-Schritt entfernen. Alternative: für Fresh-/Bestandskonsistenz behalten. Rückbau: Providerzeile wiederherstellen.
6. **Aufgegebenes PDF-Metapaket ersetzen.** Empfehlung: PDF-Ausgaben charakterisieren und auf gepflegte direkte FPDI-/FPDF-Pakete wechseln. Alternative: Paket vorübergehend behalten; es hat aktuell kein Security-Advisory, aber keinen Maintainer.

### P2 getrennte Modernisierungen

- Intervention Image 2 auf eine aktuelle Hauptversion erst nach Bild-/EXIF-/Preview-Vertragstests migrieren.
- Frontend gemeinsam auf unterstützte Node-, Vue- und Build-Versionen heben; Webpack 4 läuft nicht nativ auf dem aktuellen Host-Node 26.
- PHPUnit 13 erst zusammen mit einer künftigen Laravel-Empfehlung und nach Prüfung aller Test-APIs bewerten.

## Verbleibende Risiken

Laravel 13 selbst und der gelockte PHP-Backend-Stand haben laut Composer keine bekannten Security-Advisories. Ein produktives Release bleibt dennoch bis zum stabilen Bible-Paket-Tag und einer erfolgreichen produktionsnahen Passport-/Backup-Probe gesperrt. Das unveränderte Legacy-Frontend und das aufgegebene PDF-Metapaket bleiben bekannte, getrennt zu bearbeitende Wartungsrisiken.

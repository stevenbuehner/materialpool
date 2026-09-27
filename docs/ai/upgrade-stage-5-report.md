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
| `league/flysystem-aws-s3-v3` | 3.35.3 | direkter Laravel-13-kompatibler Adapter ausschließlich für verschlüsselte Offsite-Backups |
| `stevenbuehner/bible-verse-bundle` | 3.0.0 | stabiler PHP-8.4-kompatibler Release; PHP-/JavaScript-Verträge erhalten |

`minimum-stability` ist `stable`. Das separat verwaltete `stevenbuehner/bible-verse-bundle` ist über `^3.0` auf den stabilen Tag `3.0.0` und Commit `c9757851ee69220293223728e1db525951e60da8` gelockt. Die frühere Commit-Referenz und ihre Stability-Ausnahme sind entfallen. Der PHP-/JavaScript-Exportvertrag des Pakets ist getestet.

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
| Password Grant | Als einzige Abweichung bei den aktivierten OAuth-Grants ausdrücklich aktiviert. Ein aus dem Altschema migrierter Client erhält mit unverändertem Credential ein Access- und Refresh-Token. Die bereits zuvor gesetzten Token-Laufzeiten, `CreateFreshApiToken` und der Cookie-Name bleiben aus Kompatibilitätsgründen bestehen und sind separat dokumentiert. |

## Weitere Paket- und Konventionsanpassungen

- Nanigans STI wurde vollständig durch Parental ersetzt. `Resource` hydratisiert alle zehn gespeicherten Typen; `File` begrenzt Queries weiterhin auf die sechs Dateitypen.
- Debugbar nutzt Provider-Autodiscovery statt manueller Registrierung eines alten Namespaces.
- Der überholte Passport-6-Aufruf `withoutCookieSerialization()` wurde entfernt, weil `false` in Passport 13 bereits der Paketdefault ist und getestet wird.
- Die beiden doppelten API-Routennamen heißen nun eindeutig `api.v1.materials.copy` und `api.v1.bundles.index`. Pfade und HTTP-Methoden bleiben unverändert.
- Laravel UI behält den Standardnamen `logout` für POST. Der für das unveränderte Vue-2-Frontend noch nötige GET-Pfad bleibt als `logout.legacy` erreichbar.
- PHPUnit verwendet das 12.5-Schema. Boost-1-Kompatibilitätszweige wurden vollständig entfernt.

## Ausgeführte Prüfungen

- Kaltstart mit `sail down` und `sail up -d` ohne Volume-Löschung: MySQL wurde gesund, Anwendung startete mit PHP 8.4.25; die vollständige Suite wurde anschließend erneut erfolgreich ausgeführt.
- `composer update --lock --no-interaction`: reproduzierbarer Lock-Stand, Paket-Discovery erfolgreich.
- `composer validate --strict`: gültig und ohne Warnung.
- `composer audit --locked`: keine Security-Advisories; Composer markiert `setasign/fpdi-fpdf` als aufgegebenes Metapaket ohne maschinenlesbaren Ersatz. Das Projekt selbst empfiehlt die direkten Abhängigkeiten `setasign/fpdi` und `setasign/fpdf`.
- PHP-Syntaxprüfung aller 29 geänderten beziehungsweise neuen PHP-Dateien im PHP-8.4-Container: bestanden.
- `php artisan migrate:fresh --env=testing --force`: vollständiges Fresh-Schema einschließlich Passport-13-Migrationen: bestanden.
- Befülltes Passport-Altschema: Secret-Hashing, Owner-/Grant-/Redirect-Übernahme, ID-/Tokenreferenz-Erhalt und echter Password-Grant-Tokenaustausch: bestanden.
- Vollständige PHPUnit-Suite: 156 Tests, 1.588 Assertions, bestanden.
- Nach Umstellung auf BibleVerseBundle `3.0.0`: gezielter Bundle-Vertrag mit 6 Tests/39 Assertions sowie die vollständige Suite mit 156 Tests/1.588 Assertions erneut bestanden.
- Nach Umsetzung des Produktionsvertrags und der Release-Regressionsprüfungen: gezielte Produktions-/Backup-/Scheduler-Gruppe mit 16 Tests und 98 Assertions sowie vollständige Suite mit 165 Tests und 1.641 Assertions bestanden.
- Der Commit-basierte Release-Build verwendet `npm ci --ignore-scripts`: Das ungenutzte PhantomJS-Postinstall aus der indirekten `svg-icon`-Kette besitzt kein Linux-arm64-Binary. Der danach verpflichtende Webpack-Produktionsbuild beweist, dass keine benötigte Buildstufe übersprungen wurde; Frontendquellen und Lockfile bleiben unverändert.
- Aus dem exakten Commit `80aa2d83c75c7c65c81e87b5d95fe503b91dbde8` wurde ein Release-Artefakt erzeugt. Äußere SHA-256-Datei und die deterministische Payload-Prüfsumme wurden nach Linux-Extraktion erfolgreich geprüft; `.env`, `node_modules` und `vendor` sind nicht enthalten, die gebauten JS-/CSS-Artefakte sind enthalten.
- Das extrahierte Release wurde im PHP-8.4-Container ausschließlich mit `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` installiert: 121 Laufzeitpakete, keine PHPUnit-Dev-Abhängigkeit, Package Discovery erfolgreich, `php artisan --version` meldet Laravel 13.31.0. Die redundante manuelle IDE-Helper-Registrierung wurde entfernt; im Entwicklungsbetrieb bleibt dessen Laravel-Package-Discovery maßgeblich.
- Der unveränderte Frontend-Lockstand meldet 129 npm-Audit-Funde insgesamt. `npm audit --omit=dev` ordnet 28 davon dem Production-Abhängigkeitsgraphen zu (10 low, 6 moderate, 10 high, 2 critical), darunter direkte Funde für `axios` und `dompurify`. Es wurde kein `npm audit fix` und kein Paket-Upgrade ausgeführt; Bewertung beziehungsweise Behebung braucht wegen möglicher Major-Wechsel eine eigene Frontend-Freigabe.
- AES-256-Backup wurde in der isolierten Testdisk mit Passwort geöffnet, entpackt und per SHA-256 gegen die Fixture geprüft. Der echte S3-Download-/Restore-Nachweis bleibt ein externes Go-live-Gate.
- Nginx-Konfiguration wurde mit `nginx -t` im offiziellen Nginx-Stable-Container erfolgreich geprüft; Shell-Syntax und fail-closed Produktions-Preflight wurden ebenfalls geprüft.
- Nestedset-Vertragsgruppe: alle Forest-, Boundary-, Depth-, Move-, Merge-, Delete-, Search-, Pivot- und JSON-Fälle innerhalb des Gesamtlaufs bestanden; `isBroken()` bleibt false.
- Handler-Gruppe separat: 7 Tests, 40 Assertions; gemeinsam mit Boost 10 Tests und 46 Assertions, bestanden.
- Backup 10: isoliertes Datei-Backup erzeugt, entpackt und per SHA-256 gegen die Fixture geprüft.
- Production-Webpack-Build: in isolierter Kopie im Sail-Image mit Node 16 erfolgreich; Repository-Ausgaben wurden nicht überschrieben.
- Nach Umstellung auf BibleVerseBundle `3.0.0` wurde dieser isolierte Production-Webpack-Build erneut erfolgreich ausgeführt; alle direkten Paketimporte werden aufgelöst.
- `route:list --json`: erfolgreich; Passport-Core-/Device-Routen vorhanden, alte JSON-Verwaltungsrouten abwesend.
- Frontend-Manifeste/-Quellen und reguläre Storage-Pfade zeigen keine Git-Änderungen. Alle Dateioperationstests verwenden Fakes; nur `testing` wurde migriert.

## Offene Punkte und Empfehlungen

### P0 vor produktivem Deployment

Der Repository-Teil ist im verbindlichen [`production-deployment-contract.md`](production-deployment-contract.md) umgesetzt: Healthcheck, expliziter Proxy-Trust, Supervisor-Worker, Scheduler, verschlüsseltes lokales/S3-Backup, Preflight, atomare Release-Skripte und Servervorlagen sind versioniert. Offen bleiben zwingend externe Abnahmen:

1. **Passport-Cutover proben.** Clientinventar, verifiziertes Backup, Wartungsfenster und den Ablauf aus `passport-13-client-migration.md` in einer produktionsnahen Kopie vollständig durchspielen.
2. **Zielruntime abnehmen.** Reale Domain, Proxy-CIDR, SSH-, SMTP- und S3-Werte bereitstellen; Ubuntu/Nginx/PHP-FPM 8.4/MySQL 8, Firewall, Rechte, Cron und Supervisor prüfen.
3. **Restore-Gate erfüllen.** Ein verschlüsseltes S3-Backup herunterladen, entschlüsseln und Datenbank samt repräsentativen persistenten Dateien isoliert wiederherstellen. Ohne protokollierten Erfolg bleibt Produktion blockiert.
4. **Frontend-Audit entscheiden.** Den dokumentierten Production-Audit-Befund am tatsächlichen Deployment-Lockstand prüfen und entweder ausdrücklich zeitlich begrenzt akzeptieren oder einen gesonderten Frontend-Security-Schritt freigeben. Automatische Major-Upgrades sind durch diesen Vertrag nicht autorisiert.

### P1 nach stabilem Laravel-13-Deployment

1. **Session auf JSON migrieren.** Empfehlung: gespeicherte Sessionwerte prüfen, geplanten Re-Login kommunizieren und danach `serialization=json` setzen. Nutzen: zusätzliche Deserialisierungshärtung. Alternative: `php` behalten. Rückbau: Konfiguration zurück; bereits invalidierte Sessions bleiben verloren.
2. **OAuth-Consent-UI entscheiden.** Erledigt mit drei kleinen, anwendungseigenen Bootstrap-Views für Authorization-Code-Consent sowie Device-Code-Eingabe und -Freigabe. Passport bindet sie über `Passport::viewPrefix('auth.oauth')`; die bestehenden OAuth-Routen, Grants und deaktivierten JSON-Verwaltungsrouten bleiben unverändert. Rückbau: View-Bindings und Views entfernen.
3. **Logout auf POST vereinheitlichen.** Empfehlung: im separaten Frontend-Upgrade den GET-Link durch einen CSRF-geschützten POST ersetzen und danach `logout.legacy` entfernen. Alternative: Legacy-Route beibehalten. Nutzen: Laravel-Standard und weniger CSRF-Risiko.
4. **Historische Passport-Hilfstabelle entfernen.** Erledigt per eigener Migration `2026_09_16_094238_drop_oauth_personal_access_clients_table`. Die historische Ursprungmigration bleibt unverändert. Der Rückbau stellt ausschließlich die Tabellenstruktur wieder her; vor einem Produktivlauf ist ein verifiziertes Datenbankbackup erforderlich, weil frühere Tabelleninhalte nur daraus wiederherstellbar sind.
5. **Altes Standard-String-Limit bewerten.** Erledigt: `Schema::defaultStringLength(191)` bleibt bewusst erhalten. MySQL 8 kann zwar auch den bereits vorhandenen indexierten `VARCHAR(255)`-Wert `resources.remote_path` tragen, die historischen Fresh-Migrationen erzeugen ihre indexierten Standard-Strings jedoch implizit mit 191 Zeichen und die bestehenden Tabellen verwenden diese Länge. Ein bloßes Entfernen im Provider würde damit Fresh- und Bestandsschema auseinanderlaufen lassen. Der Vertragstest erzeugt in der isolierten Testdatenbank eine indexierte Standard-String-Spalte und erwartet `VARCHAR(191)`. Alternative: alle betroffenen Bestands- und Ursprungsschemata in einem ausdrücklich freigegebenen, breiten Migrationsprojekt auf 255 Zeichen vereinheitlichen. Rückbau der jetzigen Entscheidung: Providerzeile entfernen und diesen Vertragstest auf den neuen, migrierten Zielzustand anpassen.
6. **PDF-Metapaket direkt deklarieren.** `setasign/fpdi-fpdf` ist ausschließlich ein aufgegebenes Composer-Metapaket; es enthält keine PDF-Implementierung. FPDI 2.6.8 und FPDF 1.9.0 funktionieren weiter und werden separat veröffentlicht. Empfehlung: das Metapaket entfernen und dieselben Bibliotheken direkt als `setasign/fpdi:^2.6` und `setasign/fpdf:^1.9` deklarieren. Die aktuelle statische Analyse findet keine eigenen FPDI-/FPDF-Aufrufstellen; vor und nach dem Wechsel sind dennoch Autoload, Lockfile, vollständige Suite und bei indirekter Nutzung repräsentative PDF-Artefakte zu prüfen. Alternative: Metapaket vorübergehend behalten; kein aktuelles Security-Advisory, aber dauerhafte Composer-Abandonment-Warnung. Details und Abnahmebedingungen stehen im Upgradevertrag unter „P1-PDF-Abhängigkeitsbereinigung“.

### P2 getrennte Modernisierungen

- Intervention Image 2 auf eine aktuelle Hauptversion erst nach Bild-/EXIF-/Preview-Vertragstests migrieren.
- Frontend gemeinsam auf unterstützte Node-, Vue- und Build-Versionen heben; Webpack 4 läuft nicht nativ auf dem aktuellen Host-Node 26.
- PHPUnit 13 erst zusammen mit einer künftigen Laravel-Empfehlung und nach Prüfung aller Test-APIs bewerten.

## Verbleibende Risiken

Laravel 13 selbst und der gelockte PHP-Backend-Stand haben laut Composer keine bekannten Security-Advisories. Der stabile Bible-Paket-Tag ist eingebunden und kein Blocker mehr. Die Produktionsartefakte sind vorbereitet; ein produktives Release bleibt dennoch bis zu realen Betriebswerten, abgenommener Zielruntime und erfolgreicher Passport-/S3-Backup-/Restore-Probe gesperrt. Das unveränderte Legacy-Frontend hat nachweislich auch Funde im Production-Abhängigkeitsgraphen und benötigt vor Go-live eine ausdrückliche Risikofreigabe oder einen separat freigegebenen Security-Schritt. Daneben bleibt ausschließlich das aufgegebene FPDI-/FPDF-Metapaket ein Wartungsrisiko; die eigentlichen FPDI-/FPDF-Bibliotheken bleiben funktionsfähig und gepflegt.

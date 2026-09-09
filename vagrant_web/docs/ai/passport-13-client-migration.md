# Passport 13: Client- und Betriebsumstellung

## Zielbild

Materialpool verwendet ab Laravel 13 Laravel Passport 13 möglichst ohne Abweichung von dessen Defaults:

- neue OAuth-Clients erhalten UUIDs;
- Client-Secrets liegen ausschließlich gehasht in `oauth_clients.secret`;
- `oauth_clients` verwendet `owner_type`/`owner_id`, JSON-basierte `redirect_uris` und `grant_types`;
- die Device-Code-Tabelle und die offiziellen Device-Routen sind vorhanden;
- die veralteten JSON-Verwaltungsrouten für Clients und Tokens bleiben deaktiviert;
- Passport bleibt headless und bringt keine anwendungseigene Authorization- oder Device-View mit.

Die einzige bewusste Abweichung bei den aktivierten OAuth-Grants ist `Passport::enablePasswordGrant()`. Der externe Material Grabber benötigt diesen OAuth-Flow weiterhin. Zusätzlich bleiben aus Kompatibilitätsgründen die bereits zuvor gesetzten Token-Laufzeiten (Access Token fünf Tage, Refresh Token 30 Tage, Personal Access Token sechs Monate), `CreateFreshApiToken` und der Cookie-Name `materialpool_token` erhalten. Diese Einstellungen sind keine neu eingeführten Abweichungen des Upgrades; ihre spätere Annäherung an Passport-Defaults wäre jedoch eine eigene Client-/Session-Entscheidung. Grant, Laufzeiten und Cookie-Integration sind durch Authentifizierungs- und Routentests abgedeckt.

## Auswirkungen auf vorhandene und neue Clients

Bestehende numerische Client-IDs werden wertgleich als Zeichenketten in die neue 36-stellige Spalte übernommen. Zugehörige Einträge in `oauth_access_tokens` und `oauth_auth_codes` behalten denselben Wert. Neue Clients erhalten dagegen eine UUID; Clientsoftware darf deshalb keine Ganzzahl-ID mehr voraussetzen.

Das Klartext-Secret eines bestehenden Clients ändert sich nicht. Die Migration ersetzt lediglich dessen Datenbankdarstellung durch einen Hash. Ein Secret kann danach nicht mehr aus der Datenbank ausgelesen werden. Es muss wie ein Passwort behandelt und clientseitig sicher verwahrt werden.

Neue vertrauliche Clients geben ihr Klartext-Secret nur beim Anlegen aus. `ApiKeysSeeder` verwendet dafür die Passport-13-API und zeigt das zufällig erzeugte Secret einmalig im ausführenden Terminal. Der frühere fest codierte Schlüssel ist entfernt. Alternativ und bevorzugt wird ein Client interaktiv mit dem offiziellen Befehl angelegt:

```bash
php artisan passport:client --password
```

Die bisherigen Endpunkte `oauth/clients`, `oauth/personal-access-tokens` und `oauth/tokens` werden von Passport 13 standardmäßig nicht mehr registriert. Clients müssen ihre Verwaltung auf einen kontrollierten Betriebsprozess oder eine später bewusst entworfene eigene API umstellen. `Passport::$registersJsonApiRoutes` wird nicht aktiviert.

Authorization-Code- und Device-Flows benötigen bei Passport 13 eine anwendungseigene Zustimmungsoberfläche. Materialpool registriert im Backend-Upgrade absichtlich keine neue View, weil dies eine separate UI- und Sicherheitsentscheidung wäre. Bis zu einem solchen Auftrag dürfen Clients nur die tatsächlich eingerichteten headless Flows verwenden; insbesondere bleibt der bestehende Password Grant verfügbar.

## Verbindlicher Deployment-Ablauf

1. Alle betroffenen Clients und deren Verantwortliche inventarisieren. Vor allem Annahmen über numerische IDs, die drei entfernten JSON-Endpunkte und browserbasierte Authorization-Views prüfen.
2. Direkt vor der Umstellung ein konsistentes Datenbank-Backup erstellen und dessen Wiederherstellbarkeit in einer isolierten Umgebung prüfen.
3. Anwendung in Wartung nehmen und Queue-Worker kontrolliert beenden. Zwischen dem Composer-Update und der Migration darf wegen des neuen Secret-Hashings kein OAuth-Traffic stattfinden.
4. Laravel-13-Code und den geprüften Composer-Lock-Stand ausrollen.
5. `php artisan migrate --force` ausführen. Die Migration hasht bestehende Klartext-Secrets, überführt das Client-Schema und passt referenzierende Passport-Spalten an.
6. Konfigurations- und Route-Caches neu aufbauen; Queue-Worker neu starten.
7. Für mindestens einen vorhandenen Password-Grant-Client den Token-Austausch mit dessen unverändertem Klartext-Secret prüfen. Danach einen neuen UUID-Client anlegen und ebenfalls prüfen.
8. Erst danach Wartungsmodus beenden. Datenbankwerte oder Secrets werden dabei weder protokolliert noch in Tickets oder Commits übernommen.

`passport:hash` muss nach dieser Anwendungsmigration nicht zusätzlich ausgeführt werden. Der Befehl bleibt als idempotente Diagnose beziehungsweise für Sonderfälle verfügbar; ein vorheriger separater Lauf würde den Wartungszeitraum verlängern, ohne den Endzustand zu verändern.

## Rückbau

Ein vollständiger Rückbau auf Passport 12 erfolgt ausschließlich durch Wiederherstellung des unmittelbar vor der Umstellung erstellten Datenbank-Backups zusammen mit dem Laravel-12-Code. Gehashte Secrets lassen sich nicht in Klartext zurückverwandeln. Sobald UUID-Clients existieren, lassen sich außerdem deren IDs nicht verlustlos in das alte Integer-Schema überführen.

Die Migration besitzt nur für den technischen Schemafall ohne UUID-Clients einen `down()`-Pfad. Dieser stellt Spalten zurück, kann Secret-Hashes aber naturgemäß nicht rückgängig machen und ersetzt daher kein Backup.

## Schnelle Client-Checkliste

- Client-ID als opaque String behandeln, nicht als Integer.
- Vorhandenes Secret unverändert weiternutzen; nicht aus der Datenbank auszulesen versuchen.
- Kein Aufruf der entfernten JSON-Verwaltungsendpunkte.
- Keine Annahme einer von Passport bereitgestellten HTML-Zustimmungsseite.
- Password Grant nur für den bestehenden, kontrollierten Material-Grabber-Anwendungsfall verwenden.
- Bei neuen Clients das einmalig ausgegebene Secret unmittelbar im vorgesehenen Secret Store sichern.

Grundlage: offizieller [Passport-13-Upgradeleitfaden](https://github.com/laravel/passport/blob/13.x/UPGRADE.md), erneut geprüft am 9. September 2026.

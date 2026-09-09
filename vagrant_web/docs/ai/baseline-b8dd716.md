# Verifizierte technische Basis: `28f4ec4`

## Verbindliche Referenz

Die Backend-Basis dieser KI-Dokumentation wurde gegen den Git-Commit `28f4ec452c1a4446a361ae21dd40d99d6fee8a11` geprüft:

```text
upgrade: Laravel 13 und Passport 13 abschließen
```

Dieser Commit ist der grüne Laravel-13-/Passport-13-Implementierungsstand auf `master`. Der historische Dateiname `baseline-b8dd716.md` bleibt bestehen, damit vorhandene Links stabil bleiben. Die ursprüngliche Vue-2-Ausgangsbasis `b8dd716931de60f5cf60936b20d1233847afa831` (Tag `1.2.1`) bleibt für den Vergleich relevant; `develop` und `Vue3_Upgrade` wurden weiterhin nicht als Quelle übernommen.

## Verifizierte Aussagen

| Aussage der KI-Dokumentation | Ergebnis am Commit `28f4ec4` | Nachweis im Bestand |
| --- | --- | --- |
| Backend basiert auf PHP 8.4 und Laravel 13 | bestätigt | `composer.json`: `php ^8.4`, `laravel/framework ^13.0`; Laufzeitprüfung im Sail-Container: PHP 8.4.25, Laravel 13.31.0 |
| API-Authentifizierung nutzt Passport 13 | bestätigt | `laravel/passport 13.8.0`, `config/auth.php`, `AuthServiceProvider`, Passport-Vertragstests |
| Passport verwendet das neue Client-Schema | bestätigt | UUID-fähige IDs, gehashte Secrets, Owner-/Redirect-/Grant-Felder und Device Codes; Migration und echter Password-Grant-Austausch getestet |
| Frontend bleibt Vue 2 mit Vue Router 3 und Vuex 3 | bestätigt | Frontend-Quellen, `package.json` und `package-lock.json` wurden im Backend-Upgrade nicht geändert |
| Build bleibt Webpack 4 und Bootstrap 4/Bootstrap-Vue | bestätigt | isolierter Production-Build aus dem bestehenden Lock-Stand erfolgreich |
| SPA-Einstieg liegt unter `/vue` und ist geschützt | bestätigt | `routes/web.php`, `resources/js/apps/main/index.js` |
| APIs führen v1 und v2 | bestätigt | `routes/api.php`; Pfade und HTTP-Methoden blieben erhalten |
| Resources verwenden Parental-STI mit unveränderten `type`-Werten | bestätigt | `app/Models/Resource.php`, `app/Models/File.php`, STI-Vertragstests |
| Keywords verwenden Nestedset 7 mit unverändertem Baumvertrag | bestätigt | `kalnoy/nestedset ^7.0`; Forest-, Move-, Merge-, Delete-, Search-, Pivot- und JSON-Tests |
| Material-Relationen führen fachliche Pivotdaten | bestätigt | Vertragstests für `limitation` und `relevance` |
| Events/Listener verwalten Hashes, Metadaten und Vorschau-Caches | bestätigt | Handler-/Resource-/Queue-Tests |
| Bundles verwenden individuelle Queues | bestätigt | Bundle-Vertragstests für `bundle_{id}_queue` |
| Persistente Disks für Ressourcen, Archive und Bundles existieren | bestätigt | `config/filesystems.php`; Tests verwenden Fakes oder isolierte Test-Disks |
| Tests basieren auf PHPUnit 12 und der Laravel-Testumgebung | bestätigt | PHPUnit 12.5.35; 156 Tests und 1.588 Assertions gegen MySQL `testing` grün |

Die nachgelagerte Produktionsvertrags-Etappe ergänzt neun Tests; der aktuelle ausführbare Stand umfasst 165 Tests und 1.612 Assertions. Die ursprüngliche Laravel-13-Basiszahl bleibt in der Tabelle als historischer Nachweis erhalten.

## Bewusste Kompatibilitätsentscheidungen

- Passport folgt bei UUIDs, Secret-Hashing, Client-Schema, Device Codes, Headless-Betrieb und deaktivierter Legacy-JSON-API den Version-13-Defaults.
- Als einzige Abweichung bei den aktivierten OAuth-Grants bleibt der für den Material Grabber benötigte Password Grant aktiv. Bestehende Token-Laufzeiten, `CreateFreshApiToken` und der Cookie-Name `materialpool_token` bleiben ebenfalls als zuvor vorhandene Kompatibilitätskonfiguration erhalten.
- Session-Serialisierung bleibt vorerst `php`; Cache-Serialisierung und bestehende Prefixes sind explizit abgesichert.
- Die klassische Laravel-Anwendungsstruktur mit Kerneln, Providern und Konfigurationsdateien bleibt erhalten. Innerhalb dieser Struktur werden aktuelle Laravel-13-Konventionen bevorzugt, sofern sie vollständig und verhaltensneutral übernommen werden können.
- `stevenbuehner/bible-verse-bundle` ist über den stabilen Constraint `^3.0` auf Release `3.0.0` (Commit `c9757851ee69220293223728e1db525951e60da8`) gelockt. Die frühere Dev-/Commit-Ausnahme ist vollständig entfallen.
- Das Frontend bleibt bewusst auf Vue 2/Laravel Mix. Der erfolgreiche isolierte Build ändert nichts daran, dass die Modernisierung ein separates Projekt ist.

## Verifikationsgrenze und Deployment-Status

Die vollständige Suite, Fresh-Migration, befüllte Passport-Altschema-Migration, echter Tokenaustausch, isoliertes verschlüsseltes Backup, Handler, Nested Sets, STI, Routen, Composer-Validierung und Security-Audit sind grün. Der Repository-Endstand ist technisch abgenommen, aber noch kein produktiver Rollout. Der verbindliche Betriebsablauf steht in [`production-deployment-contract.md`](production-deployment-contract.md):

- Der Passport-Cutover muss mit Clientinventar, verifiziertem Datenbankbackup, Wartungsfenster und Restore-Probe produktionsnah geprobt werden.
- Die produktive Runtime muss PHP 8.4.x, die benötigten Erweiterungen, Composer 2 und MySQL 8 in der geprüften Semantik bereitstellen.
- `setasign/fpdi-fpdf` ist ausschließlich ein aufgegebenes Composer-Metapaket, aktuell ohne bekanntes Security-Advisory. FPDI 2.6.8 und FPDF 1.9.0 bleiben funktionsfähig und separat gepflegt; die spätere Bereinigung besteht nur aus ihrer direkten Composer-Deklaration und ist im Laravel-13-Vertrag festgelegt.
- Das Host-PHP 8.0 und Node 26 sind keine gültige Backend-/Legacy-Frontend-Referenz. Backendprüfungen laufen über Sail/PHP 8.4; der Legacy-Build wurde isoliert mit Node 16 ausgeführt.

Details stehen in `docs/ai/upgrade-stage-5-report.md` und `docs/ai/passport-13-client-migration.md`.

## Pflege bei künftigen Änderungen

Wenn die technische Basis bewusst geändert wird, sind diese Baseline, `architecture.md`, `design-system.md`, `quality-gates.md` und `AGENTS.md` gemeinsam zu prüfen. Bis zu einer ausdrücklich freigegebenen Vue-3-/Build-Migration gilt Vue 2 als verbindlicher Frontendstandard. Passport-, Datenbank-, Session-, Storage- und Nested-Set-Verträge bleiben entscheidungspflichtig.

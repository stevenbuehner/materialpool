# Verifizierte Ausgangsbasis: `b8dd716`

## Verbindliche Referenz

Diese KI-Dokumentation wurde gegen den Git-Commit `b8dd716931de60f5cf60936b20d1233847afa831` geprüft:

```text
Fix: Wenn mehr als drei Leerzeichen in der ersten Zeile in einem Tag sind,
erkenne die ganze Zeile nicht als Valide Texterkennung an
```

Zum Prüfzeitpunkt zeigt `master` genau auf diesen Commit (Tag `1.2.1`). Dieser Commit ist die Ausgangsbasis für alle Aussagen in `AGENTS.md` und `docs/ai/`. Der Branch `develop` sowie `Vue3_Upgrade` wurden absichtlich nicht als Quelle ausgewertet, weil sie einen nicht übernommenen Vue-3-Upgradeversuch enthalten.

## Verifizierte Aussagen

| Aussage der KI-Dokumentation | Ergebnis am Commit `b8dd716` | Nachweis im Bestand |
| --- | --- | --- |
| Backend basiert auf PHP 7.4 und Laravel 8 | bestätigt | `composer.json`: `php ^7.4`, `laravel/framework ^8.0` |
| API-Authentifizierung nutzt Laravel Passport | bestätigt | `composer.json`: `laravel/passport ^10.0`; `config/auth.php`; API-Routen |
| Frontend ist Vue 2 mit Vue Router 3 und Vuex 3 | bestätigt | `package.json`: Vue `^2.6.14`, Router `^3.5.3`, Vuex `^3.6.2` |
| Build nutzt Webpack 4 und Bootstrap 4/Bootstrap-Vue | bestätigt | `package.json`, `resources/sass/main.scss` |
| SPA-Einstieg liegt unter `/vue` und ist geschützt | bestätigt | `routes/web.php`, `resources/js/apps/main/index.js` |
| APIs führen v1 und v2 | bestätigt | `routes/api.php` |
| Resources verwenden Single-Table-Inheritance mit `type` | bestätigt | `app/Models/Resource.php`, `app/Models/File.php` |
| Keywords sind ein Nested-Set-Baum mit vier Typen | bestätigt | `app/Models/Keyword.php` |
| Material-Relationen führen fachliche Pivotdaten | bestätigt | `app/Models/Material.php`: `limitation` und `relevance` |
| Events/Listener verwalten Hashes, Metadaten und Vorschau-Caches | bestätigt | `app/Providers/EventServiceProvider.php` |
| Bundles verwenden individuelle Queues | bestätigt | `app/Services/Bundles/BundleQueueService.php`, `BundleImportController.php` |
| Persistente Disks für Ressourcen, Archive und Bundles existieren | bestätigt | `config/filesystems.php` |
| Tests basieren auf PHPUnit 9 und Laravel-Testumgebung | bestätigt | `composer.json`, `phpunit.xml`, `tests/` |

## Bewusst nicht behauptet

- Die Dokumentation behauptet keine lokale Laufzeitfähigkeit: DB-Zugang, installierte Abhängigkeiten, Docker-Verfügbarkeit und externe Medienprogramme hängen von der konkreten Umgebung ab.
- Sie schreibt keine ungeprüften Aussagen aus `develop` oder `Vue3_Upgrade` in die Vue-2-Architektur fort.
- Sie ersetzt keine fachliche Freigabe für bestehende, nur teilweise getestete Altprozesse.

## Pflege bei künftigen Änderungen

Wenn die technische Basis bewusst geändert wird (z. B. PHP-, Laravel-, Vue- oder Build-Upgrade), muss zuerst diese Datei, dann `architecture.md`, `design-system.md`, `quality-gates.md` und schließlich `AGENTS.md` aktualisiert werden. Bis zu einer ausdrücklich freigegebenen Vue-3-Migration gilt Vue 2 als verbindlicher Standard.

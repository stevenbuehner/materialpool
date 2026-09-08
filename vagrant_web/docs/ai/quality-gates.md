# Prüf- und Übergaberegeln

## Sichere Standardprüfungen

Die Reihenfolge ist bewusst risikobewusst. Nur Befehle ausführen, deren lokale Voraussetzungen vorhanden sind; niemals aus einer Prüfung heraus Datenbank oder gespeicherte Ressourcen löschen.

```sh
# PHP-Test-Suite (benötigt konfigurierte Testdatenbank)
php artisan test

# Alternativ die vorhandene PHPUnit-Suite
./vendor/bin/phpunit

# Produktions-Build für Vue/Sass/Übersetzungen
npm run build
```

Vor gezielten PHP-Änderungen sind mindestens Syntaxprüfung und die passende Testklasse auszuführen. Bei einer vorhandenen Docker-/Sail-Umgebung können Befehle darin laufen; erst Konfiguration und Datenbankzustand prüfen.

## Bereichsspezifische Gates

| Änderung | Zusätzlich prüfen |
| --- | --- |
| Modell, Service, Controller | passende Feature-/Unit-Tests, Validierung, Fehlerpfade |
| API oder Policy | Authentifiziert/unauthentifiziert, erlaubte/verbotene Rolle, Payload-Kompatibilität |
| Resource/Datei/Vorschau | repräsentativer Dateityp, Storage-Disk, Eventfolge, Cache-Invalidation, keine verlorenen Originale |
| Material-/Keyword-/Bibleverse-Relation | Pivotdaten (insb. `relevance`/`limitation`), Bereinigung verwaister Datensätze, UI-Darstellung |
| Bundle/Queue | Queue-Name, Job-Reihenfolge, Wiederholbarkeit, Fehlerbehandlung; nur mit Test- oder ausdrücklich freigegebenen Daten |
| Vue/Sass | `npm run build`, Desktop- und Mobile-Ansicht, Lade-/Fehler-/Leerezustand, Tastaturzugang |
| Migration/Dependency/Infra | vorherige Freigabe, Up-/Down-Plan, Aktualisierungsnotiz, vollständige passende Tests |

## Abschlussbericht

Jede Umsetzung endet mit:

1. Was geändert wurde und warum.
2. Welche Domänen-/Designauswirkungen geprüft wurden.
3. Ausgeführte Befehle mit Ergebnis.
4. Nicht ausgeführte Prüfungen mit Grund.
5. Verbleibende Risiken, Annahmen und gegebenenfalls Freigabebedarf.

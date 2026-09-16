# Prüf- und Übergaberegeln

## Sichere Standardprüfungen

Die Reihenfolge ist bewusst risikobewusst. Backendprüfungen laufen im Sail-PHP-8.4-Container gegen die dedizierte MySQL-Datenbank `testing`; niemals die Entwicklungs- oder Produktionsdatenbank verwenden. Datei- und Backuptests müssen gefakte oder isolierte Test-Disks verwenden.

```sh
# Container und Versionen prüfen
./vendor/bin/sail up -d
./vendor/bin/sail php -v
./vendor/bin/sail artisan --version

# Aufgelöste Verbindung prüfen; ohne explizite Werte fällt --env=testing bei
# fehlender .env.testing auf die Entwicklungsdatenbank zurück.
./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan config:show database --env=testing

# Frisches Testschema und Seeder ausschließlich mit denselben expliziten Werten
./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan migrate:fresh --env=testing --force
./vendor/bin/sail exec -e APP_ENV=testing -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=testing laravel.test php artisan db:seed --env=testing --force
./vendor/bin/sail test

# Composer-Verträge
./vendor/bin/sail composer validate --strict
./vendor/bin/sail composer audit --locked

# Frontend-Gates in der durch package.json/.node-version festgelegten Node-Laufzeit
npm ci --ignore-scripts
npm run test:ci
npm run inventory:frontend
npm run inventory:dependencies > frontend-sbom.cdx.json
npm run build
npm run test:e2e
npm run test:visual
```

Das ESLint-Gate umfasst Anwendungs-, Skript-, Unit- und Browsertestcode und
akzeptiert weder Fehler noch Warnungen (`--max-warnings=0`). Neue Befunde sind
vor dem Merge im verursachenden Arbeitspaket zu beheben.

Die Playwright-Befehle starten lokal selbstständig einen PHP-Server auf
`127.0.0.1:8000`. Mit `PLAYWRIGHT_BASE_URL` prüfen sie stattdessen eine bereits
gestartete Testumgebung. WebKit benötigt unter macOS einen Prozesskontext mit
AppKit-/Mach-Port-Zugriff; ein `Abort trap: 6` bei `RegisterApplication` weist
auf eine zu restriktive Ausführungssandbox und nicht auf einen fehlerhaften
Browser-Download hin. Die Browserfixtures ersetzen externe Fonts und
dynamische Debugbar-Zeitwerte, damit Bildvergleiche reproduzierbar bleiben.

Vor gezielten PHP-Änderungen sind mindestens die Syntaxprüfung im PHP-8.4-Container und die passende Testklasse auszuführen. Ein lokales Host-PHP unter 8.4 darf Composer oder Artisan für diese Anwendung nicht ausführen.

`db:seed` ist wegen `ClearAllTablesSeeder` destruktiv und darf ausschließlich nach Kontrolle von `APP_ENV=testing` und der aufgelösten Verbindung gegen die dedizierte, entbehrliche Testdatenbank laufen. `--env=testing` allein ist ohne `.env.testing` **kein** Isolationsnachweis; deshalb verwenden die Befehle oben zusätzlich explizite Container-Variablen. Ein erfolgreicher Exit-Code wird durch read-only Assertions auf repräsentative Benutzer-, OAuth-, Resource-, Material-, Keyword- und Bibeldaten ergänzt. Seed-Ausgaben mit einmaligen Test-Client-Secrets dürfen nicht in Logs, Screenshots oder Commits übernommen werden. Für die Vue-3-Migration ist dieses Gate mindestens an der Vue-2-Ausgangsbasis und vor der Releasefreigabe verbindlich; ist Sail/MySQL nicht verfügbar, bleibt es offen.

## Bereichsspezifische Gates

| Änderung | Zusätzlich prüfen |
| --- | --- |
| Modell, Service, Controller | passende Feature-/Unit-Tests, Validierung, Fehlerpfade |
| API oder Policy | Authentifiziert/unauthentifiziert, erlaubte/verbotene Rolle, Payload-Kompatibilität |
| Resource/Datei/Vorschau | repräsentativer Dateityp, Storage-Disk, Eventfolge, Cache-Invalidation, keine verlorenen Originale |
| Material-/Keyword-/Bibleverse-Relation | Pivotdaten (insb. `relevance`/`limitation`), Bereinigung verwaister Datensätze, UI-Darstellung |
| Bundle/Queue | Queue-Name, Job-Reihenfolge, Wiederholbarkeit, Fehlerbehandlung; nur mit Test- oder ausdrücklich freigegebenen Daten |
| Vue/Sass | `npm run build`, Desktop- und Mobile-Ansicht, Lade-/Fehler-/Leerezustand, Tastaturzugang |
| Vue-/Sass-/Frontend-Dependency | zusätzlich die Funktions-, E2E-, Visual-, Accessibility-, Security-, Lockfile- und Rückbaugates aus [`vue-3-migration-contract.md`](vue-3-migration-contract.md) sowie vorhandene komponentenspezifische Verträge |
| Seeder/Testdaten | frisches isoliertes Testschema, erfolgreicher `db:seed`-Lauf, repräsentative Datenassertions, keine echten Daten oder persistierten Secrets; Änderungen an Lösch- oder Fachsemantik nur nach Freigabe |
| Migration/Dependency/Infra | vorherige Freigabe, offizieller Online-Upgradeleitfaden, Up-/Down-Plan, Aktualisierungsnotiz, Fresh- und Bestandsschema, vollständige passende Tests |
| Passport/OAuth | Client-ID als String/UUID, Secret-Hashing, Referenzerhalt, Grant-/Redirect-Migration, Auth-Fehlerfälle und echter Tokenaustausch; vor Rollout den Ablauf in `passport-13-client-migration.md` |
| Nested Sets | vollständige `KeywordNestedSetTest`- und `KeywordApiControllerTest`-Gruppen; nach Mutationen `countErrors()` und `isBroken()` |
| Resource-STI | alle gespeicherten Resource- und File-Typcodes: Erzeugung, Hydrierung, Relation, Scope und JSON |
| Backup | Erzeugung und Restore-/Inhaltsprüfung ausschließlich in isolierter Umgebung; niemals produktive Ziele bereinigen |
| Produktion/Deployment | zusätzlich alle Gates, externen Werte, Restore-Nachweise und Rollbackschritte aus [`production-deployment-contract.md`](production-deployment-contract.md); ohne echten S3-Restore kein Go-live |

## Produktionsartefakte

Repository-seitig sind vor einem Release mindestens zu prüfen:

```sh
bash -n ops/production/*.sh
./vendor/bin/sail artisan test tests/Feature/UpgradeBaseline/ProductionDeploymentContractTest.php
./vendor/bin/sail artisan production:preflight --configuration-only
```

Der letzte Befehl muss mit der lokalen Testkonfiguration bewusst fehlschlagen; erfolgreich sein darf er erst mit vollständigen Produktionswerten. Auf einer produktionsnahen Zielplattform folgen `production:preflight` ohne Ausnahme, `nginx -t`, `php-fpm8.4 -t`, Supervisor-/Cron-/systemd-/Rechte-/Firewall-Prüfung sowie der verschlüsselte S3-Download-/Restore-Test.

## Laravel-13-Referenzgates

Der verifizierte Laravel-13-Stand umfasst mindestens:

```sh
./vendor/bin/sail test
./vendor/bin/sail test --filter KeywordNestedSetTest
./vendor/bin/sail test --filter KeywordApiControllerTest
./vendor/bin/sail test --filter Passport13MigrationContractTest
./vendor/bin/sail test --filter BackupExecutionContractTest
./vendor/bin/sail artisan route:list --json
```

Die vollständige Referenz am Implementierungscommit `28f4ec4` lautet 156 Tests mit 1.588 Assertions. Eine niedrigere Zahl ist zu erklären; Tests dürfen bei Paket- oder Frameworkänderungen nicht stillschweigend entfallen oder abgeschwächt werden.

Nach Ergänzung des Produktionsvertrags und der Vue-3-Release-Regressionsprüfungen lautet die aktuelle Untergrenze 170 Tests mit 1.658 Assertions. Die historische Zahl bleibt zur Einordnung des reinen Laravel-13-Checkpoints dokumentiert.

## Abschlussbericht

Jede Umsetzung endet mit:

1. Was geändert wurde und warum.
2. Welche Domänen-/Designauswirkungen geprüft wurden.
3. Ausgeführte Befehle mit Ergebnis.
4. Nicht ausgeführte Prüfungen mit Grund.
5. Verbleibende Risiken, Annahmen und gegebenenfalls Freigabebedarf.

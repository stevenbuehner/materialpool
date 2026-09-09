# Prüf- und Übergaberegeln

## Sichere Standardprüfungen

Die Reihenfolge ist bewusst risikobewusst. Backendprüfungen laufen im Sail-PHP-8.4-Container gegen die dedizierte MySQL-Datenbank `testing`; niemals die Entwicklungs- oder Produktionsdatenbank verwenden. Datei- und Backuptests müssen gefakte oder isolierte Test-Disks verwenden.

```sh
# Container und Versionen prüfen
./vendor/bin/sail up -d
./vendor/bin/sail php -v
./vendor/bin/sail artisan --version

# Frisches Testschema und vollständige PHPUnit-Suite
./vendor/bin/sail artisan migrate:fresh --env=testing --force
./vendor/bin/sail test

# Composer-Verträge
./vendor/bin/sail composer validate --strict
./vendor/bin/sail composer audit --locked

# Produktions-Build für Vue/Sass/Übersetzungen; bis zum Frontend-Upgrade
# nur in der dokumentierten isolierten Node-16-Umgebung ausführen
npm run build
```

Vor gezielten PHP-Änderungen sind mindestens die Syntaxprüfung im PHP-8.4-Container und die passende Testklasse auszuführen. Ein lokales Host-PHP unter 8.4 darf Composer oder Artisan für diese Anwendung nicht ausführen.

## Bereichsspezifische Gates

| Änderung | Zusätzlich prüfen |
| --- | --- |
| Modell, Service, Controller | passende Feature-/Unit-Tests, Validierung, Fehlerpfade |
| API oder Policy | Authentifiziert/unauthentifiziert, erlaubte/verbotene Rolle, Payload-Kompatibilität |
| Resource/Datei/Vorschau | repräsentativer Dateityp, Storage-Disk, Eventfolge, Cache-Invalidation, keine verlorenen Originale |
| Material-/Keyword-/Bibleverse-Relation | Pivotdaten (insb. `relevance`/`limitation`), Bereinigung verwaister Datensätze, UI-Darstellung |
| Bundle/Queue | Queue-Name, Job-Reihenfolge, Wiederholbarkeit, Fehlerbehandlung; nur mit Test- oder ausdrücklich freigegebenen Daten |
| Vue/Sass | `npm run build`, Desktop- und Mobile-Ansicht, Lade-/Fehler-/Leerezustand, Tastaturzugang |
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

Nach Ergänzung des Produktionsvertrags und der Release-Regressionsprüfungen lautet die aktuelle Untergrenze 165 Tests mit 1.641 Assertions. Die historische Zahl bleibt zur Einordnung des reinen Laravel-13-Checkpoints dokumentiert.

## Abschlussbericht

Jede Umsetzung endet mit:

1. Was geändert wurde und warum.
2. Welche Domänen-/Designauswirkungen geprüft wurden.
3. Ausgeführte Befehle mit Ergebnis.
4. Nicht ausgeführte Prüfungen mit Grund.
5. Verbleibende Risiken, Annahmen und gegebenenfalls Freigabebedarf.

# Verbindlicher Upgradevertrag: Laravel 8 auf Laravel 13

## Status und Auftrag

Dieser Vertrag ist die verbindliche Arbeitsgrundlage für P1. Ziel ist Laravel 13 auf PHP 8.4 mit aktualisierten Composer-Abhängigkeiten. Die Umstellung erfolgt in überprüfbaren Hauptversionsschritten. Das bestehende Vue-2-Frontend wird nicht modernisiert und seine Paketdefinitionen, sein Buildsystem sowie sein sichtbares Verhalten bleiben unangetastet, soweit eine Backend-Kompatibilitätsanpassung dies nicht zwingend erfordert.

**Vertragsrevision:** 9. September 2026. Der Vertrag wurde gegen die zu diesem Zeitpunkt online verfügbaren offiziellen Laravel-Upgradeleitfäden 9.x bis 13.x sowie den offiziellen Passport-Upgradeleitfaden geprüft. Die Online-Prüfung ist ab dieser Revision selbst Bestandteil jedes weiteren Hauptversionsschritts.

Der Vertrag autorisiert die nachfolgend ausdrücklich beschriebenen Paket- und Laufzeitänderungen. Ein Upgrade ist niemals eine stillschweigende Freigabe, bestehendes Verhalten zu ändern. Der Vertrag autorisiert insbesondere keine Änderung fachlicher oder technischer Verhaltensverträge, produktiver Daten, Datenbankstrukturen, Berechtigungen, API-Payloads, Speicherorte oder Frontendgestaltung. Wird eine solche Änderung technisch unvermeidbar oder erscheint eine Änderung gegenüber dem Ist-Verhalten sinnvoll, endet die Umsetzungsautorisierung an dieser Stelle und es ist vorab eine neue Entscheidung nach `docs/ai/decision-template.md` einzuholen.

## Verbindliche Zielentscheidungen

| Thema | Verbindliche Entscheidung | Begründung und Begrenzung |
| --- | --- | --- |
| PHP | Zielversion PHP 8.4 | Laravel 13 unterstützt PHP 8.3 bis 8.5; PHP 8.4 erfüllt zugleich die Anforderung von `spatie/laravel-backup` 10 und bietet mehr Bibliotheksreserve als 8.3. |
| Laravel | Laravel 9 → 10 → 11 → 12 → 13 | Jeder Hauptversionsschritt erhält einen eigenen grünen Prüfpunkt. Zwischenstände sind technische Checkpoints, keine produktiven Releases. |
| EOL-Zwischenstände | Laravel 9 bis 11 werden ausschließlich als lokale, nicht deploybare Kompatibilitäts-Checkpoints verwendet | Für diese historischen Hauptversionen werden die jeweils letzten stabilen Releases verwendet. Versionsbedingt unvermeidbare Security-Advisories werden dokumentiert, nicht verschwiegen und nicht durch bewegliche Dev-Stände umgangen. Sie dürfen nur für den kurzen lokalen Prüfpfad toleriert werden. |
| Anwendungsstruktur | Bestehende klassische Laravel-Struktur beibehalten | Die Anwendung wird nicht auf die schlanke Laravel-11+-Skeleton-Struktur umgebaut. Provider, Kernel und Konfigurationsdateien bleiben explizit. |
| OAuth | Laravel Passport beibehalten und auf Version 13 aktualisieren | Kein Wechsel zu Sanctum; OAuth-Clients, Tokens, Scopes, Keys, Guards und Statuscodes bleiben kompatibel. |
| Resource-STI | `nanigans/single-table-inheritance` durch `tightenco/parental` `^1.6` ersetzen | Der nicht zukunftsfähige STI-Treiber wird ersetzt. Gespeicherte `type`-Werte, Modellklassen, Hydrierung und JSON bleiben identisch. |
| Nested Sets | `kalnoy/nestedset` auf die Laravel-13-kompatible Hauptversion 7 aktualisieren | Das aktuelle Baumverhalten ist durch `KeywordNestedSetTest` und `KeywordApiControllerTest` verbindlich eingefroren. Keine Reparatur oder Neuordnung produktiver Bäume im Rahmen des Upgrades. |
| Eigenes Bible-Paket | Das `stevenbuehner/bible-verse-bundle` wird als separates, vom Auftraggeber verwaltetes Composer-Projekt behandelt | In diesem Repository werden nur Anforderungen und Kompatibilitätsbefunde ermittelt. Änderungen und Release-Tags des Pakets nimmt der Auftraggeber im separaten Projekt vor. Eingebunden wird anschließend ein vom Auftraggeber bereitgestellter stabiler, unveränderlicher Tag mit Laravel-13-/PHP-8.4-Kompatibilität und unveränderten PHP- und JavaScript-Exports. |
| Frontend | Vue 2, Vue Router 3, Vuex 3, Bootstrap 4, Bootstrap-Vue, Laravel Mix/Webpack und beide npm-Lock-/Manifestverträge bleiben unverändert | `laravel/ui` darf auf `^4.0` aktualisiert werden, aber es wird kein Scaffolding-Befehl ausgeführt. Änderungen unter `resources/js`, `resources/sass`, `package.json` oder `package-lock.json` sind nicht Teil von P1. |
| Stability | Alle Framework-Checkpoints verwenden stabile Releases; die finale Composer-Konfiguration erlaubt ausschließlich stabile Releases | Für EOL-Checkpoints darf Composers Security-Blocking nur beim kontrollierten Lockfile-Aufbau übergangen werden, nicht der Audit selbst. `minimum-stability: dev` wird im Endstand entfernt oder auf `stable` gesetzt. Verbleibende Dev-Abhängigkeiten sind dann nicht zulässig. |
| Offizielle Upgradeleitfäden | Vor jedem Laravel-Hauptversionsschritt wird der zu diesem Zeitpunkt online verfügbare offizielle Laravel-Upgradeleitfaden vollständig geprüft | Die Prüfung wird mit URL, Abrufdatum und Einordnung jedes anwendbaren Punkts im Stufenbericht dokumentiert. Bei Major-Upgrades von Laravel-Erstpaketen, insbesondere Passport, gilt dies zusätzlich für deren offiziellen Upgradeleitfaden. |

## Entscheidungs- und Änderungskontrolle

- Bestehendes Verhalten ist unabhängig davon zu erhalten, ob es modern, optimal oder von einer neuen Laravel-/Paketversion empfohlen ist.
- Neue Framework-Defaults werden nicht automatisch übernommen, wenn sie Laufzeit-, Daten-, Sicherheits-, API-, Queue-, Storage-, Serialisierungs- oder Nutzerverhalten verändern könnten. Der bisherige Wert wird zunächst explizit konfiguriert und durch Charakterisierungstests geschützt.
- Tests werden nicht auf ein abweichendes Verhalten aktualisierter Bibliotheken umgeschrieben. Eine solche Abweichung wird zunächst als Regression behandelt.
- Während des Upgrades werden Verbesserungspotenziale aktiv ermittelt und dokumentiert. Jeder Vorschlag enthält mindestens Empfehlung, Alternative, Nutzen, Risiken, betroffene Verträge, Migrationsweg und Rückbauaufwand.
- Ein Verbesserungsvorschlag wird erst nach ausdrücklicher Freigabe des Auftraggebers umgesetzt. Ohne Freigabe bleibt das bisherige Verhalten bestehen oder der betroffene Upgrade-Schritt pausiert, falls es technisch nicht erhalten werden kann.
- Rein interne Kompatibilitätsanpassungen dürfen ohne erneute Freigabe erfolgen, wenn Tests nachweisen, dass alle beobachtbaren Verträge unverändert bleiben.
- Neue Laravel-Standards und aktuelle Framework-Konventionen werden während jeder Stufe übernommen, wenn die Umstellung im betroffenen Bereich vollständig möglich, für die folgenden Laravel-Stufen tragfähig und durch Tests nachweislich verhaltensneutral ist. Veraltete oder überholte Konventionen werden in diesem Fall nicht als bloßer Kompatibilitätsballast fortgeführt.
- Eine Konventionsumstellung wird nicht nur teilweise oder kosmetisch durchgeführt. Sind zusammengehörige Aufrufstellen, Konfigurationen oder Tests nicht vollständig migrierbar, bleibt vorerst die bestehende Konvention bestehen und der Befund wird als Entscheidungsvorschlag dokumentiert.
- Neue Framework-Defaults, die beobachtbares Verhalten verändern, gelten nicht allein wegen ihres Standardstatus als freigegeben. Für Änderungen an API, Daten, Sicherheit, Berechtigungen, Storage, Queues, Serialisierung, UX oder Nutzerverhalten ist weiterhin vorab eine ausdrückliche Entscheidung erforderlich.
- Die Zuständigkeit für das separate `bible-verse-bundle` verbleibt beim Auftraggeber. Der Agent darf notwendige Änderungen an diesem Paket beschreiben und eine konkrete Versionierungs-/Release-Empfehlung geben, aber weder dessen Repository noch dessen Releases ohne einen separaten ausdrücklichen Auftrag verändern.

## Verbindliche Leitfadenprüfung je Stufe

- Vor der Änderung von PHP-, Laravel- oder Composer-Constraints wird der offizielle Online-Leitfaden der unmittelbar nächsten Laravel-Hauptversion unter `https://laravel.com/framework/docs/<version>.x/upgrade` geöffnet und vollständig auf das Repository abgebildet. Eine gespeicherte Kopie, Erinnerung oder Zusammenfassung allein genügt nicht.
- Der Stufenbericht enthält mindestens Abrufdatum, genaue Zielversionen, alle für das Projekt anwendbaren Punkte sowie je Punkt den Status `nicht betroffen`, `durch Test abgedeckt`, `verhaltensneutral umgesetzt` oder `Entscheidung erforderlich`.
- Für jedes angehobene Laravel-Erstpaket wird dessen offizieller Major-Upgradeleitfaden ebenfalls online geprüft. Für Passport gilt insbesondere `https://github.com/laravel/passport/blob/<version>.x/UPGRADE.md`.
- Änderungen am offiziellen Leitfaden zwischen Planung und Umsetzung werden erneut bewertet. Widerspricht eine neue Vorgabe diesem Vertrag oder verlangt sie eine Verhaltens-, Sicherheits-, Daten- oder Schemaentscheidung, pausiert die Stufe vor der betreffenden Änderung.
- Der offizielle Leitfaden ist eine technische Pflichtquelle, erweitert aber nicht eigenständig die Änderungsautorisierung dieses Vertrags.

## Unveränderliche Produktverträge

Die folgenden Punkte sind harte Abnahmekriterien. Eine Abweichung ist kein „Upgrade-Fix“, sondern eine gesondert freizugebende Produktänderung.

### API, Authentifizierung und Sicherheit

- Alle bestehenden Web-, API-v1- und API-v2-Routen, HTTP-Methoden, Routennamen, Middleware-Anforderungen, Statuscodes und JSON-Strukturen bleiben erhalten.
- Passport bleibt der `api`-Guard. Bestehende OAuth-Clients, Access-/Refresh-Tokens, Scopes und Schlüssel bleiben nutzbar.
- `User`, Policies, Gates, Rollen, Eigentümerschaft, `is_public` und Sichtbarkeitsregeln behalten ihre Semantik.
- Die für Passport 13 erforderliche Modellanpassung auf `OAuthenticatable` ist erlaubt, sofern die beschriebenen Verträge durch Tests nachgewiesen unverändert bleiben.
- Der Wechsel des Framework-CSRF-Middleware-Namens darf nur als kompatible Klassenanpassung erfolgen; Ausnahmen, geschützte Routen und Fehlerverhalten bleiben gleich.

### Datenmodell und Serialisierung

- Bestehende Tabellen, Spalten, Indizes und Primärschlüssel werden nicht ohne separate Entscheidung geändert.
- Anwendungseigene historische Migrationen werden nicht umgeschrieben. Eine neue Schema- oder Datenmigration ist in P1 nicht autorisiert und erfordert eine separate Entscheidung.
- Migrationen eines Laravel-Erstpakets, die in einer Vorversion automatisch aus dem Paket geladen wurden und laut offiziellem Upgradeleitfaden nun veröffentlicht werden müssen, dürfen unverändert in das Repository übernommen werden. Dies gilt nicht als neues Schema, sofern Dateiname, Inhalt und resultierendes Schema dem bisher automatisch ausgeführten Stand entsprechen. Anzahl und Inhalt werden gegen unbeabsichtigte Paketänderungen getestet. Änderungen an diesen veröffentlichten Dateien sind nicht erlaubt.
- Kann eine neue Laravel-Version das bisherige Zielschema aus einer unveränderten anwendungseigenen Migration wegen geänderter Migrationssemantik nicht mehr reproduzieren, ist dies kein gewöhnlicher Kompatibilitätsfix: Das Fresh-Migration-Gate pausiert und verlangt eine Entscheidung zwischen einer minimalen, nachweislich schemaäquivalenten Quellenkorrektur und einem versionierten Schema-Baseline-Verfahren.
- **Freigegebene Ausnahme vom 9. September 2026:** In `2019_01_19_235903_bigger_local_path.php` werden ausschließlich die bereits zuvor geltenden `nullable`-Attribute von `resources.remote_path` und `resources.local_path` bei `change()` explizit wiederholt. Dies folgt der Laravel-11-Migrationssemantik und darf das resultierende Schema nicht verändern. Vorwärts- und Rückwärtsdefinition sowie Fresh-/Bestandsschema werden darauf geprüft. Weitere Änderungen historischer Migrationen sind dadurch nicht autorisiert.
- Resource-STI behält alle vorhandenen Kurzcodes und Zuordnungen, insbesondere `res`, `link`, `file`, `text`, `book` sowie die Datei-Untertypen.
- `MaterialResource.limitation`, Keyword-/Bibleverse-`relevance`, `options`, Zeitstempel und alle bisher serialisierten Werte bleiben les- und schreibkompatibel.
- Laravel-13-Härtungen für Cache-Serialisierung werden explizit konfiguriert und getestet. Bestehende erlaubte Anwendungsobjekte dürfen nicht stillschweigend unlesbar werden.
- Die Session-Serialisierung bleibt zunächst PHP-kompatibel. Eine spätere Umstellung auf JSON ist ein separater, geplanter Session-Cutover und nicht Teil dieses Upgrades.
- Explizite Session-Cookie- und Cache-Prefix-Werte bleiben erhalten, damit keine ungewollte Namespace-Änderung entsteht.

### Verbindlicher Nested-Set-Vertrag

- `keywords` bleibt ein gemeinsamer, nicht nach `type` gescopter Forest mit den Typen `key`, `person`, `place` und `lang`.
- `_lft`, `_rgt` und `parent_id` bleiben strukturell konsistent; `Keyword::query()->isBroken()` muss nach jeder geprüften Mutation `false` liefern.
- Die historischen Sprachknoten `Deutsch`, `Englisch` und `Französisch` bleiben Root-Knoten in ihrer bisherigen Reihenfolge.
- `defaultOrder()` liefert Preorder. `ancestors`, `descendants`, `children`, `siblings`, `withDepth` und `descendantsAndSelf` behalten Umfang und Reihenfolge.
- Das Setzen von `parent_id`, `appendToNode`, `prependToNode`, relative Einfügungen und `saveAsRoot` behalten die nachgewiesene Teilbaumsemantik.
- Ein Knoten darf nie unter sich selbst oder einem eigenen Nachfahren platziert werden; der Fehler darf keinen teilweise veränderten Baum hinterlassen.
- Die Suche nach einem Parent-Keyword umfasst direkt und indirekt verschlagwortete Materialien sowie Autoren im Teilbaum, aber keine Geschwisterzweige.
- `descendantMaterials()` umfasst den Knoten selbst und alle Tiefen, liefert Materialien trotz mehrerer Treffer nur einmal und schließt fremde Zweige aus.
- Beim Keyword-Merge gehen Kinder, Materialbeziehungen mit `relevance` und Autorenbeziehungen auf den überlebenden Knoten über.
- „Löschen ohne Kinder“ hebt direkte Kinder unter Beibehaltung ihrer Teilbäume auf die Ebene des gelöschten Knotens. Die heute nachgewiesene Reihenfolge mehrerer gehobener Kinder wird beibehalten.
- „Löschen mit Kindern“ entfernt ausschließlich den vollständigen gewählten Teilbaum.
- `_lft` und `_rgt` bleiben in JSON verborgen; `parent_id` und explizit geladene Baumrelationen bleiben sichtbar.

Diese Semantik ist in `tests/Feature/KeywordNestedSetTest.php` und den erweiterten Fällen in `tests/Feature/KeywordApiControllerTest.php` ausführbar festgeschrieben. Eine Anpassung der Tests an ein abweichendes Verhalten einer neuen Paketversion ist unzulässig, solange nicht zuvor eine fachliche Änderung freigegeben wurde.

### Dateien, Events und asynchrone Verarbeitung

- `resources.local_path` bleibt im Format `disk::relative/path` les- und schreibkompatibel.
- Die Disks `resources`, `archive`, `bundles`, `local_tmp`, `backup` und `testfiles` behalten Zweck, Sichtbarkeit und Pfadsemantik.
- Upload, Download, Archivierung, Löschung, Hashing, Metadaten, Vorschauerzeugung und Cache-Invalidierung bleiben erhalten.
- Material-/Resource-Events, Listener, Jobs, Retry-Verhalten und Queue-Namen bleiben erhalten; insbesondere bleiben bundle-spezifische Queues `bundle_{id}_queue` kompatibel.
- Der Wechsel von Flysystem 1 auf 3 ersetzt interne Adapterzugriffe durch öffentliche APIs, ohne gespeicherte Pfade oder Dateioperationen zu verändern.

## Composer-Zielbild

Folgende Ziel-Hauptversionen sind vereinbart. Pro Upgrade-Schritt werden die neueste stabile Framework-Version und die für diese Stufe vorgesehenen neuesten stabilen direkten Paketversionen gelockt. Ein verhaltenssensitives transitive Major-Upgrade darf nur dann bis zur dafür vorgesehenen Stufe festgehalten werden, wenn der Vertrag dies ausdrücklich benennt; Constraint, Grund und späteste Auflösungsstufe werden im Stufenbericht dokumentiert. Bewegliche Dev-Stände sind davon ausgenommen und im Endstand unzulässig.

| Paket/Bereich | Ziel oder Aktion |
| --- | --- |
| `php` | `^8.4` |
| `laravel/framework` | `^13.0` |
| `laravel/passport` | `^13.0` |
| `laravel/tinker` | `^3.0` |
| `laravel/ui` | `^4.0`, ohne Scaffolding |
| `kalnoy/nestedset` | `^7.0` |
| `nanigans/single-table-inheritance` | entfernen |
| `tightenco/parental` | `^1.6` hinzufügen |
| `spatie/laravel-backup` | `^10.0` |
| `phpunit/phpunit` | `^12.0` im Laravel-13-Endstand |
| `fzaninotto/faker` | durch `fakerphp/faker` ersetzen |
| `facade/ignition` | durch die kompatible stabile `spatie/laravel-ignition`-Version ersetzen |
| `barryvdh/laravel-debugbar` | durch `fruitcake/laravel-debugbar` `^4.0` ersetzen |
| `barryvdh/laravel-ide-helper` | kompatible stabile 3.x-Version |
| `mariuzzo/laravel-js-localization` | kompatible stabile 2.x-Version; generierte JS-Schnittstelle muss gleich bleiben |
| Sail, Collision, Mockery | jeweils neueste stabile Version, die Laravel 13, PHP 8.4 und PHPUnit 12 gemeinsam unterstützt |
| `stevenbuehner/bible-verse-bundle` | vom Auftraggeber im separaten Projekt bereitgestellter stabiler Release-Tag mit PHP-8.4-/Laravel-13-Kompatibilität; dieses Repository aktualisiert danach nur die Composer-Referenz |

Medien- und Konvertierungsbibliotheken (`intervention/image`, EXIF/ExifTool, FFmpeg, FPDI/FPDF, PDF-to-text und Office-Konvertierung) werden auf die jeweils neueste stabile PHP-8.4-kompatible Version aktualisiert, soweit ihre bestehende öffentliche API erhalten bleibt. Ein API-brechender Wechsel – insbesondere Intervention Image 2 auf 3 – wird als separates Backend-Teilprojekt behandelt und nur dann in P1 aufgenommen, wenn Laravel 13/PHP 8.4 sonst nicht erreichbar ist. In diesem Fall ist vor der Umsetzung eine neue Entscheidung mit Migrations- und Rückbauplan erforderlich.

`howtomakeaturn/pdfinfo` ist nach aktueller statischer Analyse nicht im Anwendungscode referenziert. Seine Entfernung ist erst nach Composer-/Autoload-Laufzeitanalyse zulässig; wird eine indirekte Nutzung gefunden, bleibt es auf einer kompatiblen stabilen Version. Das Entfernen anderer vermeintlich ungenutzter Pakete ist nicht durch diesen Vertrag autorisiert.

## Verbindliche Reihenfolge

### Stufe 0 – Charakterisierung und reproduzierbare Basis

**Status:** Am 9. September 2026 umgesetzt und mit 141 grünen Tests abgenommen. Details und nicht umgesetzte Verbesserungsvorschläge: `docs/ai/upgrade-stage-0-report.md`.

1. Die vollständige Laravel-8-Suite muss einschließlich der neuen Nested-Set-Tests grün sein.
2. Ergänzt werden noch fehlende Charakterisierungstests für Resource-STI, Passport, relevante serialisierte Pivot-/Cachewerte, Storage/Archiv, Queue-Namen, Bundle-Paketintegration und Backup-Konfiguration, bevor der jeweils betroffene Code geändert wird.
3. `route:list`, relevante API-Beispielantworten und Composer-Paketstand werden als maschinenlesbare oder testbare Referenz erfasst, ohne reale Daten oder Secrets zu speichern.
4. Für das Bible-Paket werden benötigte Composer-Constraints sowie PHP-/Laravel- und Export-Kompatibilität ermittelt und dem Auftraggeber als konkrete Anforderung übergeben. Der Auftraggeber stellt den stabilen Tag im separaten Projekt bereit; erst danach wird er hier eingebunden und gegen den bestehenden Stand geprüft.

### Stufe 1 – Laravel 9

**Status:** Am 9. September 2026 als ausschließlich lokaler, nicht deploybarer EOL-Kompatibilitäts-Checkpoint umgesetzt. Prüfungen und bekannte Befunde: `docs/ai/upgrade-stage-1-report.md`.

- PHP-Laufzeit mindestens 8.0.2, bevorzugt 8.1 für diesen Checkpoint.
- Framework und kompatible Pakete auf Laravel 9 anheben.
- Flysystem 3 und Symfony Mailer kompatibel umstellen.
- `facade/ignition` durch `spatie/laravel-ignition` ersetzen.
- Keine fachlichen oder Frontendänderungen.

### Stufe 2 – Laravel 10

**Status:** Am 9. September 2026 als ausschließlich lokaler, nicht deploybarer EOL-Kompatibilitäts-Checkpoint umgesetzt. Prüfungen und bekannte Befunde: `docs/ai/upgrade-stage-2-report.md`.

- PHP mindestens 8.1.
- Framework und direkte Composer-Abhängigkeiten auf Laravel-10-kompatible stabile Versionen anheben.
- Monolog-3-, Signatur- und Rückgabetypanpassungen ausschließlich verhaltensneutral durchführen.

### Stufe 3 – Laravel 11

**Status:** Am 9. September 2026 als ausschließlich lokaler, nicht deploybarer EOL-Kompatibilitäts-Checkpoint umgesetzt. Prüfungen, Leitfadenmatrix und bekannte Befunde: `docs/ai/upgrade-stage-3-report.md`.

- PHP mindestens 8.2.
- Klassische Anwendungsstruktur beibehalten.
- Den offiziellen Laravel-11- und Passport-12-Upgradeleitfaden online prüfen und vollständig im Stufenbericht abbilden.
- Authentifizierung, Queues, Scheduling, Mail und Carbon-Verhalten gegen die Charakterisierungstests prüfen.
- Carbon 3 bereits in dieser Stufe kontrolliert einführen: Laravel 11 unterstützt Carbon 2 und 3, und im Anwendungscode werden keine von Carbon 3 semantisch geänderten `diffIn*`-Methoden verwendet. Damit entfällt der bisherige Widerspruch zwischen „neueste stabile kompatible Pakete“ und einer künstlichen Carbon-2-Fixierung bis Laravel 12.
- Cache-Präfix, Password-Rehashing und Queue-`after_commit` so explizit konfigurieren, dass das bisherige Verhalten erhalten bleibt.
- Die fünf bislang automatisch geladenen Passport-Migrationen gemäß Passport-12-Leitfaden unverändert veröffentlichen und ihre exakte Liste prüfen; keine zusätzlichen Passport-Schemaänderungen einführen.
- Den bisher verfügbaren Password Grant ausdrücklich aktivieren und testen.
- Die freigegebene schemaäquivalente `nullable()`-Korrektur der zwei historischen `change()`-Definitionen für `resources.remote_path` und `resources.local_path` umsetzen und gegen Fresh- sowie Bestandsschema prüfen.

### Stufe 4 – Laravel 12

- Zielruntime PHP 8.4 herstellen.
- Den offiziellen Laravel-12-Upgradeleitfaden online prüfen und vollständig im Stufenbericht abbilden.
- PHPUnit 11 einführen.
- Backup-Paket samt veröffentlichter Konfiguration kontrolliert auf die kompatible Hauptversion migrieren.
- Veraltete Framework- und Test-APIs beseitigen, ohne Verträge zu ändern.
- Insbesondere lokale Disk-Roots, SVG-Bildvalidierung, `mergeIfMissing`, Route-Namenspräzedenz und Container-Auflösung prüfen; neue Defaults nur bei nachgewiesen identischem Verhalten übernehmen.

### Stufe 5 – Laravel 13

- Den offiziellen Laravel-13-Upgradeleitfaden sowie den Passport-13-Upgradeleitfaden online prüfen und vollständig im Stufenbericht abbilden.
- `laravel/framework ^13.0`, `laravel/tinker ^3.0`, PHPUnit 12 und alle finalen Paketziele locken.
- Passport 13 einschließlich `OAuthenticatable` integrieren.
- Integer-basierte bestehende Passport-Client-IDs und die bisherigen JSON-API-Routen ausdrücklich konfigurieren. UUID-Default und das Abschalten der JSON-API werden nicht stillschweigend übernommen.
- Vor dem Passport-13-Upgrade sind separate Entscheidungen zu treffen: Hashing bestehender Client-Secrets, Umgang mit dem neuen `oauth_clients`-Schema einschließlich der dokumentierten Integer-ID-Kollision sowie Ersatz der entfallenen Passport-Authorization-View. Bis dahin ist dieser Teil der Stufe gesperrt.
- Den CSRF-Middleware-Namenswechsel auf `PreventRequestForgery` modernisieren. Die neue Origin-Prüfung über `Sec-Fetch-Site` ist eine Sicherheits- und Verhaltensänderung und benötigt vor Aktivierung eine separate Entscheidung mit Erfolgs- und Ablehnungstests.
- Cache- und Session-Serialisierung sowie Prefix-Kontinuität explizit konfigurieren und testen.
- Cache-Objekte auf eine explizite `serializable_classes`-Allowlist begrenzen. Die Session-Serialisierung bleibt für einen unterbrechungsfreien Upgradepfad ausdrücklich `php`; ein Wechsel zu `json` bleibt ein separater Session-Cutover.
- Parental-STI und Nestedset 7 anhand ihrer vollständigen Vertragsgruppen abnehmen.
- `minimum-stability` auf stabile Releases begrenzen und bewegliche Branch-Abhängigkeiten entfernen.

## Quality Gates je Hauptversionsschritt

Ein Schritt darf erst begonnen werden, wenn der vorherige vollständig grün ist. Ein fehlgeschlagenes Gate wird repariert oder der Schritt wird auf seinen letzten grünen Commit zurückgesetzt; Tests dürfen nicht abgeschwächt werden, um neue Paketsemantik zu akzeptieren.

1. Online-Prüfung des offiziellen Laravel-Upgradeleitfadens und aller angehobenen Laravel-Erstpakete mit dokumentierter Punkt-für-Punkt-Einordnung.
2. `composer validate --strict` und ein reproduzierbarer Lockfile-Aufbau.
3. `composer audit`; offene Findings werden immer dokumentiert. Bei den ausdrücklich nicht deploybaren Laravel-9- bis Laravel-11-Checkpoints blockieren ausschließlich durch die gewählte historischen Framework-/Passport-Stufe unvermeidbare Findings den lokalen Kompatibilitätsschritt nicht. Andere sicherheitskritische Findings blockieren weiterhin. Der Laravel-13-Endstand darf keine offenen sicherheitskritischen Findings enthalten.
4. PHP-Syntaxprüfung aller geänderten PHP-Dateien.
5. Vollständige PHPUnit-Suite gegen die dedizierte MySQL-Datenbank `testing`.
6. Nested-Set-Vertragsgruppe separat; zusätzlich `countErrors()` und `isBroken()` nach allen Mutationsszenarien.
7. Frische Datenbank aus allen unveränderten anwendungseigenen und den ausdrücklich übernommenen Paketmigrationen sowie Upgrade einer Datenbankkopie mit realistischer Struktur; niemals gegen Produktionsdaten.
8. API-v1/v2- und Passport-Prüfungen: unauthentifiziert, authentifiziert, erlaubt, verboten sowie Token-/Client-Kontinuität.
9. Resource-STI für jeden gespeicherten Typ und Untertyp: Erzeugung, Hydrierung, Relation und JSON.
10. Material-, Resource-, Keyword- und Bibleverse-Pivots einschließlich `limitation` und `relevance`.
11. Upload, Lesen, Vorschau, Archivieren und Löschen repräsentativer Dateiarten auf gefakten beziehungsweise isolierten Test-Disks.
12. Events, Listener, synchrone Testjobs, Queue-Namen und Bundle-Jobreihenfolge.
13. Backup-Erzeugung und testweiser Restore in eine isolierte temporäre Umgebung.
14. Das unveränderte Frontend wird durch den bestehenden Production-Build und Hashvergleich der Manifeste und Quellen geprüft. `npm ci` wird versucht; ein bereits in Stufe 0 nachgewiesener, unveränderter Plattformfehler einer Legacy-Frontend-Abhängigkeit blockiert die reinen Backend-Checkpoints nicht, solange Lockfiles unverändert bleiben und der Build aus dem bestehenden Lock-Stand erfolgreich ist. Die Behebung dieses Frontend-Installationsproblems bleibt Aufgabe des separaten Frontend-Upgrades.
15. Prüfung, dass Entwicklungsdatenbank, reguläre Storage-Dateien und Archive unverändert geblieben sind.

## Commit-, Rückbau- und Abnahmeregeln

- Charakterisierungstests werden vor dem ersten Dependency-Upgrade separat committed.
- Jeder Laravel-Hauptversionsschritt besteht aus einem eigenen, reviewbaren Commit oder einer kleinen zusammenhängenden Commitserie und endet mit einem dokumentierten grünen Gate.
- Commits der EOL-Zwischenstände Laravel 9 bis 11 sind weder Release- noch Deployment-Kandidaten und dürfen nicht in einer erreichbaren produktiven Umgebung betrieben werden.
- Das `composer.lock` wird pro Schritt vollständig geprüft; unerklärte transitive Major-Upgrades blockieren die Abnahme.
- Der Rückbau erfolgt auf den letzten grünen Hauptversions-Checkpoint. Datenbank-Rückbau ist nicht vorgesehen, weil P1 keine Schema- oder Datenänderung autorisiert.
- Laravel 13 ist erst abgenommen, wenn ein kalter Sail-Start, eine frische Installation aus Lockfiles, die vollständige Suite, alle Spezial-Gates und der bestehende Frontend-Build erfolgreich sind.
- „Frische Installation aus Lockfiles“ bezeichnet in P1 zwingend den Composer-Lockfile-Aufbau. Für npm gilt bis zum separaten Frontend-Upgrade die in Quality Gate 14 beschriebene, dokumentierte Legacy-Ausnahme.
- Am Ende werden `AGENTS.md`, `docs/ai/baseline-b8dd716.md`, `docs/ai/architecture.md` und `docs/ai/quality-gates.md` auf die neue verifizierte Basis aktualisiert. Vorher bleiben sie als Beschreibung der noch gültigen Ausgangsbasis bestehen.

## Nicht Bestandteil von P1

- Vue-3-, Vite-, Bootstrap- oder sonstige Frontendmodernisierung.
- Änderung von UX, Navigation, Design oder sichtbaren Texten.
- Neue Funktionen, API-Versionen oder Datenmodelle.
- Bereinigung, Neuordnung oder Reparatur produktiver Nested Sets.
- Wechsel von Passport zu einem anderen Authentifizierungssystem.
- Umstellung der Session-Serialisierung auf JSON.
- Deployment in Produktion; dafür ist nach erfolgreicher technischer Abnahme ein eigener Rollout-/Backup-/Rollbackplan erforderlich.

## Referenzen

- Laravel-13-Upgradeleitfaden: <https://laravel.com/framework/docs/13.x/upgrade>
- Laravel-Release- und Supportübersicht: <https://laravel.com/docs/13.x/releases>
- Laravel-9-Upgradeleitfaden: <https://laravel.com/framework/docs/9.x/upgrade>
- Laravel-10-Upgradeleitfaden: <https://laravel.com/framework/docs/10.x/upgrade>
- Laravel-11-Upgradeleitfaden: <https://laravel.com/framework/docs/11.x/upgrade>
- Laravel-12-Upgradeleitfaden: <https://laravel.com/framework/docs/12.x/upgrade>
- Passport-Upgradeleitfaden: <https://github.com/laravel/passport/blob/13.x/UPGRADE.md>
- Projektinterne Invarianten: `docs/ai/domain-invariants.md`
- Projektinterne Quality Gates: `docs/ai/quality-gates.md`

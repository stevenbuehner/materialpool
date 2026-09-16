# Verbindlicher Upgradevertrag: Laravel 8 auf Laravel 13

## Status und Auftrag

Dieser Vertrag ist die verbindliche Arbeitsgrundlage für P1. Ziel ist Laravel 13 auf PHP 8.4 mit aktualisierten Composer-Abhängigkeiten. Die Umstellung erfolgt in überprüfbaren Hauptversionsschritten. Das bestehende Vue-2-Frontend wird nicht modernisiert und seine Paketdefinitionen, sein Buildsystem sowie sein sichtbares Verhalten bleiben unangetastet, soweit eine Backend-Kompatibilitätsanpassung dies nicht zwingend erfordert.

**Vertragsrevision:** 9. September 2026. Der Vertrag wurde gegen die zu diesem Zeitpunkt online verfügbaren offiziellen Laravel-Upgradeleitfäden 9.x bis 13.x sowie den offiziellen Passport-Upgradeleitfaden geprüft. Die Online-Prüfung ist ab dieser Revision selbst Bestandteil jedes weiteren Hauptversionsschritts.

Der Vertrag autorisiert die nachfolgend ausdrücklich beschriebenen Paket- und Laufzeitänderungen. Ein Upgrade ist niemals eine stillschweigende Freigabe, bestehendes Verhalten zu ändern. Der Vertrag autorisiert insbesondere keine Änderung fachlicher oder technischer Verhaltensverträge, produktiver Daten, Datenbankstrukturen, Berechtigungen, API-Payloads, Speicherorte oder Frontendgestaltung, mit Ausnahme des in Stufe 5 ausdrücklich freigegebenen Passport-13-Schema- und Client-Cutovers. Wird eine weitere solche Änderung technisch unvermeidbar oder erscheint sie gegenüber dem Ist-Verhalten sinnvoll, endet die Umsetzungsautorisierung an dieser Stelle und es ist vorab eine neue Entscheidung nach `docs/ai/decision-template.md` einzuholen.

## Verbindliche Zielentscheidungen

| Thema | Verbindliche Entscheidung | Begründung und Begrenzung |
| --- | --- | --- |
| PHP | Zielversion PHP 8.4 | Laravel 13 unterstützt PHP 8.3 bis 8.5; PHP 8.4 erfüllt zugleich die Anforderung von `spatie/laravel-backup` 10 und bietet mehr Bibliotheksreserve als 8.3. |
| Produktionsplattform | Einzelner Ubuntu-24.04-LTS-Server mit Nginx, PHP-FPM 8.4 und MySQL 8 hinter externem TLS-Reverse-Proxy | Sail bleibt ausschließlich Entwicklungs- und Testumgebung. Atomare Releases, Shared-Pfade, Rechte, Proxy-Trust, Queue, Cron, verschlüsseltes lokales/S3-Backup und Restore-Gate sind im verbindlichen [`production-deployment-contract.md`](production-deployment-contract.md) festgelegt. |
| Laravel | Laravel 9 → 10 → 11 → 12 → 13 | Jeder Hauptversionsschritt erhält einen eigenen grünen Prüfpunkt. Zwischenstände sind technische Checkpoints, keine produktiven Releases. |
| Nicht deploybare Zwischenstände | Laravel 9 bis 11 sowie der bewusst exakte Laravel-12.0.0-Messpunkt werden ausschließlich als lokale, nicht deploybare Kompatibilitäts-Checkpoints verwendet | Für Laravel 9 bis 11 werden die jeweils letzten stabilen Releases verwendet. Laravel 12 wird gemäß Entscheidung A exakt auf 12.0.0 begrenzt, weil Nestedset 6 keine spätere 12.x-Version unterstützt; danach folgt ohne Deployment unmittelbar Laravel 13 mit Nestedset 7. Versionsbedingt unvermeidbare Security-Advisories werden dokumentiert und nicht durch bewegliche Dev-Stände umgangen. |
| Anwendungsstruktur | Bestehende klassische Laravel-Struktur beibehalten | Die Anwendung wird nicht auf die schlanke Laravel-11+-Skeleton-Struktur umgebaut. Provider, Kernel und Konfigurationsdateien bleiben explizit. |
| OAuth | Laravel Passport beibehalten und auf Version 13 mit möglichst unveränderten Paketdefaults aktualisieren | UUIDs, gehashte Client-Secrets, das neue Client-Schema und die standardmäßig deaktivierte veraltete JSON-Verwaltungs-API werden übernommen. Der produktbedingt benötigte Password Grant bleibt die einzige bewusste Grant-Abweichung und wird explizit aktiviert. Bestehende numerische Client-IDs bleiben als Zeichenketten erhalten; neue Clients verwenden UUIDs. Details stehen in `docs/ai/passport-13-client-migration.md`. |
| Resource-STI | `nanigans/single-table-inheritance` durch `tightenco/parental` `^1.6` ersetzen | Der nicht zukunftsfähige STI-Treiber wird ersetzt. Gespeicherte `type`-Werte, Modellklassen, Hydrierung und JSON bleiben identisch. |
| Nested Sets | `kalnoy/nestedset` auf die Laravel-13-kompatible Hauptversion 7 aktualisieren | Das aktuelle Baumverhalten ist durch `KeywordNestedSetTest` und `KeywordApiControllerTest` verbindlich eingefroren. Keine Reparatur oder Neuordnung produktiver Bäume im Rahmen des Upgrades. |
| Eigenes Bible-Paket | Das `stevenbuehner/bible-verse-bundle` wird als separates, vom Auftraggeber verwaltetes Composer-Projekt behandelt | Der bereitgestellte stabile Tag `3.0.0` ist seit dem 9. September 2026 über `^3.0` eingebunden. Er verweist auf Commit `c9757851ee69220293223728e1db525951e60da8`, unterstützt PHP 8.4 und erhält die geprüften PHP- und JavaScript-Exports. Änderungen und künftige Releases des Pakets bleiben Aufgabe des Auftraggebers. |
| Frontend | Vue 2, Vue Router 3, Vuex 3, Bootstrap 4, Bootstrap-Vue, Laravel Mix/Webpack und beide npm-Lock-/Manifestverträge bleiben unverändert | `laravel/ui` darf auf `^4.0` aktualisiert werden, aber es wird kein Scaffolding-Befehl ausgeführt. Änderungen unter `resources/js`, `resources/sass`, `package.json` oder `package-lock.json` sind nicht Teil von P1. |
| Stability | Alle Framework-Checkpoints und der finale Abhängigkeitsstand verwenden stabile Releases; die finale Composer-Konfiguration setzt `minimum-stability` auf `stable` | Für EOL-Checkpoints durfte Composers Security-Blocking nur beim kontrollierten Lockfile-Aufbau übergangen werden, nicht der Audit selbst. Der frühere Bible-Paket-Commit-Pin wurde nach Veröffentlichung von `3.0.0` entfernt; der Laravel-13-Endstand enthält keine Dev-Anforderung und keine Stability-Ausnahme mehr. |
| Offizielle Upgradeleitfäden | Vor jedem Laravel-Hauptversionsschritt wird der zu diesem Zeitpunkt online verfügbare offizielle Laravel-Upgradeleitfaden vollständig geprüft | Die Prüfung wird mit URL, Abrufdatum und Einordnung jedes anwendbaren Punkts im Stufenbericht dokumentiert. Bei Major-Upgrades von Laravel-Erstpaketen, insbesondere Passport, gilt dies zusätzlich für deren offiziellen Upgradeleitfaden. |
| KI-Entwicklungsunterstützung | Laravel Boost als lokale `require-dev`-Abhängigkeit einsetzen | Boost 2 gilt ab Laravel 11 und wieder im Laravel-13-Endstand. Für exakt Laravel 12.0.0 wird vorübergehend die stabile Version 1.0.21 mit zusätzlich ausgeschlossenen Alt-Werkzeugen verwendet, weil aktuelle Boost-2-Versionen eine spätere Laravel-12-Patchversion verlangen. Produkt-KI, ein anwendungseigener MCP-Server und selbstständige Schreib-/Ausführungswerkzeuge sind nicht Bestandteil von P1. |

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

- Alle bestehenden Web-, API-v1- und API-v2-Pfade, HTTP-Methoden, Middleware-Anforderungen, Statuscodes und JSON-Strukturen bleiben erhalten. Die zwei bislang doppelt vergebenen Routennamen werden eindeutig (`materials.copy`, `bundles.index`); Laravel UI behält `logout`, während der unveränderte Vue-2-GET-Endpunkt vorübergehend `logout.legacy` heißt.
- Passport bleibt der `api`-Guard. Bestehende numerische OAuth-Client-IDs werden wertgleich in `CHAR(36)` überführt; zugehörige Tokenreferenzen bleiben erhalten. Client-Secrets werden einmalig gehasht und weiterhin mit demselben Klartext-Credential verwendet. Neue Clients nutzen UUIDs.
- `User`, Policies, Gates, Rollen, Eigentümerschaft, `is_public` und Sichtbarkeitsregeln behalten ihre Semantik.
- Die für Passport 13 erforderliche Modellanpassung auf `OAuthenticatable` ist erlaubt, sofern die beschriebenen Verträge durch Tests nachgewiesen unverändert bleiben.
- Der Wechsel auf `PreventRequestForgery` einschließlich Laravel-13-Origin-Prüfung ist als freigegebene Sicherheitshärtung umzusetzen; Ausnahmen und geschützte Routen bleiben gleich. Same-Origin- und Cross-Site-Verhalten werden getestet.

### Datenmodell und Serialisierung

- Bestehende Tabellen, Spalten, Indizes und Primärschlüssel werden nicht ohne separate Entscheidung geändert. Die Passport-13-Standardmigration ist die ausdrücklich freigegebene Ausnahme.
- Anwendungseigene historische Migrationen werden nicht umgeschrieben. Eine neue Schema- oder Datenmigration ist in P1 grundsätzlich nicht autorisiert. Ausgenommen ist der am 9. September 2026 freigegebene Passport-13-Cutover auf das offizielle Client-Schema einschließlich Secret-Hashing, Referenzspalten und Device-Code-Tabelle.
- Migrationen eines Laravel-Erstpakets, die in einer Vorversion automatisch aus dem Paket geladen wurden und laut offiziellem Upgradeleitfaden nun veröffentlicht werden müssen, dürfen unverändert in das Repository übernommen werden. Dies gilt nicht als neues Schema, sofern Dateiname, Inhalt und resultierendes Schema dem bisher automatisch ausgeführten Stand entsprechen. Anzahl und Inhalt werden gegen unbeabsichtigte Paketänderungen getestet. Änderungen an diesen veröffentlichten Dateien sind nicht erlaubt.
- Kann eine neue Laravel-Version das bisherige Zielschema aus einer unveränderten anwendungseigenen Migration wegen geänderter Migrationssemantik nicht mehr reproduzieren, ist dies kein gewöhnlicher Kompatibilitätsfix: Das Fresh-Migration-Gate pausiert und verlangt eine Entscheidung zwischen einer minimalen, nachweislich schemaäquivalenten Quellenkorrektur und einem versionierten Schema-Baseline-Verfahren.
- **Freigegebene Ausnahme vom 9. September 2026:** In `2019_01_19_235903_bigger_local_path.php` werden ausschließlich die bereits zuvor geltenden `nullable`-Attribute von `resources.remote_path` und `resources.local_path` bei `change()` explizit wiederholt. Dies folgt der Laravel-11-Migrationssemantik und darf das resultierende Schema nicht verändern. Vorwärts- und Rückwärtsdefinition sowie Fresh-/Bestandsschema werden darauf geprüft. Weitere Änderungen historischer Migrationen sind dadurch nicht autorisiert.
- Resource-STI behält alle vorhandenen Kurzcodes und Zuordnungen, insbesondere `res`, `link`, `file`, `text`, `book` sowie die Datei-Untertypen.
- `MaterialResource.limitation`, Keyword-/Bibleverse-`relevance`, `options`, Zeitstempel und alle bisher serialisierten Werte bleiben les- und schreibkompatibel.
- Laravel-13-Härtungen für Cache-Serialisierung werden explizit konfiguriert und getestet. Bestehende erlaubte Anwendungsobjekte dürfen nicht stillschweigend unlesbar werden.
- Die Session-Serialisierung verwendet nach einem separat autorisierten Cutover `json`. Bestehende PHP-serialisierte Browser-Sessions werden nicht migriert.
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

Folgende Ziel-Hauptversionen sind vereinbart. Pro Upgrade-Schritt werden grundsätzlich die neueste stabile Framework-Version und die für diese Stufe vorgesehenen neuesten stabilen direkten Paketversionen gelockt. Die verbindliche historische Ausnahme war Stufe 4 mit exakt Laravel 12.0.0. Der zeitweilige Bible-Paket-Commit-Pin wurde mit dem stabilen Release `3.0.0` vollständig aufgelöst. Ein verhaltenssensitives transitives Major-Upgrade darf nur dann bis zur dafür vorgesehenen Stufe festgehalten werden, wenn der Vertrag dies ausdrücklich benennt; Constraint, Grund und späteste Auflösungsstufe werden im Stufenbericht dokumentiert. Bewegliche Dev-Stände sind im Endstand unzulässig.

| Paket/Bereich | Ziel oder Aktion |
| --- | --- |
| `php` | `^8.4` |
| `laravel/framework` | `^13.0` |
| `laravel/passport` | `^13.0` |
| `laravel/tinker` | `^3.0` |
| `laravel/ui` | `^4.0`, ohne Scaffolding |
| `laravel/boost` | stabile 2.x-Version als reine Entwicklungsabhängigkeit ab Laravel 11; kompatibel bis Laravel 13 halten |
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
| `stevenbuehner/bible-verse-bundle` | `^3.0`; gelockt auf den stabilen Tag `3.0.0`/Commit `c9757851ee69220293223728e1db525951e60da8` |
| `setasign/fpdi-fpdf` | ausschließliches, aufgegebenes Composer-Metapaket; nicht mehr als Root-Abhängigkeit deklarieren |
| `setasign/fpdi` | direkte Abhängigkeit `^2.6.8` |
| `setasign/fpdf` | direkte Abhängigkeit `^1.9` |

Medien- und Konvertierungsbibliotheken (`intervention/image`, EXIF/ExifTool, FFmpeg, FPDI/FPDF, PDF-to-text und Office-Konvertierung) werden auf die jeweils neueste stabile PHP-8.4-kompatible Version aktualisiert, soweit ihre bestehende öffentliche API erhalten bleibt. Ein API-brechender Wechsel – insbesondere Intervention Image 2 auf 3 – wird als separates Backend-Teilprojekt behandelt und nur dann in P1 aufgenommen, wenn Laravel 13/PHP 8.4 sonst nicht erreichbar ist. In diesem Fall ist vor der Umsetzung eine neue Entscheidung mit Migrations- und Rückbauplan erforderlich.

### P1-PDF-Abhängigkeitsbereinigung

`setasign/fpdi-fpdf` enthält keine eigene PDF-Implementierung. Es ist ein seit 2020 aufgegebenes Metapaket, das lediglich `setasign/fpdi` und `setasign/fpdf` nachzieht. Die Anwendung verwendet `setasign\\Fpdi\\Fpdi` unmittelbar in `PdfHandlingService::extractPdfPagesInFilepath()`; diese Klasse erbt über `FpdfTpl` von `FPDF`. Deshalb sind `setasign/fpdi` und `setasign/fpdf` die erforderlichen Laufzeitabhängigkeiten, das Metapaket jedoch nicht.

Der verbindliche P1-Schritt ist keine PDF-API-Migration, sondern eine verhaltensneutrale Composer-Bereinigung: `setasign/fpdi-fpdf` entfernen und stattdessen `setasign/fpdi:^2.6.8` sowie `setasign/fpdf:^1.9` direkt deklarieren. Die FPDI-Untergrenze schließt die aktuell bekannten DoS-Advisories der Versionen vor 2.6.7 aus. Öffentliche PDF-Ausgaben, Speicherorte, Routen und Integrationen dürfen sich nicht ändern.

Die Abnahme erfolgt in dieser Reihenfolge: Der Regressionstest `PdfHandlingServiceTest` muss mit dem vorhandenen PDF-Fixture sowohl die explizite als auch die nicht eingeschränkte Seitenauswahl, ein erneut lesbares Ausgabedokument, dessen Seitenformat sowie die Fehlerbehandlung bei einer ungültigen Seite nachweisen. Danach müssen `composer validate --strict`, `composer audit --locked`, ein reproduzierbarer Lockfile-Aufbau, die Autoload-Prüfung der Klassen `setasign\\Fpdi\\Fpdi` und `FPDF` sowie die vollständige PHPUnit-Suite grün sein. Der Lockfile-Diff darf außer dem Entfernen des Metapakets, dem Root-Content-Hash und den beiden direkten Abhängigkeiten keine unbeteiligten Pakete ändern. Bei einer API-, Ausgabe- oder unerwarteten Lockfile-Abweichung pausiert der Schritt und verlangt eine neue Entscheidung; ein API-brechender PDF-Bibliothekswechsel ist nicht autorisiert.

`howtomakeaturn/pdfinfo` bleibt unabhängig von dieser Bereinigung erforderlich: `PdfHandlingService::countPdfPagesInFilepath()` verwendet dessen `PDFInfo` zur Seitenermittlung. `spatie/pdf-to-text` bleibt für die Textextraktion erforderlich. Das Entfernen weiterer PDF-Abhängigkeiten ist nicht Teil dieses P1-Schritts.

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

### Zwischenschritt 3a – Laravel-KI-Unterstützung

**Status:** Am 9. September 2026 auf dem abgeschlossenen Laravel-11-Checkpoint umgesetzt. Technische Entscheidung und Sicherheitsgrenzen: `docs/ai/laravel-ai-support.md`.

- Laravel Boost 2 ausschließlich in `require-dev` installieren und über Sail als lokales MCP für Codex konfigurieren.
- Browser-Instrumentierung, Tinker und das selbstständige Schreiben dauerhafter Regeln deaktivieren.
- Keine Produkt-KI-Funktion, kein Laravel AI SDK und keinen anwendungseigenen MCP-Endpunkt hinzufügen.
- Boost-Kompatibilität vor jeder weiteren Laravel-Stufe prüfen.
- Für den exakten Laravel-12.0.0-Checkpoint Boost vorübergehend auf die stabile Version 1.0.21 zurücksetzen, zusätzliche unsichere Alt-Werkzeuge ausschließen und in Stufe 5 wieder auf Boost 2 wechseln.
- Versionsspezifische Boost-Guidelines und Skills erst nach der Laravel-13-Abnahme und der Aktualisierung von `AGENTS.md` sowie der Baseline-Dokumente erzeugen, damit während der Zwischenstände keine widersprüchlichen Projektanweisungen entstehen.

### Stufe 4 – Laravel 12

**Status:** Am 9. September 2026 als kurzer, ausschließlich lokaler und nicht deploybarer Laravel-12.0.0-Kompatibilitäts-Checkpoint vollständig umgesetzt. Prüfungen, Leitfadenmatrix, temporäre Pins und Audit: `docs/ai/upgrade-stage-4-report.md`.

- Zielruntime PHP 8.4 herstellen.
- Den offiziellen Laravel-12-Upgradeleitfaden online prüfen und vollständig im Stufenbericht abbilden.
- PHPUnit 11 einführen.
- Wegen der Nestedset-6-Obergrenze exakt Laravel 12.0.0 verwenden und diesen Stand nicht deployen; danach unmittelbar auf Laravel 13 mit Nestedset 7 wechseln.
- Backup-Paket und veröffentlichte Konfiguration auf der höchsten mit exakt Laravel 12.0.0 kompatiblen Linie prüfen. Backup 10 folgt in Stufe 5, weil es eine spätere Laravel-12-Patchversion voraussetzt.
- Boost für diesen exakten Checkpoint temporär auf die stabile Version 1.0.21 begrenzen und dessen schwächer abgesicherte Werkzeuge zusätzlich ausschließen; in Stufe 5 wieder auf Boost 2 wechseln.
- Veraltete Framework- und Test-APIs beseitigen, ohne Verträge zu ändern.
- Insbesondere lokale Disk-Roots, SVG-Bildvalidierung, `mergeIfMissing`, Route-Namenspräzedenz und Container-Auflösung prüfen; neue Defaults nur bei nachgewiesen identischem Verhalten übernehmen.

### Stufe 5 – Laravel 13

**Status:** Am 9. September 2026 vollständig umgesetzt und als technischer Laravel-13-/Passport-13-Endstand abgenommen. Leitfadenmatrix, Paketstand, ausgeführte Gates und verbleibende Deployment-Voraussetzungen: `docs/ai/upgrade-stage-5-report.md`.

- Den offiziellen Laravel-13-Upgradeleitfaden sowie den Passport-13-Upgradeleitfaden online prüfen und vollständig im Stufenbericht abbilden.
- `laravel/framework ^13.0`, `laravel/tinker ^3.0`, PHPUnit 12 und alle finalen Paketziele locken.
- Passport 13 einschließlich `OAuthenticatable` integrieren.
- Den freigegebenen Passport-Standard-Cutover umsetzen: bestehende IDs wertgleich nach `CHAR(36)` migrieren, neue Clients als UUID anlegen, Secrets hashen, `owner`/`redirect_uris`/`grant_types` übernehmen und die Device-Code-Tabelle veröffentlichen.
- Die veralteten Passport-JSON-Verwaltungsrouten bleiben gemäß Paketdefault deaktiviert. Passport bleibt headless; eine eigene Authorization-View wird nicht ohne separaten UI-Auftrag eingeführt. Betroffene Clients und der Betriebsablauf werden in `docs/ai/passport-13-client-migration.md` dokumentiert.
- Den CSRF-Middleware-Namenswechsel auf `PreventRequestForgery` modernisieren und die neue Origin-Prüfung über `Sec-Fetch-Site` mit Erfolgs- und Ablehnungstest aktivieren.
- Cache- und Session-Serialisierung sowie Prefix-Kontinuität explizit konfigurieren und testen.
- Cache-Objekte auf eine explizite `serializable_classes`-Allowlist begrenzen. Die Session-Serialisierung verwendet nach dem separat autorisierten Cutover `json`; Cookie- und Cache-Prefixe bleiben unverändert.
- Parental-STI und Nestedset 7 anhand ihrer vollständigen Vertragsgruppen abnehmen.
- `minimum-stability` auf `stable` setzen. Der temporäre Bible-Paket-Commit-Pin wurde nach Bereitstellung des stabilen Tags durch `^3.0` ersetzt; der gelockte Endstand ist `3.0.0`.

## Quality Gates je Hauptversionsschritt

Ein Schritt darf erst begonnen werden, wenn der vorherige vollständig grün ist. Ein fehlgeschlagenes Gate wird repariert oder der Schritt wird auf seinen letzten grünen Commit zurückgesetzt; Tests dürfen nicht abgeschwächt werden, um neue Paketsemantik zu akzeptieren.

1. Online-Prüfung des offiziellen Laravel-Upgradeleitfadens und aller angehobenen Laravel-Erstpakete mit dokumentierter Punkt-für-Punkt-Einordnung.
2. `composer validate --strict` und ein reproduzierbarer Lockfile-Aufbau. Beim exakten Laravel-12.0.0-Messpunkt ist ausschließlich Composers Warnung gegen den bewusst exakten Framework-Constraint akzeptiert; weitere Fehler oder Warnungen blockieren.
3. `composer audit`; offene Findings werden immer dokumentiert. Bei den ausdrücklich nicht deploybaren Laravel-9- bis Laravel-11-Checkpoints und dem exakten Laravel-12.0.0-Messpunkt blockieren ausschließlich durch die gewählte historische Framework-/Passport-Stufe unvermeidbare Findings den lokalen Kompatibilitätsschritt nicht. Andere sicherheitskritische Findings blockieren weiterhin. Der Laravel-13-Endstand darf keine offenen sicherheitskritischen Findings enthalten.
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
- Commits der EOL-Zwischenstände Laravel 9 bis 11 und des exakten Laravel-12.0.0-Messpunkts sind weder Release- noch Deployment-Kandidaten und dürfen nicht in einer erreichbaren produktiven Umgebung betrieben werden.
- Das `composer.lock` wird pro Schritt vollständig geprüft; unerklärte transitive Major-Upgrades blockieren die Abnahme.
- Der Code-Rückbau erfolgt auf den letzten grünen Hauptversions-Checkpoint. Der Passport-Datenbank-Rückbau erfolgt wegen des nicht umkehrbaren Secret-Hashings ausschließlich aus dem unmittelbar vor dem Cutover erstellten Backup. Solange ausschließlich numerische Alt-IDs vorliegen, unterstützt die Migration zwar einen technischen Schema-Down-Pfad, dieser kann die Klartext-Secrets jedoch nicht wiederherstellen.
- Laravel 13 ist erst abgenommen, wenn ein kalter Sail-Start, eine frische Installation aus Lockfiles, die vollständige Suite, alle Spezial-Gates und der bestehende Frontend-Build erfolgreich sind.
- „Frische Installation aus Lockfiles“ bezeichnet in P1 zwingend den Composer-Lockfile-Aufbau. Für npm gilt bis zum separaten Frontend-Upgrade die in Quality Gate 14 beschriebene, dokumentierte Legacy-Ausnahme.
- `AGENTS.md`, `docs/ai/baseline-b8dd716.md`, `docs/ai/architecture.md` und `docs/ai/quality-gates.md` werden nach dem grünen Laravel-13-Implementierungscommit auf die neue verifizierte Basis aktualisiert. Der historische Dateiname `baseline-b8dd716.md` bleibt zur Linkstabilität erhalten; der ursprüngliche Stand ist darin weiterhin nachvollziehbar.

## Nicht Bestandteil von P1

- Vue-3-, Vite-, Bootstrap- oder sonstige Frontendmodernisierung.
- Änderung von UX, Navigation, Design oder sichtbaren Texten.
- Neue Funktionen, API-Versionen oder Datenmodelle.
- Bereinigung, Neuordnung oder Reparatur produktiver Nested Sets.
- Wechsel von Passport zu einem anderen Authentifizierungssystem.
- Die Session-Serialisierung wurde nach dem Laravel-13-Upgrade separat auf JSON umgestellt.
- Ausführung eines Deployments auf einem realen Produktionsserver. Die dafür freigegebene technische Vorbereitung und der verpflichtende Rollout-/Backup-/Rollbackablauf stehen in [`production-deployment-contract.md`](production-deployment-contract.md); reale Zugänge und der nachgewiesene S3-Restore bleiben Voraussetzung.

## Referenzen

- Laravel-13-Upgradeleitfaden: <https://laravel.com/framework/docs/13.x/upgrade>
- Laravel-13-Deploymentleitfaden: <https://laravel.com/framework/docs/13.x/deployment>
- Produktions- und Deploymentvertrag: [`production-deployment-contract.md`](production-deployment-contract.md)
- Laravel-Release- und Supportübersicht: <https://laravel.com/docs/13.x/releases>
- Laravel-9-Upgradeleitfaden: <https://laravel.com/framework/docs/9.x/upgrade>
- Laravel-10-Upgradeleitfaden: <https://laravel.com/framework/docs/10.x/upgrade>
- Laravel-11-Upgradeleitfaden: <https://laravel.com/framework/docs/11.x/upgrade>
- Laravel-12-Upgradeleitfaden: <https://laravel.com/framework/docs/12.x/upgrade>
- Passport-Upgradeleitfaden: <https://github.com/laravel/passport/blob/13.x/UPGRADE.md>
- Laravel Boost: <https://laravel.com/framework/docs/12.x/boost>
- Laravel Boost 2 Upgrade Guide: <https://github.com/laravel/boost/blob/main/UPGRADE.md>
- Aktuelle Laravel-Boost-Kompatibilitäts-Constraints: <https://github.com/laravel/boost/blob/main/composer.json>
- Laravel AI SDK, Boost und MCP im Vergleich: <https://laravel.com/blog/laravel-ai-sdk-boost-or-mcp-which-tool-do-you-need>
- Projektinterne Invarianten: `docs/ai/domain-invariants.md`
- Projektinterne Quality Gates: `docs/ai/quality-gates.md`

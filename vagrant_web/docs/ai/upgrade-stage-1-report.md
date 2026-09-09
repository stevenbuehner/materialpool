# Upgradebericht Stufe 1: Laravel 9

## Ergebnis und Gültigkeit

Stufe 1 wurde am 9. September 2026 mit Laravel 9.52.22 auf PHP 8.1.34 abgeschlossen. Der Stand ist ein lokaler technischer EOL-Kompatibilitäts-Checkpoint und ausdrücklich weder Release- noch Deployment-Kandidat. Die vollständige Suite mit 141 Tests ist gegen die dedizierte MySQL-Datenbank `testing` grün.

Der Schritt verändert keine Datenbankstruktur, API, Berechtigung, fachliche Semantik oder Frontenddefinition. Entwicklungsdatenbank, reguläre Ressourcen- und Archiv-Disks wurden nicht verwendet. Dateioperationen der PHPUnit-Suite liefen auf Fakes; Migration, Backup und Restore liefen ausschließlich in eigens angelegten temporären Datenbanken beziehungsweise unter `/tmp`.

## Laufzeit und zentrale Paketstände

- PHP 8.1.34 im Sail-Image auf Ubuntu 22.04
- Laravel Framework 9.52.22
- Laravel Passport 11.10.6
- Laravel UI 4.6.3, ohne Scaffolding
- Flysystem 3 über Laravel 9
- Spatie Laravel Backup 8.2.0
- PHPUnit 9.6.36
- FakerPHP 1.24.1 statt `fzaninotto/faker`
- Spatie Laravel Ignition 1.7.2 statt `facade/ignition`
- PHP-FFMpeg 1.4.0
- `mariuzzo/laravel-js-localization` 1.10.0, bewusst auf der letzten mit `resources/lang` kompatiblen Version gehalten

## Nachgetragener Online-Leitfadenabgleich

Im Rahmen der Vertragsrevision vom 9. September 2026 wurde der bereits abgeschlossene Checkpoint nochmals online mit dem offiziellen [Laravel-9-Upgradeleitfaden](https://laravel.com/framework/docs/9.x/upgrade) abgeglichen. Die für dieses Repository anwendbaren Hauptpunkte waren PHP 8.0.2, Framework-/Collision-/Ignition-Constraints, Flysystem 3, Symfony Mailer, PHP-Rückgabetypen, Sprachverzeichnis und die Prüfung weiterer Drittanbieterpakete. Diese Punkte entsprechen den unten dokumentierten Paket-, Storage-, Mail-, Signatur- und Übersetzungspfad-Anpassungen. Die übrigen beschriebenen Änderungen wurden im Anwendungscode gesucht und waren entweder nicht betroffen oder durch die vollständige Vertrags-Suite abgedeckt. Diese Nachprüfung ersetzt keine künftig stufenbegleitende Prüfung; ab Stufe 3 wird die Einordnung vor dem Dependency-Wechsel erstellt.

Die wirkungslose Composer-Repository-Definition für einen GitHub-Fork von PHPExifTool wurde entfernt. Bereits zuvor und weiterhin wird `phpexiftool/exiftool` 10.16 aus dessen Upstream-Repository gelockt. Das separat verwaltete `stevenbuehner/bible-verse-bundle` bleibt unverändert auf `dev-develop`; die stabile Ablösung ist gemäß Upgradevertrag Aufgabe einer späteren Stufe nach Bereitstellung durch den Auftraggeber.

## Kompatibilitäts- und Konventionsanpassungen

- Interne Flysystem-1-Adapterzugriffe wurden vollständig auf öffentliche Laravel-/Flysystem-3-APIs (`path`, `size`, `readStream`, `writeStream`, `exists`) umgestellt.
- Flysystem-Ausnahmen verwenden den Laravel-Filesystemvertrag.
- Passport-Routen werden entsprechend Passport 11 durch das Paket registriert; der entfernte manuelle `Passport::routes()`-Aufruf erzeugt keine Routendifferenz.
- Der bisherige Übersetzungspfad `resources/lang` ist explizit gesetzt, damit der neue Laravel-9-Default `/lang` das bestehende Verhalten nicht verändert.
- Das unter PHP 8.1 veraltete `strftime()` wurde für Archivpfade vollständig durch `now()->format('o/m/d/')` mit identischer ISO-Wochenjahrsemantik ersetzt.
- Storage-Assertions prüfen nach dem Löschen den zuvor gespeicherten Pfad. Damit wird nicht versehentlich `exists(null)` und folglich das Disk-Wurzelverzeichnis geprüft.

Weitere Skeleton-, Signatur- oder Typisierungsmodernisierungen wurden nicht nur kosmetisch begonnen. Sie werden erst in der Framework-Stufe umgesetzt, in der der jeweilige zusammenhängende Bereich vollständig migriert und getestet werden kann.

## Ausgeführte Prüfungen

- `composer validate --strict`: erfolgreich.
- Stabiler Lockfile-Aufbau mit `--no-security-blocking`: Laravel 9.52.22 und `firebase/php-jwt` 6.11.1 statt beweglicher Dev-Stände.
- PHP-Syntaxprüfung aller geänderten PHP-Dateien: erfolgreich.
- Vollständige PHPUnit-Suite in Sail gegen MySQL `testing`: 141 Tests bestanden in 52,35 Sekunden.
- Nested-Set-Vertragsgruppe separat: 15 Tests bestanden; Waldstruktur, Grenzen, Reihenfolgen, Verschieben, Merge, beide Löschvarianten, Suche und Serialisierung bleiben erhalten.
- Passport-/Routen-, Resource-STI-, Pivot-, Storage-/Archiv-, Queue-, Bundle-/Bibel-/Backup- und Handler-Verträge: im vollständigen Lauf bestanden.
- Frische Datenbank aus allen 37 historischen Migrationen: erfolgreich in `materialpool_stage1_fresh`.
- Reines Datenbankbackup von `testing`: erfolgreich erzeugt und in `materialpool_stage1_restore` zurückgespielt; 24 Tabellen und 37 Migrationseinträge verifiziert.
- Beide temporären Datenbanken und das temporäre Backup wurden anschließend entfernt.
- Bestehender Production-Build: erfolgreich. `package.json`, `package-lock.json` und `resources/js/lang-js-translation.js` blieben byteidentisch; erzeugte Build-Artefakte wurden nicht übernommen.
- `git diff --check`: erfolgreich.

## Dokumentierte Sicherheitsbefunde

`composer audit --locked` meldet fünf Advisories in zwei Paketen:

- `laravel/framework`: temporäre Signed-URL-Pfadverwechslung (mittel), CRLF-Injection der Standard-E-Mail-Regel (hoch und zusätzlich als CVE-2026-48019 erfasst) sowie File-Validation-Bypass (mittel).
- `firebase/php-jwt` 6.11.1: schwache Verschlüsselung (niedrig); Version 7 ist mit Passport 11 nicht kompatibel.

Diese Befunde sind innerhalb der letzten stabilen Laravel-9-/Passport-11-Versionen unvermeidbar und werden ausschließlich aufgrund der ausdrücklich freigegebenen EOL-Checkpoint-Regel toleriert. Der Stand darf nicht produktiv oder öffentlich erreichbar betrieben werden. Der Audit wurde nicht deaktiviert; nur Composers Auflösungsblockade wurde einmalig für den stabilen Lockfile-Aufbau übergangen.

Composer meldet außerdem `setasign/fpdi-fpdf` als aufgegebenes Paket ohne vorgeschlagenen Ersatz. Dies ist kein neuer Befund aus Anwendungscode und wird nicht ohne gesonderte Paket-/PDF-Vertragsanalyse entfernt.

## Nicht vollständig ausführbares Frontend-Gate

Ein unverändertes `npm ci` scheitert auf der lokalen ARM64-Plattform, weil `phantomjs-prebuilt` 2.1.16 kein passendes Linux-ARM64-Binary anbietet. `npm ci --ignore-scripts` und der anschließende bestehende Production-Build sind erfolgreich. Die npm-Prüfung meldet zudem 129 bekannte Schwachstellen im eingefrorenen Legacy-Frontend-Baum. Eine Behebung würde das ausdrücklich ausgeklammerte Frontend und dessen Lockfile verändern und wurde daher nicht vorgenommen.

## Verbesserungsvorschläge ohne Umsetzungsfreigabe

### Frontend-Installierbarkeit

**Empfehlung:** Im späteren Frontend-Projekt PhantomJS und davon abhängige Testwerkzeuge entfernen oder ersetzen und den npm-Baum vollständig erneuern.

**Alternative:** Für den unveränderten Legacy-Build vorübergehend eine x86_64-Buildumgebung verwenden. Dies erhält den alten Lockfile-Stand, beseitigt aber weder die Abhängigkeit noch deren Sicherheitsbefunde.

**Auswirkung und Rückbau:** Betrifft ausschließlich den später separat freizugebenden Frontend-Upgradeumfang. In Stufe 1 wurde nichts geändert; ein Rückbau ist daher nicht erforderlich.

### PDF-Paket

**Empfehlung:** Vor Laravel 13 die tatsächlichen FPDI-/FPDF-Aufrufstellen und Ausgabe-Artefakte charakterisieren und anschließend auf einzeln gepflegte `setasign/fpdi`- und FPDF-Pakete migrieren, sofern die PDF-Ausgabe byte- oder visuell kompatibel bleibt.

**Alternative:** Das aufgegebene Metapaket bis zum Laravel-13-Checkpoint beibehalten, sofern PHP 8.4 und der Solver dies erlauben. Das reduziert kurzfristiges Risiko, lässt aber die Wartungsschuld bestehen.

**Auswirkung und Rückbau:** Eine Umstellung berührt PDF-Erzeugung und Composer-Abhängigkeiten und benötigt deshalb vorab eine separate Entscheidung sowie PDF-Regressionstests. Rückbau über den vorherigen Paket-Checkpoint.

## Verbleibende Risiken für Stufe 2

- Laravel 10 muss mindestens 10.48.29 erreichen, um den dokumentierten File-Validation-Befund zu schließen; andere EOL-bedingte Advisories können bis Laravel 12 bestehen bleiben.
- `firebase/php-jwt` 7 wird voraussichtlich erst mit einer späteren Passport-Hauptversion erreichbar.
- Das bewegliche Bible-Paket und `minimum-stability: dev` bleiben bis zum vom Auftraggeber bereitgestellten stabilen Release ein Reproduzierbarkeitsrisiko.
- Medienkonvertierung mit PHP-FFMpeg 1.x ist durch Composer-Auflösung und Anwendungstests, aber noch nicht durch einen vollständigen echten Video-/Audio-Konvertierungslauf charakterisiert. Vor einer weiteren API-brechenden Medienbibliotheksänderung ist ein isolierter Medien-Fixture-Test zu ergänzen.

# P1 – Abnahmebericht Stufe 0

## Ergebnis

Stufe 0 des Laravel-13-Upgradevertrags ist am 9. September 2026 umgesetzt. Es wurden keine Laufzeit-, Composer-, Datenbank-, Produktionscode- oder Frontendänderungen vorgenommen.

Die ausführbare Ausgangsbasis umfasst nach Abschluss 141 grüne PHPUnit-Tests. Davon wurden 22 neue Stufe-0-Vertragsfälle für folgende Risikozonen ergänzt:

- vollständige Resource-STI-Typzuordnung, Speicherung und Hydrierung;
- Passport-Guard, anonyme und authentifizierte API-Antwort sowie kritische Anwendungs- und Passport-Routen;
- `options`- und `MaterialResource.limitation`-Serialisierung;
- qualifizierte `local_path`-Werte, Ressourcen- und Archiv-Disks;
- Bundle-Queue-Namen, Queue-Metadaten und serialisierte Job-Payloads;
- PHP- und JavaScript-Verträge des separat verwalteten Bible-Pakets;
- Backup-Quellen, Ziel-Disk, Aufbewahrung, Monitoring und Scheduler;
- vorhandene API-Beispielstrukturen über die bestehenden und neuen Feature-Tests.

Der direkte Composer-Paketstand und kritische Routennamen sind in `docs/ai/upgrade-baseline-laravel8.json` maschinenlesbar eingefroren. Die Anforderungen an den vom Auftraggeber verwalteten Bible-Paket-Release stehen in `docs/ai/bible-verse-bundle-requirements.md`.

## Ausgeführte Prüfungen

- PHP-Syntaxprüfung aller neuen Testdateien: erfolgreich.
- JSON-Validierung der maschinenlesbaren Baseline: erfolgreich.
- `git diff --check`: erfolgreich.
- Stufe-0-Spezialgruppe: 22 Tests erfolgreich.
- Gesamte PHPUnit-Suite in Sail gegen MySQL `testing`: 141 Tests erfolgreich.
- Storage- und Archivprüfungen liefen ausschließlich gegen Laravel-Fakes.
- Scheduler wurde nur ausgelesen; es wurde kein Backup, Cleanup oder Queue-Worker ausgeführt.

## Festgestellte Altverträge

Die folgenden Befunde werden während des Upgrades bewahrt. Sie wurden nicht verändert:

1. STI kennt den gespeicherten Typ `pdf`, während `Resource::$allResourceTypeKeys` ihn nicht enthält. Dadurch wird `pdf` unter anderem nicht wie die dort registrierten Suchtypen behandelt.
2. `Text` ergänzt `content` und `original_filename` erst nach dem Eloquent-Basiskonstruktor zu `$fillable`. Diese Felder werden deshalb bei `new Text([...])` ignoriert, funktionieren aber bei nachträglicher Zuweisung beziehungsweise Factory-`fill()`.
3. `PageLimitation::setPages()` sortiert Seitenzahlen, entfernt Duplikate jedoch nicht.
4. Eine fachlich leere Resource-Limitierung wird im Pivot als PHP-Serialisierung `N;` und nicht als SQL-`NULL` gespeichert; beim Lesen ergibt sie wieder `null`.
5. Mehrere v2-Routennamen besitzen historisch den doppelten Präfix `api.v2.api.v2.*`.
6. Bundle-Queue-Auswertung hängt vom bisherigen serialisierten Laravel-Job-Payload und vom Queue-Namen `bundle_{id}_queue` ab.
7. Das Vue-2-Frontend importiert JavaScript direkt aus zwei Pfaden im Composer-Paket `bible-verse-bundle`.

## Verbesserungsvorschläge – nicht freigegeben und nicht umgesetzt

### A – Typregister konsolidieren

**Empfehlung:** Nach Abschluss von P1 ein einziges Resource-Typregister schaffen und entscheiden, ob `pdf` als öffentlicher Suchtyp ergänzt wird.

**Alternative:** Die heutige Trennung unverändert lassen.

**Auswirkung:** Eine Ergänzung von `pdf` kann Suchergebnisse und UI-Filter verändern und benötigt deshalb eine separate API-/UX-Freigabe. Rückbauaufwand gering, Datenmigration nicht erforderlich.

### B – Dynamische Resource-Attribute konstruktorfest machen

**Empfehlung:** Nach P1 die Initialisierung von `Text` und `File` so ordnen, dass dokumentierte Fillable-Felder auch im Konstruktor funktionieren.

**Alternative:** Nur die nachträgliche Zuweisung als zulässigen Aufruf dokumentieren.

**Auswirkung:** Potenziell bisher ignorierte Eingaben würden künftig wirksam. Das ist eine Verhaltensänderung und benötigt Sicherheits- und Mass-Assignment-Prüfung. Rückbauaufwand gering.

### C – Limitierungen normalisieren

**Empfehlung:** Duplikate in `PageLimitation` künftig entfernen, aber die bestehende `N;`-Serialisierung bis zu einem gesonderten Datenmigrationsprojekt beibehalten.

**Alternative:** Beides unverändert lassen oder SQL-`NULL` samt kontrollierter Datenmigration einführen.

**Auswirkung:** Deduplizierung verändert Ausgabe und gespeicherte Objekte geringfügig; ein Wechsel zu SQL-`NULL` betrifft Bestandsdaten und Serialisierung. Rückbau bei Deduplizierung gering, bei Datenmigration mittel.

### D – Routennamen und Konfiguration bereinigen

**Empfehlung:** Doppelte v2-Routenpräfixe während P1 nicht ändern. Eine spätere neue API-Version kann konsistente Namen einführen. Backup-Empfänger sollten in einem separaten Deployment-/Konfigurationsauftrag externalisiert werden.

**Alternative:** Bestehende Routennamen nach P1 mit Kompatibilitätsaliasen korrigieren.

**Auswirkung:** Routennamen können interne oder externe URL-Erzeugung betreffen. Backup-Konfiguration betrifft Betriebsverhalten. Beides benötigt eine separate Freigabe.

## Nächster autorisierter Schritt

Nach diesem Checkpoint folgt Stufe 1: Laravel 9 mit Flysystem 3, Symfony Mailer und dem Wechsel von `facade/ignition` zu `spatie/laravel-ignition`. Vor ihrer Umsetzung sind die konkreten Composer-Auflösungen und unvermeidbaren Kompatibilitätsänderungen gegen diesen Bericht und den Upgradevertrag zu prüfen.

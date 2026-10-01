# Architekturkarte

## Gültigkeitsbereich

Diese Karte beschreibt den nach der Vue-3-Migration verifizierten Stand auf `master`. Die historische Laravel-13- und Vue-2-Ausgangsbasis bleibt in [`baseline-b8dd716.md`](baseline-b8dd716.md) erhalten; nicht integrierte Inhalte anderer Branches sind keine Grundlage für Entscheidungen.

## Systemüberblick

Materialpool ist eine serverseitig geschützte Materialverwaltung. Laravel 13 stellt klassische Web-Endpunkte und eine versionierte JSON-API bereit. Die Hauptoberfläche ist eine Vue-3-Single-Page-App unter `/vue`; Laravel liefert den SPA-Einstieg und zusätzliche klassische Verwaltungs-/Dateirouten.

```text
Browser → Laravel Web-Routen → Vue 3 SPA (/vue)
       → API v1/v2 → Controller → Requests/Policies → Services/Modelle
                                              ↓
                              Events → Listener/Jobs/Queues/Caches
                                              ↓
                        MySQL + Storage-Disks + Vorschau-Dateien
```

## Laufzeit und Build

Für neue Produktionsinstallationen gilt das [Proxmox-LXC-Profil](../../deployment/README.md): Debian 13, PHP 8.4, Nginx, MariaDB und systemd im Laravel-LXC sowie Qdrant in einem eigenen LXC. Versionierte GitHub-Releases liefern bereits gebaute Vite-Assets. Der bisherige Ubuntu-/MySQL-Server unter `ops/production/` bleibt als Altbetrieb dokumentiert; es gibt keinen automatischen Datenumzug.

- Produktion läuft auf einem einzelnen Ubuntu-24.04-LTS-Server mit Nginx, PHP-FPM `8.4` und MySQL 8 hinter einem externen TLS-Reverse-Proxy. Nginx liefert ausschließlich `public/` aus. Atomare Releases, Shared-Pfade, Proxy-Trust, Queue/Scheduler sowie Backup/Restore sind verbindlich in [`production-deployment-contract.md`](production-deployment-contract.md) festgelegt.
- Docker/Sail ist ausschließlich die lokale Entwicklungs- und Testlaufzeit. Lokales Backend-Referenzsystem ist der PHP-8.4-Sail-Container; ein älteres Host-PHP ist nicht maßgeblich. Sail oder `php artisan serve` sind kein Produktions-Webserver.
- PHPUnit 12.5 testet ausschließlich gegen die dedizierte MySQL-Datenbank `testing`; Ressourcen-, Archiv- und Backup-Dateien werden gefakt oder isoliert.
- Frontend: Vue 3.5, Vue Router 4, Pinia 4, Bootstrap 5, BootstrapVueNext, lokale Materialpool-Adapter, Sass und Vite über `laravel-vite-plugin`.
- `@vue/compat`, Vuex, BootstrapVue, Webpack und Laravel Mix sind entfernt. Der Verlauf, die Abnahmegrenzen und Rücksprungpunkte stehen im [`Vue-3-Migrationsvertrag`](vue-3-migration-contract.md) und den Stufenberichten.
- Paketdefinitionen: `composer.json`, `package.json`; Lock-Dateien sind Teil des reproduzierbaren Builds.
- `npm run build` erzeugt das Produktionsbundle und führt vorher `php artisan lang:js -c --no-lib` aus.

## Einstiegspunkte

| Bereich | Einstieg |
| --- | --- |
| Browser-SPA | `routes/web.php` → `/vue/{vue_capture?}` → `resources/views/vuerouter/index.blade.php` |
| Vue-Anwendung | `resources/js/apps/main/index.js`, Router in `routes.js`, Pinia-Stores in `stores/` |
| JSON-API | `routes/api.php`, Controller unter `app/Http/Controllers/Api/` |
| Klassische Oberfläche | Controller unter `app/Http/Controllers/` und Blade-Views |
| Domänenlogik | `app/Services/`, `app/ResourceLimitations/`, `app/Models/` |
| Nebenläufigkeit | `app/Jobs/`, `app/Events/`, `app/Listeners/`, `app/Console/` |

## Kernmodell

- **Material** ist der inhaltliche Eintrag: Titel, Beschreibung, Bewertung, Herkunft und Beziehungen zu Ressourcen, Schlagwörtern und Bibelstellen.
- **Resource** ist ein wiederverwendbarer Träger. Single-Table-Inheritance wird mit Tighten Parental umgesetzt und unterscheidet u. a. URL, Text und Datei; Dateien haben wiederum Medien-Subtypen (Bild, Audio, Video, Dokument, PDF). Gespeicherte Typcodes bleiben unverändert.
- **Keyword** ist ein hierarchischer Nested-Set-Baum (`key`, `person`, `place`, `lang`). Die Zuordnung zu Materialien hat eine Relevanz im Pivot.
- **Bibleverse** wird Materialien ebenfalls mit Relevanz zugeordnet. Bibelinhalt und Querverweise bilden einen separaten Datenbereich.
- **Bundle** repräsentiert importierbare externe Inhalte. `ForeignMaterialId` und `ForeignResourceId` bilden die Zuordnung äußerer IDs zu lokalen Entitäten ab.
- **MaterialUsage** protokolliert Nutzungen eines Materials.

Details und unveränderliche Beziehungen: `docs/ai/domain-invariants.md`.

## Kritische Prozessketten

### Resource anlegen oder ändern

Controller-/Trait-Logik verarbeitet Daten und Dateien, erzeugt `ResourceWasCreated` oder `ResourceWasChanged`. Listener aktualisieren Hashes, Medienmetadaten, Dublettenprüfungen und Vorschau-Caches. Nach erfolgreichem Commit plant ein deduplizierter Job fehlende kleine Resource-Vorschauen gemäß `app.preview.small` auf `resource-previews-low`; für PDFs und Dokumente wird jede bekannte Seite als eigene kleine Variante eingeplant. Große Varianten gemäß `app.preview.large` entstehen ausschließlich bei einer Detail- oder Zoomanfrage. Beide Profile definieren maximale Breite und Höhe, PDF-Auflösung, Qualität und Ausgabeformat; die gemeinsamen Cache-Werte liegen unter `app.preview`. Bei Änderungen wird zusätzlich das kleine Vorschaubild jedes zugeordneten Materials eingeplant, sofern die geänderte Resource dessen tatsächlich verwendete Preview-Quelle ist. Der Worker konsumiert weiterhin `default` vor dieser niedrigen Queue. Änderungen müssen diese Kette erhalten.

### Material ändern oder Ressourcen zuordnen

Material- und Relation-Services lösen `MaterialWas…`, `ResourceWasAttached` und `ResourceWasDetached` aus. Listener invalidieren Material- oder Ressourcen-Vorschauen; Jobs bereinigen verwaiste Ressourcen, Keywords und Bibelstellen.

### Bundle-Import

`BundleImportController` baut eine bundle-spezifische Queue `bundle_{id}_queue` auf. Der Proxmox-Hintergrunddienst verarbeitet aktive Bundle-Läufe vor der Vorschau-Queue; die Browser-API zeigt nur noch den Fortschritt. Jobs legen Ressourcen/Materialien an, aktualisieren Foreign-ID-Mappings und finalisieren Import oder Deinstallation. Das Löschen oder Umordnen dieser Jobs verändert Datenbestände und verlangt Freigabe.

Die geplante Härtung von Laufsteuerung, Laravel-Batches, Wiederaufnahme und bundlebezogenen Leserechten ist im [Bundle-Import-Updatevertrag](bundle-import-update-contract.md) beschrieben. Der Vertrag ist noch nicht implementiert und wird erst nach Bestätigung seiner offenen Entscheidungen verbindliche Zielarchitektur.

## Persistenz und Speicher

Material-ZIPs werden asynchron auf einer eigenen Datenbank-Queue erzeugt. Dateien, Statusdaten und Arbeitsdateien liegen getrennt unter `storage/app/material-downloads`; nur fertige ZIP-Dateien werden über eine streng eingeschränkte Nginx-`alias`-Route mit geheimem Token direkt ausgeliefert. Verzögerte Löschjobs auf `default` und ein täglicher Abgleich entfernen abgelaufene Downloads. Die abgeleiteten ZIP-Dateien gehören nicht in reguläre Dateibackups.

- Die primären Tabellen entstehen aus `database/migrations/`; bestehende Migrationen sind historische Fakten. Änderungen benötigen Freigabe. Dokumentierte Ausnahmen des Laravel-Upgrades sind die schemaäquivalente `nullable()`-Korrektur und die neuen Passport-13-Cutover-/Device-Code-Migrationen.
- `config/filesystems.php` definiert relevante Disks: `resources`, `archive`, `bundles`, `local_tmp`, `backup`, `backup_s3` und `testfiles`. `backup_s3` ist ausschließlich Offsite-Backupziel; Ressourcen bleiben lokal.
- Bei Dateien ist `resources.local_path` ein persistenter Vertrag: üblicherweise `disk::relative/path`. Speicherpfade, Archivierung und Löschverhalten nie isoliert ändern.
- `resources.filesize` speichert die Byteanzahl lokaler Dateien beziehungsweise von Textinhalten. Reine Remote-Ressourcen verwenden `NULL`; API- und Frontend-Lesezugriffe dürfen die Größe nicht aus dem Storage oder Netzwerk nachladen. Altbestände werden nach einer Migration explizit mit `resources:backfill-filesizes` über die Default-Queue ergänzt.
- Preview- und Cache-Dateien sind abgeleitet, aber ihre Invalidation ist Teil des sichtbaren Verhaltens. Resource- und Material-Preview-Schlüssel werden je Entität registriert, damit Änderungen und Löschungen auch Seiten-, kleine, große sowie nichtstandardisierte Größen vollständig entfernen können. Die Controller liefern vorhandene JPEG-Bytes ohne erneute Bilddekodierung aus; ein Varianten-Lock bündelt parallele Cache-Misses. Geschützte Bildantworten sind für sieben Tage nur im privaten Browser-Cache zulässig und tragen einen ETag.

## Authentifizierung und Berechtigungen

- Browser-Zugang ist geschützt; die SPA-Route nutzt `auth`.
- APIs verwenden Laravel Passport 13 (`auth:api`) und teilweise explizite Policies (`can:*`). OAuth-Clients nutzen das Passport-13-Schema mit UUID-fähigen IDs, gehashten Secrets, Owner-, Redirect- und Grant-Feldern.
- Passport nutzt anwendungseigene, servergerenderte Consent- und Device-Code-Views über die offiziellen Passport-13-View-Hooks; die veralteten JSON-Verwaltungsrouten bleiben deaktiviert. Der Material Grabber nutzt weiterhin den ausdrücklich aktivierten Password Grant. Token-Laufzeiten, `CreateFreshApiToken` und `materialpool_token` bleiben kompatibel zum bisherigen Betrieb.
- Policies liegen unter `app/Policies/`; Guards/Provider stehen in `config/auth.php`.
- Jede neue oder veränderte API-Aktion benötigt eine explizite Autorisierungsprüfung, passende Tests und Freigabe, wenn sich Sichtbarkeit oder Berechtigung ändert.

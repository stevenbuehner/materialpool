# Architekturkarte

## Gültigkeitsbereich

Diese Karte ist gegen den Commit [`b8dd716`](baseline-b8dd716.md) auf `master` verifiziert. Sie ist ausdrücklich eine **Vue-2-Referenz**. Erkenntnisse oder Dateien aus `develop` und `Vue3_Upgrade` sind keine Grundlage für Architektur-, Design- oder Umsetzungsentscheidungen, bis sie separat beauftragt und freigegeben werden.

## Systemüberblick

Materialpool ist eine serverseitig geschützte Materialverwaltung. Laravel 8 stellt klassische Web-Endpunkte und eine versionierte JSON-API bereit. Die Hauptoberfläche ist eine Vue-2-Single-Page-App unter `/vue`; Laravel liefert lediglich den SPA-Einstieg und zusätzliche klassische Verwaltungs-/Dateirouten.

```text
Browser → Laravel Web-Routen → Vue 2 SPA (/vue)
       → API v1/v2 → Controller → Requests/Policies → Services/Modelle
                                              ↓
                              Events → Listener/Jobs/Queues/Caches
                                              ↓
                        MySQL + Storage-Disks + Vorschau-Dateien
```

## Laufzeit und Build

- PHP `^7.4`, Laravel `^8.0`, MySQL 8, Docker/Sail-Konfiguration in `docker-compose.yml`.
- Frontend: Vue 2, Vuex 3, Vue Router 3, Bootstrap 4, Bootstrap-Vue, Sass und Webpack/Laravel Mix.
- Paketdefinitionen: `composer.json`, `package.json`; Lock-Dateien sind Teil des reproduzierbaren Builds.
- `npm run build` erzeugt das Produktionsbundle und führt vorher `php artisan lang:js -c --no-lib` aus.

## Einstiegspunkte

| Bereich | Einstieg |
| --- | --- |
| Browser-SPA | `routes/web.php` → `/vue/{vue_capture?}` → `resources/views/vuerouter/index.blade.php` |
| Vue-Anwendung | `resources/js/apps/main/index.js`, Router in `routes.js`, Store in `store/` |
| JSON-API | `routes/api.php`, Controller unter `app/Http/Controllers/Api/` |
| Klassische Oberfläche | Controller unter `app/Http/Controllers/` und Blade-Views |
| Domänenlogik | `app/Services/`, `app/ResourceLimitations/`, `app/Models/` |
| Nebenläufigkeit | `app/Jobs/`, `app/Events/`, `app/Listeners/`, `app/Console/` |

## Kernmodell

- **Material** ist der inhaltliche Eintrag: Titel, Beschreibung, Bewertung, Herkunft und Beziehungen zu Ressourcen, Schlagwörtern und Bibelstellen.
- **Resource** ist ein wiederverwendbarer Träger. Single-Table-Inheritance unterscheidet u. a. URL, Text und Datei; Dateien haben wiederum Medien-Subtypen (Bild, Audio, Video, Dokument, PDF).
- **Keyword** ist ein hierarchischer Nested-Set-Baum (`key`, `person`, `place`, `lang`). Die Zuordnung zu Materialien hat eine Relevanz im Pivot.
- **Bibleverse** wird Materialien ebenfalls mit Relevanz zugeordnet. Bibelinhalt und Querverweise bilden einen separaten Datenbereich.
- **Bundle** repräsentiert importierbare externe Inhalte. `ForeignMaterialId` und `ForeignResourceId` bilden die Zuordnung äußerer IDs zu lokalen Entitäten ab.
- **MaterialUsage** protokolliert Nutzungen eines Materials.

Details und unveränderliche Beziehungen: `docs/ai/domain-invariants.md`.

## Kritische Prozessketten

### Resource anlegen oder ändern

Controller-/Trait-Logik verarbeitet Daten und Dateien, erzeugt `ResourceWasCreated` oder `ResourceWasChanged`. Listener aktualisieren Hashes, Medienmetadaten, Dublettenprüfungen und Vorschau-Caches. Änderungen müssen diese Kette erhalten.

### Material ändern oder Ressourcen zuordnen

Material- und Relation-Services lösen `MaterialWas…`, `ResourceWasAttached` und `ResourceWasDetached` aus. Listener invalidieren Material- oder Ressourcen-Vorschauen; Jobs bereinigen verwaiste Ressourcen, Keywords und Bibelstellen.

### Bundle-Import

`BundleImportController` baut eine bundle-spezifische Queue `bundle_{id}_queue` auf. Jobs legen Ressourcen/Materialien an, aktualisieren Foreign-ID-Mappings und finalisieren Import oder Deinstallation. Das Löschen oder Umordnen dieser Jobs verändert Datenbestände und verlangt Freigabe.

## Persistenz und Speicher

- Die primären Tabellen entstehen aus `database/migrations/`; bestehende Migrationen sind historische Fakten und werden nicht geändert.
- `config/filesystems.php` definiert relevante Disks: `resources`, `archive`, `bundles`, `local_tmp`, `backup` und `testfiles`.
- Bei Dateien ist `resources.local_path` ein persistenter Vertrag: üblicherweise `disk::relative/path`. Speicherpfade, Archivierung und Löschverhalten nie isoliert ändern.
- Preview- und Cache-Dateien sind abgeleitet, aber ihre Invalidation ist Teil des sichtbaren Verhaltens.

## Authentifizierung und Berechtigungen

- Browser-Zugang ist geschützt; die SPA-Route nutzt `auth`.
- APIs verwenden Laravel Passport (`auth:api`) und teilweise explizite Policies (`can:*`).
- Policies liegen unter `app/Policies/`; Guards/Provider stehen in `config/auth.php`.
- Jede neue oder veränderte API-Aktion benötigt eine explizite Autorisierungsprüfung, passende Tests und Freigabe, wenn sich Sichtbarkeit oder Berechtigung ändert.

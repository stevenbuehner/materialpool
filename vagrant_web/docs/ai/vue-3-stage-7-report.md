# Vue-3-Migration – Stufe 7: Konsolidierung und Releasegate

Status: Technisch konsolidiert; **noch nicht releasefreigegeben**. Der Vue-3-/Pinia-/Vite-Endstand ist funktionsfähig und reproduzierbar. Die Releasefreigabe bleibt durch fünf High-Security-Befunde ohne erlaubten Nicht-Major-Fix sowie die noch nicht gewählte CI-Plattform gesperrt. Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md).

## Konsolidierter Endstand

- Vue 3.5.42, Vue Router 4.6.4 und Pinia 4.0.3 bilden die einzige Frontend-Laufzeit. Vue 2, `@vue/compat` und Vuex sind weder in Source noch Dependency-Tree oder Lockfile vorhanden.
- Bootstrap 5.3.8, BootstrapVueNext 1.1.0 und die lokalen Materialpool-Adapter bewahren die belegten Bootstrap-4-Verträge. Die Adapter sind eine dauerhafte Abstraktionsgrenze für Props, Emits, Slots, Tastatur/Fokus und visuelle Parität.
- Vite wurde ohne Major-Wechsel von 8.2.2 auf 8.3.0, date-fns von 4.1.0 auf 4.4.0 aktualisiert. `@vitejs/plugin-vue` 6.0.8 und `laravel-vite-plugin` 3.2.0 akzeptieren Vite 8 laut den am 11. September 2026 abgerufenen npm-Peer-Metadaten. Weitere von `npm outdated` gemeldete Ziele sind Major-Wechsel oder im Fall von `vue-star-rating` ein niedrigerer `latest`-Tag und wurden nicht automatisch übernommen.
- `npm dedupe` reduzierte die Installation von 271 auf 261 auditierte Pakete, ohne direkte Abhängigkeit zu entfernen. `package-lock.json` bleibt die installierbare Quelle; das neue `npm run inventory:dependencies` erzeugt daraus ein CycloneDX-1.5-SBOM. Der geprüfte Lauf enthält 255 Komponenten und 256 Dependency-Knoten.
- ESLint verwendet nun `eslint-plugin-vue` mit dem Vue-3-Profil `flat/essential`, nicht mehr das historische Vue-2-Profil. `npm run test:ci` bündelt Lint und Vitest providerneutral. Weil das Repository weder Remote noch CI-Konfiguration enthält, ist die konkrete Einbindung in GitHub Actions, GitLab CI oder ein anderes System erst nach Auswahl des Zielsystems möglich.

## Adapter- und Übergangscode-Audit

Alle 60 Dateien unter `resources/js/adapters/` besitzen mindestens eine Source- oder Testreferenz, direkt, über `adapters/bootstrap.js` oder als interner Teil eines Adapters. Der Production-Build löst diese Kette erfolgreich auf. Es wurde deshalb kein Adapter allein aufgrund seines Namens als vermeintlicher Übergangscode gelöscht. Das entspricht dem Vertrag: Die lokalen Adapter kapseln bewusst Drittanbieter und sind auch im Vue-3-Endzustand wartbare Anwendungsgrenzen.

Repositoryweite Scans finden keine Vue-2-/Compat-Muster, keine Vuex-Imports und keinen `$store`-Zugriff. Das Frontend-Inventar zählt 85 JavaScript- und 139 Vue-Dateien, insgesamt 224.

## Isolierter Datenbank- und Seeder-Nachweis

Vor dem destruktiven Gate wurde read-only nachgewiesen, dass `materialpool` und `testing` getrennte MySQL-Schemas sind. Eine wichtige Vertragskorrektur folgt daraus: `--env=testing` allein fällt ohne `.env.testing` auf Werte aus `.env` zurück und ist kein Isolationsnachweis. Die dokumentierten Befehle setzen deshalb `APP_ENV`, `DB_CONNECTION`, `DB_HOST` und `DB_DATABASE` explizit im Sail-Container.

`migrate:fresh` und `db:seed` liefen anschließend erfolgreich ausschließlich gegen `testing`. Der erste Seed-Versuch deckte einen PHP-8-Typfehler bei einem fehlenden Dateistream auf. `ResourceHashProcessor` erkennt nun jedes Nicht-Resource-Ergebnis kontrolliert als `ResourceNotHashable` und schließt gültige Streams zuverlässig; zwei Regressionstests sichern beide Pfade. Der Fix ist als `eae0272b` isoliert rückbaubar.

Read-only Assertions nach dem erfolgreichen Lauf ergaben ausschließlich aggregierte Testdaten:

| Vertrag | Nachweis |
| --- | ---: |
| Benutzer / Admins | 11 / 1 |
| OAuth-Clients / UUID / Hash / Password Grant | 1 / 1 / 1 / 1 |
| Resources / Typen / Hashes | 35 / 7 / 35 |
| Materials / Keywords | 32 / 43 |
| Bibeln / Bibelinhalte / Verse / Querverweise | 4 / 78.207 / 10 / 343.600 |
| Material–Resource / Keyword–Material / Bibleverse–Material | 27 / 40 / 10 |

Zufällige IDs, Inhalte und das einmalige Test-Client-Secret wurden nicht protokolliert. Eine zusätzliche Laravel/MySQL-Vertragsreise prüft mit synthetischen Daten authentifiziertes Lesen, Speichern, ein echtes persistentes Neuladen sowie reale 422- und 403-Antworten. Die gemockten Browserreisen bleiben für UI-Grenzfälle bestehen, ersetzen diesen Backend-Nachweis aber nicht.

## Dependency- und Security-Audit

`npm audit` und `npm audit --omit=dev` melden jeweils 10 Paketbefunde: 1 low, 4 moderate, 5 high, 0 critical. Die relevanten Pfade sind:

- Axios 0.21.4 ist eine direkte Browserabhängigkeit; npm bietet Axios 1.20.0 als Fix an und kennzeichnet ihn als SemVer-Major.
- DOMPurify 2.5.9 schützt die vorhandenen Markdown-/`v-html`-Pfade; npm bietet DOMPurify 3.4.15 als SemVer-Major-Fix an.
- `@videojs/themes` zieht `postcss-inline-svg`, eine alte PostCSS-/Selector-Kette ohne angebotenen Fix. Sie verarbeitet statische Theme-CSS im Build; die Abhängigkeit bleibt dennoch Teil des Production-Graphen und wird nicht als folgenlos ausgeblendet.
- Weitere transitive Befunde hängen unter diesen Pfaden. Ein pauschales `npm audit fix --force` wurde nicht ausgeführt.

Die Sicherheitslage hat sich gegenüber der Stufe-0-Baseline mit 114 Befunden, darunter 15 critical und 47 high, deutlich verbessert. Der Zielvertrag erlaubt trotzdem keine offenen High-Befunde ohne explizite befristete Ausnahme. Da der Auftrag Major-Upgrades ausschließt, bleibt das Releasegate geschlossen. Empfehlung ist ein eigener, testgedeckter Security-Schritt für Axios 1 und DOMPurify 3 sowie der Ersatz oder die lokale statische Übernahme des tatsächlich verwendeten Video.js-Themes; Alternative ist ausschließlich eine dokumentierte, befristete Risikoakzeptanz mit Owner und Frist.

## Bundle- und Laufzeitmessung

Die Stufe-0-Referenz dokumentiert nur rohe Assetgrößen: 2.351.395 Bytes JavaScript und 339.376 Bytes CSS. Der aktuelle initiale Production-Graph umfasst dedupliziert:

| Assetart | Stufe 0 roh | Stufe 7 roh | Änderung | Stufe 7 gzip |
| --- | ---: | ---: | ---: | ---: |
| JavaScript | 2.351.395 B | 1.829.282 B | −22,2 % | 550.417 B |
| CSS | 339.376 B | 387.934 B | +14,3 % | 61.066 B |
| Gesamt | 2.690.771 B | 2.217.216 B | −17,6 % | 611.483 B |

Das CSS-Wachstum überschreitet die 10-%-Prüfschwelle. Es ist durch den freigegebenen gemeinsamen Bootstrap-5-Wechsel, die Materialpool-Kompatibilitätsschicht und komponentenspezifische Styles erklärbar; gleichzeitig sinkt der gesamte rohe Initialgraph um 17,6 %. Die unveränderten Visualreferenzen belegen, dass daraus kein Redesign entstand. Eine weitere CSS-Reduktion ohne Selektor-/Visualanalyse wäre riskanter als der belegte Größenanstieg und wurde nicht mechanisch vorgenommen.

Drei vergleichbare Desktop-WebKit-Läufe gegen denselben Production-Build und dieselben synthetischen Fixtures ergaben:

| Reise | Läufe | Median | Streuung |
| --- | --- | ---: | ---: |
| App-Start, Navigation, Suche und Modal | 2.338 / 2.398 / 2.731 ms | 2.398 ms | 393 ms |
| asynchroner Select | 1.183 / 1.195 / 1.223 ms | 1.195 ms | 40 ms |
| Assign-App mit verschachtelten Bilddialogen | 6.135 / 6.138 / 6.256 ms | 6.138 ms | 121 ms |

Stufe 0 enthält keine entsprechenden Laufzeitwerte. Eine prozentuale Laufzeitänderung kann deshalb rückwirkend nicht seriös berechnet werden; die aktuellen Werte sind die neue reproduzierbare Referenz. Diese fehlende historische Messung wird nicht als bestandener Vergleich ausgegeben.

## Abnahme und bekannte Hinweise

Die vollständigen Endgates sind auf dem abschließenden Lockstand grün: frisches `npm ci --ignore-scripts`; Vue-3-Lint mit 0 Fehlern; 34 Vitest-Dateien mit 129 Tests; Production-Build mit 952 Modulen und ausschließlich gehashten Manifest-Assets; 26 funktionale sowie 2 visuelle WebKit-Prüfungen auf Desktop und Mobile; Frontend- und CycloneDX-SBOM-Inventar; 124 auflösbare Laravel-Routen; Shell-Syntax der Produktionsskripte; Composer-Validierung sowie 170 Sail-PHPUnit-Tests mit 1.658 Assertions. `composer audit --locked` meldet keine bekannte PHP-Sicherheitslücke, aber weiterhin das bereits dokumentierte aufgegebene Paket `setasign/fpdi-fpdf` ohne vorgeschlagenen Ersatz.

Bekannte, nicht stillschweigend unterdrückte Hinweise:

- ESLint hat 0 Fehler und 196 historische Warnungen. Das Vue-3-Regelprofil ist aktiv; ein repositoryweiter Formatierungs-/Warnungsumbau wäre ein unprüfbarer Rauschcommit und ist nicht Teil dieser Stufe.
- Sass meldet weiterhin `@import`- und Bootstrap-Upstream-Deprecations sowie den bewusst paritätischen `xxl`-Wert. Dart Sass 3 oder eine visuell wirksame Breakpointänderung sind separate Major-/Designentscheidungen.
- Vite meldet den Hauptchunk über 500 kB. Die initialen Gesamtbytes sind gegenüber Stufe 0 gesunken; fachliche Code-Splitting-Grenzen erfordern einen eigenen Laufzeit-/Caching-Schritt.
- Eine nach einem abgebrochenen Dev-Prozess verbliebene ignorierte `public/hot`-Datei wurde erkannt und entfernt. Der production-artige Browserlauf lädt danach ausschließlich gehashte Manifest-Assets.

## Rückbau und Freigabeentscheidung

Der Stufe-7-Konsolidierungscommit kann gemeinsam auf `9ba8248a` zurückgebaut werden; der davor liegende Seeder-Fix ist separat über `eae0272b` rückbaubar. Es gibt keine Datenmigration und keine Änderung produktiver Daten.

Für tatsächliche Releasefähigkeit fehlen zwei Entscheidungen:

1. **Empfehlung: Security-Majors separat umsetzen.** Axios 1, DOMPurify 3 und die Video.js-Theme-Kette mit ihren jeweiligen Verträgen und vollständigen Gates migrieren. Alternative: befristete Risikoausnahme mit Advisory, Exploitierbarkeit, Kompensation, Owner und Datum.
2. **CI-Ziel festlegen.** Empfehlung: das bereits verwendete Hosting-System wählen und dort `npm ci --ignore-scripts` sowie `npm run test:ci` verpflichtend ausführen. Ohne konfigurierten Remote kann im Repository kein Anbieter faktenbasiert angenommen werden.

Bis beide Punkte erfüllt oder formal akzeptiert sind, ist „Vue-3-Migration technisch abgeschlossen“ korrekt, „releasefähig“ dagegen nicht.

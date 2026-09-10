# Vue-3-Migration – Stufe 0: Baseline

Status: Teilcheckpoint „Werkzeuge und öffentliche Login-Referenz“ abgeschlossen; geschützte Referenzreisen offen

Diese Datei protokolliert die reproduzierbare technische und visuelle Ausgangslage vor den migrationsbedingten Änderungen. Sie ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md).

## Laufzeit und Werkzeuge

- Referenzumgebung: Laravel Sail; Host-PHP ist nicht maßgeblich.
- Festgelegtes Endziel: Node.js 24.21.0 LTS mit npm 11.19.0. Node 24 wird laut Node.js bis Ende April 2028 gepflegt; Vite 8, Laravel Vite Plugin 3 und Vue Plugin 6 verlangen gemeinsam Node `^20.19.0 || >=22.12.0` und schließen Node 24 daher ein.
- Der lokale Baseline-Build wurde zusätzlich unter Node 26.8.1/npm 11.19.0 ausgeführt. Das belegt Kompatibilität der Legacy-Referenz, ersetzt aber nicht den späteren Releasegate-Lauf unter der festgelegten LTS-Version.
- Frontend-Inventur: `npm run inventory:frontend`
- JavaScript-Lint: `npm run lint`
- JavaScript-Unit-Tests: `npm run test:unit`
- Browser-Smoke-Tests: `npm run test:e2e`
- Visuelle Browser-Baseline: `npm run test:visual -- --update-snapshots`
- Production-Build: `npm run build`

## Sicherheitsbaseline

Vor Beginn von Stufe 0 meldete `npm audit --json` 116 bekannte Findings: 18 niedrig, 35 mittel, 48 hoch und 15 kritisch. Nach Installation der ausschließlich für die Baseline benötigten Entwicklungswerkzeuge waren es 114 Findings: 18 niedrig, 34 mittel, 47 hoch und 15 kritisch. Diese Werte sind keine Freigabe; jede Migrationsstufe darf die Lage nicht unbemerkt verschlechtern und muss direkte Findings priorisieren.

## Verifizierte Zielmatrix am 10. September 2026

Die Werte stammen aus den npm-Registry-Metadaten der jeweils genannten stabilen Version. Sie werden bei Beginn der eigentlichen Stufe nochmals geprüft und dann im Lockfile eingefroren.

| Paket | verifizierte Zielversion | relevante Grenze |
|---|---:|---|
| `vue` / `@vue/compat` | 3.5.42 | Compat verlangt exakt Vue 3.5.42 |
| `vue-router` | 4.6.4 | Vue `^3.5.0` |
| `vuex` | 4.1.0 | Vue `^3.2.0` |
| `vue-loader` | 17.4.2 | Webpack `^4.1.0 || ^5.0.0-0` |
| `bootstrap-vue-next` | 1.1.0 | Vue `^3.5.13`, Bootstrap `^5.3.0` sowie dokumentierte weitere Peers |
| `vite` | 8.2.2 | Node `^20.19.0 || >=22.12.0` |
| `laravel-vite-plugin` | 3.2.0 | Vite `^8.0.0`, gleiche Node-Grenze |
| `@vitejs/plugin-vue` | 6.0.8 | Vue `^3.2.25`, Vite 5–8, gleiche Node-Grenze |

## Messbare Vue-2-Referenz

- 163 Frontend-Quelldateien: 63 JavaScript- und 100 Vue-Dateien.
- 16 registrierte Vuex-Module.
- 38 Dateien mit direkten BootstrapVue-Imports.
- 36 Dateien mit Event-Emission/-Abonnement, 5 Filterdefinitionen, 2 Dateien mit alter Scoped-Slot-Syntax, 7 Dateien mit `Vue.set`/`Vue.delete`, 2 Dateien mit `v-html`, eine Renderfunktion und eine Laufzeit-Template-Erzeugung.
- ESLint analysiert den gesamten Bestand ohne Parserfehler. 378 bestehende Befunde sind als Warnungsbaseline sichtbar; neue Tool- und Testdateien werden mit Fehlerniveau geprüft.
- Production-Bundle am Checkpoint: `public/js/main_build.js` 2.351.395 Bytes, `public/css/main.css` 339.376 Bytes.
- Lockfile-SHA-256: `b0170160ef1e6710ca2249ac0afe1d97e12244d045370119d5f98a86cc98a366`.

## Ausgeführte Prüfungen

- `npm run inventory:frontend`: erfolgreich und auf die oben genannten Kernzahlen geprüft.
- `npm run lint`: erfolgreich, 0 Fehler und 378 dokumentierte Legacy-Warnungen.
- `npm run test:unit`: 2 Dateien, 5 Tests erfolgreich.
- `npm run build`: erfolgreich; vorhandene Dart-Sass- und Bundlegrößenwarnungen bleiben klassifiziert.
- `php artisan test tests/Unit`: 7 Tests mit 37 Assertions erfolgreich unter Host-PHP 8.5.10; nicht die Referenzumgebung.
- `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8003 npm run test:e2e`: Login-Smoke-Test in WebKit 26.6 bei 1440×900 und 390×844 erfolgreich.
- Visuelle Login-Baseline aktualisiert, unmittelbar ein zweites Mal erfolgreich verglichen und manuell geprüft. Das externe Legacy-Stylesheet lädt Raleway von Google; der Test beantwortet diesen Request absichtlich mit leerem CSS, damit Netzwerk und Drittanbieterinhalt die Referenz nicht verändern. Die lokale Entfernung dieses externen Laufzeitvertrags wird in einer späteren Stufe separat behandelt.

## Offene Umgebungsgrenze

Die vollständige Backend-Suite wurde auf dem Host angestoßen: 17 Tests liefen erfolgreich, 148 brachen ausschließlich beim Verbindungsaufbau zum Docker-DNS-Namen `mysql` ab. Sail meldet, dass Docker/Podman nicht läuft. Docker Desktop konnte wegen des gesperrten Macs nicht per UI gestartet werden. Deshalb sind geschützte synthetische Nutzerreisen und die Sail-Gesamtsuite noch kein grünes Stufe-0-Abnahmegate. Es wurden keine Entwicklungsdatenbank und keine realen Nutzerdaten verwendet.

## Noch zu schließende Gates

- Docker Desktop bei entsperrtem Mac starten und die vollständige Suite in Sail ausführen.
- Ausschließlich synthetische Browser-Testdaten bereitstellen und die geschützten Referenzreisen für Suche, Material/Resource, Keywords, Bibelstellen, Upload, Vorschau, Zuweisung, Bundles und Berechtigungsfehler aufnehmen.
- Browserkonsole dieser Reisen klassifizieren und Zustandsmatrizen vervollständigen.
- Den isolierten Compat-/Router-/Vuex-/Vue-Loader-/BootstrapVueNext-Machbarkeitsversuch ausführen.

Stufe 0 wird erst nach diesen Gates insgesamt auf „abgeschlossen“ gesetzt. Der vorliegende Teilcheckpoint ist eigenständig rückbaubar und verändert keine Produktfunktion.

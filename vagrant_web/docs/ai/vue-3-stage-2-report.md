# Vue-3-Migration – Stufe 2: Compat-Cutover

Status: Technischer Checkpoint abgeschlossen; die vollständige Releaseabnahme bleibt wegen der nicht verfügbaren geschützten Testdaten und der bewusst bis Stufe 3 verbleibenden Vue-2-UI-Pakete offen

Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Er beschreibt den ersten lauffähigen Stand unter der Vue-3-Laufzeit und ist der Rücksprungpunkt vor dem Austausch der UI-Komponentenfamilien.

## Umgesetzter Umfang

- Vue `3.5.42` und `@vue/compat` `3.5.42` sind exakt gekoppelt. Compiler und Runtime verwenden gemäß der offiziellen BootstrapVue-Anleitung vorübergehend global `MODE: 2`.
- Der Einstieg verwendet `createApp`; Plugins, Übersetzung, Store, Router und Directives werden app-lokal installiert. Vue Router wurde auf `4.6.4`, Vuex auf `4.1.0`, `vue-loader` auf `17.4.2` und `@vue/compiler-sfc` auf `3.5.42` umgestellt.
- Router-History, Basis `/vue`, Routennamen und der Catch-all bleiben erhalten; das Scroll-Ergebnis verwendet die Vue-Router-4-Felder `left` und `top`.
- `vue-async-computed` und `epic-spinners` wurden auf ihre stabilen Vue-3-fähigen Majors aktualisiert. Der eigene Spinner-Adapter erhält den bisherigen Anwendungsimport.
- `@eli5/vue-lang-js` wurde durch einen kleinen App-Adapter um die unveränderte `lang.js`-Engine ersetzt. Die bestehenden Schlüssel, das `$t`-API und die Laravel-generierten Übersetzungsdaten bleiben erhalten.
- `vue-flash-message` wurde durch einen lokalen Vue-3-Service ersetzt. Die bestehenden `flash*`-Methoden, Standardoptionen, Klassen, Transitionen und ARIA-Live-Ausgabe bleiben erhalten; die MIT-CSS-Herkunft ist am übernommenen Stylesatz vermerkt.
- Der Markdown-Renderer verwendet die Vue-3-Render-API und kompiliert weiterhin keine Nutzereingaben als Vue-Template. Alte Lifecycle-Namen in Anwendungskomponenten wurden auf `beforeUnmount` beziehungsweise `unmounted` aktualisiert.
- SVGs werden durch den lokalen Loader `scripts/loaders/svg-vue-loader.cjs` unverändert in Vue-3-Komponenten kompiliert. XML-Prolog und Doctype werden entfernt, weil sie in Inline-SVGs keine Darstellung tragen. Compilefehler werden jetzt an Webpack weitergegeben; der zuvor erprobte Drittloader hatte Fehler verschluckt und leere Komponenten erzeugt. Die 36 tatsächlich verwendeten SVGs aus `svg-icon` `0.8.2` liegen mit MIT-Lizenz, Quelle und Tarball-Integrität unter `resources/icons/vendor/svg-icon`; die historische Paket-CLI samt PhantomJS ist nicht mehr Teil der Installation.
- `vuejs-dialog` wurde nach repositoryweiter Suche entfernt: außer Manifest und Dokumentation gab es keinen Laufzeitimport. Vite `8.2.2` und `vue-eslint-parser` `10.4.1` sind als bereits verwendete Test-/Lint-Abhängigkeiten nun explizit festgelegt, statt nur zufällig transitiv installiert zu sein. Vite ist in dieser Stufe **nicht** der Anwendungsbundler.

## Verifizierte Paketfakten und Quellen

Die Zielversionen und Peer-Verträge wurden am 10. September 2026 unmittelbar vor Installation über npm-Metadaten und die Primärdokumentation geprüft:

- [Vue-3-Migrationsbuild und Compat-Konfiguration](https://v3-migration.vuejs.org/migration-build.html)
- [BootstrapVue unter Vue 3](https://bootstrap-vue.org/vue3/): weiterhin BootstrapVue `2.23.1`, Bootstrap 4 und globaler/compilerseitiger Compat-Modus 2 als Übergang; BootstrapVueNext bleibt das Ziel für Stufe 3.
- [Vue Router 4](https://router.vuejs.org/guide/migration/)
- [Vuex 4](https://vuex.vuejs.org/guide/migrating-to-4-0-from-3-x)

Installiert und exakt gelockt sind Vue/Compat/Compiler `3.5.42`, Vue Router `4.6.4`, Vuex `4.1.0`, vue-loader `17.4.2`, epic-spinners `2.0.0` und vue-async-computed `4.0.1`.

## Compat-Inventar und Zuordnung

Der synthetische Browser-Smoke sammelt alle Console-Warnungen als Test-Anhang und lässt nur ausdrücklich erkennbare Vue-Compat-Deprecations sowie die bekannte BootstrapVue-Mehrfachinstanzwarnung zu. Andere Warnungen und Page Errors brechen den Test ab.

| Warnungsfamilie / Paket | Beleg und Owner | Späteste Auflösung |
| --- | --- | --- |
| `RENDER_FUNCTION`, `$scopedSlots`, `$listeners`, Functional Components, alte Lifecycle-Hooks, Component-`v-model`, Attr-Coercion | Component-Traces zeigen BootstrapVue-Komponenten. Bootstrap-Adapter/UI-Familien sind Owner. | Stufe 3 |
| `GLOBAL_EXTEND`, `GLOBAL_MOUNT`, `GLOBAL_PROTOTYPE`, Mehrfachinstanz | BootstrapVue und sein `portal-vue` verwenden nachweislich `Vue.extend`, Vue-Prototyp-Erweiterungen und programmatic mount. | Stufe 3 |
| `WATCH_ARRAY` ohne Component-Trace | Beim BootstrapVue-App-Boot reproduzierbar, aber vom Runtime-Trace nicht enger zuordenbar; bleibt deshalb ausdrücklich inventarisiert und darf nicht pauschal deaktiviert werden. | Stufe 3, bei Entfernung der UI-Altlasten erneut prüfen |
| `@hokify/vuejs-datepicker`, `vue-select` 3, `vue-star-rating` 1, `vue-transmit`, `vue-shortkey` | npm Peer-Tree weist Vue-2-Verträge beziehungsweise bei `vue-star-rating` sogar eine verschachtelte Vue-2-Laufzeit aus. Nutzung läuft über Adapter. | Stufe 3 |

Eigene neue Render-Komponenten laufen bereits mit Vue-3-Rendersemantik. Der Root muss bis zum BootstrapVue-Wechsel die übrigen Instance-Compat-APIs behalten, da insbesondere Navbar/Collapse den Root-Event-Emitter verwenden.

## Ausgeführte Prüfungen

- Laufzeiten: Node `v26.8.1`, npm `11.19.0`, PHP `8.5.10`; die Repository-Engine erlaubt Node 24 bis unter 27. PHP weicht damit von der dokumentierten PHP-8.4-Zielbasis ab, der Frontend-Checkpoint ändert aber keinen PHP-Code.
- `npm run inventory:frontend`: 74 JavaScript- und 100 Vue-Dateien, 16 registrierte Vuex-Module, ein direkter BootstrapVue-Import ausschließlich im Adapter, keine Filter, Runtime-Templates, alte Scoped-Slot-Syntax oder `.sync`-Modifier.
- Development-Webpack-Compile: erfolgreich. `php artisan dev` wurde neu gestartet; dessen Tab `vite` (historischer Laravel-Prozessname) meldet `Compiled successfully` und bedient Port 8080.
- Browser gegen den HMR-Server auf Port 8080: WebKit Desktop und Mobile erfolgreich; Vue mountet, Navigation ist sichtbar, beide SVG-Navigationsicons sind echte `<svg>`-Elemente, keine Page Errors oder unbekannten Warnungen.
- `npm run test:unit`: 5 Dateien, 10 Tests erfolgreich.
- `npm run lint`: erfolgreich nach expliziter Parser-Abhängigkeit; 0 Fehler und 356 sichtbare Legacy-Warnungen.
- `npm ci --legacy-peer-deps`: frischer vollständiger Lockfile-Install erfolgreich. Ein zuvor scheiternder PhantomJS-Lifecycle-Pfad wurde durch die gezielte lokale Übernahme der tatsächlich verwendeten SVG-Dateien beseitigt.
- `npm run build`: Production-Build erfolgreich; die versionierten CSS-/JS-Artefakte wurden neu erzeugt.
- `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8003 npm run test:e2e`: vier Desktop-/Mobile-Tests für Login und synthetischen Vue-3-App-Boot erfolgreich.
- `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8003 npm run test:visual`: beide Login-Referenzbilder unverändert.
- `git diff --check`: erfolgreich.

## Installation und Audit

Der Compat-Checkpoint benötigt vorübergehend `npm ci --legacy-peer-deps`, weil die oben genannten, in Stufe 3 zu ersetzenden UI-Pakete noch Vue-2-Peerbereiche deklarieren. Dies ist eine gezielte, befristete Ausnahme und **kein** Endzustand; es gibt keine `.npmrc`-weite Aufweichung. Ein normales `npm ci` und ein sauberer `npm ls` sind spätestens am Ende von Stufe 3 Pflicht.

`npm audit` meldet nach Entfernung der ungenutzten `svg-icon`-Toolchain 79 Einträge: 9 low, 19 moderate, 39 high und 12 critical. Der direkte produktive High-Pfad ist das weiterhin auf Major 0 gehaltene Axios. Weitere wesentliche Pfade liegen im bewusst noch vorhandenen Webpack-4-/Dev-Server-Stack und `webpack-bundle-analyzer`; deren von npm angebotene Korrekturen sind Majorwechsel und wurden deshalb nicht automatisch angewendet. Keine Findings wurden unterdrückt. Axios erhält ein eigenes verhaltensgesichertes Upgrade-Arbeitspaket; Webpack-/Dev-Server-Pfade enden mit Stufe 5.

## Stufenabgleich, ausgelassene Gates und nächste Zuordnung

- Erreicht: Anwendung läuft unter Vue 3 Compat, Router 4 und Vuex 4; eigene kritische Renderpfade und Lifecycle-Hooks wurden portiert; Build, HMR, Unit-, Lint-, synthetische App-, Login- und Visual-Gates sind grün.
- Bewusst Stufe 3 zugeordnet: BootstrapVue/Bootstrap 4, Select, Datepicker, Rating, Upload, Shortcuts und die dadurch verursachten Compat-Warnungen und Peer-Konflikte. Diese Komponenten werden familienweise hinter den vorhandenen Adaptern ersetzt und separat visuell geprüft.
- Bewusst später zugeordnet: reine Vue-3-Laufzeit ohne Compat (Stufe 4), Vite als Anwendungsbundler (Stufe 5), Pinia (Stufe 6), verbleibende Sass-`@import`-/Bootstrap-4-Deprecations und Auditbereinigung entlang der sie verursachenden Stufen.
- Offen: Sail/MySQL und geschützte synthetische Kernreisen waren weiterhin nicht verfügbar. Deshalb sind Schreiben/Persistenz, reale Validierungs-/Berechtigungsfehler, Upload, Markdown/Bibel-Popover und komplexe Overlays noch kein vollständiger Releasebeleg. Der Checkpoint ist technisch rückbaubar, aber keine Produktionsfreigabe.

## Rückbau

Der gesamte Stufencommit kann auf den Stufe-1-Commit `20ccd3d` zurückgesetzt werden; es existiert keine Datenmigration. Die Adapter erlauben zusätzlich einen komponentenfamilienweisen Rückbau in Stufe 3.

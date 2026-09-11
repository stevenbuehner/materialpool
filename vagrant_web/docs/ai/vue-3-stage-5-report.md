# Vue-3-Migration – Stufe 5: Laravel-Vite-Integration

Status: Abgeschlossen. Webpack, die manuell referenzierten Legacy-Bundles und der separate Webpack-Dev-Server sind durch Vite und Laravels `@vite`-Integration ersetzt. Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Der Rücksprungpunkt vor dem Cutover ist `04f3edac`; Vorbereitung und eigentlicher Cutover liegen in `d4e8cc63` und `c5a63c56`.

## Ergebnis und öffentliche Verträge

- Die stabile, gegenseitig kompatible Toolchain ist exakt auf Vite `8.2.2`, `laravel-vite-plugin` `3.2.0` und `@vitejs/plugin-vue` `6.0.8` gelockt. Das kontrollierte Build-Image enthält PHP `8.4.25`, Node `24.21.0` und npm `11.19.0`.
- `vite.config.mjs` baut die Vue-SPA und das globale Sass getrennt. Laravel löst beide über das Vite-Manifest auf; `resources/views/layouts/app.blade.php` lädt nur das globale Stylesheet, während `resources/views/vuerouter/index.blade.php` zusätzlich die SPA startet. Login- und Passwortseiten mounten daher keine Vue-Anwendung auf einem fehlenden `#app`.
- `npm run dev` generiert zuerst die Laravel-Sprachdatei und startet genau einen Vite-Server. `php artisan dev` verwendet diesen Prozess unverändert. `npm run build` bricht bei fehlgeschlagener Sprachgenerierung ab und erzeugt ausschließlich gehashte Dateien unter `public/build`.
- Der lokale SVG-Pluginadapter kompiliert bestehende direkte SVG-Imports vor Vites Asset-Loader als Vue-3-Renderkomponenten. Ein Unit-Test schützt diesen Vertrag. Die Navbar-Icons wurden im Browser geprüft; das Produktionsmanifest enthält keine irrtümlich als URL ausgegebenen SVG-Komponenten.
- Der öffentliche `p-queue`-Export ersetzt den privaten Deep-Import. Tilde-Imports aus dem Webpack-Loaderpfad, das Babel-Polyfill und die gesamte Webpack-/Babel-/Loader-Abhängigkeitskette sind entfernt. Das ReadBible-Hilfsmodul wurde ohne Verhaltensänderung aus der Lazy-Route herausgelöst, damit Vites Chunkanalyse deterministisch bleibt.
- Die zuvor eingecheckten Dateien unter `public/css` und `public/js` sowie `webpack.config.js` wurden erst nach einem erfolgreichen Commit-basierten Release-Build entfernt. Gehashte Vite-Artefakte bleiben gemäß Produktionsvertrag generiert und durch `.gitignore` außerhalb von Git.

Routen, API-Aufrufe, Payloads, Authentifizierung, Policies, Daten, Storage, Queues, sichtbare Texte und Gestaltung wurden nicht geändert. Die vier beim Build bewusst extern bleibenden öffentlichen URLs `/img/icons/entypo-plus/rocket.svg`, `/img/icons/entypo-plus/new-message.svg`, `/img/icons/entypo-plus/flow-tree.svg` und `/img/icons/tag.svg` zeigen auf vorhandene Public-Dateien und behalten ihre bisherigen Laufzeitpfade.

## Reproduzierbarer Release-Nachweis

Der neue Sail-8.4-Container wurde aus `docker/8.4/Dockerfile` erfolgreich gebaut. `ops/production/build-release.sh` erzeugte anschließend isoliert aus dem exakten Commit `c5a63c566f7857f341de10c6dd252536ab5e80d5` das Release-Archiv und dessen SHA-256-Datei. Die Prüfsumme wurde erfolgreich gegengeprüft. Das eingebettete Release-Manifest nennt denselben Commit und die festgelegten Laufzeiten.

Der Archivvertrag wurde ohne Ausgabe von Secrets oder Nutzdaten geprüft:

- `public/build/manifest.json` ist enthalten;
- `public/hot`, `.env`, `node_modules` und `vendor` sind nicht enthalten;
- Composer installiert im isolierten Export aus dem Lockfile, danach löst Vite auch die JavaScript-Imports aus dem Composer-Paket auf;
- der gezielte `ProductionDeploymentContractTest` besteht im PHP-8.4-Container mit 9 Tests und 56 Assertions.

CSP-, Nonce- oder SRI-Konfigurationen sind im aktuellen Anwendungsbestand nicht vorhanden. Die Migration erfindet daher keine neue Policy. Sollte Produktion künftig eine CSP verlangen, wird sie gesondert entschieden und über Laravels öffentliche Vite-APIs integriert und getestet.

## Ausgeführte Frontend-Gates

- frisches `npm ci --ignore-scripts`: 264 Pakete installiert;
- `npm run build`: 946 Module transformiert, Vite-Manifest und 21 gehashte Assets erfolgreich erzeugt;
- Vitest: 18 Dateien, 40 Tests bestanden;
- ESLint: 0 Fehler; 318 bereits erfasste Warnungen bleiben als Konsolidierungsbestand;
- Frontend-Inventar: keine Vue-2-/Compat-/Webpack-/Babel-/Loader-Laufzeitreste in den geschützten Kategorien;
- Browser-Funktionstests: 16 von 16 Desktop-/Mobile-Reisen bestanden;
- production-artige Login-Visualtests: 2 von 2 bestanden;
- alle 22 vom Produktionsmanifest referenzierten Dateien lieferten HTTP 200 und den passenden JavaScript-, CSS- oder JSON-Inhaltstyp;
- Dev-HMR wurde durch eine vorübergehende, anschließend vollständig entfernte Sass-Änderung ausgelöst und von Vite als CSS-HMR verarbeitet;
- die sichtbare Dev-Loginseite unter dem von `php artisan dev` gestarteten Laravel-Server wurde im Browser geprüft; Browserkonsole ohne Fehler oder Warnungen.

## Bekannte Hinweise und nächste Stufe

- `npm audit` meldet nach Entfernung der alten Buildkette 10 Befunde: 1 low, 4 moderate und 5 high. Es wurde bewusst weder `npm audit fix` noch ein Major-Upgrade ausgeführt. Die verbleibenden direkten beziehungsweise transitiven Befunde werden in Stufe 7 einzeln bewertet.
- Dart Sass meldet Deprecations aus den vorhandenen `@import`-Pfaden und aus Bootstrap-Sass. Eine isolierte Umstellung ist erst zusammen mit der verbleibenden UI-/Sass-Konsolidierung sinnvoll, weil sie die Reihenfolge und Sichtbarkeit von Variablen verändern kann.
- Der Hauptchunk liegt bei rund 1.287 kB minimiert beziehungsweise 379 kB gzip und überschreitet Vites 500-kB-Hinweisgrenze. Die Größe ist gegenüber dem alten Buildpfad gesunken; weitere fachlich sinnvolle Chunkgrenzen werden in Stufe 7 anhand des Stufe-0-Vergleichs geprüft.
- Der Build meldet, dass der bestehende `xxl`-Containerwert 1140 px nicht größer als `xl` ist. Das bewahrt bewusst das bisherige Layout; eine sichtbare Breakpoint-Änderung ist kein technischer Vite-Fix und bleibt entscheidungspflichtig.
- `php artisan db:seed` verändert keine Vite-Artefakte. Das Gate bleibt dennoch gemäß Vertrag vor der Releasefreigabe in Stufe 7 gegen die dedizierte, entbehrliche Sail-MySQL-Datenbank `testing` verbindlich; ein Host- oder Entwicklungsdatenbank-Ersatz ist unzulässig.

Nächster abhängiger Schritt ist Stufe 6: Vuex 4 wird fachmodulweise und mit jeweils genau einem schreibenden Store durch Pinia ersetzt. Webpack oder die entfernten statischen Bundles werden dafür nicht mehr benötigt.

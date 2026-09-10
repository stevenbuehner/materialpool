# Vue-3-Migration – Stufe 1: Vue-2-Vorbereitung

Status: Technischer Checkpoint abgeschlossen; vollständiges Stufengate wegen der offenen geschützten Referenzreisen noch nicht releasefähig

Dieser Bericht dokumentiert den unter Vue 2 geprüften Rücksprungpunkt vor dem Framework-Cutover. Er ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md) und die [Stufe-0-Baseline](vue-3-stage-0-report.md).

## Umgesetzter Umfang

- Direkte BootstrapVue-Imports aus Anwendungskomponenten wurden auf den zentralen Adapter `resources/js/adapters/bootstrap.js` reduziert.
- Weitere Vue-gebundene Drittkomponenten für Datepicker, Flash-Meldungen, Spinner, Sternebewertung, Select und Upload sind über lokale Adapter erreichbar. Die Adapter erhalten die bisherigen Importverträge und bilden die spätere Austauschgrenze.
- Globale Plugins und die Direktive für verzögertes Laden von Bildern werden ausschließlich über `installLegacyPlugins.js` installiert.
- Template-Filter wurden durch importierbare JavaScript-Funktionen und gleichnamige Komponentenmethoden ersetzt. Die bisherigen Rückgabewerte sind durch Charakterisierungstests abgesichert.
- Veraltete Slot-Syntax und problematische Deep-Selector wurden auf die vom aktuellen Compiler unterstützte Schreibweise überführt. `.sync` kommt im Anwendungsbestand nicht mehr vor.
- Der Bible-Popover-Eventbus verwendet keine Vue-Instanz mehr, sondern eine explizite, getestete Schnittstelle zum Registrieren, Abmelden und Senden von Ereignissen.
- Zugriffe auf `Vue.set` und `Vue.delete` laufen über einen Reactivity-Adapter. Dadurch kann Stufe 2 auf native Vue-3-Reaktivität umstellen, ohne erneut alle Konsumenten zu verändern.
- Markdown erzeugt keine zur Laufzeit kompilierten Vue-Templates mehr. Bibelstellen werden nach DOMPurify-Sanitizing als VNodes aufgebaut; Werte gelangen nur über erlaubte `data-*`-Attribute in die bekannte Komponente. Ein Test deckt HTML-Attribut-Escaping und die unveränderte Autoload-Markierung ab.

## Inventur am Checkpoint

- 73 JavaScript- und 100 Vue-Quelldateien; die zusätzlichen JavaScript-Dateien sind Adapter, Plugin-Bootstrap und Tests.
- 16 registrierte Vuex-Module, unverändert zur Baseline.
- 1 direkter BootstrapVue-Import, ausschließlich im Bootstrap-Adapter statt zuvor 38 Dateien.
- 0 Filterdefinitionen, 0 Runtime-Templates, 0 alte Scoped-Slot-Syntax und 0 `.sync`-Modifier.
- 2 statisch erkannte `Vue.set`-/`Vue.delete`-Vorkommen: beide befinden sich im Reactivity-Adapter; ein dritter Inventurtreffer in `keywordInput.vue` ist auskommentierter Legacycode.
- 2 bestehende `v-html`-Verwendungen außerhalb des Markdown-Renderers bleiben für ihre jeweiligen Sicherheits- und UI-Tests inventarisiert.
- 7 bestehende Komponenten-/Mixin-`model`-Verträge sowie 5 alte Lifecycle-Hooks bleiben bewusst Stufe 2 zugeordnet, weil ihre Semantik beim Vue-3-Cutover gemeinsam mit Compat-Warnungen geprüft wird.

## Ausgeführte Prüfungen

- `npm run inventory:frontend`: erfolgreich; die oben genannten Vue-3-Bruchstellen wurden erneut gezählt.
- `npm run test:unit`: 5 Dateien, 10 Tests erfolgreich.
- `npm run lint`: erfolgreich, 0 Fehler; 362 bestehende Legacy-Warnungen bleiben sichtbar und werden nicht unterdrückt.
- `npm run build`: Production-Build erfolgreich; die bekannten Legacy-/Sass- und Bundlegrößenwarnungen bleiben klassifiziert.
- `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8003 npm run test:e2e`: Login in WebKit Desktop und Mobile erfolgreich.
- `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8003 npm run test:visual`: beide Login-Referenzbilder unverändert.

## Auswirkungen und Rückbau

Es wurden keine Routen, API-Payloads, Berechtigungen, Datenmodelle, persistierten Daten oder sichtbaren Designregeln geändert. Der Production-Build schreibt die weiterhin versionierten Artefakte unter `public/css` und `public/js` neu. Jeder Drittkomponentenadapter kann einzeln auf den vorherigen Direktimport zurückgeführt werden; der gesamte Checkpoint kann ohne Datenmigration per gezieltem Commit-Revert zurückgebaut werden.

## Offene Gates und Risiken

- Sail/MySQL war weiterhin nicht verfügbar. Daher konnten die vollständige Backend-Suite und die geschützten synthetischen Nutzerreisen nicht wiederholt werden. Der öffentliche Login-Test beweist nicht das Verhalten der SPA, insbesondere nicht Markdown/Bibel-Popover, Upload, Material-/Keywordbearbeitung oder Autorisierungsfehler.
- Das neue Markdown-Rendering ist mit Unit-Test und Production-Build abgesichert, benötigt aber noch eine authentifizierte Browserreise mit ungefährlichen XSS-Fixtures und einer real gerenderten Bibelstelle.
- Stufe 2 muss die verbleibenden `model`-Verträge, Lifecycle-Hooks, Event-/Attr-Semantik und den Vue-2-kompatiblen Render-Helper unter Vue 3 ausdrücklich bearbeiten.
- Dieser Commit ist deshalb ein grüner technischer Rücksprungpunkt, aber noch keine Release- oder vollständige Stufe-1-Abnahme.

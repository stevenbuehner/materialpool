# Vue-3-Migration – Stufe 3: UI-Komponenten

Status: In Arbeit; die Teilstufen Shortcuts, Rating und Datepicker sind abgeschlossen. Select, Upload und BootstrapVue/Bootstrap 5 folgen in getrennten, rückbaubaren Arbeitspaketen.

Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Er wird nach jeder Komponentenfamilie fortgeschrieben. Ein grüner Teilcommit ist noch keine Freigabe der gesamten Stufe 3.

## Teilstufe 3.1 – Tastaturkürzel

- `vue-shortkey` wurde entfernt. Die einzige belegte Verwendung `ctrl+n` wird durch `resources/js/directives/shortkey.js` app-lokal bereitgestellt.
- Der bestehende Templatevertrag `v-shortkey="['ctrl', 'n']"` und das Ereignis `@shortkey` bleiben erhalten. Listener werden beim Unmount entfernt; wiederholte Tastenevents und Eingaben in Formularfeldern werden nicht ausgelöst.
- Die Directive ist absichtlich kein allgemeiner Ersatz für das gesamte ehemalige Paket. Neue Tastenkombinationen benötigen zuerst einen expliziten Anwendungsvertrag und Tests.
- Abnahme: Unit-Suite mit eigenem Directive-Test, gezieltes Lint, Production-Build sowie synthetischer Compat-Browser-Smoke auf Desktop und Mobile waren grün.
- Grenze: Der geschützte Erstellungsdialog wurde nicht mit realen Nutzerdaten durchgespielt. Der Eventvertrag ist per Unit-Test und unverändertem Verbraucher abgesichert; die vollständige geschützte Reise bleibt dem Stufe-3-Integrationsgate zugeordnet.

## Teilstufe 3.2 – Sternebewertung

- `vue-star-rating` wurde von der Vue-2-Ausgabe auf die stabile Vue-3-Ausgabe `2.1.0` aktualisiert und exakt gelockt. Peer-Dependency ist Vue 3; Lizenz ist MIT.
- Der anwendungseigene Adapter bleibt der einzige Paketimport. Verbraucher verwenden den Vue-3-Vertrag `rating` / `update:rating`; die lokale Fünf-Sterne-Komponente emittiert während der Übergangszeit zusätzlich den bisherigen Eventnamen.
- Nicht verwendete lokale Registrierungen wurden nur dort entfernt, wo die Templates nachweislich keine Rating-Komponente enthalten.
- Abnahme: Unit-Suite, gezieltes Lint, Production-Build und synthetischer Browser-Smoke auf Desktop und Mobile waren grün.
- Grenze: Das Paket ist stabil und Vue-3-kompatibel, aber nicht neu. Wartungsstatus und ein möglicher kleiner lokaler Ersatz werden im Konsolidierungsgate erneut bewertet; dies blockiert die technische Vue-3-Portierung nicht.

## Teilstufe 3.3 – Datepicker

- `@hokify/vuejs-datepicker` `2.0.2` wurde durch den API-nahen Vue-3-Fork `@wslyhbb/vuejs-datepicker` `4.3.1` ersetzt und exakt gelockt. Der Zielstand verlangt Vue 3 und `date-fns` 4; beide Peer-Verträge sind erfüllt. Lizenz ist MIT.
- `date-fns` `4.1.0` wurde exakt gelockt. Locale-Objekte werden über direkte Modulimporte geladen, damit Webpack 4 nicht unnötig alle Locale-Einstiege auflösen muss.
- Der Adapter bleibt der einzige JavaScript-Paketimport. Verbraucher verwenden explizit `modelValue` / `update:modelValue`. Die bestehenden Anwendungseigenschaften für Montag als Wochenanfang, Bootstrap-Styling, Wrapper-Klasse, deaktivierte Daten, Placeholder und Eingabeklasse bleiben bestehen.
- Die bisherigen Moment-Format-Tokens `DD.MM.YYYY` und `MM/DD/YYYY` werden an der Adaptergrenze in die date-fns-Tokens `dd.MM.yyyy` und `MM/dd/yyyy` übersetzt. Der übrige Anwendungscode behält zunächst seinen bestehenden Moment-/Day.js-Vertrag.
- Der alte Webpack-4-Pfad benötigt für die moderne Paketausgabe gezielte Babel-Transformationen für logische Zuweisungen und Klassenfelder. Beide Transformer sind deklarierte Build-Abhängigkeiten; nur die betroffenen Vue-3-Pakete werden aus `node_modules` transpiliert. Dieser Übergangscode entfällt mit Vite in Stufe 5.
- Bundleauswirkung gegenüber dem Rating-Checkpoint: das minimierte Haupt-JavaScript wächst von 2.634.767 auf 2.728.708 Bytes, also um 93.941 Bytes. Diese vorübergehende Zunahme wird beim Vite-/Tree-Shaking-Gate erneut gemessen.
- Abnahme: Production-Webpack-Build ohne Buildfehler; das frische Source Map enthält den neuen und nicht den alten Datepicker. 13 Unit-Tests sowie gezieltes Lint sind grün. Der deterministische WebKit-Test besteht auf Desktop und Mobile und prüft deutsches Format `01.09.2026`, sichtbares Eingabefeld, Kalenderöffnung und fehlende Page Errors.
- Grenze: Englische Locale, Tastaturnavigation, echte Datumsauswahl/Persistenz und pixelgenaue geschützte Referenzreisen sind noch nicht vollständig automatisiert. Diese Fälle werden spätestens im gemeinsamen Stufe-3-Integrationsgate ergänzt; API- oder Persistenzverträge wurden in dieser Teilstufe nicht geändert.

## Teilstufe 3.4 – Select, Suche und Tags

- `vue-select` `3.20.4` und damit der letzte von `npm ls` ausgewiesene Vue-2-Peer wurden entfernt. Laufzeitersatz ist das stabile `@vueform/multiselect` `2.6.11`, exakt gelockt, ohne Laufzeitabhängigkeiten und unter MIT-Lizenz.
- `resources/js/adapters/vue-select.vue` erhält den bestehenden Materialpool-Vertrag: Objektwerte, Single-/Multiple-Modus, Funktions-Label und -Key, lokale oder externe Filterung, Clear-/Close-on-select, Ladecallback, Focus/Blur, Option-/Selected-/No-options-/Footer-Slots sowie die Instanzmethoden `select`, `deselect`, `isOptionSelected` und `focusSearch`.
- Die Adaptergrenze gibt immer die ursprünglichen Fachobjekte an Verbraucher und Store-Aktionen zurück. Stabile, eigenschaftsreihenfolge-unabhängige Ersatzkeys schützen Optionen ohne ID; die Normalisierung besitzt eigene Unit-Tests.
- Die bisherige Tab-Auswahl wird ausdrücklich emuliert. Falls der neue Multiselect bei extern geladenen Ergebnissen noch keinen Pointer gesetzt hat, nimmt Tab die erste noch nicht ausgewählte gefilterte Option und verwendet denselben Auswahlpfad wie ein Klick.
- Die entfernten privaten Importe `vue-select/src/scss/...` wurden durch lokale Sass-Tokens ersetzt. Für identisches Markup-Styling bleibt ausschließlich das veröffentlichte CSS aus `vue-select` `3.20.4` unter `resources/js/legacy-ports/vue-select-3.20.4/` erhalten. Quelle, Tarball-Integrität, Copyright und vollständige MIT-Lizenz sind dort dokumentiert; Vue-2-Komponentencode wurde nicht kopiert.
- Der Vue-3-Multiselect trägt parallel seine funktionalen Klassen und die etablierten `vs__...`-Klassen. Eine kleine strukturelle Bridge neutralisiert den zusätzlichen Wrapper, verhindert einen doppelten Rahmen und lässt die bereits vorhandenen Sidebar-Sonderregeln weiter gewinnen.
- Zwei beim Browsergate sichtbar gewordene Vue-3-Altstellen wurden im betroffenen Pfad repariert: `searchbarHeader` schreibt den `updated`-Wert nun explizit zurück, statt sich auf die entfernte Vue-2-`model`-Option zu verlassen; die noch lazy geladene Optimierungsliste behandelt ihren dokumentierten leeren Anfangszustand defensiv als `[]`.
- Bundleauswirkung gegenüber dem Datepicker-Checkpoint: Haupt-JavaScript plus 48.586 Bytes, Source Map plus 205.021 Bytes und CSS plus 11.269 Bytes. Darin enthalten sind der neue Select und die bewusst lokal erhaltene visuelle CSS-Kompatibilitätsschicht.
- Abnahme: sauberer Vue-Peer-Tree, kein alter Paket-/Deep-Sass-Import, 15 Unit-Tests, gezieltes Lint ohne Fehler und regulärer Production-Build ohne Buildfehler. `npm ci --legacy-peer-deps --ignore-scripts` reproduziert den Lockfile-Stand frisch. Sechs WebKit-Tests bestehen auf Desktop und Mobile mit echtem Production-CSS. Der Select-Test prüft Wrapper-/Rahmengeometrie, asynchrone Objektsuche, Dropdown, Auswahl per Tab, individuellen Tag-Slot und die resultierende Suchroute.
- Grenze: Interaktive Single-Select-Zuweisung, Entfernen/„Alle hinzufügen“, Infinite Scroll und Drag-Relevanz sind noch nicht vollständig als Browserreisen automatisiert. Die zugehörigen Material-Komponenten mounten im synthetischen Materialtest ohne Page Error; die vollständigen Interaktionen folgen im gemeinsamen Stufe-3-Integrationsgate mit repräsentativen geschützten Daten.

## Aktueller Stufenabgleich

- Erledigt und nicht übersprungen: Paket-/Peer-Prüfung, Adaptergrenze, direkte Verbraucher, Format-/Locale-Anpassung, Production-Artefakte, statische Tests sowie Desktop-/Mobile-Browserinteraktion für die Datepicker-Familie.
- Erledigt und nicht übersprungen für Select: vier direkte Verbraucher, öffentliche Props/Events/Slots und Instanzmethoden, Objektidentität, Styles/Sass-Tokens, Async-Suche, Tab-Verhalten, Parent-Update und Vue-Peer-Bereinigung.
- Als nächste Teilstufe festgehalten: `vue-transmit` und erst anschließend der zusammenhängende BootstrapVue-/Bootstrap-5-Wechsel. Bootstrap 4 und 5 werden gemäß Vertrag nicht ungekapselt gleichzeitig in der Produktseite geladen.
- Für das Ende von Stufe 3 offen: vollständiges `npm ci` ohne `--legacy-peer-deps`, Compat-Warnungsinventar ohne UI-Altlasten, alle repräsentativen geschützten Reisen sowie Visual-, Fokus-, Keyboard- und Responsive-Abnahme. Der normale npm-11-CI-Lauf scheitert derzeit ausschließlich an den verbleibenden Bootstrap-4-/BootstrapVue-Peers (`portal-vue`/Vue 2 und automatisch erwartetes jQuery); es wurde bewusst kein ungenutztes jQuery ergänzt. Der frische Legacy-Peer-Lauf meldet 77 Audit-Einträge (7 low, 19 moderate, 39 high, 12 critical). Die wesentlichen bekannten Altpfade bleiben dem Bootstrap-/Webpack-Wechsel sowie dem separaten Axios-Arbeitspaket zugeordnet.

## Rückbau

Jede bisherige Komponentenfamilie ist über ihren Adapter und ihren eigenen Commit unabhängig rückbaubar. Es wurden keine Backend-, API-, Datenbank- oder Persistenzverträge geändert.

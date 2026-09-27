# Verbindlicher Vue-3-Frontendvertrag

## Status und Zweck

Die Vue-3-Migration ist abgeschlossen. Dieses Dokument beschreibt nur noch den verbindlichen Endzustand und die Regeln für weitere Frontendarbeiten. Historische Stufen-, Fortschritts- und Ausführungsberichte wurden entfernt; ihre Nachvollziehbarkeit bleibt über Git erhalten.

Stand: 16. September 2026.

## Verbindlicher Endzustand

- Vue 3 mit Vue Router 4 und Pinia; `@vue/compat`, Vue 2 und Vuex dürfen nicht erneut eingeführt werden.
- Vite mit `laravel-vite-plugin`; Webpack und Laravel Mix dürfen nicht erneut eingeführt werden.
- Bootstrap 5 mit BootstrapVueNext beziehungsweise kleinen lokalen Materialpool-Adaptern; Bootstrap 4 und BootstrapVue dürfen nicht erneut eingeführt werden.
- Direkte Integrationen austauschbarer UI-Pakete liegen hinter `resources/js/adapters/` oder einer anwendungseigenen Wrapper-Komponente.
- `package-lock.json` gehört zum reproduzierbaren Installationsvertrag. Neue direkte Dependencies werden exakt gelockt und zusammen mit dem Quellcode geändert.
- Options API bleibt für bestehenden Code zulässig. Neue komplexe, wiederverwendbare Zustandslogik darf als Composition API oder Composable umgesetzt werden; ein Stilumbau allein ist kein Änderungsgrund.

## Unveränderliche Produkt- und Integrationsverträge

- SPA-Routen unter `/vue`, Routenparameter, Query-Semantik, Deep Links und Reload-Verhalten bleiben erhalten.
- API-v1-/API-v2-Aufrufe behalten URL, Methode, Header, Payload und Statuscodebehandlung. Frontendarbeiten ändern keine Backendverträge.
- Authentifizierung, CSRF, Passport, Policies und Rollen bleiben unverändert. UI-Sichtbarkeit ersetzt keine serverseitige Autorisierung.
- Laravel-Übergaben über `window.Laravel` und `window.materialpool` werden nur nach einem nachgewiesen kompatiblen Ersatz entfernt.
- Übersetzungsschlüssel, Datums-/Zahlenformatierung und sichtbare Texte bleiben erhalten. Neue sichtbare Texte laufen über die vorhandene Lokalisierung.
- Material-, Resource-, Keyword-, Bibleverse-, Bundle-, Upload-, Preview-, Download- und Usage-Abläufe behalten ihre fachliche Semantik.

## UI- und Zugänglichkeitsvertrag

- Frontendmodernisierung ist kein Redesign. Bestehende Sass-Variablen, Bootstrap-5-Utilities und Materialpool-Komponenten werden wiederverwendet.
- Responsive Anordnung, Zustände für Hover, Focus, Disabled, Invalid, Saving, Loading und Error sowie bestehende Abstände und Größen bleiben erhalten.
- Tastaturbedienung, Fokusreihenfolge, sichtbarer Fokus, Labels, semantische Elemente und ARIA-Zustände dürfen nicht verschlechtert werden.
- Abweichungen, die Layout, Interaktion, Texte, Farben, Icons oder Nutzerverhalten wesentlich verändern, benötigen vor Umsetzung eine Entscheidung nach `docs/ai/decision-template.md`.

## Entwicklungsregeln

- Props, Emits und Slots werden explizit beschrieben. Props werden nicht direkt mutiert; Listen erhalten stabile Keys.
- Öffentliche Komponentenverträge und Store-Nebenwirkungen werden vor einem Austausch charakterisiert und anschließend durch beobachtbares Verhalten geprüft.
- Adapter bleiben klein und fachlich begrenzt. Es entsteht keine zweite allgemeine Komponentenbibliothek.
- Private Package-Imports und Änderungen in `node_modules` sind unzulässig.
- Neue Dependencies brauchen einen konkreten Nutzen, Vue-3-kompatible Peer-Dependencies, akzeptable Lizenz, aktiven Wartungsstand und eine Prüfung der Bundleauswirkung.
- Nicht vertrauenswürdige Inhalte werden nicht als Vue-Template kompiliert. Vorhandene Markdown-/HTML-Pfade behalten Sanitizing und Sicherheitsgrenzen.

## Prüfvertrag

Für jede Frontendänderung gelten mindestens:

1. gezieltes Linting der betroffenen Dateien;
2. betroffene Unit- oder Vertragstests;
3. `npm run build`;
4. ein repräsentativer Browserablauf auf Desktop und Mobile, wenn Interaktion oder Darstellung berührt werden;
5. Prüfung auf Page Errors und relevante Console Errors;
6. dokumentierte Restlücken statt stillschweigender Annahmen.

Die vollständigen Befehle und Release-Gates stehen in `docs/ai/quality-gates.md`. Der spezifische Austausch des Datepickers folgt `docs/ai/datepicker-vuepic-migration-contract.md`.

## Fertig-Definition für Paketablösungen

Eine alte Frontend-Dependency gilt erst als entfernt, wenn:

- keine Source-Datei, kein Manifest und kein Lockfile mehr auf sie verweist;
- ihre paketbezogenen CSS-Imports und Kompatibilitätsselektoren entfernt sind;
- alle bekannten Verbraucher über die neue Adapter-/Wrappergrenze laufen;
- der Production-Build erfolgreich ist;
- die betroffenen Nutzerabläufe im Browser ohne Page Error funktionieren;
- bewusst vertagte Prüfungen mit Risiko und Folgeauftrag dokumentiert sind.

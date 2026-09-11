# Vue-3-Migration – Stufe 6: Vuex zu Pinia

Status: In Arbeit. Die Migration erfolgt Store für Store; jeder Abschnitt nennt den alleinigen schreibenden Store, seine migrierten Konsumenten und die Abnahme. Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Letzter vollständig abgeschlossener Stufenstand ist `9e2593b5`.

## Pinia-Basis und Parallelbetrieb

- Pinia ist exakt auf Version `4.0.3` gelockt. Die am 11. September 2026 aus der npm-Registry geprüften Metadaten nennen MIT-Lizenz, Vue-Peer `^3.5.11` und das offizielle Repository `vuejs/pinia`; installiert ist Vue `3.5.42`.
- Die Anwendung erzeugt genau eine Pinia-Instanz und installiert sie vor Router und Mount. Vuex 4.1.0 bleibt während der Store-für-Store-Migration parallel installiert.
- Pinia-Stores werden nicht bei Modulinitialisierung ausgewertet. Komponenten greifen erst innerhalb einer Nutzeraktion und damit nach Installation der App-Instanz auf sie zu.
- Ein Fachdatensatz hat während des Parallelbetriebs genau einen schreibenden Store. Migrierte Module werden aus der Vuex-Registrierung entfernt; eine Watch-basierte Synchronisation existiert nicht.
- `npm audit` bleibt nach der Pinia-Installation unverändert bei 10 Befunden (1 low, 4 moderate, 5 high). Es wurde kein automatischer Audit-Fix ausgeführt.

## Teilstufe 6.1 – Zuletzt verwendete Materialien

`recentmaterials` war der kleinste isolierte Schreibstore: zwei Commit-Aufrufstellen, keine Requests, keine Fehlerpfade und kein derzeitiger Getter-Konsument. Er speichert höchstens fünf Material-IDs, behält bei Duplikaten die erste Position und entfernt bei Überschreitung die älteste ID.

- `resources/js/apps/main/stores/recentMaterials.js` bildet State, Getter und Mutation als Pinia-State, Getter und Action ab.
- Material-Erstellung und Material-Auswahl rufen die Pinia-Action direkt auf.
- Das frühere Vuex-Modul ist gelöscht und nicht mehr im Root-Store registriert.
- `tests/js/recentMaterialsStore.spec.js` schützt Deduplizierung, Reihenfolge und Fünferlimit mit frischer Pinia-Instanz pro Test.

Abnahme: Production-Build mit 967 transformierten Modulen, 19 Vitest-Dateien mit 42/42 Tests, ESLint mit 0 Fehlern und 318 bekannten Warnungen sowie das Frontend-Inventar sind grün. Die Material-Creator-Reise besteht auf Desktop und Mobile. Das Inventar weist noch exakt die 15 erwarteten Vuex-Module aus und keine Vue-2-/Compat-Muster. Die bekannten Sass- und Chunkgrößenhinweise sind gegenüber Stufe 5 unverändert.

Rückbau: Der zusammengehörige Teilstufencommit entfernt Pinia wieder aus App und Lockfile, registriert das unveränderte Vuex-Modul erneut und stellt die beiden Vuex-Commit-Aufrufstellen wieder her.

## Noch zu migrieren

Nach Teilstufe 6.1 verbleiben 15 registrierte Vuex-Module: `resources`, `materials`, `materialusages`, `materialapp`, `keywords`, `keywordsSuggestions`, `bibleverses`, `bibleverseCrossReferences`, `search`, `tagsearch`, `bundles`, `biblecontents`, `general`, `bibles` und `users`. Die Reihenfolge bleibt risikobasiert: zuerst read-mostly beziehungsweise isolierte Module, dann gekoppelte Cache-/Relationsmodule und zuletzt die zentralen Material-/Resource-/General-Pfade. Vuex wird erst nach dem letzten migrierten Konsumenten entfernt.

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

## Teilstufe 6.2 – Suchvorschläge für Tags

`tagsearch` enthielt keinen State und genau eine Action mit einem Konsumenten. `useTagSearchStore` übernimmt denselben GET auf `/pool/search/guess`, denselben Query-Parameter `q`, die unveränderte Response-Body-Rückgabe und das bestehende Verhalten, einen abgewiesenen Wert zu protokollieren und anschließend als erfüllten Action-Wert zurückzugeben.

- `searchInput.vue` verwendet den Pinia-Store innerhalb der bestehenden debouncten Suche; Filterung, Loading-Callback und Auswahl bleiben unverändert.
- Das frühere Vuex-Modul ist gelöscht und aus der Root-Registrierung entfernt.
- Zwei Unit-Tests schützen Requestform, Objektidentität der Erfolgsantwort und Objektidentität samt Logging im Fehlerfall.

Abnahme: Production-Build, 20 Vitest-Dateien mit 44/44 Tests, ESLint mit 0 Fehlern und nun 315 bekannten Warnungen sowie das Frontend-Inventar sind grün. Die einzige direkte Nutzerreise „Vue 3 select keeps asynchronous search and object selection“ besteht auf Desktop und Mobile; sie prüft den echten debouncten Request, Ergebnisdarstellung, Tastaturauswahl und resultierende Route.

Rückbau: Der Teilstufencommit stellt ausschließlich das Vuex-Modul, seine Registrierung und den einen Dispatch-Aufruf wieder her; die Pinia-Basis aus Teilstufe 6.1 bleibt davon unabhängig.

## Teilstufe 6.3 – Benutzersuche

`users` besitzt einen Suchrequest und genau einen Konsumenten in der Bearbeitung von Materialnutzungen. Der Pinia-Store bewahrt URL, optionale `limit`-Semantik, Erfolgsrückgabe und die über `convertErrorResponseToMessage` geworfene Fehlermeldung.

Die Vuex-Getter und `setUsers` verwendeten nachweislich den literalen Schlüssel `id` beziehungsweise `data.id` statt der jeweiligen Benutzer-ID. Dadurch war der interne Cache trotz gefüllter Suchantwort nicht per ID lesbar. Im Repository existierte kein Getter-Konsument; die sichtbare Suche verwendet unverändert die direkt zurückgegebene Liste. Der Pinia-Port indexiert die Objekte korrekt per `user.id`. Diese lokale Fehlerbehebung verändert daher weder Request noch sichtbaren Ablauf, macht aber State, Getter und `clearUser` konsistent und testbar.

- Drei Unit-Tests schützen Parameter mit und ohne Limit, unveränderte Rückgabe, konvertierte Rejection, ID-Indexierung und gezieltes Entfernen.
- Das Vuex-Modul ist gelöscht; `usageListElement.vue` ruft die Pinia-Action innerhalb seines bestehenden 250-ms-Debounce auf.
- Abnahme: Production-Build, 21 Vitest-Dateien mit 47/47 Tests, ESLint mit 0 Fehlern und 310 bekannten Warnungen sowie Frontend-Inventar mit exakt 13 Vuex-Modulen sind grün.
- Offenes gekoppeltes Gate: Der Repository-Bestand enthält noch keine Browserfixture für Suche, Speichern und Löschen eines Materialnutzungseintrags. Diese Nutzerreise wird zusammen mit dem unmittelbar zugehörigen `materialusages`-Store ergänzt und muss beide Stores gemeinsam abnehmen. Bis dahin belegen Unit-Test und vollständiger SFC-Build den Users-Slice, nicht die gesamte Usage-Interaktion.

Rückbau: Der Teilstufencommit stellt das alte Vuex-Modul, seine Registrierung und den Dispatch in `usageListElement.vue` wieder her; andere Pinia-Stores bleiben unverändert.

## Noch zu migrieren

Nach Teilstufe 6.3 verbleiben 13 registrierte Vuex-Module: `resources`, `materials`, `materialusages`, `materialapp`, `keywords`, `keywordsSuggestions`, `bibleverses`, `bibleverseCrossReferences`, `search`, `bundles`, `biblecontents`, `general` und `bibles`. Die Reihenfolge bleibt risikobasiert: zuerst read-mostly beziehungsweise isolierte Module, dann gekoppelte Cache-/Relationsmodule und zuletzt die zentralen Material-/Resource-/General-Pfade. Vuex wird erst nach dem letzten migrierten Konsumenten entfernt.

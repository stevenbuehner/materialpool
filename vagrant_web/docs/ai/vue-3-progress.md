# Vue-Migration – Fortschritt

Stand: 11. September 2026, `master`, Ausgangs-HEAD `9e2593b5`; `.env.dev` und die außerhalb des Projekts liegende XML-Datei sind unangetastete Nutzerdateien.

Auftrag: Vollständige schrittweise Umsetzung des [Vue-3-Migrationsvertrags](vue-3-migration-contract.md), mit Git-Commit nach jeder grünen Stufe beziehungsweise klar abgegrenzten Teilstufe.

Referenz: Stufenberichte 0 bis 5; Vite-Cutover `9e2593b5`; PHP 8.4.25, Node 24.21.0, npm 11.19.0, Vue 3.5.42, Vite 8.2.2, WebKit aus Playwright 1.63.0.

Aktuelle Stufe: 6 – Vuex 4 modulweise auf Pinia, in Arbeit.

Abgeschlossen:

- Stufen 0 bis 5 gemäß `docs/ai/vue-3-stage-0-report.md` bis `docs/ai/vue-3-stage-5-report.md`.
- Teilstufe 6.1 implementiert: `recentmaterials` ist als kleinster schreibender Store migriert; Vertragstests liegen in `tests/js/recentMaterialsStore.spec.js`.
- Teilstufe 6.2 implementiert: die zustandslose `tagsearch`-Action und ihr einziger Konsument verwenden Pinia; Request-, Erfolgs- und Fehlerverträge sind getestet.
- Teilstufe 6.3 implementiert: `users` und sein einziger Konsument verwenden Pinia; der interne ID-Indexierungsfehler ist testgedeckt behoben.
- Teilstufe 6.4 implementiert: `materialusages` und sämtliche Konsumenten verwenden Pinia; sieben Store-Tests und die gekoppelte Materialdetail-Browserreise schützen Create, Search, Update und Delete.
- Teilstufe 6.5 implementiert: `bibles`, alle vier Komponenten-Konsumenten und die beiden gekoppelten Writer-Aufrufe aus `biblecontents` verwenden Pinia; Erstladefehler, UUID-Cache und parallele Abrufe sind testgedeckt.
- Teilstufe 6.6 implementiert: `biblecontents` und alle fünf Komponenten-Konsumenten verwenden Pinia; Queue, parallele Requests, Cache-Schlüssel, Retry und Suche sind testgedeckt und die Bibelleser-Browserreise zeigt reale Versinhalte.
- Teilstufe 6.7 implementiert: `bibleverseCrossReferences` und beide Komponenten-Konsumenten verwenden Pinia; Pagination, Queue, Cache und Zero-Count sind testgedeckt, die Schlagwortoptimierung zeigt Querverweis und Bibeltext responsiv im Browser.
- Teilstufe 6.8 implementiert: `bibleverses` und alle fünf Komponenten-Konsumenten verwenden Pinia; Cache, CRUD-Payloads, Queue und Fehlerverträge sind testgedeckt, die Bibelleser-Reise schützt Suche, Route und neuen Versinhalt responsiv.
- Teilstufe 6.9 implementiert: `bundles` und alle vier Komponenten-Konsumenten verwenden Pinia; Promise-/Daten-/Icon-Caches und Job-Payloads sind testgedeckt, ein vollständig gemocktes Update wechselt die sichtbare Version responsiv.
- Teilstufe 6.10 implementiert: `keywords`, elf direkte Konsumenten und der Übergabewriter aus `keywordsSuggestions` verwenden Pinia; Cache, CRUD, Queue, Suche und Fehlerverträge sind testgedeckt, Keyword-Tree und Materialdetail sind responsiv im Browser abgenommen.
- Teilstufe 6.11 implementiert: `keywordsSuggestions` und beide Konsumenten verwenden Pinia; Pagination, Count, Queue, Fehlerwert und parallele Request-Zusammenführung sind testgedeckt, die Optimierung zeigt Count, Vorschlag und Relevanz responsiv.

Offene Gates:

- Teilstufe 6.11: Commit.
- Stufe 6: weitere 5 Vuex-Module samt sämtlichen Konsumenten; danach Entfernung von Vuex.
- Stufe 7: vollständige Releasegates einschließlich `migrate:fresh` und `db:seed` ausschließlich gegen die verifizierte, entbehrliche Sail-MySQL-Datenbank `testing`.

Compat-/Paket-Ausnahmen: Keine Vue-2-/`@vue/compat`-Ausnahme. Vuex 4.1.0 bleibt nur bis zur Migration des letzten Moduls parallel zu Pinia 4.0.3 installiert.

Nächster Schritt: Teilstufe 6.11 committen; anschließend die gegenseitigen Schreibpfade und Konsumenten von `materials` und `resources` vollständig vermessen und den kleinsten sicheren nächsten Slice umsetzen.

Rückbau: Letzter vollständig grüner Stufenstand ist `9e2593b5`. Pinia-Teilstufen bleiben bis zur finalen Vuex-Entfernung einzeln rückbaubar.

Entscheidungen: Die fünf freigegebenen Optionen A gelten unverändert; es ist keine neue Produkt-, UI-, API-, Daten- oder Infrastrukturentscheidung hinzugekommen.

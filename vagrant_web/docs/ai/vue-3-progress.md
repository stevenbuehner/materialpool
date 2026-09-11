# Vue-Migration – Fortschritt

Stand: 11. September 2026, `master`, Ausgangs-HEAD `9e2593b5`; `.env.dev` und die außerhalb des Projekts liegende XML-Datei sind unangetastete Nutzerdateien.

Auftrag: Vollständige schrittweise Umsetzung des [Vue-3-Migrationsvertrags](vue-3-migration-contract.md), mit Git-Commit nach jeder grünen Stufe beziehungsweise klar abgegrenzten Teilstufe.

Referenz: Stufenberichte 0 bis 5; Vite-Cutover `9e2593b5`; PHP 8.4.25, Node 24.21.0, npm 11.19.0, Vue 3.5.42, Vite 8.2.2, WebKit aus Playwright 1.58.2.

Aktuelle Stufe: 6 – Vuex 4 modulweise auf Pinia, in Arbeit.

Abgeschlossen:

- Stufen 0 bis 5 gemäß `docs/ai/vue-3-stage-0-report.md` bis `docs/ai/vue-3-stage-5-report.md`.
- Teilstufe 6.1 implementiert: `recentmaterials` ist als kleinster schreibender Store migriert; Vertragstests liegen in `tests/js/recentMaterialsStore.spec.js`.
- Teilstufe 6.2 implementiert: die zustandslose `tagsearch`-Action und ihr einziger Konsument verwenden Pinia; Request-, Erfolgs- und Fehlerverträge sind getestet.

Offene Gates:

- Teilstufe 6.2: Commit des vollständig grünen Slices.
- Stufe 6: weitere 14 Vuex-Module samt sämtlichen Konsumenten; danach Entfernung von Vuex.
- Stufe 7: vollständige Releasegates einschließlich `migrate:fresh` und `db:seed` ausschließlich gegen die verifizierte, entbehrliche Sail-MySQL-Datenbank `testing`.

Compat-/Paket-Ausnahmen: Keine Vue-2-/`@vue/compat`-Ausnahme. Vuex 4.1.0 bleibt nur bis zur Migration des letzten Moduls parallel zu Pinia 4.0.3 installiert.

Nächster Schritt: Teilstufe 6.2 committen; anschließend anhand der Store-/Konsumentenmatrix das nächste read-mostly Modul auswählen.

Rückbau: Letzter vollständig grüner Stufenstand ist `9e2593b5`. Pinia-Teilstufen bleiben bis zur finalen Vuex-Entfernung einzeln rückbaubar.

Entscheidungen: Die fünf freigegebenen Optionen A gelten unverändert; es ist keine neue Produkt-, UI-, API-, Daten- oder Infrastrukturentscheidung hinzugekommen.

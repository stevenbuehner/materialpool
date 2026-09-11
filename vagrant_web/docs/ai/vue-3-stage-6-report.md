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
- Das zuvor offene gekoppelte Gate ist in Teilstufe 6.4 geschlossen: Die Materialdetail-Reise prüft Benutzersuche und Auswahl zusammen mit Anlegen, Speichern und Löschen eines Anlasses.

Rückbau: Der Teilstufencommit stellt das alte Vuex-Modul, seine Registrierung und den Dispatch in `usageListElement.vue` wieder her; andere Pinia-Stores bleiben unverändert.

## Teilstufe 6.4 – Materialnutzungen

`materialusages` war der alleinige Writer für die pro Material-ID gruppierte Anlassliste. Der Pinia-Port erhält die bisherigen API-Pfade, POST-/Method-Spoofing-Payloads, `moment(...).format()`-Normalisierung, Defaultwerte, Erfolgsrückgaben, Leerlistenbehandlung und die über `convertErrorResponseToMessage` geworfenen Fehlermeldungen. `usageEdit.vue` sowie `usageListElement.vue` sind die einzigen Konsumenten und rufen nun den Pinia-Store auf; das Vuex-Modul ist gelöscht und aus dem Root-Store entfernt.

Zwei lokale Defekte wurden beim verhaltensneutralen Port behoben:

- Die frühere Bedingung `!value instanceof Array` wertete wegen JavaScript-Operatorpräzedenz nie wie beabsichtigt aus. Ein fehlender oder beschädigter Material-Cache wird jetzt vor Einfügen eines einzelnen Datensatzes zuverlässig als Array initialisiert.
- Ein fachlich fehlgeschlagenes HTTP-200-Löschergebnis wurde erst in eine Meldung konvertiert, danach erneut gefangen und ein zweites Mal konvertiert. Dadurch konnte die Servermeldung zu `undefined error` werden. Der Pinia-Store transportiert diese Meldung einmalig durch den bestehenden Fehlerkonverter; der UI-Fehlerpfad bleibt derselbe, erhält aber wieder den verwertbaren Text.

Sieben Unit-Tests schützen Laden und Cache-Wiederverwendung, die bewusst nicht gecachte Leerantwort, Create-Defaults, Update und Cache-Ersetzung, erfolgreiches Löschen, die Servermeldung eines fachlich erfolglosen Löschens sowie Request-Rejections. Die erweiterte Materialdetail-Browserreise prüft darüber hinaus den tatsächlichen Ablauf: Anlass anlegen, aktuellen Benutzer übernehmen, Auswahl leeren, debouncte Benutzersuche ausführen, anderen Benutzer auswählen, Grund und Ort speichern, das aktualisierte Anzeigeobjekt sehen, Löschbestätigung akzeptieren und den Eintrag aus der Liste entfernen. Dabei werden Create-, Search-, Update- und Delete-Payloads explizit geprüft.

Abnahme: Production-Build mit 967 transformierten Modulen, 22 Vitest-Dateien mit 54/54 Tests, ESLint mit 0 Fehlern und 296 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Der gekoppelte Materialdetail-Browsertest besteht im Desktop-WebKit; seine vorhandenen Referenzbilder vor der Interaktion bleiben unverändert. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 12 registrierte Vuex-Module. `php artisan dev --inline` wurde neu gestartet; Laravel läuft auf Port 8000 und Vite schreibt den aktiven Hot-Endpunkt auf Port 5174.

Rückbau: Der Teilstufencommit stellt das Vuex-Modul und seine Registrierung wieder her und setzt die drei Komponentenaufrufe auf `dispatch` zurück. Die bereits unabhängigen Pinia-Stores einschließlich `users` bleiben erhalten.

## Noch zu migrieren

Nach Teilstufe 6.4 verbleiben 12 registrierte Vuex-Module: `resources`, `materials`, `materialapp`, `keywords`, `keywordsSuggestions`, `bibleverses`, `bibleverseCrossReferences`, `search`, `bundles`, `biblecontents`, `general` und `bibles`. Die Reihenfolge bleibt risikobasiert: zuerst read-mostly beziehungsweise isolierte Module, dann gekoppelte Cache-/Relationsmodule und zuletzt die zentralen Material-/Resource-/General-Pfade. Vuex wird erst nach dem letzten migrierten Konsumenten entfernt.

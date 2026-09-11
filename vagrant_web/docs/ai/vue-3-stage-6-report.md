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

## Teilstufe 6.5 – Bibelübersetzungen

`bibles` ist ein read-mostly UUID-Cache mit Einzelabruf und paginiertem Vollabruf. `useBiblesStore` erhält die beiden API-Pfade, die UUID-Indexierung und den Drei-Zustands-Vertrag des Vollabrufs: `false` vor dem ersten Request, dasselbe laufende Ergebnis für parallele Aufrufer und `true` nach erfolgreichem Abschluss. Alle vier Komponenten-Konsumenten verwenden Pinia. Das noch nicht migrierte `biblecontents` übergibt mitgelieferte Übersetzungsmetadaten nun ebenfalls an die Pinia-Action; damit bleibt Pinia der einzige Writer dieses Datensatzes und es gibt keine Store-Synchronisation.

Der alte Erstladepfad rief nach erfolgreichem Request `state.allBibles.values()` auf. `allBibles` ist ein Plain Object und besitzt diese Methode nicht, sodass ausgerechnet der erste Aufruf nach gefülltem Cache mit einem `TypeError` endete. Der Port gibt korrekt `Object.values(this.allBibles)` zurück. Drei Unit-Tests schützen Einzelabruf und UUID-Cache, explizites Hinzufügen sowie die Zusammenführung paralleler Vollabrufe einschließlich des ersten und aller späteren Rückgabewerte.

Abnahme: Production-Build mit 967 transformierten Modulen, 23 Vitest-Dateien mit 57/57 Tests, ESLint mit 0 Fehlern und 293 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Eine neue Browserreise lädt die echte lazy geladene Bibelleser-Route, prüft den einmaligen paginierten Listenrequest sowie Anzeige und Auswahl einer Übersetzung; sie besteht in Desktop- und Mobile-WebKit. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 11 registrierte Vuex-Module.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die vier Komponenten-Dispatches und die beiden Root-Commits in `biblecontents` wieder her und entfernt Store und Tests. Andere Pinia-Slices bleiben unabhängig.

## Teilstufe 6.6 – Bibeltexte

`biblecontents` verwaltet Versbereiche im Speicher, serialisiert HTTP-Zugriffe über die bestehende `PQueue` und speist gefundene Übersetzungsmetadaten in den bereits migrierten `bibles`-Store. Der Pinia-Port erhält die Routenbildung einschließlich optionaler Übersetzungs-UUID, das bisherige Response- und Suchfehlerverhalten, die Reihenfolge von `getMultiple`, die Cache-Schlüssel aus `getRangeId` sowie das doppelte Caching eines Defaultabrufs unter Default- und tatsächlich aufgelöster Übersetzung. Alle fünf Komponenten-Konsumenten verwenden nun Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt.

Zwei lokale Cachedefekte wurden behoben:

- Der alte Code erzeugte zwar ein Queue-Promise, schrieb es aber erst **nach** `await` in den Cache. Parallele identische Aufrufe starteten deshalb mehrere Requests, obwohl Kommentar und nachfolgender Promise-Zweig ausdrücklich Zusammenführung vorsahen. Der Pinia-Store cached das laufende Promise vor dem Warten. Bei Rejection wird der Eintrag entfernt, sodass der bisher mögliche spätere Retry erhalten bleibt.
- `searchAndGet` normalisierte eine nicht übergebene `bibleUuid` nicht auf `null`. Dadurch wurde die Defaultantwort zwar unter dem leeren Schlüssel, wegen des strikten `=== null` aber nicht zusätzlich unter der vom Server gelieferten Übersetzungs-UUID gespeichert. Beide Schlüssel werden jetzt konsistent wie im normalen `get`-Pfad befüllt.

Sechs Unit-Tests schützen Queue und beide Cache-Schlüssel, Zusammenführung paralleler Requests, Retry nach Fehler, Ergebnisreihenfolge mehrerer Bereiche, Suchroute/-parameter/-caching und den unverändert geloggten und geworfenen fachlichen Suchfehler. Die in Teilstufe 6.5 eingeführte Browserreise lädt nun zusätzlich einen realen Versbereich, prüft den einmaligen Content-Request im bestehenden neunstelligen Versnummernformat sowie beide sichtbaren Verstexte. Auswahl und Anzeige der Übersetzungen bleiben enthalten.

Abnahme: Production-Build mit 967 transformierten Modulen, 24 Vitest-Dateien mit 63/63 Tests, ESLint mit 0 Fehlern und 288 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die gekoppelte Bibelleser-Reise besteht in Desktop- und Mobile-WebKit. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 10 registrierte Vuex-Module.

Rückbau: Der Teilstufencommit registriert das Vuex-Modul erneut und stellt die fünf Komponenten-Dispatches wieder her. Der eigenständige Pinia-Store `bibles` aus Teilstufe 6.5 bleibt bestehen; seine beiden Writer-Aufrufe werden beim Rückbau wieder als Root-Commits aus `biblecontents` ausgeführt.

## Teilstufe 6.7 – Bibel-Querverweise

`bibleverseCrossReferences` hält paginierte Querverweise und deren Gesamtzahl pro Bibelversbereich. Der Pinia-Port erhält den v2-API-Pfad, das bisherige Standardmaximum 50, die Bereichsnormalisierung, die Begrenzung der Rückgabe auf `maximum`, die sequenzielle Mehrfachabfrage über die bestehende `PQueue` sowie den bisherigen Vertrag, konvertierte Requestfehler als erfüllten Action-Wert zurückzugeben. Beide Komponenten-Konsumenten verwenden Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt.

Ein lokaler Cachedefekt wurde behoben: Der frühere Count-Getter prüfte den gespeicherten Wert auf Truthiness. Ein korrekt geladener Count von `0` wurde dadurch wie „nicht geladen“ behandelt und löste bei jedem Zugriff erneut einen Request aus. Der neue Getter unterscheidet mit Nullish-Prüfung zuverlässig zwischen `0` und einem fehlenden Cachewert.

Fünf Unit-Tests schützen Pagination bis zum gewünschten Maximum, Wiederverwendung vollständig geladener Daten, den Zero-Count-Cache, den bestehenden erfüllten Fehlerwert und Reihenfolge/Queue-Nutzung von `getMultiple`. Eine neue responsive Browserreise öffnet die Schlagwortoptimierung aus einer real geparsten Bibelstellen-Suche, prüft den Querverweis-Request, den sichtbaren Zielvers und dessen Bibeltext. Dabei wurde ein bestehender Darstellungsfehler sichtbar und behoben: Die Vorschlagsobjekte wurden nachträglich über ihre nicht-reaktive Rohreferenz verändert und blieben deshalb dauerhaft bei `is loading ...`. Die Komponente lädt Text und Übersetzung nun vor Rückgabe des Vorschlags; Funktion und endgültige Darstellung sind unverändert, der vorgesehene Endzustand erscheint zuverlässig.

Abnahme: Production-Build mit 967 transformierten Modulen, 25 Vitest-Dateien mit 68/68 Tests, ESLint mit 0 Fehlern und 288 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die neue Querverweis-Reise besteht in Desktop- und Mobile-WebKit. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 9 registrierte Vuex-Module. Die bekannten Sass-Deprecations und der Chunkgrößenhinweis bleiben unverändert.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die beiden Komponenten-Dispatches wieder her und entfernt Store und Tests. Die Pinia-Stores für Bibeltexte und Übersetzungen bleiben davon unabhängig.

## Teilstufe 6.8 – Bibelstellen

`bibleverses` verwaltet den ID-Cache und die schreibenden Abläufe zum Anlegen, Zuordnen, Relevanzändern und Entfernen von Bibelstellen sowie die eigenständige Bibelstellensuche. Der Pinia-Port erhält alle bestehenden GET-/POST-Pfade, Method-Spoofing-Payloads, das Weglassen einer falsy Relevanz zugunsten des Serverdefaults, die Queue-Nutzung und Ergebnisreihenfolge bei Mehrfachaktionen sowie die unterschiedlichen historischen Fehlerverträge: normale CRUD-Fehler werden konvertiert als erfüllter Wert zurückgegeben, der Suchfehler wird weiterhin mit `response.data` geworfen. Alle fünf Komponenten-Konsumenten verwenden Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt.

Der etablierte, fehlerhaft geschriebene Action-Name `deleteMultipleAssignemts` bleibt absichtlich erhalten, damit der öffentliche JS-Vertrag dieser Teilstufe nicht nebenbei geändert wird. Eine Korrektur kann erst nach Entfernung aller Legacy-Aufrufe als eigenes, mechanisches Refactoring erfolgen. Es wurden keine Backend-Routen, Controller, Policies, Events oder Queue-Jobs verändert; insbesondere bleibt `CheckLonelyBibleverse` serverseitig im Löschablauf maßgeblich.

Acht Unit-Tests schützen ID-Cache, erfüllten Ladefehler, Queue und Reihenfolge von Mehrfachabrufen, Create-and-Assign samt Payloads, Serverdefault bei falsy Relevanz, Einzel- und Mehrfachlöschung sowie Sucherfolg und geworfenen Suchfehler. Die responsive Bibelleser-Reise wurde um eine echte Eingabe erweitert und prüft den Such-POST, den daraus folgenden Routenwechsel und den neu geladenen sichtbaren Bibeltext.

Abnahme: Production-Build mit 967 transformierten Modulen, 26 Vitest-Dateien mit 76/76 Tests, ESLint mit 0 Fehlern und 273 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die erweiterte Bibelleser-Reise besteht in Desktop- und Mobile-WebKit. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 8 registrierte Vuex-Module. Die bekannten Sass-Deprecations und der Chunkgrößenhinweis bleiben unverändert.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die elf direkten Komponenten-Dispatches wieder her und entfernt Pinia-Store und Unit-Tests. Die bereits migrierten Stores für Bibeltexte, Übersetzungen und Querverweise bleiben unabhängig.

## Teilstufe 6.9 – Bundles

`bundles` verwaltet die gemeinsam geladene Liste installierter Bundles, lokale Bundle-Metadaten, Icons sowie die Initialisierung und schrittweise Ausführung von Update- und Deinstallationsjobs. Der Pinia-Port erhält den Drei-Zustands-Cache aus `null`, laufendem Promise und fertigem Array, die Zusammenführung paralleler Erstabrufe, erzwungenes Neuladen beider Listen, Lookup- und Fehlertexte, Icon-Promise-Caching, Requestpfade und `{timeout: 0}` sowie die Aktualisierung des vorhandenen Bundle-Objekts nach Abschluss der Queue. Alle vier Komponenten-Konsumenten verwenden Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt.

Die etablierten Bezeichner `getAllBundeInfos` und die als erfüllte Werte zurückgegebenen konvertierten Fehler der Init-, Run- und Icon-Actions bleiben absichtlich erhalten. Damit ändert diese Teilstufe weder Komponentensteuerung noch Fehlerfluss. Backend-Routen, `auth:api`, Bundle-Dateien, Datenbank, Jobs und Queue-Zustand wurden nicht verändert. Die Browserabnahme fängt alle Bundle-Requests ab und führt daher keinen echten Import, keine echte Deinstallation und keinen Queue-Lauf aus.

Acht Unit-Tests schützen parallelen Erstabruf, Force-Reload, UUID-Lookups samt exakten Fehlern, Update-/Uninstall-Initialisierung, erfüllten Fehlerwert, Run-Payload und Cache-Merge, Icon-Cache sowie ID-/Namenslookup einschließlich der historischen String-Rejection `not found`. Eine neue responsive Browserreise lädt die Bundle-Übersicht mit Metadaten, startet den vollständig gemockten Updateablauf, prüft Init- und Run-POSTs und verifiziert den sichtbaren Wechsel von Version 1.0 auf 2.0.

Abnahme: Production-Build mit 967 transformierten Modulen, 27 Vitest-Dateien mit 84/84 Tests, ESLint mit 0 Fehlern und 258 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die Bundle-Reise besteht in Desktop- und Mobile-WebKit. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 7 registrierte Vuex-Module. Die bekannten Sass-Deprecations und der Chunkgrößenhinweis bleiben unverändert.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die neun direkten Komponenten-Dispatches wieder her und entfernt Pinia-Store, Tests und die isolierte Bundle-Browserreise. Andere Pinia-Slices bleiben unabhängig.

## Noch zu migrieren

Nach Teilstufe 6.9 verbleiben 7 registrierte Vuex-Module: `resources`, `materials`, `materialapp`, `keywords`, `keywordsSuggestions`, `search` und `general`. Die verbleibende Reihenfolge berücksichtigt nun ihre Kopplung: `keywords` vor `keywordsSuggestions`, `materials` und `resources` vor ihren schreibenden Sekundärstores `search` und `materialapp`, `general` nach den fachlichen Stores. Vuex wird erst nach dem letzten migrierten Konsumenten entfernt.

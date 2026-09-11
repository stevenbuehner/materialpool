# Vue-3-Migration – Stufe 6: Vuex zu Pinia

Status: In Arbeit. Die Migration erfolgt Store für Store; jeder Abschnitt nennt den alleinigen schreibenden Store, seine migrierten Konsumenten und die Abnahme. Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Letzter vollständig committeter Teilstufenstand ist 6.14 (`5e39bd2d`).

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

## Teilstufe 6.10 – Schlagwörter

`keywords` verwaltet den zentralen ID-Cache sowie Laden, Suche, Anlegen, Bearbeiten, Zuordnen, Relevanzänderung und Löschen von Schlagwörtern. Der Pinia-Port erhält die bestehenden v1- und Such-API-Pfade, Requestparameter, Method-Spoofing-Payloads, Seitennavigation, Queue-Reihenfolge, Serverdefault bei falsy Relevanz und die historischen erfüllten beziehungsweise geworfenen Fehlerverträge. Die 24 direkten Komponenten- und Helper-Aufrufe in elf Konsumenten verwenden Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt. Der noch nicht migrierte Store `keywordsSuggestions` schreibt geladene Vorschläge über die Pinia-Action in denselben Cache. Damit bleibt Pinia auch während des Parallelbetriebs der einzige Writer für Schlagwortdaten.

Zwei lokale Fehler wurden im unmittelbar betroffenen Ablauf behoben:

- Der alte Delete-Pfad wandelte ein fachlich fehlgeschlagenes HTTP-200-Ergebnis zunächst in eine aussagekräftige Meldung um, fing diese anschließend erneut ab und konvertierte sie zu `undefined error`. Requestfehler und fachliche Fehler durchlaufen den Konverter jetzt jeweils genau einmal.
- Der Keyword-Tree schrieb bei jeder Filteränderung in sein read-only `page`-Prop. Unter Vue 3 brach dadurch die reaktive Filteraktualisierung ab. Die wirkungslose Prop-Mutation ist entfernt; Laden, Filtern und Force-Refresh sind responsiv im Browser geprüft. Beim Warnungsabgleich wurde außerdem der fehlerhafte Bezeichner `backKw` im API-Fehler-Rollback des Drag-and-drop-Pfads auf das tatsächlich vorhandene Backup `backKW` korrigiert.

13 Unit-Tests schützen Clone-ohne-Pivot, Einzel-, Mehrfach- und Vollabruf einschließlich Force-Reload, Relationszählung, Create-and-Assign, Update samt Merge-Invalidierung, Relevanzpayload, Einzel- und Mehrfachentfernung, die beiden Löschfehlerklassen, paginierte Suche und deduplizierte Mehrfachsuche. Eine neue Browserreise prüft Keyword-Tree, Filter und erzwungenes Neuladen. Die bestehende Materialdetail-Reise deckt zusätzlich Laden eines Schlagworts, Relevanzänderung per Drag und Übergabe an die Suche ab; ihre flüchtigen Erfolgsmeldungen werden vor dem bestehenden Modal-Screenshot explizit geschlossen, damit die visuelle Referenz deterministisch bleibt.

Abnahme: Production-Build mit 967 transformierten Modulen, 28 Vitest-Dateien mit 97/97 Tests, ESLint mit 0 Fehlern und 238 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Keyword-Tree und Materialdetail bestehen in Desktop- und Mobile-WebKit (4/4 Prüfungen); die visuellen Referenzen bleiben unverändert. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 6 registrierte Vuex-Module. Backend-Routen, Controller, Policies, `CheckLonelyKeyword`, Datenbank, Events und Queues wurden nicht verändert. Die bekannten Sass-Deprecations und der Chunkgrößenhinweis bleiben unverändert.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die 25 Aufrufe in zwölf Konsumenten einschließlich des Übergabewriters aus `keywordsSuggestions` wieder her und entfernt Pinia-Store, Unit-Tests und die Keyword-Browserreise. Andere Pinia-Slices bleiben unabhängig.

## Teilstufe 6.11 – Schlagwortvorschläge

`keywordsSuggestions` hält Vorschlagslisten und deren Gesamtzahl pro Ausgangsschlagwort. Der Pinia-Port erhält den v2-API-Pfad, das Standardmaximum 50, die Pagination bis zum angeforderten Maximum, Teilmengenrückgabe, Queue und Reihenfolge der Mehrfachabfrage sowie den historischen Vertrag, konvertierte Requestfehler als erfüllten Action-Wert zurückzugeben. Beide Komponenten-Konsumenten verwenden Pinia; das Vuex-Modul und seine Root-Registrierung sind entfernt. Die Vorschlagsobjekte behalten ihre `relevance`, während eine tiefe Kopie ohne `relevance` an den zentralen `keywords`-Store übergeben wird. Damit bleibt dieser weiterhin der einzige Writer des allgemeinen Schlagwort-Caches.

Zwei lokale Cacheverbesserungen wurden testgedeckt umgesetzt:

- Der frühere Count-Getter prüfte den gespeicherten Wert auf Truthiness. Ein korrekt geladener Count von `0` wurde daher wie „nicht geladen“ behandelt und erneut angefragt. Nullish-Prüfung unterscheidet jetzt zuverlässig zwischen Nulltreffer und fehlendem Cache.
- Count und Vorschlagsliste werden im Dialog parallel lazy berechnet. Vor dem ersten Cache-Schreibvorgang entstanden dadurch bis zu vier identische Requests. Der Store führt laufende Abrufe nun pro Ausgangsschlagwort zusammen. Ein nachfolgend angefordertes größeres Maximum wird nach Abschluss weiterhin gegen Cache und Gesamtzahl geprüft und bei Bedarf korrekt nachgeladen; ein erfüllter Fehlerwert wird ohne automatischen Retry an parallele Aufrufer weitergegeben.

Sechs Unit-Tests schützen Pagination und Maximum, den separaten Relevanzvertrag beider Stores, vollständigen Cache und Invalidierung, parallele Count-/Listenabfragen, Zero-Count, erfüllten Fehlerwert sowie Queue und Ergebnisreihenfolge. Eine neue vollständig gemockte Browserreise lädt ein Ausgangsschlagwort, öffnet die Optimierung und prüft Count-Badge, vorgeschlagenes Schlagwort, Relevanz und den einzelnen Request in Desktop- und Mobile-WebKit.

Abnahme: Production-Build mit 967 transformierten Modulen, 29 Vitest-Dateien mit 103/103 Tests, ESLint mit 0 Fehlern und 233 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die neue Vorschlagsreise besteht responsiv mit 2/2 Prüfungen. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 5 registrierte Vuex-Module. Backend-Route, Controller, Datenbankabfrage, Autorisierung und Daten wurden nicht verändert. Die bekannten Sass-Deprecations und der Chunkgrößenhinweis bleiben unverändert.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die drei Dispatch-Aufrufe in zwei Komponenten wieder her und entfernt Pinia-Store, Unit-Tests und die isolierte Browserreise. Der zentrale Pinia-Store `keywords` bleibt bestehen; beim Rückbau schreibt das Vuex-Modul wie in Teilstufe 6.10 dokumentiert über dessen gerichtete Übergabe-Action.

## Teilstufe 6.12 – Materialien und Ressourcen

`materials` und `resources` bilden wegen ihrer Attach-/Detach-, Auto-Create-, Copy- und Replace-Abläufe einen atomaren Cacheverbund. Sie wurden deshalb gemeinsam migriert: Beide Pinia-Stores aktualisieren innerhalb ihrer Actions den jeweils anderen Store, ohne Watch oder doppelte Zuständigkeit. Die externen Material-Writer aus App-Bootstrap, `materialapp` und `search` schreiben im selben Slice direkt in Pinia. Alle Komponenten-Konsumenten verwenden die neuen Stores; beide Vuex-Module und ihre Root-Registrierungen sind entfernt. Insgesamt wurden 52 Vuex-Zugriffe in 21 Dateien einschließlich der internen Cross-Writer ersetzt.

Erhalten bleiben Detail-/Preview-Flags, Promise-Zusammenführung paralleler Detailabrufe, Queue-Reihenfolge, optionale Create-/Find-Parameter, Method-Spoofing beim Materialupdate, rohe Axios-Erfolgsantwort des Updates, konvertierte Fehler der übrigen Actions, Limitation-Payload, vollständige Attach-/Detach-Rückgabe, `success === true`-Semantik sowie alle bestehenden API-Pfade. Backend-Routen, `MaterialRequest`, `MaterialResourceRequest`, Policies, Passport-Guard, Events `MaterialWasCreated`, `MaterialWasChanged`, `ResourceWasAttached` und `ResourceWasDetached`, Lonely-Checks, Download-Job, Datenbank und Dateiablage wurden nicht verändert.

Vier lokale Defekte wurden im direkt betroffenen Cache- und Fehlerfluss behoben:

- `create` versuchte das Ergebnis per `commit('setMaterialDetailed')` zu speichern, obwohl `setMaterialDetailed` eine Action und keine Mutation war. Neu erstellte Materialien werden jetzt tatsächlich als vollständig geladen gecacht.
- Ein abgewiesenes Resource-Detail-Promise blieb bisher dauerhaft im Cache; jeder spätere Aufruf erhielt dieselbe Rejection. Der Eintrag wird bei Fehler entfernt und ein bewusster Retry ist wieder möglich, analog zum Material-Detailcache.
- `autoCreateMaterial` und `replaceResource` übergaben ganze Resource-/Materialobjekte an die jeweilige Cache-Invalidierung. Die Pinia-Actions verwenden die belegten Objekt-IDs und akzeptieren defensiv weiterhin eine bereits numerische ID.
- Der fachliche Fehler `success: false` beim Downloadlink wurde nach dem Throw erneut durch den HTTP-Fehlerkonverter geschickt. HTTP-Rejections werden nun vor Auswertung der Erfolgsantwort konvertiert; die konkrete Meldung `invalid download link` bleibt erhalten. Beim Materialupdate entfällt außerdem eine vom Rückgabepromise abgetrennte Catch-Kette, die eine zusätzliche unbeobachtete Rejection erzeugen konnte; der für die UI notwendige rohe Axios-Fehlervertrag bleibt unverändert.

13 Unit-Tests schützen Preview-/Detailcache, Coalescing und Retry, Create-Payload und Detailcache, Update-Rückgabe, Keywordbeziehungen, Attach/Detach samt beider Caches, Delete/Copy/Download, Resource-CRUD, Queue, Auto-Create, Find/Lonely und Replace-Invalidierung. Vier vorhandene Kernreisen prüfen Materialdetail, Resourcekarten samt Materialpagination, Material-Creator und Assign-App gegen den Production-Build in Desktop und Mobile.

Abnahme: Production-Build mit 967 transformierten Modulen, 31 Vitest-Dateien mit 116/116 Tests, ESLint mit 0 Fehlern und 209 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die vier Kernreisen bestehen responsiv mit 8/8 Prüfungen und unveränderten visuellen Referenzen. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 3 registrierte Vuex-Module.

Rückbau: Der gemeinsame Teilstufencommit muss als Einheit zurückgebaut werden. Er registriert beide Vuex-Module erneut, stellt die 52 Zugriffe einschließlich der Cross-Writer und der gerichteten Writer aus `search`, `materialapp` und App-Bootstrap wieder her und entfernt beide Pinia-Stores und ihre Tests. Die bereits migrierten fachfremden Pinia-Stores bleiben unabhängig.

## Teilstufe 6.13 – Materialseiten

`materialapp` war nach Teilstufe 6.12 ein isolierter Seitencache: Er lädt `/api/v1/materials?page=…`, speichert die vollständige Laravel-Paginator-Antwort pro Seitenschlüssel und übergibt deren Materialpreviews an den allein schreibenden Pinia-Store `materials`. `useMaterialPagesStore` erhält Request, rohe Fehlerweitergabe, Seitenschlüssel und Response-Body unverändert. Der einzige Komponenten-Konsument `MaterialList.vue` verwendet Pinia; Vuex-Modul und Root-Registrierung sind entfernt.

Zwei Unit-Tests schützen einmaligen Abruf, Cachetreffer, Requestparameter, Preview-Übergabe ohne fälschliches Detailflag sowie die semantisch wichtige Property-Prüfung: Auch ein explizit falsy gespeicherter Seitenwert gilt als vorhanden und löst keinen Request aus. `Object.hasOwn` ersetzt dabei nur den warnenden direkten Prototypzugriff.

Abnahme: Production-Build mit 967 transformierten Modulen, 32 Vitest-Dateien mit 118/118 Tests, ESLint mit 0 Fehlern und 207 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Die bestehende Resource-/Materiallisten-Reise prüft initiale Seite 6, Navigation auf Seite 7, sichtbare Inhalte, aktive Pagination und unveränderte Screenshots in Desktop- und Mobile-WebKit (2/2). Backend-Route, Authentifizierung, Query, Pagination und Daten wurden nicht verändert. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 2 registrierte Vuex-Module.

Rückbau: Der Teilstufencommit registriert das Vuex-Modul erneut, stellt den Dispatch in `MaterialList.vue` wieder her und entfernt Pinia-Store und Unit-Tests. Der bereits migrierte Materialstore bleibt bestehen; wie in Teilstufe 6.12 wird der gerichtete Preview-Writer beim Rückbau weiterhin direkt an Pinia übergeben.

## Teilstufe 6.14 – Materialsuche

`search` verwaltet den auf 20 Einträge begrenzten LRU-Promise-Cache der Materialsuche, die unveränderte POST-Payload an `/pool/search/get`, die Laravel-Paginator-Abbildung und die Übergabe gefundener Materialpreviews an den allein schreibenden Pinia-Store `materials`. Alle vier Konsumenten – Suchseite, Bibelleser, Bibelstellen-Popover und Materialauswahl – verwenden den neuen Pinia-Store; das Vuex-Modul und seine Root-Registrierung sind entfernt. Die historisch doppelt geschachtelte Queryform von `materialsWithParams`, Fallbacks für falsy `page`/`per_page`, Fehlermeldungswert und der derzeit nicht extern verwendete State `selectedSearchValues` bleiben erhalten.

Ein lokaler Cachedefekt wurde testgedeckt behoben: Eine fehlgeschlagene Suche blieb früher als abgewiesenes Promise dauerhaft im Cache. Jeder spätere identische Aufruf scheiterte ohne neuen Request. Der Pinia-Store entfernt ausschließlich den fehlgeschlagenen Cacheeintrag einschließlich LRU-Historie und ermöglicht damit einen gezielten Retry; erfolgreiche parallele Aufrufe werden weiterhin zusammengeführt und erfolgreiche Cachetreffer an das Ende der LRU-Reihenfolge verschoben.

Vier Unit-Tests schützen Requestpfad, Defaultpayload, Paginatorvertrag, Preview-Übergabe ohne Detailflag, Zusammenführung identischer Requests, 20er-LRU samt Promotion und Ersetzung, Retry nach Rejection, Titelqueryform und `selectedSearchValues`. Die Browserabnahme deckt die initiale Suchseite, asynchrone Suchvorschläge und Auswahl sowie die Bibelstellen- und Schlagwortoptimierung ab.

Abnahme: Production-Build mit 967 transformierten Modulen, 33 Vitest-Dateien mit 122/122 Tests, ESLint mit 0 Fehlern und 203 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Vier Suchreisen bestehen in Desktop- und Mobile-WebKit mit 8/8 Prüfungen; die vorhandene visuelle Startseitenreferenz bleibt unverändert. Backend-Route, Controller, Authentifizierung, Queryformat, Pagination und Daten wurden nicht verändert. Das Inventar meldet keine Vue-2-/Compat-Muster und noch exakt 1 registriertes Vuex-Modul.

Rückbau: Der Teilstufencommit registriert das frühere Vuex-Modul erneut, stellt die vier Dispatch-Aufrufe wieder her und entfernt Pinia-Store und Unit-Tests. Der bereits migrierte Materialstore bleibt bestehen; der gerichtete Preview-Writer wird beim Rückbau wie in Teilstufe 6.12 weiterhin direkt an Pinia übergeben.

## Teilstufe 6.15 – Allgemeine Optionen und Benutzereinstellungen

`general` war das letzte fachliche Vuex-Modul. `useGeneralStore` erhält den Options- und Benutzer-Promise-Cache, die Ableitungen für Uploadlimit, aktuellen Benutzer, Adminstatus und Systemnamen sowie Lesen, Überschreiben und Entfernen verschachtelter `frontend_user_settings`. Alle elf Aufrufe in Landingpage, Navbar-/Admin-Mixins, Uploader, Materialanlässen und Materialvorlagen verwenden Pinia. Das Vuex-Modul ist gelöscht; die noch installierte Vuex-Hülle registriert keine Module mehr.

Die API-Pfade, GET-/POST-Payloads, vollständige Settings-Übertragung, Cache-Aktualisierung des aktuellen Benutzers und die historischen erfüllten Fehlermeldungswerte bleiben unverändert. Backend-Routen, Passport-Authentifizierung, Controller, Validierung, Benutzerrechte und Daten wurden nicht verändert. Insbesondere ist die bestehende serverseitige Autorisierungssemantik von `UserSelfController` nicht Teil dieses Frontend-Ports.

Zwei gekoppelte Fehlerpfade wurden lokal stabilisiert: Nach fehlgeschlagenem Options- oder Benutzerabruf verblieb zuvor ein erfüllter Fehlerstring dauerhaft im Cache. Zusätzlich versuchte die jeweils nachgelagerte `.then`-Kette, diesen String als Options- beziehungsweise Benutzerobjekt einzutragen und konnte einen unbeobachteten `TypeError` erzeugen. Der Store liefert weiterhin denselben erfüllten Meldungswert an den Aufrufer, entfernt aber den betroffenen Cacheeintrag und aktualisiert Objektcaches nur mit valider Objektdatenform. Damit ist ein späterer Retry möglich. Weil Pinia State und Action nicht unter demselben Namen zulässt, heißt der nie extern gelesene interne State `generalOptions`; der verwendete Action-Vertrag `options()` bleibt erhalten.

Sieben Unit-Tests schützen parallele Options- und Benutzerabrufe, abgeleitete Werte, beide retryfähigen Fehlerpfade, Current-User-Synchronisierung, verschachteltes Lesen, vollständige Schreibpayloads, Hinzufügen und Entfernen von Materialvorlagen sowie den erfüllten Settings-Schreibfehler. Vier Browserreisen prüfen Navigation/Benutzeranzeige, Admin-/Anlasspfad, Uploadlimit und Uploadablauf sowie Laden und Speichern verschachtelter Materialvorlagen.

Abnahme: Production-Build mit 967 transformierten Modulen, 34 Vitest-Dateien mit 129/129 Tests, ESLint mit 0 Fehlern und 197 bekannten Warnungen, Frontend-Inventar und Diff-Check sind grün. Vier gekoppelte Reisen bestehen in Desktop- und Mobile-WebKit mit 8/8 Prüfungen und unveränderten visuellen Referenzen. Das Inventar meldet keine Vue-2-/Compat-Muster und 0 registrierte Vuex-Module; eine Volltextkontrolle findet keine `$store`-Aufrufe mehr.

Rückbau: Der Teilstufencommit registriert `general` erneut, stellt die elf Dispatch-Aufrufe in sechs Bereichen wieder her und entfernt Pinia-Store und Unit-Tests. Die in früheren Teilstufen migrierten Fachstores bleiben unabhängig.

## Noch zu migrieren

Nach Teilstufe 6.15 existiert kein Vuex-Konsument und kein registriertes Vuex-Modul mehr. Als eigene mechanische Teilstufe folgen Entfernung der leeren Root-Store-Hülle, `app.use(store)`, npm-Paket und Lockfile-Einträge sowie vollständige Gates.

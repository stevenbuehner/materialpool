# Verbindlicher Migrationsvertrag: Vue 2 auf Vue 3

## Status, Ziel und Geltungsbereich

Dieser Vertrag ist die verbindliche Arbeitsgrundlage für die Modernisierung des Materialpool-Frontends. Ziel ist dieselbe fachliche Funktionalität und dasselbe Erscheinungsbild auf Vue 3, integriert über den aktuellen Laravel-13-Vite-Standard und einen reproduzierbaren npm-Build. Die Migration ist eine technische Erneuerung, kein Redesign und keine Produktänderung.

**Vertragsrevision:** 10. September 2026. Die technische Grundlage wurde an diesem Datum gegen den Repository-Stand auf `master`, die offiziellen Vue-, Vue-Router-, Pinia-, Laravel-13-, npm-, BootstrapVue- und BootstrapVueNext-Dokumentationen geprüft. Vor jeder Stufe sind die betroffenen offiziellen Leitfäden erneut online zu prüfen; konkrete Zielversionen werden erst dann anhand der zu diesem Zeitpunkt stabilen Releases und ihrer Peer-Dependencies gelockt.

Die fünf Zielentscheidungen A sind freigegeben. Dieser Dokumentationsauftrag startet noch keine Implementierung. Sobald der Auftraggeber die Umsetzung einer Stufe oder der gesamten Migration beauftragt, sind dafür notwendige Paketwechsel, Tests und interne Anpassungen innerhalb dieses Vertrags eingeschlossen; bereits entschiedene Optionen werden nicht erneut abgefragt. Bei einem Gesamtauftrag geht es nach grünen Stufengates selbstständig weiter. Änderungen an Backendverträgen, Daten, Datenbank, API, Authentifizierung, Berechtigungen, Storage, Queues oder Produktgestaltung bleiben außerhalb des Auftrags.

**Pragmatische Revision:** Die Ausführungsregeln wurden am 10. September 2026 präzisiert: kleine Arbeitspakete, risikogerechte Prüfungen, frühzeitiger Kompatibilitätsnachweis und ein eigener [Arbeitsleitfaden für GPT-5.6 Sol](vue-3-sol-workflow.md). Die fünf freigegebenen Zielentscheidungen bleiben erhalten.

## Verbindliches Endbild

| Bereich | Endzustand | Vertragsgrenze |
| --- | --- | --- |
| Framework | Stabiles Vue 3 ohne `@vue/compat` und ohne Vue-2-Laufzeit | Keine verbleibenden Compat-Warnungen oder Vue-2-Abhängigkeiten |
| Einstieg | `createApp`, app-lokale Plugins/Komponenten/Directives | Kein globaler, zwischen Tests geteilter Vue-Konstruktorzustand |
| Routing | Vue Router 4, History-Basis `/vue` | Pfade, Namen, Aliase, Parameter, Query-Semantik, Scroll- und Redirectverhalten bleiben gleich |
| State | Pinia, modulweise aus Vuex überführt | State, Getter-Ergebnisse, Actions, Nebenwirkungen, Requestreihenfolge und Fehlerzustände bleiben gleich |
| Build | Vite mit `laravel-vite-plugin`, `@vitejs/plugin-vue` und Blade-`@vite` | `npm run dev` bietet HMR; `npm run build` erzeugt versionierte Produktionsassets |
| UI | Vue-3-fähige, gewartete Komponenten hinter anwendungseigenen Adaptern | Kein Redesign; Bootstrap-4-Bestand wird visuell und interaktiv reproduziert |
| Code | ES Modules, Vue-SFCs; Composition API bevorzugt für neue komplexe Logik und Composables | Getestete Options-API-Komponenten dürfen auch im Endstand bleiben; kein TypeScript- oder Stil-Komplettumbau |
| Tests | Vitest, Vue Test Utils 2 und browserbasierte E2E-/Visual-Regressionstests | Tests prüfen öffentliche Schnittstellen und Nutzerverhalten, nicht interne Implementierung |
| Installation | festgelegte Node-/npm-Laufzeit, committed `package-lock.json`, CI mit `npm ci` | Keine beweglichen Git-Abhängigkeiten, keine ungeprüften `latest`- oder Beta-Ziele im Endstand |

## Ausgangsbasis

Die Bestandsaufnahme am Vertragsdatum ergibt:

- Vue `2.7.16`, Vue Router `3.6.5`, Vuex `3.6.2`, Bootstrap `4.6.2`, BootstrapVue `2.23.1` und Webpack `4.47.0`;
- 100 Vue-SFCs und 63 JavaScript-Dateien unter `resources/js`;
- 16 registrierte Vuex-Module in `resources/js/apps/main/store/index.js`; Hilfsdateien im Modulordner sind keine zusätzlichen Stores;
- 38 Dateien mit direkter BootstrapVue-Nutzung;
- direkte Vue-nahe Integrationen für Übersetzung, Datepicker, asynchrone Computeds, Flash-Meldungen, Shortcuts, Select, Star-Rating, Upload und Dialoge;
- ein globaler Vue-Eventbus, globale Plugins/Directives, Options-API-Mixins und Vue-2-Filter;
- zwei Stellen mit `v-html` sowie dynamische Template-Kompilierung in `compiledMarkdown.vue`; `marked`, DOMPurify und die vorhandene Sanitizing-Konfiguration sind daher besonders zu prüfen;
- eine Laravel-zu-SPA-Übergabe über `window.Laravel` und `window.materialpool` sowie CSRF-Header in der Axios-Instanz;
- Produktionsassets `/js/main_build.js` und `/css/main.css`, im Entwicklungsbetrieb derzeit ein Webpack-Dev-Server auf Port 8080.

Vor Stufe 1 wird diese Inventur maschinell neu erzeugt und als Stufenbericht gespeichert. Abweichungen sind zu erklären; die hier genannten Zahlen sind eine Ausgangsmessung, keine dauerhaft festgeschriebene Dateianzahl.

Manifest-Constraints sind keine exakten installierten Versionen. Jede ausführbare Referenz nennt Git-SHA, Lockfile-Hash, Node/npm/PHP-Version, Testdatenrevision und Browserversion. Die Reparaturcommits `646e325`, `ce2081e` und `a7e31c9` sind historische Ausgangspunkte, kein Beleg für vollständige UI-Parität. Eine funktionierende Login-Maske allein nimmt die geschützte SPA nicht ab. Alte Buildfehler und bereits bestehende UI-Defekte sind gesondert zu erfassen; sie dürfen nicht als gewünschtes Zielverhalten eingefroren werden.

## Unveränderliche Produkt-, UI- und Integrationsverträge

### Funktionalität und Datenfluss

- Alle heute erreichbaren SPA-Routen unter `/vue`, ihre Namen, Aliase, Parameter, Query-Parameter, Deep Links, Browser-Zurück/Vorwärts-Verhalten und Reload-Verhalten bleiben erhalten.
- Alle API-v1-/API-v2-Aufrufe behalten URL, HTTP-Methode, Header, Request-Payload, Statuscodebehandlung und erwartete Response-Struktur. Ein API-Umbau ist kein Frontend-Migrationsschritt.
- CSRF-, Session-, Passport- und Autorisierungsverhalten bleiben unverändert. UI-Ausblendung ersetzt keine serverseitige Policy.
- Vuex-/später Pinia-Aktionen müssen dieselben Requests, Queue-Reihenfolgen, Cache- und Reload-Effekte auslösen. Optimistische Updates, Lade-, Leer-, Erfolgs- und Fehlerzustände bleiben beobachtbar gleich.
- Material-, Resource-, Keyword-, Bibleverse-, Bundle-, Upload-, Preview-, Download-, Zuweisungs- und Shutdown-Abläufe behalten ihre bestehende Semantik.
- Die Laravel-Übergaben `window.Laravel` und `window.materialpool` dürfen intern gekapselt, aber erst nach einem bewiesenen kompatiblen Ersatz entfernt werden.
- Generierte Übersetzungsschlüssel, Datums-/Dateigrößenformatierung und sichtbare Texte bleiben gleich. Neue sichtbare Texte müssen den bestehenden Übersetzungsweg verwenden.

### Identisches Erscheinungsbild

„Identisch“ bedeutet für die Abnahme nicht nur ähnliche Farben, sondern denselben wahrnehmbaren UI-Vertrag:

- dieselben Fonts, Farben, Kontraste, Abstände, Größen, Breakpoints, Rahmen, Radien, Icons, Tabellen-, Karten-, Formular-, Modal-, Dropdown-, Tooltip-, Popover-, Spinner- und Navigationsdarstellungen;
- dieselben Zustände für Hover, Focus, Active, Disabled, Invalid, Saving, Loading, Empty und Error;
- dieselbe Reihenfolge, Beschriftung, Ausrichtung und responsive Anordnung der Bedienelemente;
- dieselben Übergänge, Overlays, Scrollpositionen und Fokuswechsel, soweit nicht ein dokumentierter Browserfehler verhindert wird;
- keine Bootstrap-5-bedingte Umbenennung oder Defaultänderung darf ungeprüft sichtbar werden.

Visuelle Referenzen werden vor der ersten UI-Änderung auf dem grünen Vue-2-Stand aufgenommen. Verbindliche Viewports sind mindestens ein repräsentativer Desktop- und ein repräsentativer Mobile-Viewport; weitere Breakpoints werden aus tatsächlich umspringenden Layouts abgeleitet. Dynamische Inhalte, Zeiten, IDs und Ladeanimationen werden deterministisch stabilisiert. Eine Pixel-Differenz ist nur für Renderingrauschen mit dokumentierter Toleranz zulässig. Jede fachlich wahrnehmbare Differenz stoppt die Stufe.

Stufe 0 legt konkrete Viewports fest, als Startwerte 1440×900 und 390×844 sowie jeweils knapp unter/über tatsächlich betroffenen Breakpoints. Vorher/Nachher verwenden dasselbe Betriebssystem, dieselbe Browserversion, DPR, Schriftdateien, Locale und Zeitzone. Referenzen zweimal unverändert aufnehmen und erst dann eine enge, begründete Rauschtoleranz definieren. Keine pauschale Prozenttoleranz darf einen verschobenen Button verstecken. Masken sind nur für belegte dynamische Inhalte zulässig. Baselines werden niemals bloß aktualisiert, um einen fehlerhaften Test grün zu machen. DOM-Snapshots allein beweisen weder Layout- noch Verhaltensparität.

### Zugänglichkeit und Sicherheit

- Die Migration darf Tastaturbedienung, Fokusreihenfolge, sichtbaren Fokus, Labels, semantische Elemente, Überschriften, Landmarks, Alternativtexte oder ARIA-Zustände nicht verschlechtern.
- Nach Routenwechseln wird das bestehende Fokusverhalten charakterisiert. Eine Verbesserung wie Skip-Link oder Fokus auf den Seitenanfang ist sinnvoll, aber wegen ihres beobachtbaren UX-Effekts separat freizugeben.
- Nicht vertrauenswürdige Inhalte werden niemals als Vue-Template kompiliert. Interpolation bleibt Standard; `v-html` bleibt auf die bestehenden, ausdrücklich sanitisierten Anwendungsfälle begrenzt.
- DOMPurify-Konfiguration, Markdown-Renderer, Link-/URL-Behandlung und Backendvalidierung werden durch adversariale Charakterisierungstests geschützt. Paketwechsel dürfen Sanitizing nicht schwächen.
- Keine Secrets, Tokens, realen Nutzerdaten oder lokalen Datenbanken werden in Fixtures, Screenshots, Traces oder Commits aufgenommen.

**Konkreter Bestandshinweis:** `components/markdown/compiledMarkdown.vue` erzeugt aus `compiledText` eine Komponente mit `template`. `bibleverseRenderer.js` liefert darin Bibelstellen-Komponententags; `sanitizeSetup.js` erlaubt unter anderem `:text` und `:load-contents`. DOMPurify allein beweist nicht die Sicherheit anschließender Vue-Template-Auswertung. Stufe 0 charakterisiert Markdown, Literale wie `{{ ... }}`, erlaubte Tags und interaktive Bibelstellen; sie bestätigt hier ausdrücklich noch keine Schwachstelle oder sichere Gesamtkette. In `my-text-block.vue` und `customDialog.vue` ist Sanitizing ebenfalls am jeweiligen Datenursprung nachzuweisen, nicht zu unterstellen.

Vor Entfernung des Runtime-Compilers diese Integration gezielt portieren: bevorzugt kontrollierte Markdown-Tokens/AST in feste Vue-Komponenten und Textknoten rendern, ohne Benutzerdaten als Vue-Ausdruck zu kompilieren. Eine pauschale Umstellung auf `v-html` wäre unzureichend, da dadurch keine Vue-Popovers instanziiert werden. Bestehende Texte und Bibelstelleninteraktionen charakterisieren und erhalten. Erfordert der sichere Ersatz veränderte erlaubte Inhalte oder andere beobachtbare Semantik, vor dieser Änderung eine konkrete Entscheidung vorlegen. Ein bekannt unsicherer Pfad darf nicht als Paritätsanforderung konserviert werden.

## Verbindliche Entwicklungsregeln

- Vue Style Guide Priority A ist verbindlich. Priority B wird für neuen oder vollständig berührten Code angewendet, soweit sie nicht dem bestehenden Projektvertrag widerspricht. Abweichungen werden lokal begründet. Der Style Guide ist eine Referenz, kein Auftrag für kosmetische Massenänderungen.
- Props werden explizit definiert; Listen besitzen stabile Keys; direktes Mutieren von Props ist verboten; Komponenten-Kommunikation erfolgt über Props/Events oder den zuständigen Store.
- Neue Komponenten erklären ihre öffentlichen Props, Emits und Slots. Bei Migration bestehender Komponenten werden diese Verträge zunächst charakterisiert und dann explizit gemacht.
- Options API bleibt auch nach der Migration zulässig: Vue erklärt sie ausdrücklich nicht für veraltet. Composition API wird bei nachvollziehbarem Nutzen verwendet, insbesondere für wiederverwendbare zustandsbehaftete Logik. Eine Datei wird nicht allein aus Stilgründen umgeschrieben. [Vue Composition API FAQ](https://vuejs.org/guide/extras/composition-api-faq)
- Gemeinsame fachliche Logik wandert aus Mixins schrittweise in getestete Funktionen oder Composables. Der Umbau erfolgt aufrufstellenweise mit unverändertem Ergebnis; keine gleichzeitige fachliche Bereinigung.
- Globale Filter werden vor dem Vue-3-Cutover durch explizite Funktionen/Computed Values ersetzt. Der Eventbus wird durch explizite Owner-Kommunikation, Store-Actions oder eine kleine typisierte Ereignisschnittstelle ersetzt.
- Lokale Adapter kapseln migrationskritische Verträge wie Select, Modal, Datum, Upload und Notifications. Einfache, kompatible Buttons oder Container brauchen keinen zusätzlichen Wrapper allein aufgrund mehrfacher Verwendung. Jeder Adapter braucht einen konkreten Paritätszweck; keine zweite allgemeine UI-Bibliothek entwickeln.
- Neu eingeführte Dependencies benötigen einen konkreten Nutzen, stabile Releases, kompatible Peer-Dependencies, akzeptable Lizenz, gepflegtes Repository, Browserkompatibilität und Bundle-Auswirkungsprüfung.
- Automatische Codemods und KI-generierte Änderungen sind Hilfsmittel, keine Nachweise. Jede Änderung wird diffbasiert geprüft, gebaut und durch passende Tests sowie Browserabnahme validiert.

## Drittkomponentenvertrag

Vor Austausch oder Fork wird für jedes direkte Paket eine Akte im Stufenbericht geführt: aktuelle Verwendung, öffentliche Schnittstelle, sichtbare Zustände, stabile Vue-3-Version oder Kandidat, letzte stabile Veröffentlichung, Wartungsstatus, offene migrationsrelevante Fehler, Peer-Dependencies, Lizenz, Bundlegröße, Barrierefreiheit, Sicherheitsbefunde, Testfälle und Rückbau.

| Paket/Bereich | Heutige Nutzung | Zielrichtung; keine ungeprüfte Versionszusage |
| --- | --- | --- |
| `bootstrap-vue` / Bootstrap 4 | 38 Dateien; zentrale UI-Basis | Bevorzugt BootstrapVueNext/Bootstrap 5 hinter lokalen Adaptern; jede Komponente und Utility-Klasse einzeln gegen die Referenz prüfen |
| `vue-router` | SPA-Routing | Vue Router 4; Routenvertrag und Guards charakterisieren |
| `vuex` | 16 registrierte Module | Zunächst Vuex 4 als Vue-3-Brücke, danach modulweise Pinia |
| `@eli5/vue-lang-js` | globale Übersetzung | Adapter um bestehende Schlüssel; stabilen Vue-3-Ersatz prüfen, bevorzugt Vue I18n nur bei bewiesener Ausgabeparität |
| `vue-select` | sechs Dateien | stabile Vue-3-Option gegen Slots, Suche, Auswahlwerte, Drag-/Tag-Verhalten prüfen; keine Beta im Endstand |
| `vue-star-rating` | sechs Dateien | Vue-3-Version oder kleine lokale Rating-Komponente anhand Keyboard-, Hover- und Wertvertrag prüfen |
| `@hokify/vuejs-datepicker` | eine Datei | Vue-3-fähigen Datepicker gegen Format, Locale, Min/Max, Tastatur und Popover-Geometrie prüfen |
| `vue-transmit` | Upload-Komponente | bevorzugt anwendungseigener Upload-Adapter auf Browser-/Axios-Basis; Chunking, Progress, Retry, CSRF und Fehlertexte charakterisieren |
| `vue-flash-message` | globaler Flash-Service | lokale, app-weite Notification-Schnittstelle; Timing, Reihenfolge, Styling und ARIA-Live-Verhalten erhalten |
| `vue-shortkey` | globales Shortcut-Plugin | kleine lokale Directive/Composable bevorzugen; Scope, Modifikatortasten und Eingabefeld-Ausnahmen testen |
| `vue-async-computed` | globales Plugin | explizite async Actions/Composables mit Loading/Error/Abbruch; keine Promises als normale Computed Values behandeln |
| `vuejs-dialog` / eigene Dialoge | Paket derzeit ohne gefundenen Import; eigene Dialogkomponenten vorhanden | Laufzeitnutzung und transitive Nutzung beweisen; ungenutztes Paket erst dann entfernen, sonst lokale Dialogschnittstelle |
| `epic-spinners` | eine Datei | stabile Vue-3-Version oder kleine lokale Spinner-Komponente mit identischer Geometrie/Animation |
| `video.js` / Theme | eine Datei | separates Major-Upgrade; Wiedergabe, Controls, Poster, Streaming, Cleanup und mobile Bedienung prüfen |
| `marked` / `dompurify` / `striptags` | Markdown und HTML | Sicherheitskritische Kette separat aktualisieren; Ausgabe- und XSS-Verträge vor jedem Major einfrieren |
| SVG-/Icon-Pakete | direkte SVG-Imports und Loader | Vite-kompatible Importstrategie; Pfad, ViewBox, Größe, Farbe und zugänglicher Name bleiben gleich |
| `moment` / `dayjs` | Lokalisierung und Datumsformatierung | keine pauschale Bereinigung; Aufrufstellen und exakte Ausgabe inventarisieren, danach Duplikation separat entscheiden |

Die Tabelle ist ein Risikoverzeichnis, keine vollständige Paketliste und keine pauschale Freigabe für Eigenimplementierungen. Die Entscheidung A „gepflegter Ersatz zuerst“ gilt auch bei Flash, Shortcuts, Async-Computed und Upload. Stufe 0 erfasst außerdem alle übrigen direkten Pakete und deren transitive Risiken, insbesondere Axios, `p-queue`, Lodash, Polyfills, Babel, Popper/Tether und Loader. Private Deep-Imports wie `lodash/_baseClone`, `p-queue/dist` oder Paket-`src`-Imports erfordern einen expliziten Ersatznachweis; funktionierende öffentliche Exports bevorzugen.

Vue-unabhängige Paket-Upgrades erfolgen als eigene kleine Arbeitspakete vor ihrem jeweiligen Verbraucher-Cutover. Bei Axios sind Fehlerobjekte, Header, Multipart und Abbruch, bei Queue-Paketen Parallelität und Reihenfolge, bei Übersetzungen Laravel-Platzhalter/Pluralisierung und bei Datumspaketen Zeitzone und leere Werte zu prüfen. ESM-/CommonJS- und Peer-Kompatibilität müssen zur aktuellen Zwischenstufe passen. „Aktuell“ bedeutet neueste stabile kompatible Version innerhalb des freigegebenen Ziel-Majors am Stufenstart; diese Auswahl während der Stufe einfrieren, außer bei einem relevanten Fehler oder Sicherheitsbefund.

### Regel für fehlende oder ungeeignete Ersatzpakete

Quellcode darf nur dann aus einem npm-Paket in das Repository übernommen und auf Vue 3 portiert werden, wenn alle folgenden Bedingungen erfüllt sind:

1. Ein stabiler, gepflegter Ersatz mit passender Funktion, Zugänglichkeit und visueller Anpassbarkeit wurde nachvollziehbar nicht gefunden oder scheitert an einem dokumentierten Vertragstest.
2. Die Lizenz erlaubt Kopie, Änderung und Weiterverteilung. Lizenztext, Copyright-Hinweise, genaue Ausgangsversion, Paket-Tarball-Integrität und Quell-URL werden unter einem klaren `vendor`- oder `legacy-ports`-Pfad erhalten.
3. Die benötigte Komponente wird vollständig einschließlich ihrer internen Helfer, Styles und notwendigen Assets übernommen. Nicht das gesamte npm-Paket kopieren; benötigte Funktionalität aber auch nicht verkürzt nachbauen. Ablage unter `resources/js/legacy-ports/<name>/`, nicht im Composer-Verzeichnis `vendor/` und nicht als dauerhafte Änderung unter `node_modules`.
4. Vor der Portierung existieren Blackbox-Tests und visuelle Referenzen für Props, Events, Slots, DOM-/CSS-Vertrag, Tastatur, Fokus und Fehlerzustände.
5. Die portierte Komponente verwendet öffentliche Vue-3-APIs, besitzt eigene Tests und ist nicht als unveränderter Fremdcode getarnt.
6. Sicherheits- und Wartungsverantwortung, Upstream-Abgleich und späterer Ersatz werden dokumentiert.

Ein Fork einer großen Komponentenbibliothek ist nicht pauschal freigegeben. Er verlangt eine eigene Entscheidung mit Umfang und Wartungskosten.

## Stufenplan und Gates

Jede Stufe endet in einem eigenen Commit oder einer kleinen, zusammenhängenden Commitfolge. Der letzte grüne Commit ist der Rücksprungpunkt. Lockfile- und Quellcodeänderungen werden gemeinsam geprüft, aber Buildartefakte werden nur gemäß dem festgelegten Deploymentvertrag versioniert.

### Stufe 0 – Ausführbare Vue-2-Referenz

Ziel: Das heutige Verhalten messbar machen, bevor Abhängigkeiten ausgetauscht werden.

- Node-/npm-Zielmatrix anhand der Engine-Anforderungen der später gewählten stabilen Toolchain festlegen und versionieren.
- Legacy-Referenz und endgültige, unterstützte Node-LTS-Laufzeit getrennt dokumentieren; keine EOL-Node-Version als Endziel. Die gemeinsamen Engine-/Peer-Anforderungen aller Toolchain-Pakete entscheiden, nicht eine pauschale Mindestversion aus einem Tutorial.
- vollständige Route-, Komponenten-, Store-, Plugin-, Directive-, Mixin-, Filter-, Eventbus-, Slot-, `v-model`-/`.sync`-, Transition-, `v-html`- und Drittanbieterinventur erzeugen;
- kritische Nutzerreisen und Testdaten definieren, insbesondere Suche, Material/Resource, Keywords, Bibelstellen, Upload, Vorschau, Zuweisung, Bundles und Berechtigungsfehler;
- Vitest-kompatible reine JS-Tests nur dort vorziehen, wo sie den bestehenden Build nicht verändern; browserbasierte E2E- und visuelle Referenztests gegen den Vue-2-Stand einführen;
- Bundlegröße, Buildzeit, Browserkonsole und `npm audit --json` als Baseline speichern, ohne Secrets oder reale Daten.

Vor größerer Portierungsarbeit einen begrenzten technischen Versuch in einer isolierten Arbeitskopie durchführen: tatsächliche stabile Versionen von Vue/Compat/Compiler, Router, Vuex, Vue Loader und BootstrapVueNext gemeinsam auflösbar? Eine repräsentative Formular-/Modal-/Select-Kombination auf dem geplanten Webpack-Zwischenstand kompilieren und bedienen. Compat und Compiler müssen zum Vue-Release passen. Das ist ein Machbarkeitsnachweis, kein neuer Produktions-Buildweg. Verlangt das Zielpaket Tooling, das dieser Reihenfolge widerspricht, zunächst genau diesen Konflikt lösen oder mit konkreten Befunden melden; keine wochenlange Portierung vor diesem Nachweis.

Browser-E2E verwenden einen Runner für Referenz und Ziel; Standardvorschlag ist Playwright für E2E und Screenshots, alternativ ein bereits etablierter Runner. Vue-2-Komponententests nur für konkrete Risikobausteine mit passender Test-Utils-Version einrichten; keine kurzlebige zweite Vollsuite bauen. Ab dem Vue-3-Wechsel Vue Test Utils 2 einsetzen, nicht erst nach Entfernung von Compat. ESLint früh für neue/geänderte Dateien aktivieren; vorhandene Altbefunde einmalig dokumentieren.

**Ausgangsgate:** bestehender Production-Build und Backend-Suite grün.  
**Abnahme:** alle vereinbarten Referenzreisen reproduzierbar; keine unklassifizierten Konsolenfehler; Screenshots und Zustandsmatrizen vollständig.  
**Rückbau:** nur Test-/Dokumentationsänderungen entfernen.

### Stufe 1 – Vue-2-Code vorbereiten und Abhängigkeiten kapseln

Ziel: Vue-3-Bruchstellen beseitigen, während die Vue-2-Referenz noch direkt vergleichbar ist.

- `vue-template-compiler`, Vue und Vue Loader weiterhin exakt kompatibel halten;
- globale Plugins/BootstrapVue-Registrierungen in einen kontrollierten Bootstrap-Pfad bündeln;
- Filter in normale Funktionen, Eventbus in explizite Schnittstelle, alte Slot-Syntax in `v-slot`, `.sync` in klaren Prop-/Eventvertrag und problematische Deep-Selector in die vom aktuellen Compiler unterstützte Form überführen;
- Drittanbieterzugriffe über lokale Adapter kapseln; ungenutzte Abhängigkeiten nur nach statischem und Laufzeitnachweis entfernen;
- Sass-Deprecations an eigenem Code verhaltensneutral abbauen; Bootstrap-4-Upstreamwarnungen werden nicht durch ungeprüfte Vendor-Patches verändert.

**Abnahme:** Vue-2-Build, Komponenten-/E2E-/Visual-Gates grün; keine sichtbare Änderung.  
**Rückbau:** adapter- oder komponentenweise möglich.

### Stufe 2 – Vue 3 Migration Build auf bestehendem Buildsystem

Ziel: Frameworksemantik ändern, ohne gleichzeitig Laravel-Assetintegration und UI-Basis auszutauschen.

- auf Vue 3 mit `@vue/compat` gemäß offiziellem Migration-Build-Leitfaden wechseln;
- Webpack nur so weit aktualisieren, wie es die stabile Vue-3-/Compat-Toolchain zwingend verlangt;
- Anwendung mit `createApp` booten; Plugins, globale Components und Directives app-lokal registrieren;
- Vuex auf Vuex 4 und Vue Router auf Vue Router 4 überführen; Routing und Storesemantik separat abnehmen;
- eigene Komponenten schrittweise auf Compat `MODE: 3` setzen und alle Warnungen aus Anwendungscode beheben;
- Vue-3-Brüche vollständig bearbeiten: Global API, `v-model`, `key`, `v-if`/`v-for`, `v-bind`-Reihenfolge, `.native`, Emits, Slots/Attrs/Listeners, Directives, Lifecycle, Transitionklassen, Array-Watches und entfernte Events/Filter.

BootstrapVue erfordert laut [offizieller Anleitung](https://bootstrap-vue.org/vue3/) global `MODE: 2` sowohl im Compiler als auch zur Laufzeit. Eigene Komponenten dürfen schrittweise `MODE: 3` verwenden; Adapter an Vue-2-Paketen können gezielte, dokumentierte Ausnahmen benötigen. Compat-Warnungen werden mit Component-Trace erfasst; jede Ausnahme erhält Owner, Grund und späteste Auflösungsstufe 3. Nicht jede Vue-2-Bibliothek funktioniert unter Compat: inkompatible Plugins müssen vor oder zusammen mit ihrem ersten Vue-3-Verbraucher ersetzt werden.

**Abnahme:** unabhängige eigene Komponenten in `MODE: 3`, Anwendung unter Vue-3-Laufzeit grün; verbleibende Compatstellen einschließlich notwendiger Adapter sind vollständig inventarisiert und Stufe 3 zugeordnet. Keine zusätzliche unbekannte Warnung.  
**Rückbau:** Lockfile und Framework-/Bootstrap-Commit auf Stufe 1 zurücksetzen; keine Datenmigration nötig.

### Stufe 3 – UI-Komponenten auf Vue 3 portieren

Ziel: BootstrapVue und alle übrigen Vue-2-only-Komponenten entfernen, ohne das Erscheinungsbild zu ändern.

- Komponentenfamilien einzeln migrieren: primitive Controls, Formulare, Navigation, Feedback/Spinner, Modal/Popover/Tooltip, Listen/Karten, komplexe Selektoren, Upload/Media;
- für BootstrapVueNext/Bootstrap 5 eine Materialpool-Kompatibilitätsschicht für Tokens, Utilities, Props, Events und Slots anlegen;
- Bootstrap-5-Änderungen wie `left/right` zu `start/end`, Popper-Verhalten, Form-/Modal-/Navbar-Markup und Defaultvariablen nicht mechanisch übernehmen, sondern per Referenztest angleichen;
- je Familie Visual-, Keyboard-, Fokus-, Responsive- und Fehlerzustände abnehmen;
- nur bei bestandenem Drittkomponentenvertrag einen kleinen benötigten Paketbestand lokal portieren.

Bootstrap 4 und 5 dürfen nicht ungekapselt gleichzeitig in dasselbe Dokument geladen werden: ihre globalen Klassen und Resets würden auch noch nicht migrierte Seiten verändern. Neue Familien werden zunächst in einem getrennten Testdokument mit Bootstrap 5 und Materialpool-Sass geprüft; die Altanwendung bleibt bis zum zusammenhängenden CSS-Wechsel unter Bootstrap 4. Der gemeinsame Wechsel von globalem Stylesheet und vorbereiteten Familien ist ein eigener Integrationscheckpoint mit vollständiger Sichtprüfung. Damit sind kleine Portierungs-Commits möglich, aber nicht jeder Zwischencommit muss produktiv aktivierbar sein. Keine zweite App mit eigener Authentifizierung oder paralleler Datenhaltung aufbauen.

Modals/Popovers/Tooltips zusätzlich bei Teleport zum `body`, verschachtelten Overlays, Scroll-Lock, Z-Index, Escape, Outside-Click und Fokusrückgabe prüfen. Ein nur unter dem App-Root gescoptes Stylesheet erreicht solche Overlays nicht automatisch. Verwendete Props, Emits, Slots und Instanzmethoden anhand der konkreten Zielversion prüfen, nicht allein anhand ähnlicher Komponentennamen.

**Abnahme:** kein Import von BootstrapVue oder einer anderen Vue-2-only-Dependency; alle visuellen Referenzen innerhalb der festgelegten Toleranz; keine Funktionsabweichung.  
**Rückbau:** pro Komponentenfamilie über Adapter möglich; keine Big-Bang-Entfernung vor vollständiger Abnahme.

### Stufe 4 – Compat entfernen, reines Vue 3

Ziel: Den tatsächlichen Vue-3-Endzustand erreichen.

- `@vue/compat`, Compat-Aliase und alle Compat-Konfigurationen entfernen;
- Vue Test Utils 2 für Komponenten verwenden; keine Vue-Test-Utils-v1-Reste;
- Runtime-only-Build verwenden, sofern die Inventur keine bewusst im Browser kompilierten Templates nachweist;
- Console-Warnings als Fehler im Test-/CI-Lauf behandeln;
- Vue-2-Pakete und ihre ausschließlich transitiven Altloader entfernen.

**Abnahme:** `npm ls` zeigt keine Vue-2-Laufzeit; keine Compat-Warnungen; vollständige Funktional-, Visual-, Security- und Accessibility-Gates grün.  
**Rückbau:** gesamter Stufencommit auf den grünen Compat-Stand zurück.

### Stufe 5 – Laravel-Vite-Integration

Ziel: Webpack und manuelle Asset-URLs durch den aktuellen Laravel-13-Standard ersetzen.

- stabile, zueinander kompatible Versionen von Vite, `laravel-vite-plugin` und `@vitejs/plugin-vue` einführen;
- SPA-JavaScript als Entry konfigurieren und Sass/CSS aus dem JavaScript-Entry importieren;
- Blade auf `@vite` umstellen; HMR, Produktionsmanifest, CSP-/Nonce-/SRI-Anforderungen und Deploymentpfade gegen den Produktionsvertrag prüfen;
- Webpack-Konfiguration, Loader, Babel-Polyfill und OpenSSL-Workaround erst entfernen, wenn alle Imports und Browserziele über Vite nachgewiesen sind;
- `npm run dev`, `npm run build`, Übersetzungsgenerierung und `php artisan dev` so definieren, dass kein zweiter konkurrierender Dev-Server entsteht;
- dynamische Imports, SVGs, Fonts, Videos, Source Maps und öffentliche URLs einzeln verifizieren.

Beide Blade-Einstiege (`resources/views/vuerouter/index.blade.php` und `resources/views/layouts/app.blade.php`) testen: auch Login/Passwortseiten brauchen ihre Styles, dürfen aber keine SPA auf einem fehlenden Mountpunkt starten. Übersetzungsdaten müssen vor Bundleerzeugung verfügbar sein; schlägt `lang:js` fehl, muss der Build fehlschlagen (kein Weiterlaufen über `;`). Die JavaScript-Imports aus `vendor/stevenbuehner/bible-verse-bundle` müssen auch im frischen Release nach Composer-Installation auflösbar sein. `VITE_*`-Werte sind öffentliches Bundlematerial und enthalten keine Secrets.

Produktion ohne laufenden Dev-Server und ohne veraltete Hot-Datei testen. Alle vom HTML/Manifest referenzierten CSS-/JS-Chunks müssen HTTP 200 mit passendem Inhaltstyp liefern. Asset-Hashes dürfen sich ändern; Medien-/Download-URLs und Deep Links bleiben kompatibel. Source Maps und bisher eingecheckte `public/css`-/`public/js`-Artefakte mit `ops/production` abgleichen und erst nach funktionierender Releaseerzeugung umstellen.

Der offizielle Vue-2-Vite-Pluginweg ist am Vertragsdatum nicht mehr aktiv gepflegt. Vite wird deshalb verbindlich erst auf dem reinen Vue-3-Stand eingeführt; eine temporäre Vue-2-Vite-Stufe ist nicht Teil dieses Vertrags.

**Abnahme:** frisches `npm ci`, Dev-HMR, Production-Build, Laravel-Assetauflösung und Deployment-Preflight grün; keine Webpack-/Mix-Laufzeitreste.  
**Rückbau:** Blade-/Manifest-/Buildcommit gemeinsam auf Stufe 4 zurücksetzen.

### Stufe 6 – Vuex 4 modulweise auf Pinia

Ziel: den von Vue empfohlenen State-Management-Endstand erreichen, getrennt vom Framework-Cutover.

- jedes Vuex-Modul wird ein Pinia-Store mit stabiler fachlicher Zuständigkeit;
- zuerst reine/read-mostly Module, danach relationale und nebenwirkungsreiche Module;
- während der Übergangszeit dürfen Vuex und Pinia gemäß offizieller Pinia-Migrationsanleitung parallel existieren;
- Getter-, Action-, Lade-, Fehler-, Cache- und Requestreihenfolgeverträge werden pro Store getestet;
- kein Store greift bei Modulinitialisierung auf eine noch nicht installierte Pinia-Instanz zu; Router-Guard-Nutzung erfolgt innerhalb des aktiven App-Kontexts;
- Vuex wird erst nach Migration aller Konsumenten entfernt.

Während des Parallelbetriebs gibt es pro fachlichem Datensatz genau einen schreibenden Store. Keine gegenseitigen Watches zur Synchronisierung zweier Kopien. Konsumenten dürfen vorübergehend über eine gerichtete Fassade zugreifen. Bei Pinia kein reaktivitätsbrechendes Destructuring von State/Gettern; bei Bedarf `storeToRefs` verwenden. Rückgabewerte und Promise-/Fehlerverhalten der Actions sowie Änderungen gemeinsam referenzierter Objekte charakterisieren.

**Abnahme:** keine Vuex-Imports oder Vuex-Dependencies; alle Store- und Nutzerreisen grün.  
**Rückbau:** storeweise über die Adaptergrenze, solange Vuex noch vorhanden ist; finale Entfernung als separater Commit.

### Stufe 7 – Konsolidierung und Releasefreigabe

- tote Adapter und Übergangscode nur mit Nutzungsnachweis entfernen;
- ESLint mit `eslint-plugin-vue` für SFCs in CI integrieren; Formatierung nur für berührte Dateien, kein repositoryweiter Rauschcommit;
- direkte und transitive Dependencies auditieren, deduplizieren und SBOM/Inventar aktualisieren;
- Bundle- und Laufzeitperformance gegen Stufe 0 vergleichen; erhebliche Regressionen analysieren und freigeben lassen;
- Architektur, Baseline, Designsystem, Quality Gates, Deploymentvertrag und AGENTS.md auf den verifizierten Endstand aktualisieren;
- Release nur aus einem frischen Lockfile-Install und nach vollständigem Backend-/Frontend-Gate.

## Verbindliche Prüfungen je Stufe

Die folgende Liste beschreibt das vollständige Stufengate. In einem kleinen Arbeitspaket zunächst nur betroffene Tests, Lint und den notwendigen Build ausführen. `npm ci` läuft in einer isolierten Installation bei verändertem Lockfile und am Releasegate, nicht nach jedem Quellcodepatch im laufenden Dev-Verzeichnis. Die vollständige Backend-Suite läuft an Stufe 0, an Integrations-/Stufenabschlüssen und vor Release, nicht nach jeder CSS-Korrektur. Reine Dokumentationsänderungen benötigen Diff-/Link-/Konsistenzprüfung. Gleiche grüne Prüfungen ohne weitere relevante Änderung nicht wiederholen.

```sh
node --version
npm --version
npm ci
npm ls
npm audit --json
npm run lint
npm run test:unit
npm run test:e2e
npm run test:visual
npm run build
./vendor/bin/sail test
```

Die npm-Skripte werden in Stufe 0/der jeweils ersten benötigten Stufe eingerichtet. Nicht vorhandene Skripte dürfen vorher nicht als erfolgreich fingiert werden. Security-Audit-Befunde werden getrennt für Produktionsbundle, Build/CI und Dev-Server bewertet; `--omit=dev` allein beweist keine Nichterreichbarkeit. Ziel sind keine offenen High-/Critical-Befunde ohne explizit akzeptierte, befristete Ausnahme. Ein Audit ohne Findings beweist keine vollständige Sicherheit. `npm audit fix --force` und ungeprüftes Umgehen von Peer-Konflikten sind verboten. Eine Ausnahme benötigt Advisory, Pfad, Exploitierbarkeit, Kompensation, Frist und Owner.

Ein Login-Smoke ersetzt keine authentifizierte Nutzerreise. Kernreisen müssen mindestens Lesen, Ändern/Speichern, erneutes Laden mit persistiertem Ergebnis und einen realen Berechtigungs-/Validierungsfehler prüfen. Mock-Tests decken Fehler und Grenzfälle ab; sie ersetzen nicht die Integration gegen Laravel/MySQL mit synthetischen Daten. Shutdown-Tests dürfen keinen echten Rechner herunterfahren; Seiteneffekt isoliert abfangen und den UI-/Requestvertrag prüfen. Nicht verfügbare Umgebung = Gate offen, nicht bestanden.

Performance mit identischen Fixtures und Produktionsbuilds messen: transferierte komprimierte Bytes, Suche/Navigation und wiederholtes Öffnen/Schließen komplexer Komponenten. Mindestens drei vergleichbare Läufe, Median und Streuung festhalten. Mehr als 10 % Verschlechterung gegenüber der stabilen Referenz löst eine Untersuchung aus, keine automatische Neugestaltung. Messrauschen, geänderte Infrastruktur und größere Testdaten getrennt behandeln.

Zusätzliche Abnahme:

| Bereich | Nachweis |
| --- | --- |
| Router | jede Route direkt, Navigation, Redirect, Alias, Query/Params, 404-Fallback, Reload und Browserhistorie |
| Store/API | Erfolg, Validierungsfehler, 401/403/404/422/500, Race/Cancel soweit vorhanden, unveränderte Payloads |
| Komponenten | Props, Emits, Slots, `v-model`, Disabled/Loading/Error/Empty, Mount/Unmount-Cleanup |
| UI | Visual Diff auf allen Referenzviewports und Zuständen; manuelle Desktop-/Mobile-Sichtprüfung |
| Accessibility | Tastaturreise, Fokus, Labels/Name/Role/Value, Modal-Fokusfalle, Kontrast ohne Verschlechterung |
| Security | XSS-Fixtures für Markdown/`v-html`, unsichere URLs, CSRF-/Session-Verhalten, Dependency-Audit |
| Upload/Media | Progress, Abbruch/Fehler, Dateitypen, Preview, Video/Audio, Object-URL-/Listener-Cleanup |
| Build | frisches `npm ci`, Dev-HMR, Productionmanifest, dynamische Chunks, Source Maps, Asset-URLs |
| Backend | vollständige bestehende PHPUnit-Verträge; keine Abschwächung zur Anpassung an Frontendänderungen |

## KI-gestützter Arbeitsvertrag

KI-Agenten dürfen Inventur, offizielle Dokumentationsrecherche, Codemod-Vorschläge, Adapterentwürfe, Testfallableitung, statische Analyse, Dependency-Diffs, Builddiagnose und Browser-/Screenshotprüfung unterstützen. Dabei gelten zusätzlich:

1. Vor jeder Änderung lesen sie `AGENTS.md`, diesen Vertrag und die betroffenen Architektur-, Domänen-, Design- und Quality-Gate-Dokumente vollständig.
2. Paketfakten werden nicht aus Erinnerung behauptet. Release, Peer-Dependencies, Lizenz, Maintenance und Migration Guide werden unmittelbar vor dem Schritt aus Primärquellen oder dem installierten Paket geprüft.
3. Ein Agent ändert pro Schritt nur eine klar abgrenzbare Migrationsdimension. Framework-, UI-, Store- und Build-Cutover werden nicht in einem unprüfbaren Big Bang vermischt.
4. Generierter Code wird wie fremder Code behandelt: Review des vollständigen Diffs, keine erfundenen APIs, keine stillen Testanpassungen, keine unterdrückten Warnungen.
5. Browserautomation verwendet ausschließlich synthetische, freigegebene Testdaten und prüft sichtbaren Zustand nach jeder Interaktion. Screenshots und Traces werden auf Secrets/personenbezogene Daten geprüft.
6. Technische Unklarheiten zuerst durch Quellprüfung, einen kleinen Versuch oder einen passenden Test klären. Reproduzierbare Regressionen im beauftragten Umfang selbst reparieren. Nur bei erforderlicher Produkt-/Architekturabweichung, neuer Autorisierung oder verbleibendem externen Blocker eine gebündelte Entscheidung nach `decision-template.md` einholen; unabhängige beauftragte Arbeit fortsetzen.
7. Jeder Stufenbericht nennt geänderte Bereiche, Quellen mit Abrufdatum, ausgeführte und ausgelassene Prüfungen, Auditbefunde, Abweichungen, Rückbau und offene Risiken.

Es wird kein projektspezifischer „Vue-Migrations-Skill“ vorausgesetzt. Wird später ein solcher Skill erstellt oder installiert, erweitert er weder diesen Vertrag noch die Änderungsautorisierung; seine Anweisungen müssen diesem Vertrag untergeordnet sein.

Für die Ausführung mit GPT-5.6 Sol gilt ergänzend der kurze [Arbeitsleitfaden](vue-3-sol-workflow.md). Er enthält den Startauftrag, ein dauerhaftes Fortschrittsformat und projektspezifische Fehlerfallen. Er ist ein Repository-Leitfaden, kein automatisch installierter Skill. Tatsächlich verfügbare Skills bedarfsgerecht lesen und verwenden; keine Modellfähigkeiten oder erfolgreich ausgeführten Prüfungen unterstellen.

## Verbindlich freigegebene Zielentscheidungen

Der Auftraggeber hat am 10. September 2026 Option A in allen fünf Punkten ausdrücklich freigegeben. Diese Entscheidungen optimieren für identisches Erscheinungsbild, kleine Rückbauschritte und einen wartbaren Endzustand. Option B bleibt ausschließlich zur Nachvollziehbarkeit und als Grundlage für eine neue Entscheidung dokumentiert; sie darf nicht ohne erneute Freigabe umgesetzt werden.

| Entscheidung | Verbindliche Option A | Nicht freigegebene Option B | Auswirkung und Rückbau |
| --- | --- | --- | --- |
| UI-Basis | BootstrapVueNext + Bootstrap 5, abgeschirmt durch Materialpool-Adapter und Kompatibilitäts-Sass | benötigte BootstrapVue-Komponenten lizenziert lokal auf Vue 3 portieren und Bootstrap-4-CSS länger behalten | A folgt dem gepflegten Vue-3-Weg, verlangt aber intensive Visual-Parität; B erleichtert kurzfristig Pixelparität, erzeugt dauerhafte Wartungsverantwortung. Rückbau bei A komponentenfamilienweise, bei B nur über eigene Fork-Commits. |
| State | Vuex 3 → Vuex 4 beim Vue-Cutover, Pinia erst nach reinem Vue 3 | Vuex-Module schon auf Vue 2.7 schrittweise zu Pinia migrieren | A trennt Framework- von Storelogik; B verteilt Arbeit früher, koppelt sie aber an die dann aktuelle Pinia-/Vue-2-Kompatibilität, die vorab erneut zu prüfen wäre. |
| Build | Webpack bis reines Vue 3, danach direkter Laravel-Vite-Cutover | temporär Vite mit dem nicht mehr aktiv gepflegten Vue-2-Plugin | A vermeidet eine EOL-Brückenabhängigkeit; B isoliert den Build früher, führt aber einen zusätzlichen Wegwerf-Cutover ein. |
| TypeScript | JavaScript beibehalten, neue Verträge über ESLint, Tests und optional JSDoc; TypeScript später separat | schrittweise TypeScript-Migration parallel | A minimiert gleichzeitige Änderungen; B bringt frühere Typprüfung, vergrößert aber Diff und Fehlersuche. |
| Drittkomponenten | stabilen Ersatz hinter lokalem Adapter bevorzugen; nur kleine, klar benötigte Komponenten bei fehlender Parität portieren | frühzeitig mehr UI selbst implementieren | A reduziert eigene Wartung; B maximiert Kontrolle, erhöht aber Sicherheits-, Accessibility- und Pflegeaufwand. |

Die UI-Entscheidung schließt die im Drittkomponentenvertrag definierte Rückfallregel ein: Scheitert eine einzelne Komponente nachweislich an Funktions-, Accessibility- oder Visual-Parität, stoppt nur diese Komponentenfamilie. Eine kleine, klar begrenzte Eigenportierung kann anschließend als neue Einzelentscheidung vorgeschlagen werden. Daraus entsteht keine pauschale Freigabe, BootstrapVue oder eine andere Bibliothek zu forken.

## Stop-Kriterien

Die folgenden Punkte verhindern die Abnahme und das Weiterziehen abhängiger Schritte. Behebbare Defekte werden zunächst innerhalb des Auftrags repariert; sie erfordern nicht automatisch eine Rückfrage:

- ein fachlicher Ablauf, API-/Securityvertrag oder sichtbarer UI-Zustand weicht ab;
- visuelle Parität ist nur durch ein Redesign oder eine globale Designänderung erreichbar;
- ein erforderliches Paket besitzt keinen geeigneten stabilen Vue-3-Weg und die Portierung überschreitet eine kleine, klar abgegrenzte Komponente;
- Compat-Warnungen werden unterdrückt statt behoben;
- Build oder Tests funktionieren nur mit nicht gelockten, Beta-, Git- oder global installierten Dependencies;
- Security-/Lizenzlage ist ungeklärt;
- Testdaten oder Browsernachweise könnten reale Daten oder Secrets enthalten;
- ein Rückbau ist nicht mehr als Code-/Lockfile-Rollback möglich.

Bleibt ein Punkt nach der Diagnose bestehen und erfordert eine Änderung am vereinbarten Ziel oder zusätzliche Befugnisse, ist eine gebündelte Entscheidung mit Empfehlung, Alternativen, Vor-/Nachteilen, Nutzerwirkung und Rückbau nach `docs/ai/decision-template.md` einzuholen. Ein Rückbau erfolgt über einen gezielten Revert eigener Commits oder eine isolierte Referenzarbeitskopie; niemals durch pauschales Zurücksetzen fremder Änderungen. Jeder Zwischenstand ist als `in Arbeit`, `technischer Checkpoint` oder `releasefähig` zu kennzeichnen.

## Referenzen

- Vue 3 Migration Build: <https://v3-migration.vuejs.org/migration-build>
- Vue 3 Breaking Changes: <https://v3-migration.vuejs.org/breaking-changes/>
- Vue Framework Recommendations: <https://v3-migration.vuejs.org/recommendations.html>
- Vue Tooling: <https://vuejs.org/guide/scaling-up/tooling.html>
- Vue State Management: <https://vuejs.org/guide/scaling-up/state-management.html>
- Vue Testing: <https://vuejs.org/guide/scaling-up/testing.html>
- Vue Test Utils 2: <https://test-utils.vuejs.org/>
- Vue Security: <https://vuejs.org/guide/best-practices/security.html>
- Vue Accessibility: <https://vuejs.org/guide/best-practices/accessibility.html>
- Vue Performance: <https://vuejs.org/guide/best-practices/performance.html>
- Vue Style Guide: <https://vuejs.org/style-guide/>
- Vue Options-/Composition-API-Einordnung: <https://vuejs.org/guide/extras/composition-api-faq>
- Vue Router: <https://router.vuejs.org/guide/>
- Pinia-Migration von Vuex: <https://pinia.vuejs.org/cookbook/migration-vuex.html>
- Laravel 13 Asset Bundling mit Vite: <https://laravel.com/framework/docs/13.x/vite>
- npm Lockfile: <https://docs.npmjs.com/cli/v11/configuring-npm/package-lock-json/>
- npm CI: <https://docs.npmjs.com/cli/v11/commands/npm-ci/>
- npm Audit: <https://docs.npmjs.com/cli/v11/commands/npm-audit/>
- BootstrapVue unter Vue-3-Compat: <https://bootstrap-vue.org/vue3>
- BootstrapVueNext-Migrationsübersicht: <https://bootstrap-vue-next.github.io/bootstrap-vue-next/docs/migration-data/patterns/overview>
- Projektarchitektur: [`architecture.md`](architecture.md)
- Designvertrag: [`design-system.md`](design-system.md)
- Qualitätsregeln: [`quality-gates.md`](quality-gates.md)
- Entscheidungsvorlage: [`decision-template.md`](decision-template.md)

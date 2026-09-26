# Vertrag: Markdown-Rendering, GitHub-inspiriertes Styling und `marked`-Upgrade

## Status, Zielgruppe und Freigabegrenze

**Vertragsstand:** 21. September 2026  
**Status:** Planung erstellt; noch nicht zur Umsetzung freigegeben  
**Zielgruppe:** GPT-5.6-Sol- und GPT-5.6-Terra-Modelle sowie menschliche Entwickler  
**Technisches Endziel:** `marked` 18.0.13, `marked-smartypants` 1.1.12 und
`marked-gfm-heading-id` 4.1.4, jeweils exakt gelockt  
**Visuelle Referenz:** `github-markdown-css` 5.9.0 in der hellen Ausprägung,
ohne Übernahme als Runtime-Dependency und ohne Einführung von Primer als zweitem
Designsystem

Dieser Vertrag zerlegt die Modernisierung in einzeln prüf- und rückbaubare
Arbeitspakete. Er ist so konkret, dass ein Sol- oder Terra-Modell nach der
ausdrücklichen Gesamtfreigabe ohne zusätzliche Architekturannahmen arbeiten
kann. Bei einem Stop-Kriterium darf das ausführende Modell weder raten noch den
Umfang stillschweigend erweitern. Es hält an, berichtet den nachgewiesenen
Befund und legt eine Empfehlung mit Alternativen nach
[`decision-template.md`](decision-template.md) vor.

Der Vertrag selbst autorisiert noch keine sichtbare Änderung, keine neue
Dependency und keine Änderung der Sanitizing-Policy. Vor AP 1 ist die unter
[Entscheidung und benötigte Freigabe](#entscheidung-und-benötigte-freigabe)
formulierte Gesamtentscheidung ausdrücklich zu bestätigen.

## Auftrag

Das bestehende clientseitige Markdown-Rendering wird charakterisiert,
abgesichert, testbar strukturiert und GitHub-inspiriert gestaltet. Erst nachdem
die anwendungseigenen Erweiterungen und das sichtbare Verhalten automatisiert
belegt sind, wird `marked` von 4.3.0 auf die aktuelle, für diesen Vertragsstand
verifizierte Version 18.0.13 aktualisiert.

Die Modernisierung muss insbesondere vollständig erhalten:

- automatische Erkennung deutscher Bibelstellen;
- Darstellung erkannter Bibelstellen durch
  `bibleverse-inline-popover-txt` einschließlich nachladbarem Popover;
- Zusammenfassungszeilen mit `->`, `-->`, `=>` und `==>`;
- Inline-Markdown innerhalb einer Zusammenfassungszeile;
- GitHub Flavored Markdown, Tabellen, Task-Listen und durch `breaks: true`
  erzeugte Zeilenumbrüche;
- typografische Umwandlung gerader Anführungszeichen und Bindestrichfolgen, die
  heute durch `smartypants: true` entsteht;
- Überschriften-IDs und damit bestehende Fragmentlinks;
- Speicherung des unveränderten Markdown-Rohtexts und die bestehenden API-
  Payloads für Textresources.

## Verbindliche Quellen und Reihenfolge

Vor jedem Arbeitspaket sind mindestens die für das Paket betroffenen Dateien
erneut zu lesen. Für die Gesamtumsetzung gelten zusätzlich:

- [`architecture.md`](architecture.md)
- [`design-system.md`](design-system.md)
- [`quality-gates.md`](quality-gates.md)
- [`vue-3-migration-contract.md`](vue-3-migration-contract.md)
- [`readme-maintenance.md`](readme-maintenance.md)
- die zum Ausführungszeitpunkt offiziellen Dokumentationen und Release Notes
  von [`marked`](https://marked.js.org/),
  [`DOMPurify`](https://github.com/cure53/DOMPurify) und
  [`github-markdown-css`](https://github.com/sindresorhus/github-markdown-css)

Eine neuere Online-Dokumentation darf diesen Vertrag nicht stillschweigend
erweitern. Ergibt die erneute Prüfung eine neuere stabile `marked`-Version als
18.0.13, gilt das Stop-Kriterium aus AP 7.

## Ziel und Grenzen

### Im Umfang

- heutiges Parser-, Sanitizing-, Erweiterungs- und Darstellungsverhalten durch
  Charakterisierungstests festhalten;
- die heutige globale `marked`-Singleton-Konfiguration zunächst hinter einer
  eindeutigen Parse-Grenze kapseln und mit der Zielversion in eine nur einmal
  konfigurierte Parserinstanz überführen;
- die Sanitizing-Policy auf tatsächlich benötigtes Markdown-HTML begrenzen;
- Bibelstellen- und Zusammenfassungs-Erweiterungen an den aktuellen
  `marked`-Extension-Vertrag anpassen, ohne ihre Produktsyntax zu verändern;
- Markdown-Styling innerhalb der bestehenden Komponentengrenze erneuern;
- Inline-Code und Codeblöcke korrekt trennen;
- Überschriften, Listen, Task-Listen, Tabellen, Links, Bilder, Zitate,
  Trennlinien und Code responsiv und zugänglich gestalten;
- Texteditor und Live-Vorschau auf kleinen Viewports stapeln und auf großen
  Viewports weiterhin nebeneinander zeigen;
- `marked` zuletzt kontrolliert auf die aktuelle Zielversion aktualisieren;
- neue oder geänderte Parser-, Sanitizing-, Browser- und Visual-Regressionstests
  ergänzen;
- betroffene README-Aussagen und Screenshots nach dem bestehenden
  Dokumentationsvertrag prüfen.

### Nicht im Umfang

- Datenbank-, Modell-, API-, Route-, Authentifizierungs-, Policy- oder
  Berechtigungsänderungen;
- Umwandlung oder Neuschreibung gespeicherter Markdown-Texte;
- serverseitiges Markdown-Rendering;
- WYSIWYG-, Rich-Text- oder Block-Editor;
- Syntax-Highlighting mit Shiki, Highlight.js oder einer anderen neuen
  Dependency;
- Mermaid, MathML, SVG, eingebettete iframes, Audio oder Video;
- frei wählbare Themes, Dark Mode oder nutzerspezifische Designpräferenzen;
- Änderung der Bibelstellen-Regeln im eingebundenen Bible-verse-Paket;
- Änderung der fachlichen Bedeutung der Zusammenfassungsmarker;
- Übernahme des vollständigen GitHub- oder Primer-Designsystems.

### Bewusst vertagt

- synchronisiertes Scrollen zwischen Editor und Vorschau;
- Markdown-Werkzeugleiste, Autovervollständigung und Tastaturkürzel jenseits
  der vorhandenen Speichern-/Abbrechen-Aktionen;
- sichtbare Anker-Icons neben Überschriften;
- Code-Syntax-Highlighting;
- Umschaltung zwischen mehreren Markdown-Themes.

Vertagte Punkte sind keine stillschweigend bestandenen Abnahmekriterien.

## Bestandsinventar

### Laufzeitpfad

```text
Textresource.content
  -> compiledMarkdown.vue
  -> marked 4.3.0 mit GFM und Materialpool-Erweiterungen
  -> unsanitisiertes HTML
  -> DOMPurify 3.4.15
  -> erneutes Parsen in ein temporäres DOM
  -> rekursive Vue-VNodes
  -> bibleverse-inline-popover-txt als echte Vue-Komponente
```

Der Markdown-Rohtext wird in `Text::content` gespeichert. Parser- oder
CSS-Änderungen dürfen den Rohtext weder beim Lesen noch beim Speichern
normalisieren, umschreiben oder migrieren.

### Betroffene Dateien und heutige Verantwortung

| Ort | Heutige Verantwortung | Vertrag für die Modernisierung |
| --- | --- | --- |
| `resources/js/components/markdown/compiledMarkdown.vue` | Drosselung, Parsen, Sanitizing, DOM-zu-VNode-Abbildung und Markdown-CSS | bleibt einzige allgemeine Vue-Anzeigekomponente; kein `v-html` und keine Runtime-Template-Kompilierung |
| `resources/js/components/markdown/markdownSetup.js` | globale `marked.use()`-Konfiguration | liefert eine isolierte, genau einmal konfigurierte Parserinstanz oder eine kleine Parse-Funktion |
| `resources/js/components/markdown/sanitizeSetup.js` | DOMPurify mit breiter Standard-Allowlist | erhält explizite Markdown-Allowlist und getestete URL-/Attributregeln |
| `resources/js/components/markdown/bibleverseRenderer.js` | Inline-Tokenizer und Custom-Element-Ausgabe | Syntax, Treffertext, Autoload und Vue-Komponentenübergabe bleiben erhalten |
| `resources/js/components/markdown/arrowRenderer.js` | Block-Tokenizer für Zusammenfassungszeilen | vier Marker, Pfeilzeichen, einzeiliger Verbrauch und Inline-Parsing bleiben erhalten |
| `resources/js/components/bibleverse/bibleverse-inline-popover-txt.vue` | interaktive Bibelstellenanzeige | öffentlicher Props-/Interaktionsvertrag bleibt unverändert |
| `resources/js/components/resource/show/text-detail.vue` | Lesen, Aktivieren des Editors, Live-Vorschau und Speichern | API-Payload bleibt unverändert; responsive und zugängliche Anordnung wird verbessert |
| `resources/js/apps/main/pages/ResourceTextCreateWithMaterial.vue` | neue Textresource mit Live-Vorschau | Erzeugungsablauf und Validierung bleiben erhalten; Vorschau erhält dasselbe Styling |
| `resources/js/apps/main/stores/resources.js` | Laden und Speichern des Rohtexts | keine Vertragsänderung vorgesehen |
| `tests/js/bibleverseRenderer.spec.js` | schmaler Escaping-Test | wird Teil einer vollständigen Parser-Vertragsgruppe |
| `tests/browser/` | echte Browser-, Responsive- und Visual-Abnahme | erhält dedizierte Markdown-Abläufe und deterministische Fixtures |

Direkte Markdown-Parser- oder Sanitizer-Nutzung außerhalb des Verzeichnisses
`resources/js/components/markdown/` ist im Zielzustand unzulässig. Verbraucher
verwenden ausschließlich `compiledMarkdown.vue`.

## Unveränderliche Produktverträge

### Markdown-Grundprofil

| Funktion | Sollvertrag |
| --- | --- |
| GFM | aktiviert |
| einfache Zeilenumbrüche | `breaks: true`; ein einzelner Zeilenumbruch erzeugt weiterhin `<br>` |
| Tabellen | GFM-Tabellen werden semantisch als Tabelle ausgegeben |
| Task-Listen | GFM-Checkboxen bleiben sichtbar, nicht editierbar und tastaturseitig nicht als Formulareingabe missverständlich |
| Durchstreichung | GFM-Durchstreichung bleibt erhalten |
| Inline-Code | bleibt innerhalb des Absatzflusses; kein Blocklayout |
| Codeblock | bleibt blockförmig, horizontal scrollbar und übernimmt vorhandene `language-*`-Klassen |
| Smart punctuation | sichtbares Ergebnis von `smartypants: true` bleibt über `marked-smartypants` erhalten |
| Überschriften-ID | bestehende Slug-Semantik bleibt über `marked-gfm-heading-id` erhalten; keine sichtbaren Anker-Icons |
| Roh-HTML | nur die in der Sanitizing-Policy erlaubte sichere Teilmenge bleibt wirksam; nicht erlaubte Container verlieren ihr Element, lesbarer Text bleibt soweit sicher erhalten |

Bytegenaue Gleichheit des erzeugten HTML ist kein Produktvertrag. Semantik,
sichtbarer Text, Links, Fragmentziele und Materialpool-Erweiterungen sind es.

### Bibelstellenerkennung

Die Bibelstellenerkennung bleibt eine Inline-Erweiterung. Verbindlich sind:

- das vorhandene Pattern aus `BibleVerseService.biblePattern` bleibt die einzige
  fachliche Quelle;
- `start()` bleibt nur eine Optimierung und darf keine abweichende fachliche
  Erkennung definieren;
- der gesamte vom Pattern erkannte Text wird konsumiert und als `text` an
  `bibleverse-inline-popover-txt` übergeben;
- `loadContents` bleibt für den allgemeinen Markdown-Renderer `true`;
- Nutzertext darf nicht als Vue-Template, dynamische Prop-Bindung oder
  ausführbares Attribut interpretiert werden;
- Sonderzeichen werden vor der HTML-Zwischenrepräsentation korrekt escaped und
  durch DOMPurify erneut geprüft;
- Bibelstellen in normalem Fließtext bleiben interaktiv;
- Bibelstellen in Inline-Code, Codeblöcken, HTML-Attributen und Linkzielen
  dürfen nicht zu Popovern werden;
- mehrere Bibelstellen in einem Dokument müssen unabhängig funktionieren;
- die Komponente lädt Inhalte weiterhin erst über ihren bestehenden Ablauf und
  nicht während Parser- oder Sanitizer-Tests.

Mindestens folgende fachlichen Formen werden als Fixture aus dem tatsächlich
installierten Bible-verse-Paket abgeleitet und festgeschrieben:

- Buch, Kapitel und einzelner Vers;
- nummeriertes Buch;
- Versbereich;
- mehrere Treffer in getrennten Zeilen;
- unmittelbar angrenzende Satzzeichen;
- ein ähnlich aussehender Nichttreffer;
- Treffer in Fettschrift beziehungsweise einer Zusammenfassungszeile;
- Nichttreffer in Inline- und Blockcode.

Die Tests dürfen keine neue Schreibweise erfinden. Falls eine der genannten
Formen vom heutigen Pattern nicht unterstützt wird, dokumentiert AP 1 den
Istzustand und erwartet genau diesen. Eine fachliche Erweiterung des Patterns
benötigt einen separaten Auftrag.

### Zusammenfassungszeilen

Eine Zusammenfassungszeile beginnt nach optionalem horizontalem Leerraum am
Zeilenanfang mit genau einem der folgenden Marker:

| Eingabe | sichtbares Pfeilzeichen | Codepoint |
| --- | --- | --- |
| `-> Text` | `→` | U+2192 |
| `--> Text` | `→` | U+2192 |
| `=> Text` | `⇒` | U+21D2 |
| `==> Text` | `⇒` | U+21D2 |

Verbindlich sind außerdem:

- genau die betroffene Zeile wird als eigener Block konsumiert;
- der Text nach dem Marker wird mit dem Inline-Parser verarbeitet;
- Hervorhebungen, Links und erkannte Bibelstellen innerhalb des Textes bleiben
  funktionsfähig;
- die ausgegebene Klasse `summary` bleibt erhalten;
- ein Marker innerhalb eines Absatzes, Inline-Codes oder Codeblocks wird nicht
  als Zusammenfassungszeile interpretiert;
- aufeinanderfolgende Zusammenfassungszeilen erzeugen eigenständige Blöcke;
- normales Markdown vor und nach der Zeile bleibt strukturell getrennt.

Eine Änderung der Pfeilzeichen, Marker oder Blocksemantik ist außerhalb dieses
Vertrags.

## Zielarchitektur

### Parsergrenze

`marked` 4.3.0 exportiert noch keine `Marked`-Klasse. Vor dem Upgrade kapselt
`markdownSetup.js` deshalb die bestehende, einmalig auf Modulebene konfigurierte
Singletoninstanz hinter einer kleinen Parse-Funktion. Die Registrierung darf
nicht bei jedem Funktionsaufruf erfolgen.

Mit `marked` 18 erzeugt `markdownSetup.js` im Zielzustand eine eigene
`Marked`-Instanz. Die globale, aus dem Paket exportierte Singletoninstanz wird
dann nicht mehr konfiguriert. Damit dürfen HMR, mehrfaches Importieren oder
Tests Erweiterungen nicht mehrfach registrieren.

Die Instanz erhält in dokumentierter Reihenfolge:

1. unterstützte Kernoptionen `gfm: true` und `breaks: true`;
2. `marked-smartypants` zur Erhaltung der sichtbaren Typografie;
3. `marked-gfm-heading-id` zur Erhaltung bestehender Fragmentziele;
4. den Bibleverse-Inline-Tokenizer;
5. den Summary-Block-Tokenizer.

Die veralteten beziehungsweise entfernten Optionen `sanitize`, `smartLists`,
`smartypants` und `tables` stehen nach AP 7 nicht mehr in der
`marked`-Konfiguration. Tabellen folgen dem GFM-Profil.

Die öffentliche anwendungsinterne API bleibt synchron und liefert für einen
String einen HTML-String. Asynchrones Parsen wird nicht aktiviert.

### Sanitizing-Grenze

Nicht vertrauenswürdiger Markdown-Text wird zuerst geparst und danach genau
einmal mit DOMPurify bereinigt. Die Sanitizing-Policy ist zentral, unveränderlich
exportiert und testbar. Erlaubt sind ausschließlich:

```text
a, blockquote, br, code, del, div, em,
h1, h2, h3, h4, h5, h6, hr, img, input,
li, ol, p, pre, span, strong,
table, tbody, td, th, thead, tr, ul,
bibleverse-inline-popover-txt
```

Die Attribute werden ebenfalls explizit begrenzt:

```text
alt, align, checked, class, data-load-contents, data-text,
disabled, href, id, loading, rel, src, start, title, type
```

Zusätzliche Regeln:

- `style`, `name`, `srcset`, Eventhandler und beliebige `data-*`-Attribute sind
  nicht erlaubt;
- SVG, MathML, Formulare, Buttons, Selects, Textfelder, iframes, Audio, Video,
  Canvas, Templates und Stylesheets sind nicht erlaubt;
- `input` bleibt ausschließlich wegen GFM-Task-Listen erlaubt; nach dem
  Sanitizing darf es nur `type="checkbox"`, `disabled`, optional `checked` und
  die erwartete Task-List-Klasse tragen;
- ein unerlaubtes oder veränderbares `input` wird entfernt, nicht nachträglich
  repariert;
- Links erlauben relative URLs, Fragmentziele sowie `http`, `https` und
  `mailto`; `javascript`, `data`, `file` und unbekannte Schemes sind verboten;
- Bilder erlauben relative URLs sowie `http` und `https`; Data-URIs und
  unbekannte Schemes sind verboten;
- Bilder erhalten in der VNode-Abbildung `loading="lazy"`, sofern das Attribut
  nicht bereits sicher vorhanden ist;
- `target` wird nicht erlaubt; Links behalten das heutige Same-Tab-Verhalten;
- DOMPurifys Schutz gegen DOM-Clobbering und XML-/Namespace-Verwechslungen darf
  nicht deaktiviert werden;
- die Policy verwendet keine breite `ADD_TAGS`-/`ADD_ATTR`-Erweiterung der
  Standard-Allowlist, sondern die oben definierte vollständige Allowlist.

DOMPurify bleibt die letzte Sicherheitsprüfung vor der DOM-Verarbeitung. Nach
dem Sanitizing dürfen ausschließlich konstante, anwendungseigene Attribute wie
das beschriebene `loading="lazy"` ergänzt werden. Nutzerkontrollierte Werte
dürfen nach der Bereinigung nicht verändert oder neu zusammengesetzt werden.

Die VNode-Abbildung darf weiterhin notwendig sein, um das bereinigte
`bibleverse-inline-popover-txt` in eine echte Vue-Komponente zu übersetzen. Sie
darf weder beliebige Tag-Namen in Komponenten auflösen noch Vue-Direktiven aus
Attributen interpretieren.

### Styling-Grenze

Das Styling gehört zur Markdown-Komponente. Der lokale Styleblock wird
`scoped`; dynamisch erzeugte Nachfahren werden gezielt über `:deep(...)`
adressiert. Alternativ ist genau eine dedizierte Markdown-Sass-Partialdatei
zulässig, wenn der Production-Build nachweist, dass sie nur unter
`.compiledMarkdown` wirkt. Allgemeine Elementselektoren außerhalb dieser
Wurzel sind unzulässig.

Alle Farben, Rahmen, Radien und semantischen Zustände verwenden vorhandene
Materialpool-/Bootstrap-Tokens aus `resources/sass/theme.scss` oder
Bootstrap-CSS-Variablen. Es werden keine GitHub-Hexwerte kopiert und keine neue
Schrift geladen. Raleway bleibt die Anwendungsschrift; Code verwendet den
vorhandenen Bootstrap-Monospace-Stack.

## GitHub-inspiriertes visuelles Ziel

Die Referenz ist GitHubs ruhige Dokumentdarstellung, nicht eine pixelgenaue
Kopie. Verbindlich sind folgende Gestaltungsziele:

### Lesefläche und Rhythmus

- `.compiledMarkdown` übernimmt Text- und Hintergrundfarbe vom umgebenden
  Bootstrap-Kontext;
- Detailansichten erhalten eine lesbare maximale Textbreite zwischen 72 und
  78 Zeichen und bleiben innerhalb ihres vorhandenen Containers;
- die Live-Vorschau nutzt die verfügbare Spaltenbreite und erzwingt keine
  zusätzliche Mindestbreite;
- Zeilenhöhe für Fließtext liegt zwischen 1.5 und 1.65;
- erster und letzter Kindblock erzeugen keinen unnötigen Außenabstand;
- lange URLs, Codefragmente und Wörter dürfen den Viewport nicht verbreitern.

### Überschriften

- `h1` bis `h6` bilden eine klar abnehmende Hierarchie;
- `h1` und `h2` erhalten nach GitHub-Vorbild eine dezente untere Trennlinie;
- Überschriften besitzen ausreichend Abstand zum vorherigen Abschnitt und
  weniger Abstand zum direkt folgenden Inhalt;
- vorhandene `id`-Attribute bleiben erhalten;
- es entstehen keine zusätzlichen sichtbaren Link-Icons.

### Links und Fokus

- Links verwenden das vorhandene `$link-color`-/`--bs-link-color`-Token;
- Links im Fließtext sind nicht allein durch Farbe erkennbar;
- Hover- und `:focus-visible`-Zustände sind klar sichtbar;
- Bibelstellen bleiben visuell als interaktive Textstellen erkennbar, ohne wie
  primäre Buttons zu wirken.

### Code

- `code` innerhalb von Fließtext bleibt `display: inline` und erhält nur kleine
  horizontale Innenabstände, Monospace-Schrift und eine dezente Fläche;
- `pre` ist der Blockcontainer für Codeblöcke und übernimmt Padding, Rahmen,
  Radius und horizontales Scrollen;
- `pre > code` erhält keinen zweiten Rahmen, keinen eigenen Blockabstand und
  keine doppelte Hintergrundfläche;
- Whitespace in Codeblöcken bleibt erhalten;
- eine vorhandene `language-*`-Klasse bleibt unangetastet, löst aber in diesem
  Auftrag noch kein Syntax-Highlighting aus.

### Tabellen

- Tabellen besitzen Kopfzeile, Zellrahmen, ausreichende Zellabstände und
  alternierende Zeilenflächen nach GitHub-Vorbild, umgesetzt mit
  Bootstrap-Tokens;
- breite Tabellen werden in einem ausschließlich für Markdown erzeugten
  Wrapper horizontal scrollbar;
- Tabellen behalten echte `table`-/`thead`-/`tbody`-/`th`-/`td`-Semantik;
- der Scrollwrapper darf Popover-Inhalte nicht vertikal abschneiden;
- Ausrichtung aus GFM-Tabellenspalten bleibt erhalten.

Der Wrapper wird entweder durch einen getesteten Table-Renderer oder in der
sanitisierten VNode-Abbildung erzeugt. Eine DOM-Nachbearbeitung außerhalb der
Komponente ist unzulässig.

### Listen, Task-Listen und Zitate

- ungeordnete und geordnete Listen erhalten konsistente Einzüge und
  Blockabstände;
- verschachtelte Listen bleiben erkennbar;
- Task-List-Checkboxen sind deaktiviert, am Text ausgerichtet und erhalten
  keinen allgemeinen Formularstil;
- Blockzitate verwenden eine dezente linke beziehungsweise logische
  Start-Rahmenlinie und die sekundäre Textfarbe;
- Zitatinhalt bleibt ausreichend kontrastreich.

### Bilder und Trennlinien

- Bilder überschreiten nie die Breite des Markdown-Containers;
- Höhe bleibt proportional; das Styling erzwingt keine feste Bildhöhe;
- Alternativtext bleibt erhalten;
- horizontale Linien verwenden das vorhandene Border-Token.

### Zusammenfassungszeilen

- `.summary` bleibt ein eigener Absatzblock;
- Pfeil und Inhalt werden stabil ausgerichtet, ohne den Pfeil als eigenes Icon-
  Paket abzubilden;
- mehrere Zeilen besitzen denselben vertikalen Rhythmus wie eine kompakte Liste;
- die beiden Pfeilarten bleiben visuell unterscheidbar;
- Farbe allein trägt keine fachliche Bedeutung.

## Editor- und Vorschauvertrag

Die vorhandene Speicherung bleibt unverändert. Erlaubt sind ausschließlich
folgende UX-Anpassungen:

- ab dem vorhandenen großen Bootstrap-Breakpoint stehen Editor und Vorschau
  nebeneinander;
- darunter werden sie in der Reihenfolge Editor, Vorschau gestapelt;
- beide Bereiche besitzen sichtbare, lokalisierte Beschriftungen;
- der Editor behält eine sinnvolle Mindesthöhe, verwendet mobil aber keine
  starre `80vh`-Gesamthöhe;
- der Editor erhält einen sichtbaren `:focus-visible`-Zustand;
- Speichern und Abbrechen bleiben explizite Buttons;
- vorhandener Doppelklick zum Bearbeiten darf als Komfortfunktion bleiben, ist
  aber nicht der einzige auffindbare Zugang zum Editor;
- `Cmd+Enter` auf macOS und `Ctrl+Enter` auf Windows/Linux lösen denselben
  vorhandenen Speichervorgang aus;
- während eines laufenden Speichervorgangs wird Mehrfachauslösung verhindert;
- Fehler-, Erfolgs- und Abbruchmeldungen verwenden weiterhin den bestehenden
  Flash-Mechanismus;
- sichtbare Texte und Tastaturhinweise werden über `resources/lang/` geführt.

Keine dieser Änderungen darf die API-Methode, den Payload `{content: ...}`, die
Policy oder die Ereigniskette beim Aktualisieren einer Resource ändern.

## Testmatrix

### Parser-Vertragstests

Eine neue oder klar erweiterte Vitest-Gruppe prüft mindestens:

| Bereich | Fälle |
| --- | --- |
| GFM | Überschrift, Absatz, einfacher Umbruch, Fett, Kursiv, Durchstreichung, Link, Bild, Liste, Task-Liste, Tabelle |
| Code | Inline-Code, Fenced Code mit und ohne Sprache, Bibelstellen und Summary-Marker innerhalb von Code bleiben inert |
| Überschriften | deterministische IDs, Dubletten, Umlaute/Sonderzeichen und interner Fragmentlink |
| Smart punctuation | gerade Anführungszeichen, Apostroph, `--`, `---` und Auslassungspunkte anhand des heutigen Outputs |
| Bibelstellen | alle im Abschnitt Bibelstellenerkennung definierten positiven und negativen Fixtures |
| Summary | alle vier Marker, führender Leerraum, Inline-Markdown, Bibelstelle, Folgezeilen und negative Code-/Absatzfälle |
| Robustheit | leerer String, nur Whitespace, sehr langer Absatz und mehrfacher Parseraufruf ohne doppelte Erweiterung |

AP 1 legt die Erwartungen mit `marked` 4.3.0 fest. AP 7 führt dieselben Tests
unverändert gegen `marked` 18.0.13 aus. Erwartungsänderungen in AP 7 sind nur
zulässig, wenn sie im Vertrag bereits als nicht bytegenaue, semantisch gleiche
Ausgabe ausgewiesen sind. Bibelstellen- und Summary-Erwartungen dürfen in AP 7
nicht abgeschwächt werden.

### Sanitizing-Vertragstests im echten Browser

DOMPurify wird nicht mit einem neu eingeführten, künstlichen DOM-Shim getestet.
Die Sicherheitsfälle laufen in einem von Playwright gestarteten echten Browser
gegen den tatsächlich gebauten Anwendungscode. Mindestens:

- `script`, Eventhandler und `javascript:`-URL werden entfernt;
- `style`-Attribut und `style`-Element werden entfernt;
- SVG und MathML werden entfernt;
- Formular, Button, Textfeld, iframe, Audio und Video werden entfernt;
- lesbarer Text in entfernten Containern bleibt erhalten, soweit dies die
  festgelegte DOMPurify-Policy sicher zulässt;
- unsichere Task-List-Inputs werden entfernt;
- echte, deaktivierte GFM-Task-List-Checkboxen bleiben erhalten;
- relative, Fragment-, HTTP-, HTTPS- und Mailto-Links folgen der URL-Policy;
- Bilder mit erlaubter URL bleiben responsiv, Data-URIs und unbekannte Schemes
  werden entfernt;
- das Bibleverse-Custom-Element behält ausschließlich `data-text` und
  `data-load-contents` und wird danach als Vue-Komponente gerendert;
- speziell präparierte Attribute werden weder zu Vue-Props noch zu Direktiven.

### Browser- und Visual-Vertrag

Eine deterministische Markdown-Fixture enthält alle sichtbaren Elementtypen auf
einer Seite. Sie verwendet ausschließlich synthetische Texte, lokale Testbilder
und gefakte API-Antworten. Erforderlich sind:

- Desktop-WebKit bei 1440 × 900;
- Mobile-WebKit mit dem bestehenden mobilen Projekt/ViewPort;
- Leseansicht;
- Bearbeitungsansicht mit Editor und Live-Vorschau;
- lange Tabelle, langer Link, großes Bild, Codeblock, Task-Liste,
  Zusammenfassungszeilen und interaktive Bibelstelle;
- Tastaturfokus auf Bearbeiten, Editor, Link, Bibelstelle, Speichern und
  Abbrechen;
- keine Page Errors und keine relevanten Console Errors;
- Screenshotvergleich der vollständigen Markdown-Fixture.

Ein Screenshot darf erst übernommen werden, nachdem die Differenz einzeln
visuell geprüft wurde. Ein zweiter unveränderter Lauf muss denselben Stand
erzeugen.

### Performance- und Stabilitätsgrenzen

- Live-Vorschau bleibt bei normalem Tippen reaktionsfähig;
- die konfigurierbare Verzögerung wird entweder tatsächlich verwendet oder die
  ungenutzte Prop wird entfernt; Name und Verhalten müssen übereinstimmen;
- eine ausstehende gedrosselte Aktualisierung wird beim Unmount abgebrochen;
- ein Dokument mit 100 KB synthetischem Markdown wird in Chromium ohne Fehler,
  Endlosschleife oder mehrsekündiges Blockieren dargestellt;
- es wird kein Parsercache mit Nutzertext über Resource-Grenzen hinweg
  eingeführt;
- Browser- und Bundlebudgets aus dem bestehenden Vue-Vertrag bleiben grün.

Der 100-KB-Test ist ein lokaler Robustheitstest, kein allgemeiner
Performancebenchmark und kein Grund für eine neue Worker-Architektur.

## Ausführungspakete

Jedes Arbeitspaket beginnt mit `git status --short`, der erneuten Lektüre der
erlaubten Dateien und der Prüfung auf fremde Änderungen. Es endet mit den
kleinstmöglichen passenden Tests und einem Diff-Review. Unverbundene Änderungen
werden nicht angefasst.

### AP 0 – Freigabe, Baseline und Versionsprüfung

**Zweck:** Auftrag und reproduzierbaren Ausgangszustand sichern.  
**Erlaubte Änderungen:** keine Produktivdateien; ausschließlich Ergänzungen an
diesem Vertrag, falls die Freigabe davon abweicht.  
**Aktionen:**

1. Gesamtentscheidung am Ende dieses Dokuments bestätigen lassen.
2. `git status --short` und fremde Änderungen dokumentieren.
3. installierte Versionen von Node, npm, `marked` und DOMPurify protokollieren.
4. `npm view marked version` und offizielle Release-/Security-Hinweise prüfen.
5. aktuellen gezielten Unit-Test, Lint, Production-Build und vorhandene
   relevante Browserabläufe ausführen.
6. eine aktuelle Lese- und Bearbeitungsansicht als nicht zu commitende
   Vergleichsaufnahme sichern.

**Abnahme:** Baseline ist grün oder bestehende Fehler sind eindeutig als
vorbestehend dokumentiert; Zielversion ist weiterhin freigegeben.  
**Rückbau:** keiner, da keine Produktivänderung.  
**Stop:** neuere stabile `marked`-Version als 18.0.13, Security Advisory für
eine Zieldependency, nicht erfüllte Node-Anforderung oder nicht erklärbare
Baselinefehler.

### AP 1 – Charakterisierung vor Änderungen

**Zweck:** Bestehendes Verhalten beweisen, bevor Parser oder CSS geändert
werden.  
**Erlaubte Dateien:** `tests/js/**`, Markdown-nahe `tests/browser/**` und
deterministische Testfixtures.  
**Aktionen:** Parser-, Bibleverse-, Summary- und Renderingfälle aus der
Testmatrix gegen `marked` 4.3.0 ergänzen. Erwartete HTML-Details werden nur dort
festgeschrieben, wo sie Produktvertrag sind; ansonsten werden Semantik und
sichtbarer Inhalt geprüft.  
**Abnahme:** alle vier Summary-Marker und alle abgeleiteten Bibleverse-Fixtures
sind positiv/negativ abgedeckt; Tests schlagen nach gezielter lokaler
Deaktivierung der jeweiligen Extension nachweislich fehl.  
**Rückbau:** reine Teständerung zurücksetzen.  
**Stop:** heutige Parserausgabe widerspricht einem in diesem Vertrag als
unveränderlich bezeichneten Verhalten. Dann Vertrag mit belegtem Istzustand
klären, nicht den Test passend machen.

### AP 2 – Parse-Grenze ohne Versionswechsel

**Zweck:** Aufrufer vom Paketexport entkoppeln und eine klare Upgradegrenze
schaffen.  
**Erlaubte Dateien:** `resources/js/components/markdown/markdownSetup.js`, bei
Bedarf eine kleine neue Datei im selben Verzeichnis, sowie AP-1-Tests.  
**Aktionen:** bestehende Singletonkonfiguration ausschließlich auf Modulebene
behalten; eine kleine synchrone Parse-Funktion als anwendungsinterne Grenze
exportieren; Aufrufer auf diese Grenze umstellen; Erweiterungen weder pro
Aufruf noch über einen zweiten Importpfad registrieren. Noch keine Dependency
ändern und noch keine nicht vorhandene `Marked`-Klasse nachbauen.  
**Abnahme:** AP-1-Tests unverändert grün; mehrfache Imports/Parseraufrufe
verdoppeln weder Summary-Blöcke noch Bibleverse-Komponenten.  
**Rückbau:** `markdownSetup.js` und die zugehörigen Tests gemeinsam
zurücksetzen.  
**Stop:** Die Parse-Grenze verlangt eine Änderung des erzeugten Outputs oder
eine zweite parallele Parserkonfiguration. Dann den Istzustand beibehalten und
die Abweichung vorlegen, nicht vorzeitig upgraden.

### AP 3 – Sanitizing-Policy härten

**Zweck:** Markdown-Ausgabe auf den benötigten sicheren HTML-Vertrag begrenzen.  
**Erlaubte Dateien:** `sanitizeSetup.js`, kleine Markdown-nahe Helper,
`compiledMarkdown.vue` ausschließlich für sichere VNode-Regeln und die
zugehörigen Tests.  
**Aktionen:** vollständige Tag-/Attribut-Allowlist, URL-Regeln und
Task-List-Input-Prüfung umsetzen; tote `.myMarkdown`-Debugregel noch nicht im
gleichen Commit entfernen, wenn dies den Sicherheitsdiff unübersichtlich
macht.  
**Abnahme:** alle Sanitizing-Browsertests grün; Bibleverse-Popover und
GFM-Task-Liste funktionieren; keine Form-, Media-, SVG-/MathML- oder
Style-Inhalte überleben.  
**Rückbau:** Policy und Tests als Einheit auf die vorige Version zurücksetzen.  
**Stop:** nachgewiesene Bestandsinhalte benötigen ein ausgeschlossenes Element
für einen freigegebenen Fachablauf. Dann Element, Risiko und Alternative als
neue Entscheidung vorlegen.

### AP 4 – GitHub-inspiriertes Markdown-Styling

**Zweck:** vollständige, gekapselte und tokenbasierte Dokumentdarstellung.  
**Erlaubte Dateien:** `compiledMarkdown.vue`, bei begründetem Bedarf genau eine
Markdown-Sass-Partialdatei und `theme.scss` nur für tatsächlich
komponentenübergreifend wiederverwendbare Tokens; Visual-/Browserfixtures.  
**Aktionen:** alle Abschnitte des visuellen Ziels umsetzen; Inline-Code von
Codeblöcken trennen; tote CSS-Regeln und harte GitHub-/Altfarben entfernen;
Tabellenwrapper ergänzen. `github-markdown-css` weder installieren noch
vollständig kopieren.  
**Abnahme:** Desktop- und Mobile-Fixture visuell geprüft; Styles wirken nicht
außerhalb `.compiledMarkdown`; dynamische Nachfahren sind trotz Scoped CSS
vollständig gestaltet; Accessibility-Prüfung ohne neue Befunde.  
**Rückbau:** Markdown-Styleänderung und Visual-Baseline gemeinsam zurücksetzen.  
**Stop:** Umsetzung verlangt neue globale Typografie-, Farb- oder
Layoutentscheidungen außerhalb des Markdown-Containers.

### AP 5 – Texteditor und Live-Vorschau responsiv machen

**Zweck:** Markdown-Erstellung und -Bearbeitung auf Desktop, Mobile und per
Tastatur nutzbar machen.  
**Erlaubte Dateien:** `text-detail.vue`,
`ResourceTextCreateWithMaterial.vue`, passende Sprachdateien und gezielte
Browsertests.  
**Aktionen:** responsive Spalten, Labels, Fokus, expliziten Bearbeitungszugang,
beide Plattformkürzel und Schutz vor Mehrfachspeichern gemäß Editorvertrag
umsetzen. Keine Store-/API-Änderung.  
**Abnahme:** Erstellen, Bearbeiten, Speichern, Abbrechen und Fehlerfall auf
Desktop/Mobile; Payload unverändert; Tastaturablauf und sichtbarer Fokus
bestanden.  
**Rückbau:** SFC-, Übersetzungs- und Browserteständerungen gemeinsam
zurücksetzen.  
**Stop:** gewünschte Bedienung erfordert einen neuen Editor oder eine Änderung
des Resource-API-Vertrags.

### AP 6 – Vorbereitender Upgrade-Checkpoint

**Zweck:** letzten grünen Stand vor dem Major-Upgrade sichern.  
**Erlaubte Änderungen:** nur Tests oder Dokumentation, keine Dependency.  
**Aktionen:** vollständige Markdown-Testmatrix, Lint, Build und gezielte
Browser-/Visual-Suite ausführen; Bundlegröße protokollieren; `rg` auf direkte
`marked`-/DOMPurify-Verbraucher und obsolete Optionen ausführen.  
**Abnahme:** Sicherheits-, Funktions- und Stylingstand ist mit `marked` 4.3.0
vollständig grün und als Rücksprungpunkt identifizierbar.  
**Rückbau:** nicht erforderlich.  
**Stop:** offene Regression, flackernde Visual-Baseline oder nicht erklärte
Parsernutzung außerhalb der zentralen Grenze.

### AP 7 – `marked` auf die aktuelle Zielversion aktualisieren

**Zweck:** kontrollierter Major-Sprung bei erhaltenem Produktvertrag.  
**Erlaubte Dateien:** `package.json`, `package-lock.json`, Markdown-Parsergrenze,
Bibleverse-/Summary-Extensions und ausschließlich upgradebedingte Tests.  
**Aktionen:**

1. Onlineversion erneut prüfen.
2. Wenn weiterhin aktuell, exakt installieren:
   `marked@18.0.13`, `marked-smartypants@1.1.12` und
   `marked-gfm-heading-id@4.1.4`.
3. die Parse-Grenze intern auf eine eigene `Marked`-Instanz umstellen.
4. neue offizielle Extensions in der festgelegten Reihenfolge registrieren.
5. obsolete Optionen entfernen.
6. Bibleverse- und Summary-Tokenizer nur soweit an die aktuelle Extension-API
   anpassen, wie Kompatibilität dies verlangt.
7. Lockfile-Diff auf unbeteiligte Änderungen prüfen.
8. AP-1-Vertragstests zunächst unverändert ausführen.

**Abnahme:**

- `npm ls marked marked-smartypants marked-gfm-heading-id dompurify` löst die
  Zielversionen ohne Peer-Fehler auf;
- `package.json` enthält die drei neuen Zieldependencies exakt, DOMPurify bleibt
  auf dem geprüften Stand;
- `rg -n "sanitize:|smartLists:|smartypants:|tables:" resources/js/components/markdown`
  findet keine obsolete `marked`-Option;
- alle Parser-, Bibleverse-, Summary-, Sanitizing-, Browser- und Visualtests
  bleiben grün;
- Überschriften-IDs und Smart-Punctuation sind sichtbar erhalten;
- Production-Build und Bundlebudget bleiben grün;
- `npm audit --omit=dev` erzeugt keinen neuen Moderate-, High- oder
  Critical-Befund durch die Änderung.

**Rückbau:** Manifest, Lockfile, Parserkonfiguration und upgradebedingte
Extension-Anpassungen gemeinsam auf den grünen AP-6-Checkpoint zurücksetzen.  
**Stop:**

- `npm view marked version` meldet eine Version neuer als 18.0.13;
- eine Zieldependency verlangt ein weiteres direktes Paketupgrade;
- Bibelstellen- oder Summary-Verträge können mit der aktuellen Extension-API
  nicht erhalten werden;
- Outputänderung bricht gespeicherte Fragmentlinks oder sichere Bestandsinhalte;
- Lockfile ändert unbeteiligte direkte Dependencies;
- neue Security- oder Bundlebudget-Regressionsbefunde.

Bei einer neueren `marked`-Version wird nicht 18.0.13 installiert und auch nicht
automatisch die neuere Version gewählt. Release Notes, Node-Anforderung,
Advisories und Extension-Kompatibilität werden zuerst als kurze Vertragsrevision
vorgelegt. Ziel bleibt die dann aktuelle stabile Version, aber nur nach dieser
erneuten Bestätigung.

### AP 8 – Vollständige Abnahme und Dokumentation

**Zweck:** Endzustand nach dem Upgrade beweisen und dokumentieren.  
**Erlaubte Dateien:** betroffene Tests, dieser Vertrag, README und vorhandene
Architektur-/Design-/Qualitätsdokumentation nur bei tatsächlich notwendiger
Aktualisierung.  
**Aktionen:** vollständige Gates ausführen; README und Screenshot-Refresh-Felder
prüfen; Endversionen, Testergebnisse, bewusst nicht ausgeführte Prüfungen und
Restlücken als Umsetzungsnachweis in diesem Dokument ergänzen.  
**Abnahme:** Abschlusscheckliste vollständig; keine veraltete Versionsaussage;
README-Entscheidung begründet; keine echte Nutzerresource in Tests,
Screenshots oder Bericht.  
**Rückbau:** auf AP-6 oder das letzte vollständig grüne Paket zurückgehen; keine
Teilrücknahme nur des Lockfiles.  
**Stop:** eine Pflichtprüfung ist rot oder die visuelle Kontrolle konnte nicht
in der vorgesehenen Browserumgebung durchgeführt werden.

## Verbindliche Prüfungen

Mindestens in dieser Reihenfolge:

```bash
git status --short
npm ls marked marked-smartypants marked-gfm-heading-id dompurify
npm run lint
npm run test:unit
npm run inventory:frontend
npm run build
npm run test:e2e
npm run test:visual
npm audit --omit=dev
npm run docs:check
```

Zusätzlich gezielt, sobald die Testdateien feststehen:

```bash
npx vitest run tests/js/bibleverseRenderer.spec.js
npx vitest run tests/js/markdownParser.spec.js
npx playwright test tests/browser/markdown.spec.js
```

Die tatsächlichen Dateinamen dürfen an vorhandene Testkonventionen angepasst
werden. Der fachliche Umfang der Testmatrix darf dabei nicht reduziert werden.

Der Production-Build allein ist keine Abnahme. Ebenso ersetzen Snapshots keine
semantischen Assertions für Bibelstellen, Summary-Marker und Sanitizing.

## Commit- und Rückbauregeln

- AP 1 bis AP 8 bleiben als nachvollziehbare, jeweils grüne Änderungen
  voneinander trennbar.
- Parsercharakterisierung, Sicherheitsänderung, sichtbares Styling,
  Editor-UX und Dependency-Upgrade werden nicht in einen einzigen unprüfbaren
  Commit vermischt.
- `package.json` und `package-lock.json` werden im Upgrade immer gemeinsam
  geändert und zurückgebaut.
- Visual-Baselines werden nur zusammen mit der sichtbaren Änderung übernommen,
  die sie erklären.
- Tests werden nicht abgeschwächt oder gelöscht, um ein Upgrade grün zu machen.
- Vorhandene unverbundene Arbeitsbaumänderungen bleiben unangetastet.
- Es wird kein `npm audit fix`, insbesondere kein `--force`, ausgeführt.

## Abschlusscheckliste

- [ ] Gesamtentscheidung ausdrücklich freigegeben.
- [ ] Baseline mit `marked` 4.3.0 dokumentiert.
- [ ] Bibleverse- und Summary-Verträge vor Änderungen vollständig getestet.
- [ ] Parse-Grenze eingerichtet und mit `marked` 18 auf eine isolierte
      Parserinstanz ohne Mehrfachregistrierung umgestellt.
- [ ] explizite Sanitizing-Allowlist und URL-Policy umgesetzt.
- [ ] gefährliche Form-, Style-, SVG-/MathML- und Media-Inhalte geblockt.
- [ ] GitHub-inspiriertes Materialpool-Styling vollständig und gekapselt.
- [ ] Inline-Code und Codeblock korrekt getrennt.
- [ ] Tabellen, Bilder, Listen, Task-Listen und Zitate responsiv.
- [ ] Editor/Vorschau auf Desktop und Mobile abgenommen.
- [ ] Tastaturzugang und sichtbarer Fokus abgenommen.
- [ ] `marked` auf die erneut bestätigte aktuelle stabile Version aktualisiert.
- [ ] Smart-Punctuation und Überschriften-IDs erhalten.
- [ ] alle vier Summary-Marker erhalten.
- [ ] Bibelstellen-Popover einschließlich Autoload erhalten.
- [ ] Lint, Unit-, Build-, E2E-, Visual- und Security-Gates grün.
- [ ] README-/Screenshotprüfung durchgeführt und begründet.
- [ ] Umsetzungsnachweis mit Versionen, Prüfungen und Restrisiken ergänzt.

## Abschlussbedingung

Die Modernisierung gilt erst als abgeschlossen, wenn alle nicht vertagten Punkte
der Checkliste erfüllt sind, der finale Lockstand die erneut bestätigte aktuelle
stabile `marked`-Version enthält und dieselbe Bibleverse-/Summary-Testmatrix vor
und nach dem Upgrade grün ist. Ein modernes Aussehen bei ungeprüfter
Parsersemantik oder ein grüner Build ohne Browser-Sanitizing-Test genügt nicht.

## Entscheidung und benötigte Freigabe

### Entscheidung erforderlich: Markdown-Modernisierung als gestufter Vertrag

**Ausgangslage:** Das bestehende Rendering ist funktional, verwendet aber
`marked` 4.3.0, eine zu breite DOMPurify-Standard-Allowlist, unvollständiges
Markdown-CSS und eine auf kleinen Viewports nicht geeignete Editoraufteilung.
Die fachlich wichtigen Bibleverse- und Summary-Erweiterungen besitzen noch keine
vollständige Regressionsabdeckung.

**Empfehlung:** Option A.

| Option | Vorteile | Nachteile/Risiken | Betroffene Bereiche | Rückbauaufwand |
| --- | --- | --- | --- | --- |
| A – gestufte Modernisierung nach diesem Vertrag | Sicherheitsgrenze wird enger; Verhalten wird vor Upgrade belegt; GitHub-inspiriertes Styling bleibt Materialpool-konform; aktuelle `marked`-Version im Endstand | mehrere Arbeitspakete; zwei kleine offizielle `marked`-Extensions werden neue direkte Dependencies; nicht erlaubtes Alt-HTML kann anders erscheinen | Markdown-Komponenten, Texteditor, Sass, npm-Lockstand, Unit-/Browser-/Visualtests | niedrig bis mittel, da jeder Schritt einen grünen Rücksprungpunkt besitzt |
| B – nur Styling, Parser 4.3.0 und breite Policy behalten | kleiner sichtbarer Diff; keine neue Dependency | Sicherheits- und Wartungsschuld bleibt; kein aktuelles `marked`; zentrale Zielsetzung wird verfehlt | hauptsächlich CSS/SFC | niedrig |
| C – `github-markdown-css` direkt importieren und sofort upgraden | schnellste optische Annäherung an GitHub | zweites Farbsystem, größere Kollisionen, schwerer prüfbarer Major-Sprung, höheres Risiko für Bibleverse/Summary und Altinhalte | globales CSS, Parser, Lockfile, Tests | mittel bis hoch |

**Design-/Nutzungswirkung:** Option A erzeugt eine helle, ruhige,
GitHub-inspirierte Dokumentansicht innerhalb des bestehenden Materialpool-
Designsystems. Editor und Vorschau werden mobil gestapelt. Daten, API und
fachliche Textsyntax bleiben unverändert. Unsicheres oder fachlich nicht
freigegebenes Roh-HTML wird künftig auf die ausdrücklich erlaubte sichere
Teilmenge reduziert.

**Benötigte Freigabe:** Option A einschließlich der engen HTML-Allowlist, der
beiden zusätzlichen offiziellen `marked`-Extensions, des beschriebenen
GitHub-inspirierten Designs und der Editor-Responsive-Anpassung bestätigen.

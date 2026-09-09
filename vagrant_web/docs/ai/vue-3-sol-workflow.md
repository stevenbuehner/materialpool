# Arbeitsleitfaden: Vue-Migration mit GPT-5.6 Sol

Dieser Leitfaden konkretisiert den [Migrationsvertrag](vue-3-migration-contract.md), ohne dessen Zielentscheidungen oder Freigaben zu erweitern. Zielmodell ist `gpt-5.6-sol`. Die Hinweise sind projektspezifische Arbeitsregeln, keine Behauptung garantierter Modellleistung. Grundlage ist die am 10. September 2026 abgerufene [offizielle Prompting-Anleitung für GPT-5.6 Sol](https://developers.openai.com/api/docs/guides/prompt-guidance-gpt-5p6): Ergebnis, Evidenz, Grenzen und Abschlusskriterien klar benennen; redundante Prozessanweisungen vermeiden.

## Kopierbarer Startauftrag

Vor Verwendung `<Umfang>` durch die konkret beauftragte Stufe oder „gesamte Migration“ ersetzen:

```text
Setze <Umfang> des docs/ai/vue-3-migration-contract.md um.
Die fünf Optionen A sind freigegeben. Beachte AGENTS.md und den Vertrag.
Ziel: dieselben Funktionen und dasselbe Aussehen; am Ende reines Vue 3,
BootstrapVueNext/Bootstrap 5, Laravel-Vite und Pinia. JavaScript bleibt.

Prüfe zunächst Git-Status, den letzten nachgewiesenen grünen Stand und
vorhandene Stufenberichte. Lies relevante Aufrufstellen und Paketquellen,
bevor du APIs änderst. Verwende kleine, zusammenhängende Arbeitspakete.
Bei Gesamtauftrag nach grünen Gates selbstständig fortfahren.

Verifiziere Paketversionen/Peers und migrationskritische Aussagen anhand
aktueller Primärquellen. Behebe Regressionen im beauftragten Umfang selbst.
Frage nur bei einer notwendigen Änderung freigegebener Produktverträge,
fehlender Befugnis oder einem externen Blocker. Bündele solche Punkte mit
einer Empfehlung und Alternativen; wiederhole keine bereits erteilte Freigabe.

Prüfe zuerst gezielt, dann an Stufenabschlüssen vollständig. Inspiziere bei
UI-Änderungen tatsächliche Browserdarstellung und Nutzerinteraktion.
Dokumentiere Ergebnis, Beleg und verbleibende Grenze. Eine nicht ausgeführte
Prüfung bleibt offen. Aktualisiere das Fortschrittsprotokoll, damit die Arbeit
nach einem Kontextwechsel ohne erneute Gesamtinventur weitergehen kann.
```

## Arbeitspakete und Evidenz

Ein Arbeitspaket hat ein überprüfbares Ergebnis, beispielsweise „Select behält Werte, Slots und Tastatursteuerung auf Vue 3“. Umfang nach fachlicher Abhängigkeit schneiden, nicht nach willkürlicher Zeilenquote. Als Richtwert eine Komponentenfamilie oder ein Store mit direkten Konsumenten und Tests bearbeiten. Framework-/Router-/Compilerwechsel dürfen zusammengehören, wenn sie technisch untrennbar sind.

Vor dem Patch genügen wenige Zeilen: betroffenes Verhalten, relevante Dateien, Risiko und Abnahme. Danach die Implementierung einschließlich der vorgesehenen Verifikation abschließen. Keine vollständige Methodenerzählung oder wiederholte Planung statt Umsetzung.

| Frage | Konkreter Beleg |
| --- | --- |
| Wie funktioniert der Bestand? | Aufrufstelle plus Test oder reproduzierter Browserablauf; reine Textsuche ist nur ein Hinweis |
| Unterstützt das Paket die Zielkombination? | exakte Version, `engines`, `peerDependencies`, öffentliche Exports und einschlägiger Upgradehinweis |
| Ist der Wechsel gelungen? | Exitcodes, gezielter Test und bei sichtbaren Änderungen Referenz/Nachher/Diff samt Interaktionsprüfung |
| Ist ein Fehler neu? | gleicher Fall am Referenzstand und nach dem Patch, gleiche Umgebung |
| Kann es weitergehen? | bestandenes Gate oder klar benannte noch offene Voraussetzung |

Bei einem Fehler die erste ursächliche Meldung isolieren, eine konkrete Hypothese prüfen und dann erneut testen. Nicht wahllos mehrere Dependencies verändern. Nach zwei Versuchen ohne neue Erkenntnis den Ansatz wechseln: kleinere Reproduktion, installierte Paketquelle oder Versionsmatrix. Das ist kein automatischer Abbruch nach zwei Versuchen.

## Projektspezifische Fehlerfallen

| Bereich | Vor Änderung prüfen und anschließend nachweisen |
| --- | --- |
| Router | `resources/js/apps/main/routes.js` und `index.js`: History-Basis `/vue`, Catch-all, Zahlenkonvertierung, optionale Parameter, Querys, Aliase, Navigation und Scrollverhalten |
| Komponentenereignisse | `value`/`input` versus `modelValue`/`update:modelValue`, `.sync`, Slotparameter, `$attrs`, `class/style`, `inheritAttrs` und doppelte Events nach Entfernung von `.native` |
| Reaktivität | Array-/Objekt-Watches, Getter ohne Seiteneffekte, gemeinsam referenzierte Daten, schnelle Suche mit verspäteten Responses; Cleanup von Listenern, Timern und Abos |
| Bootstrap | globaler Compiler-/Runtime-Compatmodus, CSS-Reset, Utility-Klassen, Reboot, Fontmetriken, Modal-Fokus und teleportierte Overlays; Bootstrap 4/5 nicht ungekapselt mischen |
| Store | 16 registrierte Module; Namespaces, Root-Actions, Rückgabewerte, Netzwerkqueue und Mutationsreihenfolge; pro Datensatz eine zuständige schreibende Datenquelle |
| Übersetzung | `localisation.js`, `lang-js-translation.js`, Laravel-Sprachdateien: Schlüssel, Platzhalter, Pluralformen, Fallback, Locale und fehlende Werte; Vue I18n nicht als automatisch formatkompatibel behandeln |
| Markdown | `compiledMarkdown.vue` kompiliert HTML als Template; Bibelstellen-Popovers brauchen einen kontrollierten Komponentenrenderer. `markdownSetup.js`, Renderer, `sanitizeSetup.js`, `my-text-block.vue`, Dialog-HTML: tatsächliche Datenherkunft und Transformationsreihenfolge prüfen; weder DOMPurify-Sicherheit unterstellen noch blind alles auf `v-html` umstellen |
| Drittanbieter | öffentliche Exports statt ungesicherter `src`-/`dist`-/privater Imports; bestehende Abhängigkeit nicht allein wegen ausbleibender einfacher Texttreffer löschen |
| Laravel | beide Blade-Einstiege, fehlender SPA-Mountpunkt auf Auth-Seiten, `window.Laravel`, `window.materialpool`, CSRF und Keepalive |
| Build | `lang:js` muss bei Fehler abbrechen; BibleVerse-JS aus Composer-Paket benötigt vorherige Composer-Installation; kein Node-Polyfill auf Verdacht |
| Dev-Server | tatsächlichen Prozess und Projektpfad prüfen; nur eigenen eindeutig zugeordneten Server neu starten; Ports/HMR-URL und erfolgreiche Kompilierung prüfen |
| Verifikation | Browser explizit per GET auf bekannte Route navigieren; eine frühere POST-Loginseite nicht blind neu laden. Fehlerseiten/Traces können Credentials enthalten und werden nicht unredigiert gespeichert |

## Fortschritt für lange Aufgaben

Bei Implementierungsbeginn `docs/ai/vue-3-progress.md` anlegen und an abgeschlossenen Arbeitspaketen aktualisieren. Es ist ein kurzes Inhaltsverzeichnis zum tatsächlichen Stand; umfangreiche Nachweise gehören in `docs/ai/vue-3-stage-<n>-report.md`. Noch nicht angelegte Dateien sind keine bestehenden Nachweise.

```md
# Vue-Migration – Fortschritt
Stand: <Datum, Branch, HEAD, relevante uncommittete Änderungen>
Auftrag: <freigegebener Umfang>
Referenz: <Git-SHA, Lockfile-Hash, Runtime-/Fixture-/Browserrevision>
Aktuelle Stufe: <Nummer, in Arbeit / technischer Checkpoint / releasefähig>
Abgeschlossen: <Arbeitspaket → Test-/Berichtspfad>
Offene Gates: <konkreter Fall, Ursache, notwendiger Nachweis>
Compat-/Paket-Ausnahmen: <Paket/Flag → Grund, späteste Auflösung>
Nächster Schritt: <konkretes Arbeitspaket und betroffene Dateien>
Rückbau: <letzter grüner Stand, zusammengehörige Commits>
Entscheidungen: <nur neue Beschlüsse; fünf A gelten weiterhin>
```

Nach Kontextwechsel zuerst dieses Protokoll und den Git-Diff prüfen. Bereits verifizierte unveränderte Bereiche nicht erneut vollständig recherchieren. Nicht belegte Zusammenfassungen dagegen gezielt nachprüfen. Eine erledigte Stufe erhält im Bericht die exakten Prüfkommandos, Exitcodes und verbleibenden Einschränkungen; sensible Logs werden nicht eingecheckt.

## Werkzeuge und Modellkonfiguration

Vorhandene funktionierende Einstellungen zunächst beibehalten. Ohne vorhandene Vorgabe ist `medium` ein sinnvoller Startpunkt; dies ist eine Empfehlung, keine automatische Änderung der Task-Einstellungen. Mehr Denkaufwand erst bei belegtem Nutzen auf repräsentativen Migrationsfällen einsetzen. Fehlende Referenzen oder Browsernachweise lassen sich nicht durch eine höhere Einstellung ersetzen.

Verfügbare Skills nur passend zur Aufgabe verwenden und deren Anweisungen lesen. Laravel Boost kann für Laravel-Integration, offizielle Vue-/Paketdokumentation für Frontend-APIs und vorhandene Browserwerkzeuge für Sichtprüfung dienen. Ein nicht verfügbarer Skill ist kein Grund, sichere Arbeit mit vorhandenen Werkzeugen liegen zu lassen. Keine Skills installieren, neue Tasks starten oder Subagenten einsetzen, ohne dass der Auftrag dies einschließt.

Der Abschlussbericht nennt Ergebnis, relevante Dateien, tatsächlich bestandene Prüfungen, offene Grenzen und den nächsten notwendigen Schritt. „Build grün“, „im Browser geprüft“ und „gesamte Migration abgenommen“ sind unterschiedliche Aussagen und müssen mit dem jeweiligen Nachweis übereinstimmen.

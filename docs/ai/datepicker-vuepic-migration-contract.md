# Vertrag: Datepicker auf `@vuepic/vue-datepicker`

## Status und Entscheidung

**Freigegeben am:** 16. September 2026  
**Zielpaket:** `@vuepic/vue-datepicker` 14.0.0, exakt gelockt  
**Zu entfernen:** `@wslyhbb/vuejs-datepicker` 4.3.1 einschließlich seines CSS-Imports und alter paketbezogener Selektoren  
**Umsetzungsstatus:** abgeschlossen; Tastatur und manuelle Texteingabe bleiben wie vereinbart vertagt

Der bisherige Fork ist formal Vue-3-fähig, löst in der Anwendung aber den Laufzeitfehler `this.dateHasChanged is not a function` aus. `@vuepic/vue-datepicker` ist der freigegebene Ersatz. Die Integration bleibt hinter der vorhandenen Materialpool-Wrappergrenze, damit die zwei Verbraucher keinen Paketvertrag kennen müssen.

Dieser Vertrag ist so geschnitten, dass ein GPT-5.6-Sol- oder -Terra-Modell jedes Arbeitspaket ohne zusätzliche Architekturentscheidung umsetzen kann. Bei einem Stop-Kriterium darf es nicht raten, sondern muss den Befund melden.

## Ziel und Grenzen

### Im Umfang

- alle Datepicker-Verwendungen im Repository auf `@vuepic/vue-datepicker` umstellen;
- Datumsauswahl per Maus, deutsches/englisches Anzeigeformat, Disabled-/Required-Zustand und obere Datumsgrenzen erhalten;
- bestehende `model-value`-/`update:model-value`-Verträge der Materialpool-Komponente erhalten;
- altes npm-Paket, alten CSS-Import und ausschließlich dafür vorhandene CSS-Anpassungen entfernen;
- Production-Build sowie Desktop- und Mobile-Browserablauf prüfen;
- Page Errors, insbesondere den gemeldeten `dateHasChanged`-Fehler, ausschließen.

### Bewusst vertagt

- Tastaturnavigation im Kalender;
- manuelle Texteingabe und Parsing über die Tastatur;
- pixelgenaue visuelle Gleichheit außerhalb der bestehenden Formulargeometrie.

Diese Punkte sind keine stillschweigend bestandenen Gates. Die Wrapper-API darf Texteingabe weiterhin einschalten, aber diese Funktion wird in diesem Auftrag weder als kompatibel bestätigt noch automatisiert abgenommen. Eine spätere Abnahme ergänzt gezielte Tests, ohne den Paketvertrag erneut zu öffnen.

### Nicht im Umfang

- Backend-, API-, Datenbank-, Authentifizierungs- oder Berechtigungsänderungen;
- Änderung gespeicherter Datumsformate oder Zeitzonen;
- Neugestaltung der Sidebar oder anderer Formulare;
- Austausch von `moment`/Day.js-/date-fns-Nutzung außerhalb des Datepicker-Wrappers.

## Bestandsinventar und öffentlicher Vertrag

| Ort | Verwendung | Zu erhaltendes Verhalten |
| --- | --- | --- |
| `resources/js/components/sidebar-fields/textEdit.vue` | Material-Erstellungs- und Änderungsdatum | `Date` hinein; Server-Datetime-String über vorhandenen Handler hinaus; Zukunft gesperrt; Disabled, Required, Placeholder und Änderungsmarkierung erhalten |
| `resources/js/components/sidebar-fields/usage/usageListElement.vue` | Usage-Termin | vorhandener Wert hinein; ausgewähltes `Date` über Event hinaus; Werte nach heute + 90 Tage gesperrt; Required, Placeholder und Änderungsmarkierung erhalten |
| `resources/js/components/datepicker/datepicker.vue` | Materialpool-Wrapper | zentrale Locale-, Format-, CSS- und Paketabbildung |
| `resources/js/adapters/datepicker.js` | Paketgrenze | einzige direkte JavaScript-Einbindung des externen Components |
| `tests/browser/compat-app.spec.js` | Browservertrag | deutsches Anzeigeformat, Öffnen, Auswahl/Begrenzung und keine Page Errors auf Desktop/Mobile |

Der Wrapper unterstützt während dieser Migration genau diese Props und Events:

- `modelValue: Date | String | null`
- `disabled: Boolean`
- `disabledDates: { from?: Date, to?: Date }`
- `format: String`, Standard aus `getLocaleDateFormat()` mit date-fns-Tokens
- `language: Locale`, Standard aus der aktiven Materialpool-Locale
- `mondayFirst: Boolean`
- `placeholder: String`
- `required: Boolean`
- `typeable: Boolean`
- `inputClass: String | Array | Object`
- `wrapperClass: String | Array | Object`
- Emit `update:modelValue` mit `Date | null`

Neue Paket-Props dürfen nicht an die Verbraucher durchsickern. Nicht verwendete Alt-Props werden nicht vorsorglich nachgebaut.

## Verbindliche Abbildung auf Vuepic

| Materialpool-Vertrag | Vuepic-Abbildung |
| --- | --- |
| einzelnes Kalenderdatum | Standard-Single-Date-Modus |
| kein Zeitanteil im Widget | `time-config.enableTimePicker=false` |
| unmittelbare Übernahme per Maus | `auto-apply=true` |
| `format` | `formats.input` |
| `language` | `locale` mit date-fns-Localeobjekt |
| Montag als Wochenanfang | `week-start=1` |
| `disabledDates.from` | `max-date`; der Grenztag bleibt wie bisher auswählbar, spätere Tage nicht |
| `disabledDates.to` | `min-date`; der Grenztag bleibt wie bisher auswählbar, frühere Tage nicht |
| `disabled`, `placeholder` | gleichnamige Vuepic-Props |
| `required` | über `input-attrs.required` an das Eingabefeld |
| `inputClass` | über `ui.input`; Objektklassen werden im Wrapper in die dokumentierte Stringliste normalisiert, keine DOM-Manipulation |
| `wrapperClass` | Klasse am Materialpool-Wrapper |
| `typeable` | Vuepic-Text-Input-Konfiguration; in diesem Auftrag nicht funktional abgenommen |

Das neue Basis-CSS wird ausschließlich über `@vuepic/vue-datepicker/dist/main.css` geladen. Alte `@wslyhbb`-Imports und Selektoren mit `.vdp-datepicker...` werden vollständig entfernt. Notwendige neue Anpassungen verwenden vorhandene Bootstrap-/Materialpool-Tokens und bleiben im Wrapper gekapselt.

## Ausführungspakete

### AP 1 – Kontextbereinigung und Vertrag

**Erlaubte Dateien:** `docs/ai/**`, Verweise in vorhandenen Projektdokumenten.  
**Aktionen:** abgeschlossene, redundante Vue-Migrationshistorie entfernen; aktiven Vue-3-Endvertrag verdichten; diesen Vertrag anlegen.  
**Abnahme:** keine Referenz auf entfernte Dokumente; Architektur-, Design-, Qualitäts-, Domänen- und Deploymentverträge bleiben erhalten.  
**Rückbau:** Git-Revert der Dokumentationsänderungen.  
**Stop:** Wenn eine zu löschende Datei einen noch nicht übernommenen aktiven Vertrag enthält.

### AP 2 – Dependency und Adapter

**Erlaubte Dateien:** `package.json`, `package-lock.json`, `resources/js/adapters/datepicker.js`.  
**Aktionen:** Vuepic 14.0.0 installieren und exakt locken; wslyhbb entfernen; Adapter auf den öffentlichen Vuepic-Export umstellen.  
**Abnahme:** `npm ls @vuepic/vue-datepicker @wslyhbb/vuejs-datepicker`; kein invalides Peer-Dependency-Ergebnis; `rg` findet den alten Paketnamen nur noch in historischer Begründung dieses Vertrags.  
**Rückbau:** Manifest, Lockfile und Adapter gemeinsam zurücksetzen.  
**Stop:** Peer-Konflikt mit dem gelockten Vue 3 oder notwendiges Upgrade einer weiteren direkten Dependency.

### AP 3 – Wrapper portieren

**Erlaubte Dateien:** `resources/js/components/datepicker/datepicker.vue`.  
**Aktionen:** `extends` entfernen; Vuepic explizit rendern; Props/Emit aus dem Inventar implementieren; Grenzen, Locale, Format und Formularzustände abbilden; altes CSS entfernen.  
**Abnahme:** beide vorhandenen Verbraucher kompilieren unverändert; keine direkte Vuepic-Einbindung außerhalb Adapter/Wrapper; keine Referenz auf `dateHasChanged`; leere Werte bleiben `null`.  
**Rückbau:** alter Wrapper plus alte Dependency gemeinsam wiederherstellen.  
**Stop:** Wenn Vuepic den benötigten Max-/Min-Grenzvertrag nicht ohne Verbraucher- oder Datenänderung darstellen kann.

### AP 4 – Veraltete CSS-Reste entfernen

**Erlaubte Dateien:** der Wrapper und vorhandene Datepicker-bezogene Sass-Dateien.  
**Aktionen:** alte Paketimports und ausschließlich auf altes DOM zielende Selektoren löschen; nur minimale neue Tokenabbildung behalten.  
**Abnahme:** `rg -n "@wslyhbb|vdp-datepicker" resources package.json package-lock.json` ist leer; Sidebar-Feld behält Breite, Höhe, Disabled- und Changed-Zustand.  
**Rückbau:** zusammen mit AP 3.  
**Stop:** Wenn die Entfernung eine komponentenübergreifende Designänderung verlangt.

### AP 5 – Automatisierte und manuelle Abnahme

**Erlaubte Dateien:** vorhandene Datepicker-nahe Tests und bei Bedarf deterministische Fixtures.  
**Aktionen:** bestehenden Playwright-Vertrag auf stabile, nutzernahe Vuepic-Selektoren umstellen; deutsches Format, sichtbaren Kalender, Mouse-Auswahl, obere Grenze, Disabled-Zustand und fehlende Page Errors prüfen; Desktop und Mobile ausführen.  
**Abnahmebefehle:**

```bash
npm run lint
npm run test:unit
npm run build
npx playwright test tests/browser/compat-app.spec.js --grep "Vue 3 datepicker"
```

Falls die Standardkonfiguration Desktop und Mobile nicht beide enthält, sind die betroffenen Projekte explizit auszuführen.  
**Nicht abnehmen:** Keyboard- und manuelle Text-Input-Szenarien.  
**Rückbau:** Testselektoren nur gemeinsam mit der Implementierung zurücksetzen.  
**Stop:** Page Error, falscher Payload-Typ, nicht eingehaltene Datumsgrenze oder sichtbarer Layoutbruch.

## Abschlusscheckliste

- [x] Entscheidung und Umfang freigegeben.
- [x] bestehende Verbraucher und Verträge inventarisiert.
- [x] redundante Vue-Migrationshistorie aus aktivem `/docs/ai`-Kontext entfernt.
- [x] Vuepic exakt installiert und alter Fork entfernt.
- [x] Adapter und Wrapper portiert.
- [x] altes CSS und alte DOM-Selektoren entfernt.
- [x] Browservertrag aktualisiert.
- [x] Lint, Unit-Tests und Production-Build grün.
- [x] Desktop- und Mobile-Abnahme grün.
- [x] Tastatur-/Texteingabe-Lücke im Abschlussbericht ausgewiesen.

## Umsetzungsnachweis vom 16. September 2026

- `npm ls` löst `@vuepic/vue-datepicker` exakt auf 14.0.0 auf und enthält den alten Fork nicht mehr.
- Repositoryscans finden weder den alten Paketnamen noch `.vdp-datepicker`-Selektoren in Source, Manifest, Lockfile oder Tests.
- `npm run lint`: grün, keine Warnungen.
- `npm run test:unit`: 40 Dateien, 152 Tests grün.
- `npm run build`: grün; bestehende Bundlebudgets bestanden.
- gezielter Playwright-Vertrag: Desktop-WebKit und Mobile-WebKit grün. Geprüft wurden deutsches Format, Öffnen, Mouse-Auswahl, Material-Zukunftsgrenze, Usage-90-Tage-Grenze, Required-/Disabled-/Changed-Zustand, bestehende Screenshots und fehlende Page Errors.
- `npm audit --omit=dev`: ein Low-Fund unter dem bereits zuvor vorhandenen `video.js`-Pfad, keine Moderate-, High- oder Critical-Funde; kein Bezug zur neuen Dependency.
- Nicht geprüft und nicht als bestanden gewertet: Tastaturnavigation und manuelle Texteingabe/Parsing.

## Abschlussbedingung

Die Migration ist abgeschlossen, wenn alle nicht vertagten Punkte der Checkliste erfüllt sind und `@wslyhbb/vuejs-datepicker` weder in Runtime-Code, Manifest noch Lockfile vorkommt. Ein grüner Build allein genügt nicht.

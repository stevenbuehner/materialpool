# Bestehendes UI- und Designsystem

## Grundsatz

Die Oberfläche ist eine funktionale, informationsdichte Vue-3-Anwendung. Ihr Bestandsschutz beruht auf **Bootstrap 5**, **BootstrapVueNext**, lokalen Materialpool-Adaptern, Kompatibilitäts-Sass und wiederverwendbaren Vue-Komponenten. Neue Oberflächen müssen sich einfügen, nicht ein neues Designsystem begründen.

Die abgeschlossene technische Migration auf Vue 3 bewahrt identische Funktionalität und identisches Erscheinungsbild. Referenzaufnahmen, Komponentenersatz, Bootstrap-5-Kompatibilität und visuelle Abnahme folgen weiterhin dem [`Vue-3-Migrationsvertrag`](vue-3-migration-contract.md). Die Migration ist keine Freigabe für ein Redesign.

## Bausteine und Struktur

- Der Einstieg `App.vue` besteht aus Hauptnavigation, globalen Flash-Meldungen und Router-Inhalt. Der Inhaltsbereich hat einen festen oberen Abstand für die Navigation.
- Seiten liegen unter `resources/js/apps/main/pages/`; wiederkehrende Fachbausteine unter `resources/js/components/`.
- Pinia-Stores unter `resources/js/apps/main/stores/` sind die gemeinsame Datenquelle. Komponenten sollen vorhandene Store- und API-Muster verwenden statt parallele Zustände oder direkte, uneinheitliche HTTP-Zugriffe einzuführen.
- Vue-fähige UI-Pakete werden nicht direkt in Fachkomponenten importiert, wenn ein Materialpool-Adapter besteht. Die Adapter bewahren Props, Emits, Slots, Fokus und das historische Erscheinungsbild und sind eine dauerhafte Anwendungsgrenze, kein automatisch zu löschender Übergangscode.
- Der Router nutzt HTML5-History mit Basis `/vue`; Pfad- und Alias-Konventionen respektieren.

## Gestaltungstoken

Die zentralen Sass-Regeln liegen in `resources/sass/theme.scss` und `resources/sass/main.scss`:

- Bootstrap-Farbpalette und deren jeweils leicht abgedunkelte Hover-Variante verwenden.
- Raleway ist die globale Schriftfamilie.
- Tags: grauer Hintergrund, weiße Schrift; Relevanz grün, beim Ziehen orange.
- Nicht gespeicherte Sidebar-Werte sind hellrot hinterlegt und stärker umrandet. Diese Statussemantik beibehalten.
- Eingaben und Sidebar-Felder verwenden die bestehenden Abstände, Farben und Aktiv-/Deaktiviert-Zustände.

Keine eigenen Hex-Farben, Schatten, Abstände oder UI-Bibliotheken einführen, wenn ein vorhandenes Bootstrap-/Sass-Token oder eine bestehende Komponente passt.

## Quellcode-Eigentum von Komponenten

- Komponentenbezogenes Markup, Verhalten und Styling werden gemeinsam in der
  jeweiligen Vue-SFC gepflegt. Ein lokaler Style-Block verwendet grundsätzlich
  `scoped`, sofern er nicht bewusst teleportierte Inhalte oder das DOM einer
  gekapselten Drittkomponente gestaltet.
- Globale Bootstrap-, Kompatibilitäts- und Designsystem-Regeln bleiben in
  `resources/sass/main.scss`; wiederverwendbare Sass-Tokens kommen ausschließlich
  aus `resources/sass/theme.scss`. Framework-CSS wird nicht pro Komponente neu
  kompiliert.
- Drittanbieter-CSS bleibt beim zuständigen Materialpool-Adapter importiert.
  Fremdcode wird nicht zur scheinbaren Ein-Datei-Struktur in eine Fachkomponente
  kopiert.
- Gemeinsam genutzte oder fachliche Logik bleibt in Pinia-Stores, Composables
  und getesteten Helpern. Sie wird nicht zur lokalen Bündelung in mehreren SFCs
  dupliziert.
- Der Production-Build darf CSS für Caching und Lazy Loading in separate Assets
  extrahieren. Die Zusammengehörigkeit wird im Quellcode hergestellt, nicht
  durch ein erzwungenes gemeinsames JavaScript-/CSS-Ausgabeartefakt.

## Interaktion und Zugänglichkeit

- Bestehende BootstrapVueNext-/Materialpool-Controls, Dialoge, Spinner, Flash-Meldungen und Ladezustände wiederverwenden.
- Jede neue Interaktion benötigt verständliche Erfolg-/Fehlermeldungen, Tastaturbedienbarkeit, sichtbaren Fokus, ausreichenden Kontrast und einen Lade-/Deaktiviert-Zustand.
- Responsive Verhalten an kleinen und großen Viewports mit den vorhandenen Bootstrap-Breakpoints prüfen.
- Icons aus dem vorhandenen Bestand nutzen und deren Lizenzdateien respektieren.
- Anzeige- und Fehlermeldetexte über `resources/lang/` führen, insbesondere `resources/lang/de/pool.php`; neue Texte nicht nur in Komponenten hardcodieren.

## Entscheidungspflicht bei UI

Vor einer sichtbaren Veränderung an Layout, Navigation, Farbe, Typografie, Seitenstruktur, neuen Komponentenmustern oder Interaktionsabläufen:

1. Das bestehende Muster und die betroffenen Nutzerabläufe benennen.
2. Eine Empfehlung und mindestens eine Alternative mit Vor-/Nachteilen anbieten.
3. Erst nach Freigabe umsetzen.

# Anwendung

[Zur Übersicht](../README.md)

## Inhalt

- [Anmeldung und Navigation](#anmeldung-und-navigation)
- [Begriffe](#begriffe) und [Suchen und Finden](#suchen-und-finden)
- [Materialien verwalten](#materialien-verwalten) und [Resources anlegen](#resources-anlegen-und-bearbeiten)
- [Bibel lesen](#bibel-lesen)
- [Verwaltung und administrative Funktionen](#verwaltung-und-administrative-funktionen)

## Anmeldung und Navigation

Materialpool ist geschützt. Nach erfolgreicher Anmeldung öffnet `/vue` die Startseite. Oben stehen – abhängig von den zugewiesenen Berechtigungen – Upload, neue Textressource, Bibel, Suchmaske, Schnellsuche und das Benutzerkonto zur Verfügung. Das Menü **Bearbeiten** erscheint für Global-Admins und Benutzer mit passenden Verwaltungsrechten. Auf kleinen Bildschirmen wird die Navigation über den Menüschalter geöffnet.

<!-- README-SCREENSHOT
id: login
route: /login
state: leeres Anmeldeformular ohne Debugleiste
role: nicht angemeldet
viewport: desktop-webkit (1440x900)
fixture: synthetisch, keine Datenbank
source: tests/browser/login.spec.js#@visual-login-page-baseline
refresh: Blade-Layout, Loginformular, Auth-Texte oder globale Styles geändert
-->
![Anmeldeseite mit Feldern für E-Mail-Adresse und Passwort](readme/screenshots/login-desktop.png)

*Anmeldung: Der Zugang zur Anwendung erfolgt mit dem eingerichteten Benutzerkonto.*

<!-- README-SCREENSHOT
id: start-navigation
route: /vue/
state: Startseite mit sichtbarer Desktop-Hauptnavigation
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-application-mounts-with-synthetic-bootstrap-data
refresh: Hauptnavigation, Startseite, Rollenanzeige oder globale Styles geändert
-->
![Materialpool-Startseite mit Hauptnavigation und Schnellzugriffen](readme/screenshots/start-navigation-desktop.png)

*Startseite: Die Hauptfunktionen sind sowohl in der oberen Navigation als auch als Schnellzugriffe erreichbar.*

<!-- README-SCREENSHOT
id: start-navigation-mobile
route: /vue/
state: Startseite mit ausgeklappter mobiler Navigation
role: Benutzer
viewport: mobile-webkit (390x844)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-application-mounts-with-synthetic-bootstrap-data
refresh: Mobile Navigation, Breakpoints, Startseite oder globale Styles geändert
-->
![Mobile Materialpool-Startseite mit ausgeklappter Navigation](readme/screenshots/start-navigation-mobile.png)

*Mobil: Der Menüschalter blendet Navigation, Suche und Benutzerkonto ein.*

Im Benutzermenü befinden sich für Global-Admins **Benutzerverwaltung** und **Abmelden**. **Einstellungen** ist derzeit sichtbar, aber deaktiviert. Der System-Shutdown erscheint nur mit dem Recht `system.shutdown`; Global-Admins besitzen dieses Recht stets.

## Begriffe

| Begriff | Bedeutung |
| --- | --- |
| **Material** | Inhaltlicher Eintrag mit Titel, Beschreibung, Bewertung und Zuordnungen. |
| **Resource** | Wiederverwendbarer Träger eines Inhalts: Datei, Bild, Audio, Video, PDF, URL, Text oder Buch. |
| **Schlagwort** | Hierarchisch organisiertes Tag; Typen sind Thema, Person, Ort und Sprache. |
| **Bibelstelle** | Vers oder Versbereich, der einem Material mit einer Relevanz zugeordnet wird. |
| **Bundle** | Importierbarer externer Bestand aus Materialien und Resources. |
| **Nutzung** | Dokumentierter Einsatz eines Materials mit Zeitpunkt, Ort, Anlass und Benutzer. |

Ein Material kann mehrere Resources enthalten. Dieselbe Resource kann mehreren Materialien zugeordnet sein; bei begrenzbaren Medien kann jede Zuordnung zusätzlich Seiten oder Zeitbereiche festlegen.

## Suchen und Finden

Die **Schnellsuche** in der Navigation sucht direkt nach einem eingegebenen Begriff. Die **Suchmaske** bietet strukturierte Suchzeilen:

1. Begriff eingeben und einen Vorschlag auswählen.
2. Mehrere Werte innerhalb einer Zeile kombinieren.
3. Mit **+** eine weitere UND-Zeile ergänzen oder mit **−** entfernen.
4. Die Optimierungsfunktion kann verwandte Schlagwörter beziehungsweise Bibelstellen vorschlagen.
5. Ergebnisse öffnen oder mit der Pagination durch weitere Seiten wechseln.

<!-- README-SCREENSHOT
id: search-results
route: /vue/search/1*Jugendarbeit
state: strukturierte Suche mit zwei Ergebnissen
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/readme-screenshots.spec.js#search-results
refresh: Suchmaske, Ergebnisliste, Pagination oder Suchtexte geändert
-->
![Suchmaske mit Suchbegriff, zwei Materialergebnissen und Seitennavigation](readme/screenshots/search-results-desktop.png)

*Suchergebnisse: Karten zeigen Titel, Beschreibung, Resource-Typen, Schlagwörter und Bibelstellen.*

## Materialien verwalten

Die Materialliste zeigt vorhandene Materialien seitenweise. Ein Klick auf eine Karte öffnet das Materialdetail. Links erscheinen die zugeordneten Resources, rechts die Bearbeitungsseitenleiste mit den Tabs **Material**, **Zuordnungen** und **Meta**. Bei einem noch ressourcenlosen Material stehen berechtigten Benutzern die Upload-, Zuordnungs- und Textressourcen-Aktionen zusätzlich direkt im Inhaltsbereich zur Verfügung; ohne Änderungsrecht ist der Tab **Zuordnungen** ausgeblendet.

<!-- README-SCREENSHOT
id: material-detail
route: /vue/material/1
state: Materialdetail ohne Resource mit Bearbeitungsseitenleiste
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-3-datepicker-keeps-the-german-input-and-calendar-interaction
refresh: Materialdetail, Sidebar, Resource-Karte, Berechtigungen oder Materialaktionen geändert
-->
![Materialdetail mit leerem Resource-Bereich und Feldern für Titel, Beschreibung und Zuordnungen](readme/screenshots/material-detail-desktop.png)

*Materialdetail: Der Inhaltsbereich und die direkt bearbeitbaren Metadaten stehen nebeneinander; hier ist noch keine Resource zugeordnet.*

Im Tab **Material** lassen sich Titel, Datum, Autor, Beschreibung, Bibelstellen, Themen, Personen, Orte, Sprachen und Bewertung pflegen. Änderungen werden über die vorhandenen Feldaktionen gespeichert; ungespeicherte Werte sind farblich markiert.

Welche Aktionen verfügbar sind, wird serverseitig aus den Gruppenrechten ermittelt. Erstellen, Metadatenpflege, Resource-Zuordnungen und Löschen sind getrennte Rechte; `*-own` gilt nur für selbst erstellte Datensätze, `*-all` zusätzlich für fremde. Ausgeblendete oder deaktivierte UI-Aktionen ersetzen nie die serverseitige Prüfung.

Im Tab **Zuordnungen** werden unter anderem Nutzungen und Resource-Beziehungen verwaltet. Relevanzen von Schlagwörtern und Bibelstellen beeinflussen ihre Gewichtung. Bei einer Resource-Zuordnung kann **Resource zuordnen** eine vorhandene Resource suchen und verbinden. Begrenzbare PDFs, Videos und Audios können pro Material auf Seiten oder Zeiträume eingeschränkt werden.

Im Tab **Meta** stehen technische Informationen und Herkunftsdaten. Die obere Aktionsleiste bietet – abhängig vom Zustand – Download/Export, Duplizieren und Löschen.

> [!WARNING]
> Das Lösen einer Resource entfernt die Zuordnung, nicht zwingend die Resource selbst. Löschen kann Beziehungen, Foreign-IDs, lokale Dateien, Caches und Bereinigungsjobs betreffen. Bestätigungsdialoge und sichtbare Warnungen sorgfältig lesen.

## Resources anlegen und bearbeiten

Über das Upload-Symbol können eine oder mehrere Dateien ausgewählt oder in die Uploadfläche gezogen werden. Die Option **automatisch Material erzeugen** erstellt nach dem Upload zu jeder neuen Resource ein Material.

<!-- README-SCREENSHOT
id: resource-upload
route: /vue/resource/create
state: leere Uploadfläche mit aktivierter Materialoption
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#vue-3-uploader-keeps-multipart-success-and-error-handling
refresh: Uploader, Uploadoptionen, Validierung oder Texte geändert
-->
![Seite zum Hochladen neuer Resources mit Dropzone und Materialoption](readme/screenshots/resource-upload-desktop.png)

*Upload: Dateien können gewählt oder per Drag-and-drop hinzugefügt werden.*

Über **Text erstellen** entsteht eine Textresource. Metadaten werden separat eingegeben; der Textbereich zeigt eine Markdown-Vorschau. Erst valide Eingaben können gespeichert werden.

Das Resource-Detail besitzt drei Tabs:

- **Vorschau** zeigt den Inhalt passend zum Resource-Typ.
- **Materialien** zeigt alle Zuordnungen, Limitierungen und Aktionen zum Öffnen, Kopieren oder Entfernen.
- **MetaInfo** enthält ID, Ersteller, Notizen, Zeitstempel, Hash, Dateigröße, Web-URL, Öffentlichkeit und Dateiinformationen.

<!-- README-SCREENSHOT
id: resource-detail
route: /vue/resource/42
state: Resource-Detail im Tab MetaInfo
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#resource-detail-cards-and-multi-page-pagination-keep-their-application-contracts
refresh: Resource-Detail, Tabs, MetaInfo oder Resource-Aktionen geändert
-->
![Resource-Detail mit geöffnetem MetaInfo-Tab und technischen Angaben](readme/screenshots/resource-detail-desktop.png)

*Resource-Detail: Metadaten und Sichtbarkeit können unabhängig von den Materialzuordnungen gepflegt werden.*

Bei mehrseitigen PDFs öffnet **Seiten zuordnen** eine Übersicht. Seiten auswählen und anschließend entweder ein neues Material erzeugen oder die Auswahl einem vorhandenen Material hinzufügen. `Strg`+`N` löst ebenfalls **Neu** aus, wenn Seiten markiert sind.

<!-- README-SCREENSHOT
id: pdf-page-assignment
route: /vue/resource/42/assign
state: PDF-Seitenübersicht mit markierten Seiten
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#assign-app-keeps-page-selection-attachment-and-nested-image-dialogs
refresh: AssignApp, Seitenkarten, Auswahlaktionen oder PDF-Vorschau geändert
-->
![PDF-Seitenübersicht mit Seitenauswahl und Aktionen für Materialzuordnungen](readme/screenshots/pdf-page-assignment-desktop.png)

*PDF-Zuordnung: Ausgewählte Seiten können ein neues Material bilden oder einem vorhandenen Material hinzugefügt werden.*

## Bibel lesen

Unter **Bibel** wird eine Stelle wie `Johannes 3,16` eingegeben. Die Auswahl rechts wechselt zwischen den installierten Übersetzungen. Die Route speichert den ausgewählten Versbereich, sodass die Ansicht direkt verlinkt oder neu geladen werden kann. Verknüpfte Bibelstellen sind außerdem in Suche und Materialdetail interaktiv.

<!-- README-SCREENSHOT
id: bible-reader
route: /vue/readbible/1001001-1001002
state: zwei Verse aus 1. Mose mit ausgewählter deutscher Übersetzung
role: Benutzer
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#bible-reader-loads-and-selects-cached-translations
refresh: Bibelleser, Übersetzungsauswahl, Versdarstellung oder Bibelsuche geändert
-->
![Bibelleser mit Eingabefeld, Übersetzungsauswahl und zwei angezeigten Versen](readme/screenshots/bible-reader-desktop.png)

*Bibelleser: Versbereich und Übersetzung können unabhängig voneinander gewählt werden.*

## Verwaltung und administrative Funktionen

> [!IMPORTANT]
> Die Benutzer- und Gruppenverwaltung ist ausschließlich für Global-Admins sichtbar. Schlagwort-, Bundle- und Shutdown-Funktionen können über eigene Gruppenrechte freigegeben werden. Die serverseitige Autorisierung bleibt maßgeblich; ein verborgenes Menü ist kein Sicherheitsmechanismus.

### Benutzer und Gruppen

Global-Admins öffnen unter **Benutzerkonto → Benutzerverwaltung** die Seite `/vue/admin/users`. Dort können sie Benutzer suchen, nach Status filtern, einladen, Gruppen zuweisen, sperren oder wieder aktivieren sowie Einladungen erneut senden. Neue Konten bleiben bis zum erfolgreichen Festlegen eines Passworts im Status **Eingeladen**; der Einladungslink ist 60 Minuten gültig. Gesperrte Konten können sich weder über Web noch Passport anmelden, und bestehende Access-/Refresh-Tokens werden beim Sperren widerrufen.

Im Bereich **Gruppen** werden ausschließlich die fest definierten Systemrechte zugeordnet. Direkte Benutzerrechte sind nicht vorgesehen. Die Gruppe **Standardnutzer** schützt den bisherigen Arbeitsablauf für eigene Materialien und Resources. Eine noch verwendete Gruppe kann nicht gelöscht werden; der letzte aktive Global-Admin kann weder gesperrt noch herabgestuft werden.

### Schlagwörter

Der Schlagwortbaum kann gefiltert, geöffnet und hierarchisch bearbeitet werden. Verschieben, Zusammenführen oder Löschen kann ganze Teilbäume und Materialzuordnungen betreffen.

<!-- README-SCREENSHOT
id: keyword-management
route: /vue/keyword
state: gefilterter Schlagwortbaum
role: Administrator
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#keyword-tree-loads-filters-and-force-refreshes-through-pinia
refresh: KeywordList, Baumdarstellung, Filter oder Adminnavigation geändert
-->
![Administration des hierarchischen Schlagwortbaums mit Suchfeld](readme/screenshots/keyword-management-desktop.png)

*Schlagwortverwaltung: Der Baum strukturiert Themen, Personen, Orte und Sprachen.*

### Bundles

Die Bundleübersicht zeigt installierte Version, verfügbare Version, Inhalt und Fortschritt. **Installieren**, **Aktualisieren** und **Deinstallieren** erzeugen eine Folge von Jobs. Den Browser während eines laufenden Vorgangs nicht unnötig schließen und einen Abbruch nur bei geklärter Auswirkung anfordern.

<!-- README-SCREENSHOT
id: bundle-management
route: /vue/bundle
state: installiertes Bundle mit verfügbarem Update
role: Administrator
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/compat-app.spec.js#bundle-overview-loads-and-completes-an-update-through-pinia
refresh: BundleList, Bundlekarte, Fortschritt oder Adminnavigation geändert
-->
![Bundleverwaltung mit installierter Version, verfügbarem Update und Verwaltungsaktionen](readme/screenshots/bundle-management-desktop.png)

*Bundleverwaltung: Versionsstand, Umfang und verfügbare Aktionen stehen direkt auf der Bundlekarte.*

### Weitere Werkzeuge

- **Verwaiste Resources** zeigt Resources ohne Materialzuordnung; vor dem Löschen prüfen, ob die fehlende Zuordnung beabsichtigt ist.
- **Neueste Resources** hilft bei der Kontrolle kürzlich angelegter Inhalte.
- **Resource ersetzen** überführt Beziehungen von einer Resource auf eine andere und ist eine weitreichende Datenoperation.
- **System herunterfahren** zeigt einen Bestätigungsdialog und darf nur im vorgesehenen lokalen Betriebsmodell verwendet werden. Die Aktion kann den Host betreffen.

## Lade-, Leer- und Fehlerzustände

- Spinner oder Informationsmeldungen zeigen einen laufenden Abruf beziehungsweise Import.
- Leere Listen bedeuten nicht automatisch einen Fehler; Filter, Berechtigung und Pagination prüfen.
- Rote Alerts enthalten Validierungs- oder Serverfehler. Eingaben erhalten und Meldung lesen, bevor die Seite neu geladen wird.
- Eine fehlende Aktion kann an Rolle, Resource-Typ, Materialstatus oder laufendem Hintergrundprozess liegen.
- Bei einem 401/403 erneut anmelden beziehungsweise Berechtigung klären; UI-Ausblendung nicht umgehen.

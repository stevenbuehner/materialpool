# Installationsvertrag für Bibeltexte und Cross References

**Stand:** 29. September 2026

**Status:** Verbindlicher fachlicher und technischer Vertrag; Importpfad implementiert, vollständige Katalog- und Proxmox-Abnahme noch offen.

**Zielruntime:** Laravel 13, PHP 8.4 und MariaDB im Proxmox-LXC. GitHub Actions bereitet ausschließlich Cross References für das Release vor.

## 1. Ziel und Geltungsbereich

Der einzige produktive Artisan-Befehl `bible:import` bietet bei der Erstinstallation eine Auswahl von Bibelübersetzungen aus `scrollmapper/bible_databases` und lädt nur ausgewählte Texte **direkt auf dem Zielsystem** herunter. Bei Updates aktualisiert er ohne neue Auswahl nur bereits installierte Übersetzungen. Cross References werden weiterhin im GitHub-Release aus OpenBible-Daten vorbereitet und vor Ort nur aus dem Release-Paket importiert. Jede Ausführung endet mit einer Statusübersicht. Unveränderter Inhalt löst keinen Datenbankimport aus.

Dieser Vertrag gilt für Quellenkatalog, Downloads, Release-Paket, CLI, Datenbankstatus, Erstinstallation, Update, Bestandseinführung, Fehlerbehandlung und Abnahme. Maßgeblich bleiben [Architektur](architecture.md), [Domänen-Invarianten](domain-invariants.md), [Qualitätssicherung](quality-gates.md), [Produktionsvertrag](production-deployment-contract.md) und [Proxmox-Testanleitung](../../deployment/docs/testing.md). Die Auswahl und der Import verleihen keine Rechte zur Nutzung oder Weitergabe eines Bibeltextes.

## 2. Verifizierter Bestand und Grenzen

- `import:zefaniabible {xmlfile}` ruft `ZefaniaImportService::import()` auf. Dieser aktualisiert Metadaten anhand der XML-`identifier`, löscht die Verse jedes enthaltenen Buchs und schreibt sie einzeln neu. `update()` und `fileAlreadyInstalled()` im Service sind leer. Der Import ist derzeit weder atomar noch versionsgesteuert.
- Der bisherige `ImportBibleContent`-Seeder und die versionierten XML-Dateien wurden mit der Umsetzung aus dem aktuellen Git-Stand entfernt. Der manuelle Zefania-Befehl akzeptiert weiterhin eine ausdrücklich angegebene XML-Datei.
- Der bisherige `ImportBibleverseCrossReferences`-Seeder und der historische SQL-Dump wurden mit der Umsetzung entfernt. Der allgemeine `DatabaseSeeder` importiert keine Querverweise.
- Die Migrationen legen `bibles`, `bible_contents` und `bibleverses_cross_ref` an. Die Cross-Reference-Migration importiert keine Daten.
- `deployment/release/package.sh` nimmt die vorbereitete OpenBible-Payload unter `database/bible-data/` in das Archiv auf, aber keine Übersetzungstexte. `deployment/release/update.sh` führt nach Migrationen den Bibeldatenimport aus. Der produktive `db:seed`-Aufruf bleibt verboten.
- Die zuvor versionierten XML-Dateien enthielten `ELB1905` und `SCH1951` mit je 66 Büchern sowie `LEO_NA28` und `VLX3` mit je 27 Büchern. Diese lokalen Zefania-Kennungen sind **keine** bestätigten Scrollmapper-IDs. Die Dateien wurden aus dem aktuellen Git-Stand entfernt und bleiben in der Git-Historie vorhanden; bestehende Datenbankeinträge bleiben als nicht verwalteter Bestand erhalten.

**Begriffsgrenze:** Zefania ist das Format der bisherigen lokalen Übersetzungsdateien und des vorhandenen Importers. Scrollmapper stellt Übersetzungen in eigenen Exportformaten bereit und benötigt einen neuen Adapter. OpenBible bleibt direkte Quelle für Cross References; der historische Scrollmapper/OpenBible-SQL-Dump ist weder künftige Quelle noch Formatvertrag. SourceForge/Zefania ist nur als späterer, getrennter Anbieter vorgesehen.

## 3. Unveränderliche Verhaltensregeln

1. Migrationen ändern ausschließlich das Schema. Fachliche Daten werden nur durch `bible:import` eingespielt.
2. Ein Update fragt keine neue Auswahl ab und installiert keine neue Übersetzung. Nicht ausgewählte und nicht katalogisierte Bestandsdaten bleiben unberührt.
3. Das Weglassen einer Übersetzung oder ein „Nein“ in einer interaktiven Frage deinstalliert nichts. Ein Löschbefehl gehört nicht zu diesem Vertrag.
4. Alle angeforderten Downloads und Release-Daten werden vor der ersten Mutation vollständig geprüft. Ein abgebrochener Import darf keinen teilweise ersetzten Datensatz sichtbar machen oder als erfolgreich markieren.
5. Die Quelle eines Datensatzes, die stabile ID, die konkrete Quellrevision, die normalisierte Inhaltsversion und der zuletzt erfolgreich installierte Stand müssen nachvollziehbar sein. Zeitstempel, HTTP-ETag, Git-Commit und Software-Version allein gelten nicht als Inhaltsversion.
6. Eine Auswahl gilt erst nach erfolgreichem Import als installiert. Tabellenexistenz, eine `bibles`-Metadatenzeile ohne Verse und ein fehlgeschlagener früherer Lauf gelten nicht als Installation.
7. Für Übersetzungen sind nur Daten aus dem festgelegten Scrollmapper-Repository zulässig, für Cross References nur vorbereitete Dateien innerhalb des Release-Archivs. Nutzerkonten, Material-Bibelstellen-Beziehungen und sonstige Fachinhalte werden durch Bibeldatenimporte nicht verändert.
8. Ein Code-Rollback importiert niemals automatisch ältere Bibeldaten. Für einen Daten-Rollback ist der verifizierte Datenbank-Restore maßgeblich.

## 4. Quellenkatalog, Rechte und Datenwege

### 4.1 Scrollmapper als Hauptquelle für Übersetzungen

Die Anwendung liest den Katalog aus [`scrollmapper/bible_databases`](https://github.com/scrollmapper/bible_databases) und verwendet **eine feste Quellrevision pro Befehlslauf**: Zuerst wird `master` zu einem Commit-SHA aufgelöst, dann werden Katalog und ausgewählte Dateien ausschließlich von diesem Commit gelesen. Ein Branch-Wechsel während des Laufs kann keine Daten mischen. Der Scrollmapper-Adapter verwendet genau ein definiertes, getestetes Exportformat aus `formats/`; weder README-Text noch Dateinamen allein gelten als verlässliche Metadaten. Die Quelle für Titel, Sprache, Ausgabe und Rechtehinweis sowie die Zuordnung zu Exportdateien werden mit echten Repository-Fixtures nachgewiesen. Ein geändertes Upstream-Schema lässt den Import fehlschlagen, bis der Adapter angepasst wurde.

Die stabile, nicht versionierte ID lautet `scrollmapper:<translation-code>`, zum Beispiel `scrollmapper:GerElb1905`. Diese ID bezeichnet eine konkrete Ausgabe, keinen Dateinamen und keinen Commit. Identische Anzeigenamen verschiedener Anbieter oder Ausgaben werden nicht zusammengeführt. Für den Menüeintrag werden Titel, Sprache, Umfang (Vollbibel/Teilbestand), Quelladresse und vorhandene Rechteangabe angezeigt. Ein fehlender Rechtehinweis heißt sichtbar „nicht angegeben“, niemals „gemeinfrei“. Die Repository-Lizenz ersetzt keine Prüfung der Rechte am einzelnen Text.

Der Katalog zeigt alle am fixierten Commit entdeckten Übersetzungen. Das Ziel „alle Übersetzungen auswählbar“ gilt als Abnahmegate für den Scrollmapper-Adapter: Teilbibeln, abweichende Buchbestände, Verszählungen, Sprachen und Zeichensätze müssen vollständig und eindeutig auf das Materialpool-Modell abbildbar sein. Eine Ausgabe darf weder stillschweigend gekürzt noch mit falschen Bibelstellen installiert werden. Ist eine Ausgabe technisch nicht abbildbar, ist die Funktion noch nicht vollständig abgenommen; bis zur Modell- oder Adaptererweiterung wird sie mit konkretem Grund als nicht installierbar angezeigt und nicht nur teilweise importiert.

**Aktueller Nachweis:** Der Live-Katalog liefert 140 Ausgaben und 140 CSV-Exporte. Ein textfreier Prüfsnapshot für den fixierten Commit enthält Buch- und Verszahlen aller 140 Ausgaben; er zeigt 25 Ausgaben mit zusätzlichen, im 66-Bücher-Modell nicht darstellbaren Büchern und eine Ausgabe ohne Textverse. Diese 26 Ausgaben werden mit konkretem Grund nicht zur Auswahl angeboten. Bei einer neueren Quellrevision ohne passenden Snapshot wird der Umfang ausdrücklich erst beim Import bestimmt und jede gewählte Datei vollständig geprüft. Der Parser wurde mit einer deutschen Vollbibel und einem nicht deutschen Teilbestand geprüft. Scrollmapper-CSV enthält bei Teilbeständen eine vollständige Versmatrix mit leeren Platzhaltern. Der Import zählt diese Platzhalter ausdrücklich und übernimmt nur Verse mit Text. Die technische Freigabe aller 140 Ausgaben verlangt eine spätere Erweiterung des Bibelstellenmodells und ist weiterhin offen.

Eine versionierte Anwendungskonfiguration enthält eine **geordnete Liste stabiler IDs als Vorschläge**, beispielsweise `scrollmapper:GerElb1905` und `scrollmapper:GerSch`. Diese stehen in der durchsuchbaren Auswahl zuerst und sind als Vorschlag erkennbar. Sie sind nicht automatisch vorausgewählt oder installiert. Entfernte oder unbekannte Vorschlags-IDs erzeugen eine klar protokollierte Katalogwarnung, aber keinen Ersatz durch eine ähnlich benannte Ausgabe. Danach folgen die übrigen Einträge nach Sprache und Titel. Bereits installierte Übersetzungen sind unabhängig von dieser Sortierung eindeutig markiert.

### 4.2 Rechtehinweis ohne Zustimmungsabfrage

`bible:import` gibt **vor Katalogauswahl beziehungsweise nicht interaktiv vor jeder Datenaktion** diesen sinngemäßen, übersetzbaren Rechtehinweis aus:

> Bibelübersetzungen werden direkt aus `scrollmapper/bible_databases` auf diesem System gespeichert. Nutzungsrechte können je Ausgabe unterschiedlich sein. Prüfen Sie den angegebenen Rechtehinweis und die Quelle vor Nutzung oder Weitergabe der Texte. Die Verfügbarkeit zum Download ist kein allgemeiner Lizenznachweis.

Quelllink und vorhandene Rechteangabe werden bei der Auswahl und im Status auffindbar gemacht. Es gibt keine Checkbox, keinen zusätzlichen Prompt, keine automatische Annahme einer Lizenz und keine Zustimmungsprotokollierung. Der Hinweis selbst ist kein Rechtsnachweis.

### 4.3 Download und Versionierung der Übersetzungen

Im Zielzustand sind Übersetzungen **weder im aktuellen Materialpool-Git-Stand noch im öffentlichen Release-Artefakt** enthalten; bereits vorhandene Git-Historie wird dadurch nicht verändert. `bible:import` lädt nur explizit ausgewählte beziehungsweise bereits installierte Übersetzungen über HTTPS von erlaubten GitHub-Adressen; beliebige URLs aus Metadaten, Redirects auf fremde Hosts, ausführbare Repository-Skripte und vollständige Git-Checkouts sind ausgeschlossen. Ein Abruf hat Timeout, Größenlimit, begrenzte Wiederholungen und einen isolierten temporären Arbeitsbereich. Für Git LFS-Pointer oder unerwartete Exportdateien gibt es keinen stillen Fallback. Temporäre Rohdaten werden nach Erfolg oder Fehler entfernt. Persistiert werden nur Bibeltexte, notwendige Attribution und der Versions-/Provenienzstatus.

Zuerst werden Commit und Dateimetadaten geprüft. Ein unveränderter Datei-Blob bei gleicher Adapter-/Normalisierungsversion kann ohne erneuten Textdownload übersprungen werden. Bei einem geänderten Blob wird die Datei geladen und vollständig normalisiert; **nur der SHA-256-Hash der normalisierten Verse samt Normalisierungsversion** entscheidet über einen Datenbankimport. Ein Commit-Wechsel, ein neuer Dateiname, ein HTTP-ETag oder ein geändertes Rohformat allein erzwingen keinen Neuimport, wenn der fachliche Inhalt gleich bleibt. Der zuletzt erfolgreich angewendete Inhaltsstand und die zuletzt erfolgreich geprüfte Quellrevision werden getrennt gespeichert. Ist der normalisierte Inhalt gleich, darf nur der Beobachtungsstand aktualisiert werden; Verse bleiben unangetastet und derselbe Blob muss beim nächsten Lauf nicht erneut heruntergeladen werden. Die API-Abfragezahl und GitHubs Limits sind zu berücksichtigen; ein Limitfehler ist ein normaler Abruffehler und wird nicht durch heimliche andere Quellen umgangen.

### 4.4 OpenBible-Cross-References im Release

`bible:prepare:cross-references` läuft im GitHub-Release-Build, ruft **OpenBible direkt** ab und erzeugt ein deterministisches Payload samt Manifest. Die stabile ID ist `openbible:cross-references`. Der Adapter bildet Buch, Kapitel, Versbereich und Relevanz eindeutig auf `source`, `target_from`, `target_to` und `relevance` ab; `target_to=0` bleibt die bestehende Bedeutung für einen Einzelvers. Eine echte OpenBible-Quelldatei dient als Format-Fixture. Ungültige oder nicht abbildbare Referenzen dürfen nicht stillschweigend entfallen.

Die OpenBible-Quelldatei enthält auch negative Stimmenwerte. Da `relevance` in der bestehenden Tabelle vorzeichenlos ist, normalisiert der Adapter negative Werte auf `0`. Die Payload ist unabhängig von der Reihenfolge der Quellzeilen nach normalisierten Zeilen sortiert. Der geprüfte Stand vom 28. September 2026 enthält 344.799 Referenzen.

Payload und Attribution liegen beispielsweise unter `database/bible-data/` und werden von `release.json` per Manifest-Hash referenziert. Paketierung, Archiv-SHA-256 und Proxmox-Updater prüfen Vorhandensein und Integrität. Ein fehlgeschlagener Abruf oder eine inkompatible Quelle verhindern das Release. Der Release-Build verpackt keinen Übersetzungstext. Die vollständige fachliche Payload-Prüfung erfolgt innerhalb von `bible:import`; ein eigenständiger Prüf- oder produktiver Aufbereitungsbefehl für Übersetzungen wird nicht eingeführt. Ein Git-Commit allein identifiziert bei erneutem OpenBible-Abruf keinen identischen Cross-Reference-Stand: Das veröffentlichte Release-Artefakt mit seinen Hashes bleibt unveränderlich und wird für eine Reproduktion archiviert.

### 4.5 SourceForge als vorbereitete Erweiterung

Die Quellschnittstelle trennt Katalog, Abruf, Rechteangaben, Parser und stabile Anbieter-ID, damit später ein `sourceforge:<zefania-identifier>`-Adapter ergänzt werden kann. Der bestehende Zefania-Parser kann dafür fachlich wiederverwendet, muss aber auf vollständige Prüfung und atomaren Import umgestellt werden. SourceForge wird **jetzt nicht** dynamisch durchsucht, im Menü angeboten oder als automatischer Fallback genutzt. Eine spätere Aktivierung verlangt eigene Format-, Katalog-, Rechte-, Versions- und Proxmox-Tests. Gleichnamige Texte aus Scrollmapper und SourceForge sind bis zum nachgewiesenen Inhaltsvergleich verschiedene Datensätze.

## 5. Ein produktiver Importbefehl

### 5.1 Syntax

```text
bible:import
  [--update-translations]
  [--update-cross-references]
  [--cross-references]
  [--translation=ID]...
  [--no-interaction]
```

`--no-interaction` ist eine vorhandene globale Artisan-Option und wird nicht erneut definiert. `--translation` ist eine wiederholbare Array-Option (`--translation=*` in der Artisan-Signatur); doppelte IDs werden einmal verarbeitet. Die ID enthält Anbieter und nicht versionierte Ausgabe-Kennung. Für die Produktion sind ausgeführte Migrationen und eine Verbindung zur vorgesehenen Datenbank erforderlich. Die **interaktive Katalogauswahl** und jeder angeforderte Übersetzungsimport benötigen ausgehendes HTTPS; nicht interaktiver Cross-Reference-Import und reine Statusausgabe dürfen keine GitHub-Verbindung verlangen.

Vertragliche Beispiele:

```text
bible:import
bible:import --update-translations --update-cross-references --no-interaction
bible:import --translation=scrollmapper:GerElb1905 --translation=scrollmapper:GerSch --cross-references --no-interaction
```

### 5.2 Interaktiver Aufruf

Nach dem **nicht interaktiven Rechtehinweis** stellt `bible:import` genau zwei fachliche Fragen:

1. **Welche Bibelübersetzungen jetzt installieren oder bei geändertem Inhalt aktualisieren?** Der durchsuchbare Mehrfachkatalog wird von einem einmalig fixierten Scrollmapper-Commit abgeleitet. Vorgeschlagene IDs stehen zuerst, bleiben aber nicht vorausgewählt. Vorhandene Installationen sind markiert und dürfen als bisherige Auswahl vorausgewählt sein. Sprache, Ausgabe, Umfang, Quelllink und vorhandene Rechteangabe sind einsehbar. Eine leere Auswahl ist erlaubt und wird im Status sichtbar; Weglassen deinstalliert nichts.
2. **Cross References jetzt installieren oder bei geändertem Inhalt aktualisieren?** Die getrennte Ja/Nein-Frage erscheint, wenn das Release ein gültig referenziertes Cross-Reference-Payload enthält. Eine bestehende Installation ist als solche erkennbar. „Nein“ verändert sie nicht.

Ein Aufruf ohne `--no-interaction` verlangt eine TTY. Ohne TTY oder bei abgebrochener Eingabe erfolgen keine Datenänderungen und ein verständlicher Fehlerstatus. Aktionsoptionen ohne `--no-interaction` sind ungültig, damit CLI-Flags die Antworten nicht verdeckt ergänzen. Der Rechtehinweis ist ausdrücklich **keine dritte Frage**.

### 5.3 Nicht interaktiver Aufruf

| Option | Verbindliche Wirkung |
| --- | --- |
| `--update-translations` | Nur bereits erfolgreich installierte Scrollmapper-Übersetzungen auf eine geänderte Quellversion prüfen und gegebenenfalls aktualisieren. Keine neue Auswahl. |
| `--update-cross-references` | Cross References nur bei bereits erfolgreicher Installation auf eine geänderte Release-Version prüfen. |
| `--translation=ID` | Die genau bezeichnete, im Scrollmapper-Katalog vorhandene Ausgabe installieren oder aktualisieren und anschließend als installiert führen. Mehrfach zulässig. |
| `--cross-references` | Cross References aus dem Release installieren oder aktualisieren und anschließend als installiert führen. |
| Keine Aktionsoption | Nur lokalen Installationsstatus ausgeben; keinen Netzwerkabruf und keine Datenbankmutation auslösen. |

Kombinierte Optionen bilden die Vereinigungsmenge und verarbeiten jeden Datensatz höchstens einmal. Ein Update-Flag installiert niemals etwas, was bisher nicht installiert war. Explizite unbekannte IDs, nicht abrufbare Ausgaben und inkompatible Dateien sind Fehler, keine stillen Überspringer. Gespeicherte, inzwischen aus dem Katalog entfernte Übersetzungen bleiben erhalten und werden als „Quelle nicht mehr verfügbar“ gemeldet. Ein Update darf sie nicht durch eine ähnlich benannte Ausgabe ersetzen.

### 5.4 Status und Exit-Code

Jeder gestartete Lauf zeigt nach dem Rechtehinweis eine Statusübersicht: Anzahl verfügbarer Katalogeinträge **falls online abgefragt**, ID und Titel der betroffenen oder installierten Datensätze, Installationsstatus, angewendeter und beobachteter normalisierter Hash, Quell-Commit beziehungsweise Release-Version, Vers-/Referenzzahl und Aktion (`importiert`, `aktualisiert`, `unverändert`, `nicht installiert`, `übersprungen`, `nicht verfügbar` oder `fehlgeschlagen`). Bei einem Katalog- oder Netzwerkfehler wird der **letzte erfolgreiche lokale Stand** angezeigt; der unbekannte Remote-Stand wird nicht als aktuell behauptet. Auch „nichts zu tun“ endet mit Status und Exit-Code 0. Ungültige Optionen, fehlende TTY, Netzwerk-/Limitfehler bei angeforderten Übersetzungen, beschädigte Daten und Importfehler liefern einen von null verschiedenen Code. Versinhalte, Tokens, Secrets und vollständige Rohdaten erscheinen nie im Log.

## 6. Dauerhafter Auswahl- und Versionsstand

Eine neue reversible Migration führt eine Status-/Auswahltabelle mit eindeutigem Schlüssel aus Datensatztyp, Anbieter und stabiler Ausgabe-ID ein. Gespeichert werden mindestens: erfolgreich installiert/ausgewählt, zuletzt angewendeter normalisierter SHA-256-Hash und Normalisierungsversion, angewendeter sowie zuletzt erfolgreich geprüfter Quell-Commit/Blob-Hash, Quellpfad, zuletzt erfolgreiche Vers-/Referenzzahl, Rechte-/Attributionsmetadaten, Abruf- und Abschlusszeitpunkt. Für Cross References steht statt Git-Commit die OpenBible-Quellkennung und Release-/Manifestversion. Fehlerdetails gehören in geschützte Logs, nicht in den erfolgreichen Versionsstand. Die bestehenden Vers- und Cross-Reference-Tabellen bleiben bestehen.

Bei gleichem normalisiertem Hash erfolgt kein Delete, Insert oder unnötiges `touch()` der Bibel-Metadaten. Ein anderer Software-Release, Git-Commit oder Export-Blob allein begründet keinen Fachimport. Der **angewendete** Hash und die Auswahl werden nur zusammen mit erfolgreich importierten Fachzeilen festgeschrieben; ein neuer, inhaltlich gleicher Blob darf allein den getrennten Beobachtungsstand ändern. Das Abwählen im Prompt löscht weder Status noch Texte.

**Bestandsübernahme:** Vorhandene Zefania-UUIDs (`ELB1905`, `SCH1951`, `LEO_NA28`, `VLX3`) werden nicht allein wegen ähnlicher Namen auf Scrollmapper-IDs umgestellt. Ein getesteter Vergleich von Ausgabe, Buch-/Versbestand und normalisiertem Inhalt kann eine konkrete ID-Zuordnung erlauben. Bis dahin sind diese Daten „bestehend, nicht verwaltet“: Update-Flags fassen sie nicht an, und eine explizite Scrollmapper-Auswahl darf sie weder überschreiben noch löschen. Unbekannte manuelle Übersetzungen bleiben ebenso unberührt. Für bereits vorhandene Cross References ohne Statuszeile gilt nur eine belegte Zeilenzahl als Installation; ihr Hash ist zunächst unbekannt, weshalb der erste Update-Lauf sie einmalig aus dem geprüften Release erneuern darf. Unstimmige oder halb befüllte Bestände sind Fehlerfälle, keine Löschfreigabe.

## 7. Importalgorithmus und Fehlergrenze

1. Einen anwendungsweiten Import-Lock erwerben und lokale Voraussetzungen prüfen. Parallele interaktive und Update-Läufe sind unzulässig.
2. Den Rechtehinweis ausgeben. Für einen **interaktiven Aufruf oder angeforderte Übersetzungen** `master` einmalig zu einem Scrollmapper-Commit auflösen, Katalog und Dateimetadaten von genau diesem Commit lesen und die gewünschten IDs validieren. Bei interaktivem Aufruf erfolgt dieser Schritt vor der ersten Frage. Ein reiner nicht interaktiver Cross-Reference- oder Statuslauf berührt GitHub nicht.
3. Die benötigten Übersetzungsdateien in einen isolierten temporären Bereich laden; das Cross-Reference-Manifest und nur angeforderte Payloads aus dem lokalen Release lesen. HTTPS, Host-Allowlist auch nach Redirects, Größen-/Zeitgrenzen, Dateipfade, SHA-256, unterstützte Adapter- und Manifestversion sowie erwartete Quelle prüfen. Keine fremden Skripte ausführen. Bei Netz- oder Rate-Limit-Fehlern angeforderter Daten abbrechen; **kein** automatischer Rückfall auf einen Cache, eine andere Quelle oder ältere Dateien.
4. Alle angeforderten Datensätze **vor der ersten Datenbankmutation** vollständig prüfen: Buch-/Kapitel-/Verszuordnung, Zeichensatz, Pflichtfelder, Dubletten, Teilbestandskennzeichnung, auffällige Mengenrückgänge und Lizenz-/Attributionsmetadaten. Ungültige oder nicht abbildbare Zeilen dürfen nicht stillschweigend verworfen werden. Unveränderten normalisierten Inhalt überspringen.
5. Geänderte Datensätze jeweils nur in ihrem fachlichen Umfang ersetzen: Übersetzungsverse und Metadaten nur der zugeordneten ID, Cross References nur ihre Tabelle. Alle anstehenden Fach- und Statusänderungen bilden einen atomaren Lauf; Batch-Inserts innerhalb einer Datenbanktransaktion sind der erste Ansatz. `TRUNCATE` und DDL innerhalb dieser Transaktion sind ausgeschlossen. Ein Lasttest mit vielen Übersetzungen muss zeigen, dass Laufzeit und Transaktionsgröße tragfähig sind; sonst ist vor einem Staging-/Swap-Verfahren eine gesonderte Datenbankentscheidung nötig.
6. Nachkontrolle, Commit und Statusausgabe. Jeder Fehler rollt alle Änderungen dieses Laufs zurück, belässt den letzten erfolgreichen Hash und liefert einen von null verschiedenen Exit-Code. Temporäre Dateien werden auch nach Fehlern entfernt.

Material-, Resource-, Bundle-, Queue- und Preview-Events werden nicht ausgelöst. Bereits vorhandene Caches für Bibeltexte oder Cross References sind vor der Implementierung zu prüfen und nach erfolgreichem Commit gezielt zu invalidieren. API-Routen und Payloads bleiben unverändert. Ein Import verändert keine Material-Bibelstellen-Beziehungen.

## 8. Proxmox-Erstinstallation, Update und Rollback

### 8.1 Erstinstallation

1. Release herunterladen, Archiv-SHA-256 prüfen, entpacken, Composer ohne Dev-Abhängigkeiten installieren und das Cross-Reference-Manifest prüfen.
2. Datenbank und Shared-Pfade einrichten, dann `migrate --force` aus dem vorbereiteten Release ausführen.
3. Bei erreichbarer TTY `bible:import` aus dem noch nicht aktivierten Release als `www-data` starten. Der Rechtehinweis und die zwei Auswahlfragen erscheinen vor dem ersten Admin. Für den aktuellen interaktiven Katalog sind DNS, CA-Zertifikate und ausgehendes HTTPS zu GitHub Pflicht, **auch wenn anschließend nichts ausgewählt wird**. Ein Katalog-, Download- oder Importfehler stoppt die Erstinstallation, zeigt den Status und lässt die Auswahl nach Behebung erneut zu; es gibt keinen Erfolg mit halb importierten Daten. Eine leere Auswahl bleibt bei erreichbarem Katalog erlaubt. Ein gezielter nicht interaktiver Lauf ohne Übersetzungsoption kann offline nur Status beziehungsweise Cross References bearbeiten, ersetzt aber nicht den vorgesehenen interaktiven Proxmox-Erstinstallationsschritt.
4. Nur nach Exit-Code 0 Preflight, `optimize`, atomaren Releasewechsel und Healthchecks ausführen; danach `users:manage create --first-admin`.

### 8.2 Update

1. Das neue Release samt Cross-Reference-Manifest vorbereiten und die bisher erfolgreich installierten Datensätze lesend bestimmen; keine neue Auswahl anbieten.
2. Bestehendes Deployment-Backup anlegen und verifizieren, Wartungsmodus aktivieren, Worker stoppen und `migrate --force` ausführen.
3. `bible:import --update-translations --update-cross-references --no-interaction` aus dem neuen Release aufrufen. Nur vorhandene Scrollmapper-Übersetzungen werden online geprüft; Cross References kommen aus dem Release. Fehlt GitHub oder ist das Rate-Limit erschöpft, **stoppt das Update**, sofern solche Übersetzungen installiert sind. Ohne installierte Übersetzung funktioniert der Cross-Reference-Teil offline. Unveränderte Daten verursachen keinen Fachimport.
4. Nur nach Exit-Code 0 Preflight, `optimize`, atomaren Codewechsel, Dienststart, `/up` und Freigabe durchführen. Bei Fehler bleibt der Wartungsmodus bestehen; das verifizierte Backup ist der Rückweg für bereits ausgeführte Migrationen.

Der gleiche Software-Release kann wegen später geänderter Scrollmapper-Daten auf zwei Zielsystemen unterschiedliche Bibeltextstände erhalten. Der gespeicherte Quell-Commit und Inhalts-Hash machen jeden angewendeten Stand nachvollziehbar; das Software-Release allein reproduziert ihn **nicht**. Ein Code-Rollback importiert niemals automatisch ältere Bibeltexte oder Cross References. Ein Daten-Rollback erfolgt über einen geprüften Datenbank-Restore. Für manuelle Datenpflege zwischen Software-Releases wird derselbe `bible:import`-Befehl verwendet; es gibt keinen `bible:import:sync`.

## 9. Umsetzung in Teilabschnitten

1. **Katalog und Mapping:** Scrollmapper-Format und Metadaten anhand echter Fixtures festhalten; alle dokumentierten Übersetzungen samt Teilbeständen und Buchzuordnung inventarisieren. Stabile IDs, Vorschlagskonfiguration und Rechtehinweistext festlegen. SourceForge nur als getrennte, noch inaktive Quellschnittstelle berücksichtigen.
2. **OpenBible-Release-Daten:** `bible:prepare:cross-references`, deterministisches Format, Attribution, Manifest und Paket-Hashes im Release-Build ergänzen. Kein Übersetzungstext im Git-Stand oder Release.
3. **Importkern und Versionierung:** Statusmigration, Scrollmapper-Adapter, OpenBible-Adapter, Bestandserkennung, vollständige Prüfung, Hashvergleich, Lock und atomaren Ersatz implementieren. Alten Zefania-Befehl vorläufig als dokumentierten Kompatibilitätsweg erhalten; sein bisheriges nicht atomares Verhalten darf nicht als neuer Produktivpfad genutzt werden. Nach nachgewiesener Bestandseinführung die bisher versionierten XML-Texte aus dem künftigen Git-Stand entfernen und ihre Nutzung durch Seeder oder Release-Build ausschließen; eine Bereinigung alter Git-Historie ist eine separate Entscheidung.
4. **CLI und Proxmox:** `bible:import` mit zwei Fragen, Rechtehinweis, globalem `--no-interaction`, wiederholbarem `--translation` und den drei weiteren Aktionsoptionen implementieren. Erstinstallations- und Update-Aufruf in die bestehenden Deployment-Gates einfügen.
5. **Dokumentation:** README, Installations-, Update-, Release- und Testanleitung sowie Betriebsvertrag nach tatsächlich implementiertem Stand aktualisieren. Das produktive Verbot eines allgemeinen `db:seed` bleibt bestehen.

## 10. Abnahmebedingungen

- Fresh-Migrationen erzeugen leere Fachdatentabellen; importiert wird allein über `bible:import`. Tests verwenden nur die verifizierte, entbehrliche `testing`-Datenbank.
- Der Scrollmapper-Katalog wird aus **einem** Commit gebildet; alle dokumentierten Ausgaben werden mit korrekter ID, Sprache, Titel, Umfang und Rechteangabe beziehungsweise sichtbarem „nicht angegeben“ gezeigt. Vorschläge stehen zuerst, sind nicht vorausgewählt. Mindestens je eine Vollbibel, Teilbibel, nicht deutsche Ausgabe, abweichende Buchfolge und Ausgabe mit fehlender Rechteangabe werden repräsentativ importiert; vor Freigabe muss der gesamte Katalog technisch abbildbar sein.
- Neuinstallation, unveränderter Zweitlauf, geänderte Rohdatei mit gleichem Fachinhalt, geänderter Fachinhalt, explizite und doppelte IDs, kombinierte Optionen, Update nur bereits installierter Daten, entfernte Quell-ID, leere Auswahl, fehlende TTY und fehlendes Netzwerk sind nachgewiesen. Der Rechtehinweis erscheint interaktiv und nicht interaktiv ohne Zustimmungsfrage.
- Korrupte Datei oder Manifest, falscher Hash, Buchmappingfehler, Dublette, unerwarteter Mengenrückgang, GitHub-Limit, Redirect auf fremden Host und Datenbankfehler erhalten alle bisherigen Daten und Versionsstände. Nach Fehlern erscheint Status mit Exit-Code ungleich 0 und ohne verbliebene temporäre Rohdateien.
- Das Release enthält OpenBible-Cross-References samt Attribution und Prüfsummen, aber **keinen** Bibelübersetzungstext. SourceForge ist weder im Menü noch als Fallback aktiv. Bestehende Zefania-Daten bleiben bei automatischen Updates erhalten, bis ihre Zuordnung ausdrücklich nachgewiesen ist.
- Im isolierten Proxmox-Test-LXC werden Erstinstallation, wiederholtes Update, unverändertes Update, geänderte Daten, Abbruch, Netz-/Importfehler, Restore und Code-Rollback gemäß `deployment/docs/testing.md` erprobt. Updates bieten keine neue Auswahl und installieren keine zusätzlichen Übersetzungen.
- PHP-Syntax, passende PHPUnit-/Feature-Tests, Shell-Syntax, Release-Paketprüfung, README-Prüfung und Produktionsgates sind erfolgreich. Externe Nachweise werden vor produktiver Freigabe protokolliert.

## Verbindliche Entscheidungen

Scrollmapper ist die Hauptquelle für auf dem Zielsystem heruntergeladene Übersetzungen. OpenBible-Cross-References bleiben Bestandteil des Release-Pakets. SourceForge/Zefania ist eine vorbereitete, **nicht aktivierte** Erweiterung. Fehlendes Internet blockiert die interaktive Erstinstallation und jedes angeforderte Übersetzungsupdate; ein reiner nicht interaktiver Cross-Reference-Lauf bleibt offline möglich. Rechtehinweise sind sichtbar, verlangen aber keine Bestätigung. Eine Auswahl installiert nichts automatisch allein wegen eines Vorschlags. Genau ein produktiver Befehl heißt `bible:import`; sein automatischer Proxmox-Update-Aufruf lautet `bible:import --update-translations --update-cross-references --no-interaction`.

## Quellen für Format- und CLI-Annahmen

- [Scrollmapper-Repository](https://github.com/scrollmapper/bible_databases/blob/master/README.md): Katalog, Exportformate, Metadatenschema und Formatwechsel seit 2025.
- [GitHub Git-Tree-API](https://docs.github.com/en/rest/git/trees), [Contents-API](https://docs.github.com/en/rest/repos/contents) und [Rate Limits](https://docs.github.com/en/rest/using-the-rest-api/rate-limits-for-the-rest-api): revisionsgebundener Katalog und Abrufgrenzen.
- [SourceForge-Zefania-Verzeichnis](https://sourceforge.net/projects/zefania-sharp/files/Bibles/): mögliche spätere Quelle für Zefania-XML-Module.
- [OpenBible Cross References](https://www.openbible.info/labs/cross-references/): direkte Cross-Reference-Quelle und Attributionshinweis.
- [Laravel 13 Prompts](https://laravel.com/framework/docs/13.x/prompts) und [Artisan Console](https://laravel.com/framework/docs/13.x/artisan): durchsuchbare Mehrfachauswahl, globale Option `--no-interaction` und wiederholbare Array-Optionen.

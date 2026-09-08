# Materialpool – Leitlinien für KI-Agenten

## Auftrag und Arbeitsprinzip

Dieses Repository ist der **Materialpool**, eine geschützte Laravel-Anwendung zur Verwaltung von Materialien, Ressourcen, Schlagwörtern, Bibelstellen und importierten Bundles. Arbeite behutsam: Bestehendes Verhalten, Datenintegrität, Berechtigungen und die etablierte Oberfläche gehen vor Tempo, Modernisierung oder persönlichen Präferenzen.

Die Dokumentation unter [`docs/ai/`](docs/ai/) ist Teil dieser Anweisung und vor Änderungen im betroffenen Bereich zu lesen:

- [Architektur](docs/ai/architecture.md)
- [Verifizierte Ausgangsbasis](docs/ai/baseline-b8dd716.md)
- [Domänen-Invarianten](docs/ai/domain-invariants.md)
- [Designsystem](docs/ai/design-system.md)
- [Qualitätssicherung](docs/ai/quality-gates.md)
- [Entscheidungsvorlage](docs/ai/decision-template.md)

Antworte und dokumentiere auf Deutsch; belasse etablierte technische Bezeichner, API-Namen und Code-Konventionen in Englisch.

## Verbindlicher Ablauf

1. **Bestandsaufnahme vor Änderung:** Lies zuerst die betroffenen Routen, Controller, Requests, Policies, Services, Events/Listener, Modelle, Vue-Komponenten und vorhandenen Tests. Nenne kurz Abhängigkeiten, Risiken und den geplanten Umfang.
2. **Kleinstmögliche Änderung:** Ändere nur Dateien, die für das Ziel notwendig sind. Bewahre öffentliche Verträge, Routen, Payloads, Daten und UI-Verhalten, sofern keine explizite Freigabe zur Änderung vorliegt.
3. **Entscheidungspflicht:** Halte an und frage nach Freigabe, sobald eine Entscheidung Architektur, Datenbank, Sicherheit, Berechtigungen, UX, Informationsarchitektur, Design, Abhängigkeiten, Infrastruktur oder Nutzerverhalten wesentlich verändert. Lege immer mindestens eine konkrete Empfehlung mit Alternativen, Vor-/Nachteilen, Auswirkung und Rückbauaufwand vor. Siehe `docs/ai/decision-template.md`.
4. **Umsetzung:** Ergänze oder aktualisiere passende Tests. Berücksichtige Ereignisse, Queues, Caches, Speicherdateien und Autorisierung.
5. **Prüfen und berichten:** Führe die angemessenen Prüfungen aus. Berichte am Ende: geänderte Bereiche, ausgeführte Prüfungen, nicht ausgeführte Prüfungen samt Grund, Auswirkungen und verbleibende Risiken.

## Was ohne Rückfrage erlaubt ist

- Fehlerbehebungen und kleine Refactorings mit nachweislich unverändertem Verhalten.
- Tests, Dokumentation, Typ-/Kommentarverbesserungen und konsistente Übersetzungen.
- Lokale Stilkorrekturen innerhalb eines vorhandenen Komponenten- oder Code-Musters.
- Lesen, statische Analyse und nicht destruktive Prüfungen.

## Immer vorher fragen

- Neue oder veränderte Datenbanktabellen/-spalten, Migrationen, Datenkorrekturen, Lösch- oder Massenoperationen.
- Änderungen an API-Routen, Payloads, Authentifizierung/Passport, Policies, Rollen oder Sichtbarkeitsregeln.
- Änderungen an Resource-/Material-/Keyword-/Bibledaten-Beziehungen, Queues, Eventketten, Dateiablage, Import/Export, Caches oder Vorschau-Erzeugung.
- Neues Layout, Farben, Typografie, Navigation, mobile Interaktion, Icons oder komponentenübergreifende Designregeln.
- Paket-Upgrades, neue Dependencies, Laravel-/PHP-/Vue-Upgrade, Build-/Docker-/Deployment-Änderungen.
- Änderungen an `.env`, Secrets, Speicherorten, Backups oder produktiven Daten.
- Großflächige Umbenennungen, Refactorings oder das Entfernen vermeintlich ungenutzten Codes.

## Technische Leitplanken

- **Backend:** Laravel 8 und PHP 7.4. Nutze die im Projekt vorhandene Syntax und Konventionen; führe keine Modernisierung des Stacks nebenbei durch.
- **Frontend:** Vue 2, Vue Router 3, Vuex 3, Bootstrap 4/Bootstrap-Vue und Webpack via Laravel Mix. Keine Vue-3-/Vite-Muster, Composition API oder neue UI-Bibliothek ohne Freigabe.
- **API:** Versionen `v1` und `v2` sind bestehende Verträge. Prüfe vor jeder API-Änderung Route, Controller, Request, Policy und alle aufrufenden Vuex-Module/Komponenten.
- **Autorisierung:** Serverseitige Authentifizierung und Policies sind maßgeblich. UI-Ausblendung ist kein Sicherheitsmechanismus.
- **Dateien:** Ressourcen können lokale Dateien, URLs oder Text sein. Behandle den `local_path`-Wert, die konfigurierten Disks und Archive als persistenten Datenvertrag.
- **Asynchronität:** Bei Änderungen an Material oder Resource die zugehörigen Events, Listener, Queues und Cache-Invaliderungen prüfen. Keine Events stillschweigend umgehen.
- **Datenbank:** Bestehende Migrationen niemals umschreiben. Neue Migrationen nur nach Freigabe, reversibel und ohne nicht angeforderte Datenmanipulation.

## Sicherheits- und Datenregeln

- Niemals Zugangsdaten, API-Tokens, reale Nutzerdaten, lokale Datenbanken oder Inhalte aus `storage/` in Dokumentation, Commits oder Antworten ausgeben.
- Keine destruktiven Datenbank-, Datei-, Cache- oder Queue-Operationen ohne ausdrückliche Freigabe und klaren Zielumfang.
- Keine Secrets in `.env.example`; neue Konfiguration nur dokumentiert und mit sicheren Platzhaltern.
- Bei Unsicherheit über Produktionsauswirkung: stoppen, Optionen vorlegen und fragen.

## UI- und Designregeln

- Bestehende Bootstrap-4- und Bootstrap-Vue-Komponenten, Variablen und Interaktionsmuster wiederverwenden.
- Designentscheidungen stets mit einer Empfehlung und mindestens einer Alternative begründen, bevor sie umgesetzt werden.
- Responsive Verhalten, Tastaturbedienung, Fokus, Kontraste, Fehlermeldungen und Ladezustände bei UI-Änderungen mitdenken.
- Sichtbare Texte über die bestehenden Sprachdateien führen; keine neuen hartcodierten UI-Texte, wenn ein passender Übersetzungsmechanismus besteht.

## Mindestprüfungen

Wähle proportional zum Risiko und dokumentiere das Ergebnis:

- PHP-Syntax/Codequalität der geänderten Dateien.
- Betroffene PHPUnit-Tests; für Backend-Logik neue oder angepasste Tests.
- Bei Frontend-Änderungen: Production-Build und eine manuelle, responsive Sichtprüfung der berührten Oberfläche, soweit die lokale Umgebung verfügbar ist.
- Bei Routen/API: Autorisierung und Erfolg-/Fehlerfälle.
- Bei Dateiverarbeitung, Vorschauen, Import oder Queue: ein repräsentativer Ablauf sowie Event-/Cache-Auswirkungen.

Vollständige Befehle und Testgrenzen stehen in `docs/ai/quality-gates.md`.

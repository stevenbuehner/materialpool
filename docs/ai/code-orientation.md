# Orientierung im PHP- und Vue-Code

Diese Karte erklärt den vorhandenen Aufbau. Die verbindlichen Daten- und
Betriebsregeln stehen weiterhin in den benachbarten Verträgen unter `docs/ai/`.

## Anfrage bis zur Antwort

Eine Browseranfrage erreicht `routes/web.php` und lädt für `/vue` die
Vue-Anwendung. Diese wird in `resources/js/apps/main/index.js` gestartet.
`routes.js` ordnet URLs Seiten zu; Pinia-Stores unter `stores/` halten gemeinsam
genutzten Zustand. Komponenten unter `resources/js/components/` stellen
wiederverwendbare Eingaben, Listen und Detailansichten bereit.

API-Anfragen laufen über `routes/api.php` zu Controllern unter
`app/Http/Controllers/Api/`. Requests prüfen Eingaben, Policies die
Berechtigung. Services koordinieren fachliche Abläufe; Eloquent-Modelle bilden
persistente Daten und Beziehungen ab. Events, Listener und Jobs übernehmen
nachgelagerte Arbeit wie Vorschauen, Metadaten und Cache-Invaliderung.

## Sichtbare Entwurfsmuster

| Muster | Stelle im Code | Zweck |
| --- | --- | --- |
| Model–View–Controller (MVC) | `app/Models/`, `app/Http/Controllers/`, `resources/views/` | Trennt HTTP-Steuerung, Datenmodell und serverseitige Darstellung. Die Vue-SPA übernimmt einen Teil der Darstellung. |
| Service Layer | `app/Services/` | Bündelt Abläufe, die über einen einzelnen Controller oder ein Modell hinausgehen. |
| Observer über Events und Listener | `app/Events/`, `app/Listeners/`, `app/Providers/EventServiceProvider.php` | Reagiert auf Änderungen, etwa um Metadaten und Vorschauen nachzuführen. |
| Single-Table-Inheritance | `app/Models/Resource.php` und Ressourcen-Untertypen | Mehrere Ressourcentypen teilen eine Tabelle; `type` ist ein persistenter Vertrag. |
| Adapter | `resources/js/adapters/`, `app/Services/ContextSearch/Qdrant/HttpQdrantClient.php` | Kapselt Komponenten- beziehungsweise externe Client-Schnittstellen. |
| Pipeline | `app/Services/ContextSearch/ContextSearchIndexPipeline.php` | Steuert die Folge von Extraktion, Embedding und Veröffentlichung. |
| Zustandsautomat | `app/Services/Bundles/BundleImportOrchestrator.php`, `app/Enums/BundleImportPhase.php` | Führt einen Bundle-Import über festgelegte Phasen und Statuswerte. |

## Lesereihenfolge für Änderungen

Bei einer Backendänderung zuerst Route, Controller, Request und Policy lesen,
dann Service, Modell, Events und Tests. Bei einer Vue-Änderung zuerst Route,
Seite, beteiligte Komponenten, Store und API-Aufruf verfolgen. Für
Resource-, Bundle- und Kontextsuche-Abläufe zusätzlich die jeweiligen
Domänenverträge lesen, weil dort persistente Nebenwirkungen festgehalten sind.

# Betriebsanleitung: Kontextsuche-Queue (Vorbereitung)

## Status und Freigabegrenze

Diese Anleitung beschreibt die in [Schritt 2 des Queue-Änderungsvertrags](context-search-queue-change-contract.md) **vorbereitete**, noch nicht aktivierte Betriebsstruktur. `ops/production/materialpool-context-search-workers.conf.example` ist **keine** installierbare Produktionsanweisung. Sie darf vor der Abnahme von Schritt 3 weder nach `/etc/supervisor/conf.d/` kopiert noch per Supervisor gestartet werden. `autostart=false` ist eine zweite Sicherung, keine Freigabe. Der bisherige Worker `database/context-search-indexing` bleibt verboten. Der normale Worker und die Bundle-Queues laufen unverändert.

Die nachfolgenden Start-/Störungsabläufe sind Prüf- und Rolloutkriterien für Schritt 3. Bis dahin darf ausschließlich der lesende Check ausgeführt werden:

```sh
php artisan context-search:queue:check
```

Er zeigt die aufgelöste Connection-Konfiguration sowie **nur Anzahlen** alter wartender, reservierter und fehlgeschlagener Jobs. `--configuration-only` verzichtet auf Datenbankabfragen. Fehler oder Altbestand werden dokumentiert; Jobs werden nicht pauschal gelöscht oder auf eine neue Queue kopiert. Der Check liest keine Payloads und gibt keine Dokumentinhalte aus.

## Vor dem einmaligen Cutover in Schritt 3

1. Auf Produktions- und Evaluationssystem getrennt prüfen: freigegebenes Release, Datenbank-/Datei-Backup mit Restore-Nachweis, `config:cache`-Stand, `context-search:queue:check`, verfügbare CLI-Erweiterung `pcntl`, ausführbare Poppler-/Tesseract-Werkzeuge und die erreichbaren, digestgleichen Ollama-Server. Ein bestandener Check allein ist keine Freigabe.
2. Alte `context-search-indexing`-Jobs read-only inventarisieren. Für jeden noch offenen Auftrag fachlich entscheiden: nach Prüfung neu planen, alten Lauf als fehlgeschlagen belassen oder gezielt abschließen. Die Auftrags-ID und Entscheidung protokollieren, nicht die Payload oder private Texte. Alte und neue Worker dürfen nie gleichzeitig denselben Queue-Namen konsumieren.
3. Abnahme des seitenweisen Jobs mit OCR-, Last- und Kapazitätsdatensatz nachweisen: 480-Sekunden-Jobbudget, `retry_after` mindestens 600 Sekunden, Supervisor-`stopwaitsecs=540`, tatsächliche OCR-Prozesskette, Speicher- und Temp-Datei-Spitzen sowie p95/p99. Werte bei Bedarf **vor** der Freigabe gemeinsam anpassen; niemals nur `retry_after` verkürzen oder Workerzahl erhöhen.
4. Nach vollständig geprüftem Release die vorbereitete Supervisor-Datei bewusst als eigene Konfiguration installieren und mit `supervisord -t` sowie `supervisorctl reread` prüfen. Erst die dokumentierte Betriebsfreigabe erlaubt `supervisorctl update` und den kontrollierten Start. Das Release-Aktivierungsskript startet derzeit nur `materialpool-default`; seine Integration für die neuen Worker gehört zu Schritt 3.

## Zielbetrieb nach Schritt 3

| Gruppe | Queue-Namen in Prioritätsreihenfolge | Startgrenze |
| --- | --- | --- |
| OCR/Extraktion | `context-search-calibration-ocr`, `context-search-extraction` | genau 1 lokaler Worker |
| Embedding/Qdrant | `context-search-upsert`, `context-search-embedding` | zunächst genau 1 lokaler Worker |

Beide Gruppen verwenden ausschließlich die Connection `context_search`; `database/default,resource-previews-low` bleibt bei 150/120 Sekunden. Mehr Ollama-Server erhöhen die mögliche Remote-Kapazität, nicht automatisch die lokale CPU-/RAM-Grenze. Eine zweite Embedding-Instanz bedarf einer erneuten Messung und ausdrücklicher Freigabe. Kein Worker verarbeitet `bundle_{id}_queue`.

Das optionale Hintergrund-Zeitfenster und interaktive Ausnahmeberechtigungen werden erst in der späteren Automatisierungsstufe implementiert. Bis dahin sind Backfills manuell und nur nach Kapazitätsprüfung zu starten. Ein laufender Job darf bei Fensterende oder Pause fertig werden; Pause verhindert nur die **nächste** Reservierung. Bei kritischem Disk-/RAM-Druck werden neue Hintergrundaufträge angehalten, nicht gelöscht. Für die 40-GB-SSD gelten die Warn- und Stoppschwellen des Hauptvertrags (unter 12 GB Warnung; unter 8 GB Stopp).

## Beobachtung und Störung

- Mindestens überwachen: wartende/reservierte/fehlgeschlagene Jobs pro Queue, Alter des ältesten Jobs, p50/p95/p99-Laufzeit, Retry-/Timeoutquote, OCR-Seiten pro Minute, RAM-/CPU-Spitze, freie SSD, Qdrant-/Ollama-Latenz und Worker-Neustarts. Metriken und Standardlogs enthalten keine Dokumenttexte, Vektoren, Prompts, Secrets oder kompletten Payloads.
- Wenn der Worker hängt: zuerst Ollama/Qdrant/DB und externe Prozesse prüfen. Keine zweite Instanz als „Reparatur“ starten. Timeout- und `retry_after`-Ordnung sowie Prozessgruppe verifizieren. Erst nach Sichtung der Job-/Seitenzustände gezielt fortsetzen.
- Bei Netz- oder Modellfehlern die Unterscheidung transient/permanent beachten. Digest-, Dimensions-, Authentifizierungs- und Schemafehler erfordern Konfigurationskorrektur; blindes `queue:retry all` ist verboten.
- Bei Worker-Crash werden reservierte Jobs erst nach `retry_after` wieder verfügbar. Wiederholung muss idempotent sein; vor Schritt 3 ist dies für den alten Ressourcenjob **nicht** hinreichend abgesichert. Verwaiste temporäre Dateien nur nach Referenz-, Alters- und Pfadprüfung entfernen.
- Bei Qdrant-Teilausfall bleibt die direkte Suche verfügbar. Keine Teilrevision als vollständig markieren. Reconciliation und revisionsgebundene Veröffentlichung aus Schritt 3 entscheiden über erneuten Upsert und verzögerte Altpunktbereinigung.

## Schonender Rückbau

Neue Dispatches zuerst sperren, Worker nach dem laufenden Job auslaufen lassen, beide Queue-Bestände und `failed_jobs` inventarisieren. Eine Connection-Umbenennung oder pauschales `queue:clear` ist kein Rückbau. Erst nach fachlicher Prüfung alte und neue Aufträge gezielt neu disponieren. Bei Indexfehlern auf die zuvor validierte Qdrant-Generation beziehungsweise den Alias zurückgehen; Originaldateien und fachliche MySQL-Daten bleiben unberührt. Der Default-Worker wird unabhängig davon weiter betrieben. Der tatsächlich ausgeführte Rückbau ist mit Zeiten, Zählern und Freigabeperson zu dokumentieren.

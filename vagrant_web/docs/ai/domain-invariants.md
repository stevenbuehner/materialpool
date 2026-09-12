# Domänen-Invarianten und Risikozonen

Diese Regeln sind aus dem aktuellen Codebestand abgeleitet. Sie dürfen nur nach expliziter Freigabe geändert werden.

## Beziehungen und Eigentümerschaft

| Bereich | Invariante |
| --- | --- |
| Material ↔ Resource | Viele-zu-viele über `material_resource`; Pivot `limitation` ist fachlich relevant. Beim Löschen werden Beziehungen bewusst getrennt. |
| Material ↔ Keyword | Viele-zu-viele über `keyword_material`; Pivot `relevance` erhalten. Keyword-Typen strukturieren die fachliche Bedeutung. |
| Material ↔ Bibleverse | Viele-zu-viele mit Pivot `relevance`. |
| Resource | Single-Table-Inheritance über das Feld `type`; neue Typen betreffen Modell, Validierung, Upload, API, UI, Vorschauen und Extraktion. |
| Keyword | Nested-Set-Baum; `_lft`, `_rgt`, Parent-/Child-Beziehungen nicht manuell manipulieren. |
| Bundle | Fremd-IDs und bundle-spezifische Queues halten externe Inhalte mit lokalen Entitäten konsistent. |

## Lebenszyklusregeln

- Material- oder Resource-Löschungen können Pivots trennen, Fremd-ID-Mappings löschen, lokale Dateien archivieren/entfernen, Caches invalidieren und Bereinigungs-Jobs auslösen.
- Eine Änderung an Ressourcendaten kann Hash, Seitenzahl/Dauer, Dublettenerkennung und Vorschauen beeinflussen.
- Ein Attach/Detach verändert nicht nur die Relation, sondern auch Materialvorschauen und potenziell den Bereinigungsstatus verwaister Entitäten.
- `is_public`, Creator/Modifier und Policies sind Sicherheits- und Sichtbarkeitsdaten. Sie dürfen nicht über beliebige Request-Payloads massenzuweisbar werden.
- `options`, `content_hash`, `type`, `local_path` und Systemzeitstempel sind in den Modellen bewusst geschützt. Diesen Schutz nicht lockern ohne Sicherheitsentscheidung.

## Fachliche Typen

- Keyword-Typen: `key`, `person`, `place`, `lang`.
- Resource-Basistypen: `res`, `link`, `file`, `text`, `book`; Datei-Subtypen umfassen u. a. Audio, Video, Bild, Dokument und PDF.
- Material-Bewertung ist auf maximal 20 begrenzt.

## Änderungs-Checkliste

Vor einer Änderung an einem Kernprozess prüfen:

1. Welche Modelle, Pivots und Foreign-ID-Mappings sind betroffen?
2. Welche Events, Listener, Jobs, Queues und Caches folgen daraus?
3. Welche Dateien auf welchem Disk und welche abgeleiteten Vorschauen sind betroffen?
4. Welche Web-/API-Routen, Requests, Policies, Pinia-Stores und Komponenten nutzen den Ablauf?
5. Welche Bereinigung verwaister Daten könnte dadurch ausgelöst werden?
6. Gibt es einen passenden Test oder muss einer ergänzt werden?

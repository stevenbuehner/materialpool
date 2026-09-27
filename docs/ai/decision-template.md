# Vorlage für entscheidungspflichtige Änderungen

Bei einer wichtigen Entscheidung nicht direkt implementieren. Antworte stattdessen in diesem Format:

```md
## Entscheidung erforderlich: <kurzer Titel>

**Ausgangslage:** <betroffener Ablauf und heutiges Verhalten>

**Empfehlung:** <Option A>

| Option | Vorteile | Nachteile/Risiken | Betroffene Bereiche | Rückbauaufwand |
| --- | --- | --- | --- | --- |
| A – Empfehlung | … | … | … | … |
| B – Alternative | … | … | … | … |

**Design-/Nutzungswirkung:** <falls sichtbar>

**Benötigte Freigabe:** Bitte Option A oder B bestätigen bzw. Vorgaben ergänzen.
```

Als entscheidungspflichtig gelten mindestens: Datenmodell, API-Vertrag, Authentifizierung/Policies, Sicherheits- und Sichtbarkeitsregeln, persistente Dateien, Queues/Importe, neue Abhängigkeiten, Infrastruktur, Informationsarchitektur sowie jede wesentliche Design- oder UX-Änderung.

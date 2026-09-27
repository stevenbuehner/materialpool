# Pflegevertrag für README und Dokumentationsscreenshots

## Zweck und Geltungsbereich

`README.md` ist der zentrale deutschsprachige Einstieg für Administration, Entwicklung und Anwendung. Sicherheitskritische Spezialverträge unter `docs/ai/` bleiben die maßgebliche Detailquelle. Diese Anweisung gilt für Menschen und KI-Agenten, sobald Routen, sichtbare Oberfläche, Texte, CLI-Befehle, Installation, Deployment oder die README selbst geändert werden.

Die README behält genau drei nummerierte Hauptabschnitte:

1. Administration
2. Entwicklung
3. Anwendung

Einleitung, Inhaltsverzeichnis und Quellenhinweis dürfen davor stehen. Neue Themen werden in den passenden Hauptabschnitt eingeordnet; keine vierte parallele Zielgruppe eröffnen.

## Quellenprüfung vor Änderungen

README-Aussagen nie aus Erinnerung übernehmen. Vor einer Änderung mindestens die unmittelbar betroffenen Quellen lesen:

- Routen, Controller, Requests, Policies, Services, Modelle, Events, Jobs und Vue-Komponenten für Anwenderabläufe;
- `composer.json`, `package.json`, `.node-version`, Command-Signaturen und `--help` für Befehle und Versionen;
- `ops/production/` und `production-deployment-contract.md` für Produktion;
- `architecture.md`, `domain-invariants.md`, `design-system.md` und `quality-gates.md` für technische Verträge.

Dokumentiere nur Befehle, Optionen und Wirkungen, die der aktuelle Stand belegt. Platzhalter müssen als solche erkennbar sein. Produktive Secrets, reale Domains, Benutzer, IDs, Datenbankinhalte oder lokale Pfade mit vertraulichen Angaben gehören nicht in die README.

## Schreib- und Sicherheitsregeln

- Deutsch schreiben; etablierte API-, Framework- und Befehlsnamen Englisch belassen.
- Produktion, lokale Entwicklung und isolierte Testumgebung explizit unterscheiden.
- Zustandsverändernde Befehle mit Wirkung, Voraussetzung, Risiko und Nachkontrolle beschreiben.
- Destruktive Befehle nicht als beiläufigen Copy-and-paste-Schritt darstellen.
- `migrate:fresh` und `db:seed` ausschließlich zusammen mit der verifizierten Verbindung zur entbehrlichen MySQL-Datenbank `testing` dokumentieren.
- Spezialverträge verlinken und nicht verkürzt so duplizieren, dass zwei widersprüchliche Wahrheiten entstehen.
- Schnell veraltende Versionsangaben nur nennen, wenn sie durch Manifest oder Vertrag belegt und für den Ablauf nötig sind.
- Änderungen an Datenmodell, API, Berechtigungen, Storage, Queue oder UX werden nicht durch eine README-Anpassung autorisiert.

## Screenshotvertrag

Dokumentationsbilder liegen ausschließlich unter `docs/readme/screenshots/`. Die Visual-Regression-Baselines unter `tests/browser/*-snapshots/` erfüllen einen anderen Zweck und werden nicht als README-Bilder verwendet.

Jedes README-Bild besitzt unmittelbar davor genau einen Block `README-SCREENSHOT` mit diesen Feldern:

```md
<!-- README-SCREENSHOT
id: stabile-eindeutige-id
route: /vue/beispiel
state: sichtbarer Zustand
role: Benutzer oder Administrator
viewport: desktop-webkit (1440x900)
fixture: docs-v1
source: tests/browser/readme-screenshots.spec.js#testname
refresh: konkrete Änderungsauslöser
-->
![Aussagekräftiger deutscher Alternativtext](docs/readme/screenshots/datei.png)
```

Der Block beschreibt den Sollzustand. Bei einer Änderung werden Kommentar, Testzustand, Bild, Alternativtext, Bildunterschrift und erklärender Text gemeinsam geprüft.

### Sichere Erzeugung

```sh
npm run docs:screenshots
npm run docs:check
```

- Ausschließlich die synthetischen Fixtures der dedizierten Playwright-Suite verwenden.
- Keine Entwicklungs-, Staging- oder Produktionsdatenbank und keine echten HTTP-API-Antworten verwenden.
- Keine realen Namen, E-Mail-Adressen, Dateien, Tokens, Secrets, IDs oder personenbezogenen Inhalte aufnehmen.
- Locale `de-DE`, Zeitzone `Europe/Berlin`, feste Zeitwerte, stabile Schrift, feste Viewports und deaktivierte Animationen beibehalten.
- Dynamische Inhalte nur gezielt stabilisieren; keine großzügige Maske verwenden, die sichtbare Regressionen verbirgt.
- Geänderte Bilder einzeln visuell prüfen. Einen Unterschied nicht allein deshalb übernehmen, weil ein Test sonst fehlschlägt.
- Nach der visuellen Prüfung einen zweiten unveränderten Lauf durchführen. Unerklärte Hashänderungen blockieren die Übernahme.

Kann die vorgesehene Umgebung nicht ausgeführt werden, kein Bild manuell erfinden oder aus einer fremden Umgebung ersetzen. Die Aktualisierung bleibt im Abschlussbericht ausdrücklich offen.

## Aktualisierungsauslöser

Mindestens folgende Änderungen verlangen eine README-Prüfung:

- neue, entfernte oder umbenannte Route, Navigation oder Rolle;
- sichtbarer Text, Layout, Breakpoint, Formular, Dialog oder Aktionsablauf;
- Artisan-, npm-, Composer-, Shell-, Deployment-, Queue-, Backup- oder Scheduler-Befehl;
- Runtime-, Lockfile-, Installations-, Test- oder Produktionsvoraussetzung;
- Material-, Resource-, Keyword-, Bibleverse-, Bundle- oder Nutzungsablauf.

Nicht jede Änderung verlangt ein neues Bild. Maßgeblich ist das `refresh`-Feld des betroffenen Bildes und ob sich der dokumentierte sichtbare Zustand verändert. Das Unterlassen oder Regenerieren ist im Abschlussbericht zu begründen.

## Abnahme

Mindestens ausführen:

```sh
npm run docs:check
```

Bei Änderungen an Screenshot-Suite oder sichtbarer Anwendung zusätzlich:

```sh
npm run lint
npm run build
npm run docs:screenshots
```

Der Abschlussbericht nennt geänderte Dokumentationsbereiche, ausgeführte Prüfungen, visuell kontrollierte Bilder, ausgelassene Prüfungen mit Grund und verbleibende Risiken.

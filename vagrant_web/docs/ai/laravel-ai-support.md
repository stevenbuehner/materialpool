# Laravel-KI-Unterstützung im Upgradepfad

## Entscheidung

Laravel Boost 2 wird ab dem abgeschlossenen Laravel-11-Checkpoint als reine Entwicklungsabhängigkeit eingesetzt. Dieser Zeitpunkt ist verbindlich, weil Boost 2 mindestens PHP 8.2 und Laravel 11.45.3 verlangt und gleichzeitig Laravel 11, 12 und 13 unterstützt. Damit steht die Laravel-spezifische Unterstützung bereits für die noch ausstehenden Hauptversionsschritte zur Verfügung, ohne einen späteren Übergang erneut einzuführen.

Boost wird ausschließlich als lokaler MCP-Server für Codex gestartet. Die Konfiguration nutzt `vendor/bin/sail artisan boost:mcp`, sodass die Anwendung mit derselben PHP-Laufzeit und denselben Composer-Paketen wie der jeweilige Upgrade-Checkpoint inspiziert wird. Die Abhängigkeit steht nur in `require-dev` und gehört nicht in ein Production-Deployment.

## Zweck und Grenzen

Sinnvoll genutzt werden die lesenden Entwicklungswerkzeuge von Boost für:

- versionsbezogene Suche in Laravel- und Paketdokumentation,
- Anwendung-, Routen- und Konfigurationsinformationen,
- Datenbankschema und ausschließlich lesende Datenbankabfragen,
- lokale Fehler- und Logdiagnose.

Der Einsatz erweitert den Upgradevertrag und die Änderungsautorisierung nicht. Insbesondere gelten die Datenschutz-, Secret-, Produktionsdaten- und Entscheidungsregeln aus `AGENTS.md` unverändert auch für Ausgaben von Boost.

Folgende Fähigkeiten bleiben im Repository ausdrücklich deaktiviert:

- Browser-Log-Watcher und Browser-Log-Tool, damit Boost kein JavaScript in das bestehende Vue-2-Frontend injiziert,
- Tinker, damit über den MCP-Server kein beliebiger PHP-Code ausgeführt wird,
- Rule Recording, damit der Agent keine dauerhaften Projektregeln ohne den vorhandenen Änderungs- und Reviewprozess schreibt.

Die mit Boost ausgelieferte Datenbankabfrage akzeptiert nur lesende Statements, fordert eine read-only Transaktion beim Datenbankserver an und rollt die Transaktion immer zurück. Sie ersetzt trotzdem keine isolierte Testdatenbank und darf nicht zur Ausgabe realer Nutzerdaten verwendet werden.

## Nicht aufgenommen

Laravel AI SDK und ein anwendungseigener Laravel-MCP-Server werden nicht installiert. Beide würden Produktfunktionalität, externe Modellzugriffe, neue Konfiguration beziehungsweise eine neue sicherheitsrelevante Schnittstelle schaffen. Das ist weder für das Framework-Upgrade erforderlich noch durch P1 autorisiert. Ein späterer fachlicher Einsatz benötigt eine eigene Entscheidung mit Datenschutz-, Kosten-, Berechtigungs- und Betriebsbetrachtung.

## Pflege über die Upgrade-Stufen

- Vor jedem weiteren Laravel-Hauptversionsschritt wird neben dem offiziellen Laravel-Upgradeleitfaden die Kompatibilität der installierten Boost-Version mit der Zielversion geprüft.
- Boost bleibt auf einer stabilen, zur jeweiligen Laravel-Stufe kompatiblen Version. Ein Boost-Major-Upgrade wird separat anhand seines offiziellen Upgradeleitfadens bewertet.
- Versionsspezifische Boost-Guidelines und Agent-Skills werden während der historischen Zwischenstände nicht generiert. `AGENTS.md` und die bestehende Baseline beschreiben bis zur Laravel-13-Abnahme bewusst weiterhin den gültigen Ausgangsstand; automatisch erzeugte Laravel-11- oder Laravel-12-Regeln würden dazu widersprüchliche Anweisungen erzeugen.
- Nach erfolgreicher Laravel-13-Abnahme werden zuerst `AGENTS.md` und die Baseline-Dokumente auf den verifizierten Endstand aktualisiert. Danach werden Boost-Guidelines und -Skills mit der dann installierten Version erzeugt, geprüft und als eigener reviewbarer Änderungssatz übernommen.
- Nach Boost-Updates werden MCP-Start, Konfigurationsgrenzen und die Tests in `LaravelBoostIntegrationTest` erneut geprüft.

## Offizielle Quellen

- Laravel Boost: <https://laravel.com/framework/docs/12.x/boost>
- Laravel Boost 2 Upgrade Guide: <https://github.com/laravel/boost/blob/main/UPGRADE.md>
- Aktuelle Boost-Kompatibilitäts-Constraints: <https://github.com/laravel/boost/blob/main/composer.json>
- Abgrenzung AI SDK, Boost und MCP: <https://laravel.com/blog/laravel-ai-sdk-boost-or-mcp-which-tool-do-you-need>

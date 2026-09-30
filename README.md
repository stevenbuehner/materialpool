# Materialpool

Materialpool ist eine geschützte Anwendung zur Verwaltung von Materialien, Dateien und anderen Ressourcen, Schlagwörtern, Bibelstellen, Nutzungen und importierten Bundles. Die Weboberfläche läuft mit Vue 3; Laravel stellt die geschützten Web- und API-Endpunkte bereit.

## Einstieg

| Ich möchte … | Dokumentation |
| --- | --- |
| Materialpool bedienen, Materialien und Resources finden oder verwalten | [Anwendung](docs/anwendung.md) |
| Materialpool auf Proxmox neu installieren | [Proxmox-Installation](deployment/docs/installation.md) |
| Eine Instanz betreiben, aktualisieren oder sichern | [Administration](docs/administration.md) · [Proxmox-Update und Rollback](deployment/docs/update.md) |
| Lokal entwickeln oder Tests ausführen | [Entwicklung](docs/entwicklung.md) |

## Proxmox-Installation

Für eine **neue** Installation muss zuerst ein erfolgreich veröffentlichtes, stabiles GitHub-Release mit Tag `MAJOR.MINOR.PATCH` und Archiv samt SHA-256-Datei vorliegen. Dann den folgenden Befehl in der **Proxmox-VE-Shell** ausführen. Er lädt das Materialpool-Startskript; dieses erstellt den Debian-LXC und lädt dort das versionierte, geprüfte Release-Archiv. Ein Repository-Klon ist nicht erforderlich.

```bash
bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"
```

Containerwerte können wie bei Community Scripts vorangestellt werden, zum Beispiel `var_cpu=4 var_ram=4096 var_disk=32 var_ctid=123`. Vor dem Start die [Installationsanleitung mit Voraussetzungen und Sicherheitsgrenzen](deployment/docs/installation.md) lesen. Sie beschreibt auch die optionale Kontextsuche-, SMTP- und Backup-Konfiguration. Bestehende Daten benötigen einen gesondert geprüften Restore; der Befehl ist für frische Installationen bestimmt.

Beim ersten Global-Admin verlangt der Installer ein verdeckt eingegebenes Passwort mit mindestens vier Zeichen. Der Eingabedialog startet nach ungültigen Angaben erneut. Ctrl+C bricht die Installation mit Fehlerstatus ab; der Admin kann anschließend wie in der Installationsanleitung beschrieben angelegt werden.

Nach Installation und Update zeigt `materialpool:status` Version und Update-Hinweis, die vollständige Anwendungs-URL, konfigurierte Dienste sowie Daten- und Bibelbestände. Die Backup-Sektion verwendet die lesende Übersicht `backup:list`. Im LXC lässt sich die Anzeige als `runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status` erneut aufrufen. Ein Terminal kann die ausgeschriebene URL als anklickbaren Link erkennen; sie bleibt immer kopierbar.

## Weitere Dokumentation

### Betrieb und Releases

- [Proxmox-Betriebsübersicht](deployment/README.md): Zielarchitektur, Dienste und dauerhafte Pfade.
- [Release erstellen](deployment/docs/release.md): Tag, Paket, Prüfsumme und Veröffentlichung.
- [Qdrant-LXC](deployment/docs/qdrant.md): optionale Kontextsuche im getrennten Container.
- [Fehlersuche](deployment/docs/troubleshooting.md) und [manuelle Proxmox-Abnahme](deployment/docs/testing.md).

### Entwicklung und technische Verträge

- [Architektur](docs/ai/architecture.md), [Domänen-Invarianten](docs/ai/domain-invariants.md) und [Designsystem](docs/ai/design-system.md).
- [Qualitätssicherung](docs/ai/quality-gates.md) und [Produktions- und Deploymentvertrag](docs/ai/production-deployment-contract.md).
- [Vue-3-Migrationsvertrag](docs/ai/vue-3-migration-contract.md), [Bibel-Installationsvertrag](docs/ai/bible-data-installation-contract.md) und [Kontextsuche- und KI-Planungsvertrag](docs/ai/context-search-ai-contract.md).
- [Dokumentationspflege und Screenshots](docs/ai/readme-maintenance.md) sowie [Hinweise für KI-Agenten](AGENTS.md).

> [!CAUTION]
> Produktions-, Entwicklungs- und Testbefehle haben unterschiedliche Voraussetzungen. Destruktive Testbefehle gehören ausschließlich in die verifizierte, entbehrliche Testdatenbank.

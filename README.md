# Materialpool

Materialpool ist eine geschützte Anwendung zur Verwaltung von Materialien, Dateien und anderen Ressourcen, Schlagwörtern, Bibelstellen, Nutzungen und importierten Bundles. Die Weboberfläche läuft mit Vue 3; Laravel stellt die geschützten Web- und API-Endpunkte bereit.

## Einstieg

| Ich möchte … | Dokumentation |
| --- | --- |
| Materialpool bedienen, Materialien und Resources finden oder verwalten | [Anwendung](docs/anwendung.md) |
| Mein Profil, meine E-Mail-Adresse oder mein Passwort ändern | [Profil im Benutzermenü](docs/anwendung.md#anmeldung-und-navigation) |
| Materialpool auf Proxmox neu installieren | [Proxmox-Installation](deployment/docs/installation.md) |
| Eine Instanz betreiben, aktualisieren oder sichern | [Administration](docs/administration.md) · [Proxmox-Update und Rollback](deployment/docs/update.md) |
| Aktuelle Jobs, fehlgeschlagene Jobs und Batches prüfen | [Admin-Übersicht für Jobs und Queues](docs/administration.md#jobs-und-queues) |
| Lokal entwickeln oder Tests ausführen | [Entwicklung](docs/entwicklung.md) |

## Proxmox-Installation

Für eine **neue** Installation muss zuerst ein erfolgreich veröffentlichtes, stabiles GitHub-Release mit Tag `MAJOR.MINOR.PATCH` und Archiv samt SHA-256-Datei vorliegen. Dann den folgenden Befehl in der **Proxmox-VE-Shell** ausführen. Er lädt das Materialpool-Startskript; dieses erstellt den Debian-LXC und lädt dort das versionierte, geprüfte Release-Archiv. Ein Repository-Klon ist nicht erforderlich.

```bash
bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"
```

Containerwerte können wie bei Community Scripts vorangestellt werden, zum Beispiel `var_cpu=4 var_ram=4096 var_disk=32 var_ctid=123`. Vor dem Start die [Installationsanleitung mit Voraussetzungen und Sicherheitsgrenzen](deployment/docs/installation.md) lesen. Sie beschreibt auch die optionale Kontextsuche-, SMTP- und Backup-Konfiguration. Bestehende Daten benötigen einen gesondert geprüften Restore; der Befehl ist für frische Installationen bestimmt.

Beim ersten Global-Admin verlangt der Installer ein verdeckt eingegebenes Passwort mit mindestens vier Zeichen. Der Eingabedialog startet nach ungültigen Angaben erneut. Ctrl+C bricht die Installation mit Fehlerstatus ab; der Admin kann anschließend wie in der Installationsanleitung beschrieben angelegt werden.

Nach Installation und Update zeigt `materialpool:status` farbig gegliederte Sektionen mit Version und Update-Hinweis, der vollständigen Anwendungs-URL, konfigurierten Diensten sowie Daten- und Bibelbeständen. Bei aktivierter Kontextsuche prüft der Befehl Qdrants Readiness-Endpunkt; für jeden konfigurierten Ollama-Server prüft er die API und bei vorhandenem Embedding-Profil Modell, Digest, Dimension und einen Probevektor. Die Datenbankmeldung bestätigt die Erreichbarkeit und lesbare Bestände. Die Backup-Sektion verwendet die lesende Übersicht `backup:list`. Der Proxmox-Updater zeigt diesen Status auch bei einer bereits aktuellen Version zum Abschluss an. Im LXC lässt sich die Anzeige als `runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status --ansi` erneut aufrufen. Ein Terminal kann die ausgeschriebene URL als anklickbaren Link erkennen; sie bleibt immer kopierbar.

Im Proxmox-LXC arbeiten ein dauerhafter `default`-Worker und ein priorisierter Hintergrunddienst für Material-Downloads, aktive Bundle-Imports, freigegebene Kontextsuche und kleine Vorschauen. Der Backup-Scheduler bleibt ein eigener Timer. Die Details zu Diensten und Updates stehen in der [Proxmox-Betriebsübersicht](deployment/README.md).

Bei jedem Proxmox-Update wird auch die versionierte Nginx-Site aus dem Release übernommen, mit `nginx -t` geprüft und neu geladen. Individuelle Site-Anpassungen gehören in `deployment/nginx/materialpool.conf`.

Material-Downloads entstehen als ZIP im Hintergrund und werden über einen geheimen Link direkt von Nginx aus dem persistenten Storage ausgeliefert. Die Standardgültigkeit steht in `config/material_downloads.php` (28 Tage); ein verzögerter Job auf `default` und ein täglicher Abgleich löschen abgelaufene Downloads. [Nutzung](docs/anwendung.md#materialien-verwalten) und [Betrieb](docs/administration.md#material-downloads) beschreiben den Ablauf.

Für Audio-/Video-Ausschnitte und Vorschauen wird das Systempaket `ffmpeg` benötigt. Bei bestehenden Installationen ist es vor einem Update nachzuinstallieren; [Betriebsdetails](docs/administration.md#voraussetzungen) und [Produktionsvertrag](docs/ai/production-deployment-contract.md) nennen die benötigten Encoder.

Wenn `SESSION_SECURE_COOKIE=false` ist und eine HTTPS-URL oder ein Trusted Proxy konfiguriert ist, zeigt der Status eine rote Konfigurationswarnung. Bei TLS-Zugriff muss der Wert `true` sein; ein Proxy-Eintrag allein bestätigt noch keine TLS-Verbindung.

## Weitere Dokumentation

Die Bildvorschauen werden in `config/app.php` unter `app.preview.small` (Listen und Suche) und `app.preview.large` (Detail und Zoom) konfiguriert. Jedes Profil enthält maximale Breite und Höhe, PDF-Auflösung, Qualität und Ausgabeformat. Änderungen an den Profilen gelten nach dem üblichen Einlesen der Laravel-Konfiguration; neue Vorschaubilder erhalten eigene Cache-Schlüssel.

### Betrieb und Releases

- [Proxmox-Betriebsübersicht](deployment/README.md): Zielarchitektur, Dienste und dauerhafte Pfade.
- [Release erstellen](deployment/docs/release.md): Tag, Paket, Prüfsumme und Veröffentlichung.
- [Qdrant-LXC](deployment/docs/qdrant.md): optionale Kontextsuche im getrennten Container.
- [Fehlersuche](deployment/docs/troubleshooting.md), [geführte Proxmox-Abnahme](deployment/docs/abnahme.md) und [vollständige Testanleitung](deployment/docs/testing.md).

### Entwicklung und technische Verträge

- [Architektur](docs/ai/architecture.md), [Domänen-Invarianten](docs/ai/domain-invariants.md) und [Designsystem](docs/ai/design-system.md).
- [Qualitätssicherung](docs/ai/quality-gates.md) und [Produktions- und Deploymentvertrag](docs/ai/production-deployment-contract.md).
- [Vue-3-Migrationsvertrag](docs/ai/vue-3-migration-contract.md), [Bibel-Installationsvertrag](docs/ai/bible-data-installation-contract.md) und [Kontextsuche- und KI-Planungsvertrag](docs/ai/context-search-ai-contract.md).
- [Dokumentationspflege und Screenshots](docs/ai/readme-maintenance.md) sowie [Hinweise für KI-Agenten](AGENTS.md).

> [!CAUTION]
> Produktions-, Entwicklungs- und Testbefehle haben unterschiedliche Voraussetzungen. Destruktive Testbefehle gehören ausschließlich in die verifizierte, entbehrliche Testdatenbank.

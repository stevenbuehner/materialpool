# Release erstellen

`ci.yml` läuft bei Push und Pull Request mit Leserechten. `release.yml` läuft nur bei Tags `vMAJOR.MINOR.PATCH`, prüft SemVer erneut und führt PHP-Tests, npm-Tests, Lint und Vite-Build vor Veröffentlichung aus. Nur der Publish-Job erhält `contents: write`; er erstellt mit `GITHUB_TOKEN` das GitHub Release. Es wird kein Server per SSH kontaktiert.

Beispiel nach grünem CI und geprüftem Commit:

```bash
git tag v0.0.1
git push origin v0.0.1
```

Die Assets heißen `materialpool-v0.0.1.tar.gz`, `materialpool-v0.0.1.tar.gz.sha256` und `release.json`. Das Archiv enthält Anwendungscode, `composer.lock`, Migrationen, Laufzeitressourcen, Vite-Manifest und `release.json`; `.env`, `vendor`, `node_modules`, Tests, lokale Uploads und Git-Metadaten fehlen. Composer installiert auf dem LXC exakt nach Lockfile mit `--no-dev`. Frontend und PHP-Build-Gates laufen nur in Actions.

Für private Repositories braucht der LXC ein GitHub Fine-grained Token mit ausschließlich lesendem Zugriff auf Repository-Inhalte/Releases. Es liegt root-only unter `/etc/materialpool/github.token`, der Pfad in `/etc/materialpool/release.conf`. Bei öffentlichen Repositories bleibt die Token-Datei weg. Tokenrotation ist ein manueller root-only Betriebsschritt; nie in Shell-History oder Logs einfügen.

Release-Migrationen sollen abwärtskompatibel sein (Expand/Contract). Ein Code-Rollback setzt eine bereits ausgeführte Datenbankmigration nicht zurück.

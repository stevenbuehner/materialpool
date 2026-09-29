# Release erstellen

`ci.yml` läuft bei Push und Pull Request mit Leserechten. Das Tagformat lautet `MAJOR.MINOR.PATCH([a-z]?)`: drei Zahlengruppen und optional ein einzelner Kleinbuchstabe, ohne führendes `v`. Bei einem anderen Tag gibt CI einen Hinweis aus. `release.yml` läuft bei einem neu gepushten passenden Tag, prüft das Format erneut und führt PHP-Tests, npm-Tests, Dokumentationslinks, Lint und Vite-Build vor Veröffentlichung aus. Nur der Publish-Job erhält `contents: write`; er erstellt mit `GITHUB_TOKEN` das GitHub Release und prüft anschließend den veröffentlichten Tag und beide Assets über die GitHub-API. Tags mit Buchstaben, etwa `2.0.0b`, werden als Prerelease veröffentlicht. Die automatische Neuinstallation und `update` verwenden nur stabile Releases ohne Buchstaben über GitHubs `/releases/latest`. Es wird kein Server per SSH kontaktiert. Ein Release-Upgrade erfordert einen neuen Tag und dessen erfolgreich veröffentlichtes Archiv; ein Branch-Push oder bereits früher gepushter Tag startet den Workflow nicht nachträglich.

Beispiel nach grünem CI und geprüftem Commit: Zuerst mit `git remote -v` das Ziel des Remotes `github` kontrollieren. Die Beispielversion nur verwenden, wenn sie zum freigegebenen Release passt und der Tag noch nicht existiert. Der Tag veröffentlicht nach erfolgreicher Action ein produktiv auswählbares Release.

```bash
git tag 0.0.1
git push github 0.0.1
```

Die Assets heißen `materialpool-0.0.1.tar.gz` und `materialpool-0.0.1.tar.gz.sha256`. Das Archiv enthält Anwendungscode, `composer.lock`, Migrationen, Laufzeitressourcen, Vite-Manifest, den Updater, eine `release.json` mit Version und Commit sowie vorbereitete OpenBible-Cross-References mit fachlichem Manifest und Payload-Prüfsumme. GitHub Actions führt dafür vor der Paketierung `bible:prepare:cross-references` aus und prüft das fertige Paket einschließlich der Cross-Reference-Prüfsumme. Der LXC prüft die Archiv-Prüfsumme vor der Installation; `bible:import` prüft die fachlichen Daten vor Änderungen an Bibeldaten. Bibelübersetzungstexte sind nicht Bestandteil des Release-Archivs; `bible:import` lädt ausgewählte Texte direkt auf dem Zielsystem. Repository- und Entwicklungsinhalte wie `.agents`, `.ai`, `.codex`, `.github`, `docs`, `ops` und `tests` sowie `.env`, `vendor`, `node_modules`, lokale Uploads und Git-Metadaten sind ausgeschlossen. Der Paketprüfer weist solche Verzeichnisse zurück, falls sie versehentlich ins Archiv gelangen. Composer installiert auf dem LXC exakt nach Lockfile mit `--no-dev`. Frontend und PHP-Build-Gates laufen nur in Actions.

Für private Repositories braucht der LXC ein GitHub Fine-grained Token mit ausschließlich lesendem Zugriff auf Repository-Inhalte/Releases. Es liegt root-only unter `/etc/materialpool/github.token`, der Pfad in `/etc/materialpool/release.conf`. Bei öffentlichen Repositories bleibt die Token-Datei weg. Tokenrotation ist ein manueller root-only Betriebsschritt; nie in Shell-History oder Logs einfügen.

Release-Migrationen sollen abwärtskompatibel sein (Expand/Contract). Ein Code-Rollback setzt eine bereits ausgeführte Datenbankmigration nicht zurück.

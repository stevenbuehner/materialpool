#!/usr/bin/env bash
set -Eeuo pipefail
[[ $# -eq 1 && -f "$1" && -f "$1.sha256" ]] || exit 64
artifact="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"
(cd "$(dirname "$artifact")" && sha256sum -c "$(basename "$artifact").sha256")
listing="$(tar -tzf "$artifact")"
for expected in ./artisan ./composer.json ./composer.lock ./release.json ./public/build/manifest.json ./deployment/release/update.sh ./deployment/systemd/materialpool-queue.service ./deployment/systemd/materialpool-background.service ./deployment/systemd/materialpool-schedule.service ./deployment/systemd/materialpool-schedule.timer ./database/bible-data/manifest.json ./database/bible-data/cross-references.tsv; do
  grep -qxF "$expected" <<< "$listing" || { echo "Fehlt: $expected" >&2; exit 1; }
done
# Repository-, Entwicklungs-, Test- und Betriebsdateien gehören nicht in das GitHub-Release.
if grep -E '^\./(\.env($|\.)|\.agents($|/)|\.ai($|/)|\.codex($|/)|\.github($|/)|\.git($|/)|\.idea($|/)|\.vscode($|/)|docs($|/)|node_modules($|/)|ops($|/)|tests($|/)|vendor($|/)|public/uploads($|/)|public/hot$)' <<< "$listing"; then
  echo "Verbotener Paketinhalt" >&2
  exit 1
fi
if grep -E '^\./(resources/bibles/|database/seeders/data/|formats/csv/)' <<< "$listing"; then
  echo "Bibelübersetzung oder historischer SQL-Dump im Release" >&2
  exit 1
fi
# CI prüft die vorbereiteten Bibeldaten einmal vor der Veröffentlichung.
payload_hash="$(tar -xOf "$artifact" ./database/bible-data/cross-references.tsv | sha256sum | cut -d' ' -f1)"
expected_payload_hash="$(tar -xOf "$artifact" ./database/bible-data/manifest.json | jq -r '.sha256 // empty')"
[[ "$payload_hash" == "$expected_payload_hash" ]] || { echo "Cross-Reference-Hash stimmt nicht" >&2; exit 1; }
# shellcheck disable=SC2016 # Dollar signs belong to the embedded PHP program.
tar -xOf "$artifact" ./release.json | php -r '$j=json_decode(stream_get_contents(STDIN),true); exit(is_array($j) && preg_match("/^[0-9]+\\.[0-9]+\\.[0-9]+[a-z]?$/",$j["version"]??"") && preg_match("/^[0-9a-f]{40}$/",$j["commit"]??"") ? 0 : 1);'

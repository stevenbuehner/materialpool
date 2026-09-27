#!/usr/bin/env bash
set -Eeuo pipefail
[[ $# -eq 1 && -f "$1" && -f "$1.sha256" ]] || exit 64
artifact="$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"
(cd "$(dirname "$artifact")" && sha256sum -c "$(basename "$artifact").sha256")
listing="$(tar -tzf "$artifact")"
for expected in ./artisan ./composer.json ./composer.lock ./release.json ./public/build/manifest.json ./deployment/release/update.sh; do
  grep -qxF "$expected" <<< "$listing" || { echo "Fehlt: $expected" >&2; exit 1; }
done
if grep -E '^\./(\.env($|\.)|node_modules/|vendor/|tests/|\.git/|public/uploads/|public/hot$)' <<< "$listing"; then
  echo "Verbotener Paketinhalt" >&2
  exit 1
fi
# shellcheck disable=SC2016 # Dollar signs belong to the embedded PHP program.
tar -xOf "$artifact" ./release.json | php -r '$j=json_decode(stream_get_contents(STDIN),true); exit(is_array($j) && preg_match("/^v[0-9]+\\.[0-9]+\\.[0-9]+$/",$j["version"]??"") && preg_match("/^[0-9a-f]{40}$/",$j["commit"]??"") && ($j["frontend_built"]??false)===true ? 0 : 1);'

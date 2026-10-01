#!/usr/bin/env bash
set -Eeuo pipefail

[[ $# -eq 2 ]] || { echo "Aufruf: $0 X.Y.Z[a-z] AUSGABEVERZEICHNIS" >&2; exit 64; }
version="$1"
output="$(mkdir -p "$2" && cd "$2" && pwd)"
[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+[a-z]?$ ]] || { echo "Ungültige Version" >&2; exit 64; }
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root"
if git ls-files -- .env '.env.*' | grep -vFx '.env.example' | grep -q .; then
  echo "Eine lokale Umgebungsdatei ist in Git versioniert." >&2
  exit 1
fi
[[ -f public/build/manifest.json && ! -e public/hot ]] || { echo "Vite-Build fehlt oder Hot-Modus aktiv" >&2; exit 1; }
[[ -f composer.lock && -f package-lock.json ]] || { echo "Lockfile fehlt" >&2; exit 1; }
[[ -f database/bible-data/manifest.json && -f database/bible-data/cross-references.tsv ]] || { echo "Vorbereitete Cross References fehlen" >&2; exit 1; }
commit="${GITHUB_SHA:-$(git rev-parse HEAD)}"
[[ "$commit" =~ ^[0-9a-f]{40}$ ]] || exit 1
stage="$(mktemp -d)"
trap 'rm -rf -- "$stage"' EXIT
mkdir -p "$stage/payload"

# Explizite Laufzeitliste verhindert lokale Secrets, Testdaten und Build-Werkzeuge im Archiv.
tar -cf - --exclude='public/uploads' --exclude='public/hot' --exclude='database/seeders/data' \
  artisan composer.json composer.lock app bootstrap/app.php config database \
  deployment/release/update.sh deployment/nginx deployment/systemd \
  public resources/lang resources/views routes \
  | tar -xf - -C "$stage/payload"
mkdir -p "$stage/payload/bootstrap/cache" "$stage/payload/storage"
printf '%s\n' '*' '!.gitignore' > "$stage/payload/bootstrap/cache/.gitignore"
if find "$stage/payload" -type f \( -name '.env' -o -name '.env.*' -o -name '*.key' \) | grep -q .; then
  echo "Paket enthält eine Umgebungs- oder Schlüsseldatei." >&2
  exit 1
fi
# Die Versionsdatei im Archiv dient dem Updater zur Erkennung des aktiven Releases.
# shellcheck disable=SC2016 # Dollar signs belong to the embedded PHP program.
php -r '$data=["version"=>$argv[1],"commit"=>$argv[2]]; file_put_contents($argv[3],json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");' \
  "$version" "$commit" "$stage/payload/release.json"
artifact="materialpool-${version}.tar.gz"
tar -C "$stage/payload" -czf "$output/$artifact" .
# Die Archiv-Prüfsumme ist das Integritätsgate für Download und Installation.
(cd "$output" && sha256sum "$artifact" > "$artifact.sha256")
echo "$output/$artifact"

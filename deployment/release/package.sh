#!/usr/bin/env bash
set -Eeuo pipefail

[[ $# -eq 2 ]] || { echo "Aufruf: $0 vX.Y.Z AUSGABEVERZEICHNIS" >&2; exit 64; }
version="$1"
output="$(mkdir -p "$2" && cd "$2" && pwd)"
[[ "$version" =~ ^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]] || { echo "Ungültige Version" >&2; exit 64; }
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root"
if git ls-files -- .env '.env.*' | grep -vFx '.env.example' | grep -q .; then
  echo "Eine lokale Umgebungsdatei ist in Git versioniert." >&2
  exit 1
fi
[[ -f public/build/manifest.json && ! -e public/hot ]] || { echo "Vite-Build fehlt oder Hot-Modus aktiv" >&2; exit 1; }
[[ -f composer.lock && -f package-lock.json ]] || { echo "Lockfile fehlt" >&2; exit 1; }
[[ -f database/bible-data/manifest.json && -f database/bible-data/cross-references.tsv ]] || { echo "Vorbereitete Cross References fehlen" >&2; exit 1; }
manifest_hash="$(sha256sum database/bible-data/manifest.json | cut -d' ' -f1)"
payload_hash="$(jq -r '.sha256 // empty' database/bible-data/manifest.json)"
[[ "$payload_hash" =~ ^[0-9a-f]{64}$ && "$(sha256sum database/bible-data/cross-references.tsv | cut -d' ' -f1)" == "$payload_hash" ]] || { echo "Cross-Reference-Prüfsumme ungültig" >&2; exit 1; }
commit="${GITHUB_SHA:-$(git rev-parse HEAD)}"
[[ "$commit" =~ ^[0-9a-f]{40}$ ]] || exit 1
stage="$(mktemp -d)"
trap 'rm -rf -- "$stage"' EXIT
mkdir -p "$stage/payload"

# Explizite Laufzeitliste verhindert lokale Secrets, Testdaten und Build-Werkzeuge im Archiv.
tar -cf - --exclude='public/uploads' --exclude='public/hot' --exclude='database/seeders/data' \
  artisan composer.json composer.lock app bootstrap/app.php config database \
  deployment/release/update.sh deployment/nginx deployment/systemd \
  public resources/bin resources/lang resources/views routes \
  | tar -xf - -C "$stage/payload"
mkdir -p "$stage/payload/bootstrap/cache" "$stage/payload/storage"
printf '%s\n' '*' '!.gitignore' > "$stage/payload/bootstrap/cache/.gitignore"
if find "$stage/payload" -type f \( -name '.env' -o -name '.env.*' -o -name '*.key' \) | grep -q .; then
  echo "Paket enthält eine Umgebungs- oder Schlüsseldatei." >&2
  exit 1
fi
php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
node_version="$(node --version | sed 's/^v//')"
# shellcheck disable=SC2016 # Dollar signs belong to the embedded PHP program.
laravel_version="$(php -r '$l=json_decode(file_get_contents("composer.lock"),true); foreach($l["packages"] as $p){if($p["name"]==="laravel/framework"){echo $p["version"];exit;}} exit(1);')"
# shellcheck disable=SC2016 # Dollar signs belong to the embedded PHP program.
php -r '$data=["version"=>$argv[1],"commit"=>$argv[2],"built_at"=>gmdate("Y-m-d\\TH:i:s\\Z"),"php"=>$argv[3],"node"=>$argv[4],"laravel"=>$argv[5],"frontend_built"=>true,"bible_data_manifest_sha256"=>$argv[7]]; file_put_contents($argv[6],json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");' \
  "$version" "$commit" "$php_version" "$node_version" "$laravel_version" "$stage/payload/release.json" "$manifest_hash"
artifact="materialpool-${version}.tar.gz"
tar -C "$stage/payload" -czf "$output/$artifact" .
(cd "$output" && sha256sum "$artifact" > "$artifact.sha256")
cp "$stage/payload/release.json" "$output/release.json"
echo "$output/$artifact"

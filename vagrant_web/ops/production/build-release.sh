#!/usr/bin/env bash
set -Eeuo pipefail

usage() {
    echo "Usage: $0 <exact-git-commit> [output-directory]" >&2
    exit 64
}

[[ $# -ge 1 && $# -le 2 ]] || usage

requested_commit="$1"
output_directory="${2:-$(pwd)/dist}"
repository_root="$(git rev-parse --show-toplevel)"
project_prefix="$(git rev-parse --show-prefix)"
project_prefix="${project_prefix%/}"
resolved_commit="$(git -C "$repository_root" rev-parse --verify "${requested_commit}^{commit}")"

if [[ -n "$(git -C "$repository_root" status --porcelain --untracked-files=all -- "$project_prefix")" ]]; then
    echo "Build abgebrochen: Im Materialpool-Projekt liegen nicht committete Änderungen vor." >&2
    exit 1
fi

if ! git -C "$repository_root" cat-file -e "${resolved_commit}:${project_prefix}/composer.lock"; then
    echo "Build abgebrochen: Der Commit enthält kein Materialpool-Release." >&2
    exit 1
fi

build_root="$(mktemp -d "${TMPDIR:-/tmp}/materialpool-build.XXXXXX")"
source_directory="${build_root}/source"
trap 'rm -rf -- "$build_root"' EXIT
mkdir -p "$source_directory" "$output_directory"

git -C "$repository_root" archive "${resolved_commit}:${project_prefix}" | tar -x -C "$source_directory"

docker_image="${MATERIALPOOL_BUILD_IMAGE:-sail-8.4/app}"
docker run --rm \
    --volume "${source_directory}:/var/www/html" \
    --workdir /var/www/html \
    --entrypoint /bin/bash \
    "$docker_image" \
    -lc 'set -Eeuo pipefail
        test "$(php -r '\''echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;'\'')" = "8.4"
        test "$(node --version)" = "v16.20.2"
        cp .env.example .env
        composer install --prefer-dist --no-interaction
        npm ci
        npm run build
        rm -f .env
        rm -rf node_modules vendor storage/framework/cache/data/* storage/framework/views/*'

payload_checksum="$(docker run --rm \
    --volume "${source_directory}:/release:ro" \
    --entrypoint /bin/bash \
    "$docker_image" \
    -lc "tar -C /release --sort=name --mtime='UTC 1970-01-01' --owner=0 --group=0 --numeric-owner -cf - . | sha256sum | awk '{print \\$1}'")"
cat > "${source_directory}/.release-manifest" <<EOF
COMMIT=${resolved_commit}
PAYLOAD_SHA256=${payload_checksum}
PHP=8.4
NODE=16.20.2
EOF

artifact="${output_directory}/materialpool-${resolved_commit}.tar.gz"
tar -C "$source_directory" -czf "$artifact" .
artifact_checksum="$(shasum -a 256 "$artifact" | awk '{print $1}')"
printf '%s  %s\n' "$artifact_checksum" "$(basename "$artifact")" > "${artifact}.sha256"

echo "Release erstellt: $artifact"
echo "Prüfsumme: ${artifact}.sha256"

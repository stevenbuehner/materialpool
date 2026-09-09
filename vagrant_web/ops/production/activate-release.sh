#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -lt 1 || $# -gt 2 ]]; then
    echo "Usage: $0 <uploaded-release.tar.gz> [--migrate]" >&2
    exit 64
fi

artifact="$1"
migration_mode="${2:-}"
[[ -f "$artifact" && -f "${artifact}.sha256" ]] || {
    echo "Release oder Prüfsummendatei fehlt." >&2
    exit 1
}
[[ -z "$migration_mode" || "$migration_mode" == "--migrate" ]] || exit 64

base_directory="/srv/materialpool"
shared_directory="${base_directory}/shared"
releases_directory="${base_directory}/releases"
staging_directory="$(mktemp -d "${releases_directory}/.staging.XXXXXX")"
trap 'if [[ -d "$staging_directory" ]]; then rm -rf -- "$staging_directory"; fi' EXIT

expected_artifact_checksum="$(awk '{print $1}' "${artifact}.sha256")"
actual_artifact_checksum="$(sha256sum "$artifact" | awk '{print $1}')"
[[ "$actual_artifact_checksum" == "$expected_artifact_checksum" ]] || {
    echo "Prüfsumme des hochgeladenen Releases ist ungültig." >&2
    exit 1
}
tar -xzf "$artifact" -C "$staging_directory"

manifest="${staging_directory}/.release-manifest"
[[ -f "$manifest" ]] || { echo "Release-Manifest fehlt." >&2; exit 1; }
commit="$(sed -n 's/^COMMIT=//p' "$manifest")"
expected_payload_checksum="$(sed -n 's/^PAYLOAD_SHA256=//p' "$manifest")"
[[ "$commit" =~ ^[0-9a-f]{40}$ ]] || { echo "Ungültige Commit-ID im Manifest." >&2; exit 1; }
actual_payload_checksum="$(tar -C "$staging_directory" --exclude='./.release-manifest' --sort=name --mtime='UTC 1970-01-01' --owner=0 --group=0 --numeric-owner -cf - . | sha256sum | awk '{print $1}')"
[[ "$actual_payload_checksum" == "$expected_payload_checksum" ]] || {
    echo "Payload-Prüfsumme aus dem Release-Manifest ist ungültig." >&2
    exit 1
}

release_name="$(date -u +%Y%m%d%H%M%S)-${commit:0:12}"
release_directory="${releases_directory}/${release_name}"
[[ ! -e "$release_directory" ]] || { echo "Release existiert bereits." >&2; exit 1; }

for shared_path in .env storage public-uploads backups; do
    [[ -e "${shared_directory}/${shared_path}" ]] || {
        echo "Shared-Pfad fehlt: ${shared_directory}/${shared_path}" >&2
        exit 1
    }
done

rm -rf -- "${staging_directory}/storage" "${staging_directory}/public/uploads"
mkdir -p "${staging_directory}/public" "${staging_directory}/bootstrap/cache"
ln -s "${shared_directory}/.env" "${staging_directory}/.env"
ln -s "${shared_directory}/storage" "${staging_directory}/storage"
ln -s "${shared_directory}/public-uploads" "${staging_directory}/public/uploads"

chown -R materialpool:www-data "$staging_directory"
chmod -R u=rwX,g=rX,o= "$staging_directory"
chmod -R u=rwX,g=rwX,o= "${shared_directory}/storage" "${shared_directory}/public-uploads" "${staging_directory}/bootstrap/cache"
chmod 0600 "${shared_directory}/storage/oauth-private.key"

runuser -u materialpool -- composer install \
    --working-dir "$staging_directory" \
    --no-dev --prefer-dist --optimize-autoloader --no-interaction

if [[ "$migration_mode" == "--migrate" ]]; then
    [[ "${MATERIALPOOL_RESTORE_PROOF_CONFIRMED:-}" == "yes" ]] || {
        echo "Migration gesperrt: MATERIALPOOL_RESTORE_PROOF_CONFIRMED=yes fehlt." >&2
        exit 1
    }
    [[ -f "${shared_directory}/storage/framework/down" ]] || {
        echo "Migration gesperrt: Die Anwendung ist nicht im Wartungsmodus." >&2
        exit 1
    }
    worker_status="$(supervisorctl status 'materialpool-default:*' 2>/dev/null || true)"
    [[ "$worker_status" != *RUNNING* ]] || {
        echo "Migration gesperrt: Der Default-Queue-Worker läuft noch." >&2
        exit 1
    }
fi

mv "$staging_directory" "$release_directory"
staging_directory=""

runuser -u www-data -- php "${release_directory}/artisan" production:preflight

if [[ "$migration_mode" == "--migrate" ]]; then
    runuser -u www-data -- php "${release_directory}/artisan" migrate --force
fi

runuser -u www-data -- php "${release_directory}/artisan" optimize
ln -sfn "$release_directory" "${base_directory}/current.next"
mv -Tf "${base_directory}/current.next" "${base_directory}/current"

runuser -u www-data -- php "${release_directory}/artisan" reload
systemctl reload php8.4-fpm
supervisorctl restart materialpool-default:*

echo "Release aktiviert: $release_directory"
echo "Vorheriges Release für Rollback nicht löschen."

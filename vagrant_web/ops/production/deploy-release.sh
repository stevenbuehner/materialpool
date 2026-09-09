#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -ne 2 ]]; then
    echo "Usage: $0 <release.tar.gz> <materialpool@ssh-host>" >&2
    exit 64
fi

artifact="$1"
ssh_target="$2"
checksum_file="${artifact}.sha256"

[[ -f "$artifact" && -f "$checksum_file" ]] || {
    echo "Release oder zugehörige .sha256-Datei fehlt." >&2
    exit 1
}

expected_checksum="$(awk '{print $1}' "$checksum_file")"
actual_checksum="$(shasum -a 256 "$artifact" | awk '{print $1}')"
[[ "$actual_checksum" == "$expected_checksum" ]] || {
    echo "Prüfsumme des lokalen Releases ist ungültig." >&2
    exit 1
}
remote_directory="/srv/materialpool/shared/incoming"
remote_artifact="${remote_directory}/$(basename "$artifact")"

ssh "$ssh_target" "mkdir -p '$remote_directory'"
scp "$artifact" "$checksum_file" "${ssh_target}:${remote_directory}/"

echo "Upload abgeschlossen und noch nicht aktiviert."
echo "Auf dem Server ausführen:"
echo "sudo /usr/local/sbin/materialpool-activate-release '$remote_artifact'"

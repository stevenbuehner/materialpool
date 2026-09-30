#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

[[ "${EUID}" -eq 0 ]] || { echo "Nur root darf Releases aktivieren." >&2; exit 1; }
config=/etc/materialpool/release.conf
[[ -f "$config" && "$(stat -c %a "$config")" == 600 ]] || { echo "Root-Konfiguration fehlt oder hat falsche Rechte." >&2; exit 1; }
# Root-only Konfiguration enthält nur Repository und optionalen Pfad zur Token-Datei.
# shellcheck source=/dev/null
source "$config"
[[ "${REPOSITORY:-}" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || { echo "Ungültiges REPOSITORY" >&2; exit 1; }
base=/srv/materialpool
exec 9>/run/lock/materialpool-release.lock
flock -n 9 || { echo "Ein Release-Vorgang läuft bereits." >&2; exit 1; }
token_args=()
if [[ -n "${TOKEN_FILE:-}" ]]; then
  [[ -f "$TOKEN_FILE" && "$(stat -c %a "$TOKEN_FILE")" == 600 ]] || { echo "Token-Datei fehlt oder hat falsche Rechte." >&2; exit 1; }
  token_args=(-H "Authorization: Bearer $(<"$TOKEN_FILE")")
fi

managed_units=(materialpool-queue.service materialpool-background.service materialpool-schedule.service materialpool-schedule.timer)
apply_units() {
  local release="$1" unit
  for unit in materialpool-queue.service materialpool-schedule.service materialpool-schedule.timer; do
    [[ -f "$release/deployment/systemd/$unit" ]] || { echo "Dienstvorlage fehlt: $unit" >&2; return 1; }
  done
  if [[ ! -f "$release/deployment/systemd/materialpool-background.service" ]]; then
    systemctl disable --now materialpool-background.service 2>/dev/null || true
  fi
  for unit in "${managed_units[@]}"; do
    if [[ -f "$release/deployment/systemd/$unit" ]]; then
      install -m 0644 "$release/deployment/systemd/$unit" "/etc/systemd/system/$unit"
    else
      rm -f "/etc/systemd/system/$unit"
    fi
  done
  systemctl daemon-reload
}
save_units() {
  local destination="$1" unit
  mkdir -p "$destination"
  for unit in "${managed_units[@]}"; do
    if [[ -f "/etc/systemd/system/$unit" ]]; then
      cp -p "/etc/systemd/system/$unit" "$destination/$unit"
    else
      touch "$destination/$unit.absent"
    fi
  done
}
restore_units() {
  local source="$1" unit
  for unit in "${managed_units[@]}"; do
    if [[ -f "$source/$unit.absent" ]]; then
      rm -f "/etc/systemd/system/$unit"
    else
      cp -p "$source/$unit" "/etc/systemd/system/$unit"
    fi
  done
  systemctl daemon-reload
}
start_units() {
  systemctl enable --now materialpool-queue.service materialpool-schedule.timer nginx mariadb
  systemctl restart materialpool-queue.service
  systemctl restart materialpool-schedule.timer
  if [[ -f /etc/systemd/system/materialpool-background.service ]]; then
    systemctl enable --now materialpool-background.service
    systemctl restart materialpool-background.service
  fi
  for service in materialpool-queue.service materialpool-schedule.timer php8.4-fpm nginx mariadb; do
    systemctl is-active --quiet "$service"
  done
  if [[ -f /etc/systemd/system/materialpool-background.service ]]; then
    systemctl is-active --quiet materialpool-background.service
  fi
}

archive_override=""
case "${1:-update}" in
  update)
    if [[ $# -eq 3 && "$2" == --archive ]]; then
      archive_override="$3"
      [[ ! -L "$base/current" ]] || { echo "Ein lokales Archiv ist nur bei der Erstinstallation zulässig." >&2; exit 64; }
      [[ -f "$archive_override" && -f "$archive_override.sha256" ]] || { echo "Installationsarchiv oder Prüfsumme fehlt." >&2; exit 64; }
    elif [[ $# -ne 0 && $# -ne 1 ]]; then
      exit 64
    fi ;;
  rollback)
    # Der Rollback wechselt nur den Code; eine bereits migrierte Datenbank bleibt bestehen.
    [[ $# -eq 2 && "$2" =~ ^v?[0-9]+\.[0-9]+\.[0-9]+[a-z]?$ && -d "$base/releases/$2" ]] || exit 64
    old="$(readlink -f "$base/current")"
    [[ -n "$old" ]] || exit 1
    old_link="$(readlink "$base/current")"
    rollback_tmp="$(mktemp -d)"
    save_units "$rollback_tmp/units"
    rollback_done=0
    rollback_cleanup() {
      if ((rollback_done == 0)); then
        systemctl stop materialpool-background.service 2>/dev/null || true
        systemctl stop materialpool-queue.service 2>/dev/null || true
        restore_units "$rollback_tmp/units" || true
        rm -f "$base/current.next"
        ln -s "$old_link" "$base/current.next"
        mv -Tf "$base/current.next" "$base/current"
        systemctl reload php8.4-fpm || true
        start_units || true
      fi
      rm -rf -- "$rollback_tmp"
    }
    trap rollback_cleanup EXIT
    runuser -u www-data -- php "$old/artisan" down
    systemctl stop materialpool-queue.service
    systemctl stop materialpool-background.service 2>/dev/null || true
    ln -s "releases/$2" "$base/current.next"
    mv -Tf "$base/current.next" "$base/current"
    apply_units "$base/current"
    systemctl reload php8.4-fpm
    start_units
    runuser -u www-data -- php "$base/current/artisan" up
    curl -fsS --max-time 10 http://127.0.0.1/up | grep -qx OK
    rollback_done=1
    echo "Code-Rollback auf $2. Datenbankänderungen wurden nicht zurückgenommen."
    exit 0 ;;
  *) echo "Aufruf: update [update|rollback X.Y.Z[a-z]]" >&2; exit 64 ;;
esac

tmp="$(mktemp -d)"
stage=""
prepared_release=""
updater_next=""
critical=0
switched=0
units_changed=0
env_changed=0
cleanup() {
  status=$?
  # Nach einem Fehler vor der Aktivierung nur temporäre Release-Dateien entfernen.
  # Nach Migrationen bleibt die Anwendung zur manuellen Prüfung im Wartungsmodus.
  if ((status != 0)); then
    if ((env_changed == 1)); then
      cp -p "$tmp/env-before" "$base/shared/.env" || true
    fi
    if ((units_changed == 1)); then
      systemctl stop materialpool-background.service 2>/dev/null || true
      systemctl stop materialpool-queue.service 2>/dev/null || true
      restore_units "$tmp/units" || true
    fi
    if ((critical == 1)); then
      echo "Update fehlgeschlagen. Datenbank-Backup und Migrationen prüfen; bei Bestandsinstallationen bleibt der Wartungsmodus aktiv." >&2
      if ((switched == 1)) && [[ -n "${previous:-}" ]]; then
        systemctl stop materialpool-queue.service || true
        systemctl stop materialpool-background.service 2>/dev/null || true
        rm -f "$base/current.next"
        ln -s "$previous" "$base/current.next"
        mv -Tf "$base/current.next" "$base/current"
        systemctl reload php8.4-fpm || true
        runuser -u www-data -- php "$base/current/artisan" down || true
      fi
    fi
  fi
  [[ -z "$updater_next" ]] || rm -f -- "$updater_next"
  [[ -z "$stage" || ! -d "$stage" ]] || rm -rf -- "$stage"
  if ((status != 0)) && [[ -n "$prepared_release" && -d "$prepared_release" ]]; then
    active_release="$(readlink -f "$base/current" 2>/dev/null || true)"
    [[ "$active_release" == "$prepared_release" ]] || rm -rf -- "$prepared_release"
  fi
  rm -rf -- "$tmp"
}
trap cleanup EXIT

# Download und Prüfung erfolgen vollständig vor dem Wartungsmodus.
if [[ -n "$archive_override" ]]; then
  artifact="$(basename "$archive_override")"
  [[ "$artifact" =~ ^materialpool-(v?[0-9]+\.[0-9]+\.[0-9]+[a-z]?)\.tar\.gz$ ]] || { echo "Ungültiger Archivname." >&2; exit 64; }
  version="${BASH_REMATCH[1]}"
  cp -- "$archive_override" "$tmp/$artifact"
  cp -- "$archive_override.sha256" "$tmp/$artifact.sha256"
else
  api="https://api.github.com/repos/$REPOSITORY/releases/latest"
  curl -fsSL --retry 3 -H 'Accept: application/vnd.github+json' "${token_args[@]}" "$api" -o "$tmp/latest.json"
  version="$(jq -r '.tag_name // empty' "$tmp/latest.json")"
  [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Kein stabiles Release im Format X.Y.Z." >&2; exit 1; }
  jq -e '.draft == false and .prerelease == false' "$tmp/latest.json" >/dev/null
  artifact="materialpool-$version.tar.gz"
fi
installed=""
if [[ -f "$base/current/release.json" ]]; then
  installed="$(jq -r '.version' "$base/current/release.json")"
fi
if [[ "$version" == "$installed" ]]; then
  same_version_tmp="$(mktemp -d)"
  save_units "$same_version_tmp/units"
  if ! apply_units "$base/current" || ! start_units; then
    restore_units "$same_version_tmp/units"
    start_units || true
    rm -rf -- "$same_version_tmp"
    exit 1
  fi
  rm -rf -- "$same_version_tmp"
  echo "Materialpool $version ist bereits aktuell."
  echo "Materialpool-Status:"
  if ! runuser -u www-data -- php "$base/current/artisan" materialpool:status --ansi --latest-version="$version"; then
    echo "Statusanzeige konnte nicht vollständig erstellt werden." >&2
  fi
  exit 0
fi
[[ ! -e "$base/releases/$version" ]] || { echo "Release-Verzeichnis existiert bereits: $version" >&2; exit 1; }
if [[ -z "$archive_override" ]]; then
  for name in "$artifact" "$artifact.sha256"; do
    asset_id="$(jq -r --arg name "$name" '.assets[] | select(.name == $name) | .id' "$tmp/latest.json")"
    [[ "$asset_id" =~ ^[0-9]+$ ]] || { echo "Release-Asset fehlt: $name" >&2; exit 1; }
    curl -fsSL --retry 3 -H 'Accept: application/octet-stream' "${token_args[@]}" \
      "https://api.github.com/repos/$REPOSITORY/releases/assets/$asset_id" -o "$tmp/$name"
  done
fi
expected="$(awk -v name="$artifact" 'NF == 2 && $2 == name { print $1 }' "$tmp/$artifact.sha256")"
[[ "$expected" =~ ^[0-9a-f]{64}$ && "$(wc -l < "$tmp/$artifact.sha256")" -eq 1 ]] || { echo "Ungültige Prüfsummendatei" >&2; exit 1; }
[[ "$(sha256sum "$tmp/$artifact" | cut -d' ' -f1)" == "$expected" ]] || { echo "SHA256 stimmt nicht" >&2; exit 1; }
while IFS= read -r entry; do
  [[ "$entry" == ./* && "$entry" != *'/../'* && "$entry" != */.. && "$entry" != *$'\n'* ]] || { echo "Ungültiger Archivpfad" >&2; exit 1; }
done < <(tar -tzf "$tmp/$artifact")
if tar -tvzf "$tmp/$artifact" | awk 'substr($1,1,1)!="-" && substr($1,1,1)!="d" {found=1} END {exit !found}'; then
  echo "Nur reguläre Dateien und Verzeichnisse sind im Release-Archiv zulässig." >&2
  exit 1
fi
stage="$(mktemp -d "$base/releases/.staging.XXXXXX")"
tar -xzf "$tmp/$artifact" -C "$stage" --no-same-owner
[[ -f "$stage/release.json" && -f "$stage/public/build/manifest.json" && -f "$stage/composer.lock" ]] || exit 1
[[ "$(jq -r '.version' "$stage/release.json")" == "$version" ]] || exit 1
[[ "$(jq -r '.commit' "$stage/release.json")" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ ! -e "$stage/.env" && ! -e "$stage/node_modules" && ! -e "$stage/vendor" ]] || exit 1
[[ -f "$stage/deployment/release/update.sh" ]] || { echo "Updater fehlt im Release." >&2; exit 1; }
for unit in "${managed_units[@]}"; do
  [[ -f "$stage/deployment/systemd/$unit" ]] || { echo "Dienstvorlage fehlt im Release: $unit" >&2; exit 1; }
done
bash -n "$stage/deployment/release/update.sh"
# Persistente Konfiguration, Uploads und Storage bleiben außerhalb jedes Releases.
rm -rf -- "$stage/storage"
ln -s "$base/shared/.env" "$stage/.env"
ln -s "$base/shared/storage" "$stage/storage"
rm -rf -- "$stage/public/uploads"
ln -s "$base/shared/public-uploads" "$stage/public/uploads"
# Ältere Installer haben nur cache/data angelegt; das Elternverzeichnis kann
# dadurch root gehören. AppServiceProvider benötigt dort previewimages.
install -d -m 0770 -o www-data -g www-data "$base/shared/storage/framework/cache"
install -d -m 0770 -o www-data -g www-data "$base/shared/storage/framework/cache/previewimages"
chown www-data:www-data "$base/shared/storage/framework/cache" "$base/shared/storage/framework/cache/previewimages"
chmod 0770 "$base/shared/storage/framework/cache" "$base/shared/storage/framework/cache/previewimages"
chown -R materialpool:www-data "$stage"
chmod -R u=rwX,g=rX,o= "$stage"
chmod -R g+rwX "$stage/bootstrap/cache"
# Composer installiert den Code als Deploy-Nutzer. Laravel schreibt beim Discovern
# in bootstrap/cache und Shared-Storage; das erfolgt als Laufzeitnutzer www-data.
runuser -u materialpool -- composer install --working-dir="$stage" --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
for writable in "$stage/bootstrap/cache" "$stage/storage/framework/cache" "$stage/storage/framework/cache/data" "$stage/storage/framework/cache/previewimages" "$stage/storage/framework/views" "$stage/storage/logs"; do
  runuser -u www-data -- test -w "$writable" || { echo "Laufzeitverzeichnis nicht beschreibbar: $writable" >&2; exit 1; }
done
runuser -u www-data -- php "$stage/artisan" package:discover --ansi
[[ ! -e "$stage/public/hot" ]] || exit 1
if [[ ! -L "$base/current" ]]; then
  private_key="$base/shared/storage/oauth-private.key"
  public_key="$base/shared/storage/oauth-public.key"
  if [[ ! -e "$private_key" && ! -e "$public_key" ]]; then
    runuser -u www-data -- php "$stage/artisan" passport:keys
    chmod 0600 "$private_key"
  elif [[ ! -f "$private_key" || ! -f "$public_key" ]]; then
    echo "Unvollständiger Passport-Schlüsselbestand; Installation gestoppt." >&2
    exit 1
  fi
fi
mv "$stage" "$base/releases/$version"
stage=""
release="$base/releases/$version"
prepared_release="$release"

# Erst nach Download, Archivprüfung und Composer wird die laufende Anwendung angehalten.
previous=""
if [[ -L "$base/current" ]]; then
  previous="$(readlink "$base/current")"
  runuser -u www-data -- php "$base/current/artisan" down
  systemctl stop materialpool-queue.service
  systemctl stop materialpool-background.service 2>/dev/null || true
fi
critical=1
# Das Backup liegt vor Migration und Bibeldatenimport; beide können Daten verändern.
backup="$base/shared/backups/pre-$version-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"
install -d -m 0770 -o www-data -g www-data "$base/shared/backups"
mariadb-dump --single-transaction --routines --triggers materialpool | gzip -c > "$backup.partial"
gzip -t "$backup.partial"
chown root:www-data "$backup.partial"
chmod 0640 "$backup.partial"
mv "$backup.partial" "$backup"
runuser -u www-data -- php "$release/artisan" migrate --force
if [[ -n "$previous" ]]; then
  runuser -u www-data -- php "$release/artisan" bible:import --update-translations --update-cross-references --no-interaction
else
  [[ -r /dev/tty && -w /dev/tty ]] || { echo "Erstinstallation benötigt eine interaktive TTY für die Bibelauswahl." >&2; exit 1; }
  runuser -u www-data -- php "$release/artisan" bible:import < /dev/tty > /dev/tty
fi
runuser -u www-data -- php "$release/artisan" production:preflight
if ! grep -q '^PRIORITIZED_BACKGROUND_QUEUE=true$' "$base/shared/.env"; then
  cp -p "$base/shared/.env" "$tmp/env-before"
  env_changed=1
  sed -i '/^PRIORITIZED_BACKGROUND_QUEUE=/d' "$base/shared/.env"
  printf '%s\n' 'PRIORITIZED_BACKGROUND_QUEUE=true' >> "$base/shared/.env"
fi
runuser -u www-data -- php "$release/artisan" optimize
runuser -u www-data -- php "$release/artisan" queues:work-background --configuration-only
# Der atomare Symlinkwechsel aktiviert das vorbereitete Release erst nach dem Preflight.
rm -f "$base/current.next"
ln -s "releases/$version" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
switched=1
systemctl reload php8.4-fpm
save_units "$tmp/units"
units_changed=1
apply_units "$release"
start_units
runuser -u www-data -- php "$base/current/artisan" up
curl -fsS --max-time 10 http://127.0.0.1/up | grep -qx OK
updater_next="$(mktemp /usr/local/sbin/.materialpool-update.XXXXXX)"
install -m 0750 -o root -g root "$release/deployment/release/update.sh" "$updater_next"
mv -f -- "$updater_next" /usr/local/sbin/materialpool-update
updater_next=""
critical=0
echo "Materialpool $version aktiviert; Backup: $backup"
# Nur Deployment-Backups kürzen. Laravel-/S3- und Proxmox-Backups bleiben unberührt.
find "$base/shared/backups" -regextype posix-extended -maxdepth 1 -type f -regex '.*/pre-v?[0-9]+\.[0-9]+\.[0-9]+[a-z]?-[0-9]{8}T[0-9]{6}Z\.sql\.gz' -printf '%T@ %p\n' | sort -nr | tail -n +11 | cut -d' ' -f2- | xargs -r rm -f --
find "$base/releases" -regextype posix-extended -mindepth 1 -maxdepth 1 -type d -regex '.*/v?[0-9]+\.[0-9]+\.[0-9]+[a-z]?' -printf '%T@ %p\n' | sort -nr | tail -n +5 | cut -d' ' -f2- | while IFS= read -r old; do
  [[ "$old" == "$(readlink -f "$base/current")" ]] || rm -rf -- "$old"
done
echo "Materialpool-Status:"
if ! runuser -u www-data -- php "$base/current/artisan" materialpool:status --ansi --latest-version="$version"; then
  echo "Statusanzeige konnte nicht vollständig erstellt werden." >&2
fi

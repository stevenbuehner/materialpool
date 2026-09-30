#!/usr/bin/env bash
# shellcheck source=/dev/null
source /dev/stdin <<<"$FUNCTIONS_FILE_PATH"
color
verb_ip6
catch_errors
setting_up_container
network_check
# Community dev_mode=trace darf die anschließend eingelesenen Secrets nicht protokollieren.
set +x

[[ ! -e /srv/materialpool/current && ! -e /srv/materialpool/shared/.env ]] || {
  msg_error "Bestandsinstallation erkannt. Datenübernahme erfolgt nur nach Restore-Test."
  exit 1
}

repository='stevenbuehner/materialpool'
read -r -p 'Produktions-APP_URL (leer für http://<IP>): ' app_url </dev/tty
if [[ -z "$app_url" ]]; then
  system_ip="$(hostname -I | tr ' ' '\n' | awk '/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/ { print; exit }')"
  [[ -n "$system_ip" ]] || { msg_error "System-IP konnte nicht ermittelt werden."; exit 1; }
  app_url="http://$system_ip"
fi
read -r -p 'TRUSTED_PROXIES (konkrete IP/CIDR): ' trusted_proxies </dev/tty
[[ "$app_url" =~ ^https?://[^[:space:]]+$ ]] || {
  msg_error "APP_URL muss eine HTTP- oder HTTPS-URL ohne Leerzeichen sein."
  exit 64
}
for value in "$app_url" "$trusted_proxies"; do
  [[ "$value" =~ ^[A-Za-z0-9._~!@%+=:/,-]*$ ]] || { msg_error "Ein Konfigurationswert enthält ein nicht unterstütztes Zeichen."; exit 64; }
done

release_api="https://api.github.com/repos/$repository/releases/latest"
msg_info "Stabiles Materialpool-Release wird geprüft"
if ! release_status="$(curl -sSL --retry 3 --connect-timeout 10 --max-time 30 -o /dev/null -w '%{http_code}' "$release_api")"; then
  msg_error "GitHub-Release-Abfrage fehlgeschlagen. Netzwerk und DNS prüfen."
  exit 1
fi
case "$release_status" in
  200) msg_ok "Stabiles Materialpool-Release ist erreichbar" ;;
  404) msg_error "Kein stabiles GitHub-Release veröffentlicht. Erforderlich ist ein erfolgreicher Release-Workflow für einen Tag X.Y.Z."; exit 1 ;;
  *) msg_error "GitHub-Release-Abfrage fehlgeschlagen (HTTP $release_status)."; exit 1 ;;
esac

update_os
for package in nginx jq curl ca-certificates unzip openssl rsync \
  mariadb-client poppler-utils qpdf libreoffice ghostscript \
  tesseract-ocr tesseract-ocr-deu tesseract-ocr-eng; do
  msg_info "$package wird installiert"
  install_packages_with_retry "$package"
  package_status="$(dpkg-query -W -f='${Status}' "$package" 2>/dev/null || true)"
  package_version="$(dpkg-query -W -f='${Version}' "$package" 2>/dev/null || true)"
  [[ "$package_status" == 'install ok installed' && -n "$package_version" ]] || {
    msg_error "$package wurde nicht vollständig installiert."
    exit 1
  }
  msg_ok "$package installiert (Version $package_version)"
done
msg_info "PHP 8.4 und Erweiterungen werden eingerichtet"
PHP_VERSION=8.4 PHP_FPM=YES setup_php
php_version="$(php -r 'echo PHP_VERSION;')"
[[ -n "$php_version" ]] || { msg_error "PHP-Version konnte nicht ermittelt werden."; exit 1; }
msg_ok "PHP eingerichtet (Version $php_version)"
msg_info "Composer wird eingerichtet"
setup_composer
composer_version="$(composer --no-ansi --version)"
[[ -n "$composer_version" ]] || { msg_error "Composer-Version konnte nicht ermittelt werden."; exit 1; }
msg_ok "Composer eingerichtet ($composer_version)"
msg_info "MariaDB wird eingerichtet"
setup_mariadb
mariadb_version="$(mariadb --version)"
[[ -n "$mariadb_version" ]] || { msg_error "MariaDB-Version konnte nicht ermittelt werden."; exit 1; }
msg_ok "MariaDB eingerichtet ($mariadb_version)"
if [[ ! -x /usr/bin/mysqldump && -x /usr/bin/mariadb-dump ]]; then
  ln -s /usr/bin/mariadb-dump /usr/bin/mysqldump
fi
[[ -x /usr/bin/mysqldump ]] || { msg_error "Materialpool benötigt mysqldump für Backups."; exit 1; }
umask 077
install -d -m 0700 /etc/materialpool
# shellcheck disable=SC2034 # Read by setup_mariadb_db from the Community helper.
MARIADB_DB_NAME=materialpool
# shellcheck disable=SC2034 # Read by setup_mariadb_db from the Community helper.
MARIADB_DB_USER=materialpool
MARIADB_DB_PASS="$(openssl rand -hex 32)"
# shellcheck disable=SC2034 # Read by setup_mariadb_db from the Community helper.
MARIADB_DB_CREDS_FILE=/dev/null
setup_mariadb_db
printf '%s\n' '[mysqld]' 'bind-address=127.0.0.1' > /etc/mysql/mariadb.conf.d/90-materialpool.cnf
systemctl restart mariadb
id materialpool >/dev/null 2>&1 || useradd --system --create-home --home-dir /var/lib/materialpool --gid www-data materialpool
install -d -m 0750 -o materialpool -g www-data /srv/materialpool/releases
install -d -m 0750 -o root -g www-data /srv/materialpool/shared
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/app
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/app/resources
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/app/archived
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/app/bundles
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/app/tmp
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/cache
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/cache/data
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/cache/previewimages
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/sessions
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/views
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/backup-temp
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/logs
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/public-uploads
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/backups
printf 'REPOSITORY=%s\n' "$repository" > /etc/materialpool/release.conf
chmod 0600 /etc/materialpool/release.conf
app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"
cat > /srv/materialpool/shared/.env <<EOF
APP_NAME=Materialpool
APP_ENV=production
APP_KEY=$app_key
APP_DEBUG=false
APP_URL=$app_url
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=materialpool
DB_USERNAME=materialpool
DB_PASSWORD=$MARIADB_DB_PASS
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=150
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=$([[ "$app_url" == https://* ]] && echo true || echo false)
TRUSTED_PROXIES=$trusted_proxies
CONTEXT_SEARCH_ENABLED=false
QDRANT_URL=
QDRANT_API_KEY=
CONTEXT_SEARCH_OLLAMA_SERVERS=
CONTEXT_SEARCH_OLLAMA_API_KEYS=
MAIL_CONFIGURED=false
MAIL_MAILER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Materialpool
BACKUP_ENABLED=false
BACKUP_PATH=/srv/materialpool/shared/backups
BACKUP_TEMPORARY_DIRECTORY=/srv/materialpool/shared/storage/framework/backup-temp
BACKUP_NOTIFICATION_EMAIL=
BACKUP_S3_KEY=
BACKUP_S3_SECRET=
BACKUP_S3_REGION=
BACKUP_S3_BUCKET=
BACKUP_S3_ENDPOINT=
BACKUP_S3_PREFIX=materialpool
BACKUP_S3_USE_PATH_STYLE_ENDPOINT=false
EOF
chown root:www-data /srv/materialpool/shared/.env
chmod 0640 /srv/materialpool/shared/.env
unset app_key MARIADB_DB_PASS

tmp="$(mktemp -d)"
trap 'rm -rf -- "$tmp"' EXIT
msg_info "Release-Metadaten werden geladen"
github_api_call "$release_api" "$tmp/release.json"
version="$(jq -r '.tag_name // empty' "$tmp/release.json")"
[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { msg_error "Kein stabiles Release im Format X.Y.Z."; exit 1; }
jq -e '.draft == false and .prerelease == false' "$tmp/release.json" >/dev/null
msg_ok "Stabiles Release $version gefunden"
artifact="materialpool-$version.tar.gz"
for name in "$artifact" "$artifact.sha256"; do
  asset_id="$(jq -r --arg n "$name" '.assets[] | select(.name == $n) | .id' "$tmp/release.json")"
  [[ "$asset_id" =~ ^[0-9]+$ ]] || { msg_error "Release-Asset fehlt: $name"; exit 1; }
  curl -fsSL --retry 3 -H 'Accept: application/octet-stream' \
    "https://api.github.com/repos/$repository/releases/assets/$asset_id" -o "$tmp/$name"
done
expected="$(awk -v name="$artifact" 'NF == 2 && $2 == name { print $1 }' "$tmp/$artifact.sha256")"
[[ "$expected" =~ ^[0-9a-f]{64}$ && "$(wc -l < "$tmp/$artifact.sha256")" -eq 1 ]] || exit 1
[[ "$(sha256sum "$tmp/$artifact" | cut -d' ' -f1)" == "$expected" ]] || exit 1
tar -xOf "$tmp/$artifact" ./deployment/release/update.sh > "$tmp/materialpool-update"
bash -n "$tmp/materialpool-update"
install -m 0750 "$tmp/materialpool-update" /usr/local/sbin/materialpool-update
tar -xOf "$tmp/$artifact" ./deployment/nginx/materialpool.conf > /etc/nginx/sites-available/materialpool
for unit in materialpool-queue.service materialpool-schedule.service materialpool-schedule.timer; do
  tar -xOf "$tmp/$artifact" "./deployment/systemd/$unit" > "/etc/systemd/system/$unit"
done
systemctl daemon-reload
nginx_enable_site materialpool
/usr/local/sbin/materialpool-update update --archive "$tmp/$artifact"
if ! php /srv/materialpool/current/artisan context-search:configure </dev/tty; then
  msg_info "Kontextsuche bleibt deaktiviert. Im LXC kann context-search:configure später erneut ausgeführt werden."
fi
php /srv/materialpool/current/artisan mail:configure </dev/tty
php /srv/materialpool/current/artisan backup:configure </dev/tty
msg_info "Ersten Global-Admin anlegen"
msg_ok "Eingabe für den ersten Global-Admin starten"
if ! runuser -u www-data -- env -u APP_ENV php /srv/materialpool/current/artisan users:manage create --first-admin --short-password </dev/tty; then
  msg_error "Anwendung installiert, aber der erste Global-Admin fehlt. Im LXC den dokumentierten users:manage-Befehl erneut ausführen."
  exit 1
fi
motd_ssh
customize
# Das Community-Standardkommando zielt auf dessen Script-Repository; Materialpool
# verwendet sein lokal geprüftes, release-basiertes Updateprogramm.
printf '%s\n' '#!/usr/bin/env bash' 'exec /usr/local/sbin/materialpool-update update "$@"' > /usr/bin/update
chmod 0755 /usr/bin/update
command -v node >/dev/null && { msg_error "Node wurde unerwartet installiert."; exit 1; }
command -v npm >/dev/null && { msg_error "npm wurde unerwartet installiert."; exit 1; }
cleanup_lxc
msg_ok "Materialpool $version installiert"
if ! runuser -u www-data -- env -u APP_ENV php /srv/materialpool/current/artisan materialpool:status --ansi --latest-version="$version"; then
  echo "Statusanzeige konnte nicht vollständig erstellt werden." >&2
fi

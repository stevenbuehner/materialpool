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

read -r -p 'GitHub-Repository [stevenbuehner/materialpool]: ' repository </dev/tty
repository="${repository:-stevenbuehner/materialpool}"
[[ "$repository" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]] || exit 64
read -r -s -p 'GitHub-Token für privates Repository (leer bei public): ' github_token </dev/tty
echo
read -r -p 'Produktions-APP_URL (https://...): ' app_url </dev/tty
read -r -p 'TRUSTED_PROXIES (konkrete IP/CIDR): ' trusted_proxies </dev/tty
read -r -p 'QDRANT_URL des getrennten LXC (leer wenn derzeit deaktiviert): ' qdrant_url </dev/tty
qdrant_api_key=''
if [[ -n "$qdrant_url" ]]; then read -r -s -p 'QDRANT_API_KEY: ' qdrant_api_key </dev/tty; echo; fi
[[ -z "$qdrant_url" || -n "$qdrant_api_key" ]] || { msg_error "Qdrant benötigt einen API-Key."; exit 64; }
read -r -p 'MAIL_HOST: ' mail_host </dev/tty
read -r -p 'MAIL_PORT: ' mail_port </dev/tty
read -r -p 'MAIL_ENCRYPTION [tls]: ' mail_encryption </dev/tty
mail_encryption="${mail_encryption:-tls}"
read -r -p 'MAIL_USERNAME: ' mail_user </dev/tty
read -r -s -p 'MAIL_PASSWORD: ' mail_password </dev/tty
echo
read -r -p 'MAIL_FROM_ADDRESS: ' mail_from </dev/tty
read -r -p 'BACKUP_NOTIFICATION_EMAIL: ' backup_email </dev/tty
read -r -p 'BACKUP_S3_ENDPOINT (https://...): ' s3_endpoint </dev/tty
read -r -p 'BACKUP_S3_REGION: ' s3_region </dev/tty
read -r -p 'BACKUP_S3_BUCKET: ' s3_bucket </dev/tty
read -r -p 'BACKUP_S3_USE_PATH_STYLE_ENDPOINT [false]: ' s3_path_style </dev/tty
s3_path_style="${s3_path_style:-false}"
read -r -p 'BACKUP_S3_KEY: ' s3_key </dev/tty
read -r -s -p 'BACKUP_S3_SECRET: ' s3_secret </dev/tty
echo
[[ "$app_url" == https://* && "$s3_endpoint" == https://* && -n "$trusted_proxies" && -n "$mail_password" && -n "$s3_secret" && "$s3_path_style" =~ ^(true|false)$ ]] || {
  msg_error "Pflichtwerte fehlen oder HTTPS ist nicht gesetzt."
  exit 64
}
for value in "$app_url" "$trusted_proxies" "$qdrant_url" "$qdrant_api_key" "$mail_host" "$mail_port" "$mail_encryption" "$mail_user" "$mail_password" "$mail_from" "$backup_email" "$s3_endpoint" "$s3_region" "$s3_bucket" "$s3_key" "$s3_secret"; do
  [[ "$value" =~ ^[A-Za-z0-9._~!@%+=:/,-]*$ ]] || { msg_error "Ein Konfigurationswert enthält ein nicht unterstütztes Zeichen."; exit 64; }
done

update_os
install_packages_with_retry nginx jq curl ca-certificates unzip openssl rsync \
  mariadb-client poppler-utils qpdf libreoffice ghostscript \
  tesseract-ocr tesseract-ocr-deu tesseract-ocr-eng
PHP_VERSION=8.4 PHP_FPM=YES setup_php
setup_composer
setup_mariadb
if [[ ! -x /usr/bin/mysqldump && -x /usr/bin/mariadb-dump ]]; then
  ln -s /usr/bin/mariadb-dump /usr/bin/mysqldump
fi
[[ -x /usr/bin/mysqldump ]] || { msg_error "Materialpool benötigt mysqldump für Backups."; exit 1; }
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
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/cache/data
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/sessions
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/views
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/framework/backup-temp
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/storage/logs
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/public-uploads
install -d -m 0770 -o www-data -g www-data /srv/materialpool/shared/backups
printf 'REPOSITORY=%s\n' "$repository" > /etc/materialpool/release.conf
if [[ -n "$github_token" ]]; then
  printf '%s' "$github_token" > /etc/materialpool/github.token
  chmod 0600 /etc/materialpool/github.token
  printf 'TOKEN_FILE=/etc/materialpool/github.token\n' >> /etc/materialpool/release.conf
  export GITHUB_TOKEN="$github_token"
fi
chmod 0600 /etc/materialpool/release.conf
unset github_token
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
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=$trusted_proxies
CONTEXT_SEARCH_ENABLED=false
QDRANT_URL=${qdrant_url:-http://127.0.0.1:6333}
QDRANT_API_KEY=$qdrant_api_key
MAIL_MAILER=smtp
MAIL_HOST=$mail_host
MAIL_PORT=$mail_port
MAIL_ENCRYPTION=$mail_encryption
MAIL_USERNAME=$mail_user
MAIL_PASSWORD=$mail_password
MAIL_FROM_ADDRESS=$mail_from
MAIL_FROM_NAME=Materialpool
BACKUP_PATH=/srv/materialpool/shared/backups
BACKUP_TEMPORARY_DIRECTORY=/srv/materialpool/shared/storage/framework/backup-temp
BACKUP_NOTIFICATION_EMAIL=$backup_email
BACKUP_S3_KEY=$s3_key
BACKUP_S3_SECRET=$s3_secret
BACKUP_S3_REGION=$s3_region
BACKUP_S3_BUCKET=$s3_bucket
BACKUP_S3_ENDPOINT=$s3_endpoint
BACKUP_S3_PREFIX=materialpool
BACKUP_S3_USE_PATH_STYLE_ENDPOINT=$s3_path_style
EOF
chown root:www-data /srv/materialpool/shared/.env
chmod 0640 /srv/materialpool/shared/.env
unset app_key mail_password s3_secret qdrant_api_key MARIADB_DB_PASS

version="$(get_latest_github_release "$repository" false)"
[[ "$version" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || exit 1
tmp="$(mktemp -d)"
trap 'rm -rf -- "$tmp"' EXIT
github_api_call "https://api.github.com/repos/$repository/releases/latest" "$tmp/release.json"
artifact="materialpool-$version.tar.gz"
token_args=()
[[ -z "${GITHUB_TOKEN:-}" ]] || token_args=(-H "Authorization: Bearer $GITHUB_TOKEN")
for name in "$artifact" "$artifact.sha256"; do
  asset_id="$(jq -r --arg n "$name" '.assets[] | select(.name == $n) | .id' "$tmp/release.json")"
  [[ "$asset_id" =~ ^[0-9]+$ ]] || { msg_error "Release-Asset fehlt: $name"; exit 1; }
  curl -fsSL --retry 3 -H 'Accept: application/octet-stream' "${token_args[@]}" \
    "https://api.github.com/repos/$repository/releases/assets/$asset_id" -o "$tmp/$name"
done
expected="$(awk -v name="$artifact" 'NF == 2 && $2 == name { print $1 }' "$tmp/$artifact.sha256")"
[[ "$expected" =~ ^[0-9a-f]{64}$ && "$(wc -l < "$tmp/$artifact.sha256")" -eq 1 ]] || exit 1
[[ "$(sha256sum "$tmp/$artifact" | cut -d' ' -f1)" == "$expected" ]] || exit 1
tar -xOf "$tmp/$artifact" ./deployment/release/update.sh > /usr/local/sbin/materialpool-update
chmod 0750 /usr/local/sbin/materialpool-update
tar -xOf "$tmp/$artifact" ./deployment/nginx/materialpool.conf > /etc/nginx/sites-available/materialpool
for unit in materialpool-queue.service materialpool-schedule.service materialpool-schedule.timer; do
  tar -xOf "$tmp/$artifact" "./deployment/systemd/$unit" > "/etc/systemd/system/$unit"
done
systemctl daemon-reload
nginx_enable_site materialpool
/usr/local/sbin/materialpool-update update
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

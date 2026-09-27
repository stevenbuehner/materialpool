#!/usr/bin/env bash
set -Eeuo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Dieses Skript muss als root auf Ubuntu 24.04 ausgeführt werden." >&2
    exit 1
fi

apt-get update
apt-get install -y software-properties-common
add-apt-repository --yes ppa:ondrej/php
apt-get update
apt-get install -y \
    nginx mysql-server supervisor cron unzip ca-certificates curl git \
    php8.4-cli php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-gd php8.4-imagick php8.4-intl php8.4-bcmath \
    default-mysql-client poppler-utils qpdf libreoffice ghostscript

composer_signature="$(curl --fail --silent --show-error https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
actual_composer_signature="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
[[ "$composer_signature" == "$actual_composer_signature" ]] || {
    echo "Composer-Installer-Signatur ungültig." >&2
    exit 1
}
php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php

id materialpool >/dev/null 2>&1 || useradd --create-home --shell /bin/bash materialpool
install -d -o materialpool -g www-data -m 0750 /srv/materialpool/releases
install -d -o materialpool -g www-data -m 0750 /srv/materialpool/shared/incoming
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/app/resources
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/app/archived
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/app/bundles
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/app/tmp
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/framework/cache/data
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/framework/sessions
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/framework/views
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/storage/logs
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/public-uploads
install -d -o materialpool -g www-data -m 0770 /srv/materialpool/shared/backups

echo "Pakete und Verzeichnisse sind vorbereitet."
echo "Jetzt .env, Passport-Schlüssel, Composer, Nginx, Supervisor, Cron, Firewall und MySQL nach Vertrag einrichten."

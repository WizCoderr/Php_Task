#!/bin/sh
set -e

cd /var/www/html

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ] || [ ! -d vendor/laravel/framework ]; then
    composer install --no-interaction --prefer-dist
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

# Host-mounted package discovery cache may reference packages not yet installed.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi --no-interaction >/dev/null

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --no-interaction
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"

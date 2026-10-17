#!/bin/sh
set -e

mkdir -p /data storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views

if [ -z "$APP_KEY" ]; then
    if [ ! -s /data/app-key ]; then
        php artisan key:generate --show > /data/app-key
    fi
    APP_KEY="$(cat /data/app-key)"
    export APP_KEY
fi

if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data /data storage bootstrap/cache

exec "$@"

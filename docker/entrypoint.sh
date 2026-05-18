#!/usr/bin/env sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force --no-interaction >/dev/null
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap

php artisan migrate --force --no-interaction

exec "$@"

#!/usr/bin/env sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force --no-interaction >/dev/null
fi

php artisan migrate --force --no-interaction

exec "$@"

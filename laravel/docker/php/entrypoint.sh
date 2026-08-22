#!/usr/bin/env sh
set -eu

cd /var/www

if [ ! -f .env ]; then
    echo "Missing .env. Copy docker/local.env.template to .env before starting the application." >&2
    exit 1
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan config:clear >/dev/null 2>&1 || true

exec "$@"

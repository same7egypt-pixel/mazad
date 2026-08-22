#!/usr/bin/env sh
set -eu

cd /var/www

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be configured in the Render environment group." >&2
    exit 1
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

php artisan config:clear >/dev/null 2>&1 || true
php artisan config:cache

exec "$@"

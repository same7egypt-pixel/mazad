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

if [ "${RUN_MIGRATIONS_ON_START:-false}" = "true" ]; then
    echo "Running database migrations for the single-service trial deployment."
    attempt=1

    until php artisan migrate --force --no-interaction; do
        if [ "$attempt" -ge 5 ]; then
            echo "Database migrations failed after ${attempt} attempts." >&2
            exit 1
        fi

        attempt=$((attempt + 1))
        echo "Migration attempt failed; retrying in 3 seconds (${attempt}/5)." >&2
        sleep 3
    done
fi

exec "$@"

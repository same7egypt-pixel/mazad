#!/usr/bin/env sh
set -eu

cd /var/www

echo "Running database migrations for the Render trial deployment."
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

php artisan db:seed --class='Database\Seeders\RolePermissionSeeder' --force --no-interaction
php artisan db:seed --class='Database\Seeders\MarketplaceReferenceSeeder' --force --no-interaction
php artisan marketplace:provision-first-admin --no-interaction

/usr/local/bin/migrate-to-neon

exec /usr/local/bin/render-web

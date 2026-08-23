#!/bin/sh

set -eu

if [ "${NEON_MIGRATION_ENABLED:-false}" != "true" ]; then
    exit 0
fi

if [ -z "${DB_URL:-}" ] || [ -z "${NEON_DATABASE_URL:-}" ]; then
    echo "Neon migration requires both DB_URL and NEON_DATABASE_URL." >&2
    exit 1
fi

if [ "${DB_URL}" = "${NEON_DATABASE_URL}" ]; then
    echo "Neon migration source and target must be different databases." >&2
    exit 1
fi

case "${NEON_DATABASE_URL}" in
    *-pooler*)
        echo "Use the direct Neon connection string with connection pooling disabled for migration." >&2
        exit 1
        ;;
esac

target_table_count="$(psql --dbname="${NEON_DATABASE_URL}" --tuples-only --no-align --command "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE';")"

if [ "${target_table_count}" != "0" ]; then
    echo "Neon migration target must be an empty public schema; found ${target_table_count} table(s)." >&2
    exit 1
fi

source_table_count="$(psql --dbname="${DB_URL}" --tuples-only --no-align --command "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE';")"
dump_file="$(mktemp /tmp/mazad-render-to-neon.XXXXXX.dump)"

cleanup() {
    rm -f "${dump_file}"
}

trap cleanup EXIT INT TERM

echo "Exporting the Render public schema to a temporary archive."
pg_dump --dbname="${DB_URL}" --format=custom --verbose --schema=public --no-owner --no-privileges --file="${dump_file}"

echo "Restoring the archive into the empty Neon target."
pg_restore --dbname="${NEON_DATABASE_URL}" --verbose --no-owner --no-acl --single-transaction --exit-on-error "${dump_file}"

restored_table_count="$(psql --dbname="${NEON_DATABASE_URL}" --tuples-only --no-align --command "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE';")"

if [ "${source_table_count}" != "${restored_table_count}" ]; then
    echo "Neon migration table-count verification failed: source=${source_table_count}, target=${restored_table_count}." >&2
    exit 1
fi

echo "Neon migration completed. Set NEON_MIGRATION_ENABLED=false before any future deployment."

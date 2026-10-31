#!/bin/sh
set -eu

cd /var/www/html

wait_for_database() {
    echo "Waiting for database ${DB_HOST:-db}:${DB_PORT:-5432}..."

    until su-exec www-data php -r '
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            getenv("DB_HOST") ?: "db",
            getenv("DB_PORT") ?: "5432",
            getenv("DB_DATABASE") ?: "profolio"
        );

        new PDO($dsn, getenv("DB_USERNAME") ?: "profolio", getenv("DB_PASSWORD") ?: "");
    ' >/dev/null 2>&1; do
        sleep 2
    done
}

# Only the php-fpm container owns the storage volume and runs the setup steps.
# The nginx container shares the same image but mounts storage read-only.
if [ "${1:-php-fpm}" = "php-fpm" ]; then
    # The storage volume is empty on first boot, so recreate the directories
    # Laravel expects before anything tries to write to them.
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        wait_for_database

        su-exec www-data php artisan migrate --force

        if [ "${SEED_DEFAULTS:-true}" = "true" ]; then
            su-exec www-data php artisan db:seed --class=PosDefaultsSeeder --force
        fi
    fi

    su-exec www-data php artisan storage:link >/dev/null 2>&1 || true

    if [ "${APP_OPTIMIZE:-true}" = "true" ]; then
        su-exec www-data php artisan optimize
    fi
fi

exec "$@"

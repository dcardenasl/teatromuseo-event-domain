#!/usr/bin/env sh

set -eu

cd /var/www/html

db_host="${DB_HOST:-db}"
db_port="${DB_PORT:-3306}"

echo "[entrypoint] Waiting for event-domain database at ${db_host}:${db_port}..."

attempt=0
max_attempts=30
until DB_HOST="${db_host}" DB_PORT="${db_port}" php -r '
    $host = getenv("database.default.hostname") ?: getenv("DB_HOST") ?: "db";
    $port = (int) (getenv("database.default.port") ?: getenv("DB_PORT") ?: 3306);
    $user = getenv("database.default.username") ?: getenv("MYSQL_USER") ?: "ci4_user";
    $password = getenv("database.default.password") ?: getenv("MYSQL_PASSWORD") ?: "";
    $database = getenv("database.default.database") ?: getenv("MYSQL_DATABASE") ?: "teatromuseo_event_domain";
    $connection = @mysqli_connect(
        $host,
        $user,
        $password,
        $database,
        $port
    );
    exit($connection ? 0 : 1);
'; do
    attempt=$((attempt + 1))
    if [ "${attempt}" -ge "${max_attempts}" ]; then
        echo "[entrypoint] Database unavailable after ${max_attempts} attempts." >&2
        exit 1
    fi
    sleep 2
done

echo "[entrypoint] Event-domain database is ready."
echo "[entrypoint] Running pending migrations..."
php spark migrate --all

exec "$@"

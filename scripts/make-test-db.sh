#!/usr/bin/env bash
#
# Builds the test database from the development database.
#
#   scripts/make-test-db.sh            # creates <DB_DATABASE>_test, e.g. uclp_cargo_test
#
# What it does
#   1. Reads the connection from .env (the same one the application uses).
#   2. Drops and recreates the test database. It refuses any name that does not end in "_test".
#   3. Copies the structure and the working data of the development database, leaving out the
#      rows of logs, sessions, cache, queue, notifications, tokens, shifts and day closures.
#   4. Blanks every integration credential and switches every gateway off in the copy.
#
# The development database is only read. Run this again after migrating the development
# database, so the test database has the same structure. Never run "artisan migrate" with
# --env=testing: without a .env.testing file it would migrate the development database.
#
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
    echo "No .env file in $(pwd)." >&2
    exit 1
fi

# Reads one value from .env, without its surrounding quotes.
env_value() {
    local line
    line="$(grep -E "^$1=" .env | tail -n 1 || true)"
    line="${line#*=}"
    line="${line%$'\r'}"
    if [[ "$line" == \"*\" || "$line" == \'*\' ]]; then
        line="${line:1:${#line}-2}"
    fi
    printf '%s' "$line"
}

SOURCE_DB="$(env_value DB_DATABASE)"
TEST_DB="${SOURCE_DB}_test"

export PGHOST="$(env_value DB_HOST)"
export PGPORT="$(env_value DB_PORT)"
export PGUSER="$(env_value DB_USERNAME)"
export PGPASSWORD="$(env_value DB_PASSWORD)"

if [[ -z "$SOURCE_DB" || "$(env_value DB_CONNECTION)" != "pgsql" ]]; then
    echo "This script needs DB_CONNECTION=pgsql and a DB_DATABASE in .env." >&2
    exit 1
fi
if [[ "$SOURCE_DB" == *_test ]]; then
    echo "DB_DATABASE in .env is already a test database ($SOURCE_DB). Nothing to copy from." >&2
    exit 1
fi
if [[ "$TEST_DB" != *_test ]]; then
    echo "Refusing to recreate '$TEST_DB': its name does not end in _test." >&2
    exit 1
fi

# pg_dump must not be older than the server, so prefer the one that matches it.
SERVER_MAJOR="$(psql -d "$SOURCE_DB" -Atc 'show server_version_num' | cut -c1-2)"
PG_DUMP="/usr/lib/postgresql/${SERVER_MAJOR}/bin/pg_dump"
[[ -x "$PG_DUMP" ]] || PG_DUMP="pg_dump"

# Tables whose structure is copied but whose rows are not.
EMPTY_TABLES=(
    access_logs audit_logs
    sessions cache cache_locks
    jobs job_batches failed_jobs
    notification_logs notifications
    password_reset_tokens personal_access_tokens
    payment_gateway_logs
    shifts shift_overtime_requests
    business_day_closures
)

EXCLUDES=()
for table in "${EMPTY_TABLES[@]}"; do
    EXCLUDES+=("--exclude-table-data=public.${table}")
done

echo "Source : $SOURCE_DB on $PGHOST:$PGPORT (read only)"
echo "Target : $TEST_DB (dropped and recreated)"

psql -d postgres -q -v ON_ERROR_STOP=1 \
    -c "DROP DATABASE IF EXISTS \"$TEST_DB\" WITH (FORCE)" \
    -c "CREATE DATABASE \"$TEST_DB\""

"$PG_DUMP" --no-owner --no-privileges "${EXCLUDES[@]}" "$SOURCE_DB" \
    | psql -d "$TEST_DB" -q -v ON_ERROR_STOP=1 >/dev/null

# No credential leaves the development database, and no gateway is live in the copy.
psql -d "$TEST_DB" -q -v ON_ERROR_STOP=1 <<'SQL'
UPDATE integration_configs
   SET api_key = NULL, api_secret = NULL, access_token = NULL, merchant_id = NULL,
       passkey = NULL, extra_credentials = NULL, is_active = false;
SQL

TABLES="$(psql -d "$TEST_DB" -Atc "select count(*) from pg_tables where schemaname = 'public'")"
MIGRATIONS="$(psql -d "$TEST_DB" -Atc 'select count(*) from migrations')"
BOOKINGS="$(psql -d "$TEST_DB" -Atc 'select count(*) from consignments')"

echo "Done   : $TABLES tables, $MIGRATIONS migrations recorded, $BOOKINGS demo bookings copied."
echo "Run the tests with: php artisan test   (the cargo suite, on $TEST_DB)"

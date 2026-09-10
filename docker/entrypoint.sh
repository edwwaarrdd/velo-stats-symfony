#!/usr/bin/env bash
set -e

# The database lives in a bind-mounted directory, so it may not exist yet and
# may be behind the migrations. Both containers and workers run this, and
# migrating twice is a no-op.
mkdir -p /app/database /app/var
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"

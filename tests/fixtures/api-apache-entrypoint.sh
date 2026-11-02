#!/bin/sh
set -eu

runuser -u www-data -- php wizard/headless.php --use-env --db-type=sqlite3 \
  --db-database="$WEBCALENDAR_DB_DATABASE" --admin-login=api-smoke \
  --admin-password=api-smoke-password --install-password=smoke-only --force
runuser -u www-data -- php bin/api.php migrate --force
exec apache2-foreground

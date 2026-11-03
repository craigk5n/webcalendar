#!/bin/sh
set -eu

runuser -u www-data -- php wizard/headless.php --use-env --db-type=sqlite3 \
  --db-database="$WEBCALENDAR_DB_DATABASE" --admin-login=api-smoke \
  --admin-password=api-smoke-password --install-password=smoke-only --force
if [ "${API_SMOKE_PRIVATE_ENV:-false}" = "true" ]; then
  runuser -u www-data -- php -r '
    $directory = dirname(getcwd()) . "/private/" . basename(getcwd());
    mkdir($directory, 0700, true);
    file_put_contents($directory . "/.env", "WEBCALENDAR_API_ENABLED=true\n");
    chmod($directory . "/.env", 0600);
  '
fi
runuser -u www-data -- php bin/api.php migrate --force
exec apache2-foreground

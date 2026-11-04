#!/bin/sh
set -eu

if [ "${API_SMOKE_HTACCESS:-false}" = "true" ]; then
  a2disconf webcalendar-api
fi

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
if [ -n "${API_SMOKE_PUBLIC_ALIAS:-}" ]; then
  runuser -u www-data -- php tests/fixtures/api-apache-paths.php setup
  cp tests/fixtures/apache-api-public-alias.conf /etc/apache2/sites-available/000-default.conf
  runuser -u www-data -- env WEBCALENDAR_API_STORAGE=/tmp/webcalendar-api-smoke/secure/migrations \
    php bin/api.php migrate --force
else
  runuser -u www-data -- php bin/api.php migrate --force
fi
exec apache2-foreground

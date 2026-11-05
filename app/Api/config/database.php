<?php

declare(strict_types=1);

$driver = ['mysqli' => 'mysql', 'postgresql' => 'pgsql', 'sqlite3' => 'sqlite'][$GLOBALS['db_type']];
$host = $GLOBALS['db_host'] ?? 'localhost';
$port = null;
if (preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
  $host = $matches[1];
  $port = $matches[2];
}

return [
  'default' => 'webcalendar',
  'connections' => ['webcalendar' => [
    'driver' => $driver,
    'host' => $host,
    'port' => $port ?: ($driver === 'pgsql' ? '5432' : '3306'),
    'database' => $GLOBALS['db_database'],
    'username' => $GLOBALS['db_login'] ?? '',
    'password' => $GLOBALS['db_password'] ?? '',
    'charset' => $driver === 'mysql' ? 'utf8mb4' : 'utf8',
    'collation' => 'utf8mb4_bin',
    'prefix' => '',
    'foreign_key_constraints' => true,
    'busy_timeout' => 250,
  ]],
  'migrations' => ['table' => 'webcal_api_migrations', 'update_date_on_publish' => true],
];

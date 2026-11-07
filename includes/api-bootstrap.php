<?php

declare(strict_types=1);

if (!defined('WEBCALENDAR_API_BOOTSTRAP')) {
  exit;
}

defined('_ISVALID') || define('_ISVALID', 1);
set_include_path(dirname(__DIR__) . PATH_SEPARATOR . __DIR__ . PATH_SEPARATOR . get_include_path());
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/dbi4php.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/activity-log-constants.php';
require_once __DIR__ . '/translate.php';
require_once __DIR__ . '/classes/Event.php';
require_once __DIR__ . '/classes/RptEvent.php';
require_once __DIR__ . '/access.php';

require_once __DIR__ . '/recurrence-globals.php';
initialize_recurrence_globals();

// Install mode reads settings without issuing browser redirects or version updates.
do_config(true);
if (!in_array($db_type ?? '', ['mysqli', 'postgresql', 'sqlite3'], true)
    || empty($db_database)
    || !dbi_connect($db_host ?? '', $db_login ?? '', $db_password ?? '', $db_database, false)) {
  throw new RuntimeException('The API requires a configured MySQL, PostgreSQL or SQLite installation.');
}

// Account disabling and permission changes must take effect on the next request.
$db_connection_info['cachedir'] = '';
$phpdbiVerbose = false;
$version = dbi_get_cached_rows('SELECT cal_value FROM webcal_config WHERE cal_setting = ?', ['WEBCAL_PROGRAM_VERSION'], false, false);
if (!$version || $version[0][0] !== $PROGRAM_VERSION) {
  throw new RuntimeException('Complete the WebCalendar installer or upgrade before using the API.');
}

load_global_settings();
$GLOBALS['api_installation_timezone'] = $GLOBALS['TIMEZONE'];
$GLOBALS['api_reminder_timezone'] = date_default_timezone_get();
if (!in_array($user_inc, ['user.php', 'user-ldap.php'], true) || !empty($settings['use_http_auth']) || $single_user === 'Y') {
  throw new RuntimeException('API sign-in requires database or LDAP password authentication.');
}
require_once __DIR__ . '/' . $user_inc;

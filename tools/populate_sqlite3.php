<?php
/**
 * Description:
 * This script will create a SQLite v3 database for use with WebCalendar and will include the
 * default 'admin' user with 'admin' password.
 *
 * Usage:
 * php populate_sqlite3.php
 */

$outputFile = "webcalendar.salite";
$createAdminAccount = true;
$adminUsername = 'admin';
$adminPassword = 'admin';
$db_type = 'sqlite3';

// Load include files.
// If you have moved this script out of the WebCalendar directory, which you
// probably should do since it would be better for security reasons, you would
// need to change __WC_INCLUDEDIR to point to the webcalendar include directory.
define( '__WC_BASEDIR', '../' ); // Points to the base WebCalendar directory
                 // relative to current working directory.
define( '__WC_INCLUDEDIR', __WC_BASEDIR . 'includes/' );
define( '__WC_CLASSDIR', __WC_INCLUDEDIR . 'classes/' );
$old_path = ini_get ( 'include_path' );
$delim = ( strstr ( $old_path, ';' ) ? ';' : ':' );
ini_set ( 'include_path', $old_path . $delim . __WC_INCLUDEDIR . $delim );

require_once __WC_CLASSDIR . 'WebCalendar.php';
require_once __WC_CLASSDIR . 'Event.php';
require_once __WC_CLASSDIR . 'RptEvent.php';
require_once __WC_CLASSDIR . 'WebCalMailer.php';

$WebCalendar = new WebCalendar( __FILE__ );

include __WC_INCLUDEDIR . 'translate.php';
include __WC_INCLUDEDIR . 'config.php';
include __WC_INCLUDEDIR . 'dbi4php.php';
include __WC_INCLUDEDIR . 'formvars.php';
include __WC_INCLUDEDIR . 'functions.php';

$WebCalendar->initializeFirstPhase();

include __WC_INCLUDEDIR . $user_inc;
include __WC_INCLUDEDIR . 'site_extras.php';

$WebCalendar->initializeSecondPhase();

$debug = false;// Set to true to print debug info...
$only_testing = false; // Just pretend to send -- for debugging.

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database, true );
if ( ! $c ) {
  echo translate( 'Error connecting to database' ) . ': ' . dbi_error();
  exit;
}

load_global_settings();

$WebCalendar->setLanguage();

set_today();

include __WC_INCLUDEDIR . '../install/sql/tables-sqlite3.php';

function fatal($msg) {
  print "Error: $msg\n";
  exit;
}

for ($i = 1; $i < count($argv); $i++) {
  if ($argv[$i] == "-file" || $argv[$i] == "-f") {
    if (count($argv) > $i + 1) { 
      $outputFile = $argv[$i+1];
      $i++;
    } else {
      fatal("Error: -f param requires a file.");
    }
  } else if ($argv[$i] == '-noadmin') {
    $createAdminAccount = false;
  } else {
    fatal("Error: unrecognized parameter $argv[$i]");
  }
}
#var_dump($argv);

echo "SQLite3 output file: $outputFile\n";

$db_name = 'not-required';
$GLOBALS['db_type'] = 'sqlite3';
$c = dbi_connect( 'localhost', 'not-required', $db_name, $outputFile, false );

$GLOBALS['db_setup_in_progress'] = true; # don't fail if no tables exist in do_config
populate_sqlite_db($db_name, $c, false);

echo "SQLite3 database created and populated.\n";

if ($createAdminAccount) {
  $password = password_hash ( $adminPassword, PASSWORD_DEFAULT );
  $sql = 'INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) ' .
    ' VALUES ( ?, ?, ?, ?, ? )';
  $values = [$adminUsername, $password, 'Administrator', 'Default', 'Y'];
  if (! dbi_execute ($sql, $values)) {
    $error = db_error();
    echo "Error: $error\n";
    exit;
  }
  echo "Admin user created: $adminUsername\n";
}


?>

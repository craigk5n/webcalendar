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

include __WC_INCLUDEDIR . 'translate.php';
include __WC_INCLUDEDIR . 'config.php';
include __WC_INCLUDEDIR . 'dbi4php.php';
include __WC_INCLUDEDIR . 'formvars.php';
include __WC_INCLUDEDIR . 'functions.php';

$debug = false;// Set to true to print debug info...
$only_testing = false; // Just pretend to send -- for debugging.

include __WC_INCLUDEDIR . '../install/sql/tables-sqlite3.php';
include __WC_INCLUDEDIR . '../install/default_config.php';

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

$db_type = 'sqlite3';
$db_name = $outputFile;
$db_host = 'n/a';
$db_login = 'n/a';
$db_password = 'n/a';
$db_persistent = false;

$c = dbi_connect( $db_host, $db_login, $db_password, $db_name, false );

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

// Add default settings
db_load_config();
echo "Default settings saved in database.\n";

?>

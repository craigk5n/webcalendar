#!/usr/local/bin/php -q
<?php
/*
 * $Id: convert_passwords.php,v 1.8.2.6 2011/04/27 00:27:35 rjones6061 Exp $
 *
 * This script will alter the webcal_user table to allow 32 character passwords
 * and convert user passwords to PHP md5 passwords.
 *
 * It is necessary to run this to upgrade to version 0.9.43 from any version.
 *
 *
 *   ** NOTE: This script should only be run ONCE and then be deleted!!
 *
 */

/********************************************************************/

define ( '__WC_BASEDIR', '..' ); // Points to the base WebCalendar directory
                          // relative to current working directory.
define ( '__WC_INCLUDEDIR', '../includes' );

require_once  __WC_INCLUDEDIR . '/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include __WC_INCLUDEDIR . '/config.php';
include __WC_INCLUDEDIR . '/dbi4php.php';

$WebCalendar->initializeFirstPhase();
$WebCalendar->initializeSecondPhase();

$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database );
if ( ! $c ) {
  echo "Error connecting to database: " . dbi_error ();
  exit;
}

// First, look at the passwords. If we find and md5 hash in there,
// (it will have 32 chars instead of < 25 like in the old version),
// then we know this script was already run.
$sql = "SELECT cal_passwd FROM webcal_user";
$res = dbi_execute ( $sql );
$doneBefore = false;
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    if ( strlen ( $row[0] ) > 30 )
      $doneBefore = true;
  }
  dbi_free_result ( $res );
} else {
  echo "Database error: " . dbi_error ();
  exit;
}

if ( $doneBefore ) {
  echo "Passwords were already converted to md5!\n<br />\n";
  exit;
}

// See if webcal_user.cal_passwd will allow 32 characters
$sql = "DESC webcal_user";
$res = dbi_execute ( $sql );
while ( $row = dbi_fetch_row ( $res ) ) {
  if ($row[Field] == 'cal_passwd') {
    preg_match ( "/([0-9]+)/", $row[Type], $match );
    if ($match[1] < 32) {
      $sql = "ALTER TABLE webcal_user MODIFY cal_passwd VARCHAR(32) NULL";
      // Use the following on older MySQL versions
      //$sql = "ALTER TABLE webcal_user CHANGE cal_passwd cal_passwd VARCHAR(32) NULL";
      $res = dbi_execute ( $sql );
      if ($res) {
        echo "Table webcal_user altered to allow 32 character passwords.\n" .
          "<br />Converting passwords...\n<br /><br />\n";
      }
    }
  }
}
dbi_free_result ( $res );

// Convert the passwords
$sql = "SELECT cal_login, cal_passwd FROM webcal_user";
$res = dbi_execute ( $sql );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $sql2 = "UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?";
    $res2 = dbi_execute ( $sql2, array ( md5 ( $row[1] ), $row[0] ) );
    if ($res2)
      echo "Password updated for: ".$row[0]."<br />\n";
  }
  dbi_free_result ( $res );
  echo "Finished converting passwords\n<br />\n";
  echo "<br /><br />\n<h1>DO NOT Run this script again!!!</h1>\n<br />\n";
  echo '<h1>Delete this script if it ran successfully!!!</h1>';
}
?>

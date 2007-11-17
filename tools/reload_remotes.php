#!/usr/local/bin/php -q
<?php
/* $Id$
 *
 * Description:
 * This is a command-line script that will reload all user's remote calendars.
 *
 * Usage:
 * php reload_remotes.php
 *
 * Setup:
 * This script should be setup to run periodically on your system.
 * You should not run this more a once per hour for performance reasons
 *
 * To set this up in cron, add a line like the following in your crontab
 * to run it every hour:
 *   1 * * * * php /some/path/here/reload_remotes.php
 * Of course, change the path to where this script lives. If the PHP binary is
 * not in your $PATH, you may also need to provide the full path to "php".
 * On Linux, just type crontab -e to edit your crontab.
 *
 * If you're a Windows user, you'll either need to find a cron clone
 * for Windows (they're out there) or use the Windows Task Scheduler.
 * (See docs/WebCalendar-SysAdmin.html for instructions.)
 *
 * Comments:
 * You will need access to the PHP binary (command-line) rather than
 * the module-based version that is typically installed for use with
 * a web server.to build as a CGI (rather than an Apache module) for
 *
 * If running this script from the command line generates PHP
 * warnings, you can disable error_reporting by adding
 * "-d error_reporting=0" to the command line:
 *   php -d error_reporting=0 /some/path/here/tools/reload_remotes.php
 *
 *********************************************************************/

// If you have moved this script out of the WebCalendar directory, which you
// probably should do since it would be better for security reasons, you would
// need to change _WC_BASE_DIR to point to the webcalendar include directory.

// _WC_BASE_DIR points to the base WebCalendar directory relative to
// current working directory

define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );

$old_path = ini_get ( 'include_path' );
$delim = ( strstr ( $old_path, ';' ) ? ';' : ':' );
ini_set ( 'include_path', $old_path . $delim . _WC_INCLUDE_DIR . $delim );

require_once _WC_INCLUDE_DIR . 'classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include _WC_INCLUDE_DIR . 'translate.php';
include _WC_INCLUDE_DIR . 'config.php';
include _WC_INCLUDE_DIR . 'dbi4php.php';
include _WC_INCLUDE_DIR . 'functions.php';

$WC->initializeFirstPhase ();
 
include _WC_INCLUDE_DIR . 'xcal.php';

$WC->initializeSecondPhase ();
// used for hCal parsing
require_once _WC_INCLUDE_DIR . 'classes/hKit/hkit.class.php';

$debug = false; // set to true to print debug info...

// Establish a database connection.
$c = dbi_connect ( _WC_DB_HOST, _WC_DB_LOGIN, _WC_DB_PASSWORD, _WC_DB_DATABASE, true );
if ( ! $c ) {
  echo translate ( 'Error connecting to database' ) . ': ' . dbi_error ();
  exit;
}

$WC->setLanguage ();

if ( $debug )
  echo "<br />\n" . translate ( 'Include Path' )
   . ' =' . ini_get ( 'include_path' ) . "<br />\n";

if ( getPref ( 'REMOTES_ENABLED', 2 ) ) {
  $res = dbi_execute ( 'SELECT cal_login_id, cal_url, cal_admin
    FROM webcal_user WHERE cal_is_nuc = \'Y\' AND cal_url IS NOT NULL' );
  $cnt = 0;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $data = array ();
      $cnt++;
      $calUser = $row[0];
      $cal_url = $row[1];
      $login = $row[2];
      $overwrite = true;
      $type = 'remoteics';
      $data = parse_ical ( $cal_url, $type );
      // TODO it may be a vcs file
      // if ( count ( $data ) == 0 ) {
      // $data = parse_vcal ( $cal_url );
      // }
      // we may be processing an hCalendar
      if ( count ( $data ) == 0 && function_exists ( 'simplexml_load_string' ) ) {
        $h = new hKit;
        $h->tidy_mode = 'proxy';
        $result = $h->getByURL ( 'hcal', $cal_url );
        $type = 'hcal';
        $data = parse_hcal ( $result, $type );
      }
      if ( count ( $data ) && empty ( $errormsg ) ) {
        // delete existing events
        if ( $debug )
          echo "<br />\n" . translate ( 'Deleting events for' )
           . ": $calUser<br />\n";
        // Delete all events for this user.
        $WC->User->deleteUserEvents ( $calUser );
        // import new events
        if ( $debug )
          echo translate ( 'Importing events for' ) . ": $calUser<br />\n"
           . translate ( 'From' ) . ": $cal_url<br />\n";
        import_data ( $data, $overwrite, $type );
        if ( $debug )
          echo translate ( 'Events successfully imported' )
           . ": $count_suc<br /><br />\n";
      } else { // we didn't receive any data and/or there was an error
        if ( ! empty ( $errormsg ) )
          echo $errormsg . "<br />\n";

        if ( count ( $data ) == 0 )
          echo "<br />\n" . translate ( 'No data returned from' )
           . ":  $cal_url<br />\n" . translate ( 'for non-user calendar' )
           . ":  $calUser<br />\n";
      }
    }
    dbi_free_result ( $res );
  }
  if ( $cnt == 0 )
    echo "<br />\n" . translate ( 'No Remote Calendars found' );
} else
  echo "<br />\n" . translate ( 'Remote Calendars not enabled' );
// just in case
$login = '';

?>

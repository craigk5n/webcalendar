#!/usr/local/bin/php -q
<?php
/* $Id: reload_remotes.php,v 1.9.2.7 2011/04/27 00:27:35 rjones6061 Exp $
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
 * Of course, change the path to where this script lives. If the
 * php binary is not in your $PATH, you may also need to provide
 * the full path to "php".
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
// Load include files.
// If you have moved this script out of the WebCalendar directory,
// which you probably should do since it would be better for security
// reasons, you would need to change __WC_INCLUDEDIR to point to the
// webcalendar include directory.
define ( '__WC_BASEDIR', '..' ); // Points to the base WebCalendar directory
                          // relative to current working directory.
define ( '__WC_INCLUDEDIR', '../includes' );

$old_path = ini_get ( 'include_path' );
$delim = ( strstr ( $old_path, ';' ) ? ';' : ':' );
ini_set ( 'include_path', $old_path . $delim . __WC_INCLUDEDIR . $delim );

require_once __WC_INCLUDEDIR . '/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include __WC_INCLUDEDIR . '/translate.php';
include __WC_INCLUDEDIR . '/config.php';
include __WC_INCLUDEDIR . '/dbi4php.php';
include __WC_INCLUDEDIR . '/formvars.php';
include __WC_INCLUDEDIR . '/functions.php';

$WebCalendar->initializeFirstPhase ();

include __WC_INCLUDEDIR . '/' . $user_inc;
include __WC_INCLUDEDIR . '/xcal.php';

$WebCalendar->initializeSecondPhase ();
// used for hCal parsing
require_once __WC_INCLUDEDIR . '/classes/hKit/hkit.class.php';

$debug = false; // set to true to print debug info...

// Establish a database connection.
$c = dbi_connect ( $db_host, $db_login, $db_password, $db_database, true );
if ( ! $c ) {
  echo translate ( 'Error connecting to database' ) . ': ' . dbi_error ();
  exit;
}

load_global_settings ();
$WebCalendar->setLanguage ();

if ( $debug )
  echo "<br />\n" . translate ( 'Include Path' )
   . ' =' . ini_get ( 'include_path' ) . "<br />\n";

if ( $REMOTES_ENABLED == 'Y' ) {
  $res = dbi_execute ( 'SELECT cal_login, cal_url, cal_admin
    FROM webcal_nonuser_cals WHERE cal_url IS NOT NULL' );
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
        delete_events ( $calUser );
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

function delete_events ( $nid ) {
  // Get event ids for all events this user is a participant
  $events = get_users_event_ids ( $nid );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT(*) FROM webcal_entry_user
      WHERE cal_id = ?', array ( $events[$i] ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
          $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0, $cnt = count ( $delete_em ); $i < $cnt; $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_import_data WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_ext_user WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id =? ',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_blob WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
  }
  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
    array ( $nid ) );
}

?>

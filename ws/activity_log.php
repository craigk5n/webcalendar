<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality to get the activity log.
 *	Uses XML (but not SOAP at this point since that would be
 *      overkill and require extra packages to install).
 *
 * Parameters:
 *	startid* - Optional first id to start list from
 *	num* - Number of entries to return
 *
 * Comments:
 *	Client apps must use the same authentication as the web browser.
 *	If WebCalendar is setup to use web-based authentication, then
 *	the login.php found in this directory should be used to obtain
 *	a session cookie.
 *
 * Developer Notes:
 *	If you enable the WS_DEBUG option below, all data will be written
 *	to a debug file in /tmp also.
 *
 */

$WS_DEBUG = false;

$MAX_ENTRIES = 1000; // do not allow a client to ask for more than this.

require_once "ws.php";

// Initialize...
ws_init ();

$startid = getGetValue ( 'startid' );
$num = getGetValue ( 'num' );
if ( empty ( $num ) || $num < 0 || $num > 500 )
  $num = 500;

if ( $num < $MAX_ENTRIES )
  $num = $MAX_ENTRIES;

Header ( "Content-type: text/xml" );
//Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = '';

// If login is public user, make sure public can view others...
if ( $login == "__public__" && $login != $user ) {
  if ( $public_access_others != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</events>\n";
    exit;
  }
}

// TODO: move this SQL along with the SQL in activity_log.php to a shared
// function.
$sql = "SELECT webcal_entry_log.cal_login, webcal_entry_log.cal_user_cal, " .
  "webcal_entry_log.cal_type, webcal_entry_log.cal_date, " .
  "webcal_entry_log.cal_time, " .
  "webcal_entry.cal_name, webcal_entry_log.cal_log_id " .
  "FROM webcal_entry_log, webcal_entry " .
  "WHERE webcal_entry_log.cal_entry_id = webcal_entry.cal_id ";
if ( ! empty ( $startid ) )
  $sql .= "AND webcal_entry_log.cal_log_id <= $startid ";
$sql .= "ORDER BY webcal_entry_log.cal_log_id DESC";
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( "SQL> " . $sql . "\n\n" );
$res = dbi_query ( $sql );

$out .= "<activitylog>\n";
if ( $res ) {
  $out .= "<!-- in if -->\n";
  $cnt = 0;
  while ( ( $row = dbi_fetch_row ( $res ) ) && $cnt < $num ) {
    $out .= "<!-- in while type: $row[2] -->\n";
    $out .= "  <log>\n" .
           "    <login>" . ws_escape_xml ( $row[0] ) . "</login>\n" .
           "    <calendar>" . ws_escape_xml ( $row[1] ) . "</calendar>\n" .
           "    <type>" . ws_escape_xml ( $row[2] ) . "</type>\n" .
           "    <date>" . ws_escape_xml ( $row[3] ) . "</date>\n" .
           "    <time>" . ws_escape_xml ( $row[4] ) . "</time>\n" .
           "    <action>" . ws_escape_xml ( $row[5] ) . "</action>\n" .
           "    <id>" . ws_escape_xml ( $row[6] ) . "</id>\n" .
           "  </log>\n";
    $cnt++;
  }
  dbi_free_result ( $res );
} else {
  $out .= "<error>" . ws_escape_xml ( dbi_error () ) . "</error>\n";
}
$out .= "</activitylog>\n";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>

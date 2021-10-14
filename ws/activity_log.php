<?php
/**
 * Description:
 *  Web Service functionality to get the activity log.
 *  Uses REST-style Web Services.
 *
 * Parameters:
 *  startid* - Optional first id to start list from
 *  num*     - Number of entries to return
 *
 * Comments:
 *  Client apps must use the same authentication as the web browser.
 *  If WebCalendar is setup to use web-based authentication, then the login.php
 *  found in this directory should be used to obtain a session cookie.
 *
 * Developer Notes:
 *  If you enable the WS_DEBUG option below,
 *  all data will be written to a debug file in /tmp also.
 */

$WS_DEBUG = false;

$MAX_ENTRIES = 1000; // Do not allow a client to ask for more than this.

require_once 'ws.php';

// Initialize...
ws_init();

$num = getGetValue ( 'num' );
$startid = getGetValue ( 'startid' );
if ( empty ( $num ) || $num < 0 )
  $num = 100;

if ( $num > $MAX_ENTRIES )
  $num = $MAX_ENTRIES;

header ( 'Content-type: text/xml' );
// header ( 'Content-type: text/plain' );

echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";

// If login is public user, make sure public can view others...
if ( $login == '__public__' && $login != $user ) {
  if ( $PUBLIC_ACCESS_OTHERS != 'Y' ) {
    $out = '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
    exit;
  }
}

// TODO: Move this SQL along with the SQL in activity_log.php to a shared function.
$sql_params = array();
$sql = 'SELECT wel.cal_login, wel.cal_user_cal, wel.cal_type, wel.cal_date,
  wel.cal_time, we.cal_name, wel.cal_log_id
  FROM webcal_entry_log wel, webcal_entry we WHERE wel.cal_entry_id = we.cal_id ';
if ( ! empty ( $startid ) ) {
  $sql .= 'AND wel.cal_log_id <= ? ';
  $sql_params[] = $startid;
}
$sql .= 'ORDER BY wel.cal_log_id DESC';
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( 'SQL> ' . $sql . "\n\n" );

$res = dbi_execute ( $sql, $sql_params );

$out = '
<activitylog>';

if ( $res ) {
  $out .= '
<!-- in if -->';
  $cnt = 0;
  while ( ( $row = dbi_fetch_row ( $res ) ) && $cnt < $num ) {
    $out .= '
<!-- in while type: $row[2] -->
  <log>
    <login>' . ws_escape_xml ( $row[0] ) . '</login>
    <calendar>' . ws_escape_xml ( $row[1] ) . '</calendar>
    <type>' . ws_escape_xml ( $row[2] ) . '</type>
    <date>' . ws_escape_xml ( $row[3] ) . '</date>
    <time>' . ws_escape_xml ( $row[4] ) . '</time>
    <action>' . ws_escape_xml ( $row[5] ) . '</action>
    <id>' . ws_escape_xml ( $row[6] ) . '</id>
  </log>
';
    $cnt++;
  }
  dbi_free_result ( $res );
} else
  $out .= '
  <error>' . ws_escape_xml ( dbi_error() ) . '</error>';

$out .= '
</activitylog>
';
// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

?>

<?php
/**
 * Description:
 *  Web Service functionality to get events.
 *  Uses XML (but not SOAP at this point since that would be
 *       overkill and require extra packages to install).
 *
 * Comments:
 *  Client apps must use the same authentication as the web browser. If
 *  WebCalendar is setup to use web-based authentication, then the login.php
 *  found in this directory should be used to obtain a session cookie.
 *
 * Developer Notes:
 *  If you enable the WS_DEBUG option below,
 *  all data will be written to a debug file in /tmp also.
 */

$WS_DEBUG = false;

require_once 'ws.php';

// Initialize...
ws_init();

// header ( 'Content-type: text/xml' );
header ( 'Content-type: text/plain' );

echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";

$out = '
<events>';

// If login is public user, make sure public can view others...
if ( $login == '__public__' && $login != $user ) {
  if ( $PUBLIC_ACCESS_OTHERS != 'Y' ) {
    $out .= '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
    exit;
  }
  // $out .= '<!-- Allowing public user to view other users calendar -->';
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    $out .= '
  <error>' . translate ( 'Not authorized' ) . '</error>
</events>
';
    exit;
  }
  // $out .= '<!-- Allowing user to view other users calendar -->';
}

$startdate = getValue ( 'startdate' );
$enddate = getValue ( 'enddate' );

if ( empty ( $startdate ) )
  $startdate = date ( 'Ymd' );

if ( empty ( $enddate ) )
  $enddate = $startdate;

// Now read all the repeating events (for all users).
$repeated_events = query_events ( $user, true,
  'AND ( wer.cal_end > ' . $startdate . ' OR wer.cal_end IS NULL )' );

// Read non-repeating events (for all users).
if ( $WS_DEBUG )
  $out .= '
<!-- ' . str_replace ( ['XXX', 'YYY', 'ZZZ'],
    [$user, $startdate, $enddate],
    translate ( 'Checking for events for XXX from date YYY to date ZZZ.' ) )
   . ' -->
';

$events = read_events ( $user, date_to_epoch ( $startdate ),
  date_to_epoch ( $enddate ) );

if ( $WS_DEBUG )
  $out .= '
<!-- ' . str_replace ( 'XXX', count ( $events ),
    translate ( 'Found XXX events in time range.' ) ) . ' -->
';

/* Process an event for a single day. Check to see if it has a reminder,
 * when it needs to be sent and when the last time it was sent.
 */
function process_event ( $id, $name, $event_date, $event_time ) {
  global $out, $WS_DEBUG;

  if ( $WS_DEBUG )
    ws_log_message ( str_replace ( ['XXX', 'YYY', 'ZZZ', 'AAA'],
        [$id, $name, $event_time, $event_date],
        translate ( 'Event id=XXX YYY at ZZZ on AAA.' ) ) );

  return ws_print_event_xml ( $id, $event_date );
}

// $out .= '<!-- events for user "'.$user.'", login "'.$login.'" -->
// <!-- date range: '."$startdate - $enddate -->\n";

$starttime = mktime ( 0, 0, 0,
  substr ( $startdate, 4, 2 ),
  substr ( $startdate, 6, 2 ),
  substr ( $startdate, 0, 4 ) );
$endtime = mktime ( 0, 0, 0,
  substr ( $enddate, 4, 2 ),
  substr ( $enddate, 6, 2 ),
  substr ( $enddate, 0, 4 ) );

for ( $d = $starttime; $d <= $endtime; $d += 86400 ) {
  $completed_ids = [];
  $date = date ( 'Ymd', $d );
  // $out .= "Date: $date\n";
  // Get non-repeating events for this date.
  // An event will be included one time for each participant.
  $ev = get_entries ( $date );
  // Keep track of duplicates.
  $completed_ids = [];
  for ( $i = 0, $evCnt = count ( $ev ); $i < $evCnt; $i++ ) {
    $id = $ev[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $out .= process_event( $id, $ev[$i]->getName(), $date,
      $ev[$i]->getTime() );
  }
  $rep = get_repeating_entries ( $user, $date );
  for ( $i = 0, $repCnt = count ( $rep ); $i < $repCnt; $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $out .= process_event( $id, $rep[$i]->getName(), $date,
      $rep[$i]->getTime() );
  }
}

$out .= '
</events>
';

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

?>

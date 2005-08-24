<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality to get events.
 *	Uses XML (but not SOAP at this point since that would be
 *      overkill and require extra packages to install).
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

require_once "ws.php";

// Initialize...
ws_init ();

//Header ( "Content-type: text/xml" );
Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = "<events>\n";

// If login is public user, make sure public can view others...
if ( $login == "__public__" && $login != $user ) {
  if ( $public_access_others != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</events>\n";
    exit;
  }
  //$out .= "<!-- Allowing public user to view other user's calendar -->\n";
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $allow_view_other != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</events>\n";
    exit;
  }
  //$out .= "<!-- Allowing user to view other user's calendar -->\n";
}

$startdate = getValue ( 'startdate' );
$enddate = getValue ( 'enddate' );

if ( empty ( $startdate ) )
  $startdate = date ( "Ymd" );
if ( empty ( $enddate ) )
  $enddate = $startdate;

// Now read events all the repeating events (for all users)
$repeated_events = query_events ( $user, true,
  "AND (webcal_entry_repeats.cal_end > $startdate OR " .
  "webcal_entry_repeats.cal_end IS NULL) " );

// Read non-repeating events (for all users)
if ( $WS_DEBUG )
  $out .= "<!-- Checking for events for $user from date $startdate to date $enddate -->\n";
$events = read_events ( $user, $startdate, $enddate );
if ( $WS_DEBUG )
  $out .= "<!-- Found " . count ( $events ) . " events in time range. -->\n";



// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $event_date, $event_time ) {
  global $WS_DEBUG, $out;

  if ( $WS_DEBUG )
    ws_log_message ( "Event id=$id \"$name\" at $event_time on $event_date" );

  return ws_print_event_xml ( $id, $event_date );
}


//$out .= "<!-- events for user \"$user\", login \"$login\" -->\n";
//$out .= "<!-- date range: $startdate - $enddate -->\n";

$startyear = substr ( $startdate, 0, 4 );
$startmonth = substr ( $startdate, 4, 2 );
$startday = substr ( $startdate, 6, 2 );
$endyear = substr ( $enddate, 0, 4 );
$endmonth = substr ( $enddate, 4, 2 );
$endday = substr ( $enddate, 6, 2 );

$starttime = mktime ( 0, 0, 0, $startmonth, $startday, $startyear );
$endtime = mktime ( 0, 0, 0, $endmonth, $endday, $endyear );

for ( $d = $starttime; $d <= $endtime; $d += ONE_DAY ) {
  $completed_ids = array ();
  $date = date ( "Ymd", $d );
  //$out .= "Date: $date\n";
  // Get non-repeating events for this date.
  // An event will be included one time for each participant.
  $ev = get_entries ( $user, $date );
  // Keep track of duplicates
  $completed_ids = array ( );
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    $id = $ev[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $out .= process_event ( $id, $ev[$i]->getName(), $date,
      $ev[$i]->getTime() );
  }
  $rep = get_repeating_entries ( $user, $date );
  for ( $i = 0; $i < count ( $rep ); $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $out .= process_event ( $id, $rep[$i]->getName(), $date,
      $rep[$i]->getTime() );
  }
}

$out .= "</events>";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>

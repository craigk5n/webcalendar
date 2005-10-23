<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality for reminders.
 *	Uses XML (but not SOAP at this point since that would be
 *      overkill and require extra packages to install).
 *
 * Comments:
 *	Some of this code was borrowed from send_reminders.php.
 *
 *	This functionality works somewhat independent of the email-based
 *	send_reminders.php script.  If the end user intends to use
 *	client-side reminders, they should set "Event Reminders" to "No"
 *	in the "Email" section on the Prefernces page.
 *
 *	This is read-only for the client side, so the client must
 *	keep track of whether or not they have displayed the reminder
 *	to the user.  (No where in the database will it be recorded that
 *	the user received a reminder through this functionality.)
 *
 *	Client apps must use the same authentication as the web browser.
 *	If WebCalendar is setup to use web-based authentication, then
 *	the login.php found in this directory should be used to obtain
 *	a session cookie.
 *
 */

// How many days ahead should we look for events.
// To handle case of an event 30 days from now where the user asked
// for a reminder 30 days before the event.
$DAYS_IN_ADVANCE = 30;
//$DAYS_IN_ADVANCE = 365;


// Show reminders for the next N days
$CUTOFF = 7;

$WS_DEBUG = false;

require_once "ws.php";

// Initialize...
ws_init ();

Header ( "Content-type: text/xml" );
//Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = "<reminders>\n";

// If login is public user, make sure public can view others...
if ( $login == "__public__" && $login != $user ) {
  if ( $PUBLIC_ACCESS_OTHERS != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</reminders>\n";
    echo $out;
    exit;
  }
  $out .= "<!-- Allowing public user to view other user's calendar -->\n";
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    $out .= "<error>" . translate("Not authorized") . "</error>\n";
    $out .= "</reminders>\n";
    echo $out;
    exit;
  }
  $out .= "<!-- Allowing user to view other user's calendar -->\n";
}

// Make sure this user has enabled email reminders.
//if ( $EMAIL_REMINDER == 'N' ) {
//  $out .= "Error: email reminders disabled for user \"$user\"\n";
//  dbi_close ( $c );
//  exit;
//}

$startdate = date ( "Ymd" );
$enddate = date ( "Ymd", time() + ( $DAYS_IN_ADVANCE * 24 * 3600 ) );

// Now read events all the repeating events
$repeated_events = query_events ( $user, true,
  "AND (webcal_entry_repeats.cal_end > $startdate OR " .
  "webcal_entry_repeats.cal_end IS NULL) " );

// Read non-repeating events
$events = read_events ( $user, $startdate, $enddate );
if ( $WS_DEBUG )
  ws_log_message ( "Found " . count ( $events ) . " events in time range." );



// Send a reminder for a single event for a single day.
function process_reminder ( $id, $event_date, $remind_time ) {
  global $site_extras, $WS_DEBUG,
    $SERVER_URL, $APPLICATION_NAME, $single_user, $single_user_login,
    $DISABLE_PRIORITY_FIELD, $DISABLE_ACCESS_FIELD,
    $DISABLE_PARTICIPANTS_FIELD;

  $out = "<reminder>\n";

  $out .= "  <remindDate>" . date ( "Ymd", $remind_time ) . "</remindDate>\n";
  $out .= "  <remindTime>" . date ( "Hi", $remind_time ) . "</remindTime>\n";
  $out .= "  <untilRemind>" . ( $remind_time - time() ) . "</untilRemind>\n";
  $out .= ws_print_event_xml ( $id, $event_date );

  $out .= "</reminder>\n";
  return $out;
}



// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $event_date, $event_time ) {
  global $site_extras;
  global $CUTOFF, $WS_DEBUG;
  $out = '';
  $debug = '';

  $debug .= "Event id=$id \"$name\" at $event_time on $event_date\n";
  $debug .= "No site_extras: " . count ( $site_extras ) . "\n";

  // Check to see if this event has any reminders
  $extras = get_site_extra_fields ( $id );
  for ( $j = 0; $j < count ( $site_extras ); $j++ ) {
    $extra_name = $site_extras[$j][0];
    $extra_type = $site_extras[$j][2];
    $extra_arg1 = $site_extras[$j][3];
    $extra_arg2 = $site_extras[$j][4];
    if ( ! empty ( $extras[$extra_name]['cal_remind'] ) ) {
      $debug .= "  Reminder set for event.\n";
      // how many minutes before event should we send the reminder?
      $ev_h = (int) ( $event_time / 10000 );
      $ev_m = ( $event_time / 100 ) % 100;
      $ev_year = substr ( $event_date, 0, 4 );
      $ev_month = substr ( $event_date, 4, 2 );
      $ev_day = substr ( $event_date, 6, 2 );
      $event_time = mktime ( $ev_h, $ev_m, 0, $ev_month, $ev_day, $ev_year );
      if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
        $minsbefore = $extras[$extra_name]['cal_data'];
        $remind_time = $event_time - ( $minsbefore * 60 );
      } else if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
        $rd = $extras[$extra_name]['cal_date'];
        $r_year = substr ( $rd, 0, 4 );
        $r_month = substr ( $rd, 4, 2 );
        $r_day = substr ( $rd, 6, 2 );
        $remind_time = mktime ( 0, 0, 0, $r_month, $r_day, $r_year );
      } else {
        $minsbefore = $extra_arg1;
        $remind_time = $event_time - ( $minsbefore * 60 );
      }
      $debug .= "  Mins Before: $minsbefore\n";
      $debug .= "  Event time is: " . date ( "m/d/Y H:i", $event_time ) . "\n";
      $debug .= "  Remind time is: " . date ( "m/d/Y H:i", $remind_time ) . "\n";
      // Send a reminder
      if ( time() >= $remind_time - ( $CUTOFF * 24 * 3600 ) ) {
        if ( $debug )
          $debug .= "  SENDING REMINDER! \n";
        $out .= process_reminder ( $id, $event_date, $remind_time );
      }
    }
  }
  if ( $WS_DEBUG )
    ws_log_message ( $debug );

  return $out;
}


$out .= "<!-- reminders for user \"$user\", login \"$login\" -->\n";

$startdate = time(); // today
for ( $d = 0; $d < $DAYS_IN_ADVANCE; $d++ ) {
  $date = date ( "Ymd", time() + ( $d * 24 * 3600 ) );
  //echo "Date: $date\n";
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

$out .= "</reminders>\n";

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>

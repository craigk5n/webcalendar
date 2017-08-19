<?php
/**
 * Description:
 *  Web Service functionality for reminders.
 *  Uses XML (but not SOAP at this point since that would be
 *       overkill and require extra packages to install).
 *
 * Comments:
 *  Some of this code was borrowed from send_reminders.php.
 *
 *  This functionality works somewhat independent of the email-based
 *  send_reminders.php script. If the end user intends to use
 *  client-side reminders, they should set "Event Reminders" to "No"
 *  in the "Email" section on the Prefernces page.
 *
 *  This is read-only for the client side, so the client must
 *  keep track of whether or not they have displayed the reminder
 *  to the user. (No where in the database will it be recorded that
 *  the user received a reminder through this functionality.)
 *
 *  Client apps must use the same authentication as the web browser. If
 *  WebCalendar is setup to use web-based authentication, then the login.php
 *  found in this directory should be used to obtain a session cookie.
 */

// How many days ahead should we look for events?
// To handle case of an event 30 days from now where the user asked
// for a reminder 30 days before the event.
$DAYS_IN_ADVANCE = 30;
// $DAYS_IN_ADVANCE = 365;

// Show reminders for the next N days.
$CUTOFF = 7;

$WS_DEBUG = false;

require_once 'ws.php';

// Initialize...
ws_init();

header ( 'Content-type: text/xml' );
// header ( "Content-type: text/plain" );

echo '<?xml version="1.0" encoding="UTF-8"?' . ">\n";

$out = '
<reminders>';

// If login is public user, make sure public can view others...
if ( $login == '__public__' && $login != $user ) {
  if ( $PUBLIC_ACCESS_OTHERS != 'Y' ) {
    echo '
  <error>' . translate ( 'Not authorized' ) . '</error>
</reminders>
';
    exit;
  }
  $out .= '
<!-- ' . str_replace ( 'XXX', translate ( 'public' ),
    translate ( 'Allowing XXX user to view other users calendar.' ) )
   . ' -->';
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    echo '
  <error>' . translate ( 'Not authorized' ) . '</error>
</reminders>
';
    exit;
  }
  $out .= '
<!-- ' . str_replace ( 'XXX ', '',
    translate ( 'Allowing XXX user to view other users calendar.' ) ) . ' -->';
}

// Make sure this user has enabled email reminders.
// if ( $EMAIL_REMINDER == 'N' ) {
// $out .= str_replace ('XXX', $user,
// translate ( 'Error Email reminders disabled for user XXX.' ) );
// dbi_close ( $c );
// exit;
// }

$startdate = time();
$enddate = $DAYS_IN_ADVANCE * 86400 + $startdate;

// Now read all the repeating events.
$repeated_events = query_events ( $user, true,
  'AND ( wer.cal_end > ' . $startdate . ' OR wer.cal_end IS NULL )' );

// Read non-repeating events.
$events = read_events ( $user, $startdate, $enddate );
if ( $WS_DEBUG )
  ws_log_message ( str_replace ( 'XXX', count ( $events ),
      translate ( 'Found XXX events in time range.' ) ) );

/**
 * Send a reminder for a single event for a single day.
 */
function process_reminder ( $id, $event_date, $remind_time ) {
  global $DISABLE_ACCESS_FIELD, $DISABLE_PARTICIPANTS_FIELD,
  $DISABLE_PRIORITY_FIELD, $SERVER_URL, $single_user,
  $single_user_login, $site_extras, $WS_DEBUG;

  return '
<reminder>
  <remindDate>' . date ( 'Ymd', $remind_time ) . '</remindDate>
  <remindTime>' . date ( 'Hi', $remind_time ) . '</remindTime>
  <untilRemind>' . ( $remind_time - time() ) . '</untilRemind>
  ' . ws_print_event_xml ( $id, $event_date ) . '
</reminder>
';
}

/**
 *
 * Process an event for a single day. Check to see if it has a reminder,
 * when it needs to be sent and when the last time it was sent.
 */
function process_event ( $id, $name, $event_date, $event_time ) {
  global $CUTOFF, $site_extras, $WS_DEBUG;
  $out = '';

  $debug = str_replace ( ['XXX', 'YYY', 'ZZZ', 'AAA'],
    [$id, $name, $event_time, $event_date],
    translate ( 'Event id=XXX YYY at ZZZ on AAA.' ) ) . "\n"
   . str_replace ( 'XXX', count ( $site_extras ),
    translate ( 'Number of site_extras XXX.' ) );

  // Check to see if this event has any reminders.
  $extras = get_site_extra_fields ( $id );
  for ( $j = 0, $seCnt = count ( $site_extras ); $j < $seCnt; $j++ ) {
    $extra_name = $site_extras[$j][0];
    $extra_type = $site_extras[$j][2];
    $extra_arg1 = $site_extras[$j][3];
    $extra_arg2 = $site_extras[$j][4];

    if ( ! empty ( $extras[$extra_name]['cal_remind'] ) ) {
      $debug .= "\n" . translate ( 'Reminder set for event.' );
      // How many minutes before event should we send the reminder?
      $event_time = mktime ( intval ( $event_time / 10000 ),
        ( $event_time / 100 ) % 100, 0,
        substr ( $event_date, 4, 2 ),
        substr ( $event_date, 6, 2 ),
        substr ( $event_date, 0, 4 ) );

      if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
        $minsbefore = $extras[$extra_name]['cal_data'];
        $remind_time = $event_time - ( $minsbefore * 60 );
      } elseif ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
        $rd = $extras[$extra_name]['cal_date'];
        $remind_time = mktime ( 0, 0, 0,
          substr ( $rd, 4, 2 ),
          substr ( $rd, 6, 2 ),
          substr ( $rd, 0, 4 ) );
      } else {
        $minsbefore = $extra_arg1;
        $remind_time = $event_time - ( $minsbefore * 60 );
      }
      $debug .= '
  ' . str_replace ( 'XXX', $minsbefore, translate ( 'Mins Before XXX.' ) ) . '
  ' . str_replace ( 'XXX', date ( 'm/d/Y H:i', $event_time ),
        translate ( 'Event time is XXX.' ) ) . '
  ' . str_replace ( 'XXX', date ( 'm/d/Y H:i', $remind_time ),
        translate ( 'Remind time is XXX.' ) );
      // Send a reminder.
      if ( time() >= $remind_time - ( $CUTOFF * 86400 ) ) {
        if ( $debug )
          $debug .= '
  SENDING REMINDER!';

        $out .= process_reminder ( $id, $event_date, $remind_time );
      }
    }
  }
  if ( $WS_DEBUG )
    ws_log_message ( $debug );

  return $out;
}

$out .= '
<!-- ' . str_replace ( ['XXX', 'YYY'], [$user, $login],
  translate ( 'Reminders for user XXX, login YYY.' ) ) . ' -->
';

$startdate = time(); // today
for ( $d = 0; $d < $DAYS_IN_ADVANCE; $d++ ) {
  $date = date ( 'Ymd', time() + ( $d * 86400 ) );
  // echo "Date: $date\n";
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
    $out .= process_event ( $id, $ev[$i]->getName(),
      $date, $ev[$i]->getTime() );
  }
  $rep = get_repeating_entries ( $user, $date );
  for ( $i = 0, $repCnt = count ( $rep ); $i < $repCnt; $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    $out .= process_event ( $id, $rep[$i]->getName(), $date,
      $rep[$i]->getTime() );
  }
}

$out .= '
</reminders>
';

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

?>

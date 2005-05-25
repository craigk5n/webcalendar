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


// Load include files.
$basedir = ".."; // points to the base WebCalendar directory relative to
                 // current working directory
$includedir = "../includes";

include "$includedir/config.php";
include "$includedir/php-dbi.php";
include "$includedir/functions.php";
include "$includedir/$user_inc";
include "$includedir/validate.php";
include "$includedir/connect.php";
load_global_settings ();
load_user_preferences ();
include "$includedir/site_extras.php";

include "$includedir/translate.php";

$debug = false; // set to true to print debug info...

Header ( "Content-type: text/xml" );
//Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<reminders>\n";

// If login is public user, make sure public can view others...
if ( $login == "__public__" && $login != $user ) {
  if ( $public_access_others != 'Y' ) {
    echo "<error>" . translate("Not authorized") . "</error>\n";
    echo "</reminders>\n";
    exit;
  }
  echo "<!-- Allowing public user to view other user's calendar -->\n";
}

if ( empty ( $user ) )
  $user = $login;

// If viewing different user then yourself...
if ( $login != $user ) {
  if ( $allow_view_other != 'Y' ) {
    echo "<error>" . translate("Not authorized") . "</error>\n";
    echo "</reminders>\n";
    exit;
  }
  echo "<!-- Allowing user to view other user's calendar -->\n";
}

// Make sure this user has enabled email reminders.
//if ( $EMAIL_REMINDER == 'N' ) {
//  echo "Error: email reminders disabled for user \"$user\"\n";
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
if ( $debug )
  echo "Checking for events for $user from date $startdate to date $enddate\n";
$events = read_events ( $user, $startdate, $enddate );
if ( $debug )
  echo "Found " . count ( $events ) . " events in time range.\n";


function indent ( $str ) {
  return "  " . str_replace ( "\n", "\n  ", $str );
}


function escapeXml ( $str )
{
  return ( str_replace ( "<", "&lt;", str_replace ( ">", "&gt;", $str ) ) );
}

// Send a reminder for a single event for a single day.
function list_reminder ( $id, $event_date, $remind_time ) {
  global $site_extras, $debug,
    $server_url, $application_name;

  $pri[1] = translate("Low");
  $pri[2] = translate("Medium");
  $pri[3] = translate("High");

  // get participants first...
 
  $sql = "SELECT cal_login FROM webcal_entry_user " .
    "WHERE cal_id = $id AND cal_status IN ('A','W') " .
    "ORDER BY cal_login";
  $res = dbi_query ( $sql );
  $participants = array ();
  $num_participants = 0;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$num_participants++] = $row[0];
    }
  }

  // get external participants
  $ext_participants = array ();
  $num_ext_participants = 0;
  if ( ! empty ( $allow_external_users ) && $allow_external_users == "Y" &&
    ! empty ( $external_reminders ) && $external_reminders == "Y" ) {
    $sql = "SELECT cal_fullname, cal_email FROM webcal_entry_ext_user " .
      "WHERE cal_id = $id AND cal_email IS NOT NULL " .
      "ORDER BY cal_fullname";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $ext_participants[$num_ext_participants] = $row[0];
        $ext_participants_email[$num_ext_participants++] = $row[1];
      }
    }
  }

  if ( ! $num_participants && ! $num_ext_participants ) {
    if ( $debug )
      echo "No participants found for event id: $id\n";
    return;
  }


  // get event details
  $res = dbi_query (
    "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description FROM webcal_entry WHERE cal_id = $id" );
  if ( ! $res ) {
    echo "Db error: could not find event id $id.\n";
    return;
  }


  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    echo "Error: could not find event id $id in database.\n";
    return;
  }

  $create_by = $row[0];
  $name = $row[9];
  $description = $row[10];

  echo "<reminder>\n";
  echo "  <remindDate>" . date ( "Ymd", $remind_time ) . "</remindDate>\n";
  echo "  <remindTime>" . date ( "Hi", $remind_time ) . "</remindTime>\n";
  echo "  <untilRemind>" . ( $remind_time - time() ) . "</untilRemind>\n";
  echo "  <event>\n";
  echo "  <id>$id</id>\n";
  echo "  <name>" . escapeXml ( $name ) . "</name>\n";
  if ( ! empty ( $server_url ) ) {
    if ( substr ( $server_url, -1, 1 ) == "/" ) {
      echo "  <url>" .  $server_url . "view_entry.php?id=" . $id . "</url>\n";
    } else {
      echo "  <url>" .  $server_url . "/view_entry.php?id=" . $id . "</url>\n";
    }
  }
  echo "  <description>" . escapeXml ( $description ) . "</description>\n";
  echo "  <dateFormatted>" . date_to_str ( $event_date ) . "</dateFormatted>\n";
  echo "  <date>" . $event_date . "</date>\n";
  if ( $row[2] >= 0 ) {
    echo "  <time>" . sprintf ( "%04d", $row[2] / 100 ) . "</time>\n";
    echo "  <timeFormatted>" . display_time ( $row[2] ) . "</timeFormatted>\n";
  }
  if ( $row[5] > 0 )
    echo "  <duration>" . $row[5] . "</duration>\n";
  if ( ! $disable_priority_field )
    echo "  <priority>" . $pri[$row[6]] . "</priority>\n";
  if ( ! $disable_access_field )
    echo "  <access>" . 
      ( $row[8] == "P" ? translate("Public") : translate("Confidential") ) .
      "</access>\n";
  if ( ! strlen ( $single_user_login ) )
    echo "  <createdBy>" . $row[0] . "</createdBy>\n";
  echo "  <updateDate>" . date_to_str ( $row[3] ) . "</updateDate>\n";
  echo "  <updateTime>" . display_time ( $row[4] ) . "</updateTime>\n";

  // site extra fields
  $extras = get_site_extra_fields ( $id );
  echo "  <siteExtras>\n";
  for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_descr = $site_extras[$i][1];
    $extra_type = $site_extras[$i][2];
    if ( $extras[$extra_name]['cal_name'] != "" ) {
      $tag = preg_replace ( "/[^A-Za-z0-9]+/", "", translate ( $extra_descr ) );
      $tag = strtolower ( $tag );
      $tagname = str_replace ( '"', '', $extra_name );
      echo "    <siteExtra>\n";
      echo "      <number>$i</number>\n";
      echo "      <name>" . escapeXml ( $extra_name ) . "</name>\n";
      echo "      <description>" . escapeXml ( $extra_descr ) . "</description>\n";
      echo "      <type>" . $extra_type . "</type>\n";
      echo "      <value>";
      if ( $extra_type == EXTRA_DATE ) {
        //echo date_to_str ( $extras[$extra_name]['cal_date'] );
        echo $extras[$extra_name]['cal_date'];
      } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
        echo escapeXml ( $extras[$extra_name]['cal_data'] );
      } else if ( $extra_type == EXTRA_REMINDER ) {
        echo ( $extras[$extra_name]['cal_remind'] > 0 ?
          translate("Yes") : translate("No") );
      } else {
        // default method for EXTRA_URL, EXTRA_TEXT, etc...
        echo escapeXml ( $extras[$extra_name]['cal_data'] );
      }
      echo "</value>\n    </siteExtra>\n";
    }
  }
  echo "  </siteExtras>\n";
  if ( $single_user != "Y" && ! $disable_participants_field ) {
    echo "  <participants>\n";
    for ( $i = 0; $i < count ( $participants ); $i++ ) {
      echo "    <participant>" .  $participants[$i] .
        "</participant>\n";
    }
    for ( $i = 0; $i < count ( $ext_participants ); $i++ ) {
      echo "    <participant>" . $ext_participants[$i] .
        "</participant>\n";
    }
    echo "  </participants>\n";
  }
  echo "  </event>\n";
  echo "</reminder>\n";

}



// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $event_date, $event_time ) {
  global $site_extras, $debug;
  global $CUTOFF;

  if ( $debug )
    printf ( "Event %d: \"%s\" at %s on %s \n",
      $id, $name, $event_time, $event_date );

  // Check to see if this event has any reminders
  $extras = get_site_extra_fields ( $id );
  for ( $j = 0; $j < count ( $site_extras ); $j++ ) {
    $extra_name = $site_extras[$j][0];
    $extra_type = $site_extras[$j][2];
    $extra_arg1 = $site_extras[$j][3];
    $extra_arg2 = $site_extras[$j][4];
    //if ( $debug )
    //  printf ( "  name: %s\n  type: %d\n  arg1: %s\n  arg2: %s\n",
    //  $extra_name, $extra_type, $extra_arg1, $extra_arg2 );
    if ( ! empty ( $extras[$extra_name]['cal_remind'] ) ) {
      if ( $debug )
        echo "  Reminder set for event. \n";
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
      if ( $debug )
        echo "  Mins Before: $minsbefore \n";
      if ( $debug ) {
        echo "  Event time is: " . date ( "m/d/Y H:i", $event_time ) . "\n";
        echo "  Remind time is: " . date ( "m/d/Y H:i", $remind_time ) . "\n";
      }
      // Send a reminder
      if ( time() >= $remind_time - ( $CUTOFF * 24 * 3600 ) ) {
        if ( $debug )
          echo "  SENDING REMINDER! \n";
        list_reminder ( $id, $event_date, $remind_time );
      }
    }
  }
}


echo "<!-- reminders for user \"$user\", login \"$login\" -->\n";

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
    $id = $ev[$i]['cal_id'];
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    process_event ( $id, $ev[$i]['cal_name'], $date, $ev[$i]['cal_time'] );
  }
  $rep = get_repeating_entries ( $user, $date );
  for ( $i = 0; $i < count ( $rep ); $i++ ) {
    $id = $rep[$i]['cal_id'];
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    process_event ( $id, $rep[$i]['cal_name'], $date, $rep[$i]['cal_time'] );
  }
}

echo "</reminders>\n";

if ( $debug )
  echo "Done.\n";

?>

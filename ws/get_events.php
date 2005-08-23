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

// Load include files.
$basedir = ".."; // points to the base WebCalendar directory relative to
                 // current working directory
$includedir = "../includes";

require_once "$includedir/classes/WebCalendar.class";
require_once "$includedir/classes/Event.class";
require_once "$includedir/classes/RptEvent.class";

$WebCalendar =& new WebCalendar ( __FILE__ );

include "$includedir/config.php";
include "$includedir/php-dbi.php";
include "$includedir/functions.php";

$WebCalendar->initializeFirstPhase();

include "$includedir/$user_inc";
include "$includedir/validate.php";
include "$includedir/site_extras.php";
include "$includedir/translate.php";

$WebCalendar->initializeSecondPhase();

load_global_settings ();
load_user_preferences ();

$WebCalendar->setLanguage();

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



function escapeXml ( $str )
{
  $str = str_replace ( "\r\n", "\\n", $str );
  $str = str_replace ( "\n", "\\n", $str );
  $str = str_replace ( '<br/>', "\\n", $str );
  $str = str_replace ( '<br />', "\\n", $str );
  $str = str_replace ( '&amp;', '&', $str );
  $str = str_replace ( '&', '&amp;', $str );
  return ( str_replace ( "<", "&lt;", str_replace ( ">", "&gt;", $str ) ) );
}

// Send a single event
function print_event_xml ( $id, $event_date ) {
  global $site_extras, $WS_DEBUG, $out,
    $server_url, $application_name, $single_user, $single_user_login,
    $disable_priority_field, $disable_participants_field ;

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
    if ( $WS_DEBUG )
      $out .= "<!-- No participants found for event id: $id -->\n";
    return;
  }


  // get event details
  $res = dbi_query (
    "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description FROM webcal_entry WHERE cal_id = $id" );
  if ( ! $res ) {
    $out .= "Db error: could not find event id $id.\n";
    return;
  }


  if ( ! ( $row = dbi_fetch_row ( $res ) ) ) {
    $out .= "Error: could not find event id $id in database.\n";
    return;
  }

  $create_by = $row[0];
  $name = $row[9];
  $description = $row[10];

  $out .= "<event>\n";
  $out .= "  <id>$id</id>\n";
  $out .= "  <name>" . escapeXml ( $name ) . "</name>\n";
  if ( ! empty ( $server_url ) ) {
    if ( substr ( $server_url, -1, 1 ) == "/" ) {
      $out .= "  <url>" .  $server_url . "view_entry.php?id=" . $id . "</url>\n";
    } else {
      $out .= "  <url>" .  $server_url . "/view_entry.php?id=" . $id . "</url>\n";
    }
  }
  $out .= "  <description>" . escapeXml ( $description ) . "</description>\n";
  $out .= "  <dateFormatted>" . date_to_str ( $event_date ) . "</dateFormatted>\n";
  $out .= "  <date>" . $event_date . "</date>\n";
  if ( $row[2] >= 0 ) {
    $out .= "  <time>" . sprintf ( "%04d", $row[2] / 100 ) . "</time>\n";
    $out .= "  <timeFormatted>" . display_time ( $row[2] ) . "</timeFormatted>\n";
  }
  if ( $row[5] > 0 )
    $out .= "  <duration>" . $row[5] . "</duration>\n";
  if ( ! empty ( $disable_priority_field ) && $disable_priority_field == 'Y' )
    $out .= "  <priority>" . $pri[$row[6]] . "</priority>\n";
  if ( ! empty ( $disable_access_field ) && $disable_access_field == 'Y' )
    $out .= "  <access>" . 
      ( $row[8] == "P" ? translate("Public") : translate("Confidential") ) .
      "</access>\n";
  if ( ! strlen ( $single_user_login ) )
    $out .= "  <createdBy>" . $row[0] . "</createdBy>\n";
  $out .= "  <updateDate>" . date_to_str ( $row[3] ) . "</updateDate>\n";
  $out .= "  <updateTime>" . display_time ( $row[4] ) . "</updateTime>\n";

  // site extra fields
  $extras = get_site_extra_fields ( $id );
  $se = '';
  for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
    $extra_name = $site_extras[$i][0];
    $extra_descr = $site_extras[$i][1];
    $extra_type = $site_extras[$i][2];
    if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
      $tag = preg_replace ( "/[^A-Za-z0-9]+/", "", translate ( $extra_descr ) );
      $tag = strtolower ( $tag );
      $tagname = str_replace ( '"', '', $extra_name );
      $se .= "    <siteExtra>\n";
      $se .= "      <number>$i</number>\n";
      $se .= "      <name>" . escapeXml ( $extra_name ) . "</name>\n";
      $se .= "      <description>" . escapeXml ( $extra_descr ) . "</description>\n";
      $se .= "      <type>" . $extra_type . "</type>\n";
      $se .= "      <value>";
      if ( $extra_type == EXTRA_DATE ) {
        //$se .= date_to_str ( $extras[$extra_name]['cal_date'] );
        $se .= $extras[$extra_name]['cal_date'];
      } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
        $se .= escapeXml ( $extras[$extra_name]['cal_data'] );
      } else if ( $extra_type == EXTRA_REMINDER ) {
        $se .= ( $extras[$extra_name]['cal_remind'] > 0 ?
          translate("Yes") : translate("No") );
      } else {
        // default method for EXTRA_URL, EXTRA_TEXT, etc...
        $se .= escapeXml ( $extras[$extra_name]['cal_data'] );
      }
      $se .= "</value>\n    </siteExtra>\n";
    }
  }
  if ( $se != '' )
    $out .= "  <siteExtras>\n" . $se . "  </siteExtras>\n";
  if ( $single_user != "Y" && ! $disable_participants_field ) {
    $out .= "  <participants>\n";
    for ( $i = 0; $i < count ( $participants ); $i++ ) {
      $out .= "    <participant>" .  $participants[$i] .
        "</participant>\n";
    }
    for ( $i = 0; $i < count ( $ext_participants ); $i++ ) {
      $out .= "    <participant>" . $ext_participants[$i] .
        "</participant>\n";
    }
    $out .= "  </participants>\n";
  }
  $out .= "</event>\n";
}



// Process an event for a single day.  Check to see if it has
// a reminder, when it needs to be sent and when the last time it
// was sent.
function process_event ( $id, $name, $event_date, $event_time ) {
  global $WS_DEBUG;

  if ( $WS_DEBUG )
    printf ( "<!-- Event %d: \"%s\" at %s on %s --> \n",
      $id, $name, $event_time, $event_date );

  print_event_xml ( $id, $event_date );
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
    process_event ( $id, $ev[$i]->getName(), $date, $ev[$i]->getTime() );
  }
  $rep = get_repeating_entries ( $user, $date );
  for ( $i = 0; $i < count ( $rep ); $i++ ) {
    $id = $rep[$i]->getID();
    if ( ! empty ( $completed_ids[$id] ) )
      continue;
    $completed_ids[$id] = 1;
    process_event ( $id, $rep[$i]->getName(), $date, $rep[$i]->getTime() );
  }
}

$out .= "</events>";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  $fd = fopen ( "/tmp/webcal-ws.log", "a+", true );
  fwrite ( $fd, "\n*****************************************\n" );
  fwrite ( $fd, date ( "Y-m-d H:i:s" )  );
  fwrite ( $fd, "\n" . $out . "\n\n" );
  fclose ( $fd );
}

// Send output now...
echo $out;

?>

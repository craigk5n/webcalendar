<?php
/*
 * $Id$
 *
 * Description:
 * Creates the iCal free/busy schedule a single user.
 * Free/busy schedules are specified in the iCal RFC 2445.
 *
 * Input parameters:
 * URL should be the form of /xxx/freebusy.php/username.ifb
 * or /xxx/freebusy.php?user=username
 * Some servers seem to have problem with username.ifb version.  If
 * so, they should user the second form.
 *
 * Notes:
 * For now, we use a date range of the start of the current
 * month and include one year from there.
 * Rather arbitrary, eh???
 *
 * To read the iCal specification:
 *   http://www.ietf.org/rfc/rfc2445.txt
 *
 * WebCalendar does not use freebusy info for scheduling right now.
 * But, this may change in the future.
 *
 * We might want to cache this type of information after a calendar
 * is updated.  This would make conflict checkking much faster,
 * particularly for events with many participants.
 *
 * Developers/Debugging:
 * You can test this script from the command line if you have the
 * command-line PHP.   Create a symbolic link with a valid username,
 * and then invoke the php command using the link as a parameter:
 *  ln -s freebusy.php cknudsen.ifb
 *  php cknudsen.ifb
 *
 * Security:
 * Users do need to enable "Enable FreeBusy publishing" in their
 * Preferences or this page will generate a "you are not authorized"
 * error message.
 *
 * If $FREEBUSY_ENABLED is not 'Y' (set in each user's
 *   Preferences), do not allow.
 */

require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/php-dbi.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include 'includes/translate.php';
include_once 'includes/xcal.php';

$WebCalendar->initializeSecondPhase();

// Calculate username.
if ( empty ( $user ) ) {
  $arr = explode ( "/", $PHP_SELF );
  $user = $arr[count($arr)-1];
  # remove any trailing ".ifb" in user name
  $user = preg_replace ( "/\.[iI][fF][bB]$/", '', $user );
  if ( $user == 'public' )
    $user = '__public__';
}

load_global_settings ();

// Load user preferences (to get the DISPLAY_UNAPPROVED and
// FREEBUSY_ENABLED pref for this user).
$login = $user;
load_user_preferences ();

$WebCalendar->setLanguage();

// Load user name, etc.
user_load_variables ( $user, "publish_" );

if ( empty ( $FREEBUSY_ENABLED ) || $FREEBUSY_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  echo "user=$user\n";
  etranslate("You are not authorized");
  exit;
}

// Make sure they specified a username
if ( empty ( $user ) ) {
  die_miserable_death ( "No user specified" );
}

$get_unapproved = false;

// Start date is beginning of this month
$startdate = mktime ( 0, 0, 0, date("m"), 1, date("Y") );

// End date is one year from now
// Seems kind of arbitrary, eh?
$enddate = mktime ( 0, 0, 0, date("m"), 1, date("Y") + 1 );

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events ( $user, '',
  date ( "Ymd" ), $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $user, date ( "Ymd", $startdate ),
  date ( "Ymd", $enddate ), '' );

// Loop from start date until we reach end date...
$event_text = '';
//define ( 'ONE_DAY', ( 3600 * 24 ) );
for ( $d = $startdate; $d <= $enddate; $d += ONE_DAY ) {
  $dYmd = date ( "Ymd", $d );
  $ev = get_entries ( $user, $dYmd, $get_unapproved );
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $ev[$i]->getDuration(),
      $ev[$i]->getTime(), "ical");
  }
  $revents = get_repeating_entries ( $user, $dYmd, $get_unapproved );
  for ( $i = 0; $i < count ( $revents ); $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $revents[$i]->getDuration(),
      $revents[$i]->getTime(), "ical");
  }
}

header ( "Content-Type: text/calendar" );

echo "BEGIN:VCALENDAR\r\n";
  $title = "X-WR-CALNAME;VALUE=TEXT:" .
  ( empty ( $publish_fullname ) ? $user : translate($publish_fullname) );
$title = str_replace ( ",", "\\,", $title );
  echo "$title\r\n";
if ( preg_match ( "/WebCalendar v(\S+)/", $PROGRAM_NAME, $match ) ) {
  echo "PRODID:-//WebCalendar-$match[1]\r\n";
} else {
  echo "PRODID:-//WebCalendar-UnknownVersion\r\n";
}
echo "VERSION:2.0\r\n";
//echo "METHOD:PUBLISH\r\n";
echo "BEGIN:VFREEBUSY\r\n";

$utc_start = export_get_utc_date ( date ( "Ymd", $startdate ), 0 );
echo "DTSTART:$utc_start\r\n";
$utc_end = export_get_utc_date ( date ( "Ymd", $enddate ), '235959' );
echo "DTEND:$utc_end\r\n";
echo $event_text;
echo "URL:" . $GLOBALS['SERVER_URL'] . "freebusy.php/" .
  $user . ".ifb\r\n";
echo "END:VFREEBUSY\r\n";
echo "END:VCALENDAR\r\n";

exit;



?>

<?php // $Id: freebusy.php,v 1.35 2009/11/22 16:47:45 bbannon Exp $
/**
 * Description:
 * Creates the iCal free/busy schedule a single user.
 * Free/busy schedules are specified in the iCal RFC 2445.
 *
 * Input parameters:
 * URL should be the form of /xxx/freebusy.php/username.ifb
 * or /xxx/freebusy.php?user=username
 * Some servers seem to have problem with username.ifb version.
 * If so, they should user the second form.
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
 * We might want to cache this type of information after a calendar is updated.
 * This would make conflict checking much faster,
 * particularly for events with many participants.
 *
 * Developers/Debugging:
 * You can test this script from the command line if you have the command-line PHP.
 * Create a symbolic link with a valid username,
 * and then invoke the PHP command using the link as a parameter:
 *  ln -s freebusy.php cknudsen.ifb
 *  php cknudsen.ifb
 *
 * Security:
 * Users do need to enable "Enable FreeBusy publishing" in their
 * preferences or this page will generate a "You are not authorized"
 * error message.
 *
 * If $FREEBUSY_ENABLED is not 'Y' (set in each user' Preferences), do not allow.
 */

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/validate.php';
include 'includes/site_extras.php';
include 'includes/xcal.php';

$WebCalendar->initializeSecondPhase();

// Calculate username.
// If using http_auth, use those credentials.
if ( $use_http_auth && empty ( $user ) )
  $user = $login;

if ( empty ( $user ) ) {
  $arr = explode ( '/', $PHP_SELF );
  $user = $arr[count ( $arr )-1];
  # Remove any trailing ".ifb" in user name.
  $user = preg_replace ( '/\.[iI][fF][bB]$/', '', $user );
}

if ( $user == 'public' )
  $user = '__public__';

load_global_settings();

// Load user preferences (to get the DISPLAY_UNAPPROVED and
// FREEBUSY_ENABLED pref for this user).
$login = $user;
load_user_preferences();

$WebCalendar->setLanguage();

// Load user name, etc.
user_load_variables ( $user, 'publish_' );

if ( empty ( $FREEBUSY_ENABLED ) || $FREEBUSY_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  echo 'user=' . $user . "\n" . print_not_auth();
  exit;
}

// Make sure they specified a username.
$no_user = translate ( 'No user specified.' );
if ( empty ( $user ) )
  die_miserable_death ( $no_user );

$get_unapproved = false;
$datem = date ( 'm' );
$dateY = date ( 'Y' );
// Start date is beginning of this month.
$startdate = mktime ( 0, 0, 0, $datem, 1, $dateY );

// End date is one year from now.
// Seems kind of arbitrary, eh?
$enddate = mktime ( 0, 0, 0, $datem, 1, $dateY + 1 );

/* Pre-Load the repeated events for quicker access. */
$repeated_events = read_repeated_events ( $user, $startdate, $enddate, '' );

/* Pre-load the non-repeating events for quicker access. */
$events = read_events ( $user, $startdate, $enddate );

// Loop from start date until we reach end date...
$event_text = '';
for ( $d = $startdate; $d <= $enddate; $d += 86400 ) {
  $dYmd = date ( 'Ymd', $d );
  $ev = get_entries ( $dYmd, $get_unapproved );
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $ev[$i]->getDuration(),
      $ev[$i]->getTime(), 'ical' );
  }
  $revents = get_repeating_entries ( $user, $dYmd, $get_unapproved );
  $recnt = count ( $revents );
  for ( $i = 0; $i < $recnt; $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $revents[$i]->getDuration(),
      $revents[$i]->getTime(), 'ical' );
  }
}

header ( 'Content-Type: text/calendar' );
header ( 'Content-Disposition: attachment; filename="' . $login . '.ifb"' );
echo 'BEGIN:VCALENDAR' . "\r\n"
 . 'X-WR-CALNAME;VALUE=TEXT:' . str_replace ( ',', '\\,',
  ( empty ( $publish_fullname ) ? $user : translate ( $publish_fullname ) ) ) . "\r\n"
 . generate_prodid()
 . 'VERSION:2.0' . "\r\n"
 . 'METHOD:PUBLISH' . "\r\n"
 . 'BEGIN:VFREEBUSY' . "\r\n"
 . 'DTSTART:' . export_get_utc_date ( date ( 'Ymd', $startdate ), 0 ) . "\r\n"
 . 'DTEND:' . export_get_utc_date ( date ( 'Ymd', $enddate ), '235959' ) . "\r\n"
 . $event_text
 . 'URL:' . $GLOBALS['SERVER_URL'] . 'freebusy.php/' . $user . '.ifb' . "\r\n"
 . 'END:VFREEBUSY' . "\r\n"
 . 'END:VCALENDAR' . "\r\n";

exit;

?>

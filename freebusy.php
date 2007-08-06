<?php
/* $Id$
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
 
include_once 'includes/init.php';
include_once 'includes/xcal.php';

// Calculate username.
//if using http_auth, use those credentials
$user = ( _WC_HTTP_AUTH ? $WC->userId() : $WC->loginId() );

if ( empty ( $user ) ) {
  $arr = explode ( '/', $_SERVER['PHP_SELF'] );
  $username = $arr[count($arr)-1];
  # remove any trailing ".ifb" in user name
  $username = preg_replace ( "/\.[iI][fF][bB]$/", '', $username );
  //get id for username
  $user = $WC->User->getUserId ( $username );
}
 

// Load user preferences (to get the DISPLAY_UNAPPROVED and
// FREEBUSY_ENABLED pref for this user).

$WC->setLanguage();

// Load user name, etc.
$WC->User->loadVariables ( $user, 'publish_' );

if ( ! getPref ( 'FREEBUSY_ENABLED' ) ) {
  header ( 'Content-Type: text/plain' );
  echo "user=$user\n";
  echo print_not_auth ();
  exit;
}

// Make sure they specified a username
if ( empty ( $user ) ) {
  die_miserable_death ( 'No user specified' );
}

$get_unapproved = false;
$datem = date('m');
$dateY = date('Y');
// Start date is beginning of this month
$startdate = mktime ( 0, 0, 0, $datem, 1, $dateY );

// End date is one year from now
// Seems kind of arbitrary, eh?
$enddate = mktime ( 0, 0, 0, $datem, 1, $dateY + 1 );

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events ( $user, $startdate, $enddate, '' );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $user, $startdate, $enddate);

// Loop from start date until we reach end date...
$event_text = '';
for ( $d = $startdate; $d <= $enddate; $d += ONE_DAY ) {
  $dYmd = date ( 'Ymd', $d );
  $ev = get_entries ( $dYmd, $get_unapproved );
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $ev[$i]->getDuration(),
      $ev[$i]->getDate( 'His' ), 'ical');
  }
  $revents = get_repeating_entries ( $user, $dYmd, $get_unapproved );
  $recnt = count ( $revents );
  for ( $i = 0; $i < $recnt; $i++ ) {
    $event_text .= fb_export_time ( $dYmd, $revents[$i]->getDuration(),
      $revents[$i]->getDate( 'His' ), 'ical');
  }
}

header ( 'Content-Type: text/calendar' );
header ( 'Content-Disposition: attachment; filename="' . 
  $WC->loginId() .  '.ifb"' );
echo "BEGIN:VCALENDAR\r\n";
  $title = "X-WR-CALNAME;VALUE=TEXT:" .
  ( empty ( $publish_fullname ) ? $WC->getFullName() : translate($publish_fullname) );
$title = str_replace ( ",", "\\,", $title );
echo "$title\r\n";
echo generate_prodid ();
echo "VERSION:2.0\r\n";
//echo "METHOD:PUBLISH\r\n";
echo "BEGIN:VFREEBUSY\r\n";

$utc_start = export_get_utc_date ( date ( 'Ymd', $startdate ), 0 );
echo "DTSTART:$utc_start\r\n";
$utc_end = export_get_utc_date ( date ( 'Ymd', $enddate ), '235959' );
echo "DTEND:$utc_end\r\n";
echo $event_text;
echo 'URL:' . getPref ( 'SERVER_URL', 2 ) . 'freebusy.php/' .
  $WC->loginId() . ".ifb\r\n";
echo "END:VFREEBUSY\r\n";
echo "END:VCALENDAR\r\n";

exit;



?>

<?php
/* $Id: week_ssi.php,v 1.43.2.2 2007/08/06 02:28:31 cknudsen Exp $

 This page is intended to be used as a server-side include for another page.
 (Such as an intranet home page or something.)
 As such, no login is required. Instead, the login id is either passed in the
 URL "week_ssi.php?login=cknudsen". Unless, of course, we are in
 single-user mode, where no login info is needed.
 If no login info is passed, we check for the last login used.
*/

include_once 'includes/init.php';

load_global_settings ();

$WebCalendar->setLanguage ();

$user = '__none__'; // Don't let user specify in URL.

if ( strlen ( $login ) == 0 ) {
  if ( $single_user == 'Y' )
    $login = $user = $single_user_login;
  else
  if ( strlen ( $webcalendar_login ) > 0 )
    $login = $user = $webcalendar_login;
  else {
    echo '<span style="color:#F00;"><span style="font-weight: bold;">'
     . translate ( 'Error' ) . ':</span>'
     . translate ( 'No user specified' ) . '.</span>';
    exit;
  }
}

$view = 'week';
// TODO This is suspect
$today = mktime ();

if ( ! empty ( $date ) && ! empty ( $date ) ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
  $thisday = substr ( $date, 6, 2 );
} else {
  $thisday = ( empty ( $day ) || $day == 0 ? date ( 'd', $today ) : $day );
  $thismonth = ( empty ( $month ) || $month == 0
    ? date ( 'm', $today ) : $month );
  $thisyear = ( empty ( $year ) || $year == 0 ? date ( 'Y', $today ) : $year );
}

$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday + 1 );
$wkend = $wkstart + ( 86400 * 6 );

$startdate = date ( 'Ymd', $wkstart );
$enddate = date ( 'Ymd', $wkend );

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( $login, $startdate, $enddate, '' );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $login, $startdate, $enddate );

$first_hour = $WORK_DAY_START_HOUR;
$last_hour = $WORK_DAY_END_HOUR;
$untimed_found = false;

$tmpOut1 = $tmpOut2 = '';

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + 86400 * $i;
  $date = date ( 'Ymd', $days[$i] );

  $tmpOut1 .= '
              <th style="width: 13%; background: '
   . ( date ( 'Ymd', $days[$i] ) == date ( 'Ymd', $today )
    ? $TODAYCELLBG : $THBG )
   . ';">' . weekday_name ( ( $i + $WEEK_START ) % 7, $DISPLAY_LONG_DAYS )
   . '<br />' . month_name ( date ( 'm', $days[$i] ) - 1, 'M' ) . ' '
   . date ( 'd', $days[$i] ) . '</th>';

  $tmpOut2 .= '
              <td style="vertical-align: top; width: 75px; height: 75px; '
   . 'background: ' . ( $date == date ( 'Ymd' ) ? $TODAYCELLBG : $CELLBG )
   . print_date_entries ( $date, $login, true, true ) . '&nbsp;</td>';
}

echo '
    <table width="100%">
      <tr>
        <td style="background: ' . $TABLEBG . ';">
          <table style="border: 0; width: 100%;" cellspacing="1" cellpadding="2">
            <tr>' . $tmpOut1 . '
            </tr>
            <tr>' . $tmpOut2 . '
            </tr>
          </table>
        </td>
      </tr>
    </table>';

?>

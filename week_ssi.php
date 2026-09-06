<?php
/**
 * This page is intended to be used as a server-side include for another page.
 * (Such as an intranet home page or something.)
 *
 * It shows the week belonging to whoever is viewing it: the logged-in user,
 * or the public user when $PUBLIC_ACCESS is enabled. There is no way to embed
 * some other user's calendar. A "login" URL parameter is ignored, because
 * includes/init.php has already established the login before this page runs.
 */

require_once 'includes/init.php';

load_global_settings();

$WebCalendar->setLanguage();

$user = '__none__'; // Don't let user specify in URL.

$view = 'week';
// TODO This is suspect
$today = time();

if ( ! empty ( $date ) ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
  $thisday = substr ( $date, 6, 2 );
} else {
  $thisday = ( empty ( $day ) || $day == 0 ? date ( 'd', $today ) : $day );
  $thismonth = ( empty ( $month ) || $month == 0
    ? date ( 'm', $today ) : $month );
  $thisyear = ( empty ( $year ) || $year == 0 ? date ( 'Y', $today ) : $year );
}

$next = mktime( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday + 1 );
$wkend = end_of_day ( $wkstart, 6 );

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events ( $login, $wkstart, $wkend, '' );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $login, $wkstart, $wkend );

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
   . '<br>' . month_name ( date ( 'm', $days[$i] ) - 1, 'M' ) . ' '
   . date ( 'd', $days[$i] ) . '</th>';

  $tmpOut2 .= '
              <td style="inline-size: 75px; block-size: 75px; background: ' .
    ( $date == date ( 'Ymd' ) ? $TODAYCELLBG : $CELLBG ) .
    '; vertical-align: top;">' .
    print_date_entries ( $date, $login, true, true ) . '&nbsp;</td>';
}

echo '
    <table>
      <tr>
        <td style="background: ' . $TABLEBG . ';">
          <table style="border-collapse: separate; border-spacing: 1px; padding: 2px;">
            <tr>' . $tmpOut1 . '
            </tr>
            <tr>' . $tmpOut2 . '
            </tr>
          </table>
        </td>
      </tr>
    </table>';

?>

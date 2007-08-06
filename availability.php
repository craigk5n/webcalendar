<?php
/* $Id$
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * month (*) - specify the starting month of the timebar
 * day (*)   - specify the starting day of the timebar
 * year (*)  - specify the starting year of the timebar
 * users (*) - csv of users to include
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 */

include_once 'includes/init.php';
// Don't allow users to use this feature if "allow view others" is disabled.
if ( ! getPref ( 'ALLOW_VIEW_OTHER' ) && ! $WC->isAdmin() )
  // not allowed...
  exit;

// Input args in URL.
// users: list of comma-separated users.
$programStr = translate ( 'Program Error' ) . ' ';
$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );

if ( empty ( $users ) ) {
  echo $programStr . str_replace ( 'XXX', translate ( 'user' ),
    translate ( 'No XXX specified!' ) );
  exit;
} elseif ( empty ( $year ) ) {
  echo $programStr . str_replace ( 'XXX', translate ( 'year' ),
    translate ( 'No XXX specified!' ) );
  exit;
} elseif ( empty ( $month ) ) {
  echo $programStr . str_replace ( 'XXX', translate ( 'month' ),
    translate ( 'No XXX specified!' ) );
  exit;
} elseif ( empty ( $day ) ) {
  echo $programStr . str_replace ( 'XXX', translate ( 'day' ),
    translate ( 'No XXX specified!' ) );
  exit;
}

build_header (
  array ( 'js/availability.php/false/' . "$month/$day/$year/"
   . $WC->getGET ( 'form' ) ), '', 'onload="focus ();"', true, false, true );

$next_url = $prev_url = '?users=' . $users;
$time = mktime ( 0, 0, 0, $month, $day, $year );
$date = date ( 'Ymd', $time );
$next_url .= strftime ( '&amp;year=%Y&amp;month=%m&amp;day=%d', $time + ONE_DAY );
$prev_url .= strftime ( '&amp;year=%Y&amp;month=%m&amp;day=%d', $time - ONE_DAY );
$span = ( getPref ( 'WORK_DAY_END_HOUR' ) - getPref ( 'WORK_DAY_START_HOUR' ) ) * 3 + 1;

$users = explode ( ',', $users );

echo '
    <div style="width:99%;">
      <a title="' . $previousStr . '" class="prev" href="'
 . $prev_url . '"><img src="images/leftarrow.gif" class="prevnext" alt="'
 . $previousStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="' . $next_url
 . '"><img src="images/rightarrow.gif" class="prevnext" alt="'
 . $nextStr . '" /></a>
      <div class="title">
        <span class="date">';
printf ( "%s, %s %d, %d", weekday_name ( strftime ( "%w", $time ) ),
  month_name ( $month - 1 ), $day, $year );
echo '</span><br />
      </div>
    </div><br />
    <form action="availability.php" method="post">
      ' . daily_matrix ( $date, $users ) . '
    </form>
    ' . print_trailer ( false, true, true );

?>

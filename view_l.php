<?php /* $Id$ */
/**
 * Page Description:
 * This page will display the month "view" with all users's events on the same
 * calendar. (The other month "view" displays each user calendar in a separate
 * column, side-by-side.) This view gives you the same effect as enabling layers,
 * but with layers you can only have one configuration of users.
 *
 * Input Parameters:
 * id (*)   - specify view id in webcal_view table
 * date     - specify the starting date of the view.
 *            If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin user ($is_admin). If the view is not global, the
 * user must be owner of the view. If the view is global, then and
 * user_sees_only_his_groups is enabled, then we remove users not in this user's
 * groups (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/views.php';

$printerStr = $unapprovedStr = '';
if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events( $is_assistant || $is_nonuser_admin
   ? $user : $login );
  $printerStr = generate_printer_friendly ( 'month.php' );
}

$trailerStr = print_trailer();

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );
$nextYmd = date ( 'Ymd', $next );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );
$prevYmd = date ( 'Ymd', $prev );

if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 2, 0, $thisyear );
} else {
  $boldDays = false;
  $startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
}

$thisdate = date ( 'Ymd', $startdate );

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer();
  ob_end_flush();
  exit;
}

$e_save = $re_save = array();
foreach ( $viewusers as $i ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $i, $startdate, $enddate, '' );
  $re_save = array_merge ( $re_save, $repeated_events );
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $i, $startdate, $enddate );
  $e_save = array_merge ( $e_save, $events );
}
$events = $repeated_events = array();

foreach ( $e_save as $i ) {
  $should_add = 1;
  for ( $j = 0, $cnt = count ( $events ); $j < $cnt && $should_add; $j++ ) {
    if ( ! $i->getClone() && $i->getID() == $events[$j]->getID() )
      $should_add = 0;
  }
  if ( $should_add )
    array_push ( $events, $i );
}

foreach ( $re_save as $i ) {
  $should_add = 1;
  for ( $j = 0, $cnt = count ( $repeated_events ); $j < $cnt && $should_add; $j++ ) {
    if ( ! $i->getClone() && $i->getID() == $repeated_events[$j]->getID() )
      $should_add = 0;
  }
  if ( $should_add )
    array_push ( $repeated_events, $i );
}

if ( $DISPLAY_SM_MONTH != 'N' ) {
  $prevMonth = display_small_month ( $prevmonth, $prevyear, true, true,
    'prevmonth', 'view_l.php?id=' . $id . '&amp;' );
  $nextMonth = display_small_month ( $nextmonth, $nextyear, true, true,
    'nextmonth', 'view_l.php?id=' . $id . '&amp;' );
  $navStr = display_navigation ( 'view_l', false, false );
} else
  $navStr = display_navigation ( 'view_l', true, false );

$monthStr = display_month ( $thismonth, $thisyear );
$eventinfo = ( empty ( $eventinfo ) ? '' : $eventinfo );

echo <<<EOT
    <div class="title">
      <div class="minical">
       {$prevMonth}{$nextMonth}
      </div>
      {$navStr}
      <span class="viewname"><br>{$view_name}</span>
    </div>
    <br>
    {$monthStr}
    {$eventinfo}
    {$unapprovedStr}
    {$printerStr}
    {$trailerStr}
EOT;

ob_end_flush();

?>
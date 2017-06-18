<?php
/* $Id: view_l.php,v 1.70.2.3 2007/10/11 20:18:24 umcesrjones Exp $
 *
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

include_once 'includes/init.php';
include_once 'includes/views.php';

view_init ( $id );

$error = $printerStr = $unapprovedStr = '';
if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events (  $is_assistant || $is_nonuser_admin
   ? $user : $login );
  $printerStr = generate_printer_friendly ( 'month.php' );
}
set_today ( $date );
print_header ( array ( 'js/popups.php/true' ),
  '<script src="includes/js/weekHover.js" type="text/javascript"></script>' );
$trailerStr = print_trailer ();

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
  $startdate = mktime ( 0, 0, 0, $thismonth - 1, 0, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 2, 0, $thisyear );
} else {
  $boldDays = false;
  $startdate = mktime ( 0, 0, 0, $thismonth, 0, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
}

$thisdate = date ( 'Ymd', $startdate );
// .
// Get users in this view.
$viewusers = view_get_user_list ( $id );
if ( count ( $viewusers ) == 0 )
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view.
  $error = translate ( 'No users for this view' );

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer ();
  exit;
}

$e_save = $re_save = array ();
for ( $i = 0, $cnt = count ( $viewusers ); $i < $cnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $startdate, $enddate, '' );
  $re_save = array_merge ( $re_save, $repeated_events );
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge ( $e_save, $events );
}
$events = $repeated_events = array ();

for ( $i = 0, $cnt = count ( $e_save ); $i < $cnt; $i++ ) {
  $should_add = 1;
  for ( $j = 0, $cnt_j = count ( $events ); $j < $cnt_j && $should_add; $j++ ) {
    if ( ! $e_save[$i]->getClone () && $e_save[$i]->getID () == $events[$j]->getID () )
      $should_add = 0;
  }
  if ( $should_add )
    array_push ( $events, $e_save[$i] );
}

for ( $i = 0, $cnt = count ( $re_save ); $i < $cnt; $i++ ) {
  $should_add = 1;
  for ( $j = 0, $cnt_j = count ( $repeated_events ); $j < $cnt_j && $should_add; $j++ ) {
    if ( ! $re_save[$i]->getClone () &&
      $re_save[$i]->getID () == $repeated_events[$j]->getID () )
      $should_add = 0;
  }
  if ( $should_add )
    array_push ( $repeated_events, $re_save[$i] );
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
    <span class="viewname"><br />{$view_name}</span>
  </div>
  <br />
  {$monthStr}
  {$eventinfo}
  {$unapprovedStr}
  {$printerStr}
  {$trailerStr}
EOT;

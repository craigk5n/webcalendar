<?php
/* $Id$
 *
 * Page Description:
 * This page will display the month "view" with all users's events
 * on the same calendar.  (The other month "view" displays each user
 * calendar in a separate column, side-by-side.)  This view gives you
 * the same effect as enabling layers, but with layers you can only
 * have one configuration of users.
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */

include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';

view_init ( $eid );

$WC->setToday ($date);
$INC = array('popups.js');
build_header ($INC);
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


if ( getPref ( 'BOLD_DAYS_IN_YEAR' ) ) {
  $startdate = mktime ( 0, 0, 0, $thismonth -1, 1, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 2, 0, $thisyear );
} else {
  $startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
}


$thisdate = date ( 'Ymd', $startdate );

// get users in this view
$viewusers = view_get_user_list ( $eid );
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $startdate, $enddate, '' ); 
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge($e_save, $events);
} 
$events = array ();
$repeated_events = array ();

for ( $i = 0; $i < count ( $e_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $events ) && $should_add; $j++ ) {
    if ( ! $e_save[$i]->getClone() && 
      $e_save[$i]->getId() == $events[$j]->getId() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $events, $e_save[$i] );
  }
}

for ( $i = 0; $i < count ( $re_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $repeated_events ) && $should_add; $j++ ) {
    if ( ! $re_save[$i]->getClone() && 
      $re_save[$i]->getId() == $repeated_events[$j]->getId() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $repeated_events, $re_save[$i] );
  }
}

if ( getPref ( 'DISPLAY_SM_MONTH' ) ) {
  $prevMonth = display_small_month ( $prevmonth, $prevyear, true, true, 'prevmonth',
    "view_l.php?eid=$eid&amp;" );
  $nextMonth = display_small_month ( $nextmonth, $nextyear, true, true, 'nextmonth',
    "view_l.php?eid=$eid&amp;" ); 
  $navStr = display_navigation( 'view_l', false, false );
} else {
  $navStr = display_navigation( 'view_l', true, false );
}
$monthStr = display_month ( $thismonth, $thisyear );
$eventinfo = ( ! empty ( $eventinfo )? $eventinfo : '' );

echo <<<EOT
  <div class="title">
    <div  class="minical">
     {$prevMonth}{$nextMonth}
    </div> 
    {$navStr}
    <span class="viewname"><br />{$view_name}</span>
  </div>
	<br />
	{$monthStr}
	{$eventinfo}
	{$trailerStr}
EOT;

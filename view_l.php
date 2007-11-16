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
 * vid (*) - specify view id in webcal_view table
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


// get users in this view
$viewusers = view_get_user_list ( $vid );

if ( ! empty ( $error ) ) {
    $smarty->assign ( 'errotStr', $error );
    $smarty->display ( 'error.tpl' );
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

  $navStr = display_navigation( 'view_l', false, false );
} else {
  $navStr = display_navigation( 'view_l', true, false );
}
$monthStr = display_month ( $thismonth, $thisyear );
$eventinfo = ( ! empty ( $eventinfo )? $eventinfo : '' );

$smarty->assign('display_sm_month', getPref ( 'DISPLAY_SM_MONTH' ) );
$smarty->assign('display_tasks', false );

$smarty->assign('tableWidth', '100%');
$smarty->assign('monthURL', 'view_l.php?' . ( $WC->catId()
  ? 'cat_id=' . $WC->catId() . '&amp;' : '' ) );

$smarty->assign('navName', 'view_l' ); 

build_header ( array ( 'calendar.js') );


$smarty->display ( 'month'.tpl );
?>

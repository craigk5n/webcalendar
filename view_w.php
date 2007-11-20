<?php
/* $Id$
 *
 * Page Description:
 * Display view of a week with users side by side.
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
 * Must have "allow view others" enabled (_ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
define ( 'CALTYPE', 'week' );
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;
$vid = $WC->getValue ( 'vid' );
view_init ( $vid );

$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );
$display_long_days = getPref ( 'DISPLAY_LONG_DAYS' );
$add_link_in_views = getPref ( 'ADD_LINK_IN_VIEWS' );

build_header ();

$dateStr = date_to_str ( date ( 'Ymd', $wkstart ), '', false ) 
  . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' 
  . date_to_str ( date ( 'Ymd', $wkend ), '', false );


// get users in this view
$viewusers = view_get_user_list ( $vid );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$viewdata = array();
for ( $j = 0; $j < $viewusercnt; $j += $USERS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = $viewusercnt - $j;
  if ( $num_left > $USERS_PER_TABLE ) {
    $num_left = $USERS_PER_TABLE;
  }
  if ( $num_left > 0 ) {
    if ( $num_left < $USERS_PER_TABLE ) {
      $tdw = (int) ( 90 / $num_left );
    } else {
      $tdw = (int) ( 90 / $USERS_PER_TABLE );
    }
  } else {
    $tdw = 5;
  }
	$viewwidth[$j] = $tdw;
	
  for ( $i = $j, $k = 0;
    $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $viewuser[$i] = $WC->User->loadVariables ( $viewusers[$i]['cal_login_id'] );
  }
	

  for ( $k=0,$date = $WC->getStartDate(); $date < $WC->getEndDate(); $k++, $date += ONE_DAY ) {
    $dateYmd = date ( 'Ymd', $date );
    $is_weekend = is_weekend ( $date );
    //if ( $is_weekend && ! $display_weekends ) continue;
    $weekday = weekday_name ( date ( 'w', $date ), $display_long_days );
    if ( $dateYmd == $WC->todayYmd )
      $class = 'today';
    else if ( $is_weekend )
      $class = 'weekend';
    else
      $class = 'row';
		$viewdata[$j][$k]['dateYmd'] = $dateYmd;
		$viewdata[$j][$k]['dated'] = date ( 'd', $date );
    $viewdata[$j][$k]['class'] = $class;
		$viewdata[$j][$k]['weekday'] = $weekday;
  }

}
//echo date ( 'Ymd His', $WC->getStartDate() ) . ' ' . date ( 'Ymd His', $WC->getEndDate() );
//print_r ( $viewuser );
$user = ''; // reset

$smarty->assign ( 'users_per_table', $USERS_PER_TABLE );
$smarty->assign ( 'viewusercnt', $viewusercnt );
$smarty->assign ( 'viewuser', $viewuser );
$smarty->assign ( 'viewwidth', $viewwidth );
$smarty->assign ( 'viewdata', $viewdata );

$smarty->display ( 'view_w.tpl' );
?>


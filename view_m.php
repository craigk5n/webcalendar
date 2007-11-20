<?php
/* $Id$
 *
 * Page Description:
 * Display a month view with users side by side.
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
 define ( 'CALTYPE', 'month' );
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;

build_header ();


// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done....
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$participantscnt = count ( $participants );
$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );
$display_long_days = getpref ( 'DISPLAY_LONG_DAYS' );


for ( $j = 0; $j < $participantscnt; $j += $USERS_PER_TABLE ) {
  // Calculate width of columns in this table.
  $num_left = $participantscnt - $j;
  if ($num_left > $USERS_PER_TABLE) {
    $num_left = $USERS_PER_TABLE;
  }
  if ($num_left > 0) {
    if ($num_left < $USERS_PER_TABLE) {
      $tdw = (int) (90 / $num_left);
    } else {
      $tdw = (int) (90 / $USERS_PER_TABLE);
    }
  } else {
    $tdw = 5;
  }

  // $j points to start of this table/row
  // $k is counter starting at 0
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < $participantscnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
		 $viewusers[$participants[$i]['cal_login_id']]['tdw'] = $tdw; 
     $viewusers[$participants[$i]['cal_login_id']]['fullname'] = 
		   $participants[$i]['cal_fullname'];
  }

  $smarty->assign ( 'viewusers', $viewusers );
 for ( $date = $WC->getStartDate(); $date <= $WC->getEndDate(); $date += ONE_DAY ) {
   $dateYmd = date ('Ymd', $date);
   $is_weekend = is_weekend( $date ); 
   if ( $is_weekend && ! $display_weekends ) continue; 
   $weekday = weekday_name ( date ( 'w', $date ), $display_long_days );
   if ( $dateYmd == $WC->todayYmd )
     $class = 'class="today"';
   else if ( $is_weekend )
     $class = 'class="weekend"';
   else
     $class = 'class="row"';

  }
}

$user = ''; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

$smarty->display ( 'view_m.tpl');
 ?>


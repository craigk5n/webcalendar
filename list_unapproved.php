<?php
/**
 * This file lists unapproved entries for one or more users.
 *
 * Optional parameters in URL:
 * url=user specifies that we should only display unapproved
 *   events for that one user
 *
 * The user will be allowed to approve/reject the event if:
 * it is on their own calendar
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @version $Id$
 */
include_once 'includes/init.php';

$user =  $WC->userId();

if ( ! empty ( $_POST ) ) {
  $process_action = $WC->getPOST ( 'process_action' );
  $process_user = $WC->getPOST ( 'process_user' );
  if ( ! empty ( $process_action ) ) {
    foreach ( $_POST as $tid => $app_user ) {
      if ( substr ( $tid, 0, 5  ) == 'entry' )
        $type = substr ( $tid, 5, 1 );
        $eid = substr( $tid, 6 );
        if ( empty ( $error ) && $eid > 0 ) {
          update_status ( $process_action, $app_user, $eid, $type );
        }
      }
  }
}

//make sure we return after editing an event via this page
remember_this_view();

build_header (array('list_unapproved.js'));

$key =  0;
$eventinfo = $noret = '';

//Get list of users that we can approve
$users = $user_list = array ();
// If a user is specified, we list just that user.
if ( $user ) {
  if ( access_user_calendar ( 'approve', $user ) ) {
    $users[] = $user;
  } else {
    // not authorized to approve for specified user
    $smarty->assign ( 'errorStr', translate ( 'Not authorized' ) );
  }
} else {
  $non_users = get_nonuser_cals ( $WC->userLoginId() );
  $approve_users = get_my_users ( $WC->userLoginId(), 'approve' );
  // First, we list ourself
  $users[] = $WC->loginId();
  $all = array_merge ( $approve_users, $non_users );
}

//Get list of unapproved events for each user
$sql = 'SELECT weu.cal_login_id, we.cal_id, we.cal_name, we.cal_description, 
  we.cal_priority, we.cal_date, we.cal_duration, weu.cal_status, we.cal_type
  FROM webcal_entry we, webcal_entry_user weu
  WHERE we.cal_id = weu.cal_id AND weu.cal_login_id = ? AND weu.cal_status = \'W\'
  ORDER BY we.cal_date';
foreach ( $users as $user ) {
  $rows = dbi_get_cached_rows ( $sql, array ( $user ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
		  $user_id = $row[0];
      $eid = $row[1];
      $name = $user_list[$user_id][$eid]['name'] = $row[2];
      $description = $row[3];			
      $pri = $row[4];
      $date = $user_list[$user_id][$eid]['date'] = $row[5];
      $duration = $row[6];
      $status = $row[7];     
      $user_list[$user_id][$eid]['entryID'] = 'entry' . $row[8] . $eid;
      $popID = $user_list[$user_id][$eid]['popID']  = 'pop' . $eid . '-' . $key++;
			$divname = 'eventinfo-' . $popID;
			
	  }	
	}
}

//do_debug ( print_r ( $user_list, true ) );
$smarty->assign ( 'can_delete', access_user_calendar ( 'edit', $user ) );
$smarty->assign ( 'users', $user_list );
$smarty->display ( 'list_unapproved.tpl' );
?>


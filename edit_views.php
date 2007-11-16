<?php
/*
 * $Id$
 *
 * Page Description:
 * This page displays the views that the user currently owns and
 * allows new ones to be created
 *
 * Input Parameters:
 * id  - specify view id in webcal_view table
 * if blank, a new view is created
 *
 * Security:
 * Must be owner of the viewto edit
 */
include_once 'includes/init.php';

$error = '';

$user = ( $WC->isAdmin() ? $WC->userId() : $WC->loginId() );
$vid =  $WC->getValue ( 'vid' );

$BodyX = 'onload="usermode_handler();"';
$INC = array ('edit_views.js' );

build_header ( $INC, '', $BodyX, 5 );

$newview = true;
$all_users = $viewisglobal = 'N';

if ( ! $vid ) {
  $smarty->assign ( 'viewname', translate( 'Unnamed View' ) );
} else {
  // search for view by id
  $views = loadViews ();
  $viewcnt = count ( $views );
  for ( $i = 0; $i < $viewcnt; $i++ ) {
	  if ( $views[$i]['cal_view_id'] == $vid )
		break;
	}
  if ( isset ( $views[$i]) ) {  
    $newview = false;
		$viewisglobal = $views[$i]['cal_is_global'];
    $smarty->assign ( 'viewname', $views[$i]['cal_name'] );
    $smarty->assign ( 'viewtype', $views[$i]['cal_view_type'] );
    // get list of users for this view  
	  $sql = 'SELECT cal_login_id FROM webcal_view_user WHERE cal_view_id = ?';
    $res = dbi_execute ( $sql, array ( $vid ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $viewuser[$row[0]] = 1;
        if ( $row[0] == -1 )
          $all_users = 'Y';
      }
      dbi_free_result ( $res );
    } else {
      $error = db_error ();
    }
  } else {
    // If view not found, then  id does not belong to current user. 
    $error = print_not_auth ();	
	}
}



if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  exit;
}

  // get list of all users
  $users = get_my_users ( '', 'view' );
  if ( getPref ( 'NONUSER_ENABLED' ) ) {
    $nonusers = get_my_nonusers ( $user, true, 'view' );
    $users = ( getPref ( 'NONUSER_AT_TOP' ) ) ? 
	  array_merge($nonusers, $users) : array_merge($users, $nonusers);
  }
  for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
    $u = $users[$i]['cal_login_id'];
    if ( ! empty ( $viewuser[$u] ) ) {
      $users[$i]['selected'] = SELECTED;
    }
  }
$smarty->assign ( 'userSize', ( $cnt > 15 ? 10 : 5 ) );
$smarty->assign ( 'users', $users );
$smarty->assign ( 'selectuserall', array('N'=>'Selected','Y'=>'All') );

$smarty->assign ( 'vid', $vid );
$smarty->assign ( 'newview', $newview );
$smarty->assign ( 'all_users', $all_users );
$smarty->assign ( 'viewisglobal', $viewisglobal );
$smarty->display ( 'edit_views.tpl' );

?>


<?php
/* $Id$ */
include_once 'includes/init.php';

$gid = $WC->getValue ( 'eid' );
$smarty->assign ( 'gid', $gid );
$smarty->assign ( 'add', $WC->getValue ( 'add' ) );

$smarty->assign ('deleteConfirm', str_replace ( 'XXX', translate ( 'entry' ),
    translate ( 'Are you sure you want to delete this XXX?' ) ) );

$smarty->assign('userlist', $WC->User->getUsers () );
		
if ( empty ( $gid ) )   {
  $smarty->assign ( 'groupname', translate ( 'Unnamed Group' ) );
	$smarty->assign ( 'newgroup', true );
} else {
  $smarty->assign ( 'newgroup', false );
  // Get group by id.
  $res = dbi_execute ( 'SELECT cal_owner, cal_name, cal_last_update
    FROM webcal_group WHERE cal_group_id = ?', array ( $gid ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $smarty->assign ( 'groupname', $row[1] );
      $smarty->assign ( 'groupupdated', $row[2] );
      $tempData = $WC->User->loadVariables ( $row[0] );
      $smarty->assign ( 'groupowner', $tempData['fullname'] );
    }
    dbi_fetch_row ( $res );
  }
  // Get group users
  $res = dbi_execute ( 'SELECT wgu.cal_login_id, wu.firstname, wu.lastname
    FROM webcal_group_user wgu, webcal_user wu 
		WHERE we.cal_login_id = wgu.cal_login_id AND
		wgu.cal_group_id = ?', array ( $gid ) );
  if ( $res ) {
	  $grouplist = array();
    while ( $row = dbi_fetch_row ( $res ) ) {
       $grouplist['id'] = $row[0];
			 $grouplist['fullname'] = $row[1] . ' ' . $row[2];
    }
    dbi_fetch_row ( $res );
  }
	$smarty-assign-> ( 'grouplist', $grouplist );
}

build_header ( '', '', '', 5 );

$smarty->display ( 'edit_group.tpl' );
?>

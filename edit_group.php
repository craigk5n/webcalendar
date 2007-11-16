<?php
/* $Id$ */
include_once 'includes/init.php';

$gid = $WC->getValue ( 'gid' );
$smarty->assign ( 'gid', $gid );
$smarty->assign ( 'add', $WC->getValue ( 'add' ) );

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
	$grouplist = array();
  $res = dbi_execute ( 'SELECT wgu.cal_login_id
    FROM webcal_group_user wgu
		WHERE wgu.cal_group_id = ?', array ( $gid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
       //$grouplist[$row[0]]['cid'] = $row[0];
			 $grouplist[$row[0]] = $WC->getFullName ( $row[0] );
			 //	$smarty->append ( 'grouplist', $grouplist );
    }
    asort ( $grouplist );
		$smarty->assign ( 'grouplist', $grouplist );
    dbi_fetch_row ( $res );
  }
}

build_header ( array('edit_group.js'), '', '', 5 );

$smarty->display ( 'edit_group.tpl' );
?>

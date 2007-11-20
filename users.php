<?php
/* $Id$

 NOTE:
 There are THREE components that make up the functionality of users.php.
 1. users.php
  - contains the tabs
  - lists users
  - has an iframe for adding/editing users/nonusers/remotes
 2. edit_user.php
  - the contents of the iframe (i.e. a form for adding/editing users)
 3. edit_user_handler.php
  - handles form submittal from edit_user.php
  - provides user with confirmation of successful operation
  - refreshes the parent frame (users.php)

 This structure is mirrored for groups & nonusers
 */

include_once 'includes/init.php';

if ( ! $WC->loginId() ) {
  do_redirect ( getPref ( 'STARTVIEW', 1, '', 'month.php' ) );
  exit;
}
$tabs_ar = array();

if (access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
  $smarty->assign('doUsers',true );
	$tabs_ar['users'] =( $WC->isAdmin() ? translate( 'Users' ) 
	  : translate( 'Account' ) );
}

		
if ( $WC->isAdmin() ) {
  $smarty->assign('userlist', $WC->User->getUsers ( false ) );
	
  if ( getPref ( '_ENABLE_GROUPS' ) ) {
	  $tabs_ar['groups'] = translate ('Groups' );
    $smarty->assign('doGroups', true );

    $smarty->assign ( 'groups', get_groups ( '', true ));

  }		
	if ( getPref ( '_ENABLE_NONUSERS' ) ) {
	  $tabs_ar['nonusers'] = translate ( 'NonUser Calendars' );
    $smarty->assign('doNUCS', true );	
		$smarty->assign('nucuserlist', get_nonuser_cals () );
	}
}

if ( getPref ( '_ENABLE_REMOTES', 2 ) &&
  access_can_access_function ( ACCESS_IMPORT ) && 
	access_can_access_function ( ACCESS_LAYERS ) ) {	
  $tabs_ar['remotes'] = translate ( 'Remote Calendars' );
  $smarty->assign('doRemotes', true );
	$smarty->assign('rmtuserlist', get_nonuser_cals ( $WC->loginId(), true ) );
}

$currenttab = $WC->getGET ( 'tab', 'users' );	

$BodyX = 'onload="showTab(\''. $currenttab . '\');"';
build_header ( array ( 'users.js'), '', $BodyX, 4 );

$smarty->assign('tabs_ar', $tabs_ar );
  
$smarty->display ('users.tpl');
?>

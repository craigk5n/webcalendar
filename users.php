<?php
/* $Id$

 NOTE:
 There are THREE components that make up the functionality of users.php.
 1. users.php
  - contains the tabs
  - lists users
  - has an iframe for adding/editing users
  - include statements for groups.php and nonusers.php
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
	
  if ( getPref ( 'GROUPS_ENABLED' ) ) {
	  $tabs_ar['groups'] = translate ('Groups' );
    $smarty->assign('doGroups', true );

    $sql = 'SELECT cal_group_id, cal_name FROM webcal_group
		  ORDER BY cal_name';
		$rows = dbi_get_cached_rows ( $sql , array () );
    if ( $rows ) {
      $smarty->append ( 'groups', $rows );
    }
  }		
	if ( getPref ( 'NONUSER_ENABLED' ) ) {
	  $tabs_ar['nonusers'] = translate ( 'NonUser Calendars' );
    $smarty->assign('doNUCS', true );	
		$smarty->assign('nucuserlist', get_nonuser_cals () );
	
	}
}

if ( getPref ( 'REMOTES_ENABLED', 2 ) &&
  access_can_access_function ( ACCESS_IMPORT ) ) {	
  $tabs_ar['remotes'] = translate ( 'Remote Calendars' );
  $smarty->assign('doRemotes', true );
	$smarty->assign('rmtuserlist', get_nonuser_cals ( $WC->loginId(), true ) );
}

$currenttab = $WC->getPOST ( 'currenttab', 'users' );		
$BodyX = 'onload="showTab(\''. $currenttab . '\');"';
build_header ( array ( 'users.js', 'visible.js' ), '', $BodyX, 4 );

$smarty->assign('tabs_ar', $tabs_ar );
$smarty->assign('denotesStr', translate ( 'denotes administrative user' )   );
   
$smarty->display ('users.tpl');
?>

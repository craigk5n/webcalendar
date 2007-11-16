<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';

if ( $WC->userId() || ! $WC->isAdmin() )
  $smarty->assign ( 'user', $WC->User->loadVariables( $WC->userLoginId (), false ) );
else
  $smarty->assign ( 'user', array ( 'is_admin'=>'N', 'enabled'=>'Y' ) ) ;  

$smarty->assign ( 'chgPasswd' , ! _WC_HTTP_AUTH && _WC_USER_CAN_UPDATE_PASSWORD );

// don't allow them to create new users if it's not allowed
if ( $WC->isUser() ) {
  // asking to create a new user
  if ( ! $WC->isAdmin() ) {
    // must be admin...
    if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
      $error = print_not_auth ();
    }
  }
  if ( ! _WC_ADMIN_CAN_ADD_USER ) {
    // if adding users is not allowed...
    $error = print_not_auth ();
  }
} else {
  // User is editing their account info
  if ( ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
    $error = print_not_auth ();
}

$INC = array('edit_user.js');
build_header ( $INC, '', '', 5 );

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
} else {
   $smarty->display ( 'edit_user.tpl' );
}


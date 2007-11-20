<?php
/* $Id$ */

// There is the potential for a lot of mischief from users trying to access this
// file in ways they shouldn't.  Users may try to type in a URL to get around
// functions that are not being displayed on the web page to them.
include_once 'includes/init.php';
$layers = loadLayers ();

$error = '';

$user = ( $WC->isAdmin() ? $WC->getPOST ( 'user' ) : $WC->loginId() ) ;

$add = $WC->getPOST ( 'add' );
$formtype = $WC->getPOST ( 'formtype' );
$upassword1 = $WC->getPOST ( 'upassword1' );
$upassword2 = $WC->getPOST ( 'upassword2' );
$ufirstname = $WC->getPOST ( 'ufirstname' );
$ulastname = $WC->getPOST ( 'ulastname' );
$uis_admin = $WC->getPOST ( 'uis_admin' );
$uemail = $WC->getPOST ( 'uemail' );
$username = $WC->getPOST ( 'username' );
$uenabled = $WC->getPOST ( 'uenabled' );
$reset_login = $WC->getPOST ( 'reset_login' );

//only exist if _EXTENDED_USER is enabled
$utelephone = $WC->getPOST ( 'utelephone' );
$utitle = $WC->getPOST ( 'utitle' );
$uaddress = $WC->getPOST ( 'uaddress' );
$ubirthday = sprintf ( '%02d', $WC->getPOST ( 'ubirthday'  ) );
$ubirthmonth = sprintf ( '%02d', $WC->getPOST ( 'ubirthmonth' ) );
$ubirthyear = $WC->getPOST ( 'ubirthyear' );

$notAuthStr = print_not_auth () . '.';
$deleteStr = translate ( 'Deleting users not supported' ) . '.';
$notIdenticalStr = translate ( 'The passwords were not identical' ) . '.';
$noPasswordStr = translate ( 'You have not entered a password' ) . '.';
$blankUserStr = translate ( 'Username can not be blank' ) . '.';


// Handle delete.
$delete = $WC->getPOST ( translate ( 'Delete' ) );
if ( $WC->isAdmin() && ! empty ( $delete ) && $formtype == 'edituser' ) {
  if ( access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
    if ( _WC_ADMIN_CAN_DELETE_USER ) {
      $WC->User->deleteUser ( $user ); // Will also delete user's events.
      activity_log ( 0, $WC->loginId(), $user, LOG_USER_DELETE, '' );
    } else
      $error = $deleteStr;
  } else
    $error = $notAuthStr;
} else {
  // Handle update of password.
  if ( $formtype == 'setpassword' && $user ) {
    if ( ! _WC_USER_CAN_UPDATE_PASSWORD 
		  || ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
      $error = $notAuthStr;
    else
    if ( $upassword1 != $upassword2 )
      $error = $notIdenticalStr;
    else {
      if ( strlen ( $upassword1 ) ) {
        $WC->User->updateUser ( 
				  array ( 'cal_login_id'=>$user, 'cal_passwd'=>$upassword1 ) );
        activity_log ( 0, $WC->loginId(), $user, LOG_USER_UPDATE,
          translate ( 'Set Password' ) );
      } else
        $error = $noPasswordStr;
    }
  } else {
    // Handle update of user info.
    if ( $formtype == 'edituser' ) {
      if ( empty ( $add ) ) {
			  if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
          $error = $notAuthStr;
        else if ( $upassword1 != $upassword2 )
          $error = $notIdenticalStr;
        else if ( addslashes ( $user ) != $user )
           // This error should get caught before here anyhow, so
           // no need to translate this.  This is just in case. :-)
           $error = 'Invalid characters in login.';
         else if ( ! $user )
            // Username can not be blank.
            $error = $blankUserStr;
         else {
					  // Don't allow a user to change themself to an admin by setting
            // uis_admin in the URL by hand.  They must be admin beforehand.
						$params = array ( 'cal_login_id'=>$user,
							'cal_lastname'=>$ulastname,
							'cal_firstname'=>$ufirstname,
							'cal_email'=>$uemail,
							'cal_login'=>$username,
							'cal_is_admin'=> ( $WC->isAdmin() ? $uis_admin : 'N' ),
						  'cal_title'=>$utitle,
							'cal_address'=>$uaddress,
							'cal_telephone'=>$utelephone,
							'cal_birthday'=>$ubirthyear . $ubirthmonth . $ubirthday );
						
						 if ( $WC->isAdmin() )
						   $params['cal_enabled'] = $uenabled;
							 
						 if ( $reset_login )
							 $params['cal_last_login'] = time();
							 
             $WC->User->updateUser ( $params );
							
             activity_log ( 0, $WC->loginId(), $user, LOG_USER_UPDATE,
                "$ufirstname $ulastname"
                 . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
         }
      } else { //adding
        if ( ! $WC->isAdmin () )
          $error = $notAuthStr;
        else {
						
          $params = array ( 'cal_firstname'=>$ufirstname,
						'cal_lastname'=>$ulastname,
						'cal_passwd'=>$upassword1,
						'cal_login'=>$username,
						'cal_email'=>$uemail,
						'cal_is_admin'=>$uis_admin,
						'cal_title'=>$utitle,
						'cal_address'=>$uaddress,
						'cal_telephone'=>$utelephone,
						'cal_birthday'=>$ubirthyear . $ubirthmonth . $ubirthday,
						'cal_enabled'=>$uenabled, );
						
          $newID = $WC->User->addUser ( $params );
					
          activity_log ( 0, $WC->loginId(), $user, LOG_USER_ADD, 
            "$newID $ufirstname $ulastname"
             . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
        }
      }
    }
  }
}

echo error_check ( 'users.php', false );

?>

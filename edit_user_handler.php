<?php
/* $Id$ */

// There is the potential for a lot of mischief from users trying to
// access this file in ways they shouldn't.  Users may try to type in
// a URL to get around functions that are not being displayed on the
// web page to them. 

include_once 'includes/init.php';
load_user_layers ();

$error = '';
if ( ! $is_admin )
  $user = $login;

$notAuthStr = print_not_auth () . '.';
$deleteStr = translate( 'Deleting users not supported' ) . '.';
$notIdenticalStr = translate( 'The passwords were not identical' ) . '.';
$noPasswordStr = translate( 'You have not entered a password' ) . '.';
$invalidCharStr = translate( 'Invalid characters in login' ) . '.';
$blankUserStr = translate( 'Username can not be blank' ) . '.';
// don't allow them to edit users if it's not allowed
if ( empty ( $user ) ) {
  // asking to create a new user
  if ( ! $is_admin ) {
    // must be admin...
    if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
      send_to_preferred_view ();
    }
  }
  if ( ! $admin_can_add_user ) {
    // if adding users is not allowed...
    send_to_preferred_view ();
    exit;
  }
} else {
  // User is editing their account info
  if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
    send_to_preferred_view ();
}

// Handle delete
$delete = getPostValue ( 'delete' );
if ( ! empty ( $delete ) && $formtype == 'edituser' ) {
  if ( access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
    if ( $admin_can_delete_user ) {
      user_delete_user ( $user ); // will also delete user's events
    } else {
      $error = $deleteStr;
    }
  } else {
    $error = $notAuthStr;
  }
}

// Handle update of password
else if ( $formtype == 'setpassword' && strlen ( $user ) ) {
  if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) &&
    ! access_can_access_function ( ACCESS_ACCOUNT ) ) {
    $error = $notAuthStr;
  } else if ( $upassword1 != $upassword2 ) {
    $error = $notIdenticalStr;
  } else if ( strlen ( $upassword1 ) ) {
    if ( $user_can_update_password ) {
      user_update_user_password ( $user, $upassword1 );
    } else {
      $error = $notAuthStr;
    }
  } else
    $error = $noPasswordStr;
}

// Handle update of user info
else if ( $formtype == 'edituser' ) {
  if ( ! empty ( $add ) && $is_admin ) {
    if ( $upassword1 != $upassword2 ) { 
      $error = $notIdenticalStr; 
    } else {
      if ( addslashes ( $user ) != $user ) {
        // This error should get caught before here anyhow, so
        // no need to translate this.  This is just in case :-)
        $error = $invalidCharStr;
      } else if ( empty ( $user ) || $user == '' ) {
        // Username can not be blank. This is currently the only place that 
        // calls addUser that is located in $user_inc
        $error = $blankUserStr;
      } else {
        user_add_user ( $user, $upassword1, $ufirstname, $ulastname,
          $uemail, $uis_admin );
      }
    }
  } else if ( ! empty ( $add ) &&
    ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
    $error = $notAuthStr;
  } else {
    // Don't allow a user to change themself to an admin by setting
    // uis_admin in the URL by hand.  They must be admin beforehand.
    if ( ! $is_admin )
      $uis_admin = 'N';
    user_update_user ( $user, $ufirstname, $ulastname,
      $uemail, $uis_admin );
  }
}

echo error_check('users.php', false);
?>

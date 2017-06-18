<?php
/* $Id: edit_user_handler.php,v 1.47.2.12 2012/02/28 02:07:45 cknudsen Exp $ */

// There is the potential for a lot of mischief from users trying to access this
// file in ways they shouldn't. Users may try to type in a URL to get around
// functions that are not being displayed on the web page to them.
include_once 'includes/init.php';
require_valide_referring_url ();
load_user_layers ();

$delete = getPostValue ( 'delete' );
$formtype = getPostValue ( 'formtype' );
$add = getPostValue ( 'add' );
$user = getPostValue ( 'user' );
$ufirstname = getPostValue ( 'ufirstname' );
$ulastname = getPostValue ( 'ulastname' );
$uemail = getPostValue ( 'uemail' );
$upassword1 = getPostValue ( 'upassword1' );
$upassword2 = getPostValue ( 'upassword2' );
$uis_admin = getPostValue ( 'uis_admin' );
$uenabled = getPostValue ( 'uenabled' );

$error = '';
if ( ! $is_admin )
  $user = $login;

$deleteStr = translate ( 'Deleting users not supported.' );
$notIdenticalStr = translate ( 'The passwords were not identical.' );
$noPasswordStr = translate ( 'You have not entered a password.' );
$blankUserStr = translate ( 'Username cannot be blank.' );

// Don't let them edit users if they'e not authorized.
if ( empty ( $user ) ) {
  // Asking to create a new user. Must be admin...
  if ( ! $is_admin && ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
    send_to_preferred_view ();

  if ( ! $admin_can_add_user ) {
    // If adding users is not allowed...
    send_to_preferred_view ();
    exit;
  }
} else {
  // User is editing their account info.
  if ( ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
    send_to_preferred_view ();
}

// Handle delete.
if ( ! empty ( $delete ) && $formtype == 'edituser' ) {
  if ( access_can_access_function ( ACCESS_USER_MANAGEMENT ) ) {
    if ( $admin_can_delete_user ) {
      user_delete_user ( $user ); // Will also delete user's events.
      activity_log ( 0, $login, $user, LOG_USER_DELETE, '' );
    } else
      $error = $deleteStr;
  } else
    $error = print_not_auth (15);
} else {
  // Handle update of password.
  if ( $formtype == 'setpassword' && strlen ( $user ) ) {
    if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) && !
        access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
      $error = print_not_auth (17);
    else
    if ( $upassword1 != $upassword2 )
      $error = $notIdenticalStr;
    else {
      if ( strlen ( $upassword1 ) ) {
        if ( $user_can_update_password ) {
          user_update_user_password ( $user, $upassword1 );
          activity_log ( 0, $login, $user, LOG_USER_UPDATE,
            translate ( 'Set Password' ) );
        } else
          $error = print_not_auth (18);
      } else
        $error = $noPasswordStr;
    }
  } else {
    // Handle update of user info.
    if ( $formtype == 'edituser' ) {
      if ( ! empty ( $add ) && $is_admin ) {
        if ( $upassword1 != $upassword2 )
          $error = $notIdenticalStr;
        else {
          if ( addslashes ( $user ) != $user )
            // This error should get caught before here anyhow,
            // so no need to translate this. This is just in case. :-)
            $error = 'Invalid characters in login.';
          else {
            if ( empty ( $user ) )
              // Username cannot be blank. This is currently the only place
              // that calls addUser that is located in $user_inc.
              $error = $blankUserStr;
            else {
              user_add_user ( $user, $upassword1, $ufirstname, $ulastname,
                $uemail, $uis_admin, $u_enabled );
              activity_log ( 0, $login, $user, LOG_USER_ADD,
                "$ufirstname $ulastname"
                 . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
            }
          }
        }
      } else {
        if ( ! empty ( $add ) && !
            access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
          $error = print_not_auth (15);
        else {
          // Don't allow a user to change themself to an admin by setting
          // uis_admin in the URL by hand. They must be admin beforehand.
          if ( ! $is_admin )
            $uis_admin = 'N';

          user_update_user ( $user, $ufirstname, 
					  $ulastname, $uemail, $uis_admin, $uenabled );
          activity_log ( 0, $login, $user, LOG_USER_UPDATE,
            "$ufirstname $ulastname" . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
        }
      }
    }
  }
}

echo error_check ( 'users.php', false );

?>

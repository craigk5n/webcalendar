<?php // $Id: edit_user_handler.php,v 1.54.2.1 2012/02/28 15:43:10 cknudsen Exp $

// There is the potential for a lot of mischief from users trying to access this
// file in ways they shouldn't. Users may try to type in a URL to get around
// functions that are not being displayed on the web page to them.
include_once 'includes/init.php';
require_valid_referring_url ();
load_user_layers();

$referer = '';
if ( ! empty ( $_SERVER['HTTP_REFERER']) ) {
  $refurl = parse_url($_SERVER['HTTP_REFERER']);
  if (!empty($refurl['path']))
    $referer = strrchr($refurl['path'], '/edit_user.php' );
}

if (  $referer != '/edit_user.php' ) {
  activity_log( 0, $login, $login, SECURITY_VIOLATION, 'Hijack attempt:edit_user' );
  exit; 
}

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
$uenabled = getPostValue ( 'u_enabled' );

$error = '';
if ( ! $is_admin )
  $user = $login;

$notAuthStr = print_not_auth();
$deleteStr = translate ( 'Deleting users not supported.' );
$notIdenticalStr = translate ( 'The passwords were not identical.' );
$noPasswordStr = translate ( 'You have not entered a password.' );
$blankUserStr = translate ( 'Username cannot be blank.' );

// Don't let them edit users if they'e not authorized.
if ( empty ( $user ) ) {
  // Asking to create a new user. Must be admin...
  if ( ! $is_admin && ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
    send_to_preferred_view();

  if ( ! $admin_can_add_user ) {
    // If adding users is not allowed...
    send_to_preferred_view();
    exit;
  }
} else {
  // User is editing their account info.
  if ( ! access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
    send_to_preferred_view();
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
    $error = $notAuthStr;
} else {
  // Handle update of password.
  if ( $formtype == 'setpassword' && strlen ( $user ) ) {
    if ( ! access_can_access_function ( ACCESS_USER_MANAGEMENT ) && !
        access_can_access_function ( ACCESS_ACCOUNT_INFO ) )
      $error = $notAuthStr;
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
          $error = $notAuthStr;
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
                $uemail, $uis_admin, $uenabled );
              activity_log ( 0, $login, $user, LOG_USER_ADD,
                "$ufirstname $ulastname"
                 . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
            }
          }
        }
      } else {
        if ( ! empty ( $add ) && !
            access_can_access_function ( ACCESS_USER_MANAGEMENT ) )
          $error = $notAuthStr;
        else {
          // Don't allow a user to change themself to an admin by setting
          // uis_admin in the URL by hand. They must be admin beforehand.
          if ( ! $is_admin )
            $uis_admin = 'N';

          user_update_user ( $user, $ufirstname, $ulastname, $uemail, $uis_admin, $uenabled );
          activity_log ( 0, $login, $user, LOG_USER_UPDATE,
            "$ufirstname $ulastname" . ( empty ( $uemail ) ? '' : " <$uemail>" ) );
        }
      }
    }
  }
}

echo error_check ( 'users.php', false );

?>

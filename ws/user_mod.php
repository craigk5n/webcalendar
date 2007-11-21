<?php
/* $Id$
 *
 * Description:
 *  Web Service functionality to add, delete or update a user.
 *
 * Input Parameters:
 *  username   - user login of user to add/edit
 *  firstname* - user firstname
 *  lastname*  - user lastname
 *  password*  - user password
 *  admin*     - is admin (1 or 0)
 *  email*     - email address
 *  add*       - 1=adding user
 *  del*       - 1=deleting user
 *   (*) optional
 *
 * Result:
 *  On success:
 *    <result><success/></result>
 *  On failure/error:
 *    <result><error>Error message here...</error></result>
 *
 * Notes:
 *  If updating a user, the omission of a parameter (email, for example) will
 *  result in the value being set to an empty string (the old value will be
 *  preserved)... except for password, which cannot be blank.
 *
 * Developer Notes:
 *  If you enable the WS_DEBUG option below,
 *  all data will be written to a debug file in /tmp also.
 *
 * Security:
 *  - Remote user must be an admin user
 *  - User include Class (User, User-ldap, etc.) must have the
 *    _WC_ADMIN_CAN_ADD_USER define to add a user.
 *    _WC_ADMIN_CAN_DELETE_USER defined to delete a user.
 */

$WS_DEBUG = false;

$error = '';

require_once 'ws.php';

// Initialize...
ws_init ();

// header ( "Content-type: text/xml" );
header ( 'Content-type: text/plain' );

echo '<?xml version="1.0" encoding="UTF-8"?'.">\n";

$out = '
<result>';

// If not an admin user, they cannot do this...
if ( ! $WC->isAdmin() )
  $error = translate ( 'Not authorized (not admin).' );

// Some installs do not allow.
if ( empty ( $error ) && ! _WC_ADMIN_CAN_ADD_USER )
  $error = translate ( 'Not authorized' );

$addIn = $WC->getGET ( 'add' );
$add = ( ! empty ( $addIn ) && $addIn == '1' );

$deleteIn = $WC->getGET ( 'delete' );
if ( empty ( $deleteIn ) )
  $deleteIn = $WC->getGET ( 'del' );

$delete = ( ! empty ( $deleteIn ) && $deleteIn == '1' );
$user_admin = $WC->getGET ( 'admin' );
$user_email = $WC->getGET ( 'email' );
$user_firstname = $WC->getGET ( 'firstname' );
$user_lastname = $WC->getGET ( 'lastname' );
$user_login = $WC->getGET ( 'username' );
$user_password = $WC->getGET ( 'password' );

// This error should not happen in a properly written client,
// so no need to translate it.
if ( empty ( $error ) && empty ( $user_login ) )
  $error = 'Username can not be blank.';

// Check for invalid characters in the login.
if ( empty ( $error ) && addslashes ( $user_login ) != $user_login )
  $error = translate ( 'Invalid characters in login' );

// Check to see if username exists...
if ( empty ( $error ) ) {
  if ( $old = $WC->User->loadVariables ( $user_login ) ) {
    // username does already exist...
    if ( $add )
      $error = str_replace ( 'XXX', ws_escape_xml ( $user_login ),
        translate ( 'Username XXX already exists.' ) );
  } else {
    // username does not already exist...
    if ( ! $add || $delete )
      $error = str_replace ( 'XXX', ws_escape_xml ( $user_login ),
        translate ( 'Username XXX does not exist.' ) );
  }
}

// If adding a user, make sure a password was provided
if ( empty ( $error ) && $add && empty ( $user_password ) )
  $error = translate ( 'You have not entered a password' );

if ( empty ( $error ) && ! $add && ! $delete && empty ( $user_password ) )
  $user_password = $old['password'];

// admin must be 'Y' or 'N' for call to user_add_user ()
$user_admin = ( empty ( $user_admin ) || $user_admin != '1' ? 'N' : 'Y' );

// If user is editing themself, do not let them take away admin setting.
// We don't want them to accidentally have no admin users left.
if ( empty ( $error ) && $WC->login( $user_login ) && $user_admin == 'N' )
  $error = translate ( 'You cannot remove admin rights from yourself!' );

if ( empty ( $error ) && $delete )
  $WC->User->deleteUser ( $user_login );
// We don't check return status... hope it worked.
else
if ( empty ( $error ) && $add ) {
	$params = array ( 'cal_login'=>$user_login,
	  'cal_password'=>$user_password,
		'cal_lastname'=>$user_lastname,
		'cal_firstname'=>$user_firstname,
		'cal_email'=>$user_email,
		'cal_is_admin'=>$user_admin );
  if ( $WC->User->addUser ( $params ) ) {
    // success    :-)
  } else
    // error
    $error = ( empty ( $error )
      ? translate ( 'Unknown error saving user' )
      :// In case there are any strange chars in a db error message.
      ws_escape_xml ( $error ) );
} else
if ( empty ( $error ) ) {
  // update
	$params = array ( 'cal_login_id'=>$user_login,
	  'cal_firstname'=>$user_firstname,
		'cal_lastname'=>$user_lastname,
		'cal_email'=>$user_email,
		'cal_is_admin'=>$user_admin );
  if ( $WC->User->updateUser ( $params ) ) {
    // success    :-)
  } else
    // error
    $error = ( empty ( $error )
      ? translate ( 'Unknown error saving user' )
      :// In case there are any strange chars in a db error message.
      ws_escape_xml ( $error ) );
}

$out .= ( empty ( $error ) ? '
  <success/>' : '
  <error>' . $error . '</error>' ) . '
</result>
';

// If web service debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG )
  ws_log_message ( $out );

// Send output now...
echo $out;

?>

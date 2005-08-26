<?php
/*
 * $Id$
 *
 * Description:
 *	Web Service functionality to update or add a user.
 *
 * Input Parameters:
 *	username - user login of user to add/edit
 *	firstname* - user firstname
 *	lastname* - user lastname
 *	password* - user password
 *	admin* - is admin (1 or 0)
 *	email* - email address
 *	add* - 1=adding user
 *	(*) optional
 *
 * Result:
 *	On success:
 *		<result><success/></result>
 *	On failure/error:
 *		<result><error>Error message here...</error></result>
 *
 * Notes:
 *	If updating a user, the omission of a parameter (email, for example)
 *	will result in the value being set to an empty string
 *	(the old value will be preserved)... except for password, which
 *	cannot be blank.
 *
 * Developer Notes:
 *	If you enable the WS_DEBUG option below, all data will be written
 *	to a debug file in /tmp also.
 *
 * Security:
 *	- Remote user must be an admin user
 *	- User include file (user.php, user-ldap.php, etc.) must have the
 *	  following global variable set:
 *	    $admin_can_add_user
 *
 */

$WS_DEBUG = false;

// Security precaution.  In case, register_globals is on, unset anything
// a malicious user may set in the URL.
$admin_can_add_user = false;
$error = '';

require_once "ws.php";

// Initialize...
ws_init ();

//Header ( "Content-type: text/xml" );
Header ( "Content-type: text/plain" );

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$out = '<result>';

// If not an admin user, they cannot do this...
if ( ! $admin ) {
  $error = translate("Not authorized");
}

// Some installs do not allow
if ( empty ( $error ) && ! $admin_can_add_user ) {
  $error = translate("Not authorized");
}

$user_login = getGetValue ( 'username' );
$user_firstname = getGetValue ( 'firstname' );
$lastname = getGetValue ( 'lastname' );
$password = getGetValue ( 'password' );
$admin = getGetValue ( 'admin' );
$email = getGetValue ( 'email' );
$add = getGetValue ( 'add' );

// This error should not happen in a properly written client, so no need to
// translate it.
if ( empty ( $error ) && empty ( $user_login ) ) {
  $error = translate ( "Username can not be blank" );
}

// Check for invalid characters in the login
if ( empty ( $error ) && addslashes ( $user_login ) != $user_login ) {
  $error = "Invalid characters in login.";
}

if ( empty ( $add ) || $add != '1' ) {
  $add = false;
} else {
  $add = true;
}

// Check to see if username exists
if ( empty ( $error ) ) {
  if ( user_load_variables ( $user_login, 'old_' ) ) {
    // username does already exist
    if ( $add ) {
      $error = "User " . ws_escape_xml ( $user_login ) .
        " already exists";
    }
  } else {
    // username does not already exist
    if ( $add ) {
      $error = "User " . ws_escape_xml ( $user_login ) .
        " does not exist";
    }
  }
}

// If adding a user, make sure a password was provided
if ( empty ( $error ) && $add && empty ( $user_password ) ) {
  $error = translate("You have not entered a password");
}

if ( empty ( $error ) && ! $add ) {
  if ( empty ( $user_password ) )
    $user_password = $old_password;
}

// admin must be 'Y' or 'N' for call to user_add_user()
if ( empty ( $user_admin ) || $user_admin != '1' )
  $user_admin = 'N';
else
  $user_admin = 'Y';

// If user is editing themself, do not let them take away admin
// setting.  We don't want them to accidentally have no admin
// users left.
if ( empty ( $error ) && $user_login == $login && $user_admin == 'N' ) {
  $error = 'You cannot remove admin rights from yourself';
}


if ( empty ( $error ) ) {
  if ( user_add_user ( $user_login, $user_password, $user_firstname,
    $user_lastname, $user_email, $user_admin ) ) {
    // success    :-)
  } else {
    // error
    if ( empty ( $error ) ) {
      $error = 'Unknown error saving user';
    } else {
      // In case there are any strange chars in a db error message
      $error = ws_escape_xml ( $error );
    }
  }
}

if ( empty ( $error ) ) {
  $out .= '<success/>';
} else {
  $out .= '<error>' . $error . '</error>';
}
$out .= "</result>\n";

// If web servic debugging is on...
if ( ! empty ( $WS_DEBUG ) && $WS_DEBUG ) {
  ws_log_message ( $out );
}

// Send output now...
echo $out;

?>

<?php
// This file contains all the functions for getting information
// about users via NIS.  So, if you want to use an authentication scheme
// other than the webcal_user table, you can just create a new
// version of each function found below.
//
// Note: this application assumes that usernames (logins) are unique.
//
// Note #2: If you are using HTTP-based authentication, then you still
// need these functions and you will still need to add users to
// webcal_user.
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
	
require( "User.class" );

class UserNis extends User {

// Allow auto-creation of WebCalendar Accounts for fully authenticated users
var $_allow_auto_create = true;
//this will normally be assigned as a CONSTANT, need this to allow auto-create
var $ACCESS_ACCOUNT_INFO = 16;

define ( 'CRYPT_SALT_LENGTH', 12 );

// $user_external_group = 100;
var $user_external_email = "domain.com";

function UserNis () {
// Set some config variables about your system.
$this->_user_can_update_password = false;
$this->_admin_can_add_user = false;
$this->_admin_can_delete_user = true; // will not affect NIS server info
}


// Check to see if a given login/password is valid.  If invalid,
// the error message will be placed in $error (a global variable).
// params:
//   $login - user login
//   $password - user password
// returns: true or false
function user_valid_login ( $login, $password ) {
  global $error,$user_external_group,$user_external_email;
  $ret = false;

  $data = @yp_match (yp_get_default_domain(), "passwd.byname", $login);
  if ( strlen ( $data ) ) {
    $data = explode ( ":", $data );
    if ( $user_external_group && $user_external_group != $data[3] ) {
      $error = translate ("Invalid login");
      return $ret;
    }
    if ( $data[1] == crypt ( $password, substr ( $data[1], 0,
      CRYPT_SALT_LENGTH ) ) ) {
      if ( count ( $data ) >= 4 ) {
        $ret = true;
        if ( $this->_allow_auto_create && preg_match ( "/\/login.php/", _WC_SCRIPT )) {
          //Test if user is in WebCalendar database
          $testuser = $WC->User->loadVariables ( $login );
          if ( empty ( $testuser['login'] ) || $testuser['login'] != $login ) {
            $WC->User->addUser ( $login, $password, "" , "", "", "N" );
            //Redirect new users to enter user date
            $GLOBALS["newUserUrl"] = "edit_user.php";
          } else {
					  //update password just in case it was changed outside WebCalendar
						$WC->User->setPassword ( $login, $password );
					}
        }
      } else {
       $error = translate ("Invalid login") . ": " .
         translate("incorrect password" );
       $ret = false;
      }
    }
  } else {
     // no such user
     $error = translate ("Invalid login") . ": " . translate("no such user");
     $ret = false;
  }
  return $ret;
}

}
?>

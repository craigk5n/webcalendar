<?php

// LDAP user functions.
// This file is intended to be used instead of the standard user.inc
// file.  I have not tested this yet (I do not have an LDAP server
// running yet), so please provide feedback.
//
// This file contains all the functions for getting information
// about users.  So, if you want to use an authentication scheme
// other than the webcal_user table, you can just create a new
// version of each function found below.
//
// Note: this application assumes that usernames (logins) are unique.
//
// Note #2: If you are using HTTP-based authentication, then you still
// need these functions and you will still need to add users to
// webcal_user.


// Set some global config variables about your system.
$user_can_update_password = false;
$admin_can_add_user = false;
$admin_can_delete_user = false;

// LDAP settings
$ldap_server = "ldap.synclude.com"; // change to your LDAP server name
$ldap_port = "389"; // port to connect to LDAP server (389 by default)
$ldap_base_dn = "ou=People,dc=synclude,dc=com"; // change to your base dn
$ldap_login_attr = "uid";	//attribute of LDAP objects to use with login name
// * The bind (login) to LDAP server is made with this dn (distinguished name):
// *      "$ldap_login_attr=$login,$ldap_base_dn"
// * Check with your LDAP administrator for the values of $ldap_login_attr
// * and $ldap_base_dn that you should use.
// * Users will need to use the corresponding value to login.
// * E.g., if you use cn,  your login might be "Jane Smith"
// *       if you use uid, your login might be "jsmith"

// attributes to fetch from LDAP and corresponding user variables
$ldap_user_attr = array( 
  // LDAP attribute		//WebCalendar variable
    "uid",			//login
    "sn",			//lastname
    "givenname",		//firstname
    "cn",			//fullname
    "mail",			//email
    "objectclass"		//isadmin
);


// Check to see if a given login/password is valid.  If invalid,
// the error message will be placed in $login_error.
// params:
//   $login - user login
//   $password - user password
// returns: true or false
function user_valid_login ( $login, $password ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr;

  $ds = ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
//echo "$ldap_login_attr=$login,$ldap_base_dn<br>"; //for debugging
    $r = ldap_bind ( $ds, "$ldap_login_attr=$login,$ldap_base_dn", $password ); // bind as the user
    if (!$r) {
      $error = "Invalid login";
      $ret = false;
    } else {
      $ret = true;
    }
    ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
    $ret = false;
  }
  return $ret;
}


// Load info about a user (first name, last name, admin) and set
// globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function user_load_variables ( $login, $prefix ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr, $ldap_user_attr;
  $ret = true;
  $ds = ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = ldap_bind ( $ds ); // this is an "anonymous" bind
    
    // search for user
    $sr = ldap_search ( $ds, $ldap_base_dn, "($ldap_login_attr=$login)", $ldap_user_attr );
    if (!$sr) {
      $error = "Error searching LDAP server: " . ldap_error();
      $ret =  false;
    } else {
      $info = ldap_get_entries ( $ds, $sr );
      if ( $info["count"] == 0 ) {
        $error = "No such user";
        $ret = false;
      } else {
        $GLOBALS[$prefix . "login"] = $login;
        $GLOBALS[$prefix . "firstname"] = $info[0]["givenname"][0];
        $GLOBALS[$prefix . "lastname"] = $info[0]["sn"][0];
        $GLOBALS[$prefix . "email"] = $info[0]["mail"][0];
        $GLOBALS[$prefix . "fullname"] = $info[0]["cn"][0];
        $GLOBALS[$prefix . "is_admin"] = user_is_admin($info[0]["objectclass"]);

        $ret = true;
      }
      ldap_free_result ( $sr );
    }
    ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
    $ret = false;
  }
//echo $error."<br>"; //for debugging
  return $ret;
}



// Add a new user.
// params:
//   $user - user login
//   $password - user password
//   $firstname - first name
//   $lastname - last name
//   $email - email address
//   $admin - is admin? ("Y" or "N")
function user_add_user ( $user, $password, $firstname, $lastname, $email,
  $admin ) {
  global $error;

  $error = "Not yet supported.";
  return false;
}


// Update a user
// params:
//   $user - user login
//   $firstname - first name
//   $lastname - last name
//   $email - email address
//   $admin - is admin?
function user_update_user ( $user, $firstname, $lastname, $email, $admin ) {
  global $error;

  $error = "Not yet supported.";
  return false;
}


// Update user password
// params:
//   $user - user login
//   $password - last name
function user_update_user_password ( $user, $password ) {
  global $error;

  $error = "Not yet supported";
  return false;
}



// Delete a user from the system.
// We assume that we've already checked to make sure this user doesn't
// have events still in the database.
// params:
//   $user - user to delete
function user_delete_user ( $user ) {
  $error = "Not yet supported";
  return false;
}


// Get a list of users and return info in an array.
function user_get_users () {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_user_attr;

  $count = 0;
  $ret = array ();

  $ds = ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    $r = ldap_bind ( $ds ); // this is an "anonymous" bind
    // search for user
    $sr = ldap_search ( $ds, $ldap_base_dn, "(objectclass=person)", $ldap_user_attr );
    $info = ldap_get_entries( $ds, $sr );
    for ( $i = 0; $i < $info["count"]; $i++ ) {
      $ret[$count++] = array (
        "cal_login" => $info[$i]["uid"][0],
        "cal_lastname" => $info[$i]["sn"][0],
        "cal_firstname" => $info[$i]["givenname"][0],
        "cal_is_admin" => user_is_admin($info[$i]["objectclass"]),
        "cal_email" => $info[$i]["mail"][0],
//        "cal_password" => $info[$i]["password"][0], //useless: password is (hopefully) encrypted
        "cal_fullname" => $info[$i]["cn"][0]
      );
    }
    ldap_free_result($sr);
    ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
  }

  return $ret;
}


// Test if a user is an admin, that is: if the user's LDAP 
// entry contains the value "webcalendar-isadmin".
// params:
//   $values - array of strings, as in the list of values for the LDAP attribute "objectclass"
// returns Y if user is admin, N if not
function user_is_admin($values) {
  for ($i=0; $i < $values["count"]; $i++) {
    if ( $values[$i] == "webcalendar-isadmin" )
      return "Y";
  }
  return "N";
}


?>

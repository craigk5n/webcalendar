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

//
// LDAP Server settings
//
// The name of the LDAP server or its address
$ldap_server = "localhost"; 
// The name or the number for the LDAP server port. By default an LDAP server
// is on port 389
$ldap_port = "389"; 
//
// Global LDAP Parameters
//
// The base DN to make search in order to find users and informations like a
// user list
$ldap_base_dn = "o=compagny.com"; 
// The ldap attribute used to find a user. The search will be made with
// $ldap_login_attr=user_login
// E.g., if you use cn,  your login might be "Jane Smith"
//       if you use uid, your login might be "jsmith"
$ldap_login_attr = "uid";
// A user DN used to made the search for informations. This user must have the
// correct rights to perform search.
// If left empty ("") the search will be made in anonymous.
// Check if you need an administravive right to make search
$ldap_admin_dn = "";
// The user password for search operations
$ldap_admin_pwd = "";
//
// LDAP Search parameters
//
// A LDAP filter to find a user list.
$ldap_user_filter = "(objectclass=person)";
// Attributes to fetch from LDAP and corresponding user variables in the
// application. Do change according to your LDAP Schema
$ldap_user_attr = array( 
  // LDAP attribute    //WebCalendar variable
  "uid",         //login
  "sn",          //lastname
  "givenname",   //firstname
  "cn",          //fullname
  "mail"         //email
);

// 
// Admin parameters
//
// A groupe name (complete DN) to find user with admin's rights
$ldap_admin_group_name = "cn=webcal_admin,ou=groupes,o=compagny.com";
// The LDAP attribute used to store member of a group
$ldap_admin_group_attr = "member";


// Function to search the dn of a given user
// the error message will be placed in $login_error.
// params:
//   $login - user login
//   $dn - complete dn for the user (must be given by ref )
// return:
//   TRUE if the user is found, FALSE in other case
function user_search_dn ( $login ,$dn ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr;
  global $ldap_admin_dn,$ldap_admin_pwd;

  $ret = false;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ( $ldap_admin_dn != "") {
      $r = @ldap_bind ( $ds, $ldap_admin_dn, $ldap_admin_pwd ); // bind as administrator
    } else {
      $r = @ldap_bind ( $ds ); // bind as anonymous
    }
    if (!$r) {
      $error = "Invalid Admin's login for LDAP Server";
    } else {
      $sr = @ldap_search ( $ds, $ldap_base_dn, "($ldap_login_attr=$login)");
      if (!$sr) {
        $error = "Error searching LDAP server: " . ldap_error();
      } else {
        $info = @ldap_get_entries ( $ds, $sr );
        if ( $info["count"] != 1 ) {
          $error = translate ("Invalid login");
        } else {
          $ret = true;
          $dn = $info[0]["dn"];
          //echo "Found dn : $dn\n";
        }
        @ldap_free_result ( $sr );
      }
      @ldap_close ( $ds );
    }
  } else {
    $error = "Error connecting to LDAP server";
    $ret = false;
  }
  return $ret;
}


// Check to see if a given login/password is valid.  If invalid,
// the error message will be placed in $login_error.
// params:
//   $login - user login
//   $password - user password
// returns: true or false
function user_valid_login ( $login, $password ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr;
  global $ldap_admin_dn,$ldap_admin_pwd;

  $ret = false;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ( user_search_dn ( $login, &$dn) ) {
      $r = @ldap_bind ( $ds, $dn, $password ); // bind as the user. The LDAP
      // Server will valide the login and passowrd
      if (!$r) {
        $error = translate ("Invalid login");
      } else {
        $ret = true;
      }
    } else {
      $error = translate ("Invalid login");
    }
    @ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
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
  global $ldap_admin_dn,$ldap_admin_pwd;

  $ret =  false;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ( $ldap_admin_dn != "") {
      // bind as administrator
      $r = @ldap_bind ( $ds, $ldap_admin_dn, $ldap_admin_pwd );
    } else {
      $r = @ldap_bind ( $ds ); // bind as anonymous
    }
    if (!$r) {
      $error = "Invalid Admin's login for LDAP Server";
    } else {
      // search for user
      $sr = @ldap_search ( $ds, $ldap_base_dn, "($ldap_login_attr=$login)",
        $ldap_user_attr );
      if (!$sr) {
        $error = "Error searching LDAP server: " . ldap_error();
      } else {
        $info = @ldap_get_entries ( $ds, $sr );
        if ( $info["count"] != 1 ) {
          $error = translate ("Invalid login");
        } else {
          $GLOBALS[$prefix . "login"] = $login;
          $GLOBALS[$prefix . "firstname"] = $info[0]["givenname"][0];
          $GLOBALS[$prefix . "lastname"] = $info[0]["sn"][0];
          $GLOBALS[$prefix . "email"] = $info[0]["mail"][0];
          $GLOBALS[$prefix . "fullname"] = $info[0]["cn"][0];
          $GLOBALS[$prefix . "is_admin"] = user_is_admin($login);
          $ret = true;
        }
        @ldap_free_result ( $sr );
      }
    }
    @ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
  }
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
  global $ldap_admin_dn,$ldap_admin_pwd,$ldap_user_filter;

  $count = 0;
  $ret = array ();
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ( $ldap_admin_dn != "") {
      // bind as administrator
      $r = @ldap_bind ( $ds, $ldap_admin_dn, $ldap_admin_pwd );
    } else {
      $r = @ldap_bind ( $ds ); // bind as anonymous
    }
    if (!$r) {
      $error = "Invalid Admin's login for LDAP Server";
    } else {
      // search for user
      $sr = @ldap_search ( $ds, $ldap_base_dn, $ldap_user_filter,
        $ldap_user_attr );
      $info = @ldap_get_entries( $ds, $sr );
      for ( $i = 0; $i < $info["count"]; $i++ ) {
        $ret[$count++] = array (
          "cal_login" => $info[$i]["uid"][0],
          "cal_lastname" => $info[$i]["sn"][0],
          "cal_firstname" => $info[$i]["givenname"][0],
          "cal_email" => $info[$i]["mail"][0],
          // Something to do here : is_admin is needed in one page (admin page)
          // as it generate a lot of search, we must do it another way
          "cal_is_admin" => user_is_admin($info[$i]["uid"][0]),
          "cal_fullname" => $info[$i]["cn"][0]
          );
      }
      @ldap_free_result($sr);
    }
    @ldap_close ( $ds );
  } else {
    $error = "Error connecting to LDAP server";
  }
  return $ret;
}


// Test if a user is an admin, that is: if the user is a member of a special
// group in the LDAP Server
// params:
//   $values - the login name
// returns Y if user is admin, N if not
function user_is_admin($values) {
  global $error, $ldap_server, $ldap_port;
  global $ldap_admin_dn,$ldap_admin_pwd;
  global $ldap_admin_group_name,$ldap_admin_group_attr;

  $ret = "N";
  if (!user_search_dn ($values, &$dn)) {
    $error = "can't find user";
  } else {
    $ds = @ldap_connect ( $ldap_server, $ldap_port );
    if ( !$ds ) {
      $error = "Error connecting to LDAP server";
    } else {
      if ( $ds ) {
        if ( $ldap_admin_dn != "") {
          // bind as administrator
          $r = @ldap_bind ( $ds, $ldap_admin_dn, $ldap_admin_pwd );
        } else {
          $r = @ldap_bind ( $ds ); // bind as anonymous
        }
        if (!$r) {
          $error = "Invalid Admin's login for LDAP Server";
        } else {
          $search_filter = "($ldap_admin_group_attr=$dn)";
          $sr = @ldap_search ( $ds, $ldap_admin_group_name, $search_filter,
            array("dn") );
          if ( @ldap_count_entries ( $ds, $sr) == 1 ) {
            $ret = "Y";
          }
          @ldap_free_result($sr);
        }
        @ldap_close ( $ds );
      }
    }
  }
  return $ret;
}


?>

<?php

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
$user_can_update_password = true;
$admin_can_add_user = true;
$admin_can_delete_user = true;


// Check to see if a given login/password is valid.  If invalid,
// the error message will be placed in $login_error.
// params:
//   $login - user login
//   $password - user password
// returns: true or false
function user_valid_login ( $login, $password ) {
  global $error;

  $login_error = "";

  $sql = "SELECT cal_login FROM webcal_user WHERE " .
    "cal_login = '" . $login . "' AND cal_passwd = '" . $password . "'";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] != "" ) {
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      if ( $row[0] == $login )
        $ret = true; // found login/password
      else
        $error = translate ("Invalid login");
    } else {
      $error = translate ("Invalid login");
    }
    dbi_free_result ( $res );
  } else {
    $error = translate("Database error") . ": " . dbi_error();
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
  $sql =
    "SELECT cal_firstname, cal_lastname, cal_is_admin, cal_email, cal_passwd " .
    "FROM webcal_user WHERE cal_login = '" . $login . "'";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $GLOBALS[$prefix . "login"] = $login;
      $GLOBALS[$prefix . "firstname"] = $row[0];
      $GLOBALS[$prefix . "lastname"] = $row[1];
      $GLOBALS[$prefix . "is_admin"] = $row[2];
      $GLOBALS[$prefix . "email"] = $row[3];
      if ( strlen ( $row[0] ) && strlen ( $row[1] ) )
        $GLOBALS[$prefix . "fullname"] = "$row[0] $row[1]";
      else
        $GLOBALS[$prefix . "fullname"] = $login;
      $GLOBALS[$prefix . "password"] = $row[4];
    }
    dbi_free_result ( $res );
  } else {
    $error = translate ("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
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

  if ( strlen ( $email ) )
    $uemail = "'" . $email . "'";
  else
    $uemail = "NULL";
  if ( strlen ( $firstname ) )
    $ufirstname = "'" . $firstname . "'";
  else
    $ufirstname = "NULL";
  if ( strlen ( $lastname ) )
    $ulastname = "'" . $lastname . "'";
  else
    $ulastname = "NULL";
  if ( strlen ( $password ) )
    $upassword = "'" . $password . "'";
  else
    $upassword = "NULL";
  if ( $admin != "Y" )
    $admin = "N";
  $sql = "INSERT INTO webcal_user " .
    "( cal_login, cal_lastname, cal_firstname, " .
    "cal_is_admin, cal_passwd, cal_email ) " .
    "VALUES ( '$user', $ulastname, $ufirstname, " .
    "'$admin', $upassword, $uemail )";
  if ( ! dbi_query ( $sql ) ) {
    $error = translate ("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
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

  if ( strlen ( $email ) )
    $uemail = "'" . $email . "'";
  else
    $uemail = "NULL";
  if ( strlen ( $firstname ) )
    $ufirstname = "'" . $firstname . "'";
  else
    $ufirstname = "NULL";
  if ( strlen ( $lastname ) )
    $ulastname = "'" . $lastname . "'";
  else
    $ulastname = "NULL";
  if ( $admin != "Y" )
    $admin = "N";

  $sql = "UPDATE webcal_user SET cal_lastname = $ulastname, " .
    "cal_firstname = $ufirstname, cal_email = $uemail," .
    "cal_is_admin = '$admin' WHERE cal_login = '$user'";
  if ( ! dbi_query ( $sql ) ) {
    $error = translate ("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
}


// Update user password
// params:
//   $user - user login
//   $password - last name
function user_update_user_password ( $user, $password ) {
  global $error;

  $sql = "UPDATE webcal_user SET cal_passwd = '$password' " .
    "WHERE cal_login = '$user'";
  if ( ! dbi_query ( $sql ) ) {
    $error = translate ("Database error") . ": " . dbi_error ();
    return false;
  }
  return true;
}



// Delete a user from the system.
// We assume that we've already checked to make sure this user doesn't
// have events still in the database.
// params:
//   $user - user to delete
function user_delete_user ( $user ) {
  $sql = "DELETE FROM webcal_user WHERE cal_login = '$user'";
  dbi_query ( $sql );
}


// Get a list of users and return info in an array.
function user_get_users () {
  $res = dbi_query ( "SELECT cal_login, cal_lastname, cal_firstname, " .
    "cal_is_admin, cal_email, cal_passwd FROM webcal_user " .
    "ORDER BY cal_lastname, cal_firstname, cal_login" );
  $count = 0;
  $ret = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
        $fullname = "$row[2] $row[1]";
      else
        $fullname = $row[0];
      $ret[$count++] = array (
        "cal_login" => $row[0],
        "cal_lastname" => $row[1],
        "cal_firstname" => $row[2],
        "cal_is_admin" => $row[3],
        "cal_email" => $row[4],
        "cal_password" => $row[5],
        "cal_fullname" => $fullname
      );
    }
    dbi_free_result ( $res );
  }
  return $ret;
}



?>

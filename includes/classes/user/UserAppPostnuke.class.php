<?php
// This file contains all the functions for getting information
// about users from PostNuke 0.7.2x.

// Reference to the application means the external application (postnuke)

// user-app-*.php auth files assume the following:
//   - login ids are unique within the application
//   - user administration is done through the application

// The following functions had to be configured to work with the application:
// - user_logged_in (returns login id if true)
// - get_admins (returns an array of admin login ids)
// - user_get_users (returns array of users)
// - user_load_variables (loads info about a user)

// JGH - May 2005
// I realized that I had hacked modules/NS-User/user.php to get postnuke
// to redirect back to webcal directly after logging in and didn't mention this.
// in function user_user_loginscreen() I added:
//    $URL = ($_REQUEST['url']) ? $_REQUEST['url'] : getenv("HTTP_REFERER");
// at the top after OpenTable() and changed the line that prints the hidden
// url parameter to:
//   "<input type=\"hidden\" name=\"url\" value=\"$URL\">""
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

require( "User.class" );

class UserAppPostnuke extends User {

/************************* Config ***********************************/

//------ Postnuke Specific Settings ------//
// PostNuke session id cookie
var $pn_sid = 'POSTNUKESID';

// Name of table containing users
var $pn_user_table = 'nuke_users';

// Name of table containing sessions
var $pn_session_table = 'nuke_session_info';

// Name of table containing group memberships
var $pn_group_table = 'nuke_group_membership';

// Name of table containing settings
var $pn_settings_table = 'nuke_module_vars';

// Set the group id of the postnuke group you want to be webcal admins.
// Default is set to the postnuke 'Admins' group
var $pn_admin_gid = '2';


//------ General Application Settings ------//
// What is the full URL to the login page (including http:// or https://)
var $app_login_page = 'http://www.mysite.com/postnuke/html/user.php?op=loginscreen&module=NS-User'; 

// Is there a parameter we can pass to tell the application to
// redirect the user back to the calendar after login?
var $app_redir_param = 'url';  // postnuke uses 'url'

// What is the full URL to the logout page (including http:// or https://)
var $app_logout_page = 'http://www.mysite.com/postnuke/html/user.php?module=NS-User&op=logout'; 

// Are the application's tables in the same database as webcalendar's?
var $app_same_db = '0';  // 1 = yes, 0 = no
 
// Only need configure the rest if $app_same_db != 1

 // Name of database containing the app's tables
var $app_db = 'postnuke';

// Host that the app's db is on
var $app_host = 'localhost';

// Login/Password to access the app's database
var $app_login = 'pnuser';
var $app_pass  = 'pnpassword';

/*************************** End Config *****************************/

function UserAppPostnuke () {
// Set some config variables about your system.
$this->_user_can_update_password = false;
$this->_admin_can_add_user = false;
$this->_admin_can_delete_user = true; // will not affect Postnuke server info
}



// Checks to see if the user is logged into the application
// returns: login id
function user_logged_in() {
  global $pn_sid, $_COOKIE;
  
  // First check to see if the user even has a session cookie
  if (empty($_COOKIE[$pn_sid])) return false;
  
  // Check to see if the session is still valid
  if (! $login = UserAppPostnuke::pn_active_session($_COOKIE[$pn_sid]) ) return false;

  // Update the session last access time
  UserAppPostnuke::pn_update_session($_COOKIE[$pn_sid]);

  return $login;
}


//  Checks to see if the session has a user associated with it and 
//  if the session is timed out 
//  returns: login id
function pn_active_session($sid) {
  global $pn_user_table, $pn_session_table, $pn_settings_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  // get login and last access time
  $sql = "SELECT pn_uname, pn_lastused FROM $pn_user_table, $pn_session_table  WHERE pn_sessid = ? ".
  "AND $pn_session_table.pn_uid <> 0 AND $pn_session_table.pn_uid=$pn_user_table.pn_uid ";
  $res = dbi_execute ( $sql, array( $sid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $login = $row[0];
      $last = $row[1];
    }
    dbi_free_result ( $res );
  }

  // Get inactive session time limit and see if we have passed it
  $sql = "SELECT pn_value FROM $pn_settings_table WHERE pn_modname = '/PNConfig' AND pn_name = 'secinactivemins'";
  $res = dbi_execute ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $tmp = explode('"', $row[0]);
      if (($tmp[1] > 0) && ($tmp[1] < ((time() - $last) / 60))) return false;
    }
    dbi_free_result ( $res );
  }

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return $login;
}


//  Updates the session table to set the last access time to now 
function pn_update_session($sid) {
  global $pn_session_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  // get login and last access time
  $sql = "UPDATE $pn_session_table  SET pn_lastused = ? WHERE pn_sessid = ? ";
  dbi_execute ( $sql, array( time(), $sid ) );

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return true;
}


// Searches postnuke database for $pn_admin_gid and returns an array of the group members.
// Do this search only once per request.
// returns: array of admin ids
function get_admins() {
  global $cached_admins, $pn_group_table, $pn_admin_gid;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  if ( ! empty ( $cached_admins ) ) return $cached_admins;
  $cached_admins = array ();

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT pn_uid FROM $pn_group_table WHERE pn_gid = ? && pn_uid <> 2";
  $res = dbi_execute( $sql, array( $pn_admin_gid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cached_admins[] = $row[0];
    }
  }

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return $cached_admins;
}


/// Get a list of users and return info in an array.
// returns: array of users
function getUsers () {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $pn_user_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  $Admins = UserAppPostnuke::get_admins();
  $count = 0;
  if ( $PUBLIC_ACCESS == "Y" )
    $_user_array[$count++] = array (
       "cal_login" => "__public__",
       "cal_lastname" => "",
       "cal_firstname" => "",
       "cal_is_admin" => "N",
       "cal_email" => "",
       "cal_password" => "",
       "cal_fullname" => $PUBLIC_ACCESS_FULLNAME );

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT pn_uid, pn_name, pn_uname, pn_email FROM $pn_user_table WHERE pn_uid <> 1 && pn_uid <> 2 ORDER BY pn_name";
  $res = dbi_execute ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      list($fname, $lname) = split (" ",$row[1]);
      $_user_array[$count++] = array (
        "cal_login" => $row[2],
        "cal_lastname" => $lname,
        "cal_firstname" => $fname,
        "cal_is_admin" => UserAppPostnuke::isAdmin($row[0],$Admins),
        "cal_email" => $row[3],
        "cal_fullname" => $row[1]
      );
    }
    dbi_free_result ( $res );
  }
  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return $_user_array;
}


// Load info about a user (first name, last name, admin) and set globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function loadVariables ( $login, $prefix ) {
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX;
  global $app_host, $app_login, $app_pass, $app_db, $pn_user_table;
  global $c, $db_host, $db_login, $db_password, $db_database, $app_same_db;
  
  if ($NONUSER_PREFIX && substr($login, 0, strlen($NONUSER_PREFIX) ) == $NONUSER_PREFIX) {
    nonuser_load_variables ( $login, $prefix );
    return true;
  }

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);
  
  $sql = "SELECT pn_uid, pn_name, pn_uname, pn_email FROM $pn_user_table WHERE pn_uname = ?";

  $res = dbi_execute ( $sql, array( $login ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      list($fname, $lname) = split (" ",$row[1]);
      $GLOBALS[$prefix . "login"] = $login;
      $GLOBALS[$prefix . "firstname"] = $fname;
      $GLOBALS[$prefix . "lastname"] = $lname;
      $GLOBALS[$prefix . "is_admin"] = UserAppPostnuke::isAdmin($row[0],
			  UserAppPostnuke::get_admins());
      $GLOBALS[$prefix . "email"] = $row[3];
      $GLOBALS[$prefix . "fullname"] = $row[1];
    }
    dbi_free_result ( $res );
  } else {
    $error = "Database error: " . dbi_error ();
    return false;
  }

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return true;
}

// Redirect the user to the application's login screen
function app_login_screen($return_path = 'index.php') {
  global $app_login_page, $app_redir_param;
  
  if ($return_path != '' && $app_redir_param != '') {
    if (strstr($app_login_page, '?')) {
      $app_login_page .= '&'.$app_redir_param.'='.$return_path;
    } else {
      $app_login_page .= '?'.$app_redir_param.'='.$return_path;
    }
  } 
  header("Location: $app_login_page");
  exit;
}


// Test if a user is an admin, that is: if the user is a member of a special
// group in the postnuke database
// params:
//   $values - the login name
// returns: Y if user is admin, N if not
function isAdmin($uid,$Admins) {
  if ( ! $Admins ) {
    return "N";
  } else if (in_array ($uid, $Admins)) {
    return "Y";
  } else {
    return "N";
  }
}

}
?>

<?php

if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}

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


/************************* Config ***********************************/

//------ Postnuke Specific Settings ------//
// PostNuke session id cookie
$pn_sid = 'POSTNUKESID';

// Name of table containing users
$pn_user_table = 'nuke_users';

// Name of table containing sessions
$pn_session_table = 'nuke_session_info';

// Name of table containing group memberships
$pn_group_table = 'nuke_group_membership';

// Name of table containing settings
$pn_settings_table = 'nuke_module_vars';

// Set the group id of the postnuke group you want to be webcal admins.
// Default is set to the postnuke 'Admins' group
$pn_admin_gid = '2';


//------ General Application Settings ------//
// What is the full URL to the login page (including http:// or https://)
$app_login_page = 'http://www.mysite.com/postnuke/html/user.php?op=loginscreen&module=NS-User'; 

// Is there a parameter we can pass to tell the application to
// redirect the user back to the calendar after login?
$app_redir_param = 'url';  // postnuke uses 'url'

// What is the full URL to the logout page (including http:// or https://)
$app_logout_page = 'http://www.mysite.com/postnuke/html/user.php?module=NS-User&op=logout'; 

// Are the application's tables in the same database as webcalendar's?
$app_same_db = '0';  // 1 = yes, 0 = no
 
// Only need configure the rest if $app_same_db != 1

 // Name of database containing the app's tables
$app_db = 'postnuke';

// Host that the app's db is on
$app_host = 'localhost';

// Login/Password to access the app's database
$app_login = 'pnuser';
$app_pass  = 'pnpassword';

/*************************** End Config *****************************/


// User administration should be done through the aplication's interface
$user_can_update_password = false;
$admin_can_add_user = false;

// Allow admin to delete user from webcal tables
$admin_can_delete_user = true;


// Checks to see if the user is logged into the application
// returns: login id
function user_logged_in() {
  global $pn_sid, $_COOKIE;
  
  // First check to see if the user even has a session cookie
  if (empty($_COOKIE[$pn_sid])) return false;
  
  // Check to see if the session is still valid
  if (! $login = pn_active_session($_COOKIE[$pn_sid]) ) return false;

  // Update the session last access time
  pn_update_session($_COOKIE[$pn_sid]);

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
function user_get_users () {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $pn_user_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  $Admins = get_admins();
  $count = 0;
  $ret = array ();
  if ( $PUBLIC_ACCESS == "Y" )
    $ret[$count++] = array (
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
      $ret[$count++] = array (
        "cal_login" => $row[2],
        "cal_lastname" => $lname,
        "cal_firstname" => $fname,
        "cal_is_admin" => user_is_admin($row[0],$Admins),
        "cal_email" => $row[3],
        "cal_fullname" => $row[1]
      );
    }
    dbi_free_result ( $res );
  }
  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return $ret;
}


// Load info about a user (first name, last name, admin) and set globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function user_load_variables ( $login, $prefix ) {
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX;
  global $app_host, $app_login, $app_pass, $app_db, $pn_user_table;
  global $c, $db_host, $db_login, $db_password, $db_database, $app_same_db;
  
  if ($NONUSER_PREFIX && substr($login, 0, strlen($NONUSER_PREFIX) ) == $NONUSER_PREFIX) {
    nonuser_load_variables ( $login, $prefix );
    return true;
  }
  
  if ( $login == "__public__" ) {
    $GLOBALS[$prefix . "login"] = $login;
    $GLOBALS[$prefix . "firstname"] = "";
    $GLOBALS[$prefix . "lastname"] = "";
    $GLOBALS[$prefix . "is_admin"] = "N";
    $GLOBALS[$prefix . "email"] = "";
    $GLOBALS[$prefix . "fullname"] = $PUBLIC_ACCESS_FULLNAME;
    $GLOBALS[$prefix . "password"] = "";
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
      $GLOBALS[$prefix . "is_admin"] = user_is_admin($row[0],get_admins());
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
function user_is_admin($uid,$Admins) {
  if ( ! $Admins ) {
    return "N";
  } else if (in_array ($uid, $Admins)) {
    return "Y";
  } else {
    return "N";
  }
}

// Delete a user from the webcalendar tables. (NOT from PostNuke)
// We assume that we've already checked to make sure this user doesn't
// have events still in the database.
// params:
//   $user - user to delete
function user_delete_user ( $user ) {
  // Get event ids for all events this user is a participant
  $events = array ();
  $res = dbi_execute ( "SELECT webcal_entry.cal_id " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND webcal_entry_user.cal_login = ?", array( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $res = dbi_execute ( "SELECT COUNT(*) FROM webcal_entry_user " .
      "WHERE cal_id = ?", array( $events[$i] ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] == 1 )
   $delete_em[] = $events[$i];
      }
      dbi_free_result ( $res );
    }
  }
  // Now delete events that were just for this user
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?", array( $delete_em[$i] ) );
  }

  // Delete user participation from events
  dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_login = ?", array( $user ) );

  // Delete preferences
  dbi_execute ( "DELETE FROM webcal_user_pref WHERE cal_login = ?", array( $user ) );

  // Delete from groups
  dbi_execute ( "DELETE FROM webcal_group_user WHERE cal_login = ?", array( $user ) );

  // Delete bosses & assistants
  dbi_execute ( "DELETE FROM webcal_asst WHERE cal_boss = ?", array( $user ) );
  dbi_execute ( "DELETE FROM webcal_asst WHERE cal_assistant = ?", array( $user ) );

  // Delete user's views
  $delete_em = array ();
  $res = dbi_execute ( "SELECT cal_view_id FROM webcal_view " .
    "WHERE cal_owner = ?", array( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_view_id = ?",
      array( $delete_em[$i] ) );
  }
  dbi_execute ( "DELETE FROM webcal_view WHERE cal_owner = ?", array( $user ) );

  // Delete layers
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_login = ?", array( $user ) );

  // Delete any layers other users may have that point to this user.
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = ?", array( $user ) );
}

// Functions we don't use with this file:
function user_update_user ( $user, $firstname, $lastname, $email, $admin ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
function user_update_user_password ( $user, $password ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
function user_add_user ( $user, $password, $firstname, $lastname, $email, $admin ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
?>

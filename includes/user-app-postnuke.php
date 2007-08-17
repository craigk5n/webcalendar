<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// This file contains all the functions for getting information
// about users from PostNuke 0.761

// Reference to the application means the external application (postnuke)

// user-app-*.php auth files assume the following:
//   - login ids are unique within the application
//   - user administration is done through the application

// The following functions had to be configured to work with the application:
// - app_active_session
// - app_update_session
// - user_logged_in (returns login id if true)
// - get_admins (returns an array of admin login ids)
// - user_get_users (returns array of users)
// - user_load_variables (loads info about a user)

// *** NOTE:
// webcal must be installed somewhere in the postnuke directory to read
// postnuke's cookie OR edit postnuke to make the cookie global:
//   change line 85 in includes/pnSession.php to:
//      ini_set('session.cookie_path', '/');

/************************* Config ***********************************/

// Location of postnuke config.php file (with trailing slash)
$app_path = '/usr/local/www/data/postnuke/';

// URL to postnuke (with trailing slash)
$app_url = 'http://'.$_SERVER['SERVER_NAME'].'/postnuke/';

// Table Prefix
$pn_table_prefix = 'pn_';

// Set the group id of the postnuke group you want to be webcal admins.
// Default is set to the postnuke 'Admins' group
$pn_admin_gid = '2';

/*************************** End Config *****************************/

// For postnuke, we can automatically fetch some values we need from the
// config.php file
$app_config = '';
$config_lines = file( $app_path . "config.php" );
foreach ( $config_lines as $line ) {
  preg_match ( "/pnconfig\['([\w]+)'\] = '([^']+)'/", $line, $match);
  $app_config[$match[1]] = $match[2];
}
unset ( $config_lines );

// PostNuke session id cookie (default is POSTNUKESID)
$pn_sid = 'POSTNUKESID';

// Application login form parameters
$app_login_page['action'] = $app_url.'user.php';
$app_login_page['username'] = 'uname';
$app_login_page['password'] = 'pass';
$app_login_page['remember'] = 'rememberme';
$app_login_page['submit'] = 'submit';
$app_login_page['return'] = 'url';
// hidden params
$app_login_page['hidden']['op'] = 'Login';
$app_login_page['hidden']['module'] = 'User';

// What is the full URL to the logout page (including http:// or https://)
$app_logout_page = $app_url.'user.php?module=NS-User&op=logout';

// Name of table containing users
$pn_user_table = $pn_table_prefix.'users';

// Name of table containing sessions
$pn_session_table = $pn_table_prefix.'session_info';

// Name of table containing group memberships
$pn_group_table = $pn_table_prefix.'group_membership';

// Name of table containing settings
$pn_settings_table = $pn_table_prefix.'module_vars';

// Name of database containing the app's tables
$app_db = $app_config['dbname'];

// Host that the app's db is on
$app_host = $app_config['dbhost'];

// Login/Password to access the app's database
$app_login = $app_config['dbuname'];
$app_pass  = $app_config['dbpass'];

if ( $app_config['encoded'] ) {
  $app_login = base64_decode ( $app_login );
  $app_pass  = base64_decode ( $app_pass );
}

// Debug
//var_dump($app_config);exit;

// Cleanup stuff we don't need anymore
unset ( $app_config );

/********************************************************************/

// Checks to see if the user is logged into the application
// returns: login id
function user_logged_in () {
  global $pn_sid, $_COOKIE;

  $sid = $_COOKIE[$pn_sid];

  // First check to see if the user even has a session cookie
  if ( empty ( $sid ) ) return false;

    // addslashes if magic_quotes_gpc is off
  if ( ! get_magic_quotes_gpc () ) $sid = addslashes ( $sid );

  // Check to see if the session is still valid
  if (! $login = pn_active_session( $sid ) ) return false;

  // Update the session last access time
  pn_update_session( $sid );

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
  $sql = "SELECT pn_uname, pn_lastused FROM $pn_user_table, $pn_session_table  WHERE pn_sessid = '$sid' ".
  "AND $pn_session_table.pn_uid <> 0 AND $pn_session_table.pn_uid=$pn_user_table.pn_uid ";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $login = $row[0];
      $last = $row[1];
    }
    dbi_free_result ( $res );
  }

  // Get inactive session time limit and see if we have passed it
  $sql = "SELECT pn_value FROM $pn_settings_table WHERE pn_modname = '/PNConfig' AND pn_name = 'secinactivemins'";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $tmp = explode ( '"', $row[0] );
      if ( ( $tmp[1] > 0 ) && ( $tmp[1] < ( ( time () - $last ) / 60 ) ) ) $login = false;
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
  $sql = "UPDATE $pn_session_table  SET pn_lastused = '".time ()."' WHERE pn_sessid = '$sid' ";
  dbi_query ( $sql );

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return true;
}

// Searches postnuke database for $pn_admin_gid and returns an array of the group members.
// Do this search only once per request.
// returns: array of admin ids
function get_admins () {
  global $cached_admins, $pn_group_table, $pn_admin_gid;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  if ( ! empty ( $cached_admins ) ) return $cached_admins;
  $cached_admins = array ();

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT pn_uid FROM $pn_group_table WHERE pn_gid = $pn_admin_gid";
  $res = dbi_query ( $sql );
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
function user_get_users ( $publicOnly=false ) {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $pn_user_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  $Admins = get_admins ();
  $count = 0;
  $ret = array ();
  if ( $PUBLIC_ACCESS == 'Y' )
    $ret[$count++] = array (
       'cal_login' => '__public__',
       'cal_lastname' => '',
       'cal_firstname' => '',
       'cal_is_admin' => 'N',
       'cal_email' => '',
       'cal_password' => '',
       'cal_fullname' => $PUBLIC_ACCESS_FULLNAME );
  if ( $publicOnly ) return $ret;
  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT pn_uid, pn_name, pn_uname, pn_email FROM $pn_user_table WHERE pn_uid > 1 ORDER BY pn_name";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      list ( $fname, $lname ) = split ( ' ',$row[1] );
      $ret[$count++] = array (
        'cal_login' => $row[2],
        'cal_lastname' => $lname,
        'cal_firstname' => $fname,
        'cal_is_admin' => user_is_admin ($row[0],$Admins),
        'cal_email' => $row[3],
        'cal_fullname' => $row[1]
      );
    }
    dbi_free_result ( $res );
  }
  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);
  usort ( $ret, 'sort_users');
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

  if ($NONUSER_PREFIX && substr ($login, 0, strlen ($NONUSER_PREFIX) ) == $NONUSER_PREFIX) {
    nonuser_load_variables ( $login, $prefix );
    return true;
  }

  if ( $login == '__public__' ) {
    $GLOBALS[$prefix . 'login'] = $login;
    $GLOBALS[$prefix . 'firstname'] = '';
    $GLOBALS[$prefix . 'lastname'] = '';
    $GLOBALS[$prefix . 'is_admin'] = 'N';
    $GLOBALS[$prefix . 'email'] = '';
    $GLOBALS[$prefix . 'fullname'] = $PUBLIC_ACCESS_FULLNAME;
    $GLOBALS[$prefix . 'password'] = '';
    return true;
  }

  // if postnuke is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT pn_uid, pn_name, pn_uname, pn_email FROM $pn_user_table WHERE pn_uname = '$login'";

  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      list ( $fname, $lname ) = split ( ' ',$row[1] );
      $GLOBALS[$prefix . 'login'] = $login;
      $GLOBALS[$prefix . 'firstname'] = $fname;
      $GLOBALS[$prefix . 'lastname'] = $lname;
      $GLOBALS[$prefix . 'is_admin'] = user_is_admin ($row[0],get_admins ());
      $GLOBALS[$prefix . 'email'] = $row[3];
      $GLOBALS[$prefix . 'fullname'] = $row[1];
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
    return false;
  }

  // if postnuke is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);
  //save these results
  $cached_user_var[$login][$prefix] = true;
  return true;
}

/*********************************************************************
 *
 *        Stuff that should stay the same for all user-app files
 *
 ********************************************************************/

// Are the application's tables in the same database as webcalendar's?
$app_same_db = (($db_database == $app_db) && ($app_host == $db_host)) ? '1' : '0';
//echo "Same DB:$app_same_db";exit;

// User administration should be done through the aplication's interface
$user_can_update_password = false;
$admin_can_add_user = false;

// Allow admin to delete user from webcal tables (not application)
$admin_can_delete_user = true;
$admin_can_disable_user = false;

// Redirect the user to the login-app.php page
function app_login_screen( $return ) {
  global $SERVER_URL;
  header ( "Location: {$SERVER_URL}login-app.php?return_path={$return}");
  exit;
}

// Test if a user is an admin, that is: if the user is a member of a special
// group in the application database
// params:
//   $values - the login name
// returns: Y if user is admin, N if not
function user_is_admin ($uid,$Admins) {
  if ( ! $Admins ) {
    return 'N';
  } else if (in_array ($uid, $Admins)) {
    return 'Y';
  } else {
    return 'N';
  }
}

// Delete a user from the webcalendar tables. (NOT from the application)
// We assume that we've already checked to make sure this user doesn't
// have events still in the database.
// params:
//   $user - user to delete
function user_delete_user ( $user ) {
  // Get event ids for all events this user is a participant
  $events = get_users_event_ids ( $user );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT( * ) FROM webcal_entry_user WHERE cal_id = ?',
      array ( $events[$i] ) );
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
    dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?",
      array ( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?",
      array ( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_log WHERE cal_entry_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_import_data WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_site_extras WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_reminders WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_blob WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?",
      array ( $delete_em[$i] )  );
  }

  // Delete user participation from events
  dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_login = ?",
    array ( $user ) );
  // Delete preferences
  dbi_execute ( "DELETE FROM webcal_user_pref WHERE cal_login = ?",
    array ( $user ) );
  // Delete from groups
  dbi_execute ( "DELETE FROM webcal_group_user WHERE cal_login = ?",
    array ( $user ) );
  // Delete bosses & assistants
  dbi_execute ( "DELETE FROM webcal_asst WHERE cal_boss = ?",
    array ( $user ) );
  dbi_execute ( "DELETE FROM webcal_asst WHERE cal_assistant = ?",
    array ( $user ) );
  // Delete user's views
  $delete_em = array ();
  $res = dbi_execute ( "SELECT cal_view_id FROM webcal_view WHERE cal_owner = ?",
    array ( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_view_id = ?",
      array ( $delete_em[$i] ) );
  }
  dbi_execute ( "DELETE FROM webcal_view WHERE cal_owner = ?",
    array ( $user ) );
  //Delete them from any other user's views
  dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_login = ?",
    array ( $user ) );
  // Delete layers
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_login = ?",
    array ( $user ) );
  // Delete any layers other users may have that point to this user.
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = ?",
    array ( $user ) );
  // Delete user
  dbi_execute ( "DELETE FROM webcal_user WHERE cal_login = ?",
    array ( $user ) );
  // Delete function access
  dbi_execute ( "DELETE FROM webcal_access_function WHERE cal_login = ?",
    array ( $user ) );
  // Delete user access
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_login = ?",
    array ( $user ) );
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_other_user = ?",
    array ( $user ) );
  // Delete user's categories
  dbi_execute ( "DELETE FROM webcal_categories WHERE cat_owner = ?",
    array ( $user ) );
  dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cat_owner = ?",
    array ( $user ) );
  // Delete user's reports
  $delete_em = array ();
  $res = dbi_execute ( "SELECT cal_report_id FROM webcal_report WHERE cal_login = ?",
    array ( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_report_template WHERE cal_report_id = ?",
      array ( $delete_em[$i] ) );
  }
  dbi_execute ( "DELETE FROM webcal_report WHERE cal_login = ?",
    array ( $user ) );
    //not sure about this one???
  dbi_execute ( "DELETE FROM webcal_report WHERE cal_user = ?",
    array ( $user ) );
  // Delete user templates
  dbi_execute ( "DELETE FROM webcal_user_template WHERE cal_login = ?",
    array ( $user ) );
}

// Functions we don't use with this file:
function user_update_user ( $user, $firstname, $lastname, $email, 
  $admin, $enabled ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
function user_update_user_password ( $user, $password ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
function user_add_user ( $user, $password, $firstname, $lastname, $email, 
  $admin, $enabled ) {
  global $error;
  $error = 'User admin not supported.'; return false;
}
?>
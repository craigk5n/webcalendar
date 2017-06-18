<?php
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

// This file contains all the functions for getting information
// about users from Joomla 1.0.8 and logging in/out
//  ** also compatible with Mambo 4.5.3


// Reference to the application means the external application (joomla)

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

/************************* Config ***********************************/

// Directory that contains the joomla configuration.php file (with trailing slash)
$app_path = '/usr/local/www/data/joomla/';

// Set the group id(s) of the joomla group(s) you want to be webcal admins.
// Default is set to the 'Super Administrator' and 'Administrator' groups
// Groups in core_acl_aro_groups table
$app_admin_gid = array('24','25');

/*************************** End Config *****************************/

// For Joomla, we can automatically fetch the values we need from the
// configuration.php file
$app_config = '';
$config_lines = file( $app_path . "configuration.php" ); 
foreach ( $config_lines as $line ) {
  preg_match("/mosConfig_([\w]+) = '([^']+)'/", $line, $match);
  if ( isset ( $match[1] ) && isset ( $match[2] ) )
    $app_config[$match[1]] = $match[2];
}
unset( $config_lines );

// Joomla 1.0.8 introduced session types
$app_session_type = ( isset( $app_config['session_type'] ) ) ? $app_config['session_type'] : '';
$app_secret = $app_config['secret'];

// Session id cookie name
$app_sid = app_cookie_name( $app_config['live_site'] );

// Session lifetime before required to login again
$app_sid_lifetime = $app_config['lifetime'];

// Name of table containing users
$app_user_table = $app_config['dbprefix'].'users';

// Name of table containing sessions
$app_session_table = $app_config['dbprefix'].'session';

// Application login form parameters
$app_login_page['action'] = $app_config['live_site'].'/index.php?option=login';
$app_login_page['username'] = 'username';
$app_login_page['password'] = 'passwd';
$app_login_page['remember'] = 'remember';
$app_login_page['submit'] = 'submit';
$app_login_page['return'] = 'return';
// hidden params
$app_login_page['hidden']['op2'] = 'login';

// What is the full URL to the logout page (including http:// or https://)
$app_logout_page = $app_config['live_site'].'/index.php?option=logout&op2=logout'; 

// Name of database containing the app's tables
$app_db = $app_config['db'];

// Host that the app's db is on
$app_host = $app_config['host'];

// Login/Password to access the app's database
$app_login = $app_config['user'];
$app_pass  = $app_config['password'];

// Debug
//var_dump($app_config);exit;

// Cleanup stuff we don't need anymore
unset( $app_config );

/********************************************************************/

//  Checks to see if the session has a user associated with it and 
//  if the session is timed out 
//  returns: login id
function app_active_session($sid) {
  global $app_user_table, $app_session_table, $app_settings_table, $app_sid_lifetime;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  // if application is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  // get login and last access time
  $sql = "SELECT username, time FROM $app_session_table  WHERE session_id = '$sid' ".
  "AND guest = 0 AND userid > 0 ";

  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $login = $row[0];
      $last = $row[1];
    }
    dbi_free_result ( $res );
  }
  // Did we pass inactive session time limit
  if ( ( $app_sid_lifetime > 0 ) && ( $last < ( time() - $app_sid_lifetime ) ) ) $login = false;

  // if application is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return $login;
}


// Joomla 1.0.8 introduced the option of three different session types
function app_get_sid( $id ) {
  global $app_secret, $app_session_type;

  $browser   = @$_SERVER['HTTP_USER_AGENT'];
  switch ( $app_session_type ) {
    case 2:
    // 1.0.0 to 1.0.7 Compatibility
    // lowest level security
      $value       = md5( $id . $_SERVER['REMOTE_ADDR'] );
      break;

    case 1:
    // slightly reduced security - 3rd level IP authentication for those behind IP Proxy 
      $remote_addr   = explode( '.', $_SERVER['REMOTE_ADDR'] );
      $ip        = $remote_addr[0] .'.'. $remote_addr[1] .'.'. $remote_addr[2];
      $value       = md5( $app_secret . md5( $id . $ip . $browser ) );
      break;
    
    default:
    // Highest security level - new default for 1.0.8 and beyond
      $ip        = $_SERVER['REMOTE_ADDR'];
      $value       = md5( $app_secret . md5( $id . $ip . $browser ) );
      break;
  } 
  return $value;
}

//  Updates the session table to set the last access time to now 
function app_update_session($sid) {
  global $app_session_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  // if application is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  // get login and last access time
  $sql = "UPDATE $app_session_table SET time = '".time()."' WHERE session_id = '$sid'";
  dbi_query ( $sql );

  // if application is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return true;
}

//Ge the proper cookie name. Borrowed from joomla.php
function app_cookie_name( $live_site='' ) {
  if( substr( $live_site, 0, 7 ) == 'http://' ) {
    $hash = md5( 'site' . substr( $live_site, 7 ) );
  } elseif( substr( $live_site, 0, 8 ) == 'https://' ) {
    $hash = md5( 'site' . substr( $live_site, 8 ) );
  } else {
    $hash = md5( 'site' . $live_site );
  }
  return $hash;
}
// Searches application database for $app_admin_gid and returns an array of the group members.
// Do this search only once per request.
// returns: array of admin ids
function get_admins() {
  global $cached_admins, $app_user_table, $app_admin_gid;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  if ( ! empty ( $cached_admins ) ) return $cached_admins;
  $cached_admins = array ();

  // if application is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  // what are the gid's of the admin group
  $where = '(';
  $num = count( $app_admin_gid );
  for ($i=0; $i<$num; $i++) {
    $where .= "gid = $app_admin_gid[$i]";
    if ( ( $i + 1 ) < $num ) $where .= ' OR ';
  }
  $where .= ')';

  $sql = "SELECT id FROM $app_user_table WHERE $where AND id > 0";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cached_admins[] = $row[0];
    }
  }

  // if application is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);

  return array_unique($cached_admins);
}


/// Get a list of users and return info in an array.
// returns: array of users
function user_get_users ( $publicOnly=false ) {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $app_user_table;
  global $app_host, $app_login, $app_pass, $app_db, $app_same_db;
  global $c, $db_host, $db_login, $db_password, $db_database;

  $Admins = get_admins();
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
  // if application is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);

  $sql = "SELECT id, name, username, email FROM $app_user_table WHERE id > 0 AND block = 0 ORDER BY name";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $flname = explode (' ',$row[1]);
      $fname = ( isset ( $flname[1] ) ? $flname[0] : $row[1] );
      $lname = ( isset ( $flname[1] ) ? $flname[1] : '' );
      $ret[$count++] = array (
        'cal_login' => $row[2],
        'cal_lastname' => $lname,
        'cal_firstname' => $fname,
        'cal_is_admin' => user_is_admin($row[0],$Admins),
        'cal_email' => $row[3],
        'cal_fullname' => $row[1]
      );
    }
    dbi_free_result ( $res );
  }
  // if application is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);
  usort ( $ret, 'sort_users');
  return $ret;
}


// Load info about a user (first name, last name, admin) and set globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function user_load_variables ( $login, $prefix ) {
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX, $cached_user_var;
  global $app_host, $app_login, $app_pass, $app_db, $app_user_table;
  global $c, $db_host, $db_login, $db_password, $db_database, $app_same_db;

  if ( ! empty ( $cached_user_var[$login][$prefix] ) )
    return  $cached_user_var[$login][$prefix];
  $cached_user_var = array();
  
  if ($NONUSER_PREFIX && substr($login, 0, strlen($NONUSER_PREFIX) ) == $NONUSER_PREFIX) {
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

  // if application is in a separate db, we have to connect to it
  if ($app_same_db != '1') $c = dbi_connect($app_host, $app_login, $app_pass, $app_db);
  
  $sql = "SELECT id, name, username, email FROM $app_user_table WHERE username = '$login'";

  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $flname = explode (' ',$row[1]);
      $fname = ( isset ( $flname[1] ) ? $flname[0] : $row[1] );
      $lname = ( isset ( $flname[1] ) ? $flname[1] : '' );
      $GLOBALS[$prefix . 'login'] = $login;
      $GLOBALS[$prefix . 'firstname'] = $fname;
      $GLOBALS[$prefix . 'lastname'] = $lname;
      $GLOBALS[$prefix . 'is_admin'] = user_is_admin($row[0],get_admins());
      $GLOBALS[$prefix . 'email'] = $row[3];
      $GLOBALS[$prefix . 'fullname'] = $row[1];
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
    return false;
  }

  // if application is in a separate db, we have to connect back to the webcal db
  if ($app_same_db != '1') $c = dbi_connect($db_host, $db_login, $db_password, $db_database);
  //save these results
  $cached_user_var[$login][$prefix] = true;
  return true;
}


// Checks to see if the user is logged into the application
// returns: login id
function user_logged_in() {
  global $app_sid, $_COOKIE;


  // First check to see if the user even has a session cookie
  if (empty($_COOKIE[$app_sid])) return false;

  // Generate session id
  $sid = app_get_sid( $_COOKIE[$app_sid] );

  // addslashes if magic_quotes_gpc is off
  if ( !get_magic_quotes_gpc() ) $sid = addslashes( $sid );

  // Check to see if the session is still valid
  if (! $login = app_active_session($sid) ) return false;

  // Update the session last access time
  app_update_session($sid);

  return $login;
}

/********************************************************************* 
 *
 *        Stuff that should stay the same for all user-app files 
 *
 ********************************************************************/

// Are the application's tables in the same database as webcalendar's?
$app_same_db = (($db_database == $app_db) && ($app_host == $db_host)) ? '1' : '0';
//echo "Same DB:$app_same_db";exit;

// User administration should be done through the application's interface
$user_can_update_password = false;
$admin_can_add_user = false;

// Allow admin to delete user from webcal tables (not application)
$admin_can_delete_user = false;

// Redirect the user to the login-app.php page 
function app_login_screen( $return ) {
  global $SERVER_URL;
  header("Location: {$SERVER_URL}login-app.php?return_path={$return}");
  exit;
}


// Test if a user is an admin, that is: if the user is a member of a special
// group in the application database
// params:
//   $values - the login name
// returns: Y if user is admin, N if not
function user_is_admin($uid,$Admins) {
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
    $res = dbi_query ( "SELECT COUNT(*) FROM webcal_entry_user " .
      "WHERE cal_id = " . $events[$i] );
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
    dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = " . $delete_em[$i] );
  }

  // Delete user participation from events
  dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_login = '$user'" );

  // Delete preferences
  dbi_query ( "DELETE FROM webcal_user_pref WHERE cal_login = '$user'" );

  // Delete from groups
  dbi_query ( "DELETE FROM webcal_group_user WHERE cal_login = '$user'" );

  // Delete bosses & assistants
  dbi_query ( "DELETE FROM webcal_asst WHERE cal_boss = '$user'" );
  dbi_query ( "DELETE FROM webcal_asst WHERE cal_assistant = '$user'" );

  // Delete user's views
  $delete_em = array ();
  $res = dbi_query ( "SELECT cal_view_id FROM webcal_view " .
    "WHERE cal_owner = '$user'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_query ( "DELETE FROM webcal_view_user WHERE cal_view_id = " .
      $delete_em[$i] );
  }
  dbi_query ( "DELETE FROM webcal_view WHERE cal_owner = '$user'" );

  // Delete layers
  dbi_query ( "DELETE FROM webcal_user_layers WHERE cal_login = '$user'" );

  // Delete any layers other users may have that point to this user.
  dbi_query ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = '$user'" );
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

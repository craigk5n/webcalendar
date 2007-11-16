<?php

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
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

require( 'User.class.php' );

class UserAppJoomla extends User {
// Directory that contains the joomla configuration.php file (with trailing slash)
var $_app_path = _WC_USER_APP_PATH;


//echo "Same DB:$app_same_db";exit;

// User administration should be done through the application's interface
$user_can_update_password = false;
$admin_can_add_user = false;

// Allow admin to delete user from webcal tables (not application)
$admin_can_delete_user = false;

// Set the group id(s) of the joomla group(s) you want to be webcal admins.
// Default is set to the 'Super Administrator' and 'Administrator' groups
// Groups in core_acl_aro_groups table
var $_app_admin_gid = array ('24','25');
var $_admins;
var $_app_config;
var $_app_sid;
var $_app_user_table;
var $_app_session_table;
var $_app_login_page;
var $_app_logout_page;
var $_app_same_db;

function UserAppJoomla () {
// For Joomla, we can automatically fetch the values we need from the
// configuration.php file
$config_lines = file( $this->_app_path . 'configuration.php' );
foreach ( $config_lines as $line ) {
  preg_match ( "/mosConfig_([\w]+) = '([^']+)'/", $line, $match);
    if ( isset ( $match[1] ) && isset ( $match[2] ) )
  $this->_app_config[$match[1]] = $match[2];
}
unset ( $config_lines );

// Session id cookie name
$this->_app_sid = app_cookie_name();

// Name of table containing users
$this->_app_user_table = $this->_app_config['dbprefix'] . 'users';

// Name of table containing sessions
$this->_app_session_table = $this->_app_config['dbprefix'] . 'session';

// Application login form parameters
$this->_app_login_page['action'] = $this->_app_config['live_site']
  .'/index.php?option=login';
$this->_app_login_page['username'] = 'username';
$this->_app_login_page['password'] = 'passwd';
$this->_app_login_page['remember'] = 'remember';
$this->_app_login_page['submit'] = 'submit';
$this->_app_login_page['return'] = 'return';
// hidden params
$this->_app_login_page['hidden']['op2'] = 'login';

// What is the full URL to the logout page (including http:// or https://)
$this->_app_logout_page = $this->_app_config['live_site']
  .'/index.php?option=logout&op2=logout';

// Are the application's tables in the same database as webcalendar's?
$this->_app_same_db = ((_WC_DB_DATABASE == $this->_app_config['db']) 
  && (_WC_DB_HOST == $this->_app_config['host'] ) ) ? true : false;

//set the list of admin users
$this->_get_admins();
// Debug
//var_dump($this->_app_config);exit;
}

//  Checks to see if the session has a user associated with it and
//  if the session is timed out
//  returns: login id
function app_active_session ($sid) {

  $this->_app_db_connect ();

  // get login and last access time
  $sql = 'SELECT username, time FROM ?  
	  WHERE session_id = ? AND guest = 0 AND userid > 0';

  $res = dbi_execute ( $sql, array ( $this->_app_session_table, $sid ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $login = $row[0];
      $last = $row[1];
    }
    dbi_free_result ( $res );
  }

  // Did we pass inactive session time limit
  if ( ( $this->_app_config['lifetime'] > 0 ) && ( $last < ( time () - $this->_app_config['lifetime'] ) ) ) $login = false;

  $this->_app_db_connect ( false );

  return $login;
}

// Joomla 1.0.8 introduced the option of three different session types
function app_get_sid( $id ) {

  $browser   = @$_SERVER['HTTP_USER_AGENT'];
  switch ( $this->_app_config['session_type'] ) {
    case 2:
    // 1.0.0 to 1.0.7 Compatibility
    // lowest level security
      $value = md5 ( $id . $_SERVER['REMOTE_ADDR'] );
      break;

    case 1:
    // slightly reduced security - 3rd level IP authentication for those behind IP Proxy
      $remote_addr = explode ( '.', $_SERVER['REMOTE_ADDR'] );
      $ip = $remote_addr[0] .'.'. $remote_addr[1] .'.'. $remote_addr[2];
      $value = md5 ( $this->_app_config['secret'] 
			  . md5 ( $id . $ip . $browser ) );
      break;

    default:
    // Highest security level - new default for 1.0.8 and beyond
      $ip        = $_SERVER['REMOTE_ADDR'];
      $value       = md5 ( $this->_app_config['secret'] 
			  . md5 ( $id . $ip . $browser ) );
      break;
  }
  return $value;
}

//  Updates the session table to set the last access time to now
function app_update_session($sid) {

  $this->_app_db_connect ();

  // get login and last access time
  $sql = 'UPDATE ? SET time = ? WHERE session_id = ?';
  dbi_execute ( $sql, array ( $this->_app_session_table,  time (), $sid )  );

  $this->_app_db_connect ( false );

  return true;
}

//Get the proper cookie name. Borrowed from joomla.php
function app_cookie_name() {
  if( substr( $this->_app_config['live_site'], 0, 7 ) == 'http://' ) {
    $hash = md5( 'site' . substr( $this->_app_config['live_site'], 7 ) );
  } elseif( substr( $this->_app_config['live_site'], 0, 8 ) == 'https://' ) {
    $hash = md5( 'site' . substr( $this->_app_config['live_site'], 8 ) );
  } else {
    $hash = md5( 'site' . $this->_app_config['live_site'] );
  }
  return $hash;
}
// Searches application database for $_app_admin_gid and returns an array of the group members.
// Do this search only once per request.
function _get_admins () {

  if ( is_array ( $this->_admins ) ) return true;
  $admins = array ();

  $this->_app_db_connect ();

  $sql = 'SELECT DISTINCT(id) FROM ? WHERE id > 0 AND gid IN (';
	$query_params = array();
  $query_params[] = $this->_app_user_table;
  for ( $i = 0, $cnt = count ( $this->_app_admin_gid; $i < $cnt; $i++ ) {
    if ( $i > 0 )
      $sql .= ', ';
    $sql .= '?';
    $query_params[] = $this->_app_admin_gid[$i];
  }
	$sql .= ' )';						
  $res = dbi_execute ( $sql, $query_params );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $admins[] = $row[0];
    }
  }

  $this->_app_db_connect ( false );
	
  $this->_admins = $admins;
	
  return true;
}

/// Get a list of users and return info in an array.
// returns: array of users
function user_get_users ( $publicOnly=false ) {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME;

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
	
  $this->_app_db_connect ();

  $sql = 'SELECT id, name, username, email FROM ? 
	  WHERE id > 0 AND block = 0 ORDER BY name';
  $res = dbi_execute ( $sql, array ( $this->_app_user_table) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $flname = explode (' ',$row[1]);
      $fname = ( isset ( $flname[1] ) ? $flname[0] : $row[1] );
      $lname = ( isset ( $flname[1] ) ? $flname[1] : '' );
      $ret[$count++] = array (
        'cal_login' => $row[2],
        'cal_lastname' => $lname,
        'cal_firstname' => $fname,
        'cal_is_admin' => $this->_app_user_is_admin ( $row[0] ),
        'cal_email' => $row[3],
        'cal_fullname' => $row[1]
      );
    }
    dbi_free_result ( $res );
  }
  $this->_app_db_connect ( false );
	
  usort ( $ret, 'sort_users');
  return $ret;
}

// Load info about a user (first name, last name, admin) and set globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function user_load_variables ( $login, $prefix ) {
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX, $cached_user_var;
	
  if ( ! empty ( $cached_user_var[$login][$prefix] ) )
    return  $cached_user_var[$login][$prefix];
  $cached_user_var = array ();

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

  $this->_app_db_connect ();

  $sql = 'SELECT id, name, username, email FROM ? WHERE username = ?';

  $res = dbi_execute ( $sql, array ( $this->_app_user_table, $login ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $flname = explode (' ',$row[1]);
      $fname = ( isset ( $flname[1] ) ? $flname[0] : $row[1] );
      $lname = ( isset ( $flname[1] ) ? $flname[1] : '' );
      $GLOBALS[$prefix . 'login'] = $login;
      $GLOBALS[$prefix . 'firstname'] = $fname;
      $GLOBALS[$prefix . 'lastname'] = $lname;
      $GLOBALS[$prefix . 'is_admin'] = $this->_app_user_is_admin ($row[0] );
      $GLOBALS[$prefix . 'email'] = $row[3];
      $GLOBALS[$prefix . 'fullname'] = $row[1];
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
    return false;
  }

  $this->_app_db_connect ( false );
  //save these results
  $cached_user_var[$login][$prefix] = true;
  return true;
}

// Checks to see if the user is logged into the application
// returns: login id
function user_logged_in () {
  global $_COOKIE;

  // First check to see if the user even has a session cookie
  if (empty ($_COOKIE[$this->_app_sid])) return false;

  // Generate session id
  $sid = app_get_sid( $_COOKIE[$this->_app_sid] );

  // addslashes if magic_quotes_gpc is off
  if ( ! get_magic_quotes_gpc () ) $sid = addslashes ( $sid );

  // Check to see if the session is still valid
  if (! $login = app_active_session ($sid) ) return false;

  // Update the session last access time
  app_update_session($sid);

  return $login;
}
?>

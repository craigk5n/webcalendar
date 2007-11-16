<?php
// LDAP user functions.
// This file is intended to be used instead of the standard user.php
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
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
	
require( "User.class" );

class UserLdap extends User {

// Allow auto-creation of WebCalendar Accounts for fully authenticated users
var $_allow_auto_create = true;
//this will normally be assigned as a CONSTANT, need this to allow auto-create
var $ACCESS_ACCOUNT_INFO = 16;

// Name or address of the LDAP server 
//  For SSL/TLS use 'ldaps://localhost'
var $ldap_server = 'localhost';          

// Port LDAP listens on (default 389)        
var $ldap_port = '389';                   

// Use TLS for the connection (not the same as ldaps://)
var $ldap_start_tls = false;

// If you need to set LDAP_OPT_PROTOCOL_VERSION
var $set_ldap_version = false;
var $ldap_version = '3'; // (usually 3)

// base DN to search for users      
var $ldap_base_dn = 'ou=people,dc=company,dc=com';

// The ldap attribute used to find a user (login). 
// E.g., if you use cn,  your login might be "Jane Smith"
//       if you use uid, your login might be "jsmith"
var $ldap_login_attr = 'uid';

// Account used to bind to the server and search for information. 
// This user must have the correct rights to perform search.
// If left empty the search will be made in anonymous.
//
// *** We do NOT recommend storing the root LDAP account info here ***
var $ldap_admin_dn = '';  // user DN
var $ldap_admin_pwd = ''; // user password


//------ Admin Group Settings ------//
//
// A group name (complete DN) to find users with admin rights
var $ldap_admin_group_name = 'cn=webcal_admin,ou=group,dc=company,dc=com';

// What type of group do we want (posixgroup, groupofnames, groupofuniquenames)
var $ldap_admin_group_type = 'posixgroup';

// The LDAP attribute used to store member of a group
var $ldap_admin_group_attr = 'memberuid';


//------ LDAP Search Settings ------//
//
// LDAP filter to find a user list.
var $ldap_user_filter = '(objectclass=person)';

// Attributes to fetch from LDAP and corresponding user variables in the
// application. Do change according to your LDAP Schema
var $ldap_user_attr = array( 
  // LDAP attribute   //WebCalendar variable
  'uid',              //login
  'sn',               //lastname
  'givenname',        //firstname
  'cn',               //fullname
  'mail'              //email
);

/*************************** End Config *****************************/

function UserLdap () {
// Set some config variables about your system.
$this->_user_can_update_password = false;
$this->_admin_can_add_user = false;
$this->_admin_can_delete_user = true; // will not affect LDAP server info
}

// Convert group name to lower case to prevent problems
$ldap_admin_group_attr = strtolower($ldap_admin_group_attr);
$ldap_admin_group_type = strtolower($ldap_admin_group_type);

// Function to search the dn of a given user the error message will 
// be placed in $error.
// params:
//   $login - user login
//   $dn - complete dn for the user (must be given by ref )
// return:
//   TRUE if the user is found, FALSE in other case
function searchDn ( $login ,$dn ) {
  global $error, $ds, $ldap_base_dn, $ldap_login_attr, $ldap_user_attr;

  $ret = false;
  if ($r = UserLdap::connectBind()) {
    $sr = @ldap_search ( $ds, $ldap_base_dn, "($ldap_login_attr=$login)", $ldap_user_attr );
    if (!$sr) {
      $error = 'Error searching LDAP server: ' . ldap_error();
    } else {
      $info = @ldap_get_entries ( $ds, $sr );
      if ( $info['count'] != 1 ) {
        $error = 'Invalid login';
      } else {
        $ret = true;
        $dn = $info[0]['dn'];
      }
      @ldap_free_result ( $sr );
    }
    @ldap_close ( $ds );
  }
  return $ret;
}


// Check to see if a given login/password is valid.  If invalid,
// the error message will be placed in $error.
// params:
//   $login - user login
//   $password - user password
// returns: true or false
function validLogin ( $login, $password ) {
  global $error, $ldap_server, $ldap_port, $ldap_base_dn, $ldap_login_attr;
  global $ldap_admin_dn, $ldap_admin_pwd, $ldap_start_tls, $set_ldap_version, $ldap_version;

  if ( ! function_exists ( "ldap_connect" ) ) {
    die_miserable_death ( "Your installation of PHP does not support LDAP" );
  }

  $ret = false;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ($set_ldap_version || $ldap_start_tls) 
      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $ldap_version);
  
    if ($ldap_start_tls) {
      if (!ldap_start_tls($ds)) {
        $error = 'Could not start TLS for LDAP connection';
        return $ret;      
      }
    }

    if ( UserLdap::searchDn ( $login, &$dn) ) {
      $r = @ldap_bind ( $ds, $dn, $password );
      if (!$r) {
        $error = 'Invalid login';
        //$error .= ': incorrect password'; // uncomment for debugging
      } else {
        $ret = true;
      }
    } else {
      $error = 'Invalid login';
      //$error .= ': no such user'; // uncomment for debugging
    }
    @ldap_close ( $ds );
  } else {
    $error = 'Error connecting to LDAP server';
  }
  return $ret;
}


// Load info about a user (first name, last name, admin) and set globally.
// params:
//   $user - user login
//   $prefix - variable prefix to use
function loadVariables ( $login, $prefix ) {
  global $error, $ds, $ldap_base_dn, $ldap_login_attr, $ldap_user_attr;
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX;

  if ($NONUSER_PREFIX && substr($login, 0, strlen($NONUSER_PREFIX) ) == $NONUSER_PREFIX ) {
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

  $ret =  false;
  if ($r = UserLdap::connectBind()) {
    $sr = @ldap_search ( $ds, $ldap_base_dn, "($ldap_login_attr=$login)", $ldap_user_attr );
    if (!$sr) {
      $error = 'Error searching LDAP server: ' . ldap_error();
    } else {
      $info = @ldap_get_entries ( $ds, $sr );
      if ( $info['count'] != 1 ) {
        $error = 'Invalid login';
      } else {
        $GLOBALS[$prefix . 'login'] = $login;
        $GLOBALS[$prefix . 'firstname'] = $info[0][$ldap_user_attr[2]][0];
        $GLOBALS[$prefix . 'lastname'] = $info[0][$ldap_user_attr[1]][0];
        $GLOBALS[$prefix . 'email'] = $info[0][$ldap_user_attr[4]][0];
        $GLOBALS[$prefix . 'fullname'] = $info[0][$ldap_user_attr[3]][0];
        $GLOBALS[$prefix . 'is_admin'] = UserLdap::isAdmin($login,UserLdap::getAdmins());
        $ret = true;
      }
      @ldap_free_result ( $sr );
    }
    @ldap_close ( $ds );
  }
  return $ret;
}


// Get a list of users and return info in an array.
// returns: array of users
function getUsers () {
  global $error, $ds, $ldap_base_dn, $ldap_user_attr, $ldap_user_filter;
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME;

  $Admins = UserLdap::getAdmins();
  $count = 0;
  $ret = array ();
  if ( $PUBLIC_ACCESS == 'Y' )
    $_user_array[[$count++] = array (
       'cal_login' => '__public__',
       'cal_lastname' => '',
       'cal_firstname' => '',
       'cal_is_admin' => 'N',
       'cal_email' => '',
       'cal_password' => '',
       'cal_fullname' => $PUBLIC_ACCESS_FULLNAME );

  if ($r = UserLdap::connectBind()) {
    $sr = @ldap_search ( $ds, $ldap_base_dn, $ldap_user_filter, $ldap_user_attr );
    if (!$sr) {
      $error = 'Error searching LDAP server: ' . ldap_error();
    } else {
      if ( (float)substr(PHP_VERSION,0,3) >= 4.2 ) ldap_sort ( $ds, $sr, $ldap_user_attr[3]);
      $info = @ldap_get_entries( $ds, $sr );
      for ( $i = 0; $i < $info['count']; $i++ ) {
        $_user_array[[$count++] = array (
          'cal_login' => $info[$i][$ldap_user_attr[0]][0],
          'cal_lastname' => $info[$i][$ldap_user_attr[1]][0],
          'cal_firstname' => $info[$i][$ldap_user_attr[2]][0],
          'cal_email' => $info[$i][$ldap_user_attr[4]][0],
          'cal_is_admin' => UserLdap::isAdmin($info[$i][$ldap_user_attr[0]][0],$Admins),
          'cal_fullname' => $info[$i][$ldap_user_attr[3]][0]
          );
      }
      @ldap_free_result($sr);
    }
    @ldap_close ( $ds );
  }
  return $_user_array[;
}

// Test if a user is an admin, that is: if the user is a member of a special
// group in the LDAP Server
// params:
//   $values - the login name
// returns: Y if user is admin, N if not
function isAdmin($values,$Admins) {
  if ( ! $Admins ) {
    return 'N';
  } else if (in_array ($values, $Admins)) {
    return 'Y';
  } else {
    return 'N';
  }
}

// Searches $ldap_admin_group_name and returns an array of the group members.
// Do this search only once per request.
// returns: array of admins
function getAdmins() {
  global $error, $ds, $cached_admins;
  global $ldap_admin_group_name,$ldap_admin_group_attr,$ldap_admin_group_type;

  if ( ! empty ( $cached_admins ) ) return $cached_admins;
  $cached_admins = array ();

  if ($r = UserLdap::connectBind()) {
    $search_filter = "($ldap_admin_group_attr=*)";
    $sr = @ldap_search ( $ds, $ldap_admin_group_name, $search_filter, array($ldap_admin_group_attr) );
    if (!$sr) {
      $error = 'Error searching LDAP server: ' . ldap_error();
    } else {
      $admins = ldap_get_entries( $ds, $sr );
      for( $x = 0; $x <= $admins[0][$ldap_admin_group_attr]['count']; $x ++ ) {
       if ($ldap_admin_group_type != 'posixgroup') {
          $cached_admins[] = UserLdap::stripDn($admins[0][$ldap_admin_group_attr][$x]);
        } else {
          $cached_admins[] = $admins[0][$ldap_admin_group_attr][$x];
        }
      }
      @ldap_free_result($sr);
    }
    @ldap_close ( $ds );
  }
  return $cached_admins;
}

// Strip everything but the username (uid) from a dn.
//  params:
//    $dn - the dn you want to strip the uid from.
//  returns: string - userid
//
//  ex: stripdn(uid=jeffh,ou=people,dc=example,dc=com) returns jeffh
function stripDn($dn){
  list ($uid,$trash) = split (',', $dn, 2);
  list ($trash,$user) = split ('=', $uid);
  return($user);
}

// Connects and binds to the LDAP server
// Tries to connect as $ldap_admin_dn if we set it.
//  returns: bind result or false
function connectBind() {
  global $ds, $error, $ldap_server, $ldap_port, $ldap_version; 
  global $ldap_admin_dn, $ldap_admin_pwd, $ldap_start_tls, $set_ldap_version;

  if ( ! function_exists ( "ldap_connect" ) ) {
    die_miserable_death ( "Your installation of PHP does not support LDAP" );
  }

  $ret = false;
  $ds = @ldap_connect ( $ldap_server, $ldap_port );
  if ( $ds ) {
    if ($set_ldap_version || $ldap_start_tls) 
      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $ldap_version);
  
    if ($ldap_start_tls) {
      if (!ldap_start_tls($ds)) {
        $error = 'Could not start TLS for LDAP connection';
        return $ret;      
      }
    }
    
    if ( $ldap_admin_dn != '') {
      $r = @ldap_bind ( $ds, $ldap_admin_dn, $ldap_admin_pwd );
    } else {
      $r = @ldap_bind ( $ds );
    }

    if (!$r) {
      $error = 'Invalid Admin login for LDAP Server';
    } else {
      $ret = $r;
    }
  } else {
    $error = 'Error connecting to LDAP server';
    $ret = false;
  }
  return $ret;
}
}
?>

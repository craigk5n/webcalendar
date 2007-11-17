<?php
/**
 * Authentication functions.
 *
 * This file contains all the functions for getting information about users.
 * So, if you want to use an authentication scheme other than the webcal_user
 * table, you can just create a new version of each function found below.
 *
 * <b>Note:</b> this application assumes that usernames (logins) are unique.
 *
 * <b>Note #2:</b> If you are using HTTP-based authentication, then you still
 * need these functions and you will still need to add users to webcal_user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Authentication
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

class User {

var $_error;
var $_ret;
var $_user_array;
var $_uservar;
var $_login_return_path;


function User () {
  // Set some config variables about your system.
  if ( ! defined ( '_WC_USER_CAN_UPDATE_PASSWORD' ) ) {  
    define ( '_WC_USER_CAN_UPDATE_PASSWORD', true );
    define ( '_WC_ADMIN_CAN_ADD_USER', true );
    define ( '_WC_ADMIN_CAN_DELETE_USER', true );
  }
}
/**
 * Check to see if a given login/password is valid.
 *
 * If invalid, the error message will be placed in $this->_error.
 *
 * @param string $login    User login
 * @param string $password User password
 * @param bool   $silent   if true do not return any $error
 *
 * @return bool True on success
 *
 */
function validLogin ( $login, $password, $silent=false ) {
  $ret = $enabled = false;
	
	$password2 =  ( getPref ( 'PASSWORDS_CLEARTEXT', 2 )? $password : md5( $password ) );
  $sql = 'SELECT cal_login, cal_enabled 
	  FROM webcal_user 
		WHERE cal_login = ? AND ( cal_passwd = ? or cal_passwd = ?)';
  $res = dbi_execute ( $sql , array ( $login , md5 ( $password ), $password2 ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] != '' ) {
		  $enabled = ( $row[1] == 'Y' ? true : false );
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      if ( $row[0] == $login )
        $ret = true; // found login/password
       else if ( ! $silent )
        $this->_error = translate ('Invalid login', true ) . ': ' .
          translate('incorrect password', true );
    } else if ( ! $silent ) {
      $this->_error = translate ('Invalid login', true );
      // Could be no such user or bad password
      // Check if user exists, so we can tell.
      $res2 = dbi_execute ( 'SELECT cal_login FROM webcal_user WHERE cal_login = ?' , 
              array ( $login ) );
      if ( $res2 ) {
        $row = dbi_fetch_row ( $res2 );
        if ( $row && ! empty ( $row[0] ) ) {
          // got a valid username, but wrong password
          $this->_error = translate ('Invalid login', true ) . ': ' .
            translate('incorrect password', true );
        } else {
          // No such user.
          $this->_error = translate ('Invalid login', true) . ': ' .
            translate('no such user', true );
        }
       // dbi_free_result ( $res2 );
      }
    }
		if ( ! $enabled && ! $this->_error) {
		  $ret = false;
			$this->_error = ( ! $silent ? translate('Account disabled', true) : '' );
		}
		if ( ! $this->checkPasswd ( $login ) ) {
		  $ret = false;
		}		
    dbi_free_result ( $res );
  } else if ( ! $silent ) {
    $this->_error = translate('Database error', true) . ': ' . dbi_error();
  }
  return $ret;
}

/**
 * Check to see if a given login/crypted password is valid.
 *
 * If invalid, the error message will be placed in $this->_error.
 *
 * @param string $login          User login
 * @param string $crypt_password Encrypted user password
 *
 * @return bool True on success
 *
 */
function validCrypt ( $login, $crypt_password ) {

  $enabled = false;
	
  $sql = 'SELECT cal_login, cal_passwd, cal_enabled 
	  FROM webcal_user 
		WHERE cal_login = ?';
  $res = dbi_execute ( $sql , array ( $login ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
		//Do we allow cleartext passwords and is this not md5?
		$passwd = ( getPref ( 'PASSWORDS_CLEARTEXT', 2 ) 
		  && strlen ( $row[1] ) != 32 ? md5( $row[1] ) : $row[1] );
		  
    if ( $row && $row[0] != '' ) {
			$enabled = ( $row[2] == 'Y' ? true : false );
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      // also check if password matches
      if ( ($row[0] == $login) && ( (crypt($passwd, 
	    $crypt_password) == $crypt_password) ) ) {
        $this->_ret = true; // found login/password
      } else {
        $this->_error = 'Invalid login';
	    }
    } else {
      $this->_error = 'Invalid login';
    }
		if ( ! $enabled ) {
		  $this->ret = false;
		  $this->_error = ( ! $silent ? translate('Account disabled', true) : '' );
		}
    dbi_free_result ( $res );
  } else {
    $this->_error = 'Database error: ' . dbi_error();
  }
  return $this->_ret;
}

/**
 * Check to see if Password Expiration is enabled 
 * and track last login
 *
 * @param string $loginid  The user's cal_login_id
 *
 * @return boo    True if authorized
 */
function checkPasswd ( $login ) {
   
  $last_login = time();
	$password_expires = getPref ( 'PASSWORD_EXPIRES', 2 );

	if ( $password_expires ) {
    $sql = 'SELECT cal_last_login, cal_is_admin 
	    FROM webcal_user 
		  WHERE cal_login = ?';
    $res = dbi_execute ( $sql , array ( $login ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
			//Don't expire admin accts
			if ( $row[1] == 'N' )
		    $last_login = $row[0];
		  dbi_free_result ( $res );
	  }
		//echo date ( 'Ymd', $last_login + ( $password_expires * ONE_DAY ) );
	  if ( ( $last_login + ( $password_expires * ONE_DAY ) ) < time() ) {
			$this->_error = 'Password Expired. Contact Administrator';
	 	  return false; 
		}
	}

	//Update last login
	@dbi_execute ( 'UPDATE webcal_user 
	  SET  cal_last_login = ? 
		WHERE cal_login = ?', array ( time(), $login ) );
	return true;		
}

/**
 * Load info about a user (first name, last name, admin).
 * 
 * @param int/string   login  login or login_id
 * @param bool    $boolean   Return boolean values for Y/N
 *
 * @return array   User's information
 */
function loadVariables ( $login, $boolean=true ) {

  $ret = array();
	//allow this function to handle cal_login or cal_login_id values
	if ( is_numeric ( $login ) ) {
	  $where = 'cal_login_id   = ?';
	} else {
	  $where = 'cal_login = ?';
	}
	$sql = 'SELECT cal_login_id, cal_login, cal_passwd, cal_lastname, 
	  cal_firstname, cal_is_admin, cal_email, cal_enabled, cal_telephone,
		cal_address, cal_title, cal_birthday, cal_is_nuc, cal_admin,
		cal_is_public, cal_url, cal_selected, cal_view_part
		FROM webcal_user WHERE ' . $where;
		$rows = dbi_get_cached_rows ( $sql , array ( $login ) );
		if ( $rows ) {
			$row = $rows[0];
			$ret['login_id']     = $row[0];
			$ret['login']        = $row[1];
			$ret['password']     = $row[2];
			$ret['lastname']     = $row[3];
			$ret['firstname']    = $row[4];
			$ret['fullname']     = "$row[4] $row[3]";
			$ret['is_admin']     = $row[5];
			$ret['email']        = $row[6];
			$ret['enabled']      = $row[7];
			$ret['telephone']    = $row[8];
			$ret['address']      = $row[9];
			$ret['title']        = $row[10];
			$ret['birthday']     = $row[11];
		  $ret['is_nonuser']   = $row[12];
			$ret['admin']        = $row[13];
			$ret['is_public']    = $row[14];
      $ret['url']          = $row[15];
      $ret['is_selected']  = $row[16];
      $ret['view_part']    = $row[17];
			
			if ( $boolean ) {
			  $ret['is_admin']    = ( $ret['is_admin']    == 'Y' ? true : false );
			  $ret['enabled']     = ( $ret['enabled']     == 'Y' ? true : false );
		    $ret['is_nonuser']  = ( $ret['is_nonuser']  == 'Y' ? true : false );
			  $ret['is_public']   = ( $ret['is_public']   == 'Y' ? true : false );
        $ret['is_selected'] = ( $ret['is_selected'] == 'Y' ? true : false );
        $ret['view_part']   = ( $ret['view_part']   == 'Y' ? true : false );
			}
			
    } else {
      $this->_error = translate ( 'Database error' ) . ': ' . dbi_error ();
      return false;
		}	
  return $ret;
}

/**
 * Get user's Fullname
 * 
 * @param int  User's cal_login_id
 *
 * @return string  User's Fullname
 */
function getFullName ( $login ) {

  $ret = array();
	//allow this function to handle cal_login or cal_login_id values
	if ( is_numeric ( $login ) ) {
	  $where = 'cal_login_id = ?';
	} else {
	  $where = 'cal_login = ?';
	}
		$sql ='SELECT cal_firstname, cal_lastname
			FROM webcal_user WHERE ' . $where;
		$rows = dbi_get_cached_rows ( $sql , array ( $login ) );
		if ( $rows ) {
		  $row = $rows[0];
			$ret = "$row[0] $row[1]";
    } else {
      $this->_error = translate ( 'Database error' ) . ': ' . dbi_error ();
      return false;
		}
  return $ret;
}

/**
 * Add a new user.
 *
 * @param array $params Array containing column name -> values
 *
 * @return int    cal_login_id of new user
 *
 */
function addUser ( $params ) {

  if ( isset ( $params['cal_passwd'] ) && ! getPref ( 'PASSWORDS_CLEARTEXT', 2 ) )
    $params['cal_passwd'] = md5($params['cal_passwd']);

  if ( empty ( $params['cal_is_admin'] ) || $params['cal_is_admin'] != 'Y' )
    $params['cal_is_admin'] = 'N';
	
	//Preload cal_last_login to current datetime
	$params['cal_last_login'] = time();
					
  $placeholders = '';
	$values = array();
	$sql = 'INSERT INTO webcal_user ( ';
  foreach ( $params as $K => $V ) {
	  $sql .= $K . ', ';
	  $values[] = $V;
		$placeholders .= '?, ';
	}
		
	$sql .= 'cal_login_id ) VALUES ( ' . $placeholders . '? )';
  $res = dbi_execute ( 'SELECT MAX( cal_login_id ) FROM webcal_user' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $next_id = $row[0] + 1;
    dbi_free_result ( $res );
  }
	$values[] = $next_id;
	//print_r ( $params);
  if ( ! dbi_execute ( $sql , $values ) ) {
    $this->_error = translate ('Database error', true) . ': ' . dbi_error ();
    return false;
  }
	
  //If NUC is Public set View = 1  in UAC
  if ( $params['cal_is_nuc'] == 'Y' && $params['cal_is_public'] == 'Y' ) {
	  set_user_UAC ( $next_id, -2, 1 );
	}
	
	
  return $next_id;
}

/**
 * Update a user.
 *
 * @param array $params Array containing column name -> values
 *
 * @return bool True on success
 *
 */
function updateUser ( $params ) {

  //make sure we have set the target login_id
	if ( empty ( $params['cal_login_id'] ) )
	  return false;

  if ( isset ( $params['cal_passwd'] ) && ! getPref ( 'PASSWORDS_CLEARTEXT' ) )
    $params['cal_passwd'] = md5($params['cal_passwd']);
				
	$values = array();
  $sql = 'UPDATE webcal_user SET';
  foreach ( $params as $K => $V ) {
	  if ( $K != 'cal_login_id' ) {
	    $sql .= ' ' . $K . ' = ?,';
			$values[] = $V;
		} 	
	}
	// remove trailing ','
	$sql = preg_replace( '/,$/', '', $sql );
	 
  $sql .= ' WHERE cal_login_id = ? ';
	$values[] = $params['cal_login_id'];
	
  if ( ! dbi_execute ( $sql , $values ) ) {
    $this->_error = translate ('Database error') . ': ' . dbi_error ();
    return false;
  }
  return true;
}

/**
 * Delete a user from the system.
 *
 * This will also delete any of the user's events in the system that have
 * no other participants.  Any layers that point to this user
 * will be deleted.  Any views that include this user will be updated.
 *
 * @param int $user_id User to delete
 */
function deleteUser ( $user_id ) {
  //Delete all events for this user
	$this->deleteUserEvents( $user_id );

  // Delete preferences
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete from groups
  dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete user's views
  $delete_em = array ();
  $res = dbi_execute ( 'SELECT cal_view_id FROM webcal_view WHERE cal_owner = ?' , 
      array ( $user_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_view_id = ?' , 
          array ( $delete_em[$i] ) );
  }
  dbi_execute ( 'DELETE FROM webcal_view WHERE cal_owner = ?' , 
      array ( $user_id ) );
    //Delete them from any other user's views
    dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete layers
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser_id = ?' , 
      array ( $user_id ) );
  // Delete user
  dbi_execute ( 'DELETE FROM webcal_user WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete function access
  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  // Delete user access
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login_id = ?' ,
      array ( $user_id ) );
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_other_user_id = ?' ,
      array ( $user_id ) );
  // Delete user's categories
  dbi_execute ( 'DELETE FROM webcal_categories WHERE cat_owner = ?' ,
      array ( $user_id ) );
  dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cat_owner = ?' ,
      array ( $user_id ) );
  // Delete user's reports
  $delete_em = array ();
  $res = dbi_execute ( 'SELECT cal_report_id FROM webcal_report WHERE cal_login_id = ?' , 
      array ( $user_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_report_template WHERE cal_report_id = ?' ,
        array ( $delete_em[$i] ) );    
    }
  dbi_execute ( 'DELETE FROM webcal_report WHERE cal_login_id = ?' ,
      array ( $user_id ) );
        //not sure about this one???
  dbi_execute ( 'DELETE FROM webcal_report WHERE cal_user_id = ?' ,
      array ( $user_id ) );    
  // Delete user templates
  dbi_execute ( 'DELETE FROM webcal_user_template WHERE cal_login_id = ?' , 
      array ( $user_id ) );    
}

/**
 * Delete all entries for one user.
 *
 * This will also delete any of the user's events in the system that have
 * no other participants.  Any layers that point to this user
 * will be deleted.  Any views that include this user will be updated.
 *
 * @param int $user_id User to delete
 */
function deleteUserEvents ( $user_id ) {
  // Get event ids for all events this user is a participant
  $delete_em = get_event_ids ( $user_id );

  // Now delete events that were just for this user
  for ( $i = 0, $cnt = count ( $delete_em ); $i < $cnt; $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?' , 
          array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_exceptions WHERE cal_id = ?' ,
          array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_import_data WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_entry_ext_user WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_blob WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?' , 
          array ( $delete_em[$i] )  );
  }
	
  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login_id = ?' , 
      array ( $user_id ) );
}

/**
 * Get a list of users and return info in an array.
 *
 * @return array Array of user info
 */
function getUsers ( $getNUC='' ) {
  
	$_user_array = array();
	if ( $getNUC === true ) 
	  $getNUC = 'WHERE cal_is_nuc = \'Y\' ';
	else if ( $getNUC === false )
	  $getNUC = 'WHERE cal_is_nuc = \'N\' ';	 
	else 
		$getNUC = '';
		 
  $count = 0;
  $rows = dbi_get_cached_rows ( 'SELECT cal_login, cal_lastname, cal_firstname,
    cal_is_admin, cal_email, cal_passwd, cal_login_id, cal_enabled 
		FROM webcal_user ' . $getNUC .
    'ORDER BY cal_lastname, cal_firstname, cal_login' );
  if ( $rows ) {
     for ( $i = 0; $i < count ( $rows ); $i++ ) {
        $row = $rows[$i];
        if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
          $fullname = "$row[2] $row[1]";
        else
          $fullname = $row[0];
        $_user_array[$count++] = array (
        'cal_login' => $row[0],
        'cal_lastname' => $row[1],
        'cal_firstname' => $row[2],
        'cal_is_admin' => $row[3],
        'cal_email' => empty ( $row[4] ) ? '' : $row[4],
        'cal_password' => $row[5],
        'cal_login_id' => $row[6],
        'cal_fullname' => $fullname,
				'cal_enabled'  => $row[7],
				'selected' => ''
      );
    }
    //dbi_free_result ( $res );
  }
  return $_user_array;
}

function getUserId ( $user ) {

  if ( substr ( $user, 0, 5 ) != _WC_NONUSER_PREFIX ) {
    $sql = 'SELECT cal_login_id
      FROM webcal_user WHERE cal_login = ?';
    $rows = dbi_get_cached_rows ( $sql , array ( $user ) );
    if ( $rows ) {
      $row = $rows[0];
      return $row[0];
		}
  } else {
	  $userData = $this->loadVariables ( $user, 'tp', true );
		return $userData['login_id'];
	}
  return false;
}

function getError (){
  return $this->_error;
}

function loginReturnPath(){
  return $this->_login_return_path;
}

/*********************************************************************
 *
 *        Stuff that should stay the same for all user-app files
 *
 ********************************************************************/

// Redirect the user to the applogin.php page
function app_login_screen( $return ) {
  global $SERVER_URL;
  header ( "Location: {$SERVER_URL}applogin.php?return_path={$return}");
  exit;
}

// Test if a user is an admin, that is: if the user is a member of a special
// group in the application database
// params:
//   $values - the login name
// returns: Y if user is admin, N if not
function _app_user_is_admin ( $uid ) {
  return ( in_array ( $uid, $this->_admins ) ? 'Y' : 'N' );
}

// if application is in a separate db, we have to connect to it
function _app_db_connect ( $app=true ) {
  global $c;
  //clean up existing db connection
  if ( $this->_app_chg_db )	
	  dbi_close ($c);
	else 
	  return true;
		
  if ( $app )
	  $c = dbi_connect( $this->_app_config['host']
		  , $this->_app_config['user']
			, $this->_app_config['password']
			, $this->_app_config['db'] );
  else// we have to connect back to the webcal db
	  $c = dbi_connect(_WC_DB_HOST, _WC_DB_LOGIN, _WC_DB_PASSWORD, _WC_DB_DATABASE);	
}

function _app_autoCreate ( $login, $password ) {
	if ( $this->_allow_auto_create && _WC_SCRIPT == 'login.php' ) {
		//Test if user is in WebCalendar database
		$testuser = $this->loadVariables ( $login );
		$params = array ( 'cal_login'=>$login,
			'cal_passwd'=>$password);
		if ( empty ( $testuser ) || 
			$testuser['login'] != $login ) {
			$newID = $this->addUser ( $params );
			//Redirect new users to enter user date
			$GLOBALS['newUserUrl'] = 'edit_user.php';
		} else {
			//update password just in case it was changed outside WebCalendar
			$this->updateUser ( $params );
		}
	}
}
}
?>

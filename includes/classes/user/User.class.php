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
 * If invalid, the error message will be placed in $_error.
 *
 * @param string $login    User login
 * @param string $password User password
 * @param bool $#silent  if truem do not return any $error
 *
 * @return bool True on success
 *
 */
function validLogin ( $login, $password, $silent=false ) {
  $ret = false;
  $sql = "SELECT cal_login FROM webcal_user WHERE cal_login = ? AND cal_passwd = ?";
  $res = dbi_execute ( $sql , array ( $login , md5( $password ) ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] != "" ) {
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      if ( $row[0] == $login )
        $ret = true; // found login/password
      else if ( ! $silent )
        $_error = translate ("Invalid login", true ) . ": " .
          translate("incorrect password", true );
    } else if ( ! $silent ) {
      $_error = translate ("Invalid login", true );
      // Could be no such user or bad password
      // Check if user exists, so we can tell.
      $res2 = dbi_execute ( "SELECT cal_login FROM webcal_user WHERE cal_login = ?" , 
              array ( $login ) );
      if ( $res2 ) {
        $row = dbi_fetch_row ( $res2 );
        if ( $row && ! empty ( $row[0] ) ) {
          // got a valid username, but wrong password
          $_error = translate ("Invalid login", true ) . ": " .
            translate("incorrect password", true );
        } else {
          // No such user.
          $_error = translate ("Invalid login", true) . ": " .
            translate("no such user", true );
        }
       // dbi_free_result ( $res2 );
      }
    }
    dbi_free_result ( $res );
  } else if ( ! $silent ) {
    $_error = translate("Database error", true) . ": " . dbi_error();
  }

  return $ret;
}

/**
 * Check to see if a given login/crypted password is valid.
 *
 * If invalid, the error message will be placed in $_error.
 *
 * @param string $login          User login
 * @param string $crypt_password Encrypted user password
 *
 * @return bool True on success
 *
 */
function validCrypt ( $login, $crypt_password ) {
  $sql = "SELECT cal_login, cal_passwd FROM webcal_user WHERE cal_login = ?";
  $res = dbi_execute ( $sql , array ( $login ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] != "" ) {
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      // also check if password matches
      if ( ($row[0] == $login) && ( (crypt($row[1], 
	    $crypt_password) == $crypt_password) ) ) {
        $this->_ret = true; // found login/password
      } else {
        $_error = "Invalid login";
	  }
    } else {
      $_error = "Invalid login";
    }
    dbi_free_result ( $res );
  } else {
    $_error = "Database error: " . dbi_error();
  }
  return $this->_ret;
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
      $ret['is_selected']     = $row[16];
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
 * @return bool True on success
 *
 */
function addUser ( $params ) {

  if ( isset ( $params['cal_passwd'] ) && ! getPref ( 'CLEARTEXT_PASSWORDS' ) )
    $params['cal_passwd'] = md5($params['cal_passwd']);

  if ( empty ( $params['cal_is_admin'] ) || $params['cal_is_admin'] != 'Y' )
    $params['cal_is_admin'] = 'N';
		
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
	
  if ( ! dbi_execute ( $sql , $values ) ) {
    $_error = translate ("Database error", true) . ": " . dbi_error ();
    return false;
  }
  return true;
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

  if ( isset ( $params['cal_passwd'] ) && ! getPref ( 'CLEARTEXT_PASSWORDS' ) )
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
	$sql = preg_replace( "/,$/", "", $sql );
	 
  $sql .= ' WHERE cal_login_id = ? ';
	$values[] = $params['cal_login_id'];
	
  if ( ! dbi_execute ( $sql , $values ) ) {
    $_error = translate ("Database error") . ": " . dbi_error ();
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
  // Get event ids for all events this user is a participant
  $delete_em = get_event_ids ( $user_id );

  // Now delete events that were just for this user
  for ( $i = 0, $cnt = count ( $delete_em ); $i < $cnt; $i++ ) {
    dbi_execute ( "DELETE FROM webcal_entry_repeats WHERE cal_id = ?" , 
          array ( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?" ,
          array ( $delete_em[$i] ) );
    dbi_execute ( "DELETE FROM webcal_entry_log WHERE cal_entry_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_import_data WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_site_extras WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_entry_ext_user WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_reminders WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_blob WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
    dbi_execute ( "DELETE FROM webcal_entry WHERE cal_id = ?" , 
          array ( $delete_em[$i] )  );
  }

  // Delete user participation from events
  dbi_execute ( "DELETE FROM webcal_entry_user WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete preferences
  dbi_execute ( "DELETE FROM webcal_user_pref WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete from groups
  dbi_execute ( "DELETE FROM webcal_group_user WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete user's views
  $delete_em = array ();
  $res = dbi_execute ( "SELECT cal_view_id FROM webcal_view WHERE cal_owner = ?" , 
      array ( $user_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_view_id = ?" , 
          array ( $delete_em[$i] ) );
  }
  dbi_execute ( "DELETE FROM webcal_view WHERE cal_owner = ?" , 
      array ( $user_id ) );
    //Delete them from any other user's views
    dbi_execute ( "DELETE FROM webcal_view_user WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete layers
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete any layers other users may have that point to this user.
  dbi_execute ( "DELETE FROM webcal_user_layers WHERE cal_layeruser = ?" , 
      array ( $user_id ) );
  // Delete user
  dbi_execute ( "DELETE FROM webcal_user WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete function access
  dbi_execute ( "DELETE FROM webcal_access_function WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  // Delete user access
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_login_id = ?" ,
      array ( $user_id ) );
  dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_other_user_id = ?" ,
      array ( $user_id ) );
  // Delete user's categories
  dbi_execute ( "DELETE FROM webcal_categories WHERE cat_owner = ?" ,
      array ( $user_id ) );
  dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cat_owner = ?" ,
      array ( $user_id ) );
  // Delete user's reports
  $delete_em = array ();
  $res = dbi_execute ( "SELECT cal_report_id FROM webcal_report WHERE cal_login_id = ?" , 
      array ( $user_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( "DELETE FROM webcal_report_template WHERE cal_report_id = ?" ,
        array ( $delete_em[$i] ) );    
    }
  dbi_execute ( "DELETE FROM webcal_report WHERE cal_login_id = ?" ,
      array ( $user_id ) );
        //not sure about this one???
  dbi_execute ( "DELETE FROM webcal_report WHERE cal_user_id = ?" ,
      array ( $user_id ) );    
  // Delete user templates
  dbi_execute ( "DELETE FROM webcal_user_template WHERE cal_login_id = ?" , 
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
	else if ( $getNUC ===false )
	  $getNUC = 'WHERE cal_is_nuc = \'N\' ';	 
	else 
		$getNUC = 'WHERE cal_is_nuc IN ( \'Y\',\'N\' ) ';
		 
	$getNuc = ( $getNUC ? 'Y' : $getNUC === false ? 'N' : 'Y,N' );
  $count = 0;
  $rows = dbi_get_cached_rows ( 'SELECT cal_login, cal_lastname, cal_firstname,
    cal_is_admin, cal_email, cal_passwd, cal_login_id 
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

}
?>

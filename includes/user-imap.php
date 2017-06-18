<?php
/**
 * Authentication functions.
 *
 * This file contains all the functions for getting information about users
 * and authenticating them using an IMAP server.
 *
 * <b>Note:</b> this application assumes that usernames (logins) are unique.
 *
 * <b>Note #2:</b> If you are using HTTP-based authentication, then you still
 * need these functions and you will still need to add users to webcal_user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: user-imap.php,v 1.26.2.2 2008/03/12 10:42:03 cknudsen Exp $
 * @package WebCalendar
 * @subpackage IMAPAuthentication
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
// Set some global config variables about your system.
$user_can_update_password = false;
$admin_can_add_user = false;
$admin_can_delete_user = true; // will not affect IMAP server info
$admin_can_disable_user = false;

// Allow auto-creation of WebCalendar Accounts for fully authenticated users
$allow_auto_create = true;

//
// 'auth_imap' configuration settings "borrowed" from the Meeting Room Booking System
//  https://sourceforge.net/projects/mrbs
//  GNU General Public License (GPL)
//
// This file contains all the functions for getting information
// about users via IMAP
//
$imap_host = 'yourserver.com'; // Where is the IMAP server
$imap_port = '143';          // The IMAP server port

/* quoteIMAP($str)
 *
 * quote char's into valid IMAP string
 *
 * $str - String to be quoted
 *
 * Returns:
 *   quoted string
 */
function quoteIMAP($str)
{
    return preg_replace("/\\\\/", "\\\\\\", $str);
}

/**
 * Check to see if a given login/password is valid.
 *
 * If invalid, the error message will be placed in $error.
 *
 * @param string $login    User login
 * @param string $password User password
 *
 * @return bool True on success
 *
 * @global string Error message
 */
function user_valid_login ( $login, $password ) {
  global $error,$auth, $imap_host, $imap_port, $allow_auto_create, $PHP_SELF;
  $ret = false;
//  do_debug ("in imap/user_valid_login...<br />\nl=$login p=$password<br />\n");

  $all_imap_hosts = array ();
  $all_imap_ports = array ();

  // Check if we do not have a username/password
  if (! isset ( $login) || ! isset ( $password) || strlen ($password)==0)
  {
    return $ret;
  }

  # Check that if there is an array of hosts and an array of ports
  # then the number of each is the same
  if (is_array ( $imap_host ) && is_array ( $imap_port ) &&
    count ( $imap_port ) != count ( $imap_host ) )
  {
    return $ret;
  }

  # Transfer the list of imap hosts to an new value to ensure that
  # an array is always used.
  # If a single value is passed then turn it into an array
  if (is_array ( $imap_host ) )
  {
    $all_imap_hosts = $imap_host;
  }
  else
  {
    $all_imap_hosts = array ($imap_host);
  }

  # create an array of the port numbers to match the number of
  # hosts if a single port number has been passed.
  if (is_array ( $imap_port ) )
  {
    $all_imap_ports = $imap_port;
  }
  else
  {
    while ( each ( $all_imap_hosts ) )
    {
      $all_imap_ports[] = $imap_port;
    }
  }

  # iterate over all hosts and return if you get a successful login
  foreach ( $all_imap_hosts as $idx => $host )
  {
    $error_number = '';
    $error_string = '';

    // Connect to IMAP-server
    $stream = fsockopen ( $host, $all_imap_ports[$idx], $error_number, $error_string, 15 );
    $response = fgets( $stream, 1024 );
    if ( $stream ) {
      $logon_str = 'a001 LOGIN "' . quoteIMAP( $login ) .
        '" "' . quoteIMAP( $password ) . "\"\r\n";
      fputs( $stream, $logon_str );
      $response = fgets( $stream, 1024 );
      if ( substr ( $response, 5, 2 ) == 'OK' ) {
        fputs( $stream, "a001 LOGOUT\r\n" );
        $response = fgets( $stream, 1024 );
        $ret = true;
        if ( $allow_auto_create && ! empty ( $PHP_SELF ) &&
          preg_match ( "/\/login.php/", $PHP_SELF )) {
          //Test if user is in WebCalendar database
          $prefix = "testuser";
          user_load_variables ( $login, $prefix );
          if ( empty ( $GLOBALS[$prefix . 'login'] ) ||
            $GLOBALS[$prefix . 'login'] != $login ) {
            user_add_user ( $login, $password, '', '', '', 'N' );
            //Redirect new users to enter user date
            $GLOBALS['newUserUrl'] = $GLOBALS['SERVER_URL'] .
              "edit_user.php?user=$login";
          } else {
            //refresh their password in webcal_user
            user_update_user_password ( $login, $password );
          }
        }
        return $ret;
      }
      fputs( $stream, "a001 LOGOUT\r\n" );
    }
  }

  // return failure
  return $ret;
}

/**
 * Check to see if a given login/crypted password is valid.
 *
 * If invalid, the error message will be placed in $error.
 *
 * @param string $login          User login
 * @param string $crypt_password Encrypted user password
 *
 * @return bool True on success
 *
 * @global string Error message
 */
function user_valid_crypt ( $login, $crypt_password ) {
  global $error;
  $ret = false;

  $sql = 'SELECT cal_login, cal_passwd FROM webcal_user WHERE cal_login = ?';
  $res = dbi_execute ( $sql, array ( $login ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] != '' ) {
      // MySQL seems to do case insensitive matching, so double-check
      // the login.
      // also check if password matches
      if ( ($row[0] == $login) && ( (crypt($row[1], $crypt_password) == $crypt_password) ) )
        $ret = true; // found login/password
      else
        //$error = translate ( 'Invalid login' );
        $error = 'Invalid login';
    } else {
      //$error = translate ( 'Invalid login' );
      $error = 'Invalid login';
    }
    dbi_free_result ( $res );
  } else {
    $error = 'Database error: ' . dbi_error ();
  }

  return $ret;
}

/**
 * Load info about a user (first name, last name, admin) and set globally.
 *
 * @param string $user User login
 * @param string $prefix Variable prefix to use
 *
 * @return bool True on success
 */
function user_load_variables ( $login, $prefix ) {
  global $PUBLIC_ACCESS_FULLNAME, $NONUSER_PREFIX, $cached_user_var;
  $ret = false;

  if ( ! empty ( $cached_user_var[$login][$prefix] ) )
    return  $cached_user_var[$login][$prefix];
  $cached_user_var = array ();

  if ($NONUSER_PREFIX && substr ($login, 0, strlen ($NONUSER_PREFIX) ) == $NONUSER_PREFIX) {
    nonuser_load_variables ( $login, $prefix );
    return true;
  }
  if ( $login == '__public__' || $login == '__default__' ) {
    $GLOBALS[$prefix . 'login'] = $login;
    $GLOBALS[$prefix . 'firstname'] = '';
    $GLOBALS[$prefix . 'lastname'] = '';
    $GLOBALS[$prefix . 'is_admin'] = 'N';
    $GLOBALS[$prefix . 'email'] = '';
    $GLOBALS[$prefix . 'fullname'] = ( $login == '__public__'?
      $PUBLIC_ACCESS_FULLNAME : translate ( 'DEFAULT CONFIGURATION' ) );
    $GLOBALS[$prefix . 'password'] = '';
    return true;
  }
  $sql =
    'SELECT cal_firstname, cal_lastname, cal_is_admin, cal_email, cal_passwd ' .
    'FROM webcal_user WHERE cal_login = ?';
  $res = dbi_execute ( $sql, array ( $login ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $GLOBALS[$prefix . 'login'] = $login;
      $GLOBALS[$prefix . 'firstname'] = $row[0];
      $GLOBALS[$prefix . 'lastname'] = $row[1];
      $GLOBALS[$prefix . 'is_admin'] = $row[2];
      $GLOBALS[$prefix . 'email'] = empty ( $row[3] ) ? '' : $row[3];
      if ( strlen ( $row[0] ) && strlen ( $row[1] ) )
        $GLOBALS[$prefix . 'fullname'] = "$row[0] $row[1]";
      else
        $GLOBALS[$prefix . 'fullname'] = $login;
      $GLOBALS[$prefix . 'password'] = $row[4];
      $ret = true;
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
    return false;
  }
  //save these results
  $cached_user_var[$login][$prefix] = $ret;
  return $ret;
}

/**
 * Add a new user.
 *
 * @param string $user      User login
 * @param string $password  User password
 * @param string $firstname User first name
 * @param string $lastname  User last name
 * @param string $email     User email address
 * @param string $admin     Is the user an administrator? ('Y' or 'N')
 *
 * @return bool True on success
 *
 * @global string Error message
 */
function user_add_user ( $user, $password, $firstname,
  $lastname, $email, $admin, $enabled='Y' ) {
  global $error;

  if ( $user == '__public__' ) {
    $error = translate ( 'Invalid user login', true);
    return false;
  }

  if ( strlen ( $email ) )
    $uemail = $email;
  else
    $uemail = NULL;
  if ( strlen ( $firstname ) )
    $ufirstname = $firstname;
  else
    $ufirstname = NULL;
  if ( strlen ( $lastname ) )
    $ulastname = $lastname;
  else
    $ulastname = NULL;
  if ( strlen ( $password ) )
    $upassword = md5 ( $password );
  else
    $upassword = NULL;
  if ( $admin != 'Y' )
    $admin = 'N';
  $sql = 'INSERT INTO webcal_user ' .
    '( cal_login, cal_lastname, cal_firstname, ' .
    'cal_is_admin, cal_passwd, cal_email, cal_enabled ) ' .
    'VALUES ( ?, ?, ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array ( $user, $ulastname,
    $ufirstname, $admin, $upassword, $uemail, $enabled ) ) ) {
    $error = db_error ();
    return false;
  }
  return true;
}

/**
 * Update a user.
 *
 * @param string $user      User login
 * @param string $firstname User first name
 * @param string $lastname  User last name
 * @param string $mail      User email address
 * @param string $admin     Is the user an administrator? ('Y' or 'N')
 *
 * @return bool True on success
 *
 * @global string Error message
 */
function user_update_user ( $user, $firstname, $lastname, $email, 
  $admin, $enabled='Y' ) {
  global $error;

  if ( $user == '__public__' ) {
    $error = translate ( 'Invalid user login' );
    return false;
  }
  if ( strlen ( $email ) )
    $uemail = $email;
  else
    $uemail = NULL;
  if ( strlen ( $firstname ) )
    $ufirstname = $firstname;
  else
    $ufirstname = NULL;
  if ( strlen ( $lastname ) )
    $ulastname = $lastname;
  else
    $ulastname = NULL;
  if ( $admin != 'Y' )
    $admin = 'N';
  if ( $enabled != 'Y' )
    $enabled = 'N';
		
  $sql = 'UPDATE webcal_user SET cal_lastname = ?, ' .
    'cal_firstname = ?, cal_email = ?,' .
    'cal_is_admin = ?, cal_enabled = ?  WHERE cal_login = ?';
  if ( ! dbi_execute ( $sql, array ( $ulastname, $ufirstname, $uemail, 
	  $admin, $enabled, $user  ) ) ) {
    $error = db_error ();
    return false;
  }
  return true;
}

/**
 * Update user password.
 *
 * @param string $user     User login
 * @param string $password User password
 *
 * @return bool True on success
 *
 * @global string Error message
 */
function user_update_user_password ( $user, $password ) {
  global $error;

  $sql = 'UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?';
  if ( ! dbi_execute ( $sql, array ( md5 ( $password ), $user ) ) ) {
    $error = db_error ();
    return false;
  }
  return true;
}

/**
 * Delete a user from the system.
 *
 * This will also delete any of the user's events in the system that have
 * no other participants. Any layers that point to this user
 * will be deleted. Any views that include this user will be updated.
 *
 * @param string $user User to delete
 */
function user_delete_user ( $user ) {
  // Get event ids for all events this user is a participant
  $events = get_users_event_ids ( $user );

  // Now count number of participants in each event...
  // If just 1, then save id to be deleted
  $delete_em = array ();
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $res = dbi_execute ( 'SELECT COUNT(*) FROM webcal_entry_user ' .
      'WHERE cal_id = ?', array ( $events[$i] ) );
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
    dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?',
      array ( $delete_em[$i] ) );
    dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_import_data WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_entry_ext_user WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_blob WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
    dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?',
      array ( $delete_em[$i] )  );
  }

  // Delete user participation from events
  dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_login = ?',
    array ( $user ) );
  // Delete preferences
  dbi_execute ( 'DELETE FROM webcal_user_pref WHERE cal_login = ?',
    array ( $user ) );
  // Delete from groups
  dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_login = ?',
    array ( $user ) );
  // Delete bosses & assistants
  dbi_execute ( 'DELETE FROM webcal_asst WHERE cal_boss = ?',
    array ( $user ) );
  dbi_execute ( 'DELETE FROM webcal_asst WHERE cal_assistant = ?',
    array ( $user ) );
  // Delete user's views
  $delete_em = array ();
  $res = dbi_execute ( 'SELECT cal_view_id FROM webcal_view WHERE cal_owner = ?',
    array ( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_view_id = ?',
      array ( $delete_em[$i] ) );
  }
  dbi_execute ( 'DELETE FROM webcal_view WHERE cal_owner = ?',
    array ( $user ) );
  //Delete them from any other user's views
  dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_login = ?',
    array ( $user ) );
  // Delete layers
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_login = ?',
    array ( $user ) );
  // Delete any layers other users may have that point to this user.
  dbi_execute ( 'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    array ( $user ) );
  // Delete user
  dbi_execute ( 'DELETE FROM webcal_user WHERE cal_login = ?',
    array ( $user ) );
  // Delete function access
  dbi_execute ( 'DELETE FROM webcal_access_function WHERE cal_login = ?',
    array ( $user ) );
  // Delete user access
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login = ?',
    array ( $user ) );
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_other_user = ?',
    array ( $user ) );
  // Delete user's categories
  dbi_execute ( 'DELETE FROM webcal_categories WHERE cat_owner = ?',
    array ( $user ) );
  dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cat_owner = ?',
    array ( $user ) );
  // Delete user's reports
  $delete_em = array ();
  $res = dbi_execute ( 'SELECT cal_report_id FROM webcal_report WHERE cal_login = ?',
    array ( $user ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $delete_em[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $delete_em ); $i++ ) {
    dbi_execute ( 'DELETE FROM webcal_report_template WHERE cal_report_id = ?',
      array ( $delete_em[$i] ) );
  }
  dbi_execute ( 'DELETE FROM webcal_report WHERE cal_login = ?',
    array ( $user ) );
    //not sure about this one???
  dbi_execute ( 'DELETE FROM webcal_report WHERE cal_user = ?',
    array ( $user ) );
  // Delete user templates
  dbi_execute ( 'DELETE FROM webcal_user_template WHERE cal_login = ?',
    array ( $user ) );
}

/**
 * Get a list of users and return info in an array.
 *
 * @return array Array of user info
 */
function user_get_users ( $publicOnly=false ) {
  global $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME, $USER_SORT_ORDER;

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
  $order1 = empty ( $USER_SORT_ORDER ) ?
    'cal_lastname, cal_firstname,' : "$USER_SORT_ORDER,";
  $res = dbi_execute ( 'SELECT cal_login, cal_lastname, cal_firstname, ' .
    'cal_is_admin, cal_email, cal_passwd FROM webcal_user ' .
    "ORDER BY $order1 cal_login" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( strlen ( $row[1] ) && strlen ( $row[2] ) )
        $fullname = ( $order1 == 'cal_lastname, cal_firstname,' ?
           "$row[1] $row[2]" : "$row[2] $row[1]" );
      else
        $fullname = $row[0];
      $ret[$count++] = array (
        'cal_login' => $row[0],
        'cal_lastname' => $row[1],
        'cal_firstname' => $row[2],
        'cal_is_admin' => $row[3],
        'cal_email' => empty ( $row[4] ) ? '' : $row[4],
        'cal_password' => $row[5],
        'cal_fullname' => $fullname
      );
    }
    dbi_free_result ( $res );
  }
  //no need to call sort_users () as the sql can sort for us
  return $ret;
}

?>

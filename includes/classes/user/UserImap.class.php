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
 * @version $Id$
 * @package WebCalendar
 * @subpackage IMAPAuthentication
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

require( 'User.class.php' );

class UserImap extends User {

// Hostname or IP of the IMAP server(s) seperated by commas
var $_imap_host = _WC_IMAP_SERVER;
var $_imap_port = "143";          // The IMAP server port
// Allow auto-creation of WebCalendar Accounts for fully authenticated users
var $_allow_auto_create = true;

//
// 'auth_imap' configuration settings "borrowed" from the Meeting Room Booking System
//  https://sourceforge.net/projects/mrbs
//  GNU General Public License (GPL)
//
// This file contains all the functions for getting information
// about users via IMAP
//

function UserImap () {
  // Set some config variables about your system.
  if ( ! defined ( '_WC_USER_CAN_UPDATE_PASSWORD' ) ) {  
    define ( '_WC_USER_CAN_UPDATE_PASSWORD', false );
    define ( '_WC_ADMIN_CAN_ADD_USER', false );
    define ( '_WC_ADMIN_CAN_DELETE_USER', false );
  }
}

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
    return ereg_replace('(["\\])', '\\\\1', $str);
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
function validLogin ( $login, $password ) {

  $all_imap_hosts = array();
  $all_imap_ports = array();

  // Check if we do not have a username/password
  if(!isset($login) || !isset($password) || strlen($password)==0)
  {
    return false;
  }

  # Transfer the list of imap hosts to an new value to ensure that
  # an array is always used.
  # If a single value is passed then turn it into an array
  if(is_array( $this->_imap_host ) )
  {
    $all_imap_hosts = $this->_imap_host;
  }
  else
  {
    $all_imap_hosts = array( $this->_imap_host );
  }

  # create an array of the port numbers to match the number of
  # hosts if a single port number has been passed.
  if(is_array( $_imap_port ) )
  {
    $all_imap_ports = $this->_imap_port;
  }
  else
  {
    while( each($all_imap_hosts ) )
    {
      $all_imap_ports[] = $this->_imap_port;
    }
  }

  # iterate over all hosts and return if you get a successful login
  foreach( $all_imap_hosts as $idx => $host)
  {
    $error_number = "";
    $error_string = "";

    // Connect to IMAP-server
    $stream = fsockopen( $host, $all_imap_ports[$idx], 
		  $error_number, $error_string, 15 );
    $response = fgets( $stream, 1024 );

    if( $stream ) {
      $logon_str = "a001 LOGIN \"" . $this->quoteIMAP( $login ) . 
        "\" \"" . $this->quoteIMAP( $password ) . "\"\r\n";
      fputs( $stream, $logon_str );
      $response = fgets( $stream, 1024 );
      if( substr( $response, 5, 2 ) == 'OK' ) {
        fputs( $stream, "a001 LOGOUT\r\n" );
        $response = fgets( $stream, 1024 );  
        $this->_app_autoCreate ( $login, $password );
        return true;
      }
      fputs( $stream, "a001 LOGOUT\r\n" );
    }
  }

  // return failure
  return false;
}

}
?>

<?php
/* $Id: nulogin.php,v 1.15.2.5 2011/04/27 00:27:35 rjones6061 Exp $
 *
 * This page handles logins for nonuser calendars.
 */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase ();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase ();

load_global_settings ();

$WebCalendar->setLanguage ();

if ( $single_user == 'Y') /* No login for single-user mode.*/ 
  die_miserable_death ( print_not_auth (22) );

if ( $use_http_auth )/* No web login for HTTP-based authentication.*/
  die_miserable_death ( print_not_auth (23) );
	
$login = getValue ( 'login' );
if ( empty ( $login ) )
  die_miserable_death ( translate ( 'A login must be specified' ) . '.' );

$date = getValue ( 'date' );
$return_path = getValue ( 'return_path' );
// Was a return path set?
$url = ( ! empty ( $return_path )
  ? clean_whitespace ( $return_path
     . ( ! empty ( $date ) ? '?date=' . $date : '' ) )
  : 'index.php' );

if ( $login == '__public__' )
  do_redirect ( $url );

if ( ! nonuser_load_variables ( $login, 'temp_' ) )
  die_miserable_death ( translate ( 'No such nonuser calendar' )
     . ": $login" );

if ( empty ( $temp_is_public ) || $temp_is_public != 'Y' )
  die_miserable_death ( print_not_auth (24) );
// calculate path for cookie
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER['PHP_SELF'];

$cookie_path = str_replace ( 'nulogin.php', '', $PHP_SELF );
// echo "Cookie path: $cookie_path\n";
if ( get_magic_quotes_gpc () )
  $login = stripslashes ( $login );

$login = trim ( $login );
if ( $login != addslashes ( $login ) )
  die_miserable_death ( translate ( 'Illegal characters in login' )
     . ' <tt>' . htmlentities ( $login ) . '</tt>.' );

// Allow proper login using NUC name
$encoded_login = encode_string ( $login . '|nonuser' );

// set login to expire in 365 days
SetCookie ( 'webcalendar_session', $encoded_login,
  ( ! empty ( $remember ) && $remember == 'yes' ?
  86400 * 365 + time () : 0 ), $cookie_path );

do_redirect ( $url );

?>

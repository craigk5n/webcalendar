<?php
/* $Id$
 *
 * This page handles logins for nonuser calendars.
 */
require_once 'includes/classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WC->initializeFirstPhase ();
 
include_once 'includes/access.php';

require_once 'includes/classes/WebCalSmarty.class.php';
$smarty = new WebCalSmarty ( $WC );

$WC->initializeSecondPhase ();

//$smarty->display ( 'login.tpl' );
$WC->setLanguage ();

if ( _WC_SINGLE_USER /* No login for single-user mode.*/ ||
    _WC_HTTP_AUTH )/* No web login for HTTP-based authentication.*/
  die_miserable_death ( print_not_auth () );

$login = $WC->getValue ( 'login' );
if ( empty ( $login ) )
  die_miserable_death ( translate ( 'A login must be specified' ) . '.' );

$date = $WC->getValue ( 'date' );
$return_path = $WC->getValue ( 'return_path' );
// Was a return path set?
$url = ( ! empty ( $return_path )
  ? clean_whitespace ( $return_path
     . ( ! empty ( $date ) ? '?date=' . $date : '' ) )
  : 'index.php' );

if ( ! $nucData = $WC->User->loadVariables ( $login ) )
  die_miserable_death ( translate ( 'No such nonuser calendar' )
     . ": $login" );

if ( $nucData['is_public'] != 'Y' )
  die_miserable_death ( print_not_auth () );

$cookie_path = str_replace ( 'nulogin.php', '', $_SERVER['PHP_SELF'] );
// echo "Cookie path: $cookie_path\n";
if ( get_magic_quotes_gpc () )
  $login = stripslashes ( $login );

$login = trim ( $login );
if ( $login != addslashes ( $login ) )
  die_miserable_death ( translate ( 'Illegal characters in login' )
     . ' <tt>' . htmlentities ( $login ) . '</tt>.' );

// Allow proper login using NUC name
$encoded_login = $WC->encode_string ( $login . '|nonuser' );

// set login to expire in 365 days
SetCookie ( 'webcalendar_session', $encoded_login,
  ( ! empty ( $remember ) && $remember == 'yes' ? 
  ONE_DAY * 365 + time () : 0 ), $cookie_path );
SetCookie ( 'webcalendar_login', $login, 0, $cookie_path );

do_redirect ( $url );

?>

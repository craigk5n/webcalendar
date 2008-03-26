<?php
/* $Id$
 *
 * Description:
 *   Provides login mechanism for web service clients.
 */

// If you have moved this script out of the WebCalendar directory, which you
// probably should do since it would be better for security reasons, you would
// need to change _WC_BASE_DIR to point to the webcalendar include directory.

// _WC_BASE_DIR points to the base WebCalendar directory relative to
// current working directory

define ( '_WC_BASE_DIR', '..' );
define ( '_WC_INCLUDE_DIR', _WC_BASE_DIR . '/includes/' );

include _WC_INCLUDE_DIR . 'translate.php';
require_once _WC_INCLUDE_DIR . 'classes/WebCalendar.class';

$WC =& new WebCalendar ( __FILE__ );

include _WC_INCLUDE_DIR . 'config.php';
include _WC_INCLUDE_DIR . 'dbi4php.php';
include _WC_INCLUDE_DIR . 'functions.php';

$WC->initializeFirstPhase ();

$WC->initializeSecondPhase ();


if ( ! empty ( $last_login ) )
  $login = '';


$cookie_path = str_replace ( 'login.php', '', $_SERVER['PHP_SELF'] );
// echo 'Cookie path: ' . $cookie_path;

$out = '
<login>';

if ( _WC_SINGLE_USER )
  // No login for single-user mode.
  $out .= '
  <error>' . translate ( 'No login required for single-user mode.' )
   . '</error>';
else
if ( _WC_HTTP_AUTH )
  // There is no login page when using HTTP authorization.
  $out .= '
  <error>' . translate ( 'No login required for HTTP authentication.' )
   . '</error>';
else {
  $login = $WC->getValue ( 'login' );
  $password = $WC->getValue ( 'password' );
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    $login = trim ( $login );
    if ( user_valid_login ( $login, $password ) ) {
      // Set login to expire in 365 days.
      srand ( ( double ) microtime () * 1000000 );
      $salt = chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) )
       . chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) );
      $encoded_login = $WC->encode_string ( $login . '|'
       . crypt ( $password, $salt ) );
      // SetCookie ( 'webcalendar_session', $encoded_login, 0, $cookie_path );
      $out .= '
  <cookieName>webcalendar_session</cookieName>
  <cookieValue>$encoded_login</cookieValue>' . ( $WC->isAdmin() ? '
  <admin>1</admin>' : '' ) . '
  <calendarName>' . generate_application_name () . '</calendarName>
  <appName>' . htmlspecialchars ( PROGRAM_NAME ) . '</appName>
  <appVersion>' . htmlspecialchars ( _WEBCAL_PROGRAM_VERSION ) . '</appVersion>
  <appDate>' . htmlspecialchars ( PROGRAM_DATE ) . '</appDate>';
    } else
      $out .= '
  <error>Invalid login</error>';
  }
}

echo $out . '
</login>
';

?>

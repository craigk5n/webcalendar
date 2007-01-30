<?php
/* $Id$
 *
 * Description:
 *   Provides login mechanism for web service clients.
 */

$basedir = '..';
$includedir = '../includes';

include $includedir . '/translate.php';
require_once $includedir . '/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include $includedir . '/config.php';
include $includedir . '/dbi4php.php';
include $includedir . '/functions.php';

$WebCalendar->initializeFirstPhase ();

include "$includedir/$user_inc";

$WebCalendar->initializeSecondPhase ();

load_global_settings ();

if ( ! empty ( $last_login ) )
  $login = '';

// Calculate path for cookie.
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER['PHP_SELF'];

$cookie_path = str_replace ( 'login.php', '', $PHP_SELF );
// echo 'Cookie path: ' . $cookie_path;

$out = '
<login>';

if ( ! empty ($single_user) && $single_user == 'Y' )
  // No login for single-user mode.
  $out .= '
  <error>' . translate ( 'No login required for single-user mode.' )
   . '</error>';
else
if ( $use_http_auth )
  // There is no login page when using HTTP authorization.
  $out .= '
  <error>' . translate ( 'No login required for HTTP authentication.' )
   . '</error>';
else {
  $login = getValue ( 'login' );
  $password = getValue ( 'password' );
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    $login = trim ( $login );
    if ( user_valid_login ( $login, $password ) ) {
      user_load_variables ( $login, '' );
      // Set login to expire in 365 days.
      srand ( ( double ) microtime () * 1000000 );
      $salt = chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) )
       . chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) );
      $encoded_login = encode_string ( $login . '|'
       . crypt ( $password, $salt ) );
      // SetCookie ( 'webcalendar_session', $encoded_login, 0, $cookie_path );
      $out .= '
  <cookieName>webcalendar_session</cookieName>
  <cookieValue>$encoded_login</cookieValue>' . ( $is_admin ? '
  <admin>1</admin>' : '' ) . '
  <calendarName>' . generate_application_name () . '</calendarName>
  <appName>' . htmlspecialchars ( $PROGRAM_NAME ) . '</appName>
  <appVersion>' . htmlspecialchars ( $PROGRAM_VERSION ) . '</appVersion>
  <appDate>' . htmlspecialchars ( $PROGRAM_DATE ) . '</appDate>';
    } else
      $out .= '
  <error>Invalid login</error>';
  }
}

echo $out . '
</login>
';

?>

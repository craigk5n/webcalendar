<?php
/**
 * Description:
 *   Provides login mechanism for web service clients.
 */

define( '__WC_BASEDIR', '../' ); // Points to the base WebCalendar directory
                 // relative to current working directory.
define( '__WC_INCLUDEDIR', __WC_BASEDIR . 'includes/' );
define( '__WC_CLASSDIR', __WC_INCLUDEDIR . 'classes/' );

include_once __WC_INCLUDEDIR . 'translate.php';
require_once __WC_CLASSDIR . 'WebCalendar.php';

$WebCalendar = new WebCalendar( __FILE__ );

include __WC_INCLUDEDIR . 'config.php';
include __WC_INCLUDEDIR . 'dbi4php.php';
include __WC_INCLUDEDIR . 'functions.php';

$WebCalendar->initializeFirstPhase();

include __WC_INCLUDEDIR . $user_inc;

$WebCalendar->initializeSecondPhase();

load_global_settings();

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
      srand ( ( double ) microtime() * 1000000 );
      $salt = chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) )
       . chr ( rand ( ord ( 'A' ), ord ( 'z' ) ) );
      $encoded_login = encode_string ( $login . '|'
       . crypt ( $password, $salt ) );
      // sendCookie ( 'webcalendar_session', $encoded_login, 0, $cookie_path );
      $out .= '
  <cookieName>webcalendar_session</cookieName>
  <cookieValue>$encoded_login</cookieValue>' . ( $is_admin ? '
  <admin>1</admin>' : '' ) . '
  <calendarName>' . generate_application_name() . '</calendarName>
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

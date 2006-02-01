<?php
/*
 * $Id$
 *
 * Description:
 * 	Provides login mechanism for web service clients.
 */

$basedir = "..";
$includedir = "../includes";

require_once "$includedir/classes/WebCalendar.class";

$WebCalendar =& new WebCalendar ( __FILE__ );

include "$includedir/config.php";
include "$includedir/dbi4php.php";
include "$includedir/functions.php";

$WebCalendar->initializeFirstPhase();

include "$includedir/$user_inc";
include "$includedir/translate.php";

$WebCalendar->initializeSecondPhase();

load_global_settings ();

if ( ! empty ( $last_login ) )
  $login = "";


// calculate path for cookie
if ( empty ( $PHP_SELF ) )
  $PHP_SELF = $_SERVER["PHP_SELF"];
$cookie_path = str_replace ( "login.php", "", $PHP_SELF );
//echo "Cookie path: $cookie_path\n";

$out = "<login>\n";

if ( $single_user == "Y" ) {
  // No login for single-user mode
  $out .= "<error>No login required for single-user mode</error>\n";
} else if ( $use_http_auth ) {
  // There is no login page when using HTTP authorization
  $out .= "<error>No login required for HTTP authentication</error>\n";
} else {
  $login = getValue ( 'login' );
  $password = getValue ( 'password' );
  if ( ! empty ( $login ) && ! empty ( $password ) ) {
    $login = trim ( $login );
    if ( user_valid_login ( $login, $password ) ) {
      user_load_variables ( $login, "" );
      // set login to expire in 365 days
      srand((double) microtime() * 1000000);
      $salt = chr( rand(ord('A'), ord('z'))) . chr( rand(ord('A'), ord('z')));
      $encoded_login = encode_string ( $login . "|" . crypt($password, $salt) );
      //SetCookie ( "webcalendar_session", $encoded_login, 0, $cookie_path );
      $out .= "  <cookieName>webcalendar_session</cookieName>\n";
      $out .= "  <cookieValue>$encoded_login</cookieValue>\n";
      if ( $is_admin )
        $out .= "  <admin>1</admin>\n";
      if ( empty ( $APPLICATION_NAME ) )
        $APPLICATION_NAME = "WebCalendar";
      $out .= "  <calendarName>" . htmlspecialchars ( $APPLICATION_NAME ) .
        "</calendarName>\n";
      $out .= "  <appName>" .  htmlspecialchars ( $PROGRAM_NAME ) .
        "</appName>\n";
      $out .= "  <appVersion>" .  htmlspecialchars ( $PROGRAM_VERSION ) .
        "</appVersion>\n";
      $out .= "  <appDate>" .  htmlspecialchars ( $PROGRAM_DATE ) .
        "</appDate>\n";
    } else {
      $out .= "  <error>Invalid login</error>\n";
    }
  }
}

echo $out;
echo "</login>\n";
?>

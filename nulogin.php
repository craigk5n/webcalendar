<?php
/*
 * $Id$
 *
 * This page handles logins for nonuser calendars.
 */
require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include_once 'includes/access.php';
include 'includes/translate.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings ();

$WebCalendar->setLanguage();

// No login for single-user mode
if ( $single_user == 'Y' ) {
  die_miserable_death ( print_not_auth () );
}

// No web login for HTTP-based authentication
if ( $use_http_auth ) {
  die_miserable_death ( print_not_auth () );
}

$login = getValue ( 'login' );
$return_path = getValue ( 'return_path' );

if ( empty ( $login ) ) {
  die_miserable_death ( 'A login must be specified' );
}

// see if a return path was set
if ( ! empty ( $return_path ) ) {
  $return_path = clean_whitespace ( $return_path );
  $url = $return_path;
} else {
  $url = 'index.php';
}

if ( $login == '__public__' ) {
  do_redirect ( $url );
}

if ( ! nonuser_load_variables ( $login, 'temp_' ) ) {
  die_miserable_death ( translate ( 'No such nonuser calendar' ) .
    ': ' . $login );
}
if ( empty ( $temp_is_public ) || $temp_is_public != 'Y' ) {
  die_miserable_death ( print_not_auth () );
}

// calculate path for cookie
if ( empty ( $PHP_SELF ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
$cookie_path = str_replace ( 'nulogin.php', '', $PHP_SELF );
//echo "Cookie path: $cookie_path\n";

if ( get_magic_quotes_gpc() ) {
  $login = stripslashes ( $login );
}
$login = trim ( $login );
if ( $login != addslashes ( $login ) ) {
  die_miserable_death ( 'Illegal characters in login ' .
    '<tt>' . htmlentities ( $login ) . '</tt>' );
}

// set login to expire in 365 days
$encoded_login = encode_string ( $login . '|nonuser' );

if ( ! empty ( $remember ) && $remember == 'yes' ) {
  SetCookie ( 'webcalendar_session', $encoded_login,
    time() + ( 24 * 3600 * 365 ), $cookie_path );
} else {
  SetCookie ( 'webcalendar_session', $encoded_login, 0, $cookie_path );
}


do_redirect ( $url );

?>

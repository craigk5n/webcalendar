<?php
/* $Id$ */
@session_start ();
foreach($_SESSION as $key=>$value) {
  $dummy[$key]=$value;  // copy to a dummy array
}
if ( ! empty ( $dummy ) ) 
  foreach ($dummy as $key=>$value) {
   if ( substr( $key, 0 , 11 ) == 'webcalendar' )
     unset( $_SESSION[$key] );
  }
//php 4.1.0 may have issues with the above code
unset ( $_SESSION['webcalendar_login'] );

require_once 'includes/classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WC->initializeFirstPhase();
 
include_once 'includes/access.php';
include_once 'includes/header.php';

require_once 'includes/classes/WebCalSmarty.class.php';
$smarty = new WebCalSmarty ( $WC );

$WC->initializeSecondPhase();

// Change this to true to show "no such user" or "invalid password" on
// login failures.
$showLoginFailureReason = true;
$error  = '';

$last_login = $WC->getPOST ( 'last_login' );
$login = $WC->getPOST ( 'login' );
$password = $WC->getPOST ( 'password' );

if ( ! empty ( $last_login ) ) {
  $login = '';
}

if ( empty ( $webcalendar_login ) ) {
  $webcalendar_login = '';
}

$remember_last_login = ( getPref ( 'REMEMBER_LAST_LOGIN', 2 ) || 
$WC->getGET ( 'remember' ) == 'yes' );
if ( $remember_last_login && empty ( $login ) ) {
  $last_login = $login = $webcalendar_login;
}

$WC->setLanguage();

// Look for action=logout
$logout = false;
$action = $WC->getGET ( 'action' );
if ( ! empty ( $action ) && $action == 'logout' ) {
  $logout = true;
  $return_path = '';
  SetCookie ( 'webcalendar_login', '', 0 );
  SetCookie ( 'webcalendar_last_view', '', 0 );
} else if (  empty ( $return_path ) ) {
  // see if a return path was set
  $return_path = get_last_view();
  if ( ! empty ( $return_path ) ) 
    SetCookie ( 'webcalendar_last_view', '', 0 );
}

if ( ! empty ( $return_path ) ) {
  $return_path = clean_whitespace ( $return_path );
  $url = $return_path;
} else {
  $url = 'index.php';
}

// If Application Name is set to Title then get translation
// If not, use the Admin defined Application Name
$appStr =  generate_application_name ();

$cookie_path = str_replace ( 'login.php', '', $_SERVER['PHP_SELF'] );
$cookie_time = ( $remember_last_login ? time() + ( ONE_DAY * 365 ) : 0 );
//echo "Cookie path: $cookie_path\n";

if ( _WC_SINGLE_USER || _WC_HTTP_AUTH ) {
  // No login for single-user mode
  // There is no login page when using HTTP authorization
  do_redirect ( 'index.php' );
} else {
  if ( ! empty ( $login ) && ! empty ( $password ) && ! $logout ) {
    if ( get_magic_quotes_gpc() ) {
      $password = stripslashes ( $password );
      $login = stripslashes ( $login );
    }
    $login = trim ( $login );
    if ( $login != addslashes ( $login ) ) {
      die_miserable_death ( 'Illegal characters in login ' .
        '<tt>' . htmlentities ( $login ) . '</tt>' );
    }
    if ( $WC->User->validLogin ( $login, $password ) ) {
     $WC->User->loadVariables ( $login, '' );

      $encoded_login = $WC->encode_string ( $login . '|' . crypt(md5($password) ) );
      // set login to expire in 365 days if Remember Last Login
      SetCookie ( 'webcalendar_session', $encoded_login, $cookie_time, $cookie_path );
 
      // The cookie "webcalendar_login" is provided as a convenience to
      // other apps that may wish to find out what the last calendar
      // login was, so they can use week_ssi.php as a server-side include.
      // As such, it's not a security risk to have it un-encoded since it
      // is not used to allow logins within this app.  It is used to
      // load user preferences on the login page (before anyone has
      // logged in) if REMEMBER_LAST_LOGIN is set to "Y" (in admin.php).
      SetCookie ( 'webcalendar_login', $login, $cookie_time, $cookie_path );

      if ( ! empty ( $GLOBALS['newUserUrl'] ) ) $url = $GLOBALS['newUserUrl'];
      do_redirect ( $url );
    } else {
      // Invalid login
      if ( empty ( $error ) && $showLoginFailureReason ) {
        $error = translate('Invalid login', true );
      }
      activity_log ( 0, 'system', '', LOG_LOGIN_FAILURE, 
        translate( 'Username' ) . ": " . $login .
        ", IP: " . $_SERVER['REMOTE_ADDR'] );
    }
  } else {
    // No login info... just present empty login page
    //$error = "Start";
  }
  // delete current user
  SetCookie ( 'webcalendar_session', '', 0, $cookie_path );
}
$smarty->assign ( 'appStr', $appStr );
$smarty->assign ( 'login', $login );
$smarty->assign ( 'logout', $logout );
$smarty->assign ( 'error', $error );
$smarty->assign ( 'return_path', $return_path );
$smarty->assign ( 'last_login', $last_login );
$smarty->assign ( 'remember_last_login', $remember_last_login );


$nulist = @get_nonuser_cals ();
$nuclist = array();
for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
  if ( $nulist[$i]['cal_is_public'] == 'Y' ) {
    $nuclist[$i]['userid'] = $nulist[$i]['cal_login_id'];
    $nuclist[$i]['fullname'] = $nulist[$i]['cal_fullname'];
  }
}

if ( getPref ( 'ALLOW_SELF_REGISTRATION', 2  ) ) { 
  // We can limit what domain is allowed to self register
  // $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
  $valid_ip = validate_domain ();
}
$BodyX = ( ! $logout ? 'onload="myOnLoad();"' : '' );
build_header ( '', '', $BodyX , 61 );

$smarty->display ( 'login.tpl' );
?>


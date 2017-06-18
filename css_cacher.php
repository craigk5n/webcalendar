<?php
/* $Id: css_cacher.php,v 1.18.2.2 2007/11/13 19:37:28 umcesrjones Exp $ */
define ( '_ISVALID', true );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

do_config ( 'includes/settings.php' );
include 'includes/' . $user_inc;
include_once 'includes/access.php';
include_once 'includes/validate.php';
include_once 'includes/gradient.php';

load_global_settings ();

@session_start ();
$login = ( empty ( $_SESSION['webcal_login'] )
  ? '__public__' : $_SESSION['webcal_login'] );
$login = ( empty ( $_SESSION['webcal_tmp_login'] )
  ? $login : $_SESSION['webcal_tmp_login'] );
// .
// If calling script uses 'guest', we must also.
if ( ! empty ( $_GET['login'] ) )
  $login = $_GET['login'];
else
if ( ! empty ( $_REQUEST['login'] ) )
  $login = $_REQUEST['login'];

if ( substr ( $login, 0, 10 ) == '__public__' )
  $login = '__public__';
	
load_user_preferences ( $login );
// .
// We will cache CSS as default, but override from admin and pref
// by incrementing the webcalendar_csscache cookie value.
$cookie = ( isset ( $_COOKIE['webcalendar_csscache'] )
  ? $_COOKIE['webcalendar_csscache'] : 0 );

header ( 'Content-type: text/css' );
header ( 'Last-Modified: ' . date ( 'r', mktime ( 0, 0, 0 ) + $cookie ) );
// .
// If we are calling from admin or pref, expire CSS now.
if ( empty ( $_SESSION['webcal_tmp_login'] ) ) {
  header ( 'Expires: ' . date ( 'D, j M Y H:i:s', time () + 86400 ) . ' UTC' );
  header ( 'Cache-Control: Public' );
  header ( 'Pragma: Public' );
}

if ( ini_get ( 'zlib.output_compression' ) != 1 )
  ob_start( 'ob_gzhandler' );

include_once ( 'includes/styles.php' );

unset ( $_SESSION['webcal_tmp_login'] );

?>

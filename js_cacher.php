<?php
/* $Id$ */
// If the javascript doesn't need any input from php,
// then we can cache it and not run init.php.
define ( '_ISVALID', true );
$caching = false;
if ( empty ( $inc ) )
  $inc = $_GET['inc'];
if ( empty ( $inc ) && ! empty ( $_REQUEST['inc'] ) )
  $inc = $_REQUEST['inc'];

$arinc = explode ( '/', $inc );

if ( $arinc[0] != 'js' && $arinc[0] != 'htmlarea' )
  return false;

// get list of files in the js directory
$myDirectory = opendir( 'includes/' . $arinc[0] );
while($fileName = readdir($myDirectory)) {
	$fileList[] = $fileName;
}
closedir($myDirectory);

if ( ! empty ( $arinc[2] ) && stristr ( $arinc[2], 'true' ) )
  $caching = true;

header ( 'Content-type: text/javascript' );
if ( $caching == true ) {
  $cookie = ( isset ( $_COOKIE['webcalendar_csscache'] ) ?
    $_COOKIE['webcalendar_csscache'] : 0 );

  header('Last-Modified: '. date('r', mktime ( 0,0,0 ) + $cookie ) );
  header ( 'Expires: ' . date ( 'D, j M Y H:i:s', time () + 86400 ) . ' UTC' );
  header ( 'Cache-Control: Public' );
  header ( 'Pragma: Public' );
} else {
  include 'includes/translate.php';
  include 'includes/config.php';
  include 'includes/dbi4php.php';
  include 'includes/functions.php';

  do_config ( 'includes/settings.php' );
  include 'includes/' . $user_inc;
  include_once 'includes/access.php';
  include_once 'includes/validate.php';
  include_once 'includes/gradient.php';

  load_global_settings ();
	@session_start ();
  $login = ( ! empty ( $_SESSION['webcal_login'] ) ? $_SESSION['webcal_login'] : '__public__' );

  load_user_preferences ();

  send_no_cache_header ();
}

// We don't want to compress for IE6 because of 'object expected' errors.
if ( ini_get ( 'zlib.output_compression' ) != 1 && !
 stristr ( $_SERVER['HTTP_USER_AGENT'], 'MSIE 6' ) )
  ob_start ( 'ob_gzhandler' );

//we only allow includes if they exist in our includes/js directory, or htmlarea
$newinc = 'includes/' . $arinc[0] . '/' . $arinc[1];
if ( is_file ( $newinc ) && in_array ( $arinc[1], $fileList ) )
  include_once ( $newinc );

?>

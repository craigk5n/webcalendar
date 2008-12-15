<?php
/* $Id$ */
// If the javascript doesn't need any input from php,
// then we can cache it and not run init.php.
define( '_ISVALID', true );

if ( empty( $inc ) )
  $inc = $_GET['inc'];

if ( empty( $inc ) && ! empty( $_REQUEST['inc'] ) )
  $inc = $_REQUEST['inc'];

$arinc = explode( '/', $inc );

if ( $arinc[0] != 'js' && $arinc[0] != 'htmlarea' )
  return false;

// Get list of files in the js directory.
$myDirectory = opendir( 'includes/' . $arinc[0] );
while ( $fileName = readdir( $myDirectory ) ) {
  $fileList[] = $fileName;
}
closedir( $myDirectory );

// We don't want to compress for IE6 because of 'object expected' errors.
// Still hold the output till we're done, though.
ob_start( ini_get( 'zlib.output_compression' ) != 1
  && ! stristr( $_SERVER['HTTP_USER_AGENT'], 'MSIE 6' ) ? 'ob_gzhandler' : '' );

header( 'Content-type: text/javascript' );
header( 'Last-Modified: ' . gmdate( 'r' );
header( 'Expires: ' . gmdate( 'D, j M Y H:i:s \U\TC', gmmktime() + 3600 ) );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

if ( ( ! empty( $arinc[2] ) && stristr( $arinc[2], 'true' ) ) ) {
  header( 'Last-Modified: ' . gmdate( 'r', gmmktime() - 10000 );
  header( 'Expires: ' . gmdate( 'D, j M Y H:i:s \U\TC', gmmktime() - 3600 ) );
} else {
  include_once 'includes/translate.php';
  include_once 'includes/config.php';
  include_once 'includes/dbi4php.php';
  include_once 'includes/formvars.php';
  include_once 'includes/functions.php';

  do_config( 'includes/settings.php' );
  include_once 'includes/' . $user_inc;
  include_once 'includes/access.php';
  include_once 'includes/validate.php';
  include_once 'includes/gradient.php';

  load_global_settings();
  @session_start();
  $login = ( empty( $_SESSION['webcal_login'] )
    ? '__public__' : $_SESSION['webcal_login'] );

  load_user_preferences();
  send_no_cache_header();
}

// We only allow includes if they exist in our includes/js directory, or HTMLarea.
$newinc = 'includes/' . $arinc[0] . '/' . $arinc[1];
if ( is_file( $newinc ) && in_array( $arinc[1], $fileList ) )
  include_once $newinc;

ob_end_flush();

?>

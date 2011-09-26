<?php // $Id$
// If the JavaScript doesn't need any input from PHP,
// this file should not be called.
define( '_ISVALID', true );

if( empty( $inc ) )
  $inc = $_GET['inc'];

if( empty( $inc ) && ! empty( $_REQUEST['inc'] ) )
  $inc = $_REQUEST['inc'];

$arinc = explode( '/', $inc );

// We only allow includes if they exist in our js or HTMLarea directories.
if( $arinc[0] != 'js' && $arinc[0] != 'htmlarea' )
  return false;

if( is_dir( 'includes' ) )
  $newinc = 'includes';
elseif( is_dir( '../includes' ) )
  $newinc = '../includes';

// Get list of files in the js directory.
$myDirectory = opendir( "$newinc/$arinc[0]" );
while( $fileName = readdir( $myDirectory ) ) {
  $fileList[] = $fileName;
}
closedir( $myDirectory );

// We don't want to compress JavaScript for IE6
// because of 'object expected' errors.
// Still hold the output till we're done.
ob_start( ini_get( 'zlib.output_compression' ) != 1
  && ! stristr( $_SERVER['HTTP_USER_AGENT'], 'MSIE 6' ) ? 'ob_gzhandler' : '' );

foreach( array(
    'access',
    'config',
    'dbi4php',
    'formvars',
    'functions',
    'translate',
    'validate',
  ) as $i ) {
  include_once 'includes/' . $i . '.php';
}

do_config( 'includes/settings.php' );
include_once 'includes/' . $user_inc;
include_once 'includes/gradient.php';

header( 'Content-type: text/javascript' );
header( 'Cache-Control: Public' );
header( 'Pragma: Public' );

send_no_cache_header();
load_global_settings();
@session_start();

$login = ( empty( $_SESSION['webcal_login'] )
  ? '__public__' : $_SESSION['webcal_login'] );

load_user_preferences();

foreach( $arinc as $a ) {
  if( $a == 'true' || $a == 'false' )
    break;

  $newinc .= '/' . $a;
}

if( is_file( $newinc ) && in_array( $arinc[1], $fileList ) )
  include_once $newinc;

ob_end_flush();

?>

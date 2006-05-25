<?php
/* $Id$ */
 //if the javascript doesn't need any input from php, then we can cache it
 // and not run init.php
$caching = false;
if ( empty ( $inc ) )
  $inc = $_GET['inc'];
$arinc = explode ( '/', $inc );

if ( $arinc[0] != 'js')
 return false;
 
if ( ! empty ( $arinc[2] ) &&  preg_match ( "/true/", $arinc[2] ) ) { 
  $caching = true;
}

header( 'Content-type: text/javascript' ); 
if ( $caching == true ) {
  header('Expires: ' . date('D, j M Y H:i:s', time() + 86400) . ' UTC');
  header('Cache-Control: Public');
  header('Pragma: Public');
} else{
  include_once 'includes/init.php';
  send_no_cache_header ();
}
if ( ini_get ( 'zlib.output_compression' ) != 1 ) 
  ob_start( 'ob_gzhandler' );

$newinc = $arinc[0] . '/' . $arinc[1]; 
include_once ( "includes/$newinc" );

?>

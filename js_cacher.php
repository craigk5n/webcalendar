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
  header('Last-Modified: '.date('r'));
  header('Expires: ' . date('D, j M Y H:i:s', time() + 86400) . ' UTC');
  header('Cache-Control: Public');
  header('Pragma: Public');
} else{
  require_once 'includes/classes/WebCalendar.class';
     
  $WebCalendar =& new WebCalendar ( __FILE__ );    
     
  include 'includes/config.php';    
  include 'includes/dbi4php.php';    
  include 'includes/functions.php';    
     
  $WebCalendar->initializeFirstPhase();    
     
  include "includes/$user_inc";
  include_once 'includes/access.php';    
  include_once 'includes/validate.php';    
  include 'includes/translate.php';  
  include_once 'includes/gradient.php';

  $WebCalendar->initializeSecondPhase();

  load_global_settings ();
  load_user_preferences ();

  send_no_cache_header ();
}

// we don't want to compress for IE6 because of 'object expected' errors
$isIE6 = ereg ( "MSIE 6", $_SERVER['HTTP_USER_AGENT'] );
if ( ini_get ( 'zlib.output_compression' ) != 1  && ! $isIE6 ) 
  ob_start( 'ob_gzhandler' );

$newinc = $arinc[0] . '/' . $arinc[1]; 
include_once ( "includes/$newinc" );

?>

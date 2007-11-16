<?php 
/* $Id$ */
define ( 'CALTYPE', 'year' ); 
include_once 'includes/init.php';
require_once $smarty->_get_plugin_filepath('function', 'small_month');

if ( ! $WC->isLogin( $user ) && $WC->isNonuserAdmin() )
  $layers = loadLayers ($user);
else
  $layers = loadLayers ();


if ( getPref ( 'BOLD_DAYS_IN_YEAR' ) ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ();

  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ();
}

//populate the month info
$monthArray = array();
for ( $i=1;$i<=12;$i++ ) {
  $dateYmd = $WC->thisyear . sprintf ( '%02d', $i );
  $monthArray[$i] = smarty_function_small_month( array ( 'dateYmd'=>$dateYmd ), $smarty ); 
}
$smarty->assign ( 'monthArray', $monthArray );
$smarty->assign ( 'navArrows', true );

build_header ();

$smarty->display ( 'year.tpl' );
?>

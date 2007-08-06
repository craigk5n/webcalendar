<?php
/* $Id$ */
define ( 'CALTYPE', 'month' ); 
include_once 'includes/init.php';

$layers = loadLayers ( $WC->userId() );

$smarty->assign('display_sm_month', getPref ( 'DISPLAY_SM_MONTH' ) );
$smarty->assign('display_tasks', getPref ( 'DISPLAY_TASKS', 0 ) );

$smarty->assign('tableWidth', ( getPref ( 'DISPLAY_TASKS', 0 )? '80%' :'100%') );
$smarty->assign('monthURL', 'month.php?' . ( $WC->catId()
  ? 'cat_id=' . $WC->catId() . '&amp;' : '' ) );

$smarty->assign('navName', 'month' ); 

$HeadX = generate_refresh_meta ();
$BodyX = ( getPref ( 'DISPLAY_TASKS', 0 ) ? 
  "onload=\"sortTasks( 0, {$WC->catId()} );\"" : '' );
build_header ( array ( 'entries.js', 'popups.js', 'visible.js' ), $HeadX, $BodyX );

$smarty->display('month.tpl');
?>

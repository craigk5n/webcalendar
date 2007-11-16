<?php
/* $Id$ */
define ( 'CALTYPE', 'month' ); 
include_once 'includes/init.php';

$layers = loadLayers ( $WC->userId() );

$smarty->assign('monthURL', 'month.php?' . ( $WC->catId()
  ? 'cat_id=' . $WC->catId() . '&amp;' : '' ) );

$smarty->assign('navName', 'month' ); 

$BodyX = 'onload="onLoad();"';
build_header ( array ( 'calendar.js', 'multiselect.js' ), '', $BodyX );

$smarty->display('month.tpl');
?>

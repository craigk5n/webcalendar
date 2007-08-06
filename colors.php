<?php
/* $Id$ */
include_once 'includes/init.php';

$color = $WC->getGET ( 'color' );
if ( empty ( $color ) )
  exit;

build_header ( array ( 'colors.js' ), '', 'onload="fillhtml(); setInit();"', 61 );

$smarty->assign ( 'color', $color );
$smarty->display ( 'colors.tpl' );

?>

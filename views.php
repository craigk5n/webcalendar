<?php
/* $Id$ */
include_once 'includes/init.php';

$smarty->assign('tabs_ar', array ( 'views'=>translate ( 'Views' ) ) );

build_header ( array ( 'views.js', 'visible.js' ) );

$smarty->assign ( 'views', loadViews () );
$smarty->display ( 'views.tpl' );

?>

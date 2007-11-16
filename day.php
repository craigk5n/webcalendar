<?php
/* $Id$ */
define ( 'CALTYPE', 'day' ); 
include_once 'includes/init.php';

$layers = loadLayers ( $WC->userLoginId() );
  
$smallTasks = '';

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ();

/* Pre-load the non-repeating events for quicker access */
$events = read_events ();

  /* Pre-load tasks for quicker access */
if ( getPref ( 'DISPLAY_TASKS_IN_GRID')  )
  $tasks = read_tasks ();

$smarty->assign('navName', 'day' );
$smarty->assign('navArrows', true );

$BodyX = 'onload="onLoad();"';
build_header ( array ( 'calendar.js' ), '',$BodyX );

$smarty->display('day.tpl');
?>

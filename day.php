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

$HeadX = generate_refresh_meta ();
$BodyX = ( getPref ( 'DISPLAY_TASKS' ) ? 
  "onload=\"sortTasks( 0, {$WC->catId()} );\"" : '' );
build_header ( array ( 'entries.js',  'popups.js' ), $HeadX,$BodyX );

$smarty->display('day.tpl');
?>

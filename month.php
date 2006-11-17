<?php
/* $Id$ */
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin) {
  load_user_layers ($user);
} else if ( empty ( $user ) ) {
  load_user_layers ();
}

load_user_categories ();

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextYmd = date ( 'Ymd', $next );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevYmd = date ( 'Ymd', $prev );

if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = mktime ( 0,0,0, $prevmonth, 1, $prevyear );
  $enddate = mktime ( 23, 59, 59, $nextmonth +1 ,0, $nextyear );
} else {
  $boldDays = false;
  $startdate = mktime (  0,0,0, $thismonth, 1, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth +1, 0, $thisyear );
}

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) 
    ? $user : $login, $cat_id, $startdate, $enddate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

if ( $DISPLAY_TASKS == 'Y' ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) && $is_assistant )
    ? $user : $login, $enddate, $cat_id );
}
$tableWidth = '100%';
$monthURL = ( ! empty ( $cat_id )? "month.php?cat_id=$cat_id&amp;" :'month.php?' );
$prevMonth1 = $nextMonth1 = $prevMonth2 = $nextMonth2 = '';
$smallTasks = $unapprovedStr = $printerStr = '';
if ( empty ( $DISPLAY_TASKS ) ||  $DISPLAY_TASKS == 'N' && $DISPLAY_SM_MONTH != 'N') {
  $prevMonth1 = display_small_month ( $prevmonth, $prevyear, true, true, 'prevmonth',
    $monthURL );
  $nextMonth1 = display_small_month ( $nextmonth, $nextyear, true, true, 'nextmonth',
    $monthURL ); 
}

if ( ! empty ( $DISPLAY_TASKS ) && $DISPLAY_TASKS == 'Y' && $friendly !=1 ) {
  $prevMonth2 = display_small_month ( $prevmonth, $prevyear, true, false, 'prevmonth',
    $monthURL ) . '<br />';
  $nextMonth2 = display_small_month ( $nextmonth, $nextyear, true, false, 'nextmonth',
    $monthURL ) . '<br />';
  $smallTasks = display_small_tasks ( $cat_id );
  $tableWidth = '80%';
}
$navStr = display_navigation( 'month' );
$monthStr = display_month ( $thismonth, $thisyear );
$eventinfo = ( ! empty ( $eventinfo )? $eventinfo : '' );

if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events ( ( $is_assistant || 
    $is_nonuser_admin ? $user : $login ) );
  $printerStr = generate_printer_friendly ( 'month.php' );
}
$trailerStr = print_trailer ();

$HeadX = generate_refresh_meta ();

$INC =  array('js/popups.php/true', 'js/visible.php');
print_header($INC,$HeadX);

echo <<<EOT
  <table border="0" width="100%" cellpadding="1">
    <tr>
      <td id="printarea" valign="top" width="{$tableWidth}" rowspan="2">
      {$prevMonth1}{$nextMonth1}
      {$navStr}
      {$monthStr}
     </td>
     <td valign="top" align="center">
      {$prevMonth2}{$nextMonth2}{$smallTasks}
     </td>
    </tr>
  </table>
{$eventinfo}
{$unapprovedStr}
{$printerStr}
{$trailerStr}
EOT;
?>

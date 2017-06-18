<?php
/* $Id: month.php,v 1.95.2.9 2010/08/15 18:54:34 cknudsen Exp $ */
include_once 'includes/init.php';

//check UAC
if ( ! access_can_access_function ( ACCESS_MONTH ) || 
  ( ! empty ( $user ) && ! access_user_calendar ( 'view', $user ) )  )
  send_to_preferred_view ();

  
if ( ( $user != $login ) && $is_nonuser_admin )
  load_user_layers ( $user );
else
if ( empty ( $user ) )
  load_user_layers ();

$cat_id = getValue ( 'cat_id', '-?[0-9,\-]*', true );
load_user_categories ();

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextYmd = date ( 'Ymd', $next );
$nextyear = substr ( $nextYmd, 0, 4 );
$nextmonth = substr ( $nextYmd, 4, 2 );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevYmd = date ( 'Ymd', $prev );
$prevyear = substr ( $prevYmd, 0, 4 );
$prevmonth = substr ( $prevYmd, 4, 2 );

if ( $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = mktime ( 0, 0, 0, $prevmonth, 0, $prevyear );
  $enddate = mktime ( 23, 59, 59, $nextmonth + 1, 0, $nextyear );
} else {
  $boldDays = false;
  $startdate = mktime ( 0, 0, 0, $thismonth, 0, $thisyear );
  $enddate = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
}

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

if ( $DISPLAY_TASKS_IN_GRID == 'Y' )
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) &&
    $is_assistant )
    ? $user : $login, $enddate, $cat_id );

$tableWidth = '100%';
$monthURL = 'month.php?' . ( ! empty ( $cat_id )
  ? 'cat_id=' . $cat_id . '&amp;' : '' );
$nextMonth1 = $nextMonth2 = $prevMonth1 = $prevMonth2 = '';
$printerStr = $smallTasks = $unapprovedStr = '';
if ( empty ( $DISPLAY_TASKS ) || $DISPLAY_TASKS == 'N' &&
  $DISPLAY_SM_MONTH != 'N' ) {
  $nextMonth1 = display_small_month ( $nextmonth, $nextyear, true, true,
    'nextmonth', $monthURL );
  $prevMonth1 = display_small_month ( $prevmonth, $prevyear, true, true,
    'prevmonth', $monthURL );
}

if ( $DISPLAY_TASKS == 'Y' && $friendly != 1 ) {
  if (  $DISPLAY_SM_MONTH != 'N' ) {
  $nextMonth2 = display_small_month ( $nextmonth, $nextyear, true, false,
    'nextmonth', $monthURL ) . '<br />';
  $prevMonth2 = display_small_month ( $prevmonth, $prevyear, true, false,
    'prevmonth', $monthURL ) . '<br />';
  } else {
    $nextMonth2 =  $prevMonth2 = '<br /><br /><br /><br />';
  }
  $smallTasks = display_small_tasks ( $cat_id );
  $tableWidth = '80%';
}
$eventinfo = ( ! empty ( $eventinfo ) ? $eventinfo : '' );
$monthStr = display_month ( $thismonth, $thisyear );
$navStr = display_navigation ( 'month' );

if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events (
    ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
  $printerStr = generate_printer_friendly ( 'month.php' );
}
$trailerStr = print_trailer ();

$HeadX = generate_refresh_meta ()
  . '<script src="includes/js/weekHover.js" type="text/javascript"></script>';

print_header ( array ( 'js/popups.php/true', 'js/visible.php' ), $HeadX,
'', false, false, false, false );

echo <<<EOT
    <table border="0" width="100%" cellpadding="1">
      <tr>
        <td id="printarea" valign="top" width="{$tableWidth}" rowspan="2">
          {$prevMonth1}{$nextMonth1}
          {$navStr}
          {$monthStr}
        </td>
        <td valign="top" align="center">
          {$prevMonth2}{$nextMonth2}<div id="minitask">{$smallTasks}</div>
        </td>
      </tr>
    </table>
    {$eventinfo}
    {$unapprovedStr}
    {$printerStr}
    {$trailerStr}
EOT;

?>

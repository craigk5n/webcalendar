<?php
/* $Id$ */
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();

$wday = strftime ( "%w", mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear ) );

$now = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
$nowYmd = date ( 'Ymd', $now );

$next = mktime ( 0, 0, 0, $thismonth, $thisday + 1, $thisyear );
$nextYmd = date ( 'Ymd', $next );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextday = date ( 'd', $next );

$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 1, $thisyear );
$prevYmd = date ( 'Ymd', $prev );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevday = date ( 'd', $prev );

if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
 $boldDays = true;
} else {
 $boldDays = false;
}

$startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$enddate = mktime ( 0, 0, 0, $thismonth +1 , 0, $thisyear );

$HeadX = '';
if ( $AUTO_REFRESH == 'Y' && ! empty ( $AUTO_REFRESH_TIME ) ) {
  $refresh = $AUTO_REFRESH_TIME * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=day.php?$u_url" .
    "date=$nowYmd$caturl" . ( ! empty ( $friendly ) ? '&amp;friendly=1' : '') . "\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( empty ( $user ) ? $login : $user,
  $cat_id, $startdate  );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( empty ( $user ) ? $login : $user, $startdate, $enddate,
  $cat_id  );
 
if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) )
    ? $user : $login, $startdate, $enddate, $cat_id );
}
?>

<table  width="100%" cellpadding="1">
<tr><td style="vertical-align:top; width:80%;" >
<?php
  echo display_navigation( 'day' );
?>
</td><td>&nbsp;</td></tr><tr><td>
<table class="glance" cellspacing="0" cellpadding="0">
<?php
if ( empty ( $TIME_SLOTS ) )
  $TIME_SLOTS = 24;

echo print_day_at_a_glance ( date ( 'Ymd', $now ),
  empty ( $user ) ? $login : $user, $can_add );
?>
</table>
</td>
<td style="vertical-align:top;" rowspan="2">
<!-- START MINICAL -->
<div class="minicalcontainer" style="text-align:center">
<?php echo display_small_month ( $thismonth, $thisyear, true ); ?>
</div>
<br />
<?php 
if ( ! empty ( $DISPLAY_TASKS ) && $DISPLAY_TASKS == 'Y' ) {
  echo display_small_tasks ( $cat_id );
}
?> 
</td></tr></table>
<br />
<?php
 if ( ! empty ( $eventinfo ) ) echo $eventinfo;

echo display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );

echo  '<br />';
echo generate_printer_friendly ( 'day.php' );
echo print_trailer (); ?>
</body>
</html>

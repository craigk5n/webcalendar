<?php
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
  $enddate = mktime ( 0,0,0, $nextmonth +1 ,0, $nextyear );
} else {
  $boldDays = false;
  $startdate = mktime (  0,0,0, $thismonth, 1, $thisyear );
  $enddate = mktime ( 0,0,0, $thismonth +1, 0, $thisyear );
}

$HeadX = '';
if ( $AUTO_REFRESH == 'Y' && ! empty ( $AUTO_REFRESH_TIME ) ) {
  $refresh = $AUTO_REFRESH_TIME * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=month.php?$u_url" .
    "year=$thisyear&amp;month=$thismonth$caturl" . 
    ( ! empty ( $friendly ) ? '&amp;friendly=1' : '') . "\" />\n";
}
$INC =  array('js/popups.php', 'js/visible.php/true');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id, $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

if ( $DISPLAY_TASKS == 'Y' ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) && $is_assistant )
    ? $user : $login, $enddate, $cat_id );
}

if ( ! empty ( $cat_id ) )
  $monthURL = "month.php?cat_id=$cat_id&amp;";
else
  $monthURL = 'month.php?';

if ( empty ( $DISPLAY_TASKS ) ||  $DISPLAY_TASKS == 'N' && $DISPLAY_SM_MONTH != 'N') {
  $spacer = '<br />'; 
  display_small_month ( $prevmonth, $prevyear, true, true, 'prevmonth',
    $monthURL );
  display_small_month ( $nextmonth, $nextyear, true, true, 'nextmonth',
    $monthURL );

} else {
  $spacer = '';
  echo '<table border="0" width="100%" cellpadding="5"> ' .
   '<tr><td valign="top" width="80%" rowspan="2">';
}

display_navigation( 'month' );

display_month ( $thismonth, $thisyear );

if ( ! empty ( $DISPLAY_TASKS ) && $DISPLAY_TASKS == 'Y' && $friendly !=1 ) {
 echo '</td><td valign="top" align="center"><br />';
  display_small_month ( $prevmonth, $prevyear, true, false, 'prevmonth',
    $monthURL );
 echo '<br />';
  display_small_month ( $nextmonth, $nextyear, true, false, 'nextmonth',
    $monthURL );

?>
</td></tr><tr><td valign="bottom">

<?php 
    echo display_small_tasks ( $cat_id );

?>

</td></tr></table>
<?php }

if ( ! empty ( $eventinfo ) ) echo $eventinfo;
if ( empty ( $friendly ) ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );

  echo '<br />';
  echo generate_printer_friendly ( 'month.php' );
  print_trailer ();

}
?>
</body>
</html>

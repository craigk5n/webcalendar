<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin) {
  load_user_layers ($user);
} else {
  load_user_layers ();
}

load_user_categories ();

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
//$nextdate = date ( "Ymd" );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
//$prevdate = date ( "Ymd" );

if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );
  $enddate = sprintf ( "%04d%02d31", $nextyear, $nextmonth );
} else {
  $boldDays = false;
  $startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
  $enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );
}

$HeadX = '';
if ( $AUTO_REFRESH == "Y" && ! empty ( $AUTO_REFRESH_TIME ) ) {
  $refresh = $AUTO_REFRESH_TIME * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=month.php?$u_url" .
    "year=$thisyear&amp;month=$thismonth$caturl" . 
    ( ! empty ( $friendly ) ? "&amp;friendly=1" : "") . "\" />\n";
}
$INC =  array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id, $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == "Y" ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) && $is_assistant )
    ? $user : $login, $startdate, $enddate, $cat_id );
}

if ( ! empty ( $cat_id ) )
  $monthURL = "month.php?cat_id=$cat_id&amp;";
else
  $monthURL = 'month.php?';

if ( empty ( $DISPLAY_TASKS ) ||  $DISPLAY_TASKS == "N" ) {
  $spacer = "<br />"; 
  display_small_month ( $prevmonth, $prevyear, true, true, "prevmonth",
    $monthURL );
  display_small_month ( $nextmonth, $nextyear, true, true, "nextmonth",
    $monthURL );
} else {
  $spacer = "";
  echo "<table border=\"0\" width=\"100%\" cellpadding=\"5\"> " .
   "<tr><td valign=\"top\" width=\"80%\" rowspan=\"2\">";
}

?>
<div class="title">
<span class="date"><?php
  echo $spacer . date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );
?></span>
<span class="user"><?php
  if ( $single_user == "N" ) {
    echo "<br />\n";
    echo $user_fullname;
  }
  if ( $is_nonuser_admin ) {
    echo "<br />-- " . translate("Admin mode") . " --";
  }
  if ( $is_assistant ) {
    echo "<br />-- " . translate("Assistant mode") . " --";
  }
?></span>
<?php
  if ( $CATEGORIES_ENABLED == "Y" && (!$user || ($user == $login || $is_assistant ))) {
    echo "<br /><br />\n";
    print_category_menu('month',sprintf ( "%04d%02d01",$thisyear, $thismonth ),$cat_id );
  }
?>
</div>
<?php 
display_month ( $thismonth, $thisyear );

if ( ! empty ( $DISPLAY_TASKS ) && $DISPLAY_TASKS == "Y" && $friendly !=1 ) {
 echo "</td><td valign=\"top\"><br />";
  display_small_month ( $prevmonth, $prevyear, true, false, "prevmonth",
    $monthURL );
 echo "<br />";
  display_small_month ( $nextmonth, $nextyear, true, false, "nextmonth",
    $monthURL );
 echo "<br />";
?>
</td></tr><tr><td valign="bottom">

<?php 
    echo display_small_tasks ();
?>

</td></tr></table>
<?php }
 if ( ! empty ( $eventinfo ) ) echo $eventinfo;

 display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<br />
<a title="<?php etranslate("Generate printer-friendly version")?>" 
class="printer" href="month.php?<?php
   if ( $thisyear ) {
    echo "year=$thisyear&amp;month=$thismonth&amp;";
   }
   if ( ! empty ( $user ) ) {
     echo "user=$user&amp;";
   }
   if ( ! empty ( $cat_id ) ) {
     echo "cat_id=$cat_id&amp;";
   }
  ?>friendly=1" target="cal_printer_friendly" 
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")
  ?>'">[<?php etranslate("Printer Friendly")?>]</a>
<?php
 print_trailer ();
?>
</body>
</html>

<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
//$nextdate = date ( "Ymd" );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
//$prevdate = date ( "Ymd" );

$startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
$enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );

$HeadX = '';
if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=month.php?$u_url" .
    "date=$startdate$caturl\" target=\"_self\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );
?>

<table border="0" width="100%">
<tr>
<?php

if ( ! $friendly ) {
  echo "<td align=\"left\"><table class=\"minical\" cellspacing=\"1\" cellpadding=\"2\">";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $prevyear, $prevmonth, 1 );
  else
    $wkstart = get_sunday_before ( $prevyear, $prevmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $prevmonth, 1, $prevyear );
  $monthend = mktime ( 3, 0, 0, $prevmonth + 1, 0, $prevyear );
  echo "<tr><td colspan=\"7\" class=\"month\">" .
    "<a href=\"month.php?$u_url&amp;";
  $prevmonth_name = month_name ( $prevmonth );
  echo "year=$prevyear&amp;month=$prevmonth$caturl\">" .
    date_to_str ( sprintf ( "%04d%02d01", $prevyear, $prevmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</a></td></tr>\n";
  echo "<tr class=\"day\">";
  if ( $WEEK_START == 0 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<th>" .
      weekday_short_name ( $i ) . "</th>\n";
  }
  if ( $WEEK_START == 1 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  echo "</tr>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<tr>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<td class=\"date\"><a href=\"day.php?$u_url&amp;date=".date("Ymd", $date)."\">" . date ( "d", $date ) . "</a></td>\n";
      } else {
        print "<td>&nbsp;</td>\n";
      }
    }
    if ( isset ( $DISPLAY_WEEKNUMBER ) && $DISPLAY_WEEKNUMBER == 'Y' ) {
      print "<td><a href=\"week.php?$u_url&amp;date=".date("Ymd", $i)."\" class=\"weeknumber\">(" . week_number($i) . ")</a></td>\n";
    }
    print "</tr>\n";
  }
  echo "</table></td>\n";
}

?>

<td class="monthviewtitle">
<span class="date">
<?php
  echo date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );
?>
</span>
<span class="user">
<?php
  if ( $single_user == "N" ) {
    echo "<br />\n";
    echo $user_fullname;
  }
  if ( $is_nonuser_admin )
    echo "<br />-- " . translate("Admin mode") . " --";
  if ( $is_assistant )
    echo "<br />-- " . translate("Assistant mode") . " --";
?>
</span>
<?php
  if ( $categories_enabled == "Y" && (!$user || $user == $login)) {
    echo "<br />\n<br />\n";
    print_category_menu('month',sprintf ( "%04d%02d01",$thisyear, $thismonth ),$cat_id, $friendly );
  }
?>
</td>
<?php
if ( ! $friendly ) {
  echo "<td align=\"right\"><table class=\"minical\" cellspacing=\"1\" cellpadding=\"2\">";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $nextyear, $nextmonth, 1 );
  else
    $wkstart = get_sunday_before ( $nextyear, $nextmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $nextmonth, 1, $nextyear );
  $monthend = mktime ( 3, 0, 0, $nextmonth + 1, 0, $nextyear );
  echo "<tr><td colspan=\"7\" class=\"month\">" .
    "<a href=\"month.php?$u_url";
  echo "year=$nextyear&amp;month=$nextmonth$caturl\">" .
    date_to_str ( sprintf ( "%04d%02d01", $nextyear, $nextmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</a></td></tr>\n";
  echo "<tr class=\"day\">";
  if ( $WEEK_START == 0 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<th>" .
      weekday_short_name ( $i ) . "</th>\n";
  }
  if ( $WEEK_START == 1 ) echo "<th>" .
    weekday_short_name ( 0 ) . "</th>\n";
  echo "</tr>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<tr>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<td class=\"date\"><a href=\"day.php?$u_url&amp;date=".date("Ymd", $date)."\">" . date ( "d", $date ) . "</a></td>\n";
      } else {
        print "<td>&nbsp;</td>\n";
      }
    }
    if ( isset ( $DISPLAY_WEEKNUMBER ) && $DISPLAY_WEEKNUMBER == 'Y' ) {
      print "<td><a href=\"week.php?$u_url&amp;date=".date("Ymd", $i)."\" class=\"weeknumber\">(" . week_number($i) . ")</a></td>\n";
    }
    print "</tr>\n";
  }
  echo "</table></td>\n";
}

?>
</tr>
</table>
<br />

<table class="monthview" cellspacing="0" cellpadding="0">

<tr>
<?php if ( $WEEK_START == 0 ) { ?>
<th><?php etranslate("Sun")?></th>
<?php } ?>
<th><?php etranslate("Mon")?></th>
<th><?php etranslate("Tue")?></th>
<th><?php etranslate("Wed")?></th>
<th><?php etranslate("Thu")?></th>
<th><?php etranslate("Fri")?></th>
<th><?php etranslate("Sat")?></th>
<?php if ( $WEEK_START == 1 ) { ?>
<th><?php etranslate("Sun")?></th>
<?php } ?>
</tr>

<?php

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
//$sun = get_sunday_before ( $thisyear, $thismonth, 1 );
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
// generate values for first day and last day of month
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );

// debugging
//echo "<p>sun = " . date ( "D, m-d-Y", $sun ) . "</p>";
//echo "<p>monthstart = " . date ( "D, m-d-Y", $monthstart ) . "</p>";
//echo "<p>monthend = " . date ( "D, m-d-Y", $monthend ) . "</p>";

// NOTE: if you make HTML changes to this table, make the same changes
// to the example table in pref.php.
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  print "<tr>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      $thiswday = date ( "w", $date );
      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      if ( empty ( $WEEKENDBG ) ) $is_weekend = false;
      $class = $is_weekend ? "tablecellweekend" : "tablecell";
      //$color = $is_weekend ? $WEEKENDBG : $CELLBG;
      //if ( empty ( $color ) )
      //  $color = "#C0C0C0";
      print "<td valign=\"top\"";
      //print "style=\"height:75px;";
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        //echo "background-color:$TODAYCELLBG;\"";
         $class = 'tablecelltoday';
      //else
        //echo "background-color:$color;\"";
      echo " class=\"$class\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<br />";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login,
        $friendly, false );
      print "</td>\n";
    } else {
      print "<td valign=\"top\" class=\"tablecell\">&nbsp;</td>\n";
    }
  }
  print "</tr>\n";
}

?>

</table>

<br /><br />

<?php if ( empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<br /><br />
<a class="navlinks" href="month.php?<?php
  if ( $thisyear ) {
    echo "year=$thisyear&amp;month=$thismonth&amp;";
  }
  if ( ! empty ( $user ) ) echo "user=$user&amp;";
  if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&amp;";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php }

$today_month = date ( 'm', $today );
$today_year = date ( 'Y', $today );
?>
<a class="navlinks" href="month.php?<?php
	if ( $thisyear ) {
		echo "year=$today_year&amp;month=$today_month&amp;";
	}
	if ( ! empty ( $user ) ) echo "user=$user&amp;";
	if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&amp;";?>" onmouseover="window.status = '<?php etranslate("Jump to Today")?>';">[<?php etranslate("Today")?>]</a>

<?php
print_trailer ();
?>

</body>
</html>

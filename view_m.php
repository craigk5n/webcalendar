<?php
include_once 'includes/init.php';

$USERS_PER_TABLE = 6;

if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}

if ( empty ( $friendly ) || $friendly != "1" )
  $friendly = 0;

// Find view name in $views[]
$view_name = "";
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_view_id'] == $id ) {
    $view_name = $views[$i]['cal_name'];
  }
}

$INC = array('js/popups.php');
print_header($INC);

set_today($date);

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );

$startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
$enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );
$thisdate = $startdate;

?>

<table style="border-width:0px; width:100%;">
<tr><td style="text-align:left;">
<?php if ( ! $friendly ) { ?>
<a href="view_m.php?id=<?php echo $id?>&date=<?php echo $prevdate?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>
<?php } ?>
</td>
<td class="viewmtitle">
<span class="date">
<?php
  printf ( "%s %d", month_name ( $thismonth - 1 ), $thisyear );
?>
</span><br />
<span class="viewname">
<?php echo $view_name ?>
</span>
</td>
<td style="text-align:right;">
<?php if ( ! $friendly ) { ?>
<a href="view_m.php?id=<?php echo $id?>&date=<?php echo $nextdate?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
<?php } ?>
</td></tr>
</table>

<?php
// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done....
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$viewusers = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $viewusers[] = $row[0];
  }
  dbi_free_result ( $res );
}
$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i] );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}


for ( $j = 0; $j < count ( $viewusers ); $j += $USERS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = count ( $viewusers ) - $j;
  if ( $num_left > $USERS_PER_TABLE )
    $num_left = $USERS_PER_TABLE;
  if ( $num_left > 0 ) {
    if ( $num_left < $USERS_PER_TABLE ) {
      $tdw = (int) ( 90 / $num_left );
    } else {
      $tdw = (int) ( 90 / $USERS_PER_TABLE );
    }
  } else {
    $tdw = 5;
  }

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<table class="viewm" cellspacing="1" cellpadding="2">
<?php } else { // if printer-friendly, use table tag below ?>
<table style="border-width:1px; width:100%;" cellspacing="0" cellpadding="0">
<?php } ?>

<tr><th class="empty">&nbsp;</td>

<?php
  // $j points to start of this table/row
  // $k is counter starting at 0
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < count ( $viewusers ) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $user = $viewusers[$i];
    user_load_variables ( $user, "temp" );
    echo "<th style=\"width:$tdw%;\">$tempfullname</td>";
  }
  echo "</tr>\n";
  
  for ( $date = $monthstart; date ( "Ymd", $date ) <= date ( "Ymd", $monthend );
    $date += ( 24 * 3600 ), $wday++ ) {
    $wday = strftime ( "%w", $date );
    $weekday = weekday_short_name ( $wday );
    if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
      $color = $TODAYCELLBG;
      $class = "today";
    } else {
      if ( $wday == 0 || $wday == 6 )
        $color = $WEEKENDBG;
      else
        $color = $CELLBG;
      $class = "tableheader";
    }
    echo "<tr><th class=\"$class\" style=\"width:10%; vertical-align:top; font-size:13px;\">" .
      $weekday . " " .
      round ( date ( "d", $date ) ) . "</th>\n";
    for ( $i = $j, $k = 0;
      $i < count ( $viewusers ) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $user = $viewusers[$i];
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      echo "<td style=\"width:$tdw%; background-color:$color;\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<br />";
      if ( empty ( $add_link_in_views ) || $add_link_in_views != "N" &&
        empty ( $friendly ) )
        echo html_for_add_icon ( date ( "Ymd", $date ), "", "", $user );
      print_date_entries ( date ( "Ymd", $date ),
        $user, $friendly, true );
      echo "</td>";
    }
    echo "</tr>\n";
  }

  if ( empty ( $friendly ) || ! $friendly )
    echo "</table>\n<br /><br />\n";
  else
    echo "</table>\n<br /><br />\n";
  
}


$user = ""; // reset

if ( empty ( $friendly ) )
  echo $eventinfo;

if ( ! $friendly )
  echo "<a class=\"navlinks\" href=\"view_m.php?id=$id&date=$thisdate&friendly=1\" " .
    "target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
    translate("Generate printer-friendly version") .
    "'\">[" . translate("Printer Friendly") . "]</a>\n";

print_trailer ();
?>
</body>
</html>
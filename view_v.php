<?php
include_once 'includes/init.php';

$DAYS_PER_TABLE = 7;

if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}

if ( empty ( $friendly ) )
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

$next = mktime ( 3, 0, 0, $thismonth, $thisday + 7, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime ( 3, 0, 0, $thismonth, $thisday - 7, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
$wkend = $wkstart + ( 3600 * 24 * 6 );
$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

$thisdate = $startdate;

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<br />" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}

?>

<div style="border-width:0px; width:99%;">
<?php if ( ! $friendly ) { ?>
<div style="float:left;">
<br />
<a title="<?php etranslate("Previous")?>" href="view_v.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>
</div>

<div style="float:right;">
<br />
<a title="<?php etranslate("Next")?>" href="view_v.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
</div>
<?php } ?>
<div class="title">
<span class="date"><?php
  echo date_to_str ( date ( "Ymd", $wkstart ), false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), false );
?></span><br />
<span class="viewname"><?php echo $view_name ?></span>
</div></div><br />

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
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $startdate );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}


for ( $j = 0; $j < 7; $j += $DAYS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  $tdw = 12; // column width percent

?>

<table cellspacing="0" cellpadding="0">
<tr><th class="empty">&nbsp;</th>
<?php
  for ( $date = $wkstart, $h = 0;
    date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
    $date += ( 24 * 3600 ), $h++ ) {
	$wday = strftime ( "%w", $date );
	$weekday = weekday_short_name ( $wday );
	if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
//	$color = $TODAYCELLBG;
//      $class = "tableheadertoday";
		echo "<th class=\"today\" style=\"width:$tdw%;\">";
	} else {
//      if ( $wday == 0 || $wday == 6 ) {
//        $color = $WEEKENDBG;
//	echo "<th class=\"weekend\" style=\"width:$tdw%;\">";
//      } else {
//        $color = $CELLBG;
//       $class = "tableheader";
		echo "<th style=\"width:$tdw%;\">";
//	}
      }
//    echo "<th class=\"$class\" style=\"width:$tdw%; background-color:$color; vertical-align:top; font-size:13px;\">" .
      echo $weekday . " " .
      round ( date ( "d", $date ) ) . "</th>\n";
  }
  echo "</tr>\n";
  
      // ------
    for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
    echo "\n<tr>\n";
      // ------
    $user = $viewusers[$i];
    user_load_variables ( $user, "temp" );
//    echo "<th class=\"tableheader\" style=\"width:$tdw%;\">$tempfullname</th>";
    echo "<th class=\"row\" style=\"width:$tdw%;\">$tempfullname</th>";

      // ------
  for ( $date = $wkstart, $h = 0;
    date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
    $date += ( 24 * 3600 ), $h++ ) {
      // ------
//    if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
//      $color = $TODAYCELLBG;
//      $class = "tableheadertoday";
//      echo "<td class=\"today\" style=\"width:$tdw%;\">";
//    } else {
      if ( $wday == 0 || $wday == 6 ) {
//        $color = $WEEKENDBG;
	echo "<td class=\"weekend\" style=\"width:$tdw%;\">";
      } else {
//        $color = $CELLBG;
//      $class = "tablecell";
	echo "<td style=\"width:$tdw%;\">";
      }
//    }
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
//      echo "<td style=\"width:$tdw%; background-color:$color; border-bottom: 1px solid $TABLEBG; border-right: 1px solid $TABLEBG;\" valign=\"top\">";
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
  echo "</table>\n<br /><br />\n";
}


$user = ""; // reset

echo $eventinfo;

if ( ! $friendly )
  echo "<a title=\"" . translate("Generate printer-friendly version") . "\" class=\"printer\" href=\"view_v.php?id=$id&amp;date=$thisdate&amp;friendly=1\" " .
    "target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
    translate("Generate printer-friendly version") .
    "'\">[" . translate("Printer Friendly") . "]</a>\n";


print_trailer ();
?>

</body>
</html>

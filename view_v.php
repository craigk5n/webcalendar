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

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<BR>" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}

?>

<TABLE BORDER="0" WIDTH="100%">
<TR><TD ALIGN="left">
<?php if ( ! $friendly ) { ?>
<A HREF="view_v.php?id=<?php echo $id?>&date=<?php echo $prevdate?>"><IMG SRC="leftarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Previous")?>"></A>
<?php } ?>
</TD>
<TD ALIGN="middle">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>">
<B>
<?php
  echo date_to_str ( date ( "Ymd", $wkstart ), false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), false );
?>
</B></FONT><BR>
<FONT COLOR="<?php echo $H2COLOR?>"><?php echo $view_name ?></FONT>
</TD>
<TD ALIGN="right">
<?php if ( ! $friendly ) { ?>
<A HREF="view_v.php?id=<?php echo $id?>&date=<?php echo $nextdate?>"><IMG SRC="rightarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Next")?>"></A>
<?php } ?>
</TD></TR>
</TABLE>

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


for ( $j = 0; $j < 7; $j += $DAYS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  $tdw = 12; // column width percent

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">
<?php } else { ?>
<TABLE BORDER="1" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<?php } ?>

<TR><TD WIDTH="10%" BGCOLOR="<?php echo $THBG?>">&nbsp;</TD>

<?php

  for ( $date = $wkstart, $h = 0;
    date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
    $date += ( 24 * 3600 ), $h++ ) {
    $wday = strftime ( "%w", $date );
    $weekday = weekday_short_name ( $wday );
    if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
      $color = $TODAYCELLBG;
      $class = "tableheadertoday";
    } else {
      if ( $wday == 0 || $wday == 6 )
        $color = $WEEKENDBG;
      else
        $color = $CELLBG;
      $class = "tableheader";
    }
    echo "<TH CLASS=\"$class\" WIDTH=\"$tdw%\" BGCOLOR=\"$color\" VALIGN=\"top\">" .
      "<FONT SIZE=\"-1\" CLASS=\"$class\">" . $weekday . " " .
      round ( date ( "d", $date ) ) . "</FONT></TH>\n";
  }
  echo "</TR>\n";
  
  
      // ------
    for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
    echo "\n<tr>\n";
      // ------
    $user = $viewusers[$i];
    user_load_variables ( $user, "temp" );
    echo "<TH CLASS=\"tableheader\" WIDTH=\"$tdw%\" BGCOLOR=\"$THBG\">$tempfullname</TH>";

      // ------
  for ( $date = $wkstart, $h = 0;
    date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
    $date += ( 24 * 3600 ), $h++ ) {
      // ------
    if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
      $color = $TODAYCELLBG;
      $class = "tableheadertoday";
    } else {
      if ( $wday == 0 || $wday == 6 )
        $color = $WEEKENDBG;
      else
        $color = $CELLBG;
      $class = "tablecell";
    }
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      echo "<TD WIDTH=\"$tdw%\" BGCOLOR=\"$color\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<BR>";
      if ( empty ( $add_link_in_views ) || $add_link_in_views != "N" &&
        empty ( $friendly ) )
        echo html_for_add_icon ( date ( "Ymd", $date ), "", "", $user );
      print_date_entries ( date ( "Ymd", $date ),
        $user, $friendly, true );
      echo "</TD>";
    }
    echo "</TR>\n";
  }

  if ( empty ( $friendly ) || ! $friendly )
    echo "</TD></TR></TABLE>\n</TABLE>\n<P>\n";
  else
    echo "</TABLE>\n<P>\n";
}


$user = ""; // reset

echo $eventinfo;

if ( ! $friendly )
  echo "<A CLASS=\"navlinks\" HREF=\"view_v.php?id=$id&date=$thisdate&friendly=1\" " .
    "TARGET=\"cal_printer_friendly\" onMouseOver=\"window.status='" .
    translate("Generate printer-friendly version") .
    "'\">[" . translate("Printer Friendly") . "]</A>\n";


print_trailer ();
?>

</BODY>
</HTML>

<?php
include_once 'includes/init.php';

$USERS_PER_TABLE = 6;

if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}

// Find view name in $views[]
$view_name = "";
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_view_id'] == $id ) {
    $view_name = $views[$i]['cal_name'];
  }
}

$INC = array('js/popups.php');
print_header($INC);

// Initialise la date au premier du mois en cours
if ( $timeb == 0 )
   $date = substr($date,0,6)."01";

set_today($date);

// Week timebar
if ( $timeb == 1 )
   $next = mktime ( 3, 0, 0, $thismonth, $thisday + 7, $thisyear );
else
   $next = mktime ( 3, 0, 0, $thismonth + 1, $thisday, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

if ( $timeb == 1 )
   $prev = mktime ( 3, 0, 0, $thismonth, $thisday - 7, $thisyear );
else
   $prev = mktime ( 3, 0, 0, $thismonth - 1, $thisday, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $timeb == 1 ) {
   if ( $WEEK_START == 1 )
      $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
   else
      $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
}
else
   $wkstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );    // Debut

if ( $timeb == 1 )
   $wkend = $wkstart + ( 3600 * 24 * 6 );
else
   $wkend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );    // Fin (1 mois plus tard)

$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

$thisdate = $startdate;

if ( $timeb == 1 )
   $val_boucle = 7;
else
   $val_boucle = date("t", $wkstart);

for ( $i = 0; $i < $val_boucle; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % $val_boucle );
  $header[$i] = $weekdays[$i] . "<br />\n" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}
?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" href="view_t.php?timeb=<?php echo $timeb?>&amp;id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="leftarrow.gif" alt="<?php etranslate("Previous")?>" /></a>

<a title="<?php etranslate("Next")?>" class="next" href="view_t.php?timeb=<?php echo $timeb?>&amp;id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="rightarrow.gif" alt="<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php
  echo date_to_str ( date ( "Ymd", $wkstart ), false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), false );
?></span><br />
<span class="viewname"><?php 
	echo $view_name 
?></span>
</div>
</div><br /><br />

<?php
// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done..
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
  $re_save = $re_save + $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = $e_save + $events;
}
$events = $e_save;
$repeated_events = $re_save;
?>

<table class="viewt">
<?php
for ( $date = $wkstart, $h = 0;
  date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
  $date += ( 24 * 3600 ), $h++ ) {
  $wday = strftime ( "%w", $date );
  $weekday = weekday_short_name ( $wday );
  if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
//    $color = $TODAYCELLBG;
//    $class = "tableheadertoday";
	echo "<tr><th class=\"today\">";
  } else {
//    if ( $wday == 0 || $wday == 6 )
//      $color = $WEEKENDBG;
//    else
//      $color = $CELLBG;
//    $class = "tableheader";
	echo "<tr><th class=\"row\">";
  }
//  echo "<tr><th class=\"$class\" style=\"width:10%;  vertical-align:top;\">";
	echo $weekday . "&nbsp;" . round ( date ( "d", $date ) ) . "</th>\n";

  //start the container cell for each day, with its appropriate style
  if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
	echo "<td class=\"today\">";
  } else {
	if ( $wday == 0 || $wday == 6 )
		echo "<td class=\"weekend\">";
	else
		echo "<td class=\"reg\">";
  }
//  echo "<td style=\"width:90%; background-color:$color;\">";
  if ( empty ( $add_link_in_views ) || $add_link_in_views != "N" )
    echo html_for_add_icon ( date ( "Ymd", $date ), "", "", $user );

  // Parametres par defaut
  if ($prefarray["WORK_DAY_START_HOUR"]==NULL || $prefarray["WORK_DAY_END_HOUR"]==NULL) {
     $val = dbi_fetch_row ( dbi_query ( "SELECT cal_value FROM webcal_config where cal_setting=\"WORK_DAY_START_HOUR\"" ));
     $prefarray["WORK_DAY_START_HOUR"]=$val[0];
     $val = dbi_fetch_row ( dbi_query ( "SELECT cal_value FROM webcal_config where cal_setting=\"WORK_DAY_END_HOUR\"" ));
     $prefarray["WORK_DAY_END_HOUR"]=$val[0];
  }
    
  print_header_timebar($prefarray["WORK_DAY_START_HOUR"], $prefarray["WORK_DAY_END_HOUR"]);
  print_date_entries_timebar ( date ( "Ymd", $date ), $GLOBALS["login"], true );
  echo "</td>";
  echo "</tr>\n";
}

echo "</table>\n<br />\n";

$user = ""; // reset

echo $eventinfo;

echo "<a title=\"" . translate("Generate printer-friendly version") . "\" class=\"printer\" href=\"view_t.php?timeb=$timeb&amp;id=$id&amp;date=$thisdate&amp;friendly=1\" target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
	translate("Generate printer-friendly version") .
	"'\">[" . translate("Printer Friendly") . "]</a>\n";

print_trailer ();
?>
</body>
</html>

<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will display a timebar for a week or month as
 * specified by timeb
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * timeb - 1 = week, else month
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = "";
$USERS_PER_TABLE = 6;

view_init ( $id );

$INC = array('js/popups.php/false');
print_header($INC);


set_today($date);

// Initialize date to first of current month
if ( empty ( $timeb ) || $timeb == 0 ) {
  $date = substr($date,0,6)."01";
}



// Week timebar
if ( ! empty ( $timeb) && $timeb == 1 ) {
  $next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
} else {
  $next = mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear );
}
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

if ( ! empty ( $timeb) && $timeb == 1 ) {
  $prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
} else {
  $prev = mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear );
}
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

if ( ! empty ( $timeb) && $timeb == 1 ) {
  $wkstart = get_weekday_before ( $thisyear, $thismonth );
} else {
  $wkstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
}

if ( ! empty ( $timeb) && $timeb == 1 ) {
  $wkend = $wkstart + ( ONE_DAY * 6 );
} else {
  $wkend = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );
}
$thisdate = date ( "Ymd", $wkstart );


if ( ! empty ( $timeb) && $timeb == 1 ) {
  $val_boucle = 7;
} else {
  $val_boucle = date("t", $wkstart);
}
for ( $i = 0; $i < $val_boucle; $i++ ) {
  $days[$i] = $wkstart + ONE_DAY * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % $val_boucle );
  $header[$i] = $weekdays[$i] . "<br />\n" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}

// get users in this view
$viewusers = view_get_user_list ( $id );
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( "No users for this view" );
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" href="view_t.php?timeb=
<?php echo $timeb?>&amp;id=<?php echo $id?>&amp;date=
<?php echo $prevdate?>"><img src="images/leftarrow.gif" alt="
<?php etranslate("Previous")?>" /></a>

<a title="<?php etranslate("Next")?>" class="next" href="view_t.php?timeb=
<?php echo $timeb?>&amp;id=<?php echo $id?>&amp;date=
<?php echo $nextdate?>"><img src="images/rightarrow.gif" alt="
<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php
  echo date_to_str ( date ( "Ymd", $wkstart ), "", false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), "", false );
?></span><br />
<span class="viewname"><?php 
 echo htmlspecialchars ( $view_name  );
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


$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $wkstart );
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access 
      subtracting ONE_WEEK to allow cross-day events to display*/
  $events = read_events ( $viewusers[$i], $wkstart - ONE_WEEK, $wkend );
  $e_save = array_merge($e_save, $events);
}
$events = $e_save;
$repeated_events = $re_save;
?>

<table class="viewt">
<?php
for ( $date = $wkstart, $h = 0;
  date ( "Ymd", $date ) <= date ( "Ymd", $wkend );
  $date += ONE_DAY, $h++ ) {
  $wday = strftime ( "%w", $date );
  if ( ( $wday == 0 || $wday == 6 ) && $DISPLAY_WEEKENDS == "N" ) continue; 
  $weekday = weekday_short_name ( $wday );
  if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
    echo "<tr><th class=\"today\">";
  } else {
    echo "<tr><th class=\"row\">";
  }
  if ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != "N" )  {
    echo html_for_add_icon ( date ( "Ymd", $date ), "", "", $user );
  }
  echo $weekday . "&nbsp;" . round ( date ( "d", $date ) ) . "</th>\n";

  //start the container cell for each day, with its appropriate style
  if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) ) {
    echo "<td class=\"today\">";
  } else {
    if ( $wday == 0 || $wday == 6 ) {
      echo "<td class=\"weekend\">";
    } else {
      echo "<td class=\"reg\">";
    }
  }

  // Default settings
  if ( ! isset ($prefarray["WORK_DAY_START_HOUR"] ) || 
    ! isset ( $prefarray["WORK_DAY_END_HOUR"] ) ) {
     $val = dbi_fetch_row ( dbi_execute ( "SELECT cal_value FROM webcal_config 
     where cal_setting='WORK_DAY_START_HOUR'" ));
     $prefarray["WORK_DAY_START_HOUR"]=$val[0];
     $val = dbi_fetch_row ( dbi_execute ( "SELECT cal_value FROM webcal_config 
     where cal_setting='WORK_DAY_END_HOUR'" ));
     $prefarray["WORK_DAY_END_HOUR"]=$val[0];
  }
    
  print_header_timebar($prefarray["WORK_DAY_START_HOUR"], 
    $prefarray["WORK_DAY_END_HOUR"]);
  print_date_entries_timebar ( date ( "Ymd", $date ), $GLOBALS["login"], true );
  echo "</td>";
  echo "</tr>\n";
}

echo "</table>\n<br />\n";

$user = ""; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo "<a title=\"" . translate("Generate printer-friendly version") . "\" " .
  "class=\"printer\" href=\"view_t.php?timeb=$timeb&amp;id=$id&amp;date=" .
  "$thisdate&amp;friendly=1\" target=\"cal_printer_friendly\" " .
  "onmouseover=\"window.status='" .
  translate("Generate printer-friendly version") .
  "'\">[" . translate("Printer Friendly") . "]</a>\n";

print_trailer ();
?>
</body>
</html>

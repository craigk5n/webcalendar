<?php

/*
 * $Id$
 *
 * Page Description:
 *	This page will display the month "view" with all users's events
 *	on the same calendar.  (The other month "view" displays each user
 *	calendar in a separate column, side-by-side.)  This view gives you
 *	the same effect as enabling layers, but with layers you can only
 *	have one configuration of users.
 *
 * Input Parameters:
 *	id (*) - specify view id in webcal_view table
 *	date - specify the starting date of the view.
 *	  If not specified, current date will be used.
 *	friendly - if set to 1, then page does not include links or
 *	  trailer navigation.
 *	(*) required field
 *
 * Security:
 *	Must have "allow view others" enabled ($allow_view_other) in
 *	  System Settings unless the user is an admin user ($is_admin).
 *	Must be owner of the view.
 *
 */


include_once 'includes/init.php';

$error = "";

if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}
if ( empty ( $id ) ) {
  do_redirect ( "views.php" );
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

// If view_name not found, then the specified view id does not
// belong to current user. 
if ( empty ( $view_name ) ) {
  $error = translate ( "You are not authorized" );
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

// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$viewusers = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $viewusers[] = $row[0]; 
  }
  dbi_free_result ( $res );
} else {
  $error = translate ( "Database error" ) . ": " . dbi_error ();
}
if ( count ( $viewusers ) == 0 ) {
  // no need to translate the following since it should not happen
  // unless the db gets screwed up.
  $error = "No users for this view";
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i] ); 
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge($e_save, $events);
} 
$events = array ();
$repeated_events = array ();

for ( $i = 0; $i < count ( $e_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $events ) && $should_add; $j++ ) {
    if ( $e_save[$i]['cal_id'] == $events[$j]['cal_id'] ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $events, $e_save[$i] );
  }
}

for ( $i = 0; $i < count ( $re_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $repeated_events ) && $should_add; $j++ ) {
    if ( $re_save[$i]['cal_id'] == $repeated_events[$j]['cal_id'] ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $repeated_events, $re_save[$i] );
  }
}
?>

<table style="border-width:0px; width:100%;">
<tr>
<?php
if ( ! $friendly ) {
  echo '<td style=\"text-align:left;\"><table style=\"border-width:0px;\">';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $prevyear, $prevmonth, 1 );
  else
    $wkstart = get_sunday_before ( $prevyear, $prevmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $prevmonth, 1, $prevyear );
  $monthend = mktime ( 3, 0, 0, $prevmonth + 1, 0, $prevyear );
  echo "<tr><td colspan=\"7\" style=\"text-align:center; font-size:13px;\">" .
    "<a href=\"view_l.php?id=$id&date=$prevdate\" class=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $prevyear, $prevmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</a></td></tr>\n";
  echo "<tr>";
  if ( $WEEK_START == 0 ) echo "<td style=\"font-size:10px;\">" .
    weekday_short_name ( 0 ) . "</td>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<td style=\"font-size:10px;\">" .
      weekday_short_name ( $i ) . "</td>";
  }
  if ( $WEEK_START == 1 ) echo "<td style=\"font-size:10px;\">" .
    weekday_short_name ( 0 ) . "</td>";
  echo "</tr>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<tr>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<td style=\"font-size:10px;\">" . date ( "d", $date ) . "</td>\n";
      } else {
        print "<td>&nbsp;</td>\n";
      }
    }
    print "</tr>\n";
  }
  echo "</table></td>\n";
}

?>
<td style="text-align:center; color:<?php echo $H2COLOR?>;">
<span style="font-weight:bold; font-size:10px;">
<?php
  echo date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );
?>
</span>
<span style="font-size:18px;">
<?php
    echo "<br />\n";
    echo $view_name;
?>
</span></td>
<?php
if ( ! $friendly ) {
  echo '<td style=\"text-align:right;\"><table style=\"border-width:0px;\">';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $nextyear, $nextmonth, 1 );
  else
    $wkstart = get_sunday_before ( $nextyear, $nextmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $nextmonth, 1, $nextyear );
  $monthend = mktime ( 3, 0, 0, $nextmonth + 1, 0, $nextyear );
  echo "<tr><td colspan=\"7\" style=\"text-align:center; font-size:13px;\">" .
    "<a href=\"view_l.php?id=$id&date=$nextdate\" class=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $nextyear, $nextmonth ),
    $DATE_FORMAT_MY, false, false ) .
    "</a></td></tr>\n";
  echo "<tr>";
  if ( $WEEK_START == 0 ) echo "<td style=\"font-size:10px;\">" .
    weekday_short_name ( 0 ) . "</td>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<td style=\"font-size:10px;\">" .
      weekday_short_name ( $i ) . "</td>";
  }
  if ( $WEEK_START == 1 ) echo "<td style=\"font-size:10px;\">" .
    weekday_short_name ( 0 ) . "</td>";
  echo "</tr>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<tr>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<td style=\"font-size:10px;\">" . date ( "d", $date ) . "</td>\n";
      } else {
        print "<td>&nbsp;</td>\n";
      }
    }
    print "</tr>\n";
  }
  echo "</table></td>\n";
}

?>
</tr>
</table>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<table style="border-width:0px; width:100%;" cellspacing="0" cellpadding="0">
<tr><td style="background-color:<?php echo $TABLEBG?>;">
<table style="border-width:0px; width:100%;" cellspacing="1" cellpadding="2">
<?php } else { ?>
<table style="border-width:1px; width:100%;" cellspacing="0" cellpadding="0">
<?php } ?>

<tr>
<?php if ( $WEEK_START == 0 ) { ?>
<th style="width:14%;" class="tableheader"><?php etranslate("Sun")?></th>
<?php } ?>
<th style="width:14%;" class="tableheader"><?php etranslate("Mon")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Tue")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Wed")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Thu")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Fri")?></th>
<th style="width:14%;" class="tableheader"><?php etranslate("Sat")?></th>
<?php if ( $WEEK_START == 1 ) { ?>
<th style="width:14%;" class="tableheader"><?php etranslate("Sun")?></th>
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
//echo "<br /><br />sun = " . date ( "D, m-d-Y", $sun ) . "<br />";
//echo "<br /><br />monthstart = " . date ( "D, m-d-Y", $monthstart ) . "<br />";
//echo "<br /><br />monthend = " . date ( "D, m-d-Y", $monthend ) . "<br />";

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
      $color = $is_weekend ? $WEEKENDBG : $CELLBG;
      if ( empty ( $color ) )
        $color = "#C0C0C0";
      print "<td id=\"$class\" style=\"vertical-align:top; height:75px;"; 
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo " background-color:$TODAYCELLBG;\">";
      else
        echo " background-color:$color;\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<BR>";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login,
        $friendly, false );
      print "</td>\n";
    } else {
      print "<td style=\"vertical-align:top; height:75px; background-color:$CELLBG;\" id=\"tablecell\">&nbsp;</td>\n";
    }
  }
  print "</tr>\n";
}

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
</table>
</td></tr></table>
<?php } else { ?>
</table>
<?php } ?>

<br /><br />

<?php if ( empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<br /><br />
<a class="navlinks" href="view_l.php?id=<?php echo $id?>&<?php
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&";
  }
  if ( ! empty ( $user ) ) echo "user=$user&";
  if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php
      }
print_trailer ();?>

</body>
</html>

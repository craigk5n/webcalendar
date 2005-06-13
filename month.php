<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin) {
  load_user_layers ($user);
} else {
  load_user_layers ();
}

load_user_categories ();

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
//$nextdate = date ( "Ymd" );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
//$prevdate = date ( "Ymd" );

if ( ! empty ( $bold_days_in_year ) && $bold_days_in_year == 'Y' ) {
  $boldDays = true;
  $startdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );
  $enddate = sprintf ( "%04d%02d31", $nextyear, $nextmonth );
} else {
  $boldDays = false;
  $startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
  $enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );
}

$HeadX = '';
if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=month.php?$u_url" .
    "year=$thisyear&amp;month=$thismonth$caturl" . 
    ( ! empty ( $friendly ) ? "&amp;friendly=1" : "") . "\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quicker access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id, $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

if ( ! empty ( $cat_id ) )
  $monthURL = "month.php?cat_id=$cat_id&amp;";
else
  $monthURL = 'month.php?';
display_small_month ( $prevmonth, $prevyear, true, true, "prevmonth",
  $monthURL );
display_small_month ( $nextmonth, $nextyear, true, true, "nextmonth",
  $monthURL );
?>
<div class="title">
<span class="date"><br /><?php
  echo date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
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
  if ( $categories_enabled == "Y" && (!$user || ($user == $login || $is_assistant ))) {
    echo "<br /><br />\n";
    print_category_menu('month',sprintf ( "%04d%02d01",$thisyear, $thismonth ),$cat_id );
  }
?>
</div>

<table class="main" style="clear:both;" cellspacing="0" cellpadding="0">
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
if ( $WEEK_START == 1 ) {
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
} else {
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
}
// generate values for first day and last day of month
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );

// debugging
//echo "<p>sun = " . date ( "D, m-d-Y", $sun ) . "</p>\n";
//echo "<p>monthstart = " . date ( "D, m-d-Y", $monthstart ) . "</p>\n";
//echo "<p>monthend = " . date ( "D, m-d-Y", $monthend ) . "</p>\n";

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
      if ( empty ( $WEEKENDBG ) ) {
        $is_weekend = false;
      }
      print "<td";
      $class = "";
      if ( date ( "Ymd", $date  ) == date ( "Ymd", $today ) ) {
        $class = "today";
      }
      if ( $is_weekend ) {
        if ( strlen ( $class ) ) {
          $class .= " ";
        }
        $class .= "weekend";
      }
      if ( strlen ( $class ) )  {
      echo " class=\"$class\"";
      }
      echo ">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<br />";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login, false );
      print "</td>\n";
    } else {
      print "<td>&nbsp;</td>\n";
    }
  }
  print "</tr>\n";
}
?></table>
<br />
<?php
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

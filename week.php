<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

$view = "week";

include "includes/translate.inc";

if ( ! $allow_view_other && ! $is_admin )
  $user = "";

if ( strlen ( $user ) ) {
  $u_url = "user=$user&";
  user_load_variables ( $user, "user_" );
} else {
  $u_url = "";
  $user_fullname = $fullname;
}

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
<?php include "includes/js.inc"; ?>
</HEAD>
<BODY BGCOLOR=<?php echo "\"$BGCOLOR\"";?>>

<?php
if ( strlen ( $date ) > 0 ) {
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
} else {
  if ( $month == 0 )
    $thismonth = date("m");
  else
    $thismonth = $month;
  if ( $year == 0 )
    $thisyear = date("Y");
  else
    $thisyear = $year;
  if ( $day == 0 )
    $thisday = date("d");
  else
    $thisday = $day;
}

$next = mktime ( 2, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 2, 0, 0, $thismonth, $thisday - 7, $thisyear );

$today = mktime ( 2, 0, 0, date ( "m" ), date ( "d" ), date ( "Y" ) );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
$wkend = $wkstart + ( 3600 * 24 * 6 );
$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login,
  $startdate, $enddate );

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<BR>" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}

?>

<TABLE BORDER="0" WIDTH="100%">
<TR>
<?php if ( ! $friendly ) { ?>
<TD ALIGN="left"><A HREF="week.php?<?php echo $u_url; ?>date=<?php echo date("Ymd", $prev );?>">&lt;&lt;</A></TD>
<?php } ?>
<TD ALIGN="middle"><FONT SIZE="+2" COLOR="<?php echo $H2COLOR;?>"><B>
<?php
  if ( date ( "m", $wkstart ) == date ( "m", $wkend ) ) {
    printf ( "%s %d - %d, %d", month_name ( $thismonth - 1 ),
      date ( "d", $wkstart ), date ( "d", $wkend ), $thisyear );
  } else {
    if ( date ( "Y", $wkstart ) == date ( "Y", $wkend ) ) {
      printf ( "%s %d - %s %d, %d",
        month_name ( date ( "m", $wkstart ) - 1 ), date ( "d", $wkstart ),
        month_name ( date ( "m", $wkend ) - 1 ), date ( "d", $wkend ),
        $thisyear );
    } else {
      printf ( "%s %d, %d - %s %d, %d",
        month_name ( date ( "m", $wkstart ) - 1 ), date ( "d", $wkstart ),
        date ( "Y", $wkstart ),
        month_name ( date ( "m", $wkend ) - 1 ), date ( "d", $wkend ),
        date ( "Y", $wkend ) );
    }
  }
?>
</B></FONT>
<?php
if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" ) {
  echo "<BR>\n<FONT SIZE=\"-2\" COLOR=\"$H2COLOR\">(" .
    translate("Week") . " " . week_number ( $wkstart ) . ")</FONT>";
}
?>
<FONT SIZE="+1" COLOR="<?php echo $H2COLOR;?>">
<?php
  if ( ! strlen ( $single_user_login ) ) {
    echo "<BR>$user_fullname\n";
  }
?>
</FONT>
</TD>
<?php if ( ! $friendly ) { ?>
<TD ALIGN="right"><A HREF="week.php?<?php echo $u_url;?>date=<?php echo date ("Ymd", $next );?>">&gt;&gt;</A></TD>
<?php } ?>
</TR>
</TABLE>

<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">

<TR>
<TH WIDTH=12% BGCOLOR="<?php echo $THBG?>">&nbsp;</TH>
<?php
for ( $d = 0; $d < 7; $d++ ) {
  if ( date ( "Ymd", $days[$d] ) == date ( "Ymd", $today ) )
    $color = $TODAYCELLBG;
  else
    $color = $THBG;
  echo "<TH WIDTH=\"13%\" BGCOLOR=\"$color\">";
  if ( ! $friendly && ! $readonly ) {
    echo "<A HREF=\"edit_entry.php?" . $u_url .
      "date=" . date ( "Ymd", $days[$d] ) . "\">" .
      "<IMG SRC=\"new.gif\" WIDTH=\"10\" HEIGHT=\"10\" ALT=\"" .
      translate("New Entry") . "\" BORDER=\"0\" ALIGN=\"right\">" .  "</A>";
  }
  echo "<A HREF=\"day.php?" . $u_url .
    "date=" . date ("Ymd", $days[$d] ) . "\" CLASS=\"dayofmonthweekview\">" .
    $header[$d] . "</A></TH>\n";
}
?>
</TR>

<?php

$first_hour = $WORK_DAY_START_HOUR;
$last_hour = $WORK_DAY_END_HOUR;
$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
 // get all the repeating events for this date and store in array $rep
  $date = date ( "Ymd", $days[$d] );
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // Get static non-repeating events
  $ev = get_entries ( $user, $date );
  $hour_arr = Array ();
  $rowspan_arr = Array ();
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
        $rep[$cur_rep]['cal_status'] == 'A' )
        html_for_event_week_at_a_glance ( $rep[$cur_rep]['cal_id'],
          $date, $rep[$cur_rep]['cal_time'],
          $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_login'], $hide_icons );
      $cur_rep++;
    }
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $ev[$i]['cal_status'] == 'A' )
      html_for_event_week_at_a_glance ( $ev[$i]['cal_id'],
        $date, $ev[$i]['cal_time'],
        $ev[$i]['cal_name'], $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_login'], $hide_icons );
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $rep[$cur_rep]['cal_status'] == 'A' )
      html_for_event_week_at_a_glance ( $rep[$cur_rep]['cal_id'],
        $date, $rep[$cur_rep]['cal_time'],
        $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_login'], $hide_icons );
    $cur_rep++;
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  for ( $i = 0; $i < 24; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( strlen ( $hour_arr[$i] ) ) {
        if ( $rowspan_arr[$i] > 1 ) {
          $rowspan_arr[$last_row] += ( $rowspan_arr[$i] - 1 );
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else
          $rowspan_arr[$last_row] += $rowspan_arr[$i];
        // this will move entries apart that appear in one field, yet start on different hours
        $start_time = $i;
        $diff_start_time = $start_time - $last_row;
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) 
          $hour_arr[$last_row] .= "<BR>"; 
        $hour_arr[$last_row] .= $hour_arr[$i];
        $hour_arr[$i] = "";
        $rowspan_arr[$i] = 0;
      }
      $rowspan--;
    } else if ( $rowspan_arr[$i] > 1 ) {
      $rowspan = $rowspan_arr[$i];
      $last_row = $i;
    }
  }

  // now save the output...
  if ( strlen ( $hour_arr[99] ) ) {
    $untimed[$d] = "<TD WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\">$hour_arr[99]</TD>\n";
    $untimed_found = true;
  }
  $save_hour_arr[$d] = $hour_arr;
  $save_rowspan_arr[$d] = $rowspan_arr;
}


// untimed events first
if ( $untimed_found ) {
  echo "<TR><TD WIDTH=\"12%\" BGCOLOR=\"$THBG\">&nbsp;</TD>";
  for ( $d = 0; $d < 7; $d++ ) {
    if ( strlen ( $untimed[$d] ) )
      echo $untimed[$d];
    else
      echo "<TD WIDTH=\"12%\" BGCOLOR=\"$CELLBG\">&nbsp;</TD>";
  }
  echo "</TR>\n";
}

for ( $d = 0; $d < 7; $d++ )
  $rowspan_day[$d] = 0;

for ( $i = $first_hour; $i <= $last_hour; $i++ ) {
  $time = display_time ( $i * 10000 );
  echo "<TR><TH VALIGN=\"top\" WIDTH=\"13%\" BGCOLOR=\"$THBG\" HEIGHT=\"40\">" .
    "<FONT COLOR=\"$THFG\">" .  $time . "</FONT></TH>\n";
  for ( $d = 0; $d < 7; $d++ ) {
    if ( $rowspan_day[$d] > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( strlen ( $save_hour_arr[$d][$i] ) )
        echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\"><FONT SIZE=\"-1\">" .
          $save_hour_arr[$d][$i] . "</FONT></TD>";
      $rowspan_day[$d]--;
    } else {
      if ( ! strlen ( $save_hour_arr[$d][$i] ) )
        echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$CELLBG\">&nbsp;</TD>\n";
      else {
        $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
        if ( $rowspan_day[$d] > 1 )
          echo "<TD VALIGN=\"top\" WIDTH=\"12%\" VALIGN=\"top\" BGCOLOR=\"$TODAYCELLBG\" ROWSPAN=\"$rowspan_day[$d]\">" .
            "<FONT SIZE=\"-1\">" . $save_hour_arr[$d][$i] . "</FONT></TD>\n";
        else
          echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\"><FONT SIZE=\"-1\">" .
            $save_hour_arr[$d][$i] . "</FONT></TD>\n";
      }
    }
  }
  echo "</TR>\n";
}

?>

</TABLE>
</TD></TR></TABLE>

<P>

<?php echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( $login );
?>

<P>
<A HREF="week.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&day=$thisday&";
  }
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include "includes/trailer.inc"; ?>

<?php } ?>

</BODY>
</HTML>

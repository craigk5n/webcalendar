<?php
include_once 'includes/init.php';
load_user_layers ();
load_user_categories ();

$next = mktime ( 3, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 3, 0, 0, $thismonth, $thisday - 7, $thisyear );

$today = mktime ( 3, 0, 0, date ( "m" ), date ( "d" ), date ( "Y" ) );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
$wkend = $wkstart + ( 3600 * 24 * 6 );

$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

if ( $DISPLAY_WEEKENDS == "N" ) {
  if ( $WEEK_START == 1 ) {
    $start_ind = 0;
    $end_ind = 5;
  } else {
    $start_ind = 1;
    $end_ind = 6;
  }
} else {
  $start_ind = 0;
  $end_ind = 7;
}

if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<META HTTP-EQUIV=\"refresh\" content=\"$refresh; URL=week.php?$u_url" .
    "date=$startdate$caturl\" TARGET=\"_self\">\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login,
  $cat_id );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login,
  $startdate, $enddate, $cat_id );

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<BR>" .
     date_to_str ( date ( "Ymd", $days[$i] ), $DATE_FORMAT_MD, false );
}
?>

<TABLE BORDER="0" WIDTH="100%">
<TR>
<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TD ALIGN="left"><A HREF="week.php?<?php echo $u_url; ?>date=<?php echo date("Ymd", $prev ) . $caturl;?>"><IMG SRC="leftarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0"></A></TD>
<?php } ?>
<TD ALIGN="middle"><FONT SIZE="+2" COLOR="<?php echo $H2COLOR;?>"><B CLASS="pagetitle">
<?php
  echo date_to_str ( date ( "Ymd", $wkstart ), "", false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), "", false );
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
  if ( $single_user == "N" ) {
    echo "<BR>$user_fullname\n";
  }
  if ( $is_assistant )
    echo "<B><BR>-- " . translate("Assistant mode") . " --</B>";
  if ( $categories_enabled == "Y" ) {
    echo "<BR>\n<BR>\n";
    print_category_menu('week', sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $cat_id, $friendly );
  }
?>
</FONT>
</TD>
<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TD ALIGN="right"><A HREF="week.php?<?php echo $u_url;?>date=<?php echo date ("Ymd", $next ) . $caturl;?>"><IMG SRC="rightarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0"></A></TD>
<?php } ?>
</TR>
</TABLE>
<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">
<?php } else { ?>
<TABLE BORDER="1" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<?php } ?>

<TR>
<TH WIDTH="12%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>">&nbsp;</TH>
<?php
for ( $d = $start_ind; $d < $end_ind; $d++ ) {
  if ( date ( "Ymd", $days[$d] ) == date ( "Ymd", $today ) ) {
    $color = $TODAYCELLBG;
    $class = "tableheadertoday";
  } else {
    $color = $THBG;
    $class = "tableheader";
  }
  echo "<TH WIDTH=\"13%\" CLASS=\"$class\" BGCOLOR=\"$color\">";
  if ( empty ( $friendly ) && $can_add ) {
    echo html_for_add_icon (  date ( "Ymd", $days[$d] ), "", "", $user );
  }
  echo "<A HREF=\"day.php?" . $u_url .
    "date=" . date ("Ymd", $days[$d] ) . "$caturl\" CLASS=\"$class\">" .
    $header[$d] . "</A></TH>\n";
}
?>
</TR>

<?php

if ( empty ( $TIME_SLOTS ) )
  $TIME_SLOTS = 24;
$interval = ( 24 * 60 ) / $TIME_SLOTS;

$first_slot = (int)( ( ( $WORK_DAY_START_HOUR - $TZ_OFFSET ) * 60 ) / $interval );
$last_slot = (int)( ( ( $WORK_DAY_END_HOUR - $TZ_OFFSET ) * 60 ) / $interval );

$untimed_found = false;
$get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );
if ( $login == "__public__" )
  $get_unapproved = false;

$all_day = array ();
for ( $d = $start_ind; $d < $end_ind; $d++ ) {
  // get all the repeating events for this date and store in array $rep
  $date = date ( "Ymd", $days[$d] );
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // Get static non-repeating events
  $ev = get_entries ( $user, $date );
  $hour_arr = array ();
  $rowspan_arr = array ();
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
        if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
          $viewid = $rep[$cur_rep]['cal_ext_for_id'];
          $viewname = $rep[$cur_rep]['cal_name'] . " (" .
            translate("cont.") . ")";
        } else {
          $viewid = $rep[$cur_rep]['cal_id'];
          $viewname = $rep[$cur_rep]['cal_name'];
        }
        if ( $rep['cal_duration'] == ( 24 * 60 ) )
          $all_day[$d] = 1;
        html_for_event_week_at_a_glance ( $viewid,
          $date, $rep[$cur_rep]['cal_time'],
          $viewname, $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_login'], $hide_icons );
      }
      $cur_rep++;
    }
    if ( $get_unapproved || $ev[$i]['cal_status'] == 'A' ) {
      if ( ! empty ( $ev[$i]['cal_ext_for_id'] ) ) {
        $viewid = $ev[$i]['cal_ext_for_id'];
        $viewname = $ev[$i]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $ev[$i]['cal_id'];
        $viewname = $ev[$i]['cal_name'];
      }
      if ( $ev[$i]['cal_duration'] == ( 24 * 60 ) )
        $all_day[$d] = 1;
      html_for_event_week_at_a_glance ( $viewid,
        $date, $ev[$i]['cal_time'],
        $viewname, $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_login'], $hide_icons );
    }
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $get_unapproved || $rep[$cur_rep]['cal_status'] == 'A' ) {
      if ( ! empty ( $rep[$cur_rep]['cal_ext_for_id'] ) ) {
        $viewid = $rep[$cur_rep]['cal_ext_for_id'];
        $viewname = $rep[$cur_rep]['cal_name'] . " (" .
          translate("cont.") . ")";
      } else {
        $viewid = $rep[$cur_rep]['cal_id'];
        $viewname = $rep[$cur_rep]['cal_name'];
      }
      if ( $rep['cal_duration'] == ( 24 * 60 ) )
        $all_day[$d] = 1;
      html_for_event_week_at_a_glance ( $viewid,
        $date, $rep[$cur_rep]['cal_time'],
        $viewname, $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_login'], $hide_icons );
    }
    $cur_rep++;
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  for ( $i = 0; $i < $TIME_SLOTS; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        if ( $rowspan_arr[$i] > 1 ) {
          $rowspan_arr[$last_row] += ( $rowspan_arr[$i] - 1 );
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else
          $rowspan_arr[$last_row] += $rowspan_arr[$i];
        // this will move entries apart that appear in one field,
        // yet start on different hours
        $start_time = $i;
        $diff_start_time = $start_time - $last_row;
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) 
          $hour_arr[$last_row] .= "<BR>"; 
        $hour_arr[$last_row] .= $hour_arr[$i];
        $hour_arr[$i] = "";
        $rowspan_arr[$i] = 0;
      }
      $rowspan--;
    } else if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
      $rowspan = $rowspan_arr[$i];
      $last_row = $i;
    }
  }

  // now save the output...
  if ( ! empty ( $hour_arr[9999] ) && strlen ( $hour_arr[9999] ) ) {
    $untimed[$d] = "<TD WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\"><FONT SIZE=\"-1\">$hour_arr[9999]</FONT></TD>\n";
    $untimed_found = true;
  }
  $save_hour_arr[$d] = $hour_arr;
  $save_rowspan_arr[$d] = $rowspan_arr;
}

// untimed events first
if ( $untimed_found ) {
  echo "<TR><TD CLASS=\"tableheader\" WIDTH=\"12%\" BGCOLOR=\"$THBG\">&nbsp;</TD>";
  for ( $d = $start_ind; $d < $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    if ( empty ( $WEEKENDBG ) )
      $is_weekend = false;
    $color = $is_weekend ? $WEEKENDBG : $CELLBG;
    if ( ! empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) )
      echo $untimed[$d];
    else
      echo "<TD WIDTH=\"12%\" BGCOLOR=\"$color\">&nbsp;</TD>";
  }
  echo "</TR>\n";
}

for ( $d = $start_ind; $d < $end_ind; $d++ )
  $rowspan_day[$d] = 0;

for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = (int) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100 );
  echo "<TR><TH CLASS=\"tableheader\" VALIGN=\"top\" WIDTH=\"13%\" BGCOLOR=\"$THBG\" HEIGHT=\"40\">" .
    "<FONT COLOR=\"$THFG\">" .  $time . "</FONT></TH>\n";
  for ( $d = $start_ind; $d < $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    if ( empty ( $WEEKENDBG ) )
      $is_weekend = false;
    $color = $is_weekend ? $WEEKENDBG : $CELLBG;
    if ( $all_day[$d] > 0 )
      $color = $TODAYCELLBG;
    if ( $rowspan_day[$d] > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $save_hour_arr[$d][$i] ) )
        echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\"><FONT SIZE=\"-1\">" .
          $save_hour_arr[$d][$i] . "</FONT></TD>";
      $rowspan_day[$d]--;
    } else {
      if ( empty ( $save_hour_arr[$d][$i] ) ) {
        echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$color\">";
        if ( empty ( $friendly ) && $can_add )
          echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
        echo "&nbsp;</TD>\n";
      } else {
        $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
        if ( $rowspan_day[$d] > 1 ) {
          echo "<TD VALIGN=\"top\" WIDTH=\"12%\" VALIGN=\"top\" BGCOLOR=\"$TODAYCELLBG\" ROWSPAN=\"$rowspan_day[$d]\">";
          if ( empty ( $friendly ) && $can_add )
            echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
          echo "<FONT SIZE=\"-1\">" . $save_hour_arr[$d][$i] . "</FONT></TD>\n";
        } else {
          echo "<TD VALIGN=\"top\" WIDTH=\"12%\" BGCOLOR=\"$TODAYCELLBG\"><FONT SIZE=\"-1\">";
          if ( empty ( $friendly ) && $can_add )
            echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
          echo $save_hour_arr[$d][$i] . "</FONT></TD>\n";
        }
      }
    }
  }
  echo "</TR>\n";
}

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
</TABLE>
</TD></TR></TABLE>
<?php } else { ?>
</TABLE>
<?php } ?>

<P>

<?php if ( ! empty ( $eventinfo ) && empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( empty ( $friendly ) ) {
  display_unapproved_events ( ( $is_assistant ? $user : $login ) );
?>

<P>
<A CLASS="navlinks" HREF="week.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&day=$thisday";
  }
  echo $caturl . "&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include_once "includes/trailer.php"; ?>

<?php } else {
        dbi_close ( $c );
      }
?>

</BODY>
</HTML>

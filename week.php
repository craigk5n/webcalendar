<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();

$next = mktime ( 3, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 3, 0, 0, $thismonth, $thisday - 7, $thisyear );

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

$HeadX = '';
if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=week.php?$u_url" .
    "date=$startdate$caturl\" target=\"_self\" />\n";
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
  $header[$i] = $weekdays[$i] . "<br />" .
     date_to_str ( date ( "Ymd", $days[$i] ), $DATE_FORMAT_MD, false );
}
?>

<table style="border-width:0px; width:100%;">
<tr>
<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<td align="left"><a title="<?php etranslate("Previous")?>" href="week.php?<?php echo $u_url; ?>date=<?php echo date("Ymd", $prev ) . $caturl;?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a></td>
<?php } ?>
<td style="text-align:center;"><span class="pagetitle">
<?php
  echo date_to_str ( date ( "Ymd", $wkstart ), "", false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), "", false );
?>
</span>
<?php
if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" ) {
  echo "<br />\n<span style=\"font-size:10px; color:$H2COLOR;\">(" .
    translate("Week") . " " . week_number ( $wkstart ) . ")</span>";
}
?>
<span style="font-size:18px; color:<?php echo $H2COLOR;?>;">
<?php
  if ( $single_user == "N" ) {
    echo "<br />$user_fullname\n";
  }
  if ( $is_nonuser_admin )
    echo "<br /><span style=\"font-weight:bold;\">-- " . translate("Admin mode") . " --</span>";
  if ( $is_assistant )
    echo "<br /><span style=\"font-weight:bold;\">-- " . translate("Assistant mode") . " --</span>";
  if ( $categories_enabled == "Y" ) {
    echo "<br />\n<br />\n";
    print_category_menu('week', sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $cat_id, $friendly );
  }
?>
</span>
</td>
<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<td align="right"><a href="week.php?<?php echo $u_url;?>date=<?php echo date ("Ymd", $next ) . $caturl;?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a></td>
<?php } ?>
</tr>
</table>

<table class="weekview" cellspacing="0" cellpadding="0">

<tr>
<th>&nbsp;</th>
<?php
for ( $d = $start_ind; $d < $end_ind; $d++ ) {
  if ( date ( "Ymd", $days[$d] ) == date ( "Ymd", $today ) ) {
	  echo "<th class=\"today\">";
  } else {
	echo "<th>";
  }
  if ( empty ( $friendly ) && $can_add ) {
    echo html_for_add_icon (  date ( "Ymd", $days[$d] ), "", "", $user );
  }
  echo "<a href=\"day.php?" . $u_url .
    "date=" . date ("Ymd", $days[$d] ) . "$caturl\">" .
    $header[$d] . "</a></th>\n";
}
?>
</tr>

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
  $ev = get_entries ( $user, $date, $get_unapproved );
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
        if ( $rep[$cur_rep]['cal_duration'] == ( 24 * 60 ) )
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
      if ( $rep[$cur_rep]['cal_duration'] == ( 24 * 60 ) )
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
          $hour_arr[$last_row] .= "<br />"; 
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
    $untimed[$d] = "<td class=\"today\">$hour_arr[9999]</td>\n";
    $untimed_found = true;
  }
  $save_hour_arr[$d] = $hour_arr;
  $save_rowspan_arr[$d] = $rowspan_arr;
}

// untimed events first
if ( $untimed_found ) {
  echo "<tr><td style=\"background-color:$THBG;\">&nbsp;</td>";
  for ( $d = $start_ind; $d < $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    if ( empty ( $WEEKENDBG ) )
      $is_weekend = false;
    $color = $is_weekend ? $WEEKENDBG : $CELLBG;
    if ( ! empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) )
      echo $untimed[$d];
    else
      echo "<td style=\"background-color:$color;\">&nbsp;</td>";
  }
  echo "</tr>\n";
}

for ( $d = $start_ind; $d < $end_ind; $d++ )
  $rowspan_day[$d] = 0;

for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = (int) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100 );
  echo "<tr><th class=\"row\">" .  $time . "</th>\n";
  for ( $d = $start_ind; $d < $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    if ( empty ( $WEEKENDBG ) )
      $is_weekend = false;
    $color = $is_weekend ? $WEEKENDBG : $CELLBG;
    if ( ! empty ( $all_day[$d] ) && $all_day[$d] > 0 )
      $color = $TODAYCELLBG;
    if ( $rowspan_day[$d] > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $save_hour_arr[$d][$i] ) )
        echo "<td class=\"today\">" .
          $save_hour_arr[$d][$i] . "</td>";
      $rowspan_day[$d]--;
    } else {
      if ( empty ( $save_hour_arr[$d][$i] ) ) {
        echo "<td style=\"background-color:$color;\">";
        if ( empty ( $friendly ) && $can_add )
          echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
        echo "&nbsp;</td>\n";
      } else {
        $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
        if ( $rowspan_day[$d] > 1 ) {
          echo "<td class=\"today\" rowspan=\"$rowspan_day[$d]\">";
          if ( empty ( $friendly ) && $can_add )
            echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
          echo $save_hour_arr[$d][$i] . "</td>\n";
        } else {
          echo "<td class=\"today\">";
          if ( empty ( $friendly ) && $can_add )
            echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
          echo $save_hour_arr[$d][$i] . "</td>\n";
        }
      }
    }
  }
  echo "</tr>\n";
}

?>

</table>

<br /><br />

<?php if ( ! empty ( $eventinfo ) && empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( empty ( $friendly ) ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<br /><br />
<a class="navlinks" href="week.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&day=$thisday";
  }
  echo $caturl . "&";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php }
print_trailer ();
?>

</body>
</html>

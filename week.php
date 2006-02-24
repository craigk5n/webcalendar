<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin) {
   load_user_layers ($user);
} else {
   load_user_layers ();
}

load_user_categories ();

$nextYmd = date ( "Ymd", mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear ) );
$prevYmd = date ( "Ymd", mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear ) );


if ( $WEEK_START == 1 || $DISPLAY_WEEKENDS == "N" ) {
   $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
} else {
   $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
}

$wkend = $wkstart + ( 3600 * 24 * ( $DISPLAY_WEEKENDS == "N"? 4 : 6 ) );
 
$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );
if ( $DISPLAY_WEEKENDS == "N" ) {
  if ( $WEEK_START == 1 ) {
    $start_ind = 0;
    $end_ind = 4;
  } else {
    $start_ind = 1;
    $end_ind = 5;
  }
} else {
  $start_ind = 0;
  $end_ind = 6;
}
//echo date ("Ymd", $wkstart ) . " " . $start_ind . " " . $end_ind;
$HeadX = '';
if ( ! empty ( $AUTO_REFRESH ) && $AUTO_REFRESH == "Y" &&
  ! empty ( $AUTO_REFRESH_TIME ) ) {
  $refresh = $AUTO_REFRESH_TIME * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=week.php?$u_url" .
    "date=$startdate$caturl" . 
    ( ! empty ( $friendly ) ? "&amp;friendly=1" : "") . "\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login,
  $cat_id, $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login,
  $startdate, $enddate, $cat_id );

if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == "Y" ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) && $is_assistant )
    ? $user : $login, $startdate, $enddate, $cat_id );
}

display_navigation( 'week' );

if (  $WEEK_START == 0 && $DISPLAY_WEEKENDS == "N" ) $wkstart = $wkstart - ONE_DAY;
for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<br />" .
    date_to_str ( date ("Ymd",$days[$i]), $DATE_FORMAT_MD, false, true );
}

?>

<br />

<table class="main" cellspacing="0" cellpadding="0">
<tr>
<th class="empty">&nbsp;</th>
<?php
for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  $thiswday = date ( 'w', $days[$d] );

  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );

  if ( $is_weekend ) {
   // if ( $DISPLAY_WEEKENDS == "N" ) continue;
    $class = "weekend";
  } else {
    $class = "";
  }

  if ( date ( 'Ymd', $days[$d] ) == date ( 'Ymd', $today ) ) {
    if ( $class != '' ) {
     $class .= " ";
    }
    $class .= "today";
  }

  echo "<th";

  if ( $class != '') {
    echo " class=\"$class\"";
  }
  echo ">";

  if ( $can_add ) {
    echo html_for_add_icon (  date ( "Ymd", $days[$d] ), "", "", $user );
  }
  echo "<a href=\"day.php?" . $u_url .
    "date=" . date ('Ymd', $days[$d] ) . $caturl . "\">" .
    $header[$d] . "</a></th>\n";
}
?>
</tr>

<?php
if ( empty ( $TIME_SLOTS ) ) {
  $TIME_SLOTS = 24;
}

$interval = ( 24 * 60 ) / $TIME_SLOTS;

$first_slot = (int)( ( ( $WORK_DAY_START_HOUR ) * 60 ) / $interval );
$last_slot = (int)( ( ( $WORK_DAY_END_HOUR ) * 60 ) / $interval );

$untimed_found = false;
$get_unapproved = ( $DISPLAY_UNAPPROVED == "Y" );

$all_day = array ();
for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  // get all the repeating events for this date and store in array $rep
  $date = date ( "Ymd", $days[$d] );
  $rep = get_repeating_entries ( $user, $date );

  // Get static non-repeating events
  $ev = get_entries ( $user, $date, $get_unapproved, true, true );
  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
 
 // get all due tasks for this date and before and store in $tk
 $tk = array();
 if ( $date >= date ( "Ymd" ) ) {
    $tk = get_tasks ( $user, $date, $get_unapproved );
 }
 $ev = combine_and_sort_events($ev, $tk);

  $hour_arr = array ();
  $rowspan_arr = array ();
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      if ( $ev[$i]->isAllDay() ) {
        $all_day[$d] = 1;
      }
      html_for_event_week_at_a_glance ( $ev[$i], $date );
    }
  }


  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  for ( $i = 0; $i < $TIME_SLOTS; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        $diff_start_time = $i - $last_row;
        if ( $rowspan_arr[$i] > 1 ) {
          if (  $rowspan_arr[$i] + ( $diff_start_time ) >  $rowspan_arr[$last_row]  ) {
            $rowspan_arr[$last_row] = ( $rowspan_arr[$i] + ( $diff_start_time ) );
          }
          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else {
          $rowspan_arr[$last_row] += $rowspan_arr[$i];
        }
        // this will move entries apart that appear in one field,
        // yet start on different hours
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n"; 
        }
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
    $untimed[$d] = $hour_arr[9999];
    $untimed_found = true;
  }
  $save_hour_arr[$d] = $hour_arr;
  $save_rowspan_arr[$d] = $rowspan_arr;
}

// untimed events first
if ( $untimed_found ) {
  echo "<tr>\n<th class=\"empty\">&nbsp;</th>\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
   // if ( $is_weekend && $DISPLAY_WEEKENDS == "N" ) continue;
    $class = ( $is_weekend ? "weekend" : "" );

   if ( date ( 'Ymd', $days[$d] ) == date ( 'Ymd', $today ) ) {
     if ($class != "") {
       $class .= " ";
     }
     $class .= "today";
   }
   echo "<td";

   // Use the class 'hasevents' for any hour block that has events
   // in it.
   if ( ! empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) ) {
    $class = 'hasevents';
   }

   if ( $class != "" ) {
     echo " class=\"$class\"";
   }
   echo ">";

   if ( ! empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) ) {
     echo $untimed[$d];
   } else {
     echo "&nbsp;";
   }
   echo "</td>\n";
  }
  echo "</tr>\n";
}

for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  $rowspan_day[$d] = 0;
}

for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = (int) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  // Do not apply TZ offset
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100, 1 );
  echo "<tr>\n<th class=\"row\">" .  $time . "</th>\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    $class = ( $is_weekend ? "weekend" : "" );
    if ( date ( 'Ymd', $days[$d] ) == date ( 'Ymd', $today ) ) {
      if ( $class != "" ) {
        $class .= " ";
      }
        $class .= "today";
      }

   // Use the class 'hasevents' for any hour block that has events
   // in it.
   if ( ! empty ( $save_hour_arr[$d][$i] ) &&
     strlen ( $save_hour_arr[$d][$i] ) ) {
     $class = 'hasevents';
   }

   if ( $rowspan_day[$d] > 1 ) {
     // this might mean there's an overlap, or it could mean one event
     // ends at 11:15 and another starts at 11:30.
     if ( ! empty ( $save_hour_arr[$d][$i] ) ) {
       echo "<td";
       if ( $class != '' ) {
         echo " class=\"$class\"";
       }
       echo ">" . $save_hour_arr[$d][$i] . "</td>\n";
     }
     $rowspan_day[$d]--;
   } else if ( empty ( $save_hour_arr[$d][$i] ) ) {
     echo "<td";
     if ( $class != "" ) {
       echo " class=\"$class\"";
     }
     echo ">";
     if ( $can_add ) { //if user can add events...
       echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, 
         $user ); //..then echo the add event icon
     }
     echo "&nbsp;</td>\n";
   } else {
     $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
     if ( $rowspan_day[$d] > 1 ) {
       echo "<td";
       if ( $class != '' ) {
         echo " class=\"$class\"";
       }
       echo " rowspan=\"$rowspan_day[$d]\">";
       if ( $can_add ) {
         echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
       }
       echo $save_hour_arr[$d][$i] . "</td>\n";
     } else {
       echo "<td";
       if ( $class != '' ) {
         echo " class=\"$class\"";
       }
       echo ">";
       if ( $can_add ) {
         echo html_for_add_icon (  date ( "Ymd", $days[$d] ), $time_h, $time_m, $user );
       }
       echo $save_hour_arr[$d][$i] . "</td>\n";
     }
   }
  }
  echo "</tr>\n";
}
?>
</table>
<br />

<?php
if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}
display_unapproved_events ( ( $is_assistant || 
  $is_nonuser_admin ? $user : $login ) );
?>

<br />
<a title="<?php etranslate("Generate printer-friendly version")?>" 
class="printer" href="week.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&amp;month=$thismonth&amp;day=$thisday";
  }
  echo $caturl . "&amp;";
?>friendly=1" target="cal_printer_friendly"
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</a>

<?php
print_trailer ();
?>
</body>
</html>

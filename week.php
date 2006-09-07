<?php
/* $Id$ */
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin) {
   load_user_layers ($user);
} else {
   load_user_layers ();
}

load_user_categories ();

$nextYmd = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear ) );
$prevYmd = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear ) );

$boldDays = ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );

$wkend = $wkstart + ( ONE_DAY * ( $DISPLAY_WEEKENDS == 'N'? 5 : 7 ) );
 
$startdate = date ( 'Ymd', $wkstart );
$enddate = date ( 'Ymd', $wkend );

if ( $DISPLAY_WEEKENDS == 'N' ) {
  $WEEK_START = 1; //set to monday
  $start_ind = 0;
  $end_ind = 4;
} else {
  $start_ind = 0;
  $end_ind = 6;
}

if ( empty ( $TIME_SLOTS ) ) {
  $TIME_SLOTS = 24;
}

$interval = ( 24 * 60 ) / $TIME_SLOTS;

$first_slot = (int)( ( ( $WORK_DAY_START_HOUR ) * 60 ) / $interval );
$last_slot = (int)( ( ( $WORK_DAY_END_HOUR ) * 60 ) / $interval );

$untimed_found = false;
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

$HeadX = '';
if ( ! empty ( $AUTO_REFRESH ) && $AUTO_REFRESH == 'Y' &&
  ! empty ( $AUTO_REFRESH_TIME ) ) {
  $refresh = $AUTO_REFRESH_TIME * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=week.php?$u_url" .
    "date=$startdate$caturl" . 
    ( ! empty ( $friendly ) ? '&amp;friendly=1': '') . "\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login,
  $cat_id, $wkstart );

/* Pre-load the non-repeating events for quicker access */
//Start the search one week early to account for cross-day events
$events = read_events ( strlen ( $user ) ? $user : $login,
  $wkstart - ONE_WEEK, $wkend, $cat_id );

if ( empty ( $DISPLAY_TASKS_IN_GRID ) ||  $DISPLAY_TASKS_IN_GRID == 'Y' ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ( ( ! empty ( $user ) && strlen ( $user ) && $is_assistant )
    ? $user : $login, $wkend, $cat_id );
}

$untimedStr = $headerStr = $eventsStr = $minical_tasks = '';
$navStr = display_navigation( 'week' );;
for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
  $days[$i] = ( $wkstart + ( ONE_DAY * $i ) ) + ( 12 * 3600 );
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $thiswday = date ( 'w', $days[$i] );
  $dateYmd = date ( 'Ymd', $days[$i] );

  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
  $header[$i] = $weekdays[$i] . '<br />' .
    date_to_str ( $dateYmd, $DATE_FORMAT_MD, false, true );
  
  $class = '';
  //generate header row
  if ( $is_weekend ) { $class .= 'weekend '; }
  if ( $dateYmd == date ( 'Ymd', $today ) ) { $class .= 'today'; }
  $headerStr .=  '<th';
  if ( $class != '') { $headerStr .= " class=\"$class\""; }
  $headerStr .= '>';
  if ( $can_add ) {
    $headerStr .= html_for_add_icon (  $dateYmd, '', '', $user );
  }
  $headerStr .= '<a href="day.php?' . $u_url . 'date=' . 
    $dateYmd . $caturl . '">' . $header[$i] . "</a></th>\n";

  // get all the repeating events for this date and store in array $rep
  $date = date ( 'Ymd', $days[$i] );
  $rep = get_repeating_entries ( $user, $date );

  // Get static non-repeating events
  $ev = get_entries ( $date, $get_unapproved );
  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
 
 // get all due tasks for this date and before and store in $tk
 $tk = array();
 if ( $date >= date ( 'Ymd' ) ) {
    $tk = get_tasks ( $date, $get_unapproved );
 }
 $ev = combine_and_sort_events($ev, $tk);

  $hour_arr = array ();
  $rowspan_arr = array ();
  for ( $j = 0, $cnt = count ( $ev ); $j < $cnt; $j++ ) {
    if ( $get_unapproved || $ev[$j]->getStatus() == 'A' ) {
      html_for_event_week_at_a_glance ( $ev[$j], $date );
    }
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  for ( $j = 0; $j < $TIME_SLOTS; $j++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$j] ) ) {
        $diff_start_time = $j - $last_row;
        if ( $rowspan_arr[$j] > 1 ) {
          if (  $rowspan_arr[$j] + ( $diff_start_time ) >  $rowspan_arr[$last_row]  ) {
            $rowspan_arr[$last_row] = ( $rowspan_arr[$j] + ( $diff_start_time ) );
          }
          $rowspan += ( $rowspan_arr[$j] - 1 );
        } else {
          $rowspan_arr[$last_row] += $rowspan_arr[$j];
        }
        // this will move entries apart that appear in one field,
        // yet start on different hours
        for ( $u = $diff_start_time ; $u > 0 ; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n"; 
        }
        $hour_arr[$last_row] .= $hour_arr[$j];
        $hour_arr[$j] = '';
        $rowspan_arr[$j] = 0;
      }
      $rowspan--;
    } else if ( ! empty ( $rowspan_arr[$j] ) && $rowspan_arr[$j] > 1 ) {
      $rowspan = $rowspan_arr[$j];
      $last_row = $j;
    }
  }

  // now save the output...
  if ( ! empty ( $hour_arr[9999] ) && strlen ( $hour_arr[9999] ) ) {
    $untimed[$i] = $hour_arr[9999];
    $untimed_found = true;
  }

  $untimedStr .= '<td';

  // Use the class 'hasevents' for any hour block that has events
  // in it.
  if ( ! empty ( $untimed[$i] ) && strlen ( $untimed[$i] ) ) {
    $class .= ' hasevents';
  }
  
  $untimedStr .= " class=\"$class\">";
  
  if ( ! empty ( $untimed[$i] ) && strlen ( $untimed[$i] ) ) {
    $untimedStr .= $untimed[$i];
  } else {
    $untimedStr .= '&nbsp;';
  }
  $untimedStr .= "</td>\n";

  $save_hour_arr[$i] = $hour_arr;
  $save_rowspan_arr[$i] = $rowspan_arr;
  $rowspan_day[$i] = 0;
}
$untimedStr = ( $untimed_found ? '<tr><th class="empty">&nbsp;</th>'. 
  $untimedStr . "</tr>\n": '') ;
for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = (int) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  // Do not apply TZ offset
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100, 1 );
  $eventsStr .= "<tr>\n<th class=\"row\">" .  $time . "</th>\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $thiswday = date ( 'w', $days[$d] );
    $dateYmd = date ( 'Ymd', $days[$d] );
    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
    $class = ( $is_weekend ? 'weekend': '' );
    if ( $dateYmd == date ( 'Ymd', $today ) ) {
      if ( $class != '' ) {
        $class .= ' ';
      }
        $class .= 'today';
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
       $eventsStr .= '<td';
       if ( $class != '' ) {
         $eventsStr .= " class=\"$class\"";
       }
       $eventsStr .= '>' . $save_hour_arr[$d][$i] . "</td>\n";
     }
     $rowspan_day[$d]--;
   } else if ( empty ( $save_hour_arr[$d][$i] ) ) {
     $eventsStr .= '<td';
     if ( $class != '' ) {
       $eventsStr .= " class=\"$class\"";
     }
     $eventsStr .= '>';
     if ( $can_add ) { //if user can add events...
       $eventsStr .= html_for_add_icon (  $dateYmd, $time_h, $time_m, 
         $user ); //..then echo the add event icon
     }
     $eventsStr .= "&nbsp;</td>\n";
   } else {
     $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
     if ( $rowspan_day[$d] > 1 ) {
       $eventsStr .= '<td';
       if ( $class != '' ) {
         $eventsStr .= " class=\"$class\"";
       }
       $eventsStr .= " rowspan=\"$rowspan_day[$d]\">";
       if ( $can_add ) {
         $eventsStr .= html_for_add_icon (  $dateYmd, $time_h, $time_m, $user );
       }
       $eventsStr .= $save_hour_arr[$d][$i] . "</td>\n";
     } else {
       $eventsStr .= '<td';
       if ( $class != '' ) {
         $eventsStr .= " class=\"$class\"";
       }
       $eventsStr .= '>';
       if ( $can_add ) {
         $eventsStr .= html_for_add_icon ( $dateYmd, $time_h, $time_m, $user );
       }
       $eventsStr .= $save_hour_arr[$d][$i] . "</td>\n";
     }
   }
  }
  $eventsStr .= "</tr>\n";
}

$tableWidth = '100%';
$eventinfo = ( ! empty ( $eventinfo )? $eventinfo : '' );
if ( empty ( $friendly ) ) {
  $unapprovedStr = display_unapproved_events ( ( $is_assistant || 
    $is_nonuser_admin ? $user : $login ) );
  $printerStr = generate_printer_friendly ( 'month.php' );
} else {
  $unapprovedStr = $printerStr = '';
}
$trailerStr = print_trailer ();
if ( $DISPLAY_TASKS == 'Y'  ) {
  $tableWidth = '80%';
  $minical_tasks .= '<td id="minicolumn" rowspan="2" valign="top">';
  $minical_tasks .= '<!-- START MINICAL -->';
  $minical_tasks .= '<div class="minicontainer">';
  if  ( $DISPLAY_SM_MONTH == 'Y' ) {
    $minical_tasks .= '<div class="minicalcontainer">';
    $minical_tasks .= display_small_month ( $thismonth, $thisyear, true );
    $minical_tasks .= '</div>';
  }
  $minical_tasks .= '<br />';  
  $minical_tasks .= display_small_tasks ( $cat_id );
  $minical_tasks .= '</div>';
  $minical_tasks .= '</td>';
}//end minical

echo <<<EOT
  <table width="100%"  cellpadding="1">
    <tr>
      <td id="printarea" style="vertical-align:top; width:{$tableWidth};" >
      {$navStr}
      </td>
      <td></td>
    </tr>
    <tr>
      <td>
        <table class="main ">
          <tr>
            <th class="empty">&nbsp;</th>
            {$headerStr}
          </tr>
          {$untimedStr}
          {$eventsStr}
        </table>
      </td>
     {$minical_tasks}
    </tr>
  </table> 
  {$eventinfo}
  {$unapprovedStr}
  {$printerStr}
  {$trailerStr}
EOT;
?>
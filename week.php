<?php
/* $Id$ */
define ( 'CALTYPE', 'week' ); 
include_once 'includes/init.php';
//we need this smarty function 
require_once $smarty->_get_plugin_filepath('function', 'week_glance');	

$layers = loadLayers ( $WC->userId() );


$week_start = getPref ( 'WEEK_START' );
if ( ! getPref ( 'DISPLAY_WEEKENDS' ) ) {
  $start_ind = 0;
  $end_ind = 4;
} else {
  $start_ind = 0;
  $end_ind = 6;
}

$time_slots = getPref ( 'TIME_SLOTS' );
if ( empty ( $time_slots ) )
  $time_slots = 24;

$interval = ( 24 * 60 ) / $time_slots;

$first_slot = (int)( ( ( getPref ('WORK_DAY_START_HOUR' ) ) * 60 ) / $interval );
$last_slot = (int)( ( ( getPref ('WORK_DAY_END_HOUR' ) ) * 60 ) / $interval );

$untimed_found = false;
$get_unapproved = ( getPref ('DISPLAY_UNAPPROVED') );

/* Pre-Load the repeated events for quickier access */
$repeated_events = read_repeated_events ();

/* Pre-load the non-repeating events for quicker access */
$events = read_events ();

if ( getPref ('DISPLAY_TASKS_IN_GRID' ) ) {
  /* Pre-load tasks for quicker access */
  $tasks = read_tasks ();
}

$untimedStr = $headerStr = $eventsStr = 
  $minical_tasks = $filler = '';

$display_long_days = getPref ('DISPLAY_LONG_DAYS' );
$date_format_md = getPref ( 'DATE_FORMAT_MD' );
for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
  $days[$i] = ( $WC->getStartDate() + ( ONE_DAY * $i ) ) + ( 12 * ONE_HOUR );
  $weekdays[$i] = weekday_name ( ( $i + $week_start ) % 7, $display_long_days );
  $dateYmd = date ( 'Ymd', $days[$i] );

  $header[$i] = $weekdays[$i] . '<br />' .
    date_to_str ( $dateYmd, 'DATE_FORMAT_MD', false, true );
  
  $class = '';
  //generate header row
  if ( is_weekend ( $days[$i] ) ) { $class .= 'weekend '; }
  if ( $dateYmd == $WC->todayYmd ) { $class .= 'today'; }
  $headerStr .=  '<th id="' . $dateYmd .'"' ;
  if ( $class != '') { $headerStr .= " class=\"$class\""; }
  $headerStr .= '>';
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
      smarty_function_week_glance ( array ('event'=>$ev[$j],'date'=>$date ), $smarty );
    }
  }

  // squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  $rowspan = 0;
  $last_row = -1;
  for ( $j = 0; $j < $time_slots; $j++ ) {
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
        for ( $u = $diff_start_time; $u > 0; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n"; 
        }
        $hour_arr[$last_row] .= $hour_arr[$j];
        $hour_arr[$j] = '';
        $rowspan_arr[$j] = 0;
      }
      $rowspan--;
    } else
    if ( ! empty ( $rowspan_arr[$j] ) && $rowspan_arr[$j] > 1 ) {
      $last_row = $j;
      $rowspan = $rowspan_arr[$j];
    }
  }

  // Now save the output...
  if ( ! empty ( $hour_arr[9999] ) && strlen ( $hour_arr[9999] ) ) {
    $untimed[$i] = $hour_arr[9999];
    $untimed_found = true;
  }

  $untimedStr .= '<td';

  // Use the class 'hasevents' for any hour block that has events
  // in it.
  if ( ! empty ( $untimed[$i] ) && strlen ( $untimed[$i] ) ) {
    $class .= ' hasevents ';
  }
  
  $untimedStr .= " class=\"$class\"><div>";
  
  if ( ! empty ( $untimed[$i] ) && strlen ( $untimed[$i] ) ) {
    $untimedStr .= $untimed[$i];
  } else {
    $untimedStr .= '&nbsp;';
  }
  $untimedStr .= "</div></td>\n";

  $save_hour_arr[$i] = $hour_arr;
  $save_rowspan_arr[$i] = $rowspan_arr;
  $rowspan_day[$i] = 0;
}

$smarty->assign ( 'navStart', date ( 'Ymd', $days[$start_ind] ) );
$smarty->assign ( 'navEnd', date ( 'Ymd', $days[$end_ind] ) );

$untimedStr = ( $untimed_found ? '<tr><th class="empty">&nbsp;</th>'. 
  $untimedStr . "</tr>\n": '');
for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = sprintf ( '%02d', (int) ( ( $i * $interval ) / 60 ) );
  $time_m = sprintf ( '%02d', ( $i * $interval ) % 60 );
  // Do not apply TZ offset
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100, 1 );
  $eventsStr .= "<tr>\n<th class=\"row\">" .  $time . "</th>\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $dateYmd = date ( 'Ymd', $days[$d] );
    $class = ( is_weekend ( $days[$d] ) ? 'weekend': '' );
    if ( $dateYmd == $WC->todayYmd ) {
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
     $eventsStr .= '<td id="td' . $dateYmd . $time_h . $time_m .'"';
     if ( $class != '' ) {
       $eventsStr .= " class=\"$class\"";
     }
     $eventsStr .= '><div>';
     $eventsStr .= "&nbsp;</div></td>\n";
   } else {
     $rowspan_day[$d] = $save_rowspan_arr[$d][$i];
     if ( $rowspan_day[$d] > 1 ) {
       $eventsStr .= '<td id="td' . $dateYmd . $time_h . $time_m .'"';
       if ( $class != '' ) {
         $eventsStr .= " class=\"$class\"";
       }
       $eventsStr .= " rowspan=\"$rowspan_day[$d]\"><div>";
       $eventsStr .= $save_hour_arr[$d][$i] . "</div></td>\n";
     } else {
       $eventsStr .= '<td id="td' . $dateYmd . $time_h . $time_m .'"';
       if ( $class != '' ) {
         $eventsStr .= " class=\"$class\"";
       }
       $eventsStr .= '><div>';
       $eventsStr .= $save_hour_arr[$d][$i] . "</div></td>\n";
     }
   }
  }
  $eventsStr .= "</tr>\n";
}

$smarty->assign ('headerStr', $headerStr );
$smarty->assign ('untimedStr', $untimedStr );
$smarty->assign ('eventsStr', $eventsStr );
$smarty->assign ('navName', 'week' );
$smarty->assign ( 'navArrows', true );

$BodyX = 'onload="onLoad();"';
build_header (array( 'calendar.js' ), '',$BodyX );

$smarty->display('week.tpl');
?>
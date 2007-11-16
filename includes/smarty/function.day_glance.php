<?php
/* Prints all the calendar entries for the specified user
 * for the specified date in day-at-a-glance format.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date  Date in YYYYMMDD format
 * @param string $user  Username of calendar
 */
function smarty_function_day_glance ( $params, &$smarty ) {
  global $WC, $first_slot, $hour_arr, $last_slot, $rowspan, 
  $rowspan_arr;
 
  $date = $params['date'];
  $user = $params['user'];
  $can_add = $WC->canAdd();
	
  $time_slots = getPref ( 'TIME_SLOTS' );
  if ( ! $time_slots )
    $time_slots = 24;

  $get_unapproved = getPref ( 'DISPLAY_UNAPPROVED' );
  // Get, combine and sort the events for this date.
  $ev = combine_and_sort_events (
    get_entries ( $date, $get_unapproved ), // Get static non-repeating events.
    get_repeating_entries ( $user, $date )// Get all the repeating events.
    );
  if ( $date >= date ( 'Ymd' ) &&
      ( getPref ( 'DISPLAY_TASKS_IN_GRID' ) ) )
    $ev = combine_and_sort_events ( $ev,
      get_tasks ( $date, $get_unapproved ) // Get all due tasks.
      );
  $hour_arr = $rowspan_arr = array ();
  $interval = 1440 / $time_slots; // Number of minutes per slot.
  $last_row = -1;
  $ret = '';
  $rowspan = 0;

  $first_slot = intval ( ( getPref ( 'WORK_DAY_START_HOUR' ) * 60 ) / $interval );
  $last_slot = intval ( ( getPref ( 'WORK_DAY_END_HOUR' ) * 60 ) / $interval );

  for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus () == 'A' )
      html_for_event_day_at_a_glance ( $ev[$i], $date );
  }

  // Squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  for ( $i = ( $first_slot < 0 ? $first_slot : 0 ); $i < $time_slots; $i++ ) {
    if ( $rowspan > 1 ) {
      if ( ! empty ( $hour_arr[$i] ) ) {
        $diff_start_time = $i - $last_row;
        if ( ! empty ( $rowspan_arr[$i] ) ) {
          if ( $rowspan_arr[$i] > 1 &&
            ( $rowspan_arr[$i] + ( $diff_start_time ) > $rowspan_arr[$last_row] ) )
            $rowspan_arr[$last_row] = ( $rowspan_arr[$i] + ( $diff_start_time ) );

          $rowspan += ( $rowspan_arr[$i] - 1 );
        } else
          $rowspan_arr[$last_row] += $rowspan_arr[$i];

        // This will move entries apart that appear in one field,
        // yet start on different hours.
        for ( $u = $diff_start_time; $u > 0; $u-- ) {
          $hour_arr[$last_row] .= "<br />\n";
        }
        $hour_arr[$last_row] .= $hour_arr[$i];
        $hour_arr[$i] = '';
        $rowspan_arr[$i] = 0;
      }
      $rowspan--;
    } else
    if ( ! empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
      $last_row = $i;
      $rowspan = $rowspan_arr[$i];
    }
  }
  $ret .= '
    <table class="main glance" cellspacing="0" cellpadding="0">'
   . ( ! empty ( $hour_arr[9999] ) ? '
      <tr>
        <th class="empty">&nbsp;</th>
        <td class="hasevents">' . $hour_arr[9999] . '</td>
      </tr>' : '' );
      
  $rowspan = 0;
  for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
    $time_h = sprintf ( '%02d', intval ( ( $i * $interval ) / 60 ) );
    $time_m = sprintf ( '%02d', ( $i * $interval ) % 60 );
    $displayTime = display_time ( mktime ( $time_h, $time_m , 0, 
      $WC->thismonth, $WC->thisday, $WC->thisyear ) );

    //Check for duplicates, this  will happen on DST change days
    if ( ! empty ( $tp_displayTime ) && $tp_displayTime == $displayTime )
      continue;
    $tp_displayTime = $displayTime;
    
    $ret .= '
      <tr>
        <th class="row">'. $displayTime . '</th>';
    if ( $rowspan > 1 ) {
      // This might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $hour_arr[$i] ) )
        $ret .= '
        <td id="td' . $date . $time_h . $time_m 
				  . '" class="hasevents"><div>'  . $hour_arr[$i] . '</div></td>';

      $rowspan--;
    } else {
      $ret .= '
        <td id="td' . $date . $time_h . $time_m 
				  . '" ';
      if ( empty ( $hour_arr[$i] ) )
        $ret .= ( $date == $WC->todayYmd ? ' class="today"' : '' )
         . '><div>';
      else {
        $rowspan = ( empty ( $rowspan_arr[$i] ) ? '' : $rowspan_arr[$i] );

        $ret .= ( $rowspan > 1 ? 'rowspan="' . $rowspan . '"' : '' )
         . 'class="hasevents"><div>' . $hour_arr[$i];
      }
      $ret .= '</div></td>';
    }
    $ret .= '
      </tr>';
  }
  return $ret . '
    </table>';
}

/* Generates the HTML for an event to be viewed in the day-at-glance (day.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event  The event
 * @param string $date   Date of event in YYYYMMDD format
 */
function html_for_event_day_at_a_glance ( $event, $date ) {
  global $categories, $first_slot, $hour_arr, $last_slot, 
  $layers, $WC, $rowspan, $rowspan_arr;
  static $key = 0;

  $can_access = CAN_DOALL;
  $end_timestr = $popup_timestr = '';
  $getCalTypeName = $event->getCalTypeName ();
  $getCat = abs ( $event->getCategory () );
  $getClone = $event->getClone ();
  $getDesc = $event->getDescription ();
  $getLogin = $event->getLoginId ();
  $getPri = $event->getPriority ();
  $eid = $event->getId ();
  $ind = 9999;
  $isAllDay = $event->isAllDay ();
  $linkid = "pop$eid-$key";
  $name = $event->getName ();
  $time = $event->getDate ( 'His');
  $time_only = 'N';
  $view_text = translate ( 'View this event' );

  $catIcon = 'icons/cat-' . $getCat . '.gif';
  $key++;

  $can_access = access_user_calendar ( 'view', $getLogin, '',
    $event->getCalType (), $event->getAccess () );
  $time_only = access_user_calendar ( 'time', $getLogin );
  if ( $getCalTypeName == 'task' && $can_access == 0 )
    return false;

  // If TZ_OFFSET make this event before the start of the day or
  // after the end of the day, adjust the time slot accordingly.
  if ( ! $event->isUntimed () && ! $isAllDay && $getCalTypeName != 'task' ) {
    $tz_time = date ( 'His', $event->getDate () );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;

    if ( $ind > $last_slot )
      $last_slot = $ind;
  }
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  $class = ( $WC->loginId() != $getLogin && strlen ( $getLogin )
    ? 'layer' : ( $event->getStatus () == 'W' ? 'unapproved' : '' ) . 'entry' );
  // If we are looking at a view, then always use "entry".
  if ( defined ( '_WC_CUSTOM_VIEW' ) )
    $class = 'entry';

  if ( $getCat > 0 && file_exists ( $catIcon ) ) {
    $catAlt = translate ( 'Category' ) . ': ' . $categories[$getCat]['cat_name'];
    $hour_arr[$ind] .= '<img src="' . $catIcon . '" alt="' . $catAlt
     . '" title="' . $catAlt . '" />';
  }

  if ( $getCalTypeName == 'task' ) {
    $hour_arr[$ind] .= '<img src="images/task.gif" class="bullet" alt="*" /> ';
    $view_text = translate ( 'View this task' );
  }

  $hour_arr[$ind] .= '<a title="' . $view_text . '" class="' . $class . '" id="'
   . $linkid . '" '
  // Make sure clones have parents URL date.
  . ( $can_access != 0 && $time_only != 'Y'
    ? 'href="view_entry.php?eid=' . $eid . '&amp;date='
     . ( $getClone ? $getClone : $date )
     . ( strlen ( $GLOBALS['user'] ) > 0
      ? '&amp;user=' . $GLOBALS['user']
      : ( $class == 'layerentry' ? '&amp;user=' . $getLogin : '' ) ) . '"'
    : '' ) . '>' . ( $getPri == 3 ? '<strong>' : '' );

  if ( $WC->loginId() != $getLogin && strlen ( $getLogin ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruse_id'] == $getLogin ) {
          $hour_arr[$ind] .= '<span style="color:' . $layer['cal_color'] . ';">';
          $in_span = true;
        }
      }
    }
    // Check to see if Category Colors are set.
  } else
  if ( ! empty ( $categories[$getCat]['cat_color'] ) ) {
    $cat_color = $categories[$getCat]['cat_color'];
    if ( $cat_color != '#000000' ) {
      $hour_arr[$ind] .= '<span style="color:' . $cat_color . ';">';
      $in_span = true;
    }
  }

  if ( $isAllDay )
    $hour_arr[$ind] .= '[' . translate ( 'All day event' ) . '] ';
  else
  if ( $time >= 0 && ! $isAllDay && $getCalTypeName != 'task' ) {
    $end_timestr = '-' . display_time ( $event->getEndDate () );
    $popup_timestr = display_time ( $event->getDate () );

    $hour_arr[$ind] .= '[' . $popup_timestr;
    if ( $event->getDuration () > 0 ) {
      $popup_timestr .= $end_timestr;
      if ( getPref ( 'DISPLAY_END_TIMES' ) )
        $hour_arr[$ind] .= $end_timestr;
      // Which slot is end time in? take one off so we don't
      // show 11:00-12:00 as taking up both 11 and 12 slots.
      $end_time = date ( 'His', $event->getEndDate () );
      // This fixes the improper display if an event ends at or after midnight.
      if ( $end_time < $tz_time )
        $end_time += 240000;

      $endind = calc_time_slot ( $end_time, true );
      $rowspan = ( $endind == $ind ? 0 : $endind - $ind + 1 );

      if ( ! isset ( $rowspan_arr[$ind] ) )
        $rowspan_arr[$ind] = 0;

      if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
        $rowspan_arr[$ind] = $rowspan;
    }
    $hour_arr[$ind] .= ']'; 
  }
  $hour_arr[$ind] .= build_entry_label ( $event, 'eventinfo-' . $linkid,
    $can_access, $popup_timestr, $time_only )
   . ( $getPri == 3 ? '</strong>' : '' ) . '</a>'
   . ( getPref ( 'DISPLAY_DESC_PRINT_DAY') ? '
    <dl class="desc">
      <dt>' . translate ( 'Description' ) . ':</dt>
      <dd>'
     . ( getPref ( 'ALLOW_HTML_DESCRIPTION' )
      ? $getDesc : strip_tags ( $getDesc ) ) . '</dd>
    </dl>' : '' ) . "<br />\n";
}
?>

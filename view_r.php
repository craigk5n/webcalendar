<?php /* $Id$ */
/**
 * Page Description:
 * This is the "Week by Time" and "Week by Day" view.
 * This view will show either a week's worth of events (type='R')
 * or a single day of events (type='E')
 * using a format with days across the top of the table and time
 * showing down the left side. (This is just like the standard
 * layout of day.php and week.php.)
 * However, each cell will be subdivided into
 * however many users are part of this view.
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * (*) required field
 *
 * Comments:
 * The week view of this page will only show weekends if the
 * user has set "display weekends in week view" in their
 * user preferences.
 *
 * The week version of this page has the potential to contain
 * a large table. The layout will be skewed to try and fit this
 * into a page.
 * If you want to allow the table to grow larger than the viewable
 * area in the browser, set the $fit_to_window_week to be false below.
 * (You can do the same for the day view with $fit_to_window_day.)
 *
 * Should we make this an option when creating/updating the view?
 * If we did make this an option in the UI, we would need to either:
 * (A) add a column to the webcal_view table
 * (B) use different view types for this option ('E' and 'R' for
 *     fit-to-window, 'G' and 'X' for expand?)
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/views.php';

$can_add = true; // include '+' add icons in this view?

// Set this to true to allow the table to be larger than the browser's
// viewable area.
// Only if you have more than 7 users, would you need to set this to
// false for the day view.
// On the week view, 3 or more users start to
// get crowded and you may want to set this to true.
$fit_to_window_day = false;
$fit_to_window_week = true;

// This defines how wide the smallest column will be for the view
$col_pixels_week = 90; // if above is true, how large is each column in table
// This defines how wide the smallest column will be for the view
$col_pixels_day = 150; // if above is true, how large is each column in table

// Should the time of the event be displayed
$show_time_day = true;
$show_time_week = false;

// Display abbreviated Timezone name in popup
$DISPLAY_TZ = 2;

// Should there always be a row for untimed/all-day events?
// Normally we only show this if there are some of these events, but
// if you want to be able to add an all-day event quickly, you can
// double-click in one of these table cells, so it's handy to have
// this row around in all cases.
$show_untimed_row_always = true;

// view type 'E' = Day by Time, 'R' = Week by Time
$is_day_view = ( $view_type == 'E' );
$col_pixels = ( $is_day_view ? $col_pixels_day : $col_pixels_week );
$fit_to_window = ( $is_day_view ? $fit_to_window_day : $fit_to_window_week );
$show_time = ( $is_day_view ? $show_time_day : $show_time_week );

$printerStr = generate_printer_friendly ( 'view_r.php' );

$thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );

$next = mktime( 0, 0, 0, $thismonth, $thisday + ( $is_day_view ? 1 : 7 ), $thisyear );

$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextday = date ( 'd', $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime( 0, 0, 0, $thismonth, $thisday - ( $is_day_view ? 1 : 7 ), $thisyear );

$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevday = date ( 'd', $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );

$wkend = $wkstart + 604800;

$time_w = ( $fit_to_window ? '8%' : '100px' ); // 8% for time column

// Set the day of week range (0=Sun, 6=Sat)
// $start_ind = start of range
// $end_ind = end of range (inclusive)

if ( $is_day_view ) {
  $thistime = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
  $start_ind = $end_ind = ( date ( 'w', $thistime ) - $WEEK_START + 7 ) % 7;
} else {
  if ( $DISPLAY_WEEKENDS == 'N' ) {
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
}

// Generate the column headers for each day and the unix datetime
// values for each date.
for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
  $days[$i] = ( $wkstart + 86400 * $i ) + 43200;
  $weekdays[$i] = weekday_name ( ( $i + $WEEK_START ) % 7, $DISPLAY_LONG_DAYS );
  $header[$i] = $weekdays[$i] . '<br>' .
     month_name ( date ( 'm', $days[$i] ) - 1, 'M' ) .
     ' ' . date ( 'd', $days[$i] );
  if ( empty ( $first_date ) )
    $first_date = date_to_str ( date ( 'Ymd', $days[$i] ), '', false );
  $last_date = date_to_str ( date ( 'Ymd', $days[$i] ), '', false );
}

// The table has dates across the top and times for rows. Since we need
// to spit out an entire row before we can move to the next time slot, we'll
// save up all the HTML for each cell and then print it out when we're
// done.

// Make sure we have at least one user in our view.
// If this is a global view, we may have removed all the users if
// the current user does not have permission to view any of the
// users in the view.
// In theory, we whould always at least have ourselves in the view, right?

if ( ! empty ( $error ) ) {
  echo print_error( $error ) . print_trailer();
  ob_end_flush();
  exit;
}

// table_width = width in pixels of entire table
// col_pixels = width of smallest column
// 100 = width of time column on left of table
if ( ! $fit_to_window ) {
  $table_width = ( $col_pixels * $viewusercnt ) * ( $end_ind - $start_ind + 1 ) + 100;
}

// tdw is the cell width for each day
if ( ! $fit_to_window )
  // pixels
  $tdw = floor ( ( $table_width - $time_w ) / ( $end_ind - $start_ind + 1 ) );
else
  // %
  $tdw = floor ( ( 100 - $time_w ) / ( $end_ind - $start_ind + 1 ) );

$untimed_found = false;
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
// public access events cannot override $DISPLAY_UNAPPROVED
if ( $user == '__public__' && $PUBLIC_ACCESS_VIEW_UNAPPROVED != 'Y' )
  $get_unapproved = false;

// Step through each user and load events for that user.
// Store in $e_save[] (normal events) and $re_save[] (repeating events).
$e_save = array();
$re_save = array();
if ( ! $fit_to_window )
  $uwf = $col_pixels . 'px';
else
  $uwf = sprintf ( "%0.2f", $tdw / $viewusercnt ) . '%';

$uheader = '';
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access
     subtracting ONE_WEEK to allow cross-day events to display. */
  $events = read_events( $viewusers[$i], $wkstart - 604800, $wkend );
  $e_save[$i] = $events;
  user_load_variables ( $viewusers[$i], 'temp' );
  $uheader .= "<th class=\"small\" width=\"$uwf\" style=\"width:$uwf;\">" .
    $tempfullname . "</th>\n";
  //echo "$viewusers[$i]: loaded " . count( $events ) . " events<br>\n";
}
$num_users = $viewusercnt;

// $TIME_SLOTS is set in both admin system settings and user preferences.
if ( empty ( $TIME_SLOTS ) )
  $TIME_SLOTS = 24;
$interval = 1440 / $TIME_SLOTS;
$first_slot = (int)( ( $WORK_DAY_START_HOUR * 60 ) / $interval );
$last_slot = (int)( ( $WORK_DAY_END_HOUR * 60 ) / $interval );

?>

<div style="width:99%;">
<a title="<?php echo $prevStr;?>" class="prev" href="view_r.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="images/leftarrow.gif" alt="<?php echo $prevStr;?>"></a>

<a title="<?php echo $nextStr;?>" class="next" href="view_r.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="images/rightarrow.gif" class="prevnext" alt="<?php echo $nextStr?>"></a>
<div class="title">
<span class="date"><?php
  if ( $is_day_view ) {
    echo date_to_str ( date ( 'Ymd', $thistime ), false );
  } else {
    echo $first_date . "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" . $last_date;
  }
?></span><br>
<span class="viewname"><?php echo htmlspecialchars ( $view_name ) ?></span>
<?php
  if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
    echo "<br>\n<span class=\"titleweek\">(" .
      translate( 'Week' ) . ' ' . date( 'W', $wkstart + 86400 ) . ')</span>';
  }
?>
</div></div><br>

<?php
$help = ( $can_add ? 'title="' . $dblClickAdd . '"' : '' );

if ( ! $fit_to_window ) { ?>
<table <?php echo $help;?> class="main" style="width:<?php
  echo $table_width;?>px;" width="<?php echo $table_width;?>">
<?php } else { ?>
<table <?php echo $help;?> class="main">
<?php } ?>

<!-- table header -->
<tr><th class="empty" width="<?php echo $time_w;?>" style="width:<?php echo $time_w;?>;">&nbsp;</th>
<?php
  // heading row that displays day of week and date
  if ( ! $fit_to_window )
    $tdwf = ( $col_pixels * $viewusercnt ) . 'px';
  else
    $tdwf = sprintf ( "%0.2f", $tdw ) . "%";
  $todayYmd = date ( 'Ymd', $today );
  for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
    if ( is_weekend ( $days[$i] ) && $DISPLAY_WEEKENDS == 'N' ) continue;
    if ( $todayYmd == date ( 'Ymd', $days[$i] ) )
      $class = 'class="today"';
    else if ( is_weekend ( $days[$i] ) )
      $class = 'class="weekend"';
    else
      $class = '';

    echo '<th ' . $class . ' style="width:' . $tdwf . ';" colspan="'
     . $num_users . '">' . $header[$i] . "</th>\n";
  }
?>
</tr>

<tr><th class="empty" width="<?php echo $time_w;?>" style="width:<?php echo $time_w;?>;">&nbsp;</th>
<?php
  for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
    echo $uheader;
  }
?>
</tr>
<!-- end table header -->

<?php

// We need to store all the events and where they go before we begin
// printing any output.

$all_day = array();

//<long-winded-explanation>
// We loop through the events once checking for the start time. If we
// find a start time before the normal work hours, we will reset $first_slot
// to this new time. We do this in a separate loop because all-day events
// will assume a start time slot of the beginning of normal work hours.
// So, if there is an all-day event on Monday, it might use the first_slot
// that represents 8am only to find an event on Thu has a time of 7am which
// would change the first_slot value. There is then a gap above the all-day
// event.
//</long-winded-explanation>
$am_part = array(); // am I a participant array
for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  for ( $u = 0; $u < $viewusercnt; $u++ ) {
    $untimed = array();
    $user = $viewusers[$u];
    $events = $e_save[$u];
    $repeated_events = $re_save[$u];
    // get all the repeating events for this date and store in array $rep
    $dateYmd = date ( 'Ymd', $days[$d] );
    $rep = get_repeating_entries ( $user, $dateYmd );
    foreach ( $rep as $j ) {
      if( ! isset ( $am_part[$j->getID()] ) ) {
        $am_part[$j->getID()] = user_is_participant ( $j->getID(), $login );
      }
      if( $get_unapproved || $j->getStatus() == 'A' ) {
        if ( $j->getDuration() > 0 && $j->getDuration() != 1440 ) {
          $slot = calc_time_slot ( $j->getTime(), false );
          if ( $slot < $first_slot ) {
            $first_slot = $slot;
          }
        }
      }
    }
    $ev = get_entries ( $dateYmd, $get_unapproved, 1, 1);
    foreach ( $ev as $j ) {
      if( ! isset ( $am_part[$j->getID()] ) ) {
        $am_part[$j->getID()] = user_is_participant ( $j->getID(), $login );
      }
      if( $j->getDuration() > 0 && $j->getDuration() != 1440 ) {
        $slot = calc_time_slot( $j->getTime(), false );
        if ( $slot < $first_slot ) {
          $first_slot = $slot;
        }
      }
    }
  }
}

for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  for ( $u = 0; $u < $viewusercnt; $u++ ) {
    $untimed = array();
    $user = $viewusers[$u];
    $events = $e_save[$u];
    $repeated_events = $re_save[$u];
    // get all the repeating events for this date and store in array $rep
    $dateYmd = date ( 'Ymd', $days[$d] );
    $rep = get_repeating_entries ( $user, $dateYmd );
    $cur_rep = 0;

    // Get static non-repeating events
    $ev = get_entries ( $dateYmd, $get_unapproved, 1, 1 );
    $hour_arr = $rowspan_arr = array();
    $evcnt = count ( $ev );
    $repcnt = count ( $rep );
    foreach ( $ev as $i ) {
      // print out any repeating events that are before this one...
      while ( $cur_rep < $repcnt && $rep[$cur_rep]->getTime() < $i->getTime() ) {
        if( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
          if( $rep[$cur_rep]->getDuration() == 1440 )
            $all_day[$d] = 1;
          html_for_event_week_at_a_glance ( $rep[$cur_rep], $dateYmd, 'small', $show_time );
        }
        $cur_rep++;
      }
      if ( $get_unapproved || $i->getStatus() == 'A' ) {
        if ( $i->getDuration() == 1440 )
          $all_day[$d] = 1;
        html_for_event_week_at_a_glance ( $i, $dateYmd, 'small', $show_time );
      }
    }
    // print out any remaining repeating events
    while ( $cur_rep < $repcnt ) {
      if( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
        if( $rep[$cur_rep]->getDuration() == 1440 )
          $all_day[$d] = 1;
        html_for_event_week_at_a_glance ( $rep[$cur_rep], $dateYmd, 'small', $show_time );
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
        if ( !empty ( $hour_arr[$i] ) ) {
          if ( $rowspan_arr[$i] > 1 ) {
            $rowspan_arr[$last_row] += ( $rowspan_arr[$i] - 1 );
            $rowspan += ( $rowspan_arr[$i] - 1 );
          } else
            $rowspan_arr[$last_row] += $rowspan_arr[$i];
          // this will move entries apart that appear in one field,
          // yet start on different hours
          $start_time = $i;
          $diff_start_time = $start_time - $last_row;
          for ( $x = $diff_start_time; $x > 0; $x-- )
            $hour_arr[$last_row] .= "<br>\n";
          $hour_arr[$last_row] .= $hour_arr[$i];
          $hour_arr[$i] = '';
          $rowspan_arr[$i] = 0;
        }
        $rowspan--;
      } else if ( !empty ( $rowspan_arr[$i] ) && $rowspan_arr[$i] > 1 ) {
        $rowspan = $rowspan_arr[$i];
        $last_row = $i;
      }
    }

    // now save the output...
    if ( ! empty ( $hour_arr[9999] ) && strlen ( $hour_arr[9999] ) ) {
      $untimed[$d] = $hour_arr[9999];
      $untimed_found = true;
    }
    $save_hour_arr[$u][$d] = $hour_arr;
    $save_rowspan_arr[$u][$d] = $rowspan_arr;
    $save_untimed[$u][$d] = $untimed;
  }
}

// untimed events first
if ( $untimed_found || $show_untimed_row_always ) {
  echo '<tr><th class="empty" width="' .$time_w. '" style="width:'
   . $time_w . ';">&nbsp;</th>' . "\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $dateYmd = date ( 'Ymd', $days[$d] );
    $is_weekend = is_weekend ( $days[$d] );
    if ( $is_weekend  && $DISPLAY_WEEKENDS == 'N' ) continue;
    if ( $dateYmd == $todayYmd )
      $class .= ' class="today"';
    else if ( $is_weekend )
      $class .= ' class="weekend"';
    else
      $class = '';
    for ( $u = 0; $u < $viewusercnt; $u++ ) {
      $untimed = $save_untimed[$u][$d];
      // Use the class 'hasevents' for any hour block that contains events.
      if ( !empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) ) {
        $class = ' class="hasevents"';
      }

      echo '<td' . $class . ( $can_add
        ? " ondblclick=\"dblclick( '$dateYmd', '$viewusers[$u]' )\">" : '>' )
       . ( empty( $untimed[$d] ) && strlen ( $untimed[$d] )
         ? '&nbsp;' : $untimed[$d] ) . "</td>\n";
    }
  }
  echo "</tr>\n";
}

$rowspan_day = array();
for ( $u = 0; $u < $viewusercnt; $u++ ) {
  for ( $d = $start_ind; $d <= $end_ind; $d++ )
    $rowspan_day[$u][$d] = 0;
}

for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = ( int ) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100, 1 );
  echo "<tr>\n<th valign=\"top\" class=\"row\" width=\"$time_w" .
    '">' . $time . "</th>\n";
  //echo "<tr>\n<th valign=\"top\">" . $time . "</th>\n";

  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $dateYmd = date ( 'Ymd', $days[$d] );
    for ( $u = 0; $u < $viewusercnt; $u++ ) {
      $hour_arr = $save_hour_arr[$u][$d];
      $rowspan_arr = $save_rowspan_arr[$u][$d];
      $is_weekend = is_weekend ( $days[$d] );
      if ( $dateYmd == $todayYmd )
        $class .= ' class="today"';
      else if ( $is_weekend )
        $class .= ' class="weekend"';
      else
        $class = '';
      // Use the class 'hasevents' for any hour block that contains events.
      if ( ! empty ( $hour_arr[$i] ) && strlen ( $hour_arr[$i] ) ) {
        $class = ' class="hasevents"';
      }

      if ( $rowspan_day[$u][$d] > 1 ) {
        // this might mean there's an overlap, or it could mean one event
        // ends at 11:15 and another starts at 11:30.
        if ( !empty ( $hour_arr[$i] ) ) {
          echo '<td' .  $class . '>' . $hour_arr[$i]. "</td>\n";
        }
        $rowspan_day[$u][$d]--;
      } else {
        if ( empty ( $hour_arr[$i] ) ) {
          echo '<td' . $class . ( $can_add
            ? " ondblclick=\"dblclick( '$dateYmd', '$viewusers[$u]', '$time_h',"
              . " '$time_m' )\"" : '' ) . ">&nbsp;</td>\n";
        } else {
          $rowspan_day[$u][$d] = $save_rowspan_arr[$u][$d][$i];
          echo "<td $class " . ( $rowspan_day[$u][$d] > 1
            ? 'rowspan="' . $rowspan_day[$u][$d] . '"' : '' )
           . ( $can_add ? "ondblclick=\"dblclick( '$dateYmd', '$user', "
           . "'$time_h', '$time_m' )\">" : '>' ) . $hour_arr[$i] . "</td>\n";
        }
      }
    }
  }
  echo "</tr>\n";
}

?>

</table>
<script>
<!-- <![CDATA[
function dblclick( date, name, hour, minute ) {
 window.location.href  = 'edit_entry.php?date=' + date + '&defusers=' + name
   + ( hour ? '&hour=' + hour + '&minute='
     + ( minute ? minute : 0 ) : '&duration=1440' );
}
//]]> -->
</script>
<?php

$user = ''; // reset

echo ( empty( $eventinfo ) ? '' : $eventinfo ) . $printerStr . print_trailer();

ob_end_flush();

?>


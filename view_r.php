<?php
/*
 * $Id$
 *
 * Page Description:
 *	This is the "Week by Time" and "Week by Day" view.
 *	This view will show either a week's worth of events (type='R')
 *	or a single day of events (type='E')
 *	using a format with days across the top of the table and time
 *	showing down the left side.  (This is just like the standard
 *	layout of day.php and week.php.)
 *	However, each cell will be subdivided into
 *	however many users are part of this view.
 *
 * Input Parameters:
 *	id (*) - specify view id in webcal_view table
 *	date - specify the starting date of the view.
 *	  If not specified, current date will be used.
 *	friendly - if set to 1, then page does not include links or
 *	  trailer navigation.
 *	(*) required field
 *
 * Comments:
 *	The week view of this page will only show weekends if the
 *	user has set "display weekends in week view" in their
 *	user preferences.
 *
 *	The week version of this page has the potential to contain
 *	a large table.  The layout will be skewed to try and fit this
 *	into a page.
 *	If you want to allow the table to grow larger than the viewable
 *	area in the browser, set the $fit_to_window_week to be false below.
 *	(You can do the same for the day view with $fit_to_window_day.)
 *
 *	Should we make this an option when creating/updating the view?
 *	If we did make this an option in the UI, we would need to either:
 *	(A) add a column to the webcal_view table
 *	(B) use different view types for this option ('E' and 'R' for
 *	    fit-to-window, 'G' and 'X' for expand?)
 *
 * Security:
 *	Must have "allow view others" enabled ($allow_view_other) in
 *	  System Settings unless the user is an admin user ($is_admin).
 *	If the view is not global, the user must be owner of the view.
 *	If the view is global, and user_sees_only_his_groups is
 *	enabled, then we remove users not in this user's groups
 *	(except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = "";
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
// FYI, the add-event link defaults to "all-day event" rather than
// an untimed event.
$show_untimed_row_always = true;

view_init ( $id );

// view type 'R' is for week view, 'S' is for day view
$is_day_view = ( $view_type == 'E' );
$col_pixels = ( $is_day_view ? $col_pixels_day : $col_pixels_week );
$fit_to_window = ( $is_day_view ? $fit_to_window_day : $fit_to_window_week );
$show_time = ( $is_day_view ? $show_time_day : $show_time_week );

$INC = array ( 'js/popups.php' );
print_header ( $INC );

set_today ( $date );

$thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );

if ( $is_day_view )
  $next = mktime ( 0, 0, 0, $thismonth, $thisday + 1, $thisyear );
else
  $next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

if ( $is_day_view )
  $prev = mktime ( 0, 0, 0, $thismonth, $thisday - 1, $thisyear );
else
  $prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );


if ( $WEEK_START == 1 ) {
   $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
} else {
   $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
}
$wkend = $wkstart + ( 3600 * 24 * 6 );

if ( ! $fit_to_window )
  $time_w = "100px";
else
  $time_w = "8%"; // 8% for time column

// Set the day of week range (0=Sun, 6=Sat)
// $start_ind = start of range
// $end_ind = end of range (inclusive)
// $startdate = YYYYMMDD format of first day to display
// $enddate = YYYYMMDD format of last day to display
if ( $is_day_view ) {
  $startdate = $enddate = $thisdate;
  $thistime = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
  $start_ind = $end_ind = ( date ( 'w', $thistime ) - $WEEK_START + 7 ) % 7;
} else {
  $startdate = date ( "Ymd", $wkstart );
  $enddate = date ( "Ymd", $wkend );
  if ( $DISPLAY_WEEKENDS == "N" ) {
    if ( $WEEK_START == 1 ) {
      $start_ind = 0;
      $end_ind = 5;
    } else {
      $start_ind = 1;
      $end_ind = 5;
    }
    // Hidden setting that always forces Saturday to be displayed
    if ( ! empty ( $VIEW_R_INCLUDE_SAT ) &&
      $VIEW_R_INCLUDE_SAT == 'Y' ) {
      $end_ind = 6;
    }
  } else {
    $start_ind = 0;
    $end_ind = 6;
  }
}

//echo "startdate=$startdate, enddate=$enddate, start_ind=$start_ind, end_ind=$end_ind<br/>\n";


// Generate the column headers for each day and the unix datetime
// values for each date.
for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<br />" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
  if ( empty ( $first_date ) )
    $first_date = date_to_str ( date ( "Ymd", $days[$i] ), false );
  $last_date = date_to_str ( date ( "Ymd", $days[$i] ), false );
}


// The table has dates across the top and times for rows.  Since we need
// to spit out an entire row before we can move to the next time slot, we'll
// save up all the HTML for each cell and then print it out when we're
// done.

$viewusers = view_get_user_list ( $id );
//echo "<pre>"; print_r ( $viewusers ); echo "</pre>\n";


// Make sure we have at least one user in our view.
// If this is a global view, we may have removed all the users if
// the current user does not have permission to view any of the
// users in the view.
// In theory, we whould always at least have ourselves in the view, right?
if ( count ( $viewusers ) == 0 ) {
  // I don't think we need to translate this.
  $error = "No users for this view";
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

// table_width = width in pixels of entire table
// col_pixels = width of smallest column
// 100 = width of time column on left of table
if ( ! $fit_to_window ) {
  $table_width = ( $col_pixels * count ( $viewusers ) ) *
    ( $end_ind - $start_ind + 1 ) + 100;
}
//echo "table_width=$table_width<br>\n";

// tdw is the cell width for each day
if ( ! $fit_to_window )
  // pixels
  $tdw = floor ( ( $table_width - $time_w ) / ( $end_ind - $start_ind + 1 ) );
else
  // %
  $tdw = floor ( ( 100 - $time_w ) / ( $end_ind - $start_ind + 1 ) );

$untimed_found = false;
$get_unapproved = ( $GLOBALS["DISPLAY_UNAPPROVED"] == "Y" );

// Step through each user and load events for that user.
// Store in $e_save[] (normal events) and $re_save[] (repeating events).
$e_save = array ();
$re_save = array ();
if ( ! $fit_to_window )
  $uwf = $col_pixels . "px";
else
  $uwf = sprintf ( "%0.2f", $tdw / count ( $viewusers ) ) . "%";
$uheader = "";
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $startdate );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
  user_load_variables ( $viewusers[$i], "temp" );
  $uheader .= "<th class=\"small\" width=\"$uwf\" style=\"width:$uwf;\">" .
    $tempfullname . "</th>\n";
  //echo "$viewusers[$i]: loaded " . count ( $events ) . " events<br>\n";
}
$num_users = count ( $viewusers );

// $TIME_SLOTS is set in both admin system settings and user preferences.
if ( empty ( $TIME_SLOTS ) )
  $TIME_SLOTS = 24;
$interval = ( 24 * 60 ) / $TIME_SLOTS;
$first_slot = (int)( ( ( $WORK_DAY_START_HOUR  ) * 60 ) /
  $interval );
$last_slot = (int)( ( ( $WORK_DAY_END_HOUR ) * 60 ) /
  $interval );

?>


<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" href="view_r.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="leftarrow.gif" alt="<?php etranslate("Previous")?>" /></a>

<a title="<?php etranslate("Next")?>" class="next" href="view_r.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php
  if ( $is_day_view ) {
    echo date_to_str ( date ( "Ymd", $thistime ), false );
  } else {
    echo $first_date . "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
      $last_date;
  }
?></span><br />
<span class="viewname"><?php echo $view_name ?></span>
<?php
  if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
    echo "<br />\n<span class=\"weeknumber\">(" .
      translate("Week") . " " . week_number ( $wkstart ) . ")</span>";
  }
?>
</div></div><br />

<?php
if ( $can_add ) {
  $help = 'title="' .
    translate ( "Double-click on empty cell to add new entry" ) . '"';
} else {
  $help = '';
}
?>

<?php if ( ! $fit_to_window ) { ?>
<table <?php echo $help;?> class="main" cellspacing="0" cellpadding="0"
  style="width:<?php echo $table_width;?>px;" width="<?php echo $table_width;?>">
<?php } else { ?>
<table <?php echo $help;?> class="main" cellspacing="0" cellpadding="0" width="100%">
<?php } ?>

<!-- table header -->
<tr><th class="empty" width="<?php echo $time_w;?>" style="width:<?php echo $time_w;?>;">&nbsp;</th>
<?php
  // heading row that displays day of week and date
  if ( ! $fit_to_window )
    $tdwf = ( $col_pixels * count ( $viewusers ) ) . "px";
  else
    $tdwf = sprintf ( "%0.2f", $tdw ) . "%";
  $todayYmd = date ( "Ymd", $today );
  for ( $i = $start_ind; $i <= $end_ind; $i++ ) {
    $weekday = weekday_short_name ( date ( "w", $days[$i] ) );
    if ( $todayYmd == date ( "Ymd", $days[$i] ) ) {
      echo "<th class=\"today\" style=\"width:$tdwf;\" colspan=\"$num_users\">";
    } else {
      echo "<th style=\"width:$tdwf;\" colspan=\"$num_users\">";
    }
    echo $header[$i];
    echo "</th>\n";
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

$all_day = array (  );

//<long-winded-explanation>
// We loop through the events once checking for the start time.  If we
// find a start time before the normal work hours, we will reset $first_slot
// to this new time.  We do this in a separate loop because all-day events
// will assume a start time slot of the beginning of normal work hours.
// So, if there is an all-day event on Monday, it might use the first_slot
// that represents 8am only to find an event on Thu has a time of 7am which
// would change the first_slot value.  There is then a gap above the all-day
// event.
//</long-winded-explanation>
$am_part = array ( ); // am I a participant array
for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  for ( $u = 0; $u < count ( $viewusers ); $u++ ) {
    $untimed = array (  );
    $user = $viewusers[$u];
    $events = $e_save[$u];
    $repeated_events = $re_save[$u];
    // get all the repeating events for this date and store in array $rep
    $adate = date ( "Ymd", $days[$d] );
    $rep = get_repeating_entries ( $user, $adate );
    for ( $j = 0; $j < count ( $rep ); $j++ ) {
      if ( ! isset ( $am_part[$rep[$j]->getID()] ) ) {
        $am_part[$rep[$j]->getID()] =
          user_is_participant ( $rep[$j]->getID(), $login );
      }
      if ( $get_unapproved || $rep[$j]->getStatus() == 'A' ) {
        if ( $rep[$j]->getDuration() > 0 &&
          $rep[$j]->getDuration() != 24 * 60 ) {
          $slot = calc_time_slot ( $rep[$j]->getTime(), false );
          if ( $slot < $first_slot ) {
            $first_slot = $slot;
          }
        }
      }
    }
    $ev = get_entries ( $user, $adate, $get_unapproved , 1, 1);
    for ( $j = 0; $j < count ( $ev ); $j++ ) {
      if ( ! isset ( $am_part[$ev[$j]->getID()] ) ) {
        $am_part[$ev[$j]->getID()] =
          user_is_participant ( $ev[$j]->getID(), $login );
      }
      if ( $ev[$j]->getDuration() > 0 &&
        $ev[$j]->getDuration() != 24 * 60 ) {
        $slot = calc_time_slot ( $ev[$j]->getTime(), false );
        if ( $slot < $first_slot ) {
          $first_slot = $slot;
        }
      }
    }
  }
}

for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
  for ( $u = 0; $u < count ( $viewusers ); $u++ ) {
    $untimed = array (  );
    $user = $viewusers[$u];
    $events = $e_save[$u];
    $repeated_events = $re_save[$u];
    // get all the repeating events for this date and store in array $rep
    $adate = date ( "Ymd", $days[$d] );
    $rep = get_repeating_entries ( $user, $adate );
    $cur_rep = 0;

    // Get static non-repeating events
    $ev = get_entries ( $user, $adate, $get_unapproved, 1, 1 );
    $hour_arr = array (  );
    $rowspan_arr = array (  );
    for ( $i = 0; $i < count ( $ev ); $i++ ) {
      // print out any repeating events that are before this one...
      while ( $cur_rep < count ( $rep ) &&
        $rep[$cur_rep]->getTime() < $ev[$i]->getTime() ) {
        if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
          if ( $rep[$cur_rep]->getDuration() == ( 24 * 60 ) )
            $all_day[$d] = 1;
          html_for_event_week_at_a_glance ( $rep[$cur_rep], $adate, "small", $show_time );
        }
        $cur_rep++;
      }
      if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
        if ( $ev[$i]->getDuration() == ( 24 * 60 ) )
          $all_day[$d] = 1;
        html_for_event_week_at_a_glance ( $ev[$i], $adate, "small", $show_time );
        //echo "Found event date=$adate name='$viewname'<br>\n";
        //print_r ( $rowspan_arr );
      }
    }
    // print out any remaining repeating events
    while ( $cur_rep < count ( $rep ) ) {
      if ( $get_unapproved || $rep[$cur_rep]->getStatus() == 'A' ) {
        if ( $rep[$cur_rep]->getDuration() == ( 24 * 60 ) )
          $all_day[$d] = 1;
        html_for_event_week_at_a_glance ( $rep[$cur_rep], $adate, "small", $show_time );
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
            $hour_arr[$last_row] .= "<br/>\n";
          $hour_arr[$last_row] .= $hour_arr[$i];
          $hour_arr[$i] = "";
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
  echo "<tr><th class=\"empty\" width=\"$time_w\" style=\"width:$time_w;\">&nbsp;</th>\n";
  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );

    $is_weekend = ( $thiswday == 0 || $thiswday == 6 );

    for ( $u = 0; $u < count ( $viewusers ); $u++ ) {
      $untimed = $save_untimed[$u][$d];

      echo "<td";

      $class = ( $is_weekend ) ? "weekend" : "";

      if ( date ( 'Ymd', $days[$d] ) == date ( 'Ymd', $today ) ) {
        if ( $class != "" )
          $class .= " ";
        $class .= "today";
      }
      // Use the class 'hasevents' for any hour block that has events
      // in it.
      if ( !empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) ) {
        $class = 'hasevents';
      }
      if ( ! empty ( $class ) )
        $class .= ' ';
      $class .= "small";

      if ( $class != "" ) {
        echo " class=\"$class\"";
      }
      if ( $can_add ) {
        $add_url = "edit_entry.php?date=" . date ( "Ymd", $days[$d] ) .
          "&amp;defusers=" . $viewusers[$u] . "&amp;duration=1440";
        echo " ondblclick='document.location.href=\"$add_url\"' ";
      }
      echo ">";

      if ( !empty ( $untimed[$d] ) && strlen ( $untimed[$d] ) ) {
        echo $untimed[$d];
      } else {
        echo "&nbsp;";
      }
      echo "</td>\n";
    }
  }
  echo "</tr>\n";
}

$rowspan_day = array (  );
for ( $u = 0; $u < count ( $viewusers ); $u++ ) {
  for ( $d = $start_ind; $d <= $end_ind; $d++ )
    $rowspan_day[$u][$d] = 0;
}

for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
  $time_h = ( int ) ( ( $i * $interval ) / 60 );
  $time_m = ( $i * $interval ) % 60;
  $time = display_time ( ( $time_h * 100 + $time_m ) * 100, 1 );
  echo "<tr>\n<th valign=\"top\" class=\"row\" width=\"$time_w" .
    "\">" . $time . "</th>\n";
  //echo "<tr>\n<th valign=\"top\">" .  $time . "</th>\n";

  for ( $d = $start_ind; $d <= $end_ind; $d++ ) {
    $thiswday = date ( "w", $days[$d] );

    for ( $u = 0; $u < count ( $viewusers ); $u++ ) {

      $hour_arr = $save_hour_arr[$u][$d];
      $rowspan_arr = $save_rowspan_arr[$u][$d];

      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      $class = $is_weekend ? "weekend" : "";
      if ( date ( 'Ymd', $days[$d] ) == date ( 'Ymd', $today ) ) {
        if ( $class != "" )
          $class .= " ";
        $class .= "today";
      }
      // Use the class 'hasevents' for any hour block that has events
      // in it.
      if ( !empty ( $hour_arr[$i] ) && strlen ( $hour_arr[$i] ) ) {
        $class = 'hasevents';
      }
  
      if ( $rowspan_day[$u][$d] > 1 ) {
        // this might mean there's an overlap, or it could mean one event
        // ends at 11:15 and another starts at 11:30.
        if ( !empty ( $hour_arr[$i] ) ) {
          echo "<td";
          echo ( empty ( $class ) ) ? "" : " class=\"$class\"";
          echo ">" . $hour_arr[$i]."</td>\n";
        }
        $rowspan_day[$u][$d]--;
      } else {
        if ( empty ( $hour_arr[$i] ) ) {
          echo "<td";
          echo ( empty ( $class ) ) ? "" : " class=\"$class\"";
          if ( $can_add ) {
            $add_url = "edit_entry.php?date=" . date ( "Ymd", $days[$d] ) .
              "&amp;defusers=" . $viewusers[$u] .
              "&amp;hour=$time_h&amp;minute=$time_m";
            echo " ondblclick='document.location.href=\"$add_url\"' ";
          }
          echo ">";
          //if ( $can_add ) {        //if user can add events...
          //  echo html_for_add_icon ( date ( "Ymd", $days[$d] ),
          //    $time_h, $time_m, $user );        //..then echo the add event icon
          //}
          echo "&nbsp;</td>\n";
        } else {
          $rowspan_day[$u][$d] = $save_rowspan_arr[$u][$d][$i];
          if ( $rowspan_day[$u][$d] > 1 ) {
            echo "<td";
            echo ( empty ( $class ) ) ? "" : " class=\"$class\"";
            echo " rowspan=\"" . $rowspan_day[$u][$d] . "\"";
            if ( $can_add ) {
              $add_url = "edit_entry.php?date=" . date ( "Ymd", $days[$d] ) .
                "&amp;defusers=$user" .
                "&amp;hour=$time_h&amp;minute=$time_m";
              echo " ondblclick='document.location.href=\"$add_url\"' ";
            }
            echo ">";
            //if ( $can_add ) {
            //  echo html_for_add_icon ( date ( "Ymd", $days[$d] ), $time_h,
            //    $time_m, $user );
            //}
            echo $hour_arr[$i]."</td>\n";
          } else {
            echo "<td";
            echo ( empty ( $class ) ) ? "" : " class=\"$class\"";
            if ( $can_add ) {
              $add_url = "edit_entry.php?date=" . date ( "Ymd", $days[$d] ) .
                "&amp;defusers=$user" .
                "&amp;hour=$time_h&amp;minute=$time_m";
              echo " ondblclick='document.location.href=\"$add_url\"' ";
            }
            echo ">";
            //if ( $can_add ) {
            //  echo html_for_add_icon ( date ( "Ymd", $days[$d] ), $time_h,
            //    $time_m, $user );
            //}
            echo $hour_arr[$i]."</td>\n";
          }
        }
      }
    }
  }
  echo "</tr>\n";
}



?>

</table>
<br/><br/>

<?php

$user = ""; // reset

if ( ! empty ( $eventinfo ) ) echo $eventinfo;

echo "<a title=\"" . translate("Generate printer-friendly version") . "\" class=\"printer\" href=\"view_r.php?id=$id&amp;date=$thisdate&amp;friendly=1\" " .
        "target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
        translate("Generate printer-friendly version") .
        "'\">[" . translate("Printer Friendly") . "]</a>\n";

print_trailer ();
?>
</body>
</html>

<?php
/*
 * $Id$
 *
 * Description:
 * Presents page to view an event with links to edit, delete
 * confirm, copy, add event
 *
 * Input Parameters:
 * id (*) - cal_id of requested event
 * date  - yyyymmdd format of requested event
 * user  - user to display
 * (*) required field
 */
include_once 'includes/init.php';
include_once 'includes/site_extras.php';

// make sure this user is allowed to look at this calendar.
$can_view = false;
$is_my_event = false;

if ( $is_admin || $is_nonuser_admin || $is_assistant ) {
  $can_view = true;
} 

$error = '';

if ( empty ( $id ) || $id <= 0 || ! is_numeric ( $id ) ) {
  $error = translate ( "Invalid entry id" ) . "."; 
}

if ( empty ( $error ) ) {
  // is this user a participant or the creator of the event?
  $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
    "webcal_entry_user WHERE webcal_entry.cal_id = " .
    "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
    "AND (webcal_entry.cal_create_by = '$login' " .
    "OR webcal_entry_user.cal_login = '$login')";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] > 0 ) {
      $can_view = true;
      $is_my_event = true;
    }
    dbi_free_result ( $res );
  }

  if ( ($login != "__public__") && ($public_access_others == "Y") ) {
    $can_view = true;
  }

  if ( ! $can_view ) {
    $check_group = false;
    // if not a participant in the event, must be allowed to look at
    // other user's calendar.
    if ( $login == "__public__" ) {
      if ( $public_access_others == "Y" ) {
        $check_group = true;
      }
    } else {
      if ( $allow_view_other == "Y" ) {
        $check_group = true;
      }
    }
    // If $check_group is true now, it means this user can look at the
    // event only if they are in the same group as some of the people in
    // the event.
    // This gets kind of tricky.  If there is a participant from a different
    // group, do we still show it?  For now, the answer is no.
    // This could be configurable somehow, but how many lines of text would
    // it need in the admin page to describe this scenario?  Would confuse
    // 99.9% of users.
    // In summary, make sure at least one event participant is in one of
    // this user's groups.
    $my_users = get_my_users ();
    if ( is_array ( $my_users ) ) {
      $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
        "webcal_entry_user WHERE webcal_entry.cal_id = " .
        "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
        "AND webcal_entry_user.cal_login IN ( ";
      for ( $i = 0; $i < count ( $my_users ); $i++ ) {
        if ( $i > 0 ) {
          $sql .= ", ";
        }
        $sql .= "'" . $my_users[$i]['cal_login'] . "'";
        }
      $sql .= " )";
      $res = dbi_query ( $sql );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row && $row[0] > 0 ) {
          $can_view = true;
        }
        dbi_free_result ( $res );
      }
    }
    // If we didn't indicate we need to check groups, then this user
    // can't view this event.
    if ( ! $check_group ) {
      $can_view = false;
    }
  }
}

// If they still cannot view, make sure they are not looking at a nonuser
// calendar event where the nonuser is the _only_ participant.
if ( empty ( $error ) && ! $can_view && ! empty ( $nonuser_enabled ) &&
  $nonuser_enabled == 'Y' ) {
  $nonusers = get_nonuser_cals ();
  $nonuser_lookup = array ();
  for ( $i = 0; $i < count ( $nonusers ); $i++ ) {
    $nonuser_lookup[$nonusers[$i]['cal_login']] = 1;
  }
  $sql = "SELECT cal_login FROM webcal_entry_user " .
    "WHERE cal_id = $id AND cal_status in ('A','W')";
  $res = dbi_query ( $sql );
  $found_nonuser_cal = false;
  $found_reg_user = false;
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $nonuser_lookup[$row[0]] ) ) {
        $found_nonuser_cal = true;
      } else {
        $found_reg_user = true;
      }
    }
    dbi_free_result ( $res );
  }
  // Does this event contain only nonuser calendars as participants?
  // If so, then grant access.
  if ( $found_nonuser_cal && ! $found_reg_user ) {
    $can_view = true;
  }
}
  
if ( empty ( $error ) && ! $can_view ) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $year ) ) {
  $thisyear = $year;
}
if ( ! empty ( $month ) ) {
  $thismonth = $month;
}
$pri[1] = translate("Low");
$pri[2] = translate("Medium");
$pri[3] = translate("High");

$unapproved = FALSE;

// Make sure this is not a continuation event.
// If it is, redirect the user to the original event.
$ext_id = -1;
if ( empty ( $error ) ) {
  $res = dbi_query ( "SELECT cal_ext_for_id FROM webcal_entry " .
    "WHERE cal_id = $id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ext_id = $row[0];
    }
    dbi_free_result ( $res );
  } else {
    // db error... ignore it, I guess.
  }
}
if ( $ext_id > 0 ) {
  $url = "view_entry.php?id=$ext_id";
  if ( $date != "" ) {
    $url .= "&amp;date=$date";
  }
  if ( $user != "" ) {
    $url .= "&amp;user=$user";
  }
  if ( $cat_id != "" ) {
    $url .= "&amp;cat_id=$cat_id";
  }
  do_redirect ( $url );
}

print_header();

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}
// Try to determine the event status.
$event_status = "";

if ( ! empty ( $user ) && $login != $user ) {
  // If viewing another user's calendar, check the status of the
  // event on their calendar (to see if it's deleted).
  $sql = "SELECT cal_status FROM webcal_entry_user " .
    "WHERE cal_login = '$user' AND cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
} else {
  // We are viewing event on user's own calendar, so check the
  // status on their own calendar.
  $sql = "SELECT cal_id, cal_status FROM webcal_entry_user " .
    "WHERE cal_login = '$login' AND cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $event_status = $row[1];
    dbi_free_result ( $res );
  }
}

// At this point, if we don't have the event status, then either
// this user is not viewing an event from his own calendar and not
// viewing an event from someone else's calendar.
// They probably got here from the search results page (or possibly
// by hand typing in the URL.)
// Check to make sure that it hasn't been deleted from everyone's
// calendar.
if ( empty ( $event_status ) ) {
  $sql = "SELECT cal_status FROM webcal_entry_user " .
    "WHERE cal_status <> 'D' ORDER BY cal_status";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
}

// If we have no event status yet, it must have been deleted.
if ( ( empty ( $event_status ) && ! $is_admin ) || ! $can_view ) {
  echo "<h2>" . 
    translate("Error") . "</h2>" . 
    translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}


// Load event info now.
$sql = "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
  "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
  "cal_name, cal_description FROM webcal_entry WHERE cal_id = $id";
$res = dbi_query ( $sql );
if ( ! $res ) {
  echo translate("Invalid entry id") . ": $id";
  exit;
}

$row = dbi_fetch_row ( $res );
if ( $row ) { 
  $create_by = $row[0];
  $orig_date = $row[1];
  $event_time = $row[2];
  $name = $row[9];
  $description = $row[10];
} else {
  echo "<h2>" . 
    translate("Error") . "</h2>" . 
    translate("Invalid entry id") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}

// Timezone Adjustments
if ( $event_time >= 0 && ! empty ( $TZ_OFFSET )  && $TZ_OFFSET != 0 ) { 
  // -1 = no time specified
  $adjusted_time = $event_time + $TZ_OFFSET * 10000;
  $year = substr($row[1],0,4);
  $month = substr($row[1],4,2);
  $day = substr($row[1],-2);
  if ( $adjusted_time > 240000 ) {
    $gmt = mktime ( 3, 0, 0, $month, $day, $year );
    $gmt += $ONE_DAY;
  } else if ( $adjusted_time < 0 ) {
    $gmt = mktime ( 3, 0, 0, $month, $day, $year );
    $gmt -= $ONE_DAY;
  }
}
// Set alterted date
$tz_date = ( ! empty ( $gmt ) ) ? date ( "Ymd", $gmt ) : $row[1];

// save date so the trailer links are for the same time period
$thisyear = (int) ( $tz_date / 10000 );
$thismonth = ( $tz_date / 100 ) % 100;
$thisday = $tz_date % 100;
$thistime = mktime ( 3, 0, 0, $thismonth, $thisday, $thisyear );
$thisdow = date ( "w", $thistime );

// $subject is used for mailto URLs
$subject = translate($application_name) . ": " . $name;
// Remove the '"' character since it causes some mailers to barf
$subject = str_replace ( "\"", "", $subject );
$subject = htmlspecialchars ( $subject );

$event_repeats = false;
// build info string for repeating events and end date
$sql = "SELECT cal_type, cal_end, cal_frequency, cal_days " .
  "FROM webcal_entry_repeats WHERE cal_id = $id";

$res = dbi_query ($sql);
$rep_str = '';
if ( $res ) {
  if ( $tmprow = dbi_fetch_row ( $res ) ) {
    $event_repeats = true;
    $cal_type = $tmprow[0];
    $cal_end = $tmprow[1];
    $cal_frequency = $tmprow[2];
    $cal_days = $tmprow[3];

    if ( $cal_end ) {
      $rep_str .= "&nbsp; - &nbsp;";
      $rep_str .= date_to_str ( $cal_end );
    }
    $rep_str .= "&nbsp;(" . translate("every") . " ";

    if ( $cal_frequency > 1 ) {
      switch ( $cal_frequency ) {
        case 2: $rep_str .= translate("2nd"); break;
        case 3: $rep_str .= translate("3rd"); break;
        case 4: $rep_str .= translate("4th"); break;
        case 5: $rep_str .= translate("5th"); break;
        case 12: if ( $cal_type == 'monthlyByDay' ||
                   $cal_type == 'monthlyByDayR' ) {
                   break;
                 }
        default: $rep_str .= $cal_frequency; break;
      }
    }
    $rep_str .= ' ';
    switch ($cal_type) {
      case "daily":
        $rep_str .= translate("Day");
        break;
      case "weekly": $rep_str .= translate("Week");
        for ($i=0; $i<=7; $i++) {
          if (substr($cal_days, $i, 1) == "y") {
            $rep_str .= ", " . weekday_short_name($i);
          }
        }
        break;
      case "monthlyByDay":
      case "monthlyByDayR":
        if ( $cal_frequency == 12 ) {
          $rep_str .= month_name ( $thismonth - 1 ) . " / ";
        } else {
          $rep_str .= translate("Month") . " / ";
        }
        $days_this_month = $thisyear % 4 == 0 ? $ldays_per_month[$thismonth] :
          $days_per_month[$thismonth];
        if ( $cal_type == 'monthlyByDay' ) {
          $dow1 = date ( "w", mktime ( 3, 0, 0, $thismonth, 1, $thisyear ) );
          $days_in_first_week = ( 7 - $dow1 );
          $whichWeek = ceil ( $thisday / 7 );
        } else {
          $whichWeek = floor ( ( $days_this_month - $thisday ) / 7 );
          $whichWeek++;
        }
        $rep_str .= ' ';
        switch ( $whichWeek ) {
          case 1:
            if ( $cal_type == 'monthlyByDay' )
              $rep_str .= translate ( "1st" );
            break;
          case 2:
            $rep_str .= translate ( "2nd" ); break;
          case 3:
            $rep_str .= translate ( "3rd" ); break;
          case 4:
            $rep_str .= translate ( "4th" ); break;
          case 5:
            $rep_str .= translate ( "5th" ); break;
        }
        if ( $cal_type == 'monthlyByDayR' )
          $rep_str .= " " . translate ( "last" );
        $rep_str .= ' ' . weekday_name ( $thisdow );
        break;
      case "monthlyByDate":
        $rep_str .= translate("Month") . "/" . translate("by date");
        break;
      case "yearly":
        $rep_str .= translate("Year");
        break;
    }
    $rep_str .= ")";
  } else
    $rep_str = "";
  dbi_free_result ( $res );
}
/* calculate end time */
if ( $event_time >= 0 && $row[5] > 0 )
  $end_str = "-" . display_time ( add_duration ( $row[2], $row[5] ) );
else
  $end_str = "";

// get the email adress of the creator of the entry
user_load_variables ( $create_by, "createby_" );
$email_addr = empty ( $createby_email ) ? '' : $createby_email;

// If confidential and not this user's event, then
// They cannot seem name or description.
//if ( $row[8] == "R" && ! $is_my_event && ! $is_admin ) {
if ( $row[8] == "R" && ! $is_my_event ) {
  $is_private = true;
  $name = "[" . translate("Confidential") . "]";
  $description = "[" . translate("Confidential") . "]";
} else {
  $is_private = false;
}

if ( $event_repeats && ! empty ( $date ) )
  $event_date = $date;
else
  $event_date = $row[1];

// TODO: don't let someone view another user's private entry
// by hand editing the URL.

// Get category Info
if ( $categories_enabled == "Y" ) {
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;  
  $sql = "SELECT cat_name FROM webcal_categories, webcal_entry_user " .
    "WHERE webcal_entry_user.cal_login = '$cat_owner' AND webcal_entry_user.cal_id = $id " .
    "AND webcal_entry_user.cal_category = webcal_categories.cat_id";
  $res2 = dbi_query ( $sql );
  if ( $res2 ) {
    $row2 = dbi_fetch_row ( $res2 );
    $category = $row2[0];
    dbi_free_result ( $res2 );
  }
}
?>
<h2><?php echo htmlspecialchars ( $name ); ?></h2>
<table style="border-width:0px;">
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Description")?>:</td><td>
 <?php
  if ( ! empty ( $allow_html_description ) &&
    $allow_html_description == 'Y' ) {
    $str = str_replace ( '&', '&amp;', $description );
    $str = str_replace ( '&amp;amp;', '&amp;', $str );
    // If there is no html found, then go ahead and replace
    // the line breaks ("\n") with the html break.
    if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
      // found some html...
      echo $str;
    } else {
      echo nl2br ( activate_urls ( $str ) );
    }
  } else {
    echo nl2br ( activate_urls ( htmlspecialchars ( $description ) ) );
  }
?></td></tr>

<?php if ( $event_status != 'A' && ! empty ( $event_status ) ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Status")?>:</td><td>
 <?php
     if ( $event_status == 'W' )
       etranslate("Waiting for approval");
     if ( $event_status == 'D' )
       etranslate("Deleted");
     else if ( $event_status == 'R' )
       etranslate("Rejected");
      ?>
</td></tr>
<?php } ?>

<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Date")?>:</td><td>
 <?php
  if ( $event_repeats ) {
    echo date_to_str ( $event_date );
  } else {
    echo date_to_str ( $row[1], "", true, false, ( $row[5] == ( 24 * 60 ) ? "" : $event_time ) );
  }
  ?>
</td></tr>
<?php if ( $event_repeats ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Repeat Type")?>:</td><td>
 <?php echo date_to_str ( $row[1], "", true, false, $event_time ) . $rep_str; ?>
</td></tr>
<?php } ?>
<?php if ( $event_time >= 0 ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Time")?>:</td><td>
 <?php
    if ( $row[5] == ( 24 * 60 ) ) {
      etranslate("All day event");
    } else {
      echo display_time ( $row[2] ) . $end_str;
    }
  ?>
</td></tr>
<?php } ?>
<?php if ( $row[5] > 0 && $row[5] != ( 24 * 60 ) ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Duration")?>:</td><td>
 <?php echo $row[5]; ?> <?php etranslate("minutes")?>
</td></tr>
<?php } ?>
<?php if ( $disable_priority_field != "Y" ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Priority")?>:</td><td>
 <?php echo $pri[$row[6]]; ?>
</td></tr>
<?php } ?>
<?php if ( $disable_access_field != "Y" ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Access")?>:</td><td>
 <?php echo ( $row[8] == "P" ) ? translate("Public") : translate("Confidential"); ?>
</td></tr>
<?php } ?>
<?php if ( $categories_enabled == "Y" && ! empty ( $category ) ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Category")?>:</td><td>
 <?php echo $category; ?>
</td></tr>
<?php } ?>
<?php
// Display who originally created event
// useful if assistant or Admin
$proxy_fullname = '';  
if ( !empty ( $DISPLAY_CREATED_BYPROXY ) && $DISPLAY_CREATED_BYPROXY == "Y" ) {
  $res = dbi_query ( "SELECT wu.cal_firstname, wu.cal_lastname " .
    "FROM webcal_user wu INNER JOIN webcal_entry_log wel ON wu.cal_login = wel.cal_login " .
    "WHERE wel.cal_entry_id = $id " .
    "AND wel.cal_type = 'C'" );
  if ( $res ) {
    $row3 = dbi_fetch_row ( $res ) ;
   $proxy_fullname = $row3[0] . " " . $row3[1];
   $proxy_fullname = ($createby_fullname == $proxy_fullname ? ""  :
      " ( by " . $proxy_fullname . " )");
  }
}

if ( $single_user == "N" ) {
  echo "<tr><td style=\"vertical-align:top; font-weight:bold;\">\n" . 
 translate("Created by") . ":</td><td>\n";
  if ( $is_private ) {
    echo "[" . translate("Confidential") . "]\n</td></tr>";
  } else {
    if ( strlen ( $email_addr ) ) {
      echo "<a href=\"mailto:$email_addr?subject=$subject\">" .
        ( $row[0] == "__public__" ? translate( "Public Access" ): $createby_fullname ) .
        "</a>$proxy_fullname\n</td></tr>";
    } else {
      echo ( $row[0] == "__public__" ? translate( "Public Access" ) : $createby_fullname ) .
        "$proxy_fullname\n</td></tr>";
    }
  }
}
?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Updated")?>:</td><td>
 <?php
    echo date_to_str ( $row[3] );
    echo " ";
    echo display_time ( $row[4] );
   ?>
</td></tr>
<?php
// load any site-specific fields and display them
$extras = get_site_extra_fields ( $id );
for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
    echo "<tr><td style=\"vertical-align:top; font-weight:bold;\">\n" .
      translate ( $site_extras[$i][1] ) .
      ":</td><td>\n";
    if ( $extra_type == $EXTRA_URL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo "<a href=\"" . $extras[$extra_name]['cal_data'] . "\">" .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == $EXTRA_EMAIL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo "<a href=\"mailto:" . $extras[$extra_name]['cal_data'] .
          "?subject=$subject\">" .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == $EXTRA_DATE ) {
      if ( $extras[$extra_name]['cal_date'] > 0 ) {
        echo date_to_str ( $extras[$extra_name]['cal_date'] );
      }
    } else if ( $extra_type == $EXTRA_TEXT ||
      $extra_type == $EXTRA_MULTILINETEXT ) {
      echo nl2br ( $extras[$extra_name]['cal_data'] );
    } else if ( $extra_type == $EXTRA_USER ) {
      echo $extras[$extra_name]['cal_data'];
    } else if ( $extra_type == $EXTRA_REMINDER ) {
      if ( $extras[$extra_name]['cal_remind'] <= 0 ) {
        etranslate ( "No" );
      } else {
        etranslate ( "Yes" );
        if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          echo date_to_str ( $extras[$extra_name]['cal_date'] );
        } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          $minutes = $extras[$extra_name]['cal_data'];
          $d = (int) ( $minutes / ( 24 * 60 ) );
          $minutes -= ( $d * 24 * 60 );
          $h = (int) ( $minutes / 60 );
          $minutes -= ( $h * 60 );
          if ( $d > 1 ) {
            echo $d . " " . translate("days") . " ";
          } else if ( $d == 1 ) {
            echo $d . " " . translate("day") . " ";
          }
          if ( $h > 1 ) {
            echo $h . " " . translate("hours") . " ";
          } else if ( $h == 1 ) {
            echo $h . " " . translate("hour") . " ";
          }
          if ( $minutes > 1 ) {
            echo $minutes . " " . translate("minutes");
          } else if ( $minutes == 1 ) {
            echo $minutes . " " . translate("minute");
          }
          echo " " . translate("before event" );
        }
      }
    } else if ( $extra_type == $EXTRA_SELECTLIST ) {
      echo $extras[$extra_name]['cal_data'];
    }
    echo "\n</td></tr>\n";
  }
}
?>

<?php // participants
// Only ask for participants if we are multi-user.
$allmails = array ();
$show_participants = ( $disable_participants_field != "Y" );
if ( $is_admin ) {
  $show_participants = true;
}
if ( $public_access == "Y" && $login == "__public__" &&
  ( $public_access_others != "Y" || $public_access_view_part == "N" ) ) {
  $show_participants = false;
}
if ( $single_user == "N" && $show_participants ) { ?>
  <tr><td style="vertical-align:top; font-weight:bold;">
  <?php etranslate("Participants")?>:</td><td>
  <?php
  if ( $is_private ) {
    echo "[" . translate("Confidential") . "]";
  } else {
    $sql = "SELECT cal_login, cal_status FROM webcal_entry_user " .
      "WHERE cal_id = $id";
    //echo "$sql\n";
    $res = dbi_query ( $sql );
    $first = 1;
    $num_app = $num_wait = $num_rej = 0;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $pname = $row[0];
        if ( $login == $row[0] && $row[1] == 'W' ) {
          $unapproved = TRUE;
        }
        if ( $row[1] == 'A' ) {
          $approved[$num_app++] = $pname;
        } else if ( $row[1] == 'W' ) {
          $waiting[$num_wait++] = $pname;
        } else if ( $row[1] == 'R' )  {
          $rejected[$num_rej++] = $pname;
        }
      }
      dbi_free_result ( $res );
    } else {
      echo translate ("Database error") . ": " . dbi_error() . "<br />\n";
    }
  }
  for ( $i = 0; $i < $num_app; $i++ ) {
    user_load_variables ( $approved[$i], "temp" );
    if ( strlen ( $tempemail ) ) {
      echo "<a href=\"mailto:" . $tempemail . "?subject=$subject\">" . 
        $tempfullname . "</a><br />\n";
      $allmails[] = $tempemail;
    } else {
      echo $tempfullname . "<br />\n";
    }
  }
  // show external users here...
  if ( ! empty ( $allow_external_users ) && $allow_external_users == "Y" ) {
    $external_users = event_get_external_users ( $id, 1 );
    $ext_users = explode ( "\n", $external_users );
    if ( is_array ( $ext_users ) ) {
      for ( $i = 0; $i < count( $ext_users ); $i++ ) {
        if ( ! empty ( $ext_users[$i] ) ) {
          echo $ext_users[$i] . " (" . translate("External User") . 
            ")<br />\n";
        }
      }
    }
  }
  for ( $i = 0; $i < $num_wait; $i++ ) {
    user_load_variables ( $waiting[$i], "temp" );
    if ( strlen ( $tempemail ) ) {
      echo "<br /><a href=\"mailto:" . $tempemail . "?subject=$subject\">" . 
        $tempfullname . "</a> (?)\n";
      $allmails[] = $tempemail;
    } else {
      echo "<br />" . $tempfullname . " (?)\n";
    }
  }
  for ( $i = 0; $i < $num_rej; $i++ ) {
    user_load_variables ( $rejected[$i], "temp" );
    if ( strlen ( $tempemail ) ) {
      echo "<br /><strike><a href=\"mailto:" . $tempemail .
        "?subject=$subject\">" . $tempfullname .
        "</a></strike> (" . translate("Rejected") . ")\n";
    } else {
      echo "<br /><strike>$tempfullname</strike> (" . 
        translate("Rejected") . ")\n";
    }
  }
?>
</td></tr>
<?php
 } // end participants
?>

</table>

<br /><?php

$rdate = "";
if ( $event_repeats ) {
  $rdate = "&amp;date=$event_date";
}

// Show a printer-friendly link
if ( empty ( $friendly ) ) {
  echo "<a title=\"" . 
    translate("Generate printer-friendly version") . "\" class=\"printer\" " .
    "href=\"view_entry.php?id=$id&amp;friendly=1$rdate\" " .
    "target=\"cal_printer_friendly\">" .
    translate("Printer Friendly") . "</a><br />\n";
}

if ( empty ( $event_status ) ) {
  // this only happens when an admin views a deleted event that he is
  // not a participant for.  Set to $event_status to "D" just to get
  // rid of all the edit/delete links below.
  $event_status = "D";
}

if ( $unapproved && $readonly == 'N' ) {
  echo "<a title=\"" . 
    translate("Approve/Confirm entry") . 
    "\" href=\"approve_entry.php?id=$id\" " .
    "onclick=\"return confirm('" . 
    translate("Approve this entry?") . "');\">" . 
    translate("Approve/Confirm entry") . "</a><br />\n";
  echo "<a title=\"" . 
    translate("Reject entry") . "\" href=\"reject_entry.php?id=$id\" " .
    "onclick=\"return confirm('" .
    translate("Reject this entry?") . "');\">" . 
    translate("Reject entry") . "</a><br />\n";
}

if ( ! empty ( $user ) && $login != $user ) {
  $u_url = "&amp;user=$user";
} else {
  $u_url = "";
}

$can_edit = ( $is_admin || $is_nonuser_admin && ($user == $create_by) || 
  ( $is_assistant && ! $is_private && ($user == $create_by) ) ||
  ( $readonly != "Y" && ( $login == $create_by || $single_user == "Y" ) ) );
if ( $public_access == "Y" && $login == "__public__" ) {
  $can_edit = false;
}
if ( $readonly == 'Y' ) {
  $can_edit = false;
}

// If approved, but event category not set (and user does not have permission
// to edit where they could also set the category), then allow them to
// set it through set_cat.php.
if ( empty ( $user ) && $categories_enabled == "Y" &&
  $readonly != "Y" && $is_my_event && $login != "__public__" &&
  $event_status != "D" && ! $can_edit )  {
  echo "<a title=\"" . 
    translate("Set category") . "\" class=\"nav\" " .
    "href=\"set_entry_cat.php?id=$id$rdate\">" .
    translate("Set category") . "</a><br />\n";
}

if ( $can_edit && $event_status != "D" ) {
  if ( $event_repeats ) {
    echo "<a title=\"" .
      translate("Edit repeating entry for all dates") . 
      "\" class=\"nav\" href=\"edit_entry.php?id=$id$u_url\">" . 
      translate("Edit repeating entry for all dates") . "</a><br />\n";
    // Don't allow override of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo "<a title=\"" .
        translate("Edit entry for this date") . "\" class=\"nav\" " . 
        "href=\"edit_entry.php?id=$id$u_url$rdate&amp;override=1\">" .
        translate("Edit entry for this date") . "</a><br />\n";
    }
    echo "<a title=\"" . 
      translate("Delete repeating event for all dates") . 
      "\" class=\"nav\" href=\"del_entry.php?id=$id$u_url&amp;override=1\" " .
      "onclick=\"return confirm('" . 
      translate("Are you sure you want to delete this entry?") . "\\n\\n" . 
      translate("This will delete this entry for all users.") . "');\">" . 
      translate("Delete repeating event for all dates") . "</a><br />\n";
    // Don't allow deletion of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo "<a title=\"" . 
        translate("Delete entry only for this date") . 
        "\" class=\"nav\" href=\"del_entry.php?id=$id$u_url$rdate&amp;override=1\" " .
        "onclick=\"return confirm('" .
        translate("Are you sure you want to delete this entry?") . "\\n\\n" . 
        translate("This will delete this entry for all users.") . "');\">" . 
        translate("Delete entry only for this date") . "</a><br />\n";
    }
  } else {
    echo "<a title=\"" .
      translate("Edit entry") . "\" class=\"nav\" " .
      "href=\"edit_entry.php?id=$id$u_url\">" .
      translate("Edit entry") . "</a><br />\n";
    echo "<a title=\"" . 
      translate("Delete entry") . "\" class=\"nav\" " .
      "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
       translate("Are you sure you want to delete this entry?") . "\\n\\n" . 
       translate("This will delete this entry for all users.") . "');\">" . 
       translate("Delete entry") . "</a><br />\n";
  }
  echo "<a title=\"" . 
    translate("Copy entry") . "\" class=\"nav\" " .
    "href=\"edit_entry.php?id=$id$u_url&amp;copy=1\">" . 
    translate("Copy entry") . "</a><br />\n";  
} elseif ( $readonly != "Y" && $is_my_event && $login != "__public__" &&
  $event_status != "D" )  {
  echo "<a title=\"" . 
    translate("Delete entry") . "\" class=\"nav\" " .
    "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
    translate("Are you sure you want to delete this entry?") . "\\n\\n" . 
    translate("This will delete the entry from your calendar.") . "');\">" . 
    translate("Delete entry") . "</a><br />\n";
  echo "<a title=\"" . 
    translate("Copy entry") . "\" class=\"nav\" " .
    "href=\"edit_entry.php?id=$id&amp;copy=1\">" . 
    translate("Copy entry") . "</a><br />\n";
}
if ( $readonly != "Y" && ! $is_my_event && ! $is_private && 
  $event_status != "D" && $login != "__public__" )  {
  echo "<a title=\"" . 
    translate("Add to My Calendar") . "\" class=\"nav\" " .
    "href=\"add_entry.php?id=$id\" onclick=\"return confirm('" . 
    translate("Do you want to add this entry to your calendar?") . "\\n\\n" . 
    translate("This will add the entry to your calendar.") . "');\">" . 
    translate("Add to My Calendar") . "</a><br />\n";
}

if ( count ( $allmails ) > 0 ) {
  echo "<a title=\"" . 
    translate("Email all participants") . "\" class=\"nav\" " .
    "href=\"mailto:" . implode ( ",", $allmails ) .
    "?subject=" . rawurlencode($subject) . "\">" . 
    translate("Email all participants") . "</a><br />\n";
}

$show_log = false;

if ( $is_admin ) {
  if ( empty ( $log ) ) {
    echo "<a title=\"" . 
      translate("Show activity log") . "\" class=\"nav\" " .
      "href=\"view_entry.php?id=$id&amp;log=1\">" . 
      translate("Show activity log") . "</a><br />\n";
  } else {
    echo "<a title=\"" . 
      translate("Hide activity log") . "\" class=\"nav\" " .
      "href=\"view_entry.php?id=$id\">" . 
       translate("Hide activity log") . "</a><br />\n";
    $show_log = true;
  }
}

if ( $show_log ) {
  echo "<h3>" . translate("Activity Log") . "</h3>\n";
  echo "<table class=\"embactlog\">\n";
  echo "<tr><th class=\"usr\">\n";
  echo translate("User") . "</th><th class=\"cal\">\n";
  echo translate("Calendar") . "</th><th class=\"date\">\n";
  echo translate("Date") . "/" . 
   translate("Time") . "</th><th class=\"action\">\n";
  echo translate("Action") . "\n</th></tr>\n";

  $res = dbi_query ( "SELECT cal_login, cal_user_cal, cal_type, " .
    "cal_date, cal_time " .
    "FROM webcal_entry_log WHERE cal_entry_id = $id " .
    "ORDER BY cal_log_id DESC" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      echo "<tr><td>\n";
      echo $row[0] . "</td><td>\n";
      echo $row[1] . "</td><td>\n" . 
        date_to_str ( $row[3] ) . "&nbsp;" .
        display_time ( $row[4] ) . "</td><td>\n";
      if ( $row[2] == $LOG_CREATE ) {
        etranslate("Event created");
      } else if ( $row[2] == $LOG_APPROVE ) {
        etranslate("Event approved");
      } else if ( $row[2] == $LOG_REJECT ) {
        etranslate("Event rejected");
      } else if ( $row[2] == $LOG_UPDATE ) {
        etranslate("Event updated");
      } else if ( $row[2] == $LOG_DELETE ) {
        etranslate("Event deleted");
      } else if ( $row[2] == $LOG_NOTIFICATION ) {
        etranslate("Notification sent");
      } else if ( $row[2] == $LOG_REMINDER ) {
        etranslate("Reminder sent");
      }
      echo "</td></tr>\n";
    }
    dbi_free_result ( $res );
  }
  echo "</table>\n";
}

if (! $is_private) {
  echo "<br /><form method=\"post\" name=\"exportform\" " .
    "action=\"export_handler.php\">\n";
  echo "<label for=\"exformat\">" . 
    translate("Export this entry to") . ":&nbsp;</label>\n";
  echo "<select name=\"format\" id=\"exformat\">\n";
  echo " <option value=\"ical\">iCalendar</option>\n";
  echo " <option value=\"vcal\">vCalendar</option>\n";
  echo " <option value=\"pilot-csv\">Pilot-datebook CSV (" . 
    translate("Palm Pilot") . ")</option>\n";
  echo " <option value=\"pilot-text\">Install-datebook (" . 
    translate("Palm Pilot") . ")</option>\n";
  echo "</select>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
  echo "<input type=\"submit\" value=\"" . 
    translate("Export") . "\" />\n";
  echo "</form>\n";
}
?>

<?php
 print_trailer ( empty ($friendly) );
?>
</body>
</html>

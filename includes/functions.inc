<?php



// determine which browser
// currently supported return values:
//      Mozilla (open source Mozilla 5.0) = "Mozilla/5"
//      Netscape (3.X, 4.X) = "Mozilla/[3,4]"
//      MSIE (4.X) = "MSIE 4"
function get_web_browser () {
  if ( ereg ( "MSIE [0-9]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "MSIE";
  if ( ereg ( "Mozilla/[234]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Netscape";
  if ( ereg ( "Mozilla/[5678]", getenv ( "HTTP_USER_AGENT" ) ) )
    return "Mozilla";
  return "Uknown";
}


// log a debug message
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  3, "/tmp/webcal-debug.log" );
  //error_log ( date ( "Y-m-d H:i:s" ) .  "> $msg\n",
  //  2, "sockieman:2000" );
}


// send a redirect to the specified page
function do_redirect ( $url ) {
  Header ( "Location: $url" );
  echo "<HTML><HEAD><TITLE>Redirect</TITLE></HEAD><BODY>" .
    "Please go <A HREF=\"" . $url . "\">here</A>.</BODY></HTML>.\n";
  exit;
}


// send an HTTP login request
function send_http_login () {
  global $lang_file;

  if ( strlen ( $lang_file ) ) {
    Header ( "WWW-Authenticate: Basic realm=\"" . translate("Title") . "\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<HTML><HEAD><TITLE>Unauthorized</TITLE></HEAD><BODY>\n" .
      "<H2>" . translate("Title") . "</H2>" .
      translate("You are not authorized") .
      "\n</BODY></HTML>\n";
  } else {
    Header ( "WWW-Authenticate: Basic realm=\"WebCalendar\"");
    Header ( "HTTP/1.0 401 Unauthorized" );
    echo "<HTML><HEAD><TITLE>Unauthorized</TITLE></HEAD><BODY>\n" .
      "<H2>WebCalendar</H2>" .
      "You are not authorized" .
      "\n</BODY></HTML>\n";
  }
  exit;
}


// Generate a cookie that saves the last calendar view (month, week, day)
// so we can return to this same page after a user edits/deletes/etc an
// event
function remember_this_view () {
  global $SCRIPT_FILENAME, $REQUEST_URI;
  SetCookie ( "webcalendar_last_view", $REQUEST_URI );
}

// Get the last page stored using above function.
// Return empty string if we don't know.
function get_last_view () {
  global $webcalendar_last_view;
  return $webcalendar_last_view;
}



// Load the current user's preferences as global variables.
function load_user_preferences () {
  global $login, $browser;

  $browser = get_web_browser ();

  // Note: default values are set in config.inc
  $res = dbi_query (
    "SELECT cal_setting, cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '$login'" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $setting = $row[0];
      $value = $row[1];
      $sys_setting = "sys_" . $setting;
      // save system defaults
      if ( ! empty ( $GLOBALS[$setting] ) )
        $GLOBALS["sys_" . $setting] = $GLOBALS[$setting];
      $GLOBALS[$setting] = $value;
    }
    dbi_free_result ( $res );
  }
}


// Get a preference setting for the specified user.  If no value is
// found in the db, then the system default setting will be returned.
// params:
//   $user - user login we are getting preference for
//   $setting - the name of the setting
function get_pref_setting ( $user, $setting ) {

  // set default
  if ( $GLOBALS["sys_" .$setting] == "" ) {
    // this could happen if the current user has not saved any pref. yet
    $ret = $GLOBALS[$setting];
  } else {
    $ret = $GLOBALS["sys_" .$setting];
  }

  $sql = "SELECT cal_value FROM webcal_user_pref " .
    "WHERE cal_login = '" . $user . "' AND " .
    "cal_setting = '" . $setting . "'";
  //echo "SQL: $sql <P>\n";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $ret = $row[0];
    dbi_free_result ( $res );
  }
  return $ret;
}


// Get browser-specified language preference
function get_browser_language () {
  global $HTTP_ACCEPT_LANGUAGE, $browser_languages;
  $ret = "";
  if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) == 0 )
    return "none";
  $langs = explode ( ",", $HTTP_ACCEPT_LANGUAGE );
  for ( $i = 0; $i < count ( $langs ); $i++ ) {
    $l = strtolower ( trim ( $langs[$i] ) );
    $ret .= "\"$l\" ";
    if ( isset ( $browser_languages[$l] ) ) {
      return $browser_languages[$l];
    }
  }
  //if ( strlen ( $HTTP_ACCEPT_LANGUAGE ) )
  //  return "none ($HTTP_ACCEPT_LANGUAGE not supported)";
  //else
    return "none";
}


// Load current user's layer info and stuff it into layer global variable.
function load_user_layers () {
  global $login;
  global $layers;
  global $LAYERS_STATUS;

  $layers = array ();

  if ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS != "N" ) {
    $res = dbi_query (
      "SELECT cal_layerid, cal_layeruser, cal_color, cal_dups " .
      "FROM webcal_user_layers " .
      "WHERE cal_login = '$login' ORDER BY cal_layerid" );
    if ( $res ) {
      $count = 0;
      while ( $row = dbi_fetch_row ( $res ) ) {
        $layers[$count] = array (
          "cal_layerid" => $row[0],
          "cal_layeruser" => $row[1],
          "cal_color" => $row[2],
          "cal_dups" => $row[3]
        );
        $count++;
      }
      dbi_free_result ( $res );
    }
  } else {
    //echo "Not loading!";
  }
}




// Build the HTML for the event popup (but don't print it yet since we
// don't want this HTML to go inside the table for the month).
function build_event_popup ( $divname, $user, $description, $time ) {
  global $login;
  $ret = "<DIV ID=\"" . $divname .
    "\" STYLE=\"position: absolute; z-index: 20; visibility: hidden; top: 0px; left: 0px;\">\n" .
    "<TABLE BORDER=\"0\" WIDTH=\"30%\"><TR><TD BGCOLOR=\"" .
    $GLOBALS["POPUP_FG"] . "\">\n" .
    "<TABLE BORDER=\"0\" WIDTH=\"100%\"><TR><TD BGCOLOR=\"" .
    $GLOBALS["POPUP_BG"] . "\">\n" .
    "<FONT COLOR=\"" . $GLOBALS["POPUP_FG"] . "\">";
  
  if ( $user != $login )
    $ret .= "<B>" . translate ("User") . ":</B> $user<BR>";
  if ( strlen ( $time ) )
    $ret .= "<B>" . translate ("Time") . ":</B> $time<BR>";
  $ret .= "<B>" . translate ("Description") . ":</B>\n";
  $ret .= htmlspecialchars ( $description );
  $ret .= "</FONT></TD></TR></TABLE>\n" .
    "</TD></TR></TABLE>\n" .
    "</DIV>\n";
  return $ret;
}



// Print out a date selection for use in a form.
// params:
//   $prefix - prefix to use in front of form element names
//   $date - currently selected date (in YYYYMMDD) format
function print_date_selection ( $prefix, $date ) {
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  echo "<SELECT NAME=\"" . $prefix . "day\">";
  for ( $i = 1; $i <= 31; $i++ )
    echo "<OPTION " . ( $i == $thisday ? " SELECTED" : "" ) . ">$i";
  echo "</SELECT>\n<SELECT NAME=\"" . $prefix . "month\">";
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    print "<OPTION VALUE=\"$i\"" .
      ( $i == $thismonth ? " SELECTED" : "" ) . ">$m";
  }
  echo "</SELECT>\n<SELECT NAME=\"" . $prefix . "year\">";
  for ( $i = -1; $i < 5; $i++ ) {
    $y = date ( "Y" ) + $i;
    print "<OPTION VALUE=\"$y\"" .
      ( $y == $thisyear ? " SELECTED" : "" ) . ">$y";
  }
  echo "</SELECT>\n";
  echo "<INPUT TYPE=\"button\" ONCLICK=\"selectDate('" .
    $prefix . "day','" . $prefix . "month','" . $prefix . "year',$date)\" VALUE=\"" .
    translate("Select") . "...\">";
}



// Print the HTML for one day's events in the month view.
// params:
//   $id - event id
//   $date - date (not used)
//   $time - time (in HHMMSS format)
//   $name - event name
//   $description - long description of event
//   $status - event status
//   $pri - event priority
//   $access - event access
//   $event_owner - user associated with this event
//   $hide_icons - hide icons to make printer-friendly
function print_entry ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner, $hide_icons ) {
  global $eventinfo, $login, $user;
  static $key = 0;
  
  global $layers;


  echo "<FONT SIZE=\"-1\">";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }

  if ( $pri == 3 ) echo "<B>";
  if ( ! $hide_icons ) {
    $divname = "eventinfo-$id-$key";
    $key++;
    echo "<A CLASS=\"$class\" HREF=\"view_entry.php?id=$id&date=$date";
    if ( strlen ( $user ) > 0 )
      echo "&user=" . $user;
    echo "\" onMouseOver=\"window.status='" . translate("View this entry") .
      "'; show(event, '$divname'); return true;\" onMouseOut=\"hide('$divname'); return true;\">";
    echo "<IMG SRC=\"circle.gif\" WIDTH=\"5\" HEIGHT=\"7\" ALT=\"view icon\" BORDER=\"0\">";
  }


  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    for($index = 0; $index < sizeof($layers); $index++)
    {
        if($layers[$index]['cal_layeruser'] == $event_owner)
        {
            echo("<FONT COLOR=\"" . $layers[$index]['cal_color'] . "\">");
        }
    }
  }


  $timestr = "";
  if ( $time >= 0 ) {
    if ( $GLOBALS["TIME_FORMAT"] == "24" ) {
      printf ( "%02d:%02d", $time / 10000, ( $time / 100 ) % 100 );
    } else {
      $h = ( (int) ( $time / 10000 ) ) % 12;
      if ( $h == 0 ) $h = 12;
      echo $h;
      $m = ( $time / 100 ) % 100;
      if ( $m > 0 )
        printf ( ":%02d", $m );
      echo ( (int) ( $time / 10000 ) ) < 12 ? translate("am") : translate("pm");
    }
    echo "&gt;";
    $timestr = display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $timestr .= " - " . display_time ( $end_time );
    }
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    echo "(" . translate("Private") . ")";

  else
  if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
    echo "(" . translate("Private") . ")";
  else
  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    echo htmlspecialchars ( $name );
    echo ("</FONT>");
  }

  else
    echo htmlspecialchars ( $name );

  echo "</A>";
  if ( $pri == 3 ) echo "</B>";
  echo "</FONT><BR>";
  if ( ! $hide_icons ) {
    if ( $login != $user && $access == 'R' && strlen ( $user ) )
      $eventinfo .= build_event_popup ( $divname, $event_owner,
        translate("This event is confidential"), "" );

    else
    if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
      $eventinfo .= build_event_popup ( $divname, $event_owner,
        translate("This event is confidential"), "" );

    else
      $eventinfo .= build_event_popup ( $divname, $event_owner,
        $description, $timestr );
  }
}



// Get any site-specific fields for an entry that are stored in the database.
// Return an array.
// params:
//   $eventid - unique event id
function get_site_extra_fields ( $eventid ) {
  $sql = "SELECT cal_name, cal_type, cal_date, cal_remind, cal_data " .
    "FROM webcal_site_extras " .
    "WHERE cal_id = $eventid";
  $res = dbi_query ( $sql );
  $extras = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      // save by cal_name (e.g. "URL")
      $extras[$row[0]] = array (
        "cal_name" => $row[0],
        "cal_type" => $row[1],
        "cal_date" => $row[2],
        "cal_remind" => $row[3],
        "cal_data" => $row[4]
      );
    }
    dbi_free_result ( $res );
  }

  return $extras;
}




// Read all the events for a user for the specified range of dates.
// This is only called once per page request to improve performance.
// All the events get loaded into the array $events sorted by
// time of day (not date).
// params:
//   $user - username
//   $startdate - start date range, inclusive (in YYYYMMDD format)
//   $enddate - end date range, inclusive (in YYYYMMDD format)
function read_events ( $user, $startdate, $enddate ) {
  global $login;
  global $layers;

  if ( $startdate == $enddate )
    $date_filter = " AND webcal_entry.cal_date = $startdate";
  else
    $date_filter = " AND webcal_entry.cal_date >= $startdate " .
      "AND webcal_entry.cal_date <= $enddate";

  return query_events ( $user, false, $date_filter );
}


// Get all the events for a specific date from the array of pre-loaded
// events (which was loaded all at once to improve performance).
// The returned events will be sorted by time of day.
// params:
//   $user - username
//   $date - date to get events for in YYYYMMDD format
function get_entries ( $user, $date ) {
  global $events;
  $n = 0;
  $ret = array ();

  for ( $i = 0; $i < count ( $events ); $i++ ) {
    if ( $events[$i]['cal_date'] == $date )
      $ret[$n++] = $events[$i];
  }

  return $ret;
}


// Read events visible to a user (including layers); return results
// in an array sorted by time of day.
// params:
//   $user - username
//   $want_repeated - true to get repeating events; false to get
//     non-repeating.
//   $date_filter - SQL phrase starting with AND, to be appended to
//     the WHERE clause.  May be empty string.

function query_events ( $user, $want_repeated, $date_filter ) {
  global $login;
  global $layers;
  $result = array ();
  $layers_byuser = array ();

  $sql = "SELECT webcal_entry.cal_name, webcal_entry.cal_description, "
    . "webcal_entry.cal_date, webcal_entry.cal_time, "
    . "webcal_entry.cal_id, webcal_entry.cal_priority, "
    . "webcal_entry.cal_access, webcal_entry.cal_duration, "
    . "webcal_entry_user.cal_status, "
    . "webcal_entry_user.cal_login ";
  if ( $want_repeated ) {
    $sql .= ", "
      . "webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
      . "webcal_entry_repeats.cal_frequency, webcal_entry_repeats.cal_days "
      . "FROM webcal_entry, webcal_entry_repeats, webcal_entry_user "
      . "WHERE webcal_entry.cal_id = webcal_entry_repeats.cal_id AND ";
  } else {
    $sql .= "FROM webcal_entry, webcal_entry_user WHERE ";
  }
  $sql .= "webcal_entry.cal_id = webcal_entry_user.cal_id ";

  if ( strlen ( $user ) > 0 )
    $sql .= "AND (webcal_entry_user.cal_login = '" . $user . "' ";

  if ( $user == $login && strlen ( $user ) > 0 ) {
    for ($index = 0; $index < sizeof($layers); $index++) {
      $layeruser = $layers[$index]['cal_layeruser'];

      $sql .= "OR webcal_entry_user.cal_login = '" . $layeruser . "' ";

      // while we are parsing the whole layers array, build ourselves
      // a new array that will help when we have to check for dups
      $layers_byuser["$layeruser"] = $layers[$index]['cal_dups'];
    }
  }
  if ( strlen ( $user ) > 0 )
    $sql .= ") ";
  $sql .= $date_filter;

  // now order the results by time and by entry id.
  $sql .= " ORDER BY webcal_entry.cal_time, webcal_entry.cal_id";

  //echo $sql;
  
  $res = dbi_query ( $sql );
  if ( $res ) {
    $i = 0;
    $checkdup_id = -1;
    $first_i_this_id = -1;

    while ( $row = dbi_fetch_row ( $res ) ) {

      if ($row[8] == 'R') {
        continue;  // don't show rejected ones
      }
      $item = array (
        "cal_name" => $row[0],
        "cal_description" => $row[1],
        "cal_date" => $row[2],
        "cal_time" => $row[3],
        "cal_id"   => $row[4],
        "cal_priority" => $row[5],
        "cal_access" => $row[6],
        "cal_duration" => $row[7],
        "cal_status" => $row[8],
        "cal_login" => $row[9]
        );
      if ( $want_repeated && ! empty ( $row[10] ) ) {
        $item['cal_type'] = empty ( $row[10] ) ? "" : $row[10];
        $item['cal_end'] = empty ( $row[11] ) ? "" : $row[11];
        $item['cal_frequency'] = empty ( $row[12] ) ? "" : $row[12];
        $item['cal_days'] = empty ( $row[13] ) ? "" : $row[13];
      }

      if ( $item['cal_id'] != $checkdup_id ) {
        $checkdup_id = $item['cal_id'];
        $first_i_this_id = $i;
      }

      if ( $item['cal_login'] == $user ) {
        // Insert this one before all other ones with this ID.
        my_array_splice ( $result, $first_i_this_id, 0, array($item) );
        $i++;

        if ($first_i_this_id + 1 < $i) {
          // There's another one with the same ID as the one we inserted.
          // Check for dup and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          if ($layers_byuser[$other_item['cal_login']] == 'N') {
            // NOTE: array_splice requires PHP4
            my_array_splice ( $result, $first_i_this_id + 1, 1, "" );
            $i--;
          }
        }
      }
      else {
        if ($i == $first_i_this_id
          || ( ! empty ( $layers_byuser[$item['cal_login']] ) &&
          $layers_byuser[$item['cal_login']] != 'N' ) ) {
          // This item either is the first one with its ID, or allows dups.
          // Add it to the end of the array.
          $result [$i++] = $item;
        }
      }
    }
    dbi_free_result ( $res );
  }
  return $result;
}

// Read all the repeated events for a user.  This is only called once
// per page request to improve performance.  All the events get loaded
// into the array $repeated_events sorted by time of day (not date).
// params:
//   $user - username
function read_repeated_events ( $user ) {
  global $login;
  global $layers;

  return query_events ( $user, true, "" );
}
//Returns all the dates a specific event will fall on accounting for
//the repeating.  Any event with no end will be assigned one.
//params:
//  $date - initial date in raw format
//  $rpt_type - repeating type as stored in the database
//  $end  - end date
//  $days - days events occurs on (for weekly)
//  $freq - frequency of repetition
function get_all_dates ( $date, $rpt_type, $end, $days, $freq=1 ) {
  global $conflict_repeat_months;
  $ONE_DAY = 86400;
  $currentdate = floor($date/$ONE_DAY)*$ONE_DAY;
  $realend = floor($end/$ONE_DAY)*$ONE_DAY;
  $dateYmd = date ( "Ymd", $date );
  if ($end=='NULL') {
    //This needs to be set in a preference I think.
    //But I'll just check up to 6 months from the start date.
    $thismonth = substr($dateYmd, 4, 2);
    $thisyear = substr($dateYmd, 0, 4);
    $thisday = substr($dateYmd, 6, 2);
    $thismonth += $conflict_repeat_months;
    if ($thismonth > 12) {
      $thisyear++;
      $thismonth -= 12;
    }
    $realend = mktime(3,0,0,$thismonth,$thisday,$thisyear);
  }
  $ret = array();
  $ret[0] = $date;
  //do iterative checking here.
  //I floored the $realend so I check it against the floored date
  if ($rpt_type && $currentdate < $realend) {
    $cdate = $date;
    if (!$freq) $freq = 1;
    $n = 1;
    if ($rpt_type == 'daily') {
      //we do inclusive counting on end dates.
      $cdate += $ONE_DAY * $freq;
      while ($cdate <= $realend+$ONE_DAY) {
        $ret[$n++]=$cdate;
        $cdate += $ONE_DAY * $freq;
      }
    } else if ($rpt_type == 'weekly') {
      $daysarray = array();
      $r=0;
      $dow = date("w",$date);
      $cdate = $date - ($dow * $ONE_DAY);
      for ($i = 0; $i < 7; $i++) {
        $isDay = substr($days, $i, 1);
        if (strcmp($isDay,"y")==0) {
          $daysarray[$r++]=$i * $ONE_DAY;
        }
      }
      //we do inclusive counting on end dates.
      while ($cdate <= $realend+$ONE_DAY) {
        //add all of the days of the week.
        for ($j=0; $j<$r;$j++) {
          $td = $cdate + $daysarray[$j];
          if ($td >= $date) {
            $ret[$n++] = $td;
          }
        }
        //skip to the next week in question.
        $cdate += ( $ONE_DAY * 7 ) * $freq;
      }
    } else if ($rpt_type == 'monthlyByDay') {
      $dow  = date('w', $date);
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $week  = floor(date("d", $date)/7);
      $thismonth+=$freq;
      $dow1 += date('w',mktime (0,0,0,$thismonth,1,$thisyear));
      $t = $dow - $dow1;
      if ($t < 0) $t += 7;
      $day = 7*$week + $t + 1;
      $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        $ret[$n++] = $cdate;
        $thismonth+=$freq;
        $dow1 += date('w',mktime (3,0,0,$thismonth,1,$thisyear));
        $t = $dow - $dow1;
        if ($t < 0) $t += 7;
        $day = 7*$week + $t + 1;
        $cdate = mktime (3,0,0,$thismonth,$day,$thisyear);
      }
    } else if ($rpt_type == 'monthlyByDate') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thismonth += $freq;
      $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        $ret[$n++] = $cdate;
        $thismonth += $freq;
        $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      }
    } else if ($rpt_type == 'yearly') {
      $thismonth = substr($dateYmd, 4, 2);
      $thisyear  = substr($dateYmd, 0, 4);
      $thisday   = substr($dateYmd, 6, 2);
      $hour      = date('H',$date);
      $minute    = date('i',$date);

      $thisyear += $freq;
      $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      while ($cdate <= $realend+$ONE_DAY) {
        $ret[$n++] = $cdate;
        $thisyear += $freq;
        $cdate = mktime (3,0,0,$thismonth,$thisday,$thisyear);
      }
    }
  }
  return $ret;
}
// Get all the repeating events for the specified data and return them
// in an array (which is sorted by time of day).
// params:
//   $user - username
//   $date - date to get events for in YYYYMMDD format
function get_repeating_entries ( $user, $dateYmd ) {
  global $repeated_events;
  $n = 0;
  $ret = array ();
  //echo count($repeated_events)."<BR>";
  for ($i=0; $i < count($repeated_events); $i++ ) {
    if (repeated_event_matches_date($repeated_events[$i],$dateYmd)){
      $ret[$n++] = $repeated_events[$i];
    }
  }
  return $ret;
}
//Returns a boolean stating whether or not the event passed
//in will fall on the date passed.
function repeated_event_matches_date($event,$dateYmd) {
  // only repeat after the beginning, and if there is an end
  // before the end
  $date = date_to_epoch ( $dateYmd );
  $thisyear = substr($dateYmd, 0, 4);
  $start = date_to_epoch ( $event['cal_date'] );
  $end   = date_to_epoch ( $event['cal_end'] );
  $freq = $event['cal_frequency'];
  $thismonth = substr($dateYmd, 4, 2);
  if ($event['cal_end'] && $dateYmd > date("Ymd",$end) )
    return false;
  if ( $dateYmd <= date("Ymd",$start) )
    return false;
  $id = $event['cal_id'];

  if ($event['cal_type'] == 'daily') {
    if ( (floor(($date - $start)/86400)%$freq) )
      return false;
    return true;
  } else if ($event['cal_type'] == 'weekly') {
    $dow  = date("w", $date);
    $dow1 = date("w", $start);
    $isDay = substr($event['cal_days'], $dow, 1);
    $wstart = $start - ($dow1 * 86400);
    if (floor(($date - $wstart)/604800)%$freq)
      return false;
    if (strcmp($isDay,"y") == 0) {
      return true;
    }
  } else if ($event['cal_type'] == 'monthlyByDay') {
    $dowS = date("w", $start);
    $dayS = ceil(date("d", $start)/7);
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);

    $dow  = date("w", $date);
    $day  = ceil(date("d", $date)/7);
    $mth  = date("m", $date);
    $yr   = date("Y", $date);

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    if (($dowS == $dow) && ($day == $dayS)) {
      return true;
    }

  } else if ($event['cal_type'] == 'monthlyByDate') {
    $mthS = date("m", $start);
    $yrS  = date("Y", $start);

    $mth  = date("m", $date);
    $yr   = date("Y", $date);

    if ((($yr - $yrS)*12 + $mth - $mthS) % $freq)
      return false;

    if (date("d", $date) == date("d", $start)) {
      return true;
    }
  }
  else if ($event['cal_type'] == 'yearly') {
    $yrS = date("Y", $start);
    $yr  = date("Y", $date);

    if (($yr - $yrS)%$freq)
      return false;

    if (date("dm", $date) == date("dm", $start)) {
      return true;
    }
  } else {
    // unknown repeat type
    return false;
  }
}
function date_to_epoch ( $d ) {
  return mktime ( 2, 0, 0, substr ( $d, 4, 2 ), substr ( $d, 6, 2 ),
    substr ( $d, 0, 4 ) );
}



// Get the Sunday of the week that the specified date is in.
// (If the date specified is a Sunday, then that date is returned.)
function get_sunday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 2, 0, 0, $month, $day, $year ) );
  $newdate = mktime ( 2, 0, 0, $month, $day - $weekday, $year );
  return $newdate;
}

// Get the Monday of the week that the specified date is in.
// (If the date specified is a Monday, then that date is returned.)
function get_monday_before ( $year, $month, $day ) {
  $weekday = date ( "w", mktime ( 2, 0, 0, $month, $day, $year ) );
  if ( $weekday == 0 )
    return mktime ( 2, 0, 0, $month, $day - 6, $year );
  if ( $weekday == 1 )
    return mktime ( 2, 0, 0, $month, $day, $year );
  return mktime ( 2, 0, 0, $month, $day - ( $weekday - 1 ), $year );
}


// Returns week number for specified date
// depending from week numbering settings.
// params:
//   $date - date in UNIX time format
function week_number ( $date ) {
  if ( $GLOBALS["WEEK_START"] == "1" )
    return strftime ( "%V", $date ); // ISO Weeks -- which start on Mondays
  else
    return strftime ( "%W", $date );
}



// This function is not yet used.  Some of the places that will call it
// have to be updated to also get the event owner so we know if the current
// user has access to edit and delete.
function icon_text ( $id, $can_edit, $can_delete ) {
  global $readonly, $is_admin;
  $ret = "<A HREF=\"view_entry.php?id=$id\">" .
    "<IMG SRC=\"view.gif\" ALT=\"View Entry\" BORDER=\"0\" " .
    "WIDTH=\"10\" HEIGHT=\"10\">" .
    "</A>";
  if ( $can_edit && ! $readonly )
    $ret .= "<A HREF=\"edit_entry.php?id=$id\">" .
      "<IMG SRC=\"edit.gif\" ALT=\"Edit Entry\" BORDER=\"0\" " .
      "WIDTH=\"10\" HEIGHT=\"10\">" .
      "</A>";
  if ( $can_delete && ( ! $readonly || $is_admin ) )
    $ret .= "<A HREF=\"del_entry.php?id=$id\" " .
      "onClick=\"return confirm('" .
      translate("Are you sure you want to delete this entry?") .
      "\\n\\n" . translate("This will delete this entry for all users.") .
      "');\">" .
      "<IMG SRC=\"delete.gif\" ALT=\"Delete Entry\" BORDER=\"0\" " .
      "WIDTH=\"10\" HEIGHT=\"10\">" .
      "</A>";
  return $ret;
}


//
// Print all the calendar entries for the specified user for the
// specified date.  If we are display data from someone other than
// the logged in user, then check the access permission of the entry.
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $hide_icons - hide icons to make printer-friendly
//   $is_ssi - is this being called from week_ssi.php?
function print_date_entries ( $date, $user, $hide_icons, $ssi ) {
  global $events, $readonly, $is_admin;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );
 
  $dateu = mktime ( 2, 0, 0, $month, $day, $year );

  if ( ! $hide_icons && ! $ssi && ( ! $readonly || $is_admin ) ) {
    print "<A HREF=\"edit_entry.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      print "user=$user&";
    print "date=$date\">" .
      "<IMG SRC=\"new.gif\" WIDTH=\"10\" HEIGHT=\"10\" ALT=\"" .
      translate("New Entry") . "\" BORDER=\"0\" ALIGN=\"right\">" .
      "</A>";
  }
  if ( ! $ssi ) {
    echo "<FONT SIZE=\"-1\"><A CLASS=\"dayofmonth\" HREF=\"day.php?";
    if ( strcmp ( $user, $GLOBALS["login"] ) )
      echo "user=$user&";
    echo "date=$date\">$day</A></FONT>";
    if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" &&
      date ( "w", $dateu ) == $GLOBALS["WEEK_START"] ) {
      echo "<A HREF=\"week.php?date=$date";
      if ( strcmp ( $user, $GLOBALS["login"] ) )
        echo "&user=$user";
       echo "\" CLASS=\"weeknumber\">";
      echo "<FONT SIZE=\"-2\">(" .
        translate("Week") . " " . week_number ( $dateu ) . ")</FONT></A>";
    }
    print "<BR>\n";
  }

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $user, $date );

  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
        $rep[$cur_rep]['cal_status'] == 'A' )
        print_entry ( $rep[$cur_rep]['cal_id'],
          $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
          $hide_icons );
      $cur_rep++;
    }
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $ev[$i]['cal_status'] == 'A' )
      print_entry ( $ev[$i]['cal_id'],
        $date, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_name'], $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_login'], $hide_icons );
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $rep[$cur_rep]['cal_status'] == 'A' )
      print_entry ( $rep[$cur_rep]['cal_id'],
        $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'],
        $hide_icons );
    $cur_rep++;
  }
}

//Find overlaps between an array of dates and the other dates in the database.
//$date is an array of dates in Ymd format that is check for overlaps.
//the $duration, $hour, and $minute are integers that show the time of the event
//which is shared among the dates.
//$particpants are those whose calendars are to be checked.
//$login is the current user name.
//$id is the current calendar entry being checked if it has been stored before
//(this keeps overlaps from wrongly checking an event against itself.
function overlap($dates,$duration,$hour,$minute,$participants,$login,$id) {
  global $single_user_login, $single_user;
  global $repeated_events;
  if (!count($dates)) return false;
  $sql = "SELECT distinct webcal_entry_user.cal_login, webcal_entry.cal_time," .
    "webcal_entry.cal_duration, webcal_entry.cal_name, " .
    "webcal_entry.cal_id, webcal_entry.cal_access, " .
    "webcal_entry_user.cal_status, webcal_entry.cal_date " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND (";
  for ($x = 0; $x < count($dates); $x++) {
    if ($x != 0) $sql .= " OR ";
    $sql.="webcal_entry.cal_date = " . date ( "Ymd", $dates[$x] );
  }
  $sql .=  ") AND webcal_entry.cal_time >= 0 " .
    "AND ( webcal_entry_user.cal_status = 'A' OR " .
    "webcal_entry_user.cal_status = 'W' ) " .
    "AND ( ";
  if ( $single_user ) {
     $participants[0] = $single_user_login;
  } else if ( strlen ( $participants[0] ) == 0 ) {
     // likely called from a form with 1 user
     $participants[0] = $login;
  }
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    if ( $i ) $sql .= " OR ";
    $sql .= " webcal_entry_user.cal_login = '" . $participants[$i] . "'";
  }
  $sql .= " )";
  // make sure we don't get something past the end date of the
  // event we are saving.
  //echo "SQL: $sql<P>";
  $overlap = "";
  $res = dbi_query ( $sql );
  $found = array();
  $count = 0;
  if ( $res ) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    while ( $row = dbi_fetch_row ( $res ) ) {
      //Add to an array to see if it has been found already for the next part.
      $found[$count++] = $row[4];
      // see if either event overlaps one another
      if ( $row[4] != $id ) {
        $time2 = $row[1];
        $duration2 = $row[2];
        if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $overlap .= "<LI>";
          if ( $single_user )
            $overlap .= "$row[0]: ";
          if ( $row[5] == 'R' && $row[0] != $login )
            $overlap .=  "(PRIVATE)";
          else {
            $overlap .=  "<A HREF=\"view_entry.php?id=$row[4]";
            if ( $user != $login )
              $overlap .= "&user=$user";
            $overlap .= "\">$row[3]</A>";
          }
          $overlap .= " (" . display_time ( $time2 );
          if ( $duration2 > 0 )
            $overlap .= "-" .
              display_time ( add_duration ( $time2, $duration2 ) );
          $overlap .= ")";
          $overlap .= " on " . date("l, F j, Y", date_to_epoch($row[7]));
        }
      }
    }
    dbi_free_result ( $res );
  }
  
      
  //echo "<br>hello";
  
  for ($q=0;$q<count($participants);$q++) {
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    $duration1 = sprintf ( "%d", $duration );
    //This date filter is not necessary for functional reasons, but it eliminates some of the
    //events that couldn't possibly match.  This could be made much more complex to put more
    //of the searching work onto the database server, or it could be dropped all together to put
    //the searching work onto the client.
    $date_filter  = "AND (webcal_entry.cal_date <= " . date("Ymd",$dates[count($dates)-1]);
    $date_filter .= " AND (webcal_entry_repeats.cal_end IS NULL OR webcal_entry_repeats.cal_end >= " . date("Ymd",$dates[0]) . "))";
    //Read repeated events for the participants only once for a participant for
    //for performance reasons.
    $repeated_events=query_events($participants[$q],true,$date_filter);
    //for ($dd=0; $dd<count($repeated_events); $dd++) {
    //  echo $repeated_events[$dd]['cal_id'] . "<BR>";
    //}
    for ($i=0; $i < count($dates); $i++) {
      $dateYmd = date ( "Ymd", $dates[$i] );
      $list = get_repeating_entries($participants[$q],$dateYmd);
      $thisyear = substr($dateYmd, 0, 4);
      $thismonth = substr($dateYmd, 4, 2);
      for ($j=0; $j < count($list);$j++) {
        //okay we've narrowed it down to a day, now I just gotta check the time...
        //I hope this is right...
        $row = $list[$j];
        if ( $row['cal_id'] != $id ) {
          $time2 = $row['cal_time'];
          $duration2 = $row['cal_duration'];
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $overlap .= "<LI>";
            if ( ! $single_user )
              $overlap .= $row['cal_login'] . ": ";
            if ( $row['cal_access'] == 'R' && $row['cal_login'] != $login )
              $overlap .=  "(PRIVATE)";
            else {
              $overlap .=  "<A HREF=\"view_entry.php?id=" . $row['cal_id'];
              if ( $user != $login )
                $overlap .= "&user=$user";
              $overlap .= "\">" . $row['cal_name'] . "</A>";
            }
            $overlap .= " (" . display_time ( $time2 );
            if ( $duration2 > 0 )
              $overlap .= "-" .
                display_time ( add_duration ( $time2, $duration2 ) );
            $overlap .= ")";
            $overlap .= " on " . date("l, F j, Y", $dates[$i]);
          }
        }
      }
    }
  }
   
  return $overlap;
}



// Generate the HTML for an event to be viewed in the week-at-glance.
// The HTML will be stored in an array ($hour_arr) indexed on the event's
// starting hour.
function html_for_event_week_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner,
  $hide_icons ) {
  global $first_hour, $last_hour, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user;
  static $key = 0;
  global $DISPLAY_ICONS;
  global $layers;

  $divname = "eventinfo-day-$id-$key";
  $key++;
  if ( $time >= 0 ) {
    $ind = (int) ( $time / 10000 );
    if ( $ind < $first_hour )
      $first_hour = $ind;
    if ( $ind > $last_hour )
      $last_hour = $ind;
  } else
    $ind = 99;


  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }


  // avoid php warning for undefined array index
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( ! $hide_icons ) {
    $hour_arr[$ind] .=
      "<A CLASS=\"$class\" HREF=\"view_entry.php?id=$id&date=$date";
    if ( strlen ( $GLOBALS["user"] ) > 0 )
      $hour_arr[$ind] .= "&user=" . $GLOBALS["user"];
    $hour_arr[$ind] .= "\" onMouseOver=\"window.status='" .
      translate("View this entry") .
      "'; show(event, '$divname'); return true;\" onMouseOut=\"hide('$divname'); return true;\">";
  }
  if ( $pri == 3 )
    $hour_arr[$ind] .= "<B>";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    for ( $index = 0; $index < sizeof($layers); $index++ ) {
      if ( $layers[$index]['cal_layeruser'] == $event_owner ) {
        $hour_arr[$ind] .= "<FONT COLOR=\"" .
          $layers[$index]['cal_color'] . "\">";
      }
    }
  }


  if ( $time >= 0 ) {
    $hour_arr[$ind] .= display_time ( $time ) . "&gt; ";
    $timestr = display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $timestr .= "-" . display_time ( $end_time );
      if ( empty ( $rowspan_arr[$ind] ) )
        $rowspan_arr[$ind] = 0; // avoid warning below
      if ( $m == 0 )
        $rowspan = $h - $ind;
      else
        $rowspan = $h - $ind + 1;
      if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
        $rowspan_arr[$ind] = $rowspan;
    }
  } else {
    $timestr = "";
  }

  // avoid php warning of undefined index when using .= below
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $hour_arr[$ind] .= "(Private)";
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    $hour_arr[$ind] .= "(Private)";
  } else if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
    $hour_arr[$ind] .= "</FONT>";
  } else {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  }

  if ( $pri == 3 ) $hour_arr[$ind] .= "</B>";
    $hour_arr[$ind] .= "</A>";
  //if ( $DISPLAY_ICONS == "Y" ) {
  //  $hour_arr[$ind] .= icon_text ( $id, true, true );
  //}
  $hour_arr[$ind] .= "<BR>";
  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $eventinfo .= build_event_popup ( $divname, $event_owner,
      translate("This event is confidential"), "" );
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    $eventinfo .= build_event_popup ( $divname, $event_owner,
      translate("This event is confidential"), "" );
  } else {
    $eventinfo .= build_event_popup ( $divname, $event_owner,
      $description, $timestr );
  }
}



// Generate the HTML for an event to be viewed in the day-at-glance.
// The HTML will be stored in an array ($hour_arr) indexed on the event's
// starting hour.
function html_for_event_day_at_a_glance ( $id, $date, $time,
  $name, $description, $status, $pri, $access, $duration, $event_owner, $hide_icons ) {
  global $first_hour, $last_hour, $hour_arr, $rowspan_arr, $rowspan,
    $eventinfo, $login, $user;
  static $key = 0;

  global $layers;

  $divname = "eventinfo-day-$id-$key";
  $key++;

  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    $eventinfo .= build_event_popup ( $divname, $event_owner,
      translate("This event is confidential"), "" );
  else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) )
    $eventinfo .= build_event_popup ( $divname, $event_owner,
      translate("This event is confidential"), "" );
  else
    $eventinfo .= build_event_popup ( $divname, $event_owner, $description, "" );

  if ( $time >= 0 ) {
    $ind = (int) ( $time / 10000 );
    if ( $ind < $first_hour )
      $first_hour = $ind;
    if ( $ind > $last_hour )
      $last_hour = $ind;
  } else
    $ind = 99;


  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = "";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" )
      $class = "unapprovedentry";
  }


  if ( ! $hide_icons ) {
    $hour_arr[$ind] .=
      "<A CLASS=\"$class\" HREF=\"view_entry.php?id=$id&date=$date";
    if ( strlen ( $GLOBALS["user"] ) > 0 )
      $hour_arr[$ind] .= "&user=" . $GLOBALS["user"];
    $hour_arr[$ind] .= "\" onMouseOver=\"window.status='" .
      translate("View this entry") .
      "'; show(event, '$divname'); return true;\" onMouseOut=\"hide('$divname'); return true;\">";
  }
  if ( $pri == 3 ) $hour_arr[$ind] .= "<B>";


  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    for ( $index = 0; $index < sizeof($layers); $index++) {
      if ( $layers[$index]['cal_layeruser'] == $event_owner) {
        $hour_arr[$ind] .= "<FONT COLOR=\"" .
          $layers[$index]['cal_color'] . "\">";
      }
    }
  }


  if ( $time >= 0 ) {
    $hour_arr[$ind] .= "[" . display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $hour_arr[$ind] .= "-" . display_time ( $end_time );
      if ( $m == 0 )
        $rowspan = $h - $ind;
      else
        $rowspan = $h - $ind + 1;
      if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
        $rowspan_arr[$ind] = $rowspan;
    }
    $hour_arr[$ind] .= "] ";
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) )
    $hour_arr[$ind] .= "(Private)";

  else
  if ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) )
    $hour_arr[$ind] .= "(Private)";
  else
  if ( $login != $event_owner && strlen ( $event_owner ) )
  {
    $hour_arr[$ind] .= htmlspecialchars ( $name );
    $hour_arr[$ind] .= "</FONT>";
  }


  else
    $hour_arr[$ind] .= htmlspecialchars ( $name );
  if ( $pri == 3 ) $hour_arr[$ind] .= "</B>";
  $hour_arr[$ind] .= "</A><BR>";
}

//
// Print all the calendar entries for the specified user for the
// specified date in day-at-a-glance format.
// If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// We output a two column format like:   time: event
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $hide_icons - should we hide the icons to make it printer-friendly
function print_day_at_a_glance ( $date, $user, $hide_icons ) {
  global $first_hour, $last_hour, $hour_arr, $rowspan_arr, $rowspan;
  global $CELLBG, $TODAYCELLBG, $THFG, $THBG;
  global $repeated_events;

  $rowspan_arr = array ();
  for ( $i = 0; $i < 24; $i++ ) {
    $rowspan_arr[$i] = 0;
  }

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // Get static non-repeating events
  $ev = get_entries ( $user, $date );
  $hour_arr = Array ();
  $first_hour = $GLOBALS["WORK_DAY_START_HOUR"];
  $last_hour = $GLOBALS["WORK_DAY_END_HOUR"];
  $rowspan_arr = Array ();
  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
        $rep[$cur_rep]['cal_status'] == 'A' )
        html_for_event_day_at_a_glance ( $rep[$cur_rep]['cal_id'],
          $date, $rep[$cur_rep]['cal_time'],
          $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_login'], $hide_icons );
      $cur_rep++;
    }
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $ev[$i]['cal_status'] == 'A' )
      html_for_event_day_at_a_glance ( $ev[$i]['cal_id'],
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
      html_for_event_day_at_a_glance ( $rep[$cur_rep]['cal_id'],
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
  if ( ! empty ( $hour_arr[99] ) ) {
    echo "<TR><TD HEIGHT=\"40\" BGCOLOR=\"$TODAYCELLBG\">&nbsp;</TD><TD VALIGN=\"top\" HEIGHT=\"40\" BGCOLOR=\"$TODAYCELLBG\">$hour_arr[99]</TD></TR>\n";
  }
  $rowspan = 0;
  for ( $i = $first_hour; $i <= $last_hour; $i++ ) {
    $time = display_time ( $i * 10000 );
    echo "<TR><TH VALIGN=\"top\" HEIGHT=\"40\" WIDTH=\"14%\" BGCOLOR=\"$THBG\">" .
      "<FONT COLOR=\"$THFG\">" .
      $time . "</FONT></TH>\n";
    if ( $rowspan > 1 ) {
      // this might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( strlen ( $hour_arr[$i] ) )
        echo "<TD VALIGN=\"top\" HEIGHT=\"40\" BGCOLOR=\"$TODAYCELLBG\">$hour_arr[$i]</TD>";
      $rowspan--;
    } else {
      if ( empty ( $hour_arr[$i] ) )
        echo "<TD HEIGHT=\"40\" BGCOLOR=\"$CELLBG\">&nbsp;</TD></TR>\n";
      else {
        $rowspan = $rowspan_arr[$i];
        if ( $rowspan > 1 )
          echo "<TD VALIGN=\"top\" BGCOLOR=\"$TODAYCELLBG\" ROWSPAN=\"$rowspan\">$hour_arr[$i]</TD></TR>\n";
        else
          echo "<TD VALIGN=\"top\" HEIGHT=\"40\" BGCOLOR=\"$TODAYCELLBG\">$hour_arr[$i]</TD></TR>\n";
      }
    }
  }
}


// display a link to any unapproved events
function display_unapproved_events ( $user ) {
  $sql = "SELECT COUNT(cal_id) FROM webcal_entry_user " .
    "WHERE cal_status = 'W' AND cal_login = '$user'";
  //print "SQL: $sql<BR>";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] > 0 )
        echo translate("You have") . " <A HREF=\"list_unapproved.php\">" .
          $row[0] . " " . translate("unapproved") . " " .
          ( $row[0] > 1 ? translate("events") : translate("event") ) .
          "</A><BR>\n";
    }
    dbi_free_result ( $res );
  }
}



// Look for URLs in the given text, and make them into links.
// params:
//   $text - input text
function activate_urls ( $text ) {
  $str = eregi_replace ( "(http://[[:alnum:].,=?+;-\/]+)",
    "<A HREF=\"\\1\">\\1</A>", $text );
  return $str;
}


// Display a time in either 12 or 24 hour format
// params:
//   $time - an interger like 235900
function display_time ( $time ) {
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  if ( $GLOBALS["TIME_FORMAT"] == "12" ) {
    $ampm = $hour >= 12 && $hour != 24 ? translate("pm") : translate("am");
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;
    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else {
    $ret = sprintf ( "%d:%02d", $hour, $min );
  }
  return $ret;
}


// Return the full month name
// params:
//   $m - month (0-11)
function month_name ( $m ) {
  switch ( $m ) {
    case 0: return translate("January");
    case 1: return translate("February");
    case 2: return translate("March");
    case 3: return translate("April");
    case 4: return translate("May_"); // needs to be different than "May"
    case 5: return translate("June");
    case 6: return translate("July");
    case 7: return translate("August");
    case 8: return translate("September");
    case 9: return translate("October");
    case 10: return translate("November");
    case 11: return translate("December");
  }
  return "unknown-month($m)";
}


// Return the abbreviated month name
// params:
//   $m - month (0-11)
function month_short_name ( $m ) {
  switch ( $m ) {
    case 0: return translate("Jan");
    case 1: return translate("Feb");
    case 2: return translate("Mar");
    case 3: return translate("Apr");
    case 4: return translate("May");
    case 5: return translate("Jun");
    case 6: return translate("Jul");
    case 7: return translate("Aug");
    case 8: return translate("Sep");
    case 9: return translate("Oct");
    case 10: return translate("Nov");
    case 11: return translate("Dec");
  }
  return "unknown-month($m)";
}


// Return the full weekday name
// params:
//   $w - weekday (0=Sunday,...,6=Saturday)
function weekday_name ( $w ) {
  switch ( $w ) {
    case 0: return translate("Sunday");
    case 1: return translate("Monday");
    case 2: return translate("Tuesday");
    case 3: return translate("Wednesday");
    case 4: return translate("Thursday");
    case 5: return translate("Friday");
    case 6: return translate("Saturday");
    case 7: return translate("July");
  }
  return "unknown-weekday($w)";
}

// Return the abbreviated weekday name
// params:
//   $w - weekday (0=Sun,...,6=Sat)
function weekday_short_name ( $w ) {
  switch ( $w ) {
    case 0: return translate("Sun");
    case 1: return translate("Mon");
    case 2: return translate("Tue");
    case 3: return translate("Wed");
    case 4: return translate("Thu");
    case 5: return translate("Fri");
    case 6: return translate("Sat");
    case 7: return translate("Jul");
  }
  return "unknown-weekday($w)";
}

// convert a date from an int format "19991231" into "Friday, December 31, 1999"
function date_to_str ( $indate ) {
  if ( strlen ( $indate ) == 0 ) {
    $indate = date ( "Ymd" );
  }
  $y = (int) ( $indate / 10000 );
  $m = (int) ( $indate / 100 ) % 100;
  $d = $indate % 100;
  $date = mktime ( 0, 0, 0, $m, $d, $y );
  $wday = strftime ( "%w", $date );
  return sprintf ( "%s, %s %d, %04d",
    weekday_name ( $wday ), month_name ( $m - 1 ), $d, $y );
}



// Define an array to use to jumble up the key
$offsets = array ( 31, 41, 59, 26, 54 );


function hextoint ( $val ) {
  if ( empty ( $val ) )
    return 0;
  switch ( strtoupper ( $val ) ) {
    case "0": return 0;
    case "1": return 1;
    case "2": return 2;
    case "3": return 3;
    case "4": return 4;
    case "5": return 5;
    case "6": return 6;
    case "7": return 7;
    case "8": return 8;
    case "9": return 9;
    case "A": return 10;
    case "B": return 11;
    case "C": return 12;
    case "D": return 13;
    case "E": return 14;
    case "F": return 15;
  }
  return 0;
}

// Extract a user's name from a session id
// This is a lame attempt at security.  Otherwise, users would be
// able to edit their cookies.txt file and set the username in plain
// text.
// $instr is a hex-encoded string. "Hello" would be "678ea786a5".
function decode_string ( $instr ) {
  global $offsets;
  //echo "<P>DECODE <BR>";
  $orig = "";
  for ( $i = 0; $i < strlen ( $instr ); $i += 2 ) {
    //echo "<P>";
    $ch1 = substr ( $instr, $i, 1 );
    $ch2 = substr ( $instr, $i + 1, 1 );
    $val = hextoint ( $ch1 ) * 16 + hextoint ( $ch2 );
    //echo "decoding \"" . $ch1 . $ch2 . "\" = $val <br>\n";
    $j = ( $i / 2 ) % count ( $offsets );
    //echo "Using offsets $j = " . $offsets[$j] . "<br>";
    $newval = $val - $offsets[$j] + 256;
    $newval %= 256;
    //echo " neval \"$newval\" <br>\n";
    $dec_ch = chr ( $newval );
    //echo " which is \"$dec_ch\" <br>\n";
    $orig .= $dec_ch;
  }
  return $orig;
}

// Take an input string and encoded it into a slightly encoded hexval
// that we can use as a session cookie.
function encode_string ( $instr ) {
  global $offsets;
  //echo "<P>ENCODE<BR>";
  $ret = "";
  for ( $i = 0; $i < strlen ( $instr ); $i++ ) {
    //echo "<P>";
    $ch1 = substr ( $instr, $i, 1 );
    $val = ord ( $ch1 );
    //echo "val = $val for \"$ch1\" <br>\n";
    $j = $i % count ( $offsets );
    //echo "Using offsets $j = $offsets[$j]<br>";
    $newval = $val + $offsets[$j];
    $newval %= 256;
    //echo "newval = $newval for \"$ch1\" <br>\n";
    $ret .= bin2hex ( chr ( $newval ) );
  }
  return $ret;
}

function user_external_auth ( $login, $password ) {
  $ret = exec ( escapeshellcmd ( "/usr/bin/ypmatch $login passwd" ) );
  if ( strlen ( $ret ) ) {
    $ret = explode ( ":", $ret );
    if ( $user_external_group && $user_external_group != $ret[3] ) {
      return "";
    }
    if ( strcmp ( $ret[1], crypt ( $password, substr ( $ret[1], 0, 2 ) ) ) ) {
      return "";
    }
    return $ret;
  }
  return "";
}



// an implementatin of array_splice() for PHP3
//   test cases:
//     insert an element
//       array_splice($array,$offset,0,array($item));
//     delete an element
//       array_splice($array,$offset,1);
function my_array_splice(&$input,$offset,$length,$replacement) {
  if ( floor(phpversion()) < 4 ) {
    // if offset is negative, then it starts at the end of array
    if ( $offset < 0 )
      $offset = count($input) + $offset;

    for ($i=0;$i<$offset;$i++) {
      $new_array[] = $input[$i];
    }

    // if we have a replacement, insert it
    for ($i=0;$i<count($replacement);$i++) {
      $new_array[] = $replacement[$i];
    }

    // now tack on the rest of the original array
    for ($i=$offset+$length;$i<count($input);$i++) {
      $new_array[] = $input[$i];
    }

    $input = $new_array;
  } else {
    array_splice($input,$offset,$length,$replacement);
  }
}



?>

<?php
/**
 * Lists a user's reports or displays a specific report.
 *
 * Input Parameters:
 * - <var>report_id</var> (optional) - specified report id in webcal_report
 *   table
 * - <var>offset</var> (optional) - specifies how many days/weeks/months +/- to
 *   display.  For example, if the report type is 1 (today) with offset=5, then
 *   the report will display 5 days from now.  Should only be specified if
 *   report_id is specified.  Will be ignored if specified report does not have
 *   the webcal_report.cal_allow_nav field set to 'Y'.
 * - <var>user</var> (optional) - specifies which user's calendar to use for
 *   the report.  This will be ignored if the chosen report is tied to a
 *   specific user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Reports
 *
 */

/*
 * Security:
 * If system setting $reports_enabled is set to anything other than
 *   'Y', then don't allow access to this page.
 * If webcal_report.cal_is_global is set to 'Y', any user can view
 *   the report.  If set to 'N', only the creator (set in
 *   webcal_report.cal_login) can view the report.
 * If webcal_report.cal_allow_nav is 'Y', then Next and Previous
 *   links will be presented.  If 'N', then they will not and the
 *   offset parameter will be ignored.
 * Public user cannot edit/list reports.
 *
 */

include_once 'includes/init.php';

/**
 * Replaces all site_extras placeholders in a template with the actual data
 *
 * All occurences of '${extra:ExtraName}' (where 'ExtraName' is the unique name
 * of a site_extra) will be replaced with that extra's data.
 *
 * @param string $template The template
 * @param array  $extras   The formatted site_extras as returned by
 *                         {@link format_site_extras()}
 *
 * @return string The template with site_extras replaced
 */
function replace_site_extras_in_template ( $template, $extras ) {
  $extra_names = get_site_extras_names();

  $ret = $template;

  foreach ( $extra_names as $extra_name ) {
    $replace_text = '${extra:' . $extra_name . '}';

    if ( empty ( $extras[$extra_name] ) ) {
      $ret = str_replace ( $replace_text, '', $ret );
    } else {
      $ret = str_replace ( $replace_text, $extras[$extra_name]['data'], $ret );
    }
  }

  return $ret;
}

/**
 * Generates the HTML for one event
 *
 * @param int    $id          Event id
 * @param int    $date        Date (YYYYMMDD)
 * @param int    $time        Time (in HHMMSS format)
 * @param int    $duration    Event duration (in minutes)
 * @param string $name        Event name
 * @param string $description Long description of event
 * @param string $status      Event status
 * @param string $pri         Event priority
 * @param string $access      Event access
 * @param string $event_owner User associated with this event
 *
 * @return string HTML for this event based on report template.
 */
function event_to_text ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner ) {
  global $login, $user, $event_template, $report_id, $allow_html_description;

  $time_str = $start_time_str = $end_time_str = '';

  if ( $duration == ( 24 * 60 ) ) {
    $time_str = translate("All day event");
  } else if ( $time == -1 ) {
    $time_str = translate("Untimed event");
  } else {
    $time_str = display_time ( $time );
    $start_time_str = $time_str;
    $time_short = preg_replace ("/(:00)/", '', $time_str);
    if ( $duration > 0 ) {
      if ( $duration == ( 24 * 60 ) ) {
        $time_str = translate("All day event");
      } else {
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
        $time_str .= " - " . display_time ( $end_time );
        $end_time_str = display_time ( $end_time );
      }
    }
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $name_str = "(" . translate("Private") . ")";
    $description_str = translate("This event is confidential");
  } else if ( $login != $event_owner && $access == 'R' &&
    strlen ( $event_owner ) ) {
    $name_str = "(" . translate("Private") . ")";
    $description_str = translate("This event is confidential");
  } else {
    $name_str = htmlspecialchars ( $name );
    if ( ! empty ( $allow_html_description ) &&
      $allow_html_description == 'Y' ) {
      $str = str_replace ( '&', '&amp;', $description );
      $description_str = str_replace ( '&amp;amp;', '&amp', $str );
      if ( strstr ( $description_str, "<" ) &&
        strstr ( $description_str, ">" ) ) {
        // found some HTML
      } else {
        // No HTML found.  Add line breaks.
        $description_str = nl2br ( $description_str );
      }
    } else {
      $description_str = nl2br (
        activate_urls ( htmlspecialchars ( $description ) ) );
    }
  }

  $date_str = date_to_str ( $date, "", false );
  $date_full_str = date_to_str ( $date, "", true, false );

  if ( $duration > 0 ) {
    $duration_str = $duration . ' ' . translate ( "minutes" );
  } else {
    $duration_str = '';
  }

  if ( $pri == 1 ) {
    $pri_str = translate ( "Low" );
  } else if ( $pri == 2 ) {
    $pri_str = translate ( "Medium" );
  } else if ( $pri == 3 ) {
    $pri_str = translate ( "High" );
  }

  if ( $status == 'W' ) {
    $status_str = translate ( "Waiting for approval" );
  } else if ( $status == 'D' ) {
    $status_str = translate ( "Deleted" );
  } else if ( $status == 'R' ) {
    $status_str = translate ( "Rejected" );
  } else if ( $status == 'A' ) {
    $status_str = translate ( "Approved" );
  } else {
    $status_str = translate ( "Unknown" );
  }

  $href_str = "view_entry.php?id=$id";

  // Replace all variables in the event template.
  $text = $event_template;
  $text = str_replace ( '${name}', $name_str, $text );
  $text = str_replace ( '${description}', $description_str, $text );
  $text = str_replace ( '${date}', $date_str, $text );
  $text = str_replace ( '${dateYmd}', $date, $text );
  $text = str_replace ( '${fulldate}', $date_full_str, $text );
  $text = str_replace ( '${time}', $time_str, $text );
  $text = str_replace ( '${starttime}', $start_time_str, $text );
  $text = str_replace ( '${endtime}', $end_time_str, $text );
  $text = str_replace ( '${duration}', $duration_str, $text );
  $text = str_replace ( '${priority}', $pri_str, $text );
  $text = str_replace ( '${href}', $href_str, $text );
  $text = str_replace ( '${id}', $id, $text );
  $text = str_replace ( '${user}', $event_owner, $text );
  $text = str_replace ( '${report_id}', $report_id, $text );

  $text = replace_site_extras_in_template ( $text,
                                            format_site_extras (
                                              get_site_extra_fields ( $id ) ) );

  return $text;
}

$error = "";
$list = ""; // list of reports when no id specified

if ( ! empty ( $user ) && $user != $login &&
  ( ( ! empty ( $allow_view_other ) && $allow_view_other == 'Y' )
  || $is_admin ) ) {
  $report_user = $user;
  $u_url = "&amp;user=$user";
} else {
  $u_url = "";
}

if ( empty ( $reports_enabled ) || $reports_enabled != 'Y' ) {
  $error = translate ( "You are not authorized" ) . ".";
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $report_user = "__public__";
}

$report_id = getIntValue ( "report_id", true );
$offset = getIntValue ( "offset", true );
if ( empty ( $offset ) ) {
  $offset = 0;
}

// If no report id is specified, then generate a list of reports for
// the user to select from.
if ( empty ( $error ) && empty ( $report_id ) && $login == "__public__" ) {
  $error = translate ( "You are not authorized" ) . ".";
}
if ( empty ( $error ) && empty ( $report_id ) ) {
  $list = "";
  if ( $is_admin ) {
    if ( ! $updating_public ) {
      $list .= "<p><a title=\"" .
        translate("Click here") . " " .
        translate("to manage reports for the Public Access calendar") . "." .
        "\" href=\"report.php?public=1\">" .
        translate("Click here") . " " .
        translate("to manage reports for the Public Access calendar") . "." .
        "</a></p>\n";
      $sql = "SELECT cal_report_id, cal_report_name " .
        "FROM webcal_report WHERE cal_login = '$login' OR " .
        "cal_is_global = 'Y' ORDER BY cal_update_date DESC, cal_report_name";
    } else {
      $sql = "SELECT cal_report_id, cal_report_name " .
        "FROM webcal_report WHERE cal_login = '__public__' " .
        "ORDER BY cal_update_date DESC, cal_report_name";
    }
  } else {
    $sql = "SELECT cal_report_id, cal_report_name " .
      "FROM webcal_report WHERE cal_login = '$login' " .
      "ORDER BY cal_update_date DESC, cal_report_name";
  }
  $res = dbi_query ( $sql );
  $list .= "<ul>\n";
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ){
      $rep_name = trim ( $row[1] );
      if ( empty ( $rep_name ) )
        $rep_name = translate ( "Unnamed Report" );
      $list .= "<li><a href=\"edit_report.php?report_id=$row[0]\" class=\"nav\">" .
        $rep_name . "</a></li>\n";
    }
    $list .= "</ul>\n";
    $addurl = $updating_public ? "edit_report.php?public=1" : "edit_report.php";
    $list .= "<p><a title=\"" .
      translate("Add new report") . "\" href=\"$addurl\" class=\"nav\">" .
      translate("Add new report") . "</a></p>\n";
    dbi_free_result ( $res );
  } else {
    $error = translate ( "Invalid report id" );
  }
}

// Load the specified report
if ( empty ( $error ) && empty ( $list ) ) {
  $res = dbi_query ( "SELECT cal_login, cal_report_id, cal_is_global, " .
    "cal_report_type, cal_include_header, cal_report_name, " .
    "cal_time_range, cal_user, " .
    "cal_allow_nav, cal_cat_id, cal_include_empty, cal_update_date " .
    "FROM webcal_report WHERE cal_report_id = $report_id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) { 
      if ( $row[2] != 'Y' && $login != $row[0] ) {
        $error = translate ( "You are not authorized" ) . ".";
      } else {
        $i = 0;
        $report_login = $row[$i++];
        $report_id = $row[$i++];
        $report_is_global = $row[$i++];
        $report_type = $row[$i++];
        $report_include_header = $row[$i++];
        $report_name = $row[$i++];
        $report_time_range = $row[$i++];
        $test_report_user = $row[$i++];
        // If this report type specifies a specific user, then we will
        // use that user rather even if a user was passed in via URL.
        if ( ! empty ( $test_report_user ) ) {
          $report_user = $test_report_user;
        }
        $report_allow_nav = $row[$i++];
        $report_cat_id = $row[$i++];
        $report_include_empty = $row[$i++];
        $report_update_date = $row[$i++];
      }
    } else {
      $error = translate ( "Invalid report id" );
    }
    dbi_free_result ( $res );
  } else {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  }
}

if ( empty ( $report_user ) ) {
  $report_user = $login;
}
//echo "User: $report_user <p>";

// Set default templates (in case there are none in the database for
// this report.)
$page_template = '<dl>${days}</dl>';
$day_template = '<dt><b>${date}</b></dt><dd><dl>${events}</dl></dd>';
$event_template = '<dt>${name}</dt><dd>' .
  '<b>' . translate ( "Date" ) . ':</b> ${date}<br />' .
  '<b>' . translate ( "Time" ) . ':</b> ${time}<br />' .
  '${description}</dd>';

// Load templates for this report.
if ( empty ( $error ) && empty ( $list ) ) {
  $res = dbi_query ( "SELECT cal_template_type, cal_template_text " .
    "FROM webcal_report_template " .
    "WHERE cal_report_id = $report_id" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] == 'P' ) {
        $page_template = $row[1];
      } else if ( $row[0] == 'D' ) {
        $day_template = $row[1];
      } else if ( $row[0] == 'E' ) {
        $event_template = $row[1];
      } else {
        // This shouldn't happen under normal circumstances, so
        // no need translate.
        echo "Invalid template type: '$row[0]'";
        exit;
      }
    }
    dbi_free_result ( $res );
  } else {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  }
}

if ( ! empty ( $report_include_header ) && $report_include_header == 'Y' ||
  ! empty ( $list ) || ! empty ( $error ) ) {
  print_header();
}

if ( empty ( $offset ) || empty ( $report_allow_nav ) ||
  $report_allow_nav != 'Y' ) {
  $offset = 0;
}

// Set time range based on cal_time_range field.
if ( empty ( $report_time_range ) ) {
  // manage reports
} else if ( $report_time_range >= 0 && $report_time_range < 10 ) {
  $today = mktime ( 3, 0, 0, date ( "m" ), date ( "d" ), date ( "Y" ) );
  $days_offset = 1 - $report_time_range + $offset;
  $start_date = date ( "Ymd", $today + ( $days_offset * ONE_DAY ) );
  $end_date = $start_date;
} else if ( $report_time_range >= 10 && $report_time_range < 20 ) {
  if ( $WEEK_START == 1 ) {
    $wkstart = get_monday_before ( date ( "Y" ), date ( "m" ),
      date ( "d" ) );
  } else {
    $wkstart = get_sunday_before ( date ( "Y" ), date ( "m" ),
      date ( "d" ) );
  }
  //echo "wkstart = " . date("Ymd",$wkstart) . "<br />";
  $week_offset = 11 - $report_time_range + $offset;
  //echo "week_offset=$week_offset <br />";
  $start_date = date ( "Ymd", $wkstart + ( $week_offset * 7 * ONE_DAY ) );
  $end_date = date ( "Ymd", $wkstart + ( $week_offset * 7 * ONE_DAY ) + 
    ( ONE_DAY * 6 ) );
} else if ( $report_time_range >= 20 && $report_time_range < 30 ) {
  if ( $WEEK_START == 1 ) {
    $wkstart = get_monday_before ( date ( "Y" ), date ( "m" ),
      date ( "d" ) );
  } else {
    $wkstart = get_sunday_before ( date ( "Y" ), date ( "m" ),
      date ( "d" ) );
  }
  //echo "wkstart = " . date("Ymd",$wkstart) . "<br />";
  $week_offset = 21 - $report_time_range + $offset;
  //echo "week_offset=$week_offset <br />";
  $start_date = date ( "Ymd", $wkstart + ( $week_offset * 7 * ONE_DAY ) );
  $end_date = date ( "Ymd", $wkstart + ( $week_offset * 7 * ONE_DAY ) + 
    ( ONE_DAY * 13 ) );
} else if ( $report_time_range >= 30 && $report_time_range < 40 ) {
  $thismonth = date ( "m" );
  $month_offset = 31 - $report_time_range + $offset;
  //echo "month_offset=$month_offset <br />";
  $start_date = date ( "Ymd", mktime ( 3, 0, 0, $thismonth + $month_offset,
    1, date ( "Y" ) ) );
  $end_date = date ( "Ymd", mktime ( 3, 0, 0, $thismonth + $month_offset + 1,
    0, date ( "Y" ) ) );
} else if ( $report_time_range >= 40 && $report_time_range < 50 ) {
  $thisyear = date ( "Y" );
  $year_offset = 41 - $report_time_range + $offset;
  //echo "year_offset=$year_offset <br />";
  $start_date = date ( "Ymd", mktime ( 3, 0, 0, 1, 1,
    $thisyear + $year_offset ) );
  $end_date = date ( "Ymd", mktime ( 3, 0, 0, 12, 31,
    $thisyear + $year_offset ) );
} else {
  // Programmer's bug (no translation needed)
  echo "Invalid cal_time_range setting for report id $report_id";
  exit;
}

if ( empty ( $error ) && empty ( $list ) ) {
  $cat_id = empty ( $report_cat_id ) ? "" : $report_cat_id;

  $repeated_events = read_repeated_events ( $report_user, $cat_id, $start_date );

  $events = read_events ( $report_user, $start_date, $end_date, $cat_id );

  $get_unapproved = $DISPLAY_UNAPPROVED == 'Y';
  if ( $report_user == "__public__" ) {
    $get_unapproved = false;
  }

  //echo "User: $report_user <br />\n";
  //echo "Date Range: $start_date - $end_date <br /><br />\n";

  $start_year = substr ( $start_date, 0, 4 );
  $start_month = substr ( $start_date, 4, 2 );
  $start_day = substr ( $start_date, 6, 2 );
  $start_time = mktime ( 3, 0, 0, $start_month, $start_day, $start_year );

  $end_year = substr ( $end_date, 0, 4 );
  $end_month = substr ( $end_date, 4, 2 );
  $end_day = substr ( $end_date, 6, 2 );
  $end_time = mktime ( 3, 0, 0, $end_month, $end_day, $end_year );

  $day_str = '';

  // Loop through each day
  // Get events for each day (both normal and repeating).
  // (Most of this code was copied from week.php)
  for ( $cur_time = $start_time; $cur_time <= $end_time; $cur_time += ONE_DAY ) {
    $event_str = '';
    $dateYmd = date ( "Ymd", $cur_time );
    $rep = get_repeating_entries ( empty ( $user ) ? $login : $user, $dateYmd );
    $ev = get_entries ( empty ( $user ) ? $login : $user, $dateYmd );
    $cur_rep = 0;
    //echo "DATE: $dateYmd <br />\n";
  
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
          $event_str .= event_to_text ( $viewid,
            $dateYmd, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
            $viewname, $rep[$cur_rep]['cal_description'],
            $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
            $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
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
        $event_str .= event_to_text ( $viewid,
          $dateYmd, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
          $viewname, $ev[$i]['cal_description'],
          $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
          $ev[$i]['cal_access'], $ev[$i]['cal_login'] );
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
        $event_str .= event_to_text ( $viewid,
          $dateYmd, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
          $viewname, $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
      }
      $cur_rep++;
    }
  
    if ( ! empty ( $event_str ) || $report_include_empty == 'Y' ||
      $report_time_range < 10 ) {
      $date_str = date_to_str ( $dateYmd, "", false );
      $date_full_str = date_to_str ( $dateYmd, "", true, false );
      $text = str_replace ( '${events}', $event_str, $day_template );
      $text = str_replace ( '${report_id}', $report_id, $text );
      $text = str_replace ( '${fulldate}', $date_full_str, $text );
      $day_str .= str_replace ( '${date}', $date_str, $text );
    }
  }
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate("Error") .
    "</h2>\n" . $error;
} else if ( ! empty ( $list ) ) {
  echo "<h2>";
  if ( $updating_public ) {
    echo translate($PUBLIC_ACCESS_FULLNAME) . " ";
  }
  echo translate("Manage Reports");
  echo "</h2>\n" . 
  "<a title=\"" . translate("Admin") . "\" class=\"nav\" href=\"adminhome.php\"> " .
     "&laquo;&nbsp;" . translate("Admin") . "</a><br /><br />\n" . $list;
} else {
  if ( $report_include_header == 'Y' ) {
    echo "<h2>" . $report_name . "</h2>\n";
  }
  $text = str_replace ( '${report_id}', $report_id, $page_template );
  echo str_replace ( '${days}', $day_str, $text );
}


if ( empty ( $error ) && empty ( $list ) ) {
  if ( ! empty ( $report_allow_nav ) && $report_allow_nav == 'Y' ) {
    if ( empty ( $offset ) ) {
      $offset = 0;
    }
    $next = $offset + 1;
    $prev = $offset - 1;
    echo "<br /><br /><a title=\"" .
      translate ( "Previous" ) . "\" href=\"report.php?report_id=$report_id$u_url" .
      ( empty ( $prev ) ? "" : "&amp;offset=$prev" ) . "\" class=\"nav\">" .
      translate ( "Previous" ) . "</a>\n";
    echo "&nbsp;&nbsp;<a title=\"" .
      translate ( "Next" ) . "\" href=\"report.php?report_id=$report_id$u_url" .
      ( empty ( $next ) ? "" : "&amp;offset=$next" ) . "\" class=\"nav\">" .
      translate ( "Next" ) . "</a><br />\n";
  }
  if ( $report_include_header == 'Y' ) {
    echo '<br /><br /><a title="' . translate("Printer Friendly") . 
      '" class="nav" href="report.php?report_id=' . $report_id .
      '&amp;friendly=1' . $u_url . '&amp;offset=' . $offset .
      '" target="cal_printer_friendly" onmouseover="window.status=\'' .
      translate("Generate printer-friendly version") .
      '\'">[' . translate("Printer Friendly") . ']</a>';
  }
}

if ( ! empty ( $list ) || $report_include_header == 'Y'
  || ! empty ( $error ) || ! empty ( $list ) ) {
  print_trailer ();
} else {
  print_trailer ( false );
}

?>
</body>
</html>

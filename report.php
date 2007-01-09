<?php
/* Lists a user's reports or displays a specific report.
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
 */

/*
 * Security:
 * If system setting $REPORTS_ENABLED is set to anything other than
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
 * Generates the HTML for one event for a report.
 *
 * @param Event  $event The event
 * @param string $date  The date for which we're printing (in YYYYMMDD format)
 *
 * @return string HTML for this event based on report template.
 */
function event_to_text ( $event, $date ) {
  global $login, $user, $event_template, $report_id, $ALLOW_HTML_DESCRIPTION;

  $time_str = $start_time_str = $end_time_str = '';

  if ( $event->isAllDay() ) {
    $time_str = translate ( 'All day event' );
  } else if ( $event->isUntimed() ) {
    $time_str = translate ( 'Untimed event' );
  } else {
    $time_str = display_time ( $event->getDateTime() );
    $start_time_str = $time_str;
    $time_short = getShortTime ( $time_str );
    if ( $event->getDuration() > 0 ) {
      if (  $event->isAllDay() ) {
        $time_str = translate( 'All day event' );
      } else {
        $time_str .= ' - ' . display_time (  $event->getEndDateTime() );
        $end_time_str = display_time ( $event->getEndDateTime() );
      }
    }
  }

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }

  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) ) {
    $name_str = '(' . translate ( 'Private' ) . ')';
    $description_str = translate ( 'This event is confidential' );
  } else if ( $login != $event->getLogin() && $event->getAccess() == 'R' &&
    strlen ( $event->getLogin() ) ) {
    $name_str = '(' . translate ( 'Private' ) . ')';
    $description_str = translate ( 'This event is confidential' );
  } else {
    $name_str = htmlspecialchars ( $name );
    if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) &&
      $ALLOW_HTML_DESCRIPTION == 'Y' ) {
      $str = str_replace ( '&', '&amp;', $event->getDescription() );
      $description_str = str_replace ( '&amp;amp;', '&amp;', $str );
      if ( strstr ( $description_str, '<' ) &&
        strstr ( $description_str, '>' ) ) {
        // found some HTML
      } else {
        // No HTML found.  Add line breaks.
        $description_str = nl2br ( $description_str );
      }
    } else {
      $description_str = nl2br (
        activate_urls ( htmlspecialchars ( $event->getDescription() ) ) );
    }
  }

  $date_str = date_to_str ( $date, '', false );
  $date_full_str = date_to_str ( $date);

  if ( $event->getDuration() > 0 ) {
    $duration_str = $event->getDuration() . ' ' . translate ( 'minutes' );
  } else {
    $duration_str = '';
  }

  if ( $event->getPriority() >= 7 ) {
    $pri_str = translate ( 'Low' );
  } else if ( $event->getPriority() <= 3 ) {
    $pri_str = translate ( 'High' );
  } else {
    $pri_str = translate ( 'Medium' );
  }

  if ( $event->getStatus() == 'W' ) {
    $status_str = translate ( 'Waiting for approval' );
  } else if ( $event->getStatus() == 'D' ) {
    $status_str = translate ( 'Deleted' );
  } else if ( $event->getStatus() == 'R' ) {
    $status_str = translate ( 'Rejected' );
  } else if ( $event->getStatus() == 'A' ) {
    $status_str = translate ( 'Approved' );
  } else {
    $status_str = translate ( 'Unknown' );
  }
  $location = $event->getLocation();
  $url = $event->getUrl();
  $href_str = "view_entry.php?id=$id";

  //Get user's fullname
  user_load_variables ( $event->getLogin(), 'report_' );
  $fullname = $GLOBALS['report_fullname'];
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
  $text = str_replace ( '${user}', $event->getLogin(), $text );
  $text = str_replace ( '${fullname}', $fullname, $text );
  $text = str_replace ( '${report_id}', $report_id, $text );
  $text = str_replace ( '${location}', $location, $text );
  $text = str_replace ( '${url}', $url, $text );

  $text = replace_site_extras_in_template ( $text,
    format_site_extras ( get_site_extra_fields ( $id ), EXTRA_DISPLAY_REPORT ) );

  return $text;
}

$error = '';
$list = ''; // list of reports when no id specified

if ( ! empty ( $user ) && $user != $login &&
  ( ( ! empty ( $ALLOW_VIEW_OTHER ) && $ALLOW_VIEW_OTHER == 'Y' )
  || $is_admin ) ) {
  $report_user = $user;
  $u_url = "&amp;user=$user";
} else {
  $u_url = '';
}

if ( empty ( $REPORTS_ENABLED ) || $REPORTS_ENABLED != 'Y' ) {
  $error = print_not_auth () . '.';
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $report_user = '__public__';
}

$offset = getValue ( 'offset', '-?[0-9]+', true );
if ( empty ( $offset ) ) {
  $offset = 0;
}
$report_id = getValue ( 'report_id', '-?[0-9]+', true );

// If no report id is specified, then generate a list of reports for
// the user to select from.
if ( empty ( $error ) && empty ( $report_id ) && $login == '__public__' ) {
  $error = print_not_auth () . '.';
}
if ( empty ( $error ) && empty ( $report_id ) ) {
  $list = '';
  $sql_params = array();
  if ( $is_admin ) {
    if ( ! $updating_public ) {
      if ( $PUBLIC_ACCESS == 'Y') {
// translate ( 'Click here' )
// translate ( 'to manage reports for the Public Access calendar' )
        $clickStr = translate ( 'Click here to manage reports for the Public Access calendar.' );
        $list .= '<p><a title="' . $clickStr .
           '" href="report.php?public=1">' . $clickStr . "</a></p>\n";
      }
      $sql = 'SELECT cal_report_id, cal_report_name FROM webcal_report
        WHERE cal_login = ? OR cal_is_global = \'Y\'
        ORDER BY cal_update_date DESC, cal_report_name';
      $sql_params[] = $login;
    } else {
      $sql = 'SELECT cal_report_id, cal_report_name FROM webcal_report
        WHERE cal_login = \'__public__\'
        ORDER BY cal_update_date DESC, cal_report_name';
    }
  } else {
    $sql = 'SELECT cal_report_id, cal_report_name ' .
      'FROM webcal_report WHERE cal_login = ? ' .
      'ORDER BY cal_update_date DESC, cal_report_name';
    $sql_params[] = $login;
  }
  $res = dbi_execute ( $sql, $sql_params );
  $list .= "<ul>\n";
  if ( $res ) {
    $unnamesStr = translate ( 'Unnamed Report' );
    $addStr = translate ( 'Add new report' );
    while ( $row = dbi_fetch_row ( $res ) ){
      $rep_name = trim ( $row[1] );
      if ( empty ( $rep_name ) )
        $rep_name = $unnamesStr;
      $list .= "<li><a href=\"edit_report.php?report_id=$row[0]\" class=\"nav\">" .
        $rep_name . "</a></li>\n";
    }
    $list .= "</ul>\n";
    $addurl = $updating_public ? 'edit_report.php?public=1': 'edit_report.php';
    $list .= '<p><a title="' . $addStr . "\" href=\"$addurl\" class=\"nav\">" .
      $addStr . "</a></p>\n";
    dbi_free_result ( $res );
  } else {
    $error = translate ( 'Invalid report id' );
  }
}

// Load the specified report
if ( empty ( $error ) && empty ( $list ) ) {
  $res = dbi_execute ( 'SELECT cal_login, cal_report_id, cal_is_global, ' .
    'cal_report_type, cal_include_header, cal_report_name, ' .
    'cal_time_range, cal_user, ' .
    'cal_allow_nav, cal_cat_id, cal_include_empty, cal_update_date ' .
    'FROM webcal_report WHERE cal_report_id = ?', array ( $report_id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) { 
      if ( $row[2] != 'Y' && $login != $row[0] ) {
        $error = print_not_auth () . '.';
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
      $error = translate ( 'Invalid report id' );
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
}

if ( empty ( $report_user ) ) {
  $report_user = $login;
}

// Set default templates (in case there are none in the database for
// this report.)
$day_str = $printerStr = '';
$page_template = '<dl>${days}</dl>';
$day_template = '<dt><b>${date}</b></dt><dd><dl>${events}</dl></dd>';
$event_template = '<dt>${name}</dt><dd>' .
  '<b>' . translate ( 'Date' ) . ':</b> ${date}<br />' .
  '<b>' . translate ( 'Time' ) . ':</b> ${time}<br />' .
  '${description}</dd>';

// Load templates for this report.
if ( empty ( $error ) && empty ( $list ) ) {
  $res = dbi_execute ( 'SELECT cal_template_type, cal_template_text ' .
    'FROM webcal_report_template ' .
    'WHERE cal_report_id = ?', array ( $report_id ) );
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
        echo 'Invalid template type: ' .$row[0];
        exit;
      }
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
}

$include_header = ( ! empty ( $report_include_header ) && 
  $report_include_header == 'Y'? true : false );

if ( $include_header || ! empty ( $list ) || ! empty ( $error ) ) {
  $printerStr = ( ! empty ( $report_id ) 
    ? generate_printer_friendly ( 'report.php' ) : '' );
  print_header();
}

if ( empty ( $offset ) || empty ( $report_allow_nav ) ||
  $report_allow_nav != 'Y' ) {
  $offset = 0;
}
$next = $offset + 1;
$prev = $offset - 1;
// Set time range based on cal_time_range field.
$DISPLAY_WEEKENDS = 'Y';
$dateY = date ( 'Y' );
$datem = date ( 'm' );
$dated = date ( 'd' );

$wkstart = get_weekday_before ( $dateY, $datem, $dated +1 );
if ( ! isset ( $report_time_range ) ) {
  // manage reports
} else if ( $report_time_range >= 0 && $report_time_range < 10 ) {
  $today = mktime ( 0, 0, 0, $datem, $dated, $dateY );
  $days_offset = 1 - $report_time_range + $offset;
  $start_date = $today + ( $days_offset * ONE_DAY );
  $end_date = $start_date;
} else if ( $report_time_range >= 10 && $report_time_range < 20 ) {
  $week_offset = 11 - $report_time_range + $offset;
  $start_date = $wkstart + ( $week_offset * 7 * ONE_DAY );
  $end_date = $wkstart + ( $week_offset * 7 * ONE_DAY ) + ( ONE_DAY * 6 );
} else if ( $report_time_range >= 20 && $report_time_range < 30 ) {
  $week_offset = 21 - $report_time_range + $offset;
  $start_date = $wkstart + ( $week_offset * 7 * ONE_DAY );
  $end_date = $wkstart + ( $week_offset * 7 * ONE_DAY ) + ( ONE_DAY * 13 );
} else if ( $report_time_range >= 30 && $report_time_range < 40 ) {
  $thismonth = $datem;
  $month_offset = 31 - $report_time_range + $offset;
  $start_date = mktime ( 0, 0, 0, $thismonth + $month_offset, 1, $dateY );
  $end_date = mktime ( 23, 59, 59, $thismonth + $month_offset + 1, 0, $dateY );
} else if ( $report_time_range >= 40 && $report_time_range < 50 ) {
  $thisyear = $dateY;
  $year_offset = 41 - $report_time_range + $offset;
  $start_date = mktime ( 0, 0, 0, 1, 1, $thisyear + $year_offset );
  $end_date = mktime ( 23, 59, 59, 12, 31, $thisyear + $year_offset );
} else if ( $report_time_range >= 50 && $report_time_range < 60 ) {
  // This series of reports is today + N days
  switch ( $report_time_range ) {
    case 50: $x = 14; break;
    case 51: $x = 30; break;
    case 52: $x = 60; break;
    case 53: $x = 90; break;
    case 54: $x = 180; break;
    case 55: $x = 365; break;
    default: echo 'Invalid cal_time_range setting for report id ' .$report_id;
      exit;
  }
  $today = mktime ( 0, 0, 0, $datem, $dated, $dateY );
  $start_date = $today + ( ONE_DAY * $offset * $x );
  $end_date = $start_date + ( ONE_DAY * $x );
} else {
  // Programmer's bug (no translation needed)
  echo 'Invalid cal_time_range setting for report id ' .$report_id;
  exit;
}

// The read_repeated_events calculates all event repeat dates for
// some time period after the values of $thismonth and $thisyear.
if ( ! empty ( $end_date ) ) {
  $thismonth = date ( 'm', $end_date );
  $thisyear = date ( 'Y', $end_date );
}

if ( empty ( $error ) && empty ( $list ) ) {
  $cat_id = empty ( $report_cat_id ) ? '' : $report_cat_id;

  $repeated_events = read_repeated_events ( $report_user, $start_date, $end_date, $cat_id );
  $events = read_events ( $report_user, $start_date, $end_date, $cat_id );

  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

  // Loop through each day
  // Get events for each day (both normal and repeating).
  // (Most of this code was copied from week.php)
  for ( $cur_time = $start_date; $cur_time <= $end_date; $cur_time += ONE_DAY ) {
    $event_str = '';
    $dateYmd = date ( 'Ymd', $cur_time );
    $rep = get_repeating_entries ( $report_user, $dateYmd );
    $ev = get_entries ( $dateYmd );
    $ev = combine_and_sort_events($ev, $rep);
    for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
      if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
        $event_str .= event_to_text ( $ev[$i], $dateYmd );
      }
    }
  
    if ( ! empty ( $event_str ) || $report_include_empty == 'Y' ||
      $report_time_range < 10 ) {
      $date_str = date_to_str ( $dateYmd, '', false );
      $date_full_str = date_to_str ( $dateYmd );
      $text = str_replace ( '${events}', $event_str, $day_template );
      $text = str_replace ( '${report_id}', $report_id, $text );
      $text = str_replace ( '${fulldate}', $date_full_str, $text );
      $day_str .= str_replace ( '${date}', $date_str, $text );
    }
  }
}
if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$prevStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );

$reportNameStr = ( $include_header ? '<h2>' . $report_name . '</h2>' : '' );

if ( ! empty ( $report_allow_nav ) && $report_allow_nav == 'Y' ) {
  $prevLinkStr =  '<a class="nav" title="' . $prevStr . '" href="report.php?report_id=' . 
    $report_id .$u_url . "&amp;offset=$prev\">" . $prevStr . '</a>';
  $nextLinkStr = '<a  class="nav" title="' . $nextStr . '" href="report.php?report_id=' .
    $report_id . $u_url . "&amp;offset=$next\">" . $nextStr . '</a>';
} else {
  $prevLinkStr = $nextLinkStr ='';
}
if ( ! empty ( $list ) ) {
  $textStr = '';
  $manageStr = translate ( 'Manage Reports' ); 
  if ( $updating_public ) {
    $manageStr = translate ( $PUBLIC_ACCESS_FULLNAME) . ' ' . $manageStr;
  } 
  $adminLinkStr = display_admin_link();
  $trailerStr = print_trailer ( );
} else {
  $manageStr = $adminLinkStr = '';
  $text = str_replace ( '${report_id}', $report_id, $page_template );
  $textStr = str_replace ( '${days}', $day_str, $text );
  $trailerStr = print_trailer ( $include_header  );
}

echo <<<EOT
  {$reportNameStr}
  {$prevLinkStr}&nbsp;&nbsp;
  {$nextLinkStr}
  <h2>{$manageStr}</h2>
  {$adminLinkStr}


  {$list}
  {$textStr}
  {$printerStr}
  
  {$trailerStr}
  
EOT;
?>

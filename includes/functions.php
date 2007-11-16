<?php
/* Most of WebCalendar's functions.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

/* Functions start here.  All non-function code should be above this.
 *
 * Note to developers:
 *  Documentation is generated from the function comments below.
 *  When adding/updating functions, please follow these conventions.
 *  Your cooperation in this matter is appreciated. :-)
 *
 *  If you want your documentation to link to the db documentation,
 *  just make sure you mention the db table name followed by "table"
 *  on the same line.  Here's an example:
 *    Retrieve preferences from the webcal_user_pref table.
 */

/* Load other specific function libraries.
 */
$includeDir = ( defined ( '_WC_INCLUDE_DIR' ) ? _WC_INCLUDE_DIR : 'includes/' );
include_once $includeDir . 'formvars.php';

/* Logs a debug message.
 *
 * Generally, we try not to leave calls to this function in the code.
 * It is used for debugging only.
 *
 * @param string $msg Text to be logged
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  // error_log ( date ( 'Y-m-d H:i:s' ) .  "> $msg\n<br />",
  // 3, 'd:/php/logs/debug.txt' );
  // fwrite ( $fd, date ( 'Y-m-d H:i:s' ) .  "> $msg\n" );
  // fclose ( $fd );
  // 3, '/tmp/webcal-debug.log' );
  // error_log ( date ( 'Y-m-d H:i:s' ) .  "> $msg\n",
  // 2, 'sockieman:2000' );
}

/* Looks for URLs in the given text, and makes them into links.
 *
 * @param string $text Input text
 *
 * @return string  The text altered to have HTML links for any web links.
 */
function activate_urls ( $text ) {
  return ereg_replace ( '[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]',
    '<a href="\\0">\\0</a>', $text );
}

/* Adds something to the activity log for an event.
 *
 * The information will be saved to the webcal_entry_log table.
 *
 * @param int    $event_id  Event ID
 * @param string $user      Username of user doing this
 * @param string $user_cal  Username of user whose calendar is affected
 * @param string $type      Type of activity we are logging:
 *   - LOG_APPROVE
 *   - LOG_APPROVE_T
 *   - LOG_ATTACHMENT
 *   - LOG_COMMENT
 *   - LOG_CREATE
 *   - LOG_CREATE_T
 *   - LOG_DELETE
 *   - LOG_DELETE_T
 *   - LOG_LOGIN_FAILURE
 *   - LOG_NEWUSER_FULL
 *   - LOG_NEWUSEREMAIL
 *   - LOG_NOTIFICATION
 *   - LOG_REJECT
 *   - LOG_REJECT_T
 *   - LOG_REMINDER
 *   - LOG_UPDATE
 *   - LOG_UPDATE_T
 *   - LOG_USER_ADD
 *   - LOG_USER_DELETE
 *   - LOG_USER_UPDATE
 * @param string $text     Text comment to add with activity log entry
 */
function activity_log ( $event_id, $user, $user_cal, $type, $text ) {
  $next_id = 1;

  if ( empty ( $type ) ) {
    echo translate ( 'Error Type not set for activity log!' );
    // But don't exit since we may be in mid-transaction.
    return;
  }

  $res = dbi_execute ( 'SELECT MAX( cal_log_id ) FROM webcal_entry_log' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) )
      $next_id = $row[0] + 1;

    dbi_free_result ( $res );
  }
  $sql = 'INSERT INTO webcal_entry_log ( cal_log_id, cal_entry_id, cal_login_id,
    cal_owner_id, cal_type, cal_date, cal_text )
    VALUES ( ?, ?, ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array ( $next_id, $event_id, $user,
        ( empty ( $user_cal ) ? null : $user_cal ), $type, time(),
          ( empty ( $text ) ? null : $text ) ) ) )
    db_error ( true, $sql );
}

/* Get the corrected timestamp after adding or subtracting ONE_HOUR
 * to compensate for DST.
 */
function add_dstfree_time ( $date, $span, $interval = 1 ) {
  $ctime = date ( 'G', $date );
  $date += $span * $interval;
  $dtime = date ( 'G', $date );
  if ( $ctime == $dtime )
    return $date;
  elseif ( $ctime == 23 && $dtime == 0 )
    $date -= ONE_HOUR;
  elseif ( ( $ctime == 0 && $dtime == 23 ) || $ctime > $dtime )
    $date += ONE_HOUR;
  elseif ( $ctime < $dtime )
    $date -= ONE_HOUR;

  return $date;
}

/* Return the time in HHMMSS format of input time + duration
 *
 * @param string $time   format "235900"
 * @param int $duration  number of minutes
 *
 * @return string  The time in HHMMSS format.
 */
function add_duration ( $time, $duration ) {
  $time = sprintf ( "%06d", $time );
  $minutes =
  intval ( $time / 10000 ) * 60 + ( ( $time / 100 ) % 100 ) + $duration;

  return sprintf ( "%d%02d00", $minutes / 60, $minutes % 60 );
}


/* Calculates which row/slot this time represents.
 *
 * This is used in day and week views where hours of the time are separeted
 * into different cells in a table.
 *
 * <b>Note:</b> the variable <var>TIME_SLOTS</var> is used to determine
 * how many time slots there are and how many minutes each is.  This variable
 * is defined user preferences (or defaulted to admin system settings).
 *
 * @param string $time        Input time in HHMMSS format
 * @param bool   $round_down  Should we change 1100 to 1059?
 *                            (This will make sure a 10AM-100AM appointment just
 *                            shows up in the 10AM slow and not in the 11AM slot
 *                            also.)
 *
 * @return int  The time slot index.
 */
function calc_time_slot ( $time, $round_down = false ) {

  $time_slots = getPref ( 'TIME_SLOTS' );
  $interval = 1440 / $time_slots;
  $mins_since_midnight = time_to_minutes ( sprintf ( "%06d", $time ) );
  $ret = intval ( $mins_since_midnight / $interval );
  if ( $round_down && $ret * $interval == $mins_since_midnight )
    $ret--;

  if ( $ret > $time_slots )
    $ret = $time_slots;

  return $ret;
}

/* Checks for conflicts.
 *
 * Find overlaps between an array of dates and the other dates in the database.
 *
 * Limits on number of appointments: if enabled in System Settings
 * (<var>$LIMIT_APPTS</var> global variable), too many appointments can also
 * generate a scheduling conflict.
 *
 * @todo Update this to handle exceptions to repeating events.
 *
 * @param array  $dates         Array of dates in Timestamp format that is
 *                              checked for overlaps.
 * @param int    $duration      Event duration in minutes
 * @param int    $eventstart    GMT starttime timestamp
 * @param array  $participants  Array of users whose calendars are to be checked
 * @param string $WC->loginId()         The current user name
 * @param int    $eid            Current event id (this keeps overlaps from
 *                              wrongly checking an event against itself)
 *
 * @return  Empty string for no conflicts or return the HTML of the
 *          conflicts when one or more are found.
 */
function check_for_conflicts ( $dates, $duration, $eventstart,
  $participants, $login, $eid ) {
  global $WC, $repeated_events;

  $datecnt = count ( $dates );
  if ( ! $datecnt )
    return false;

  $conflicts = '';
  $count = 0;
  $evtcnt = $found = $query_params = array ();
  $partcnt = count ( $participants );

  $hour = gmdate ( 'H', $eventstart );
  $minute = gmdate ( 'i', $eventstart );

  $allDayStr = translate ( 'All day event' );
  $confidentialStr = translate ( 'Confidential' );
  $exceedsStr = translate ( 'exceeds limit of XXX events per day' );
  $onStr = translate ( 'on' );
  $privateStr = translate ( 'Private' );

  $sql = 'SELECT DISTINCT( weu.cal_login_id ), we.cal_duration,
    we.cal_name, we.cal_id, we.cal_access, weu.cal_status, we.cal_date
    FROM webcal_entry we, webcal_entry_user weu WHERE we.cal_id = weu.cal_id AND ( ';

  for ( $i = 0; $i < $datecnt; $i++ ) {
    $sql .= ( $i != 0 ? ' OR ' : '' ) . 'we.cal_date = '
     . gmdate ( 'Ymd', $dates[$i] );
  }
  //TODO add check for untimed
  $sql .= ' ) AND weu.cal_status IN ( \'A\',\'W\' ) AND ( ';
  if ( _WC_SINGLE_USER )
    $participants[0] = _WC_SINGLE_USER_LOGIN;
  else
  if ( strlen ( $participants[0] ) == 0 )
    // Likely called from a form with 1 user.
    $participants[0] = $login;

  for ( $i = 0; $i < $partcnt; $i++ ) {
    $sql .= ( $i > 0 ? ' OR ' : '' ) . 'weu.cal_login_id = ?';
    $query_params[] = $participants[$i];
  }
  // Make sure we don't get something past the end date of the event we're saving.
  $res = dbi_execute ( $sql . ' )', $query_params );
  if ( $res ) {
    $duration1 = sprintf ( "%d", $duration );
    $time1 = sprintf ( "%d%02d00", $hour, $minute );
    while ( $row = dbi_fetch_row ( $res ) ) {
      // Add to an array to see if it has been found already for the next part.
      $found[$count++] = $row[4];
      // See if events overlaps one another.
      if ( $row[4] != $eid ) {
        $cntkey = $row[0] . '-' . $row[7];
        $duration2 = $row[2];
        $time2 = sprintf ( "%06d", $row[1] );
        if ( empty ( $evtcnt[$cntkey] ) )
          $evtcnt[$cntkey] = 0;
        else
          $evtcnt[$cntkey]++;
        $limit_appts_number = getPref ( 'LIMIT_APPTS_NUMBER' );
        $over_limit = ( getPref ( 'LIMIT_APPTS' )  && $limit_appts_number &&
          $evtcnt[$cntkey] >= $limit_appts_number ? 1 : 0 );

        if ( $over_limit ||
          times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $conflicts .= '
            <li>';

          if ( ! _WC_SINGLE_USER ) {
            $WC->User->loadVariables ( $row[0], 'conflict_' );
            $conflicts .= $GLOBALS['conflict_fullname'] . ': ';
          }
          $conflicts .= ( $row[5] == 'C' && $row[0] != $login && !
            $WC->isNonuserAdmin()
            // Assistants can see confidential stuff.
            ? '(' . $confidentialStr . ')'
            : ( $row[5] == 'R' && $row[0] != $login
              ? '( ' . $privateStr . ')'
              : '<a href="view_entry.php?eid=' . $row[4]
               . ( $row[0] != $login  ? '&amp;user=' . $row[0] : '' )
               . '">' . $row[3] . '</a>' ) )
           . ( $duration2 == 1440 && $time2 == 0
            ? ' (' . $allDayStr . ')'
            : ' (' . display_time ( $row[7] )
             . ( $duration2 > 0
              ? '-' . display_time ( $row[7]
                 . add_duration ( $time2, $duration2 ) ) : '' ) . ')' )
           . ' ' . $onStr . ' '
           . date_to_str ( date ( 'Ymd', date_to_epoch ( $row[7]
                 . sprintf ( "%06d", $row[1] ) ) ) )
           . ( $over_limit ? ' (' . str_replace ( 'XXX', $limit_appts_number,
              $exceedsStr ) . ')' : '' ) . '</li>';
        }
      }
    }
    dbi_free_result ( $res );
  } else
    db_error ( true );

  for ( $q = 0; $q < $partcnt; $q++ ) {
    // Read repeated events only once for a participant for performance reasons.
    $repeated_events = query_events ( $participants[$q], true,
      // This date filter is not necessary for functional reasons, but it
      // eliminates some of the events that couldn't possibly match.  This could
      // be made much more complex to put more of the searching work onto the
      // database server, or it could be dropped all together to put the
      // searching work onto the client.
      'AND ( we.cal_date <= ' . $dates[count ( $dates )-1]
       . ' AND ( wer.cal_end IS NULL OR wer.cal_end >= '
       . $dates[0] . ' ) )' );
    for ( $i = 0; $i < $datecnt; $i++ ) {
      $dateYmd = gmdate ( 'Ymd', $dates[$i] );
      $list = get_repeating_entries ( $participants[$q], $dateYmd );
      for ( $j = 0, $listcnt = count ( $list ); $j < $listcnt; $j++ ) {
        // OK we've narrowed it down to a day, now I just gotta check the time...
        // I hope this is right...
        $row = $list[$j];
        if ( $row->getId () != $eid &&
            ( $row->getExtForID () == '' || $row->getExtForID () != $eid ) ) {
          $time2 = sprintf ( "%06d", $row->getDate ( 'His' ) );
          $duration2 = $row->getDuration ();
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $conflicts .= '
            <li>';
            if ( ! _WC_SINGLE_USER ) {
              $WC->User->loadVariables ( $row->getLoginId (), 'conflict_' );
              $conflicts .= $GLOBALS['conflict_fullname'] . ': ';
            }
            $conflicts .= ( $row->getAccess () == 'C' && 
              ! $WC->isLogin( $row->getLoginId () ) && !
              $WC->isNonuserAdmin()
              // Assistants can see confidential stuff.
              ? '(' . $confidentialStr . ')'
              : ( $row->getAccess () == 'R' && 
                ! $WC->isLogin( $row->getLoginId () )
                ? '(' . $privateStr . ')'
                : '<a href="view_entry.php?eid=' . $row->getId ()
                 . ( ! empty ( $user ) && ! $WC->isLogin( $user )
                  ? '&amp;user=' . $user : '' )
                 . '">' . $row->getName () . '</a>' ) )
             . ' (' . display_time ( $row->getDate () )
             . ( $duration2 > 0
              ? '-' . display_time ( $row->getDate ()
                 . add_duration ( $time2, $duration2 ) ) : '' )
             . ')' . ' ' . $onStr . ' ' . date_to_str ( $dateYmd ) . '</li>';
          }
        }
      }
    }
  }

  return $conflicts;
}

/* Replaces unsafe characters with HTML encoded equivalents.
 *
 * @param string $value  Input text
 *
 * @return string  The cleaned text.
 */
function clean_html ( $value ) {
  $value = htmlspecialchars ( $value, ENT_QUOTES );
  $value = strtr ( $value, array (
      '(' => '&#40;',
      ')' => '&#41;'
      ) );
  return $value;
}

/* Removes non-digits from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_int ( $data ) {
  return preg_replace ( '/\D/', '', $data );
}

/* Removes whitespace from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_whitespace ( $data ) {
  return preg_replace ( '/\s/', '', $data );
}

/* Removes non-word characters from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_word ( $data ) {
  return preg_replace ( '/\W/', '', $data );
}

/* Combines the repeating and nonrepeating event arrays and sorts them
 *
 * The returned events will be sorted by time of day.
 *
 * @param array $ev   Array of events
 * @param array $rep  Array of repeating events
 *
 * @return array  Array of Events.
 */
function combine_and_sort_events ( $ev, $rep ) {
  $eids = array ();

  // Repeating events show up in $ev and $rep.
  // Record their ids and don't add them to the combined array.
  foreach ( $rep as $obj ) {
    $eids[] = $obj->getId ();
  }
  foreach ( $ev as $obj ) {
    if ( ! in_array ( $obj->getId (), $eids ) )
     $rep[] = $obj;
  }
  usort ( $rep, 'sort_events' );

  return $rep;
}

/* Creates a new instance of an Event or RepeatingEvent
 *
 * @param array  $row  Array containing all required data
 *
 * @return object  Class object 
 */
function createEvent ( $row, $want_repeated=true ) {
  if ( $want_repeated && ! empty ( $row[19] ) ) // row[19] = cal_type
    $item =& new RepeatingEvent ( $row[0], $row[1], $row[2], $row[3],
      $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], $row[10],
      $row['primary_cat'], $row[11], $row[12], $row[13], $row[14], $row[15],
      $row[16], $row[17], $row[18], $row[19], $row[20], $row[21], $row[22],
      $row[23], $row[24], $row[25], $row[26], $row[27], $row[28], $row[29],
      array (), array (), array () );
  else
    $item =& new Event ( $row[0], $row[1], $row[2], $row[3], 
		  $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], 
			$row[10], $row['primary_cat'], $row[11], $row[12], $row[13], 
			$row[14], $row[15], $row[16], $row[17], $row[18] );

  return $item;        
}

/* Converts a date to a timestamp.
 *
 * @param string $d   Date in YYYYMMDD or YYYYMMDDHHIISS format
 *
 * @return int  Timestamp representing, in UTC time.
 */
function date_to_epoch ( $d ) {
  if ( $d == 0 )
    return 0;

  $dH = $di = $ds = 0;
  if ( strlen ( $d ) == 13 ) { // Hour value is single digit.
    $dH = substr ( $d, 8, 1 );
    $di = substr ( $d, 9, 2 );
    $ds = substr ( $d, 11, 2 );
  }
  if ( strlen ( $d ) == 14 ) {
    $dH = substr ( $d, 8, 2 );
    $di = substr ( $d, 10, 2 );
    $ds = substr ( $d, 12, 2 );
  }

  return gmmktime ( $dH, $di, $ds,
    substr ( $d, 4, 2 ),
    substr ( $d, 6, 2 ),
    substr ( $d, 0, 4 ) );
}


function date_to_str ( $indate='', $format='', $show_weekday = true, 
  $short_months = false ) {

  if ( $indate == '' )
    $indate = time();

  //TODO Temp hack till we convert to 100% Timestamps
  if ( strlen ( $indate ) == 8 )
    $indate = date_to_epoch ( $indate ) + (12 * ONE_HOUR);

  if ( empty ( $format ) ) {
    $format = getPref ( 'DATE_FORMAT' );
  } else if ( substr ( $format,0,4 ) == 'DATE' ) {
    // if they have not set a preference yet...
    $format = getPref ( $format );
  } else if ( $format == 'datepicker' ) {
    $format =translate ( '__mm__/__dd__/__yyyy__' );
	$show_weekday = false;
  }
   
  $format = ( ! $format || $format == 'LANGUAGE_DEFINED' 
    ? translate ( '__month__ __dd__, __yyyy__' ) : $format );
		
  $y = date ( 'Y', $indate );
  $m = date ('m', $indate );
  $d = date ( 'd', $indate );
  $wday = date ( "w", $indate );
  if ( $short_months ) {
    $month = month_name ( $m - 1, 'M' );
    $weekday = weekday_name ( $wday, 'D' );
  } else {
    $month = month_name ( $m - 1 );
    $weekday = weekday_name ( $wday );
  }

  $ret = str_replace ( '__dd__', $d, $format );
  $ret = str_replace ( '__d__', intval ( $d ), $ret );
  $ret = str_replace ( '__mm__', $m, $ret );
  $ret = str_replace ( '__m__', sprintf ( "%02d", $m ), $ret );
  $ret = str_replace ( '__mon__', $month, $ret );
  $ret = str_replace ( '__month__', $month, $ret );
  $ret = str_replace ( '__yy__', sprintf ( "%02d", $y % 100 ), $ret );
  $ret = str_replace ( '__yyyy__', $y, $ret );

  return ( $show_weekday ? "$weekday, $ret" : $ret );
}

/* Prints small task list for this $WC->loginId() user.
 * TODO Convert to template file
 */
function display_small_tasks ( $cat_id='' ) {
  global $WC, $smarty, $task_filter, $user;
  static $key = 0;

  if ( ! empty ( $user ) && ! $WC->isLogin( $user ) )
    return false;

  $SORT_TASKS = 'Y';
  $bgcolor = getPref ( 'BGCOLOR' );
  $pri[1] = translate ( 'High' );
  $pri[2] = translate ( 'Medium' );
  $pri[3] = translate ( 'Low' );
  $task_user = $WC->loginId();
  $u_url = '';

  if ( ! $WC->isLogin( $user ) && ! empty ( $user ) ) {
    $u_url = 'user=' . $user . '&amp;';
    $task_user = $user;
  }
  $ajax = array ();
  $dueSpacer = '&nbsp;';

  if ( $SORT_TASKS == 'Y' ) {
    for ( $i = 0; $i < 4; $i++ ) {
      $ajax[$i] = '
        <td class="sorter" onclick="sortTasks( ' . $i . ', ' . $cat_id
       . ', this )"><img src="images/up.png" style="vertical-align:bottom" /></td>';
      $ajax[$i + 4] = '
        <td  class="sorter sorterbottom" onclick="sortTasks( ' .
      ( $i + 4 ) . ', ' . $cat_id
       . ', this )"><img src="images/down.png" style="vertical-align:top" /></td>';
    }
  } else {
    $dueSpacer = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $ajax = array_pad ( $ajax, 8, '
        <td></td>' );
  }

  $priorityStr = translate ( 'Priority' );
  $dateFormatStr = getPref ( 'DATE_FORMAT_TASK' );
  $task_list = query_events ( $task_user, false,
    ( ! empty ( $task_filter ) ? $task_filter : '' ), $cat_id, true );
  $row_cnt = 1;
  $task_html = '
    <table class="minitask" cellspacing="0" cellpadding="2">
      <tr class="header">
        <th colspan="6">' . translate ( 'TASKS' ) . '</th>
        <th align="right" colspan="2"><a href="edit_entry.php?' . $u_url
   . 'eType=task' . $WC->getCatUrl()
   . '"><img src="images/new.gif" alt="+" class="new" /></a></th>
      </tr>
      <tr class="header">
        <td rowspan="2" class="sorterbottom">!&nbsp;</td>' . $ajax[0] . '
        <td rowspan="2" width="20%" class="sorterbottom">'
   . translate ( 'Task_Title' )
   . '&nbsp;</td>' . $ajax[1] . '
        <td rowspan="2" class="sorterbottom">' . translate ( 'Due' )
   . $dueSpacer . '</td>'
   . $ajax[2] . '
        <td rowspan="2" class="sorterbottom">%</td>' . $ajax[3] . '
      </tr>
      <tr class="header">' . $ajax[4] . $ajax[5] . $ajax[6] . $ajax[7] . '
      </tr>';
  foreach ( $task_list as $E ) {
    // Check UAC.
    $task_owner = $E->getLoginId ();
    $can_access = access_user_calendar ( 'view', $task_owner, '',
        $E->getCalType (), $E->getAccess () );
    if ( $can_access == 0 )
      continue;
    $cal_id = $E->getId ();
    // Generate popup info.
    $linkid = 'pop' . "$cal_id-$key";
    $key++;
    $link = '<a href="view_entry.php?'
     . ( ! $WC->isLogin( $task_owner ) ? 'user=' . $task_owner . '&amp;' : '' )
     . 'eid=' . $cal_id . '"';
    $task_html .= '
      <tr class="task" id="' . $linkid . '" style="background-color:'
     . rgb_luminance ( $bgcolor, $E->getPriority () ) . '">
        <td colspan="2">' . $link . ' title="' . $priorityStr . '">'
     . $E->getPriority () . '</a></td>
        <td class="name" colspan="2" width="50%">&nbsp;' . $link . ' title="'
     . translate ( 'Task Name' ) . ': ' . $E->getName () . '">'
     . substr ( $E->getName (), 0, 15 )
     . ( strlen ( $E->getName () ) > 15 ? '...' : '' ) . '</a></td>
        <td colspan="2">' . $link . ' title="' . translate ( 'Task Due Date' )
     . '">'
     . date_to_str ( $E->getDueDate (), $dateFormatStr, false, false ) . '</a>'
     . '</td>
        <td class="pct" colspan="2">' . $link . ' title="% '
     . translate ( 'Completed' ) . '">' . $E->getPercent () . '</a></td>
      </tr>';
    $row_cnt++;
    // Build special string to pass to popup.
    // TODO:  Move this logic into build_entry_popup ().
		/*
    $smarty->append('eventinfo', build_entry_popup ( 'eventinfo-' 
      . $linkid, $E->getLoginId (),
      $E->getDescription (), translate ( 'Due Time' ) . ':'
       . display_time ( $E->getDueDate (), 0 ) . '</dd><dd>'
       . translate ( 'Due Date' ) . ':'
       . date_to_str ( $E->getDueDate (), '', false )
       . "</dd>\n<dt>" . $priorityStr . ":</dt>\n<dd>" . $E->getPriority ()
       . '-' . $pri[ceil ( $E->getPriority () / 3 )] . "</dd>\n<dt>"
       . translate ( 'Percent Complete' ) . ":</dt>\n<dd>" . $E->getPercent ()
       . '%', '', $E->getLocation (), $E->getName (), $cal_id ), true );
			*/
  }
  for ( $i = 7; $i > $row_cnt; $i-- ) {
    $task_html .= '<tr><td colspan="8" class="filler">&nbsp;</td></tr>' . "\n";
  }
  $task_html .= "</table>\n";
  return $task_html;
}


function display_time ( $intime, $control = 0, $format = '' ) {
  global $SERVER_TIMEZONE;

  if ( $control & 4 ) {
    $currentTZ = getenv ( 'TZ' );
    set_env ( 'TZ', $SERVER_TIMEZONE );
  }
  $t_format = ( empty ( $format ) ? getPref ( 'TIME_FORMAT' ) : $format );
  $tzid = date ( ' T' ); //Default tzid for today.

  if ( strlen ( $intime ) > 8 ) {
    $time = date ( 'His', $intime );
    $tzid = date ( ' T', $intime );
    // $control & 1 = do not do timezone calculations
    if ( $control & 1 ) {
      $time = gmdate ( 'His', $intime );
      $tzid = ' GMT';
    }
	} else if ( strlen ( $intime ) < 3 ) { //we must be getting a simple integer
	   $time  = $intime . '0000';
	} else {
	  $time  = $intime;
	}

  $hour = intval ( $time / 10000 );
  $min = abs ( ( $time / 100 ) % 100 );

  // Prevent goofy times like 8:00 9:30 9:00 10:30 10:00.
  if ( $time < 0 && $min > 0 )
    $hour = $hour - 1;
  while ( $hour < 0 ) {
    $hour += 24;
  }
  while ( $hour > 23 ) {
    $hour -= 24;
  }
  if ( $t_format == '12' ) {
    $ampm = translate ( $hour >= 12 ? 'pm' : 'am' );
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;

    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else
    $ret = sprintf ( "%02d:%02d", $hour, $min );

  if ( $control & 2 )
    $ret .= $tzid;

  // Reset timezone to previous value.
  if ( ! empty ( $currentTZ ) )
    set_env ( 'TZ', $currentTZ );

  return $ret;

}

/* Checks for any unnaproved events.
 *
 * If any are found, display a link to the unapproved events
 * (where they can be approved).
 *
 * If the user is an admin user, also count up any public events.
 * If the user is a nonuser admin, count up events on the nonuser calendar.
 *
 * @param string $user  Current user login
 */
function display_unapproved_events ( $user ) {
  global $is_nonuser, $WC;
  static $retval;

  if ( $is_nonuser )
    return;

  // Don't run this more than once.
  if ( ! empty ( $retval[$user] ) )
    return $retval[$user];

  $app_user_hash = $app_users = $query_params = array ();
  $query_params[] = $user;
  $ret = '';
  $sql = 'SELECT COUNT( weu.cal_id ) FROM webcal_entry_user weu, webcal_entry we
    WHERE weu.cal_id = we.cal_id AND weu.cal_status = \'W\'
    AND ( weu.cal_login_id = ?';

    $app_user_hash[$WC->loginId()] = 1;
    $app_users[] = $WC->loginId();

    $all = ( getPref ( 'NONUSER_ENABLED' )
      // TODO:  Add 'approved' switch to these functions.
      ? array_merge ( get_my_users (), get_my_nonusers () ) : get_my_users () );

    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login_id'];
      if ( access_user_calendar ( 'approve', $x ) &&
          empty ( $app_user_hash[$x] ) ) {
        $app_user_hash[$x] = 1;
        $app_users[] = $x;
      }
    }
    for ( $i = 0, $cnt = count ( $app_users ); $i < $cnt; $i++ ) {
      $query_params[] = $app_users[$i];
      $sql .= ' OR weu.cal_login_id = ? ';
    }

  $rows = dbi_get_cached_rows ( $sql . ' )', $query_params );
  if ( $rows ) {
    $row = $rows[0];
    if ( $row && $row[0] > 0 )
      $ret .= '<!--NOP-->';
  }

  $retval[$user] = $ret;

  return $ret;
}

/* Sends a redirect to the specified page.
 * The database connection is closed and execution terminates in this function.
 *
 * <b>Note:</b>  MS IIS/PWS has a bug that does not allow sending a cookie and a
 * redirect in the same HTTP header.  When we detect that the web server is IIS,
 * we accomplish the redirect using meta-refresh.
 * See the following for more info on the IIS bug:
 * {@link http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4}
 *
 * @param string $url  The page to redirect to.  In theory, this should be an
 *                     absolute URL, but all browsers accept relative URLs
 *                     (like "month.php").
 *
 * @global string    Type of webserver
 * @global array     Server variables
 * @global resource  Database connection
 */
function do_redirect ( $url ) {
  global $_SERVER, $c, $SERVER_SOFTWARE;

  // Replace any '&amp;' with '&' since we don't want that in the HTTP header.
  $url = str_replace ( '&amp;', '&', $url );

  if ( empty ( $SERVER_SOFTWARE ) )
    $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];
  $meta = '';
  if ( ( substr ( $SERVER_SOFTWARE, 0, 5 ) == 'Micro' ) ||
      ( substr ( $SERVER_SOFTWARE, 0, 3 ) == 'WN/' ) )
    $meta = '
    <meta http-equiv="refresh" content="0; url=' . $url . '" />';
  else
    header ( 'Location: ' . $url );

  echo send_doctype ( 'Redirect' ) . $meta . '
  </head>
  <body>
    Redirecting to.. <a href="' . $url . '">here</a>.
  </body>
</html>';
  dbi_close ( $c );
  exit;
}


/* Check for errors and return required HTML for display
 *
 * @param string $nextURL   URL the redirect to
 * @param bool   $redirect  Redirect OR popup Confirmation window
 *
 * @return string  HTML to display.
 *
 * @global string  $error  Current error message
 *
 * @uses print_error_header
 */
function error_check ( $nextURL, $redirect = true ) {
  global $error;

  $ret = '';
  if ( ! empty ( $error ) ) {
    build_header ( '', '', '', 29 );
    $ret .= '
    <h2>' . print_error ( $error ) . '</h2>';
  } else {
    if ( $redirect )
      do_redirect ( $nextURL );

    $ret .= '<html>
  <head></head>
  <body onload="alert ( \'' . translate ( 'Changes successfully saved', true )
     . '\' ); window.parent.location.href=\'' . $nextURL . '\';">';
  }
  return $ret . '
  </body>
</html>';
}

/* Gets the list of external users for an event from the
 * webcal_entry_ext_user table in HTML format.
 *
 * @param int $event_id    Event ID
 * @param int $use_mailto  When set to 1, email address will contain an href
 *                         link with a mailto URL.
 *
 * @return string  The list of external users for an event formated in HTML.
 */
function event_get_external_users ( $event_id, $use_mailto = 0 ) {
  $ret = '';

  $rows = dbi_get_cached_rows ( 'SELECT cal_fullname, cal_email
    FROM webcal_entry_ext_user WHERE cal_id = ? ORDER by cal_fullname',
    array ( $event_id ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];

      // Remove [\d] if duplicate name.
      $ret .= trim ( preg_replace ( '/\[[\d]]/', '', $row[0] ) );
      if ( strlen ( $row[1] ) ) {
        $row_one = htmlentities ( " <$row[1]>" );
        $ret .= "\n" . ( $use_mailto
          ? ' <a href="mailto:' . "$row[1]\">$row_one</a>" : $row_one );
      }
    }
  }
  return $ret;
}

/* Fakes an email for testing purposes.
 *
 * @param string $mailto  Email address to send mail to
 * @param string $subj    Subject of email
 * @param string $text    Email body
 * @param string $hdrs    Other email headers
 *
 * @ignore
 */
function fake_mail ( $mailto, $subj, $text, $hdrs ) {
  echo 'To: ' . $mailto . '<br />
Subject: ' . $subj . '<br />
' . nl2br ( $hdrs ) . '<br />
' . nl2br ( $text );
}


/* Generate Application Name
 *
 * @param bool $custom  Allow user name to be displayed
 */
function generate_application_name ( $custom = true ) {
  global $fullname;

  $application_name = getPref ( 'APPLICATION_NAME' , 2, '', 'Title' );

  return ( $custom && ! empty ( $fullname ) && $application_name == 'myname'
    ? $fullname
    : ( $application_name == 'Title' || $application_name == 'myname'
      ? ( function_exists ( 'translate' ) ? translate ( 'Title' ) : 'Title' )
      : htmlspecialchars ( $application_name ) ) );
}

/* Generate HTML to add Printer Friendly Link.
 *
 *
 * @return string  URL to printer friendly page.
 *
 * @global array SERVER
 * @global string SCRIPT name
 */
function generate_printer_friendly ( ) {
  global $_SERVER;

  // Set this to enable printer icon in top menu.
  $href = _WC_SCRIPT . '?'
   . ( ! empty ( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '' );
  $href .= ( substr ( $href, -1 ) == '?' ? '' : '&' ) . 'friendly=1';
  return $href;
}

//save color values to cached CSS file
function generate_CSS ( $replace=false ) {
  global $WC;

  $CSShandle = ( _WC_SCRIPT == 'admin.php' ? 'default' : md5($WC->userLoginId()) ); 
	$CSSfile = 'cache/css/' . $CSShandle . '.css';
	if ( $replace &&  @file_exists ( $CSSfile ) ) {	
	  unlink ( $CSSfile );
	}
	if ( ! $replace && ! @file_exists ( $CSSfile ) ) {
    ob_start ();
    include 'includes/styles.php';
    $tmpCSS = ob_get_contents ();
    ob_end_clean ();
  	$fd = @fopen ( $CSSfile, 'w+b', false );
    if ( ! empty ( $fd ) ) {
      fwrite ( $fd, $tmpCSS );
      fclose ( $fd );
      chmod ( $CSSfile, 0666 );
    }
	}
}

/* Returns all the dates a specific event will fall on
 * accounting for the repeating.
 *
 * Any event with no end will be assigned one.
 *
 * @param int $date          Initial date in raw format
 * @param string $rpt_type   Repeating type as stored in the database
 * @param int $interval      Interval of repetition
 * @param array $ByMonth     Array of ByMonth values
 * @param array $ByWeekNo    Array of ByWeekNo values
 * @param array $ByYearDay   Array of ByYearDay values
 * @param array $ByMonthDay  Array of ByMonthDay values
 * @param array $ByDay       Array of ByDay values
 * @param array $BySetPos    Array of BySetPos values
 * @param int $Count         Max number of events to return
 * @param string $Until      Last day of repeat
 * @param string $Wkst       First day of week ('MO' is default)
 * @param array $ex_days     Array of exception dates for this event in YYYYMMDD format
 * @param array $inc_days    Array of inclusion dates for this event in YYYYMMDD format
 * @param int $jump          Date to short cycle loop counts to,
 *                           also makes output YYYYMMDD
 *
 * @return array  Array of dates (in UNIX time format).
 */
function get_all_dates ( $date, $rpt_type, $interval = 1, $ByMonth = '',
  $ByWeekNo = '', $ByYearDay = '', $ByMonthDay = '', $ByDay = '', $BySetPos = '',
  $Count = 999, $Until = null, $Wkst = 'MO', $ex_days = '', $inc_days = '',
  $jump = '' ) {
  global $WC;
	
  //make sure we don't loop endlessly
	if ( $interval == 0 ) $interval = 1;
	
  $dateYmd = date ( 'Ymd', $date );
  $hour = date ( 'H', $date );
  $minute = date ( 'i', $date );

  if ( $Until == null && $Count == 999 ) {
    // Check for $CONFLICT_REPEAT_MONTHS months into future for conflicts.
    $thisyear = substr ( $dateYmd, 0, 4 );
    $thismonth = substr ( $dateYmd, 4, 2 ) + getPref ( 'CONFLICT_REPEAT_MONTHS' );
    $WC->thisday = substr ( $dateYmd, 6, 2 );
    if ( $thismonth > 12 ) {
      $thisyear++;
      $thismonth -= 12;
    }
    $realend = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
  } else
    $realend = ( $Count != 999
      ? mktime ( 0, 0, 0, 1, 1, 2038 ) // Set $until so some ridiculous value.
      : $Until );

  $ret = array ();
  $date_excluded = false; //Flag to track ical results.
  // Do iterative checking here.
  // I floored the $realend so I check it against the floored date.
  if ( $rpt_type && ( floor ( $date / ONE_DAY ) * ONE_DAY ) < $realend ) {
    $cdate = $date;
    $n = 0;
    if ( ! empty ( $ByDay ) )
      $byday = explode ( ',', $ByDay );
    if ( ! empty ( $ByMonth ) )
      $bymonth = explode ( ',', $ByMonth );
    if ( ! empty ( $ByMonthDay ) )
      $bymonthday = explode ( ',', $ByMonthDay );
    if ( ! empty ( $BySetPos ) )
      $bysetpos = explode ( ',', $BySetPos );
    if ( ! empty ( $ByWeekNo ) )
      $byweekno = explode ( ',', $ByWeekNo );
    if ( ! empty ( $ByYearDay ) )
      $byyearday = explode ( ',', $ByYearDay );

    if ( $rpt_type == 'daily' ) {
      // Skip to this year/month
      // if called from query_events and we don't need count.
      if ( ! empty ( $jump ) && $Count == 999 ) {
        while ( $cdate < $jump ) {
          $cdate = add_dstfree_time ( $cdate, ONE_DAY, $interval );
        }
      } while ( $cdate <= $realend && $n <= $Count ) {
        // Check RRULE items.
        if ( ! empty ( $bymonth ) && !
            in_array ( date ( 'n', $cdate ), $bymonth ) )
          $date_excluded = true;

        if ( ! empty ( $byweekno ) && !
            in_array ( date ( 'W', $cdate ), $byweekno ) )
          $date_excluded = true;

        if ( ! empty ( $byyearday ) ) {
          $doy = date ( 'z', $cdate ); //day of year
          $diy = date ( 'L', $cdate ) + 365; //days in year
          $diyReverse = $doy - $diy -1;
          if ( ! in_array ( $doy, $byyearday ) && !
              in_array ( $diyReverse, $byyearday ) )
            $date_excluded = true;
        }
        if ( ! empty ( $bymonthday ) ) {
          $dom = date ( 'j', $cdate ); //day of month
          $dim = date ( 't', $cdate ); //days in month
          $dimReverse = $dom - $dim -1;
          if ( ! in_array ( $dom, $bymonthday ) && !
              in_array ( $dimReverse, $bymonthday ) )
            $date_excluded = true;
        }
        if ( ! empty ( $byday ) ) {
          $bydayvalues = get_byday ( $byday, $cdate, 'daily', $date );
          if ( ! in_array ( $cdate, $bydayvalues ) )
            $date_excluded = true;
        }
        if ( $date_excluded == false )
          $ret[$n++] = $cdate;

        $cdate = add_dstfree_time ( $cdate, ONE_DAY, $interval );
        $date_excluded = false;
      }
    } elseif ( $rpt_type == 'weekly' ) {
      $r = 0;
      $dow = date ( 'w', $date );
      if ( ! empty ( $jump ) && $Count == 999 ) {
        while ( $cdate < $jump ) {
          $cdate = add_dstfree_time ( $cdate, ONE_WEEK, $interval );
        }
      }
      $cdate = $date - ( $dow * ONE_DAY );
      while ( $cdate <= $realend && $n <= $Count ) {
        if ( ! empty ( $byday ) ) {
          foreach ( $byday as $day ) {
            $td = $cdate + ( $WC->byday_values[$day] * ONE_DAY );
            if ( $td >= $date && $td <= $realend && $n <= $Count )
              $tmp_td = $td;
          }
        } else {
          $td = $cdate + ( $dow * ONE_DAY );
          $cdow = date ( 'w', $td );
          if ( $cdow == $dow )
            $tmp_td = $td;
        }
        if ( ! empty ( $tmp_td ) &&
            ( empty ( $bymonth ) || ( ! empty ( $bymonth ) &&
                in_array ( date ( 'n', $tmp_td ), $bymonth ) ) ) ) {
          $ret[$n++] = $tmp_td;
        }
        $tmp_td = $td = '';
        // Skip to the next week in question.
        $cdate = add_dstfree_time ( $cdate, ONE_WEEK, $interval );
      }
    } elseif ( substr ( $rpt_type, 0, 7 ) == 'monthly' ) {
      $thisyear = substr ( $dateYmd, 0, 4 );
      $thismonth = substr ( $dateYmd, 4, 2 );
      $WC->thisday = substr ( $dateYmd, 6, 2 );
      $hour = date ( 'H', $date );
      $minute = date ( 'i', $date );
      // Skip to this year if called from query_events and we don't need count.
      if ( ! empty ( $jump ) && $Count == 999 ) {
        while ( $cdate < $jump ) {
          $thismonth += $interval;
          $cdate = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
        }
      }
      $cdate = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
      $mdate = $cdate;
      while ( $cdate <= $realend && $n <= $Count ) {
        if ( empty ( $bymonth ) || ( ! empty ( $bymonth ) &&
              in_array ( date ( 'n', $cdate ), $bymonth ) ) ) {
          $bydayvalues = $bymonthdayvalues = $yret = array ();
          if ( isset ( $byday ) )
            $bydayvalues = get_byday ( $byday, $mdate, 'month', $date );

          if ( isset ( $bymonthday ) )
            $bymonthdayvalues = get_bymonthday ( $bymonthday, $mdate,
              $date, $realend );

          if ( isset ( $byday ) && isset ( $bymonthday ) ) {
            $bydaytemp = array_intersect ( $bymonthdayvalues, $bydayvalues );
            $yret = array_merge ( $yret, $bydaytemp );
          } elseif ( isset ( $bymonthday ) )
            $yret = array_merge ( $yret, $bymonthdayvalues );
          elseif ( isset ( $byday ) )
            $yret = array_merge ( $yret, $bydayvalues );
          elseif ( ! isset ( $byday ) && ! isset ( $bymonthday ) )
            $yret[] = $cdate;

          // Must wait till all other BYxx are processed.
          if ( isset ( $bysetpos ) ) {
            $mth = date ( 'm', $cdate );
            sort ( $yret );
            sort ( $bysetpos );
            $setposdate = mktime ( $hour, $minute, 0, $mth, 1, $thisyear );
            $dim = date ( 't', $setposdate ); //Days in month.
            $yretcnt = count ( $yret );
            $bysetposcnt = count ( $bysetpos );
            for ( $i = 0; $i < $bysetposcnt; $i++ ) {
              if ( $bysetpos[$i] > 0 && $bysetpos[$i] <= $yretcnt )
                $ret[] = $yret[$bysetpos[$i] -1];
              else
              if ( abs ( $bysetpos[$i] ) <= $yretcnt )
                $ret[] = $yret[$yretcnt + $bysetpos[$i] ];
            }
          } else
          if ( ! empty ( $yret ) ) { // Add all BYxx additional dates.
            $yret = array_unique ( $yret );
            $ret = array_merge ( $ret, $yret );
          }
          sort ( $ret );
        } //end $bymonth test
        $thismonth += $interval;
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
        $mdate = mktime ( $hour, $minute, 0, $thismonth, 1, $thisyear );
        $n = count ( $ret );
      } //end while
    } elseif ( $rpt_type == 'yearly' ) {
      // This RRULE is VERY difficult to parse because RFC2445 doesn't
      // give any guidance on which BYxxx are mutually exclusive.
      // We will assume that:
      // BYMONTH, BYMONTHDAY, BYDAY go together.
      // BYDAY will be parsed relative to BYMONTH
      // if BYDAY is used without BYMONTH,
      // then it is relative to the current year (i.e 20MO).
      $thisyear = substr ( $dateYmd, 0, 4 );
      $thismonth = substr ( $dateYmd, 4, 2 );
      $WC->thisday = substr ( $dateYmd, 6, 2 );
      // Skip to this year if called from query_events and we don't need count.
      if ( ! empty ( $jump ) && $Count == 999 ) {
        $jumpY = date ( 'Y', $jump );
        while ( date ( 'Y', $cdate ) < $jumpY ) {
          $thisyear += $interval;
          $cdate = mktime ( $hour, $minute, 0, 1, 1, $thisyear );
        }
      }
      $cdate = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
      while ( $cdate <= $realend && $n <= $Count ) {
        $yret = array ();
        $ycd = date ( 'Y', $cdate );
        $fdoy = mktime ( 0, 0, 0, 1, 1, $ycd ); //first day of year
        $fdow = date ( 'w', $fdoy ); //day of week first day of year
        $ldoy = mktime ( 0, 0, 0, 12, 31, $ycd ); //last day of year
        $ldow = date ( 'w', $ldoy ); //day of week last day  of year
        $dow = date ( 'w', $cdate ); //day of week
        $week = date ( 'W', $cdate ); //ISO 8601 number of week
        if ( isset ( $bymonth ) ) {
          foreach ( $bymonth as $month ) {
            $mdate = mktime ( $hour, $minute, 0, $month, 1, $ycd );
            $bydayvalues = $bymonthdayvalues = array ();
            if ( isset ( $byday ) )
              $bydayvalues = get_byday ( $byday, $mdate, 'month', $date );

            if ( isset ( $bymonthday ) )
              $bymonthdayvalues = get_bymonthday ( $bymonthday, $mdate,
                $date, $realend );

            if ( isset ( $byday ) && isset ( $bymonthday ) ) {
              $bydaytemp = array_intersect ( $bymonthdayvalues, $bydayvalues );
              $yret = array_merge ( $yret, $bydaytemp );
            } else
              $yret = ( isset ( $bymonthday )
                ? array_merge ( $yret, $bymonthdayvalues )
                : ( isset ( $byday )
                  ? array_merge ( $yret, $bydayvalues )
                  : mktime ( $hour, $minute, 0, $month, $WC->thisday, $ycd ) ) );
          } //end foreach bymonth
        } elseif ( isset ( $byyearday ) ) { // end if isset bymonth
          foreach ( $byyearday as $yearday ) {
            ereg ( '([-\+]{0,1})?([0-9]{1,3})', $yearday, $match );
            if ( $match[1] == '-' && ( $cdate >= $date ) )
              $yret[] =
              mktime ( $hour, $minute, 0, 12, 31 - $match[2] - 1, $thisyear );
            else
            if ( ( $n <= $Count ) && ( $cdate >= $date ) )
              $yret[] = mktime ( $hour, $minute, 0, 1, $match[2], $thisyear );
          }
        } elseif ( isset ( $byweekno ) ) {
          $wkst_date = ( $Wkst == 'SU' ? $cdate + ONE_DAY : $cdate );
          if ( isset ( $byday ) )
            $bydayvalues = get_byday ( $byday, $cdate, 'year', $date );

          if ( in_array ( $week, $byweekno ) ) {
            if ( isset ( $bydayvalues ) ) {
              foreach ( $bydayvalues as $bydayvalue ) {
                if ( $week == date ( 'W', $bydayvalue ) )
                  $yret[] = $bydayvalue;
              }
            } else
              $yret[] = $cdate;
          }
        } elseif ( isset ( $byday ) ) {
          $bydayvalues = get_byday ( $byday, $cdate, 'year', $date );
          if ( ! empty ( $bydayvalues ) )
            $yret = array_merge ( $yret, $bydayvalues );
        } else // No Byxx rules apply.
          $ret[] = $cdate;

        // Must wait till all other BYxx are processed.
        if ( isset ( $bysetpos ) ) {
          sort ( $yret );
          for ( $i = 0, $bysetposcnt = count ( $bysetpos ); $i < $bysetposcnt;
            $i++ ) {
            $ret[] = ( $bysetpos[$i] > 0
              ? $yret[$bysetpos[$i] -1]
              : $yret[count ( $yret ) + $bysetpos[$i] ] );
          }
        } else
        if ( ! empty ( $yret ) ) { // Add all BYxx additional dates.
          $yret = array_unique ( $yret );
          $ret = array_merge ( $ret, $yret );
        }
        sort ( $ret );
        $n = count ( $ret );
        $thisyear += $interval;
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $WC->thisday, $thisyear );
      }
    } //end if rpt_type
  }
	
	//Add in initial date to repeat array so it can be an exception if desired
	$ret[] = $date;
  if ( ! empty ( $ex_days ) ) {
    foreach ( $ex_days as $ex_day ) {
      for ( $i = 0, $cnt = count ( $ret ); $i < $cnt;$i++ ) {
        if ( isset ( $ret[$i] ) &&
            date ( 'Ymd', $ret[$i] ) == substr ( $ex_day, 0, 8 ) )
          unset ( $ret[$i] );
      }
      // Remove any unset elements.
      sort ( $ret );
    }
  }
  if ( ! empty ( $inc_days ) ) {
    foreach ( $inc_days as $inc_day ) {
      $ret[] = strtotime ( $inc_day );
    }
  }
  // Remove any unset elements.
  sort ( $ret );
  // We want results in YYYYMMDD format.
  if ( ! empty ( $jump ) ) {
    for ( $i = 0, $retcnt = count ( $ret ); $i < $retcnt;$i++ ) {
      if ( isset ( $ret[$i] ) )
        $ret[$i] = date ( 'Ymd', $ret[$i] );
    }
  }
  return $ret;
}

/* Get the dates the correspond to the byday values.
 *
 * @param array $byday   ByDay values to process (MO,TU,-1MO,20MO...)
 * @param string $cdate  First day of target search (Unix timestamp)
 * @param string $type   Month, Year, Week (default = month)
 * @param string $date   First day of event (Unix timestamp)
 *
 * @return array  Dates that match ByDay (YYYYMMDD format).
 */
function get_byday ( $byday, $cdate, $type = 'month', $date ) {
  global $WC;

  if ( empty ( $byday ) )
    return;

  $ret = array ();
  $hour = date ( 'H', $cdate );
  $minute = date ( 'i', $cdate );
  $mth = date ( 'm', $cdate );
  $yr = date ( 'Y', $cdate );
  if ( $type == 'month' ) {
    $ditype = date ( 't', $cdate ); //Days in month.
    $fday = mktime ( 0, 0, 0, $mth, 1, $yr ); //First day of month.
    $lday = mktime ( 0, 0, 0, $mth + 1, 0, $yr ); //Last day of month.
    $month = $mth;
  } elseif ( $type == 'year' ) {
    $ditype = date ( 'L', $cdate ) + 365; //Days in year.
    $fday = mktime ( 0, 0, 0, 1, 1, $yr ); //First day of year.
    $lday = mktime ( 0, 0, 0, 12, 31, $yr ); //Last day of year.
    $month = 1;
  } elseif ( $type == 'daily' ) {
    $fday = $lday = $cdate;
    $month = $mth;
  } else
    // We'll see if this is needed.
    return;

  $fdow = date ( 'w', $fday ); //Day of week first day of $type.
  $ldow = date ( 'w', $lday ); //Day of week last day of $type
  foreach ( $byday as $day ) {
    $byxxxDay = '';
    $dayTxt = substr ( $day, -2, 2 );
    $dayOffset = substr_replace ( $day, '', -2, 2 );
    $dowOffset = ( ( -1 * $WC->byday_values[$dayTxt] ) + 7 ) % 7; //SU=0, MO=6, TU=5...
    if ( is_numeric ( $dayOffset ) && $dayOffset > 0 ) {
      // Offset from beginning of $type.
      $dayOffsetDays = ( ( $dayOffset - 1 ) * 7 ); //1 = 0, 2 = 7, 3 = 14...
      $forwardOffset = $WC->byday_values[$dayTxt] - $fdow;
      if ( $forwardOffset < 0 )
        $forwardOffset += 7;

      $domOffset = ( 1 + $forwardOffset + $dayOffsetDays );
      if ( $domOffset <= $ditype ) {
        $byxxxDay = mktime ( $hour, $minute, 0, $month, $domOffset, $yr );
        if ( $mth == date ( 'm', $byxxxDay ) && $byxxxDay > $date )
          $ret[] = $byxxxDay;
      }
    } else
    if ( is_numeric ( $dayOffset ) ) { // Offset from end of $type.
      $dayOffsetDays = ( ( $dayOffset + 1 ) * 7 ); //-1 = 0, -2 = 7, -3 = 14...
      $byxxxDay = mktime ( $hour, $minute, 0, $month + 1,
        ( 0 - ( ( $ldow + $dowOffset ) % 7 ) + $dayOffsetDays ), $yr );
      if ( $mth == date ( 'm', $byxxxDay ) && $byxxxDay > $date )
        $ret[] = $byxxxDay;
    } else {
      if ( $type == 'daily' ) {
        if ( ( date ( 'w', $cdate ) == $WC->byday_values[$dayTxt] ) && $cdate > $date )
          $ret[] = $cdate;
      } else {
        for ( $i = 1; $i <= $ditype; $i++ ) {
          $loopdate = mktime ( $hour, $minute, 0, $month, $i, $yr );
          if ( ( date ( 'w', $loopdate ) == $WC->byday_values[$dayTxt] ) &&
            $loopdate > $date ) {
            $ret[] = $loopdate;
            $i += 6; //Skip to next week.
          }
        }
      }
    }
  }
  return $ret;
}

/* Get the dates the correspond to the bymonthday values.
 *
 * @param array $bymonthday  ByMonthDay values to process (1,2,-1,-2...)
 * @param string $cdate      First day of target search (Unix timestamp)
 * @param string $date       First day of event (Unix timestamp)
 * @param string $realend    Last day of event (Unix timestamp)
 *
 * @return array  Dates that match ByMonthDay (YYYYMMDD format).
 */
function get_bymonthday ( $bymonthday, $cdate, $date, $realend ) {
  if ( empty ( $bymonthday ) )
    return;

  $ret = array ();
  $dateYmHi = date ( 'YmHi', $cdate );
  $dim = date ( 't', $cdate ); //Days in month.
  $yr = substr ( $dateYmHi, 0, 4 );
  $mth = substr ( $dateYmHi, 4, 2 );
  $hour = substr ( $dateYmHi, 6, 2 );
  $minute = substr ( $dateYmHi, 8, 2 );
  foreach ( $bymonthday as $monthday ) {
    $byxxxDay = mktime ( $hour, $minute, 0, $mth,
      ( $monthday > 0 ? $monthday : $dim + $monthday + 1 ), $yr );
    if ( $byxxxDay > $date )
      $ret[] = $byxxxDay;
  }
  return $ret;
}

/* Get categories for a given event id
 * Global categories are changed to negative numbers
 *
 * @param int      $eid  Id of event
 * @param string   $user normally this is $login
 * @param bool     $asterisk Include '*' if Global
 *
 * @return array   Array containing category names.
 */
function get_categories_by_eid ( $eid, $user, $asterisk = false ) {
  global $WC;

  if ( empty ( $eid ) )
    return false;

  $categories = array ();

  $res = dbi_execute ( 'SELECT wc.cat_name, wc.cat_id, wc.cat_owner
    FROM webcal_categories wc, webcal_entry_categories wec WHERE wec.cal_id = ?
    AND wec.cat_id = wc.cat_id AND ( wc.cat_owner = ? OR wc.cat_owner IS NULL )
    ORDER BY wec.cat_order', array ( $eid, ( empty ( $user ) ? $WC->loginId() : $user ) ) );
  while ( $row = dbi_fetch_row ( $res ) ) {
    $categories[ ( empty ( $row[2] ) ? - $row[1] : $row[1] ) ] = $row[0]
     . ( $asterisk && empty ( $row[2] ) ? '*' : '' );
  }
  dbi_free_result ( $res );

  return $categories;
}

/* Gets all the events for a specific date.
 *
 * Events are retreived from the array of pre-loaded events
 * (which was loaded all at once to improve performance).
 *
 * The returned events will be sorted by time of day.
 *
 * @param string $date            Date to get events for in YYYYMMDD format
 *                                in user's timezone
 * @param bool   $get_unapproved  Load unapproved events?
 *
 * @return array  Array of Events.
 */
function get_entries ( $date, $get_unapproved = true ) {
  global $events;
  $ret = array ();
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $event_date = date ( 'Ymd', $events[$i]->getDate () );
    if ( ! $get_unapproved && $events[$i]->getStatus () == 'W' )
      continue;

    if ( $events[$i]->isAllDay () || $events[$i]->isUntimed () ) {
      if ( $events[$i]->getDate () == $date )
        $ret[] = $events[$i];
    } else {
      if ( $event_date == $date )
        $ret[] = $events[$i];
    }
  }
  return $ret;
}

/* Gets all the groups a user is authorized to see
 *
 *
 * @param string $user        Subject User
 * @param bool   $override    Ignore USER_SEES_ONLY_HIS_GROUPS
 *                                
 *
 * @return array  Array of Groups.
 */
function get_groups ( $user='', $override=false ) {
  global $WC;
  
  if ( ! getPref ( 'GROUPS_ENABLED', 2 ) )
    return false;
    
  $owner = ( $user? $user : $WC->userLoginId () );

  // Load list of groups.
  $sql = 'SELECT wg.cal_group_id, wg.cal_name FROM webcal_group wg';

 if ( getPref ( 'USER_SEES_ONLY_HIS_GROUPS', 2 ) ) {
   $sql .= ', webcal_group_user wgu WHERE wg.cal_group_id = wgu.cal_group_id
     AND wgu.cal_login_id = ?';
    $sql_params[] = $owner;
 }

  $res = dbi_execute ( $sql . ' ORDER BY wg.cal_name', $sql_params );

  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
     $groups[] = array (
        'cal_group_id' => $row[0],
       'cal_name' => $row[1]
       );
    }
   dbi_free_result ( $res );
 }
 return $groups;
}

/* Gets the last page stored using {@link remember_this_view ()}.
 *
 * @return string The URL of the last view or an empty string if it cannot be
 *                determined.
 *
 * @global array  Cookies
 */
function get_last_view () {
  $val = ( isset ( $_COOKIE['webcalendar_last_view'] )
    ? str_replace ( '&', '&amp;', $_COOKIE['webcalendar_last_view'] ) : '' );

  SetCookie ( 'webcalendar_last_view', '', 0 );

  return $val;
}

/* Get the moonphases for a given year and month.
 *
 * Will only work if optional moon_phases.php file exists in includes folder.
 *
 * @param int $year   Year in YYYY format
 * @param int $month  Month in m format Jan =1
 *
 * @return array  $key = phase name, $val = Ymd value.
 *
 * @global string (Y/N) Display Moon Phases
 */
function getMoonPhases ( $date ) {
  static $moons;

  if ( ! getPref ( 'DISPLAY_MOON_PHASES' ) )
    return false;

  if ( empty ( $moons ) && file_exists ( 'includes/moon_phases.php' ) ) {
    include_once ( 'includes/moon_phases.php' );
    $moons = calculateMoonPhases ( $date );
  }

  return $moons;
}

/* Gets a list of nonusers.
 *
 * If groups are enabled, this will restrict the list of nonusers to only those
 * that are in the same group(s) as the user (unless the user is an admin) or
 * the nonuser is a public calendar.  We allow admin users to see all users
 * because they can also edit someone else's events (so they may need access to
 * users who are not in the same groups).
 *
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each nonuser's calendar.  If not, then that nonuser
 * is not included in the list.
 *
 * @return array  Array of nonusers, where each element in the array is an array
 *                with the following keys:
 *    - cal_login
 *    - cal_lastname
 *    - cal_firstname
 *    - cal_is_public
 */
function get_my_nonusers ( $user = '', $add_public = false, $reason = 'invite' ) {
  global $is_nonuser, $WC,
  $my_nonuser_array, $my_user_array;

  $this_user = ( ! empty ( $user ) ? $user : $WC->loginId() );
  // Return the global variable (cached).
  if ( ! empty ( $my_nonuser_array[$this_user . $add_public] ) &&
      is_array ( $my_nonuser_array ) )
    return $my_nonuser_array[$this_user . $add_public];

  $u = get_nonuser_cals ();
  if ( getPref ( 'GROUPS_ENABLED' ) && getPref ( 'USER_SEES_ONLY_HIS_GROUPS' ) && ! $WC->isAdmin() ) {
    // Get current user's groups.
    $rows = dbi_get_cached_rows ( 'SELECT cal_group_id FROM webcal_group_user
      WHERE cal_login_id = ?', array ( $this_user ) );
    $groups = $ret = $u_byname = array ();
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $groups[] = $row[0];
      }
    }
    $groupcnt = count ( $groups );

    for ( $i = 0, $cnt = count ( $u ); $i < $cnt; $i++ ) {
      $u_byname[$u[$i]['cal_login_id']] = $u[$i];
    }

    if ( $groupcnt == 0 ) {
      // Eek.  User is in no groups... Return only themselves.
      if ( isset ( $u_byname[$this_user] ) )
        $ret[] = $u_byname[$this_user];

      $my_nonuser_array[$this_user . $add_public] = $ret;
      return $ret;
    }
    // Get other members of current users' groups.
    $sql = 'SELECT DISTINCT( wu.cal_login_id ), cal_lastname, cal_firstname,
      cal_is_public FROM webcal_group_user wgu, webcal_user wu WHERE '
     . ( $add_public ? 'wu.cal_is_public = \'Y\'  OR ' : '' )
     . ' cal_admin = ? OR ( wgu.cal_login_id = wu.cal_login_id AND cal_group_id ';
    if ( $groupcnt == 1 )
      $sql .= '= ? )';
    else {
      // Build count ( $groups ) placeholders separated with commas.
      $placeholders = '';
      for ( $p_i = 0; $p_i < $groupcnt; $p_i++ ) {
        $placeholders .= ( $p_i == 0 ) ? '?' : ', ?';
      }
      $sql .= "IN ( $placeholders ) )";
    }

    // Add $this_user to beginning of query params.
    array_unshift ( $groups, $this_user );
    $sort_order = ( getPref ( 'USER_SORT_ORDER' ) ? 
      'ORDER BY ' . getPref ( 'USER_SORT_ORDER' )  : '' );
    $rows = dbi_get_cached_rows ( $sql . $sort_order, $groups );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        if ( isset ( $u_byname[$row[0]] ) )
          $ret[] = $u_byname[$row[0]];
      }
    }
  } else
    // Groups not enabled... return all nonusers.
    $ret = $u;

  // remove any nonusers that this user does not have required access.
  $newlist = array ();
  for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
    $can_list = access_user_calendar ( $reason, $ret[$i]['cal_login_id'], $this_user );
    if ( $can_list == 'Y' || $can_list > 0 )
      $newlist[] = $ret[$i];
  }
  $ret = $newlist;

  $my_nonuser_array[$this_user . $add_public] = $ret;
  return $ret;
}

/* Gets a list of users.
 *
 * If groups are enabled, this will restrict the list to only those users who
 * are in the same group(s) as this user (unless the user is an admin).  We allow
 * admin users to see all users because they can also edit someone else's events
 * (so they may need access to users who are not in the same groups).
 *
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each user's calendar.  If not, then that user
 * is not included in the list.
 *
 * @return array  Array of users, where each element in the array is an array
 *                with the following keys:
 *    - cal_login
 *    - cal_lastname
 *    - cal_firstname
 *    - cal_is_admin
 *    - cal_email
 *    - cal_password
 *    - cal_fullname
 */
function get_my_users ( $user = '', $reason = 'invite', $nuc='' ) {
  global $is_nonuser, $WC,
  $my_user_array;

  $this_user = ( ! empty ( $user ) ? $user : $WC->loginId() );
  // Return the global variable (cached).
  if ( ! empty ( $my_user_array[$this_user][$reason] ) &&
      is_array ( $my_user_array ) )
    return $my_user_array[$this_user][$reason];

  if ( getPref ( 'GROUPS_ENABLED' ) && getPref ( 'USER_SEES_ONLY_HIS_GROUPS' ) && !
    $WC->isAdmin() ) {
    // Get groups with current user as member.
    $rows = dbi_get_cached_rows ( 'SELECT cal_group_id FROM webcal_group_user
      WHERE cal_login_id = ?', array ( $this_user ) );
    $groups = $ret = $u_byname = array ();
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $groups[] = $row[0];
      }
    }
    $groupcnt = count ( $groups );

    $u = $WC->User->getUsers ();
    if ( $WC->isNonuserAdmin() && $nuc === true )
      $u = array_merge ( get_my_nonusers (), $u );

    for ( $i = 0, $cnt = count ( $u ); $i < $cnt; $i++ ) {
      $u_byname[$u[$i]['cal_login_id']] = $u[$i];
    }

    if ( $groupcnt == 0 ) {
      // Eek.  User is in no groups... Return only themselves.
      if ( isset ( $u_byname[$this_user] ) )
        $ret[] = $u_byname[$this_user];

      $my_user_array[$this_user][$reason] = $ret;
      return $ret;
    }
    // Get other members of users' groups.
    $sql = 'SELECT DISTINCT(wgu.cal_login_id), wu.cal_lastname,
      wu.cal_firstname FROM webcal_group_user  wgu LEFT JOIN webcal_user wu
      ON wgu.cal_login_id = wu.cal_login_id WHERE cal_group_id ';
    if ( $groupcnt == 1 )
      $sql .= '= ?';
    else {
      // Build count ( $groups ) placeholders separated with commas.
      $placeholders = '';
      for ( $p_i = 0; $p_i < $groupcnt; $p_i++ ) {
        $placeholders .= ( $p_i == 0 ) ? '?' : ', ?';
      }
      $sql .= "IN ( $placeholders )";
    }
    $sort_order = ( getPref ( 'USER_SORT_ORDER' ) ? 
      'ORDER BY ' . getPref ( 'USER_SORT_ORDER' ) . ', ' : '' );
    $rows = dbi_get_cached_rows ( $sql . $sort_order
       . 'wgu.cal_login_id', $groups );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        if ( isset ( $u_byname[$row[0]] ) )
          $ret[] = $u_byname[$row[0]];
      }
    }
  } else
    // Groups not enabled... return all users.
    $ret = $WC->User->getUsers ( $nuc );

  // remove any users that this user does not have required access.
  $newlist = array ();
  for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
    $can_list = access_user_calendar ( $reason, $ret[$i]['cal_login_id'], $this_user );
    if ( $can_list == 'Y' || $can_list > 0 )
      $newlist[] = $ret[$i];
  }
  $ret = $newlist;

  $my_user_array[$this_user][$reason] = $ret;
  return $ret;
}

/* Gets a list of nonuser calendars and return info in an array.
 *
 * @param string $user    Login of admin of the nonuser calendars
 * @param bool   $remote  Return only remote calendar  records
 *
 * @return array  Array of nonuser cals, where each is an array with the
 *                following fields:
 * - <var>cal_login</var>
 * - <var>cal_lastname</var>
 * - <var>cal_firstname</var>
 * - <var>cal_admin</var>
 * - <var>cal_fullname</var>
 * - <var>cal_is_public</var>
 */
function get_nonuser_cals ( $user = '', $remote = false ) {
  $count = 0;
  $query_params = $ret = array ();
  $sql = 'SELECT cal_login_id, cal_login, cal_firstname, cal_lastname, 
	  cal_admin, cal_is_public, cal_url, cal_selected, cal_view_part
		FROM webcal_user 
		WHERE cal_is_nuc = \'Y\' AND cal_url IS '
   . ( $remote == false ? '' : 'NOT ' ) . 'NULL ';

  if ( $user != '' ) {
    $sql .= 'AND  cal_admin = ?';
    $query_params[] = $user;    
  }
  $sql .= ' ORDER BY cal_lastname, cal_firstname';
  $rows = dbi_get_cached_rows ( $sql,
    $query_params );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $ret[$count++] = array (
        'cal_login_id'  => $row[0],
        'cal_login'     => $row[1],
        'cal_fullname'  => $row[2] . ' ' . $row[3],
        'cal_admin'     => $row[4],
        'cal_is_public' => $row[5],
        'cal_url'       => $row[6],
				'cal_selected'  => $row[7],
				'cal_view_part' => $row[8],
				'selected'      => ''
        );
    }
  }

  // remove any users that this user does not have 'view' access to.
  $newlist = array ();
  for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
    if ( access_user_calendar ( 'view', $ret[$i]['cal_login_id'] ) )
      $newlist[] = $ret[$i];
  }
  $ret = $newlist;
  return $ret;
}

/* Gets a preference setting for the specified user.
 *
 * If no value is found in the database,
 * then the system default setting will be returned unless usesys= false
 *
 * @param string $setting  Name of the setting
 * @param bool   $control  0 return user pref only
 *                         1 return user pref then system pref
 *                         2 return system pref only
 *                         4 don't use static or convert Y/N to bool
 * @param string $user     User login we are getting preference for
 * @param string $defVal   Value to be returned if no setting found
 *
 * @return string  The value found in the webcal_user_pref table for the
 *                 specified setting or the sytem default if no user settings
 *                 was found if allowed by $control.
 */
function getPref ( $setting, $control=1, $user='', $defVal='' ) {
  global  $WC;
  static $sysConfig, $userPref;
  
  $ret = $defVal;
  
	//clear static variables to avoid returning bools if literals req.
	if ( $control & 4 ) {
	  unset ( $sysConfig );
		unset ( $userPref );
	}
  //load webcal_config values if not already loaded
  if ( $control%4 > 0 && empty ( $sysConfig ) ) 
    $sysConfig = loadConfig ();
 
   //load webcal_user_pref values if not already loaded
  if ( $control%4 < 2 && empty ( $userPref ) ) {
    $userPref = loadPreferences ();
	}
     
  if ( $control%4 > 0 && isset ( $sysConfig[$setting] ) )
    $ret = $sysConfig[$setting];
    
  //get a user's prefence if not requesting system only
  if ( $user && ! $WC->isLogin( $user )  && $control < 2 ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_value FROM webcal_user_pref
      WHERE cal_login_id = ? AND cal_setting = ?', array ( $user, $setting ) );
    if ( $rows ) {
      $row = $rows[0];
      if ( $row && isset ( $row[0] ) )
        $ret = $row[0];
    }
  } else if ( $control%4 < 2 ){ //we'll get the value from the userPref array
    if ( isset ( $userPref[$setting] ) ) {
      $ret = $userPref[$setting];
    }
  }
	
  //handle Y/N variables
	if ( ! ( $control & 4 ) ) {
    if  ( $ret == 'Y' )
      $ret = true;
    else if ( ! isset ( $ret ) || $ret == 'N' )
      $ret = false;
	}

  return $ret;
}

/* Gets user's preferred view.
 *
 * The user's preferred view is stored in the STARTVIEW  variable.
 * This is loaded from the user preferences (or system settings
 * if there are no user prefererences.)
 *
 * @param string $indate  Date to pass to preferred view in YYYYMMDD format
 * @param string $args    Arguments to include in the URL (such as "user=joe")
 *
 * @return string  URL of the user's preferred view.
 */
function get_preferred_view ( $indate = '', $args = '' ) {
  global $thisdate, $user, $WC;

  // We want user's to set  their pref on first login.
  $url = getPref ( 'STARTVIEW' );
  if ( ! $url)
    return false;

  // Prevent endless looping
  // if preferred view is custom and viewing others is not allowed.
  if ( substr ( $url, 0, 5 ) == 'view_' && ! getPref ( 'ALLOW_VIEW_OTHER' ) && !
      $WC->isAdmin() )
    $url = 'month.php';

  if ( ! access_can_view_page ( $url ) ) {
    if ( access_can_access_function ( ACCESS_DAY ) )
      $url = 'day.php';
    else
    if ( access_can_access_function ( ACCESS_MONTH ) )
      $url = 'month.php';
    else
    if ( access_can_access_function ( ACCESS_WEEK ) )
      $url = 'week.php';
    // At this point, this user cannot access the view set in their preferences
    // (and they cannot update their preferences), and they cannot view any of
    // the standard day/month/week/year pages.  All that's left is either
    // a custom view that was created by them, or a global view.
    $views = loadViews ();
    if ( count ( $views ) > 0 )
      $url = $views[0]['url'];
  }

  $url = str_replace ( '&amp;', '&', $url );
  $url = str_replace ( '&', '&amp;', $url );

  $xdate = empty ( $indate ) ? $thisdate : $indate;

  $url .= ( empty ( $xdate ) ? '' : ( strstr ( $url, '?' ) ? '&amp;' : '?' )
     . 'date=' . $xdate );
  $url .= ( empty ( $args ) ? '' : ( strstr ( $url, '?' ) ? '&amp;' : '?' )
     . $args );

  return $url;
}

/* Gets all the repeating events for the specified date.
 *
 * <b>Note:</b>
 * The global variable <var>$repeated_events</var> needs to be
 * set by calling {@link read_repeated_events()} first.
 *
 * @param string $user            Username
 * @param string $date            Date to get events for in YYYYMMDD format
 * @param bool   $get_unapproved  Include unapproved events in results?
 *
 * @return mixed  The query result resource on queries (which can then be
 *                passed to {@link dbi_fetch_row()} to obtain the results), or
 *                true/false on insert or delete queries.
 *
 * @global array  Array of {@link RepeatingEvent}s
 *                retreived using {@link read_repeated_events()}
 */
function get_repeating_entries ( $user, $dateYmd, $get_unapproved = true ) {
  global $repeated_events;

  $n = 0;
  $ret = array ();
  for ( $i = 0, $cnt = count ( $repeated_events ); $i < $cnt; $i++ ) {
    if ( ( $repeated_events[$i]->getStatus () == 'A' || $get_unapproved ) &&
        in_array ( $dateYmd, $repeated_events[$i]->getRepeatAllDates () ) )
      $ret[$n++] = $repeated_events[$i];
  }
  return $ret;
}

/* Gets all the tasks for a specific date.
 *
 * Events are retreived from the array of pre-loaded tasks
 * (which was loaded all at once to improve performance).
 *
 * The returned tasks will be sorted by time of day.
 *
 * @param string $date            Date to get tasks for in YYYYMMDD format
 * @param bool   $get_unapproved  Load unapproved events?
 *
 * @return array  Array of Tasks.
 */
function get_tasks ( $date, $get_unapproved = true ) {
  global $tasks;
  $ret = array ();
  $today = date ( 'Ymd' );
  for ( $i = 0, $cnt = count ( $tasks ); $i < $cnt; $i++ ) {
    // In case of data corruption (or some other bug...).
    if ( empty ( $tasks[$i] ) || $tasks[$i]->getId () == '' ||
        ( ! $get_unapproved && $tasks[$i]->getStatus () == 'W' ) )
      continue;
    $due_date = date ( 'Ymd', $tasks[$i]->getDueDate () );
    // Make overdue tasks float to today.
    if ( ( $date == $today && $due_date < $today ) || $due_date == $date )
      $ret[] = $tasks[$i];
  }
  return $ret;
}

/* Get plugins available to the current user.
 *
 * Do this by getting a list of all plugins that are not disabled by the
 * administrator and make sure this user has not disabled any of them.
 *
 * It's done this was so that when an admin adds a new plugin,
 * it shows up on each users system automatically (until they disable it).
 *
 * @return array  Plugins available to current user.
 *
 * @ignore
 */
function get_user_plugin_list () {
  $ret = array ();
  $all_plugins = get_plugin_list ();
  for ( $i = 0, $cnt = count ( $all_plugins ); $i < $cnt; $i++ ) {
    if ( $GLOBALS[$all_plugins[$i] . '.disabled'] != 'N' )
      $ret[] = $all_plugins[$i];
  }
  return $ret;
}

/* Get event ids for all events this user is a participant.
 *
 * @param mixed  $sql_params Sql values to test for
 * @param bool $unique Return only events that user is sole participant
 * @param string $sql Optional SQL statement (used by purge.php)
 */
function get_event_ids ( $sql_params, $unique=true, $sql='' ) {
  $events = array ();
	//we may be passing only an int value, so convert it if needed
	if ( ! is_array ( $sql_params ) )
	  $sql_params = array ( $sql_params );
	$def_sql = 'SELECT we.cal_id 
	  FROM webcal_entry we, webcal_entry_user weu
    WHERE we.cal_id = weu.cal_id AND weu.cal_login_id = ?';
	$sql = ( ! empty ( $sql ) ? $sql : $def_sql );
  $res = dbi_execute ( $sql, $sql_params );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }
	
	if ( $unique ) {
	  // Now count number of participants in each event...
    $unique_events = array ();
    for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
      $res = dbi_execute ( "SELECT COUNT(*) FROM webcal_entry_user " .
        "WHERE cal_id = ?" , array ( $events[$i] ) );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) {
          if ( $row[0] == 1 )
            $unique_events[] = $events[$i];
        }
        dbi_free_result ( $res );
      }
		}
	  $events = $unique_events;
  }
  return $events;
}

/* Identify user's browser.
 *
 * Returned value will be one of:
 * - "Mozilla/5" = Mozilla (open source Mozilla 5.0)
 * - "Mozilla/[3,4]" = Netscape (3.X, 4.X)
 * - "MSIE 4" = MSIE (4.X)
 *
 * @return string  String identifying browser.
 *
 * @ignore
 */
function get_web_browser () {
  $agent = getenv ( 'HTTP_USER_AGENT' );
  if ( ereg ( 'MSIE [0-9]', $agent ) )
    return 'MSIE';
  if ( ereg ( 'Mozilla/[234]', $agent ) )
    return 'Netscape';
  if ( ereg ( 'Mozilla/[5678]', $agent ) )
    return 'Mozilla';
  return 'Unknown';
}

/* Gets the previous weekday of the week containing the specified date.
 *
 * If the date specified is a Sunday, then that date is returned.
 *
 * @param int $year   Year
 * @param int $month  Month (1-12)
 * @param int $day    Day (1-31)
 *
 * @return int  The date (in UNIX timestamp format).
 */
function get_weekday_before ( $year, $month, $day = 2 ) {
  global $WC;
 
  $week_start = getPref ( 'WEEK_START' );
  if ( empty ( $week_start ) ) $week_start = 0;
  // Construct string like 'last Sun'.
  $laststr = 'last ' . $WC->weekday_names[$week_start];
  // We default day=2 so if the 1ast is Sunday or Monday it will return the 1st.
  $newdate = strtotime ( $laststr,
    mktime ( 0, 0, 0, $month, $day, $year ) + $GLOBALS['tzOffset'] );
  // Check DST and adjust newdate.
  while ( date ( 'w', $newdate ) == date ( 'w', $newdate + ONE_DAY ) ) {
    $newdate += ONE_HOUR;
  }
  return $newdate;
}


/* Calculate event rollover to next day and add partial event as needed.
 *
 * Create a cloned event on the fly as needed to display in next day slot.
 * The event times will be adjusted so that the total of all times will
 * equal the total time of the original event.  This function will get called
 * recursively until all time has been accounted for.
 *
 * @param mixed $item    Event Object
 * @param int   $i       Current count of event array
 * @param bool  $parent  flag to keep track of the original event object
 *
 * $global array     $result        Array of events
 * @global string    (Y/N)          Do we want to use cross day display
 * @staticvar int    $realEndTS     The true end of the original event
 * @staticvar string $originalDate  The start date of the original event
 * @staticvar mixed  $originalItem  The original event object
*/
function getOverLap ( $item, $i, $parent = true ) {
  global $result;
  static $originalDate, $originalItem, $realEndTS;

  if ( getPref ( 'DISABLE_CROSSDAY_EVENTS' ) )
    return false;

  $lt = localtime ( $item->getDate () );
  $recurse = 0;

  $midnight = gmmktime ( - ( $item->getDate ( 'Z' ) / ONE_HOUR ),
    0, 0, $lt[4] + 1, $lt[3] + 1, $lt[5] );

  $realEndTS = $item->getEndDate ();
	if ( $parent ) {
    $originalDate = $item->getDate ();
    $originalItem = $item;
  }
  $new_duration = ( $realEndTS - $midnight ) / 60;
	//do_debug ( print_r ( $lt, true ) ) ;
	//do_debug ( date ( 'YmdHis', $realEndTS) . ' ' . date ( 'YmdHis', $midnight).' '. $new_duration  );
  if ( $new_duration > 1440 ) {
    $new_duration = 1439;
    $recurse = 1;
  }

  if ( $realEndTS > $midnight ) {
    $result[$i] = clone ( $originalItem );
    $result[$i]->setClone ( $originalDate );
    $result[$i]->setDuration ( $new_duration );
   // $result[$i]->setTime ( gmdate ( 'G0000', $midnight ) );
    $result[$i]->setDate ( $midnight );
    $result[$i]->setName ( $originalItem->getName () . ' ('
       . translate ( 'cont.' ) . ')' );

    $i++;
    if ( $parent )
      $item->setDuration ( ( ( $midnight - $item->getDate () ) / 60 ) -1 );
  }
  // Call this function recursively until duration < ONE_DAY.
  if ( $recurse == 1 )
   getOverLap ( $result[$i -1], $i, false );
}

/* Hack to implement clone () for php4.x.
 *
 * @param mixed  Event object
 *
 * @return mixed  Clone of the original object.
 */
if ( version_compare ( phpversion (), '5.0' ) < 0 ) {
  eval ( '
    function clone ($item) {
      return $item;
    }
    ' );
}

/* Get the reminder data for a given entry id.
 *
 * @param int $eid        cal_id of requested entry
 * @param bool $display  if true, will create a displayable string
 *
 * @return string $str       string to display Reminder value.
 * @return array  $reminder
 */
function getReminders ( $eid, $display = false ) {
  $reminder = array ();
  $str = '';
  // Get reminders.
  $rows = dbi_get_cached_rows ( 'SELECT cal_id, cal_date, cal_offset, cal_related,
    cal_before, cal_repeats, cal_duration, cal_action, cal_last_sent,
    cal_times_sent FROM webcal_reminders WHERE cal_id = ? ORDER BY cal_date,
    cal_offset, cal_last_sent', array ( $eid ) );
  if ( $rows ) {
    $rowcnt = count ( $rows );
    for ( $i = 0; $i < $rowcnt; $i++ ) {
      $row = $rows[$i];
      $reminder['eid'] = $row[0];
      if ( $row[1] != 0 ) {
        $reminder['timestamp'] = $row[1];
        $reminder['date'] = date ( 'Ymd', $row[1] );
        $reminder['time'] = date ( 'His', $row[1] );
      }
      $reminder['offset'] = $row[2];
      $reminder['related'] = $row[3];
      $reminder['before'] = $row[4];
      $reminder['repeats'] = $row[5];
      $reminder['duration'] = $row[6];
      $reminder['action'] = $row[7];
      $reminder['last_sent'] = $row[8];
      $reminder['times_sent'] = $row[9];
    }
    // Create display string if needed in user's timezone.
    if ( ! empty ( $reminder ) && $display == true ) {
      $str .= translate ( 'Yes' ) . '&nbsp;&nbsp;-&nbsp;&nbsp;';
      if ( ! empty ( $reminder['date'] ) )
        $str .= date ( 'Ymd', $reminder['timestamp'] );
      else { // Must be an offset even if zero.
        $d = $h = $minutes = 0;
        if ( $reminder['offset'] > 0 ) {
          $minutes = $reminder['offset'];
          $d = intval ( $minutes / ONE_DAY );
          $minutes -= ( $d * ONE_DAY );
          $h = intval ( $minutes / 60 );
          $minutes -= ( $h * 60 );
        }
        /*
Let tools/update_translations.pl see these.
translate ( 'after' ) translate ( 'before' ) translate ( 'end' )
translate ( 'start' ) translate ( 'day' ) translate ( 'days' )
translate ( 'hour' ) translate ( 'hours' ) translate ( 'minute' )
translate ( 'minutes' )
 */
        $str .= $d . ' ' . translate ( 'day'
           . ( $d == 1 ? '' : 's' ) ) . ' ' . $h . ' ' . translate ( 'hour'
           . ( $h = 1 ? '' : 's' ) ) . ' ' . $minutes . ' ' . translate ( 'minute'
           . ( $minutes == 1 ? '' : 's' ) ) . ' '
         . translate ( $reminder['before'] == 'Y'
          ? 'before' : 'after' ) . ' ' . translate ( $reminder['related'] == 'S'
          ? 'start' : 'end' );
      }
      return $str;
    }
  }
  return $reminder;
}

/* Remove :00 from times based on $DISPLAY_MINUTES value.
 *
 * @param string $timestr  time value to shorten
 *
 * @global string (Y/N)  Display 00 if on the hour
 */
function getShortTime ( $timestr ) {

  return ( ! getPref ( 'DISPLAY_MINUTES' )
    ? preg_replace ( '/(:00)/', '', $timestr ) : $timestr );
}

/* Converts from Gregorian Year-Month-Day to ISO YearNumber-WeekNumber-WeekDay.
 *
 * @internal JGH borrowed gregorianToISO from PEAR Date_Calc Class and added
 *
 *
 * @param int $day    Day of month
 * @param int $month  Number of month
 * @param int $year   Year
 *
 * @return string  Date in ISO YearNumber-WeekNumber-WeekDay format.
 *
 * @ignore
 */
function gregorianToISO ( $day, $month, $year ) {

  $mnth = array ( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
  $y_isleap = isLeapYear ( $year );

  $day_of_year_number = $day + $mnth[$month - 1];
  if ( $y_isleap && $month > 2 )
    $day_of_year_number++;

  // Find Jan 1 weekday (Monday = 1, Sunday = 7).
  $yy = ( $year - 1 ) % 100;
  $jan1_weekday = 1 +
  intval ( ( ( ( ( ( $year - 1 ) - $yy / 100 ) % 4 ) * 5 ) + $yy +
      intval ( $yy / 4 ) ) % 7 );

  // JGH added next if/else to compensate for week begins on Sunday.
  if ( ! getPref ( 'WEEK_START' ) ) {
    if ( $jan1_weekday < 7 )
      $jan1_weekday++;
    elseif ( $jan1_weekday == 7 )
      $jan1_weekday = 1;
  }

  // Weekday for year-month-day.
  $weekday = 1 +
  intval ( ( $day_of_year_number + ( $jan1_weekday - 1 ) - 1 ) % 7 );
  $yearnumber = $year;
  // Find if Y M D falls in YearNumber Y-1, WeekNumber 52.
  if ( $day_of_year_number <= ( 8 - $jan1_weekday ) && $jan1_weekday > 4 ) {
    $weeknumber = ( $jan1_weekday == 5 || ( $jan1_weekday == 6 &&
        isLeapYear ( $year - 1 ) ) ? 53 : 52 );
    $yearnumber--;
  }

  // Find if Y M D falls in YearNumber Y+1, WeekNumber 1.
  if ( $yearnumber == $year ) {
    $i = 365;
    if ( $y_isleap )
      $i++;

    if ( ( $i - $day_of_year_number ) < ( 4 - $weekday ) ) {
      $weeknumber = 1;
      $yearnumber++;
    }
  }
  // Find if Y M D falls in YearNumber Y, WeekNumber 1 through 53.
  if ( $yearnumber == $year ) {
    $weeknumber = intval ( ( $day_of_year_number + ( 7 - $weekday ) +
        ( $jan1_weekday - 1 ) ) / 7 );
    if ( $jan1_weekday > 4 )
      $weeknumber--;
  }
  // Put it all together.
  if ( $weeknumber < 10 )
    $weeknumber = '0' . $weeknumber;

  return "{$yearnumber}-{$weeknumber}-{$weekday}";
}

/* Generates the HTML for an icon to add a new event.
 *
 * @param string $date    Date for new event in YYYYMMDD format
 * @param int    $hour    Hour of day (0-23)
 * @param int    $minute  Minute of the hour (0-59)
 * @param string $user    Participant to initially select for new event
 *
 * @return string  The HTML for the add event icon.
 */
function html_for_add_icon ( $date = 0, $hour = '', $minute = '', $user = '' ) {
  global $WC;
  static $newEntryStr;

  if ( _WC_READONLY )
    return '';

  if ( empty ( $newEntryStr ) )
    $newEntryStr = translate ( 'New Entry' );

  if ( $minute < 0 ) {
    $hour = $hour -1;
    $minute = abs ( $minute );
  }
  return '
        <a title="' . $newEntryStr . '" href="edit_entry.php?'
   . ( ! empty ( $user ) && ! $WC->isLogin( $user ) ? 'user=' . $user . '&amp;' : '' )
   . 'date=' . $date . ( strlen ( $hour ) > 0 ? '&amp;hour=' . $hour : '' )
   . ( $minute > 0 ? '&amp;minute=' . $minute : '' )
   . ( empty ( $user ) ? '' : '&amp;defusers=' . $user )
   . ( ! $WC->catId() ? '' : '&amp;cat_id=' . $WC->catId() )
   . '"><img src="images/new.gif" class="new" alt="' . $newEntryStr . '" /></a>';
}

/* Converts HTML entities in 8bit.
 *
 * <b>Note:</b> Only supported for PHP4 (not PHP3).
 *
 * @param string $html  HTML text
 *
 * @return string  The converted text.
 */
function html_to_8bits ( $html ) {
  return ( floor ( phpversion () ) < 4
   ? $html
   : strtr ( $html, array_flip ( get_html_translation_table ( HTML_ENTITIES ) ) ) );
}

/* Determine if date is a weekend
 *
 * @param int $date  Timestamp of subject date OR a weekday number 0-6
 *
 * @return bool  True = Date is weekend
 */
function is_weekend ( $date ) {

  // We can't test for empty because $date may equal 0.
  if ( ! strlen ( $date ) )
    return false;
  $weekend_start = ( ! strlen ( getPref ( 'WEEKEND_START' ) ) 
    ? 6 : getPref ( 'WEEKEND_START' ) );

  // We may have been passed a weekday 0-6.
  if ( $date < 7 )
    return ( $date == $weekend_start % 7 || $date == ( $weekend_start + 1 ) % 7 );

  // We were passed a timestamp.
  $wday = date ( 'w', $date );
  return ( $wday == $weekend_start % 7 || $wday == ( $weekend_start + 1 ) % 7 );
}

/* Is this a leap year?
 *
 * @internal JGH Borrowed isLeapYear from PEAR Date_Calc Class
 *
 * @param int $year  Year
 *
 * @return bool  True for a leap year, else false.
 *
 * @ignore
 */
function isLeapYear ( $year = '' ) {
  if ( empty ( $year ) )
    $year = strftime ( '%Y', time () );

  if ( strlen ( $year ) != 4 || preg_match ( '/\D/', $year ) )
    return false;

  return ( ( $year % 4 == 0 && $year % 100 != 0 ) || $year % 400 == 0 );
}

/* Returns a custom header, stylesheet or tailer.
 *
 * The data will be loaded from the webcal_user_template table.
 * If variable $ALLOW_EXTERNAL_HEADER is set to 'Y',
 * then we load an external file using include.
 * This can have serious security issues since a
 * malicous user could open up /etc/passwd.
 *
 * @param string $type   type of template
 *                       ('H' = header, 'S' = stylesheet, 'T' = trailer)
 */
function load_template ( $type ) {
  global $WC, $c;
  $found = false;
  $ret = '';
    
  if ( ! isset ( $c ) )
    return $ret;
  // First, check for a user-specific template.
  $sql = 'SELECT cal_template_text FROM webcal_user_template
    WHERE cal_type = ? and cal_login_id = ?';
  if ( getPref ( 'ALLOW_USER_HEADER', 2 ) ) {
    $rows = dbi_get_cached_rows ( $sql, array ( $type, $WC->loginId() ) );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  // If no user-specific template, check for the system template.
  if ( ! $found ) {
    $rows = dbi_get_cached_rows ( $sql, array ( $type, WC__SYSTEM__ ) );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  if ( $found && getPref ( 'ALLOW_EXTERNAL_HEADER' ) &&
      file_exists ( $ret ) ) {
    ob_start ();
    include "$ret";
    $ret = ob_get_contents ();
    ob_end_clean ();
  }

  return $ret;
}


/* Loads default system settings (which can be updated via admin.php).
 *
 * System settings are stored in the webcal_config table.
 *
 * <b>Note:</b> If the setting for <var>server_url</var> is not set,
 * the value will be calculated and stored in the database.
 *
 * @global string  HTTP hostname
 * @global int     Server's port number
 * @global string  Request string
 */
function loadConfig ( $boolean=false ) {
  global $_SERVER, $HTTP_HOST, $REQUEST_URI, $SERVER_PORT;
  
  $sysConfig = array ();

  $rows = dbi_get_cached_rows ( 'SELECT cal_setting, cal_value
    FROM webcal_config' );
  for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
    $row = $rows[$i];
    $setting = $row[0];
    $value = $row[1];
		if ( ! $boolean || ( $boolean && $value != 'N' ) )
      $sysConfig[$setting] = $value;
  }

  // If app name not set.... default to "Title".  This gets translated later
  // since this function is typically called before translate.php is included.
  // Note:  We usually use translate ( $APPLICATION_NAME ) instead of
  // translate ( 'Title' ).
  if ( empty ( $sysConfig['APPLICATION_NAME'] ) )
    $sysConfig['APPLICATION_NAME'] = 'Title';

  if ( empty ( $sysConfig['SERVER_URL'] ) &&
      ( ! empty ( $HTTP_HOST ) && ! empty ( $REQUEST_URI ) ) ) {
    $ptr = strrpos ( $REQUEST_URI, '/' );
    if ( $ptr > 0 ) {
      $sysConfig['SERVER_URL'] = 'http://' . $HTTP_HOST
       . ( ! empty ( $SERVER_PORT ) && $SERVER_PORT != 80
        ? ':' . $SERVER_PORT : '' )
       . substr ( $REQUEST_URI, 0, $ptr + 1 );

      dbi_execute ( 'INSERT INTO webcal_config ( cal_setting, cal_value )
        VALUES ( ?, ? )', array ( 'SERVER_URL', $sysConfig['SERVER_URL'] ) );
    }
  }

  // If no font settings, then set default.
  if ( empty ( $sysConfig['FONTS'] ) )
    $sysConfig['FONTS'] = ( $sysConfig['LANGUAGE'] == 'Japanese' ? 'Osaka, ' : '' )
     . 'Arial, Helvetica, sans-serif';
     
  return $sysConfig;
}

/* Loads and creates an Event class object given the $eid
 *
 *
 */
function loadEvent ( $eid, $want_repeated='auto' ) {
  
	if ( $want_repeated = 'auto' ) {
	  $res = dbi_execute ( 'SELECT COUNT(cal_id) FROM webcal_entry_repeats 
		  WHERE cal_id = ?', array( $eid ) );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $want_repeated = ( $row[0] > 0 );
      dbi_free_result ( $res );
    }
	}
  $item = '';
  $sql = 'SELECT we.cal_name, we.cal_description, we.cal_date,
    we.cal_id, we.cal_rmt_addr, we.cal_priority, we.cal_access,
    we.cal_duration, weu.cal_status, we.cal_create_by, weu.cal_login_id,
    we.cal_type, we.cal_location, we.cal_url, we.cal_due_date,
    weu.cal_percent, we.cal_mod_date, we.cal_completed, we.cal_parent_id '
   . ( $want_repeated
    ? ', wer.cal_type, wer.cal_end, wer.cal_frequency,
      wer.cal_bymonth, wer.cal_bymonthday,
      wer.cal_byday, wer.cal_bysetpos, wer.cal_byweekno,
      wer.cal_byyearday, wer.cal_wkst, wer.cal_count
      FROM webcal_entry we, webcal_entry_repeats wer, webcal_entry_user weu
      WHERE ( we.cal_id = wer.cal_id OR wer.cal_id IS NULL ) AND '
    : 'FROM webcal_entry we, webcal_entry_user weu WHERE ' )
   . 'we.cal_id = weu.cal_id AND weu.cal_status IN ( \'A\',\'W\' ) 
    AND we.cal_id = ?';
 
  $rows = dbi_get_cached_rows ( $sql , array ( $eid ) );
  if ( $rows ) {
    $row = $rows[0];
    //we won't do primary cat yet
    $row['primary_cat'] = '';
    $item = createEvent ( $row, $want_repeated ); 
  } 
  return $item; 
}

/* Loads the current user's preferences 
 * from the webcal_user_pref table.
 *
 *
 * <b>Notes:</b>
 * - If <var>$ALLOW_COLOR_CUSTOMIZATION</var> is set to 'N', then we ignore any
 *   color preferences.
 * - Other default values will also be set if the user has not saved a
 *   preference and no global value has been set by the administrator in the
 *   system settings.
 */
function loadPreferences ( $guest = '', $boolean=false ) {
  global $has_boss, $is_nonuser, $WC, $user, $tzOffset;
  
  $browser = get_web_browser ();
  $browser_lang = get_browser_language ();
  $lang_found = false;
  $prefarray = array ();
  $tzOffset = 0;
  
  // Allow __public__ pref to be used if logging in or user not validated.
  $tmp_login = ( ! empty ( $guest )
    ? ( $guest == 'guest' ? '__public__' : $guest ) : $WC->loginId() );

  $rows = dbi_get_cached_rows ( 'SELECT cal_setting, cal_value
    FROM webcal_user_pref WHERE cal_login_id = ?', array ( $tmp_login ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $setting = $row[0];
      $value = $row[1];
      if ( $setting == 'LANGUAGE' )
        $lang_found = true;
			if ( ! $boolean || ( $boolean && $value != 'N' ) )
        $prefarray[$setting] = $value;
    }
  }

  // Set users timezone.
  if ( isset ( $prefarray['TIMEZONE'] ) )
    set_env ( 'TZ', $prefarray['TIMEZONE'] );


  // If user has not set a language preference and admin has not specified a
  // language, then use their browser settings to figure it out
  // and save it in the database for future use (email reminders).
  if ( ! $lang_found && ! $is_nonuser && ! empty ( $tmp_login ) ) {
    dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login_id, cal_setting,
     cal_value ) VALUES ( ?, ?, ? )', array ( $tmp_login, 'LANGUAGE', $browser_lang ) );
  }
  reset_language ( ! empty ( $prefarray['LANGUAGE'] )
    ? $prefarray['LANGUAGE'] : $browser_lang );
  if ( empty ( $prefarray['DATE_FORMAT'] ) || 
    $prefarray['DATE_FORMAT'] == 'LANGUAGE_DEFINED' )
    $prefarray['DATE_FORMAT'] = translate ( '__month__ __dd__, __yyyy__' );
  if ( empty ( $prefarray['DATE_FORMAT_MY'] ) || 
    $prefarray['DATE_FORMAT_MY'] == 'LANGUAGE_DEFINED' )
    $prefarray['DATE_FORMAT_MY'] = translate ( '__month__ __yyyy__' );

  if ( empty ( $prefarray['DATE_FORMAT_MD'] ) || 
    $prefarray['DATE_FORMAT_MD'] == 'LANGUAGE_DEFINED' )
    $prefarray['DATE_FORMAT_MD'] = translate ( '__month__ __dd__' );

  if ( empty ( $prefarray['DATE_FORMAT_TASK'] ) || 
    $prefarray['DATE_FORMAT_TASK'] == 'LANGUAGE_DEFINED' )
    $prefarray['DATE_FORMAT_TASK'] = translate ( '__mm__/__dd__/__yyyy__' );

  $has_boss = user_has_boss ( $tmp_login );


  return $prefarray;
}

/* Loads current user's layer info into layer global variable.
 *
 * If the system setting <var>$ALLOW_VIEW_OTHER</var> is not set to 'Y', then
 * we ignore all layer functionality.  If <var>$force</var> is 0, we only load
 * layers if the current user preferences have layers turned on.
 *
 * @param string $user   Username of user to load layers for
 * @param int    $force  If set to 1, then load layers for this user even if
 *                       user preferences have layers turned off.
 */
function loadLayers ( $user = '', $force = 0 ) {
  global $WC;

  if ( $user == '' )
    $user = $WC->loginId();

  $layers = array ();

  if ( ! getPref ( 'ALLOW_VIEW_OTHER' ) )
    return; // Not allowed to view others' calendars, so cannot use layers.
  if ( $force || getPref ( 'LAYERS_STATUS' ) ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_layerid, cal_layeruser_id, cal_color,
      cal_dups
			FROM webcal_user_layers 
			WHERE cal_login_id = ? ORDER BY cal_layerid',
      array ( $user ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $layers[$row[0]] = array (
          'cal_layerid' => $row[0],
          'cal_layeruser_id' => $row[1],
          'cal_color' => $row[2],
          'cal_dups' => $row[3],
					'cal_fullname' => $WC->User->getFullName ( $row[1] ) 
          );
      }
    }
  }
	return $layers;
}

function loadViews ( $view_id='', $user='', $globalOnly=false) {
  global $WC;
    
	$views = $query_params = array ();	
	$query_params[] = ( empty ( $user ) ? $WC->loginId() : $user );
	if ( $view_id )
	  $query_params[] = $view_id;
  // Get views for this user and global views.
  $rows = dbi_get_cached_rows ( 'SELECT cal_view_id, cal_name, cal_view_type,
    cal_is_global, cal_owner FROM webcal_view WHERE cal_owner = ? '
     . ( $globalOnly ? '' : ' OR cal_is_global = \'Y\' ' )
		 . ( $view_id ? ' AND cal_view_id = ? ' : '' )
     . 'ORDER BY cal_name', $query_params );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $url = 'view_';
      if ( $row[2] == 'E' )
        $url .= 'r.php?';
      elseif ( $row[2] == 'S' )
        $url .= 't.php?timeb=1&amp;';
      elseif ( $row[2] == 'T' )
        $url .= 't.php?timeb=0&amp;';
      else
        $url .= strtolower ( $row[2] ) . '.php?';

      $v = array (
        'cal_view_id' => $row[0],
        'cal_name' => ( ! empty ( $row[1]) ?
				  $row[1] : translate( 'Unnamed View' ) ),
        'cal_view_type' => $row[2],
        'cal_is_global' => $row[3],
        'cal_owner' => $row[4],
        'url' => $url . 'vid=' . $row[0]
        );
      $views[] = $v;
    }
  }
  return $views;
}
/* Returns the either the full name or the abbreviation of the specified month.
 *
 * @param int     $m       Number of the month (0-11)
 * @param string  $format  'F' = full, 'M' = abbreviation
 *
 * @return string The name of the specified month.
 */
function month_name ( $m, $format = 'F' ) {
  global $lang;
  static $local_lang, $month_names, $monthshort_names;
  //.
  // We may have switched languages.
  if ( $local_lang != $lang )
    $month_names = $monthshort_names = array ();

  $local_lang = $lang;

  if ( empty ( $month_names[0] ) )
    $month_names = array (
      translate ( 'January' ),
      translate ( 'February' ),
      translate ( 'March' ),
      translate ( 'April' ),
      translate ( 'May_' ), // needs to be different than "May",
      translate ( 'June' ),
      translate ( 'July' ),
      translate ( 'August' ),
      translate ( 'September' ),
      translate ( 'October' ),
      translate ( 'November' ),
      translate ( 'December' )
      );

  if ( empty ( $monthshort_names[0] ) )
    $monthshort_names = array (
      translate ( 'Jan' ),
      translate ( 'Feb' ),
      translate ( 'Mar' ),
      translate ( 'Apr' ),
      translate ( 'May' ),
      translate ( 'Jun' ),
      translate ( 'Jul' ),
      translate ( 'Aug' ),
      translate ( 'Sep' ),
      translate ( 'Oct' ),
      translate ( 'Nov' ),
      translate ( 'Dec' )
      );

  if ( $m >= 0 && $m < 12 )
    return ( $format == 'F' ? $month_names[$m] : $monthshort_names[$m] );

  return translate ( 'unknown-month' ) . " ($m)";
}

//returned array is in form (m,d,y)
function parseDate ( $date ) {
  $formatParts = explode ( '__', translate ( '__mm__/__dd__/__yyyy__' ) );
  array_pop ( $formatParts );
  array_shift ( $formatParts );
  //get separators
  $sep1 = $formatParts[1];
  $sep2 = $formatParts[3];
  $dateArr = explode ( $sep1, $date );
  //we may be using different separators
  if ( count ( $dateArr ) == 2 ) {
    $date2 = $dateArr[1];
		array_pop (  $dateArr );
    $dateArr2 = explode (   $sep2, $date2 );
    //$dateArr[1] = $dateArr2[0];
    //$dateArr[2] = $dateArr2[1];
		$dateArr = array_merge ( $dateArr, $dateArr2 );    
  }
  $dtarr = array();  
  for( $k=0; $k<5; $k++ ) {
    if ( $formatParts[$k] == 'm' || $formatParts[$k] == 'mm')
      $dtarr[0] = $dateArr[$k/2];
    if ( $formatParts[$k] == 'd' || $formatParts[$k] == 'dd' )
      $dtarr[1] = $dateArr[$k/2];
    if ( $formatParts[$k] == 'yyyy' )
      $dtarr[2] = $dateArr[$k/2];
    if ( $formatParts[$k] == 'yy' )
      $dtarr[2] = ($dateArr[$k/2] < 30 ? '20' : '19') + $dateArr[$k/2] ;
		$k++;//we need to step 2    
  }
  return  $dtarr; 
}

/* Prints all the calendar entries for the specified user for the specified date.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date  Date in YYYYMMDD format
 * @param string $user  Username
 * @param bool   $ssi   Is this being called from week_ssi.php?
 */
function print_date_entries ( $date, $user, $ssi = false ) {
  global $events, $is_nonuser, $WC, $tasks;
  static $newEntryStr;

  if ( empty ( $newEntryStr ) )
    $newEntryStr = translate ( 'New Entry' );

  $cnt = 0;
  $get_unapproved = ( getPref ( 'DISPLAY_UNAPPROVED'  ));
  $moons = '';
  $ret = '';

  if ( ! $ssi ) {
    $userCatStr = ( strcmp ( $user, $WC->loginId() ) ? 
      'user=' . $user . '&amp;' : '' )
     . ( ! $WC->catId() ? 
       '' : 'cat_id=' . $WC->catId() . '&amp;' );

    $ret = ( $WC->isAdmin() || ( ! _WC_READONLY && ! $is_nonuser   ) ? '
        <a title="' . $newEntryStr . '" href="edit_entry.php?' . $userCatStr
       . 'date=' . $date . '"><img src="images/new.gif" alt="' . $newEntryStr
       . '" class="new" /></a>' : '' ) . '
        <a class="dayofmonth" href="day.php?' . $userCatStr . 'date=' . $date
     . '">' . substr ( $date, 6, 2 ) . '</a>' . ( empty ( $moons[$date] )
      ? '' : '<img src="images/' . $moons[$date] . 'moon.gif" alt="" />' )
     . "<br />\n";
    $cnt++;
  }
  // Get, combime and sort the events for this date.
  $ev = combine_and_sort_events (
    // Get all the non-repeating events.
    get_entries ( $date, $get_unapproved ),
    // Get all the repeating events.
    get_repeating_entries ( $user, $date, $get_unapproved ) );

  // If wanted, get all due tasks for this date.
  if ( ( getPref ( 'DISPLAY_TASKS_IN_GRID'  )) &&
      ( $date >= date ( 'Ymd' ) ) )
    $ev = combine_and_sort_events ( $ev, get_tasks ( $date, $get_unapproved ) );

  for ( $i = 0, $evCnt = count ( $ev ); $i < $evCnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus () == 'A' ) {
      $ret .= print_entry ( $ev[$i], $date );
      $cnt++;
    }
  }
  if ( $cnt == 0 )
    $ret .= '&nbsp;'; // So the table cell has at least something.

  return $ret;
}





/* Prints the HTML for one event in the month view.
 *
 * @param Event  $event  The event
 * @param string $date   The data for which we're printing (YYYYMMDD)
 *
 * @staticvar int  Used to ensure all event popups have a unique id.
 *
 * @uses build_entry_popup
 */
function print_entry ( $event, $date ) {
  global $categories,
  $layers, $WC, $user;

  static $key = 0;
  static $viewEventStr, $viewTaskStr;

  if ( empty ( $viewEventStr ) ) {
    $viewEventStr = translate ( 'View this event' );
    $viewTaskStr = translate ( 'View this task' );
  }

  $catIcon = $in_span = $padding = $popup_timestr = $ret = $timestr = '';
  $cal_type = $event->getCalTypeName ();
  $loginStr = $event->getLoginId ();

  $can_access = access_user_calendar ( 'view', $loginStr, '',
    $event->getCalType (), $event->getAccess () );
  $time_only = access_user_calendar ( 'time', $loginStr );
  if ( $cal_type == 'task' && $can_access == 0 )
    return false;

  // No need to display if show time only and not a timed event.
  if ( $time_only == 'Y' && ! $event->Istimed () )
    return false;

  $class = ( ! $WC->isLogin( $loginStr)
    ? 'layerentry' : ( $event->getStatus () == 'W' ? 'unapproved' : '' ) . 'entry' );

  // If we are looking at a view, then always use "entry".
  if ( defined ( '_WC_CUSTOM_VIEW' ) )
    $class = 'entry';

  if ( $event->getPriority () == 3 )
    $ret .= '<strong>';

  $cloneStr = $event->getClone ();
  $eid = $event->getId ();
  $linkid = 'pop' . "$eid-$key";
  $name = $event->getName ();
  $view_text = ( $cal_type == 'task' ? $viewTaskStr : $viewEventStr );

  $key++;

  // Build entry link if UAC permits viewing.
  if ( $can_access != 0 && $time_only != 'Y' ) {
    // Make sure clones have parents URL date.
    $href = 'href="view_entry.php?eid=' . $eid . '&amp;date='
     . ( $cloneStr ? $cloneStr : $date )
     . ( strlen ( $user ) > 0
      ? '&amp;user=' . $user
      : ( $class == 'layerentry' ? '&amp;user=' . $loginStr : '' ) ) . '"';
    $title = ' title="' . $view_text . '" ';
  } else
    $href = $title = '';

  $ret .= '<div id="ev' . $eid . '"><a ' . $title 
    . ' class="' . $class . '" id="' . "$linkid\" $href" . '><img src="';

  $catNum = abs ( $event->getCategory () );
  $icon = $cal_type . '.gif';
  if ( $catNum > 0 ) {
    $catIcon = 'icons/cat-' . $catNum . '.gif';

    if ( ! file_exists ( $catIcon ) )
      $catIcon = '';
  }

  if ( empty ( $catIcon ) )
    $ret .= 'images/' . $icon . '" class="bullet" alt="' . $view_text
     . '" width="5" height="7" />';
  else {
    // Use category icon.
    $catAlt = ( empty ( $categories[$catNum] )
      ? '' : translate ( 'Category' ) . ': '
       . $categories[$catNum]['cat_name'] );

    $ret .= $catIcon . '" alt="' . $catAlt . '" title="' . "$catAlt\" />";
  }

  if ( $WC->loginId() != $loginStr && strlen ( $loginStr ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruse_id'] == $loginStr ) {
          $in_span = true;
          $ret .= ( '<span style="color:' . $layer['cal_color'] . ';">' );
        }
      }
    }
    // Check to see if Category Colors are set.
  } else
  if ( ! empty ( $categories[$catNum]['cat_color'] ) ) {
    $cat_color = $categories[$catNum]['cat_color'];
    if ( $cat_color != '#000000' ) {
      $in_span = true;
      $ret .= ( '<span style="color:' . $cat_color . ';">' );
    }
  }

  if ( $event->isAllDay () )
    $timestr = $popup_timestr = translate ( 'All day event' );
  else
  if ( ! $event->isUntimed () ) {
    $timestr = $popup_timestr = display_time ( $event->getDate () );
    if ( $event->getDuration () > 0 )
      $popup_timestr .= ' - ' . display_time ( $event->getEndDate () );

    if ( getPref ( 'DISPLAY_END_TIMES' ) )
      $timestr = $popup_timestr;

    if ( $cal_type == 'event' )
      $ret .= getShortTime ( $timestr )
       . ( $time_only == 'Y' ? '' : getPref ( 'TIME_SPACER' ) );
  }
  return $ret . build_entry_label ( $event, 'eventinfo-' . $linkid, $can_access,
    $popup_timestr, $time_only )

  // Added to allow a small location to be displayed if wanted.
  . ( ! empty ( $location ) && getPref ( 'DISPLAY_LOCATION' )
    ? '<br /><span class="location">('
     . htmlspecialchars ( $location ) . ')</span>' : '' )
   . ( $in_span == true ? '</span>' : '' ) . '</a>'
   . ( $event->getPriority () == 3 ? '</strong>' : '' ) // end font-weight span
  . '</div>';
}
/* Generate standardized error message
 *
 * @param string $error  Message to display
 * @param bool   $full   Include extra text in display
 *
 * @return string  HTML to display error.
 *
 * @uses print_error_header
 */
function print_error ( $error, $full = false ) {
  return print_error_header ()
   . ( $full ? translate ( 'The following error occurred' ) . ':' : '' ) . '
    <blockquote>' . $error . '</blockquote>';
}

/* An h2 header error message.
 */
function print_error_header () {
  return '
    <h2>' . translate ( 'Error' ) . '</h2>';
}

/* Generate standardized Not Authorized message
 *
 * @param bool $full  Include ERROR title
 *
 * @return string  HTML to display notice.
 *
 * @uses print_error_header
 */
function print_not_auth ( $full = false ) {
  return ( $full ? print_error_header () : '' )
   . translate ( 'You are not authorized' ) . "\n";
}


/* Generate standardized Success message.
 *
 * @param bool $saved
 *
 * @return string  HTML to display error.
 */
function print_success ( $saved ) {
  return ( $saved ? '
    <script language="javascript" type="text/javascript">
<!-- <![CDATA[
      alert ( \'' . translate ( 'Changes successfully saved', true ) . '\' );
//]]> -->
    </script>' : '' );
}

function print_trailer() {
 echo '</body></html>';
}

/* Reads events visible to a user.
 *
 * Includes layers and possibly public access if enabled.
 * NOTE: The values for the global variables $thisyear and $thismonth
 * MUST be set!  (This will determine how far in the future to caclulate
 * repeating event dates.)
 *
 * @param string $user           Username
 * @param bool   $want_repeated  Get repeating events?
 * @param string $date_filter    SQL phrase starting with AND, to be appended to
 *                               the WHERE clause.  May be empty string.
 * @param int    $cat_id         Category ID to filter on.  May be empty.
 * @param bool   $is_task        Used to restrict results to events OR tasks
 *
 * @return array  Array of Events sorted by time of day.
 */
function query_events ( $user='', $want_repeated, $date_filter, $cat_id = '',
  $is_task = false ) {
  global $db_connection_info, $jumpdate, $layers, $WC, $max_until,
  $result, $thismonth, $thisyear;

  // New multiple categories requires some checking to see if this cat_id is
  // valid for this cal_id.  It could be done with nested SQL,
  // but that may not work for all databases.  This might be quicker also.
  $catlist = $cloneRepeats = $layers_byuser = $result = array ();

  $sql = 'SELECT DISTINCT( cal_id ) FROM webcal_entry_categories ';
  // None was selected...return only events without categories.
  if ( $cat_id == -1 )
    $rows = dbi_get_cached_rows ( $sql, array () );
  elseif ( $cat_id != '' ) {
    $cat_array = explode ( ',', $cat_id );
    $placeholders = '';
    for ( $p_i = 0, $cnt = count ( $cat_array ); $p_i < $cnt; $p_i++ ) {
      $placeholders .= ( $p_i == 0 ) ? '?' : ', ?';
    }
    $rows = dbi_get_cached_rows ( $sql . 'WHERE cat_id IN ( ' . $placeholders
       . ' )', $cat_array );
  }
  if ( $cat_id != '' ) {
    // $rows = dbi_get_cached_rows ( $sql, array ( $cat_id ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $catlist[$i] = $row[0];
      }
    }
  }

  $catlistcnt = count ( $catlist );
  $query_params = array ();
  $sql = 'SELECT we.cal_name, we.cal_description, we.cal_date,
    we.cal_id, we.cal_rmt_addr, we.cal_priority, we.cal_access,
    we.cal_duration, weu.cal_status, we.cal_create_by, weu.cal_login_id,
    we.cal_type, we.cal_location, we.cal_url, we.cal_due_date,
    weu.cal_percent, we.cal_mod_date, we.cal_completed, we.cal_parent_id '
   . ( $want_repeated
    ? ', wer.cal_type, wer.cal_end, wer.cal_frequency,
      wer.cal_bymonth, wer.cal_bymonthday,
      wer.cal_byday, wer.cal_bysetpos, wer.cal_byweekno,
      wer.cal_byyearday, wer.cal_wkst, wer.cal_count
      FROM webcal_entry we, webcal_entry_repeats wer, webcal_entry_user weu
      WHERE we.cal_id = wer.cal_id AND '
    : 'FROM webcal_entry we, webcal_entry_user weu WHERE ' )
   . 'we.cal_id = weu.cal_id AND weu.cal_status IN ( \'A\',\'W\' ) ';

  if ( $catlistcnt > 0 ) {
    $placeholders = '';
    for ( $p_i = 0; $p_i < $catlistcnt; $p_i++ ) {
      $placeholders .= ( $p_i == 0 ) ? '?' : ', ?';
      $query_params[] = $catlist[$p_i];
    }

    if ( $cat_id > 0 )
      $sql .= 'AND we.cal_id IN ( ' . $placeholders . ' ) ';
    elseif ( $cat_id == -1 ) // Eliminate events with categories.
      $sql .= 'AND we.cal_id NOT IN ( ' . $placeholders . ' ) ';
  } else
  if ( $cat_id > -99 )
    // Force no rows to be returned.  No matching entries in category.
    $sql .= 'AND 1 = 0 ';

  $sql .= 'AND we.cal_type IN '
   . ( $is_task == false
    ? '( \'E\',\'M\' ) ' : '( \'N\',\'T\' ) AND ( we.cal_completed IS NULL ) ' )
   . ( $user ? 'AND ( weu.cal_login_id = ? ' : '' );

  $sql .= 'AND we.cal_type IN '
   . ( $want_repeated == false
    ? '( \'E\',\'T\' ) ' : '( \'M\',\'N\' ) ' );
	 
  $query_params[] = $user;

  if ( $user == $WC->loginId() && strlen ( $user ) > 0 && $layers ) {
    foreach ( $layers as $layer ) {
      $layeruser = $layer['cal_layeruser_id'];

      $sql .= 'OR weu.cal_login_id = ? ';
      $query_params[] = $layeruser;

      // While we are parsing the whole layers array, build ourselves
      // a new array that will help when we have to check for dups.
      $layers_byuser[$layeruser] = $layer['cal_dups'];
    }
  }

  $rows = dbi_get_cached_rows ( $sql . ') ' . $date_filter

    // Now order the results by time, then name if not tasks.
    . ( ! $is_task ? ' ORDER BY we.cal_date, we.cal_name' : '' ), $query_params );

  if ( $rows ) {
    $i = 0;
    $checkdup_id = $first_i_this_id = -1;
    for ( $ii = 0, $cnt = count ( $rows ); $ii < $cnt; $ii++ ) {
      $row = $rows[$ii];
      if ( $row[8] == 'D' || $row[8] == 'R' )
        continue; // Don't show deleted/rejected ones.

      // Get primary category for this event, used for icon and color.
      $categories = get_categories_by_eid ( $row[3], $user );
      $cat_keys = array_keys ( $categories );
      $row['primary_cat'] = ( ! empty ( $cat_keys[0] ) ? $cat_keys[0] : -99 );

      $item = createEvent ( $row, $want_repeated );

      if ( $item->getId () != $checkdup_id ) {
        $checkdup_id = $item->getId ();
        $first_i_this_id = $i;
      }

      if ( $item->getLoginId () == $user ) {
        // Insert this one before all other ones with this ID.
        array_splice ( $result, $first_i_this_id, 0, array ( $item ) );
        $i++;

        if ( $first_i_this_id + 1 < $i ) {
          // There's another one with the same ID as the one we inserted.
          // Check for dup and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          if ( ! empty ( $layers_byuser[$other_item->getLoginId ()] ) &&
            $layers_byuser[$other_item->getLoginId ()] == 'N' ) {
            // NOTE:  array_splice requires PHP4
            array_splice ( $result, $first_i_this_id + 1, 1 );
            $i--;
          }
        }
      } else {
        if ( $i == $first_i_this_id || ( !
            empty ( $layers_byuser[$item->getLoginId ()] ) &&
              $layers_byuser[$item->getLoginId ()] != 'N' ) )
          // This item either is the first one with its ID, or allows dups.
          // Add it to the end of the array.
          $result [$i++] = $item;
      }
      // Does event go past midnight?
      if ( date ( 'Ymd', $item->getDate () ) !=
          date ( 'Ymd', $item->getEndDate () ) && !
          $item->isAllDay () && $item->getCalTypeName () == 'event' ) {
        getOverLap ( $item, $i, true );
        $i = count ( $result );
      }
    }
  }

  if ( $want_repeated  ) {
    // Now load event exceptions/inclusions and store as array.

    // TODO:  Allow passing this max_until as param in case we create
    // a custom report that shows N years of events.
    if ( empty ( $max_until ) )
      $max_until = mktime ( 0, 0, 0, $thismonth + 2, 1, $thisyear );

    for ( $i = 0, $resultcnt = count ( $result ); $i < $resultcnt; $i++ ) {
      if ( $result[$i]->getId () != '' ) {
        $rows = dbi_get_cached_rows ( 'SELECT cal_date, cal_exdate
          FROM webcal_entry_exceptions
          WHERE cal_id = ?', array ( $result[$i]->getId () ) );
        for ( $ii = 0, $rowcnt = count ( $rows ); $ii < $rowcnt; $ii++ ) {
          $row = $rows[$ii];
          // If this is not a clone, add exception date.
          if ( ! $result[$i]->getClone () )
            $except_date = $row[0];

          if ( $row[1] == 1 )
            $result[$i]->addRepeatException ( $except_date, $result[$i]->getId () );
          else
            $result[$i]->addRepeatInclusion ( $except_date );
        }
        // Get all dates for this event.
        // If clone, we'll get the dates from parent later.
        if ( ! $result[$i]->getClone () ) {
          $until = ( $result[$i]->getRepeatEndDateTimeTS ()
            ? $result[$i]->getRepeatEndDateTimeTS ()
            : // Make sure all January dates will appear in small calendars.
            $max_until );

          // Try to minimize the repeat search by shortening
          // until if BySetPos is not used.
          if ( ! $result[$i]->getRepeatBySetPos () && $until > $max_until )
            $until = $max_until;

          $rpt_count = 999; //Some BIG number.
          // End date... for year view and some reports we need whole year...
          // So, let's do up to 365 days after current month.
          // TODO:  Add this end time as a parameter in case someone creates
          // a custom report that asks for N years of events.
          // $jump = mktime ( 0, 0, 0, $thismonth -1, 1, $thisyear);
          if ( $result[$i]->getRepeatCount () )
            $rpt_count = $result[$i]->getRepeatCount () -1;

          $date = $result[$i]->getDate ();
          if ( $result[$i]->isAllDay () || $result[$i]->isUntimed () )
            $date += 43200; //A simple hack to prevent DST problems.

          // TODO get this to work
          // C heck if this event id has been cached.
          // $file = '';
          // if ( ! empty ( $db_connection_info['cachedir'] ) ){
          // $hash = md5 ( $result[$i]->getId () . $until . $jump );
          // $file = $db_connection_info['cachedir'] . '/' . $hash . '.dat';
          // }
          // if (  file_exists ( $file ) ) {
          // $dates =  unserialize ( file_get_contents ( $file ) );
          // } else {
          $dates = get_all_dates ( $date,
            $result[$i]->getRepeatType (), $result[$i]->getRepeatFrequency (),
            $result[$i]->getRepeatByMonth (), $result[$i]->getRepeatByWeekNo (),
            $result[$i]->getRepeatByYearDay (),
            $result[$i]->getRepeatByMonthDay (),
            $result[$i]->getRepeatByDay (), $result[$i]->getRepeatBySetPos (),
            $rpt_count, $until, $result[$i]->getRepeatWkst (),
            $result[$i]->getRepeatExceptions (),
            $result[$i]->getRepeatInclusions (), $jumpdate );
          $result[$i]->addRepeatAllDates ( $dates );
          // Serialize and save in cache for later use.
          // if ( ! empty ( $db_connection_info['cachedir'] ) ) {
          // $fd = @fopen ( $file, 'w+b', false );
          // if ( empty ( $fd ) ) {
          // dbi_fatal_error ( "Cache error: could not write file $file" );
          // }
          // fwrite ( $fd, serialize ( $dates ) );
          // fclose ( $fd );
          // chmod ( $file, 0666 );
          // }
          // }
        } else { // Process clones if any.
          if ( count ( $result[$i-1]->getRepeatAllDates () > 0 ) ) {
            $parentRepeats = $result[$i-1]->getRepeatAllDates ();
            for ( $j = 0, $parentRepeatscnt = count ( $parentRepeats );
              $j < $parentRepeatscnt; $j++ ) {
							//TODO Improve the logic over simply adding ONE_DAY
              $cloneRepeats[] = date ( 'Ymd', $parentRepeats[$j] + ONE_DAY );
            }
            $result[$i]->addRepeatAllDates ( $cloneRepeats );
          }
        }
      }
    }
  }
  return $result;
}

/* Reads all the events for a user for the specified range of dates.
 *
 * This is only called once per page request to improve performance.  All the
 * events get loaded into the array <var>$events</var> sorted by time of day
 * (not date).
 *
 * @param string $user       Username
 * @param string $startdate  Start date range, inclusive (in timestamp format)
 *                           in user's timezone
 * @param string $enddate    End date range, inclusive (in timestamp format)
 *                           in user's timezone
 * @param int    $cat_id     Category ID to filter on
 *
 * @return array  Array of Events
 *
 * @uses query_events
 */
function read_events () {
  global $WC;

  return query_events ( $WC->userLoginId(), false, ' AND ( we.cal_date >= ' 
    . $WC->getStartDate() . ' AND we.cal_date <= ' 
    . $WC->getEndDate() . ' )', $WC->catId() );
}

/* Reads all the repeated events for a user.
 *
 * This is only called once per page request to improve performance.
 * All the events get loaded into the array <var>$repeated_events</var>
 * sorted by time of day (not date).
 *
 * This will load all the repeated events into memory.
 *
 * <b>Notes:</b>
 * - To get which events repeat on a specific date, use
 *   {@link get_repeating_entries ()}.
 * - To get all the dates that one specific event repeats on, call
 *   {@link get_all_dates ()}.
 *
 * @param string $user    Username
 * @param int    $cat_id  Category ID to filter on  (May be empty)
 * @param int $date       Cutoff date for repeating event cal_end in timestamp
 *                        format (may be empty)
 *
 * @return array  Array of RepeatingEvents sorted by time of day.
 *
 * @uses query_events
 */
function read_repeated_events () {
  global $WC, $jumpdate, $max_until;

  // This date should help speed up things
  // by eliminating events that won't display anyway.
  $max_until = $WC->getEndDate() + ONE_DAY;
  
  $date =  $jumpdate = ( $WC->getStartDate() ? $WC->getStartDate() : time() );
  return query_events ( $WC->userLoginId(), true, 
    'AND ( wer.cal_end >= ' . $date . ' OR wer.cal_end IS NULL )',
    $WC->catId() );
}

/* Reads all the tasks for a user with due date within the specified date range.
 *
 * This is only called once per page request to improve performance.
 * All the tasks get loaded into the array <var>$tasks</var> sorted by
 * time of day (not date).
 *
 * @param string $user      Username
 * @param string $duedate   End date range, inclusive (in timestamp format)
 *                          in user's timezone
 * @param int    $cat_id    Category ID to filter on
 *
 * @return array  Array of Tasks
 *
 * @uses query_events
 */
function read_tasks () {
  global $WC;
  
  return query_events ( $WC->userLoginId(), false, 
    ' AND ( ( we.cal_due_date <= ' . $WC->getEndDate() . ' ) )', 
    $WC->catId(), true );
}

/* Generates a cookie that saves the last calendar view.
 *
 * Cookie is based on the current <var>$REQUEST_URI</var>.
 *
 * We save this cookie so we can return to this same page after a user
 * edits/deletes/etc an event.
 *
 * @param bool $view  Determine if we are using a view_x.php file
 *
 * @global string  Request string
 */
function remember_this_view ( $view = false ) {
  global $REQUEST_URI;
  if ( empty ( $REQUEST_URI ) )
    $REQUEST_URI = $_SERVER['REQUEST_URI'];

  // If called from init, only process script named "view_x.php.
  if ( $view == true && ! strstr ( $REQUEST_URI, 'view_' ) )
    return;

  // Do not use anything with "friendly" in the URI.
  if ( strstr ( $REQUEST_URI, 'friendly=' ) )
    return;

  SetCookie ( 'webcalendar_last_view', $REQUEST_URI );
}


/* Sends an HTTP login request to the browser and stops execution.
 *
 * @global string  name of language file
 * @global string  Application Name
 *
 */
function send_http_login () {
  global $lang_file;

  if ( strlen ( $lang_file ) ) {
    $not_authorized = print_not_auth ();
    $title = translate ( 'Title' );
    $unauthorized = translate ( 'Unauthorized' );
  } else {
    $not_authorized = 'You are not authorized';
    $title = 'Webcalendar';
    $unauthorized = 'Unauthorized';
  }
  header ( 'WWW-Authenticate: Basic realm="' . "$title\"" );
  header ( 'HTTP/1.0 401 Unauthorized' );
  echo send_doctype ( $unauthorized ) . '
  </head>
  <body>
    <h2>' . $title . '</h2>
    ' . $not_authorized . '
  </body>
</html>';
  exit;
}

/* Sends HTTP headers that tell the browser not to cache this page.
 *
 * Different browsers use different mechanisms for this,
 * so a series of HTTP header directives are sent.
 *
 * <b>Note:</b>  This function needs to be called before any HTML output is sent
 *               to the browser.
 */
function send_no_cache_header () {
  header ( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
  header ( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s' ) . ' GMT' );
  header ( 'Cache-Control: no-store, no-cache, must-revalidate' );
  header ( 'Cache-Control: post-check=0, pre-check=0', false );
  header ( 'Pragma: no-cache' );
}

/* Sends a redirect to the user's preferred view.
 *
 * The user's preferred view is stored in the STARTVIEW  variable.
 * This is loaded from the user preferences (or system settings
 * if there are no user prefererences.)
 *
 * @param string $indate  Date to pass to preferred view in YYYYMMDD format
 * @param string $args    Arguments to include in the URL (such as "user=joe")
 */
function send_to_preferred_view ( $indate = '', $args = '' ) {
  do_redirect ( get_preferred_view ( $indate, $args ) );
}

/* Set an environment variable if system allows it.
 *
 * @param string $val      name of environment variable
 * @param string $setting  value to assign
 *
 * @return bool  true = success false = not allowed.
 */
function set_env ( $val, $setting ) {
  global $tzOffset;

  $can_setTZ = ( substr ( $setting, 0, 11 ) == 'WebCalendar' ? false : true );
  $ret = false;
  // Test if safe_mode is enabled.
  // If so, we then check safe_mode_allowed_env_vars for $val.
  if ( ini_get ( 'safe_mode' ) ) {
    $allowed_vars = explode ( ',', ini_get ( 'safe_mode_allowed_env_vars' ) );
    if ( in_array ( $val, $allowed_vars ) )
      $ret = true;
  } else
    $ret = true;

  // We can't set TZ env on php 4.0 windows,
  // so the setting should already contain 'WebCalendar/xx'.
  if ( $ret == true && $can_setTZ )
    putenv ( $val . '=' . $setting );

  if ( $val == 'TZ' ) {
    $tzOffset = ( ! $can_setTZ ? substr ( $setting, 12 ) * ONE_HOUR : 0 );
    // Some say this is required to properly init timezone changes.
    mktime ( 0, 0, 0, 1, 1, 1970 );
  }

  return $ret;
}

/* Sorts the combined event arrays by timestamp then name.
 *
 * <b>Note:</b> This is a user-defined comparison function for usort ().
 *
 * @params passed automatically by usort, don't pass them in your call
 */
function sort_events ( $a, $b ) {
  // Handle untimed events first.
  if ( $a->isUntimed () || $b->isUntimed () )
    return strnatcmp ( $b->isUntimed (), $a->isUntimed () );

  $retval = strnatcmp (
    display_time ( $a->getDate (), 0,  24 ),
    display_time ( $b->getDate (), 0,  24 ) );

  return ( $retval ? $retval : strnatcmp ( $a->getName (), $b->getName () ) );
}

/* Sorts the combined event arrays by timestamp then name (case insensitive).
 *
 * <b>Note:</b> This is a user-defined comparison function for usort ().
 *
 * @params passed automatically by usort, don't pass them in your call.
 */
function sort_events_insensitive ( $a, $b ) {
  $retval = strnatcmp (
    display_time ( $a->getDate (), 0,  24 ),
    display_time ( $b->getDate (), 0,  24 ) );

  return ( $retval
    ? $retval
    : strnatcmp ( strtolower ( $a->getName () ), strtolower ( $b->getName () ) ) );
}

/* Sort user array based on USER_SORT_ORDER.
 * <b>Note:</b> This is a user-defined comparison function for usort ()
 * that will be called from user-xxx.php.
 *
 * @params passed automatically by usort, don't pass them in your call.
 */
function sort_users ( $a, $b ) {

  $sort_order = getPref ( 'USER_SORT_ORDER' );
  $first = strnatcmp ( strtolower ( $a['cal_firstname'] ),
    strtolower ( $b['cal_firstname'] ) );
  $last = strnatcmp ( strtolower ( $a['cal_lastname'] ),
    strtolower ( $b['cal_lastname'] ) );

  return ( empty ( $sort_order ) ? 'cal_lastname, cal_firstname,'
      : ( "$sort_order," == 'cal_lastname, cal_firstname,'
    ? ( empty ( $last ) ? $first : $last )
    : ( empty ( $first ) ? $last : $first ) ) );
}

/* Converts a time format HHMMSS (like 130000 for 1PM)
 * into number of minutes past midnight.
 *
 * @param string $time  Input time in HHMMSS format
 *
 * @return int  The number of minutes since midnight.
 */
function time_to_minutes ( $time ) {
  return intval ( $time / 10000 ) * 60 + intval ( ( $time / 100 ) % 100 );
}

/* Checks to see if two events overlap.
 *
 * @param string $time1      Time 1 in HHMMSS format
 * @param int    $duration1  Duration 1 in minutes
 * @param string $time2      Time 2 in HHMMSS format
 * @param int    $duration2  Duration 2 in minutes
 *
 * @return bool  True if the two times overlap, false if they do not.
 */
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  $hour1 = intval ( $time1 / 10000 );
  $min1 = ( $time1 / 100 ) % 100;
  $hour2 = intval ( $time2 / 10000 );
  $min2 = ( $time2 / 100 ) % 100;
  // Convert to minutes since midnight and
  // remove 1 minute from duration so 9AM-10AM will not conflict with 10AM-11AM.
  if ( $duration1 > 0 )
    $duration1 -= 1;

  if ( $duration2 > 0 )
    $duration2 -= 1;

  $tmins1start = $hour1 * 60 + $min1;
  $tmins1end = $tmins1start + $duration1;
  $tmins2start = $hour2 * 60 + $min2;
  $tmins2end = $tmins2start + $duration2;

  return ( ( $tmins1start >= $tmins2end ) || ( $tmins2start >= $tmins1end )
    ? false : true );
}

/* Updates event status and logs activity
 *
 * @param string $status  A,D,R,W to set cal_status
 * @param string $user    user to apply changes to
 * @param int    $eid      event id
 * @param string $type    event type for logging
 *
 * @global string logged in user
 * @global string current error message
 */
function update_status ( $status, $user, $eid, $type = 'E' ) {
  global $error, $WC;

  if ( empty ( $status ) )
    return;
		
  $date = false;
  if ( strlen ( $status ) == 10 ) {
		$date = substr ( $status, 2 );
		$status = 'D';
	}
  $log_type = '';
  switch ( $type ) {
    case 'N':
    case 'T':
      $log_type = '_T';
      break;
    case 'J':
    case 'O':
      $log_type = '_J';
  }
  switch ( $status ) {
    case 'A':
      $log_type = constant ( 'LOG_APPROVE' . $log_type );
      // translate ( 'Error approving event' )
      $error_msg = translate ( 'Error approving event XXX.' );
      break;
    case 'D':
      $log_type = constant ( 'LOG_DELETE' . $log_type );
      // translate ( 'Error deleting event' )
      $error_msg = translate ( 'Error deleting event XXX.' );
      break;
    case 'R':
      $log_type = constant ( 'LOG_REJECT' . $log_type );
      // translate ( 'Error rejecting event' )
      $error_msg = translate ( 'Error rejecting event XXX.' );
  }
  if ( $date ) {
    if ( ! dbi_execute ( 'INSERT INTO webcal_entry_exceptions ( cal_id, cal_date, cal_exdate )
        VALUES ( ?, ?, ? )', array( $eid, $date, 1 ) ) )
      $error = str_replace ( 'XXX', dbi_error (), $error_msg );
    else
      activity_log ( $eid, $WC->loginId(), $user, $log_type, '' );				
	 
	} else {
    if ( ! dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
      WHERE cal_login_id = ? AND cal_id = ?', array ( $status, $user, $eid ) ) )
      $error = str_replace ( 'XXX', dbi_error (), $error_msg );
    else
      activity_log ( $eid, $WC->loginId(), $user, $log_type, '' );
	}
}


/* Determine if the specified user is a participant in the event.
 * User must have status 'A' or 'W'.
 *
 * @param int    $eid    event id
 * @param string $user  user login
 */
function user_is_participant ( $eid, $user ) {
  $ret = false;

  $rows = dbi_get_cached_rows ( 'SELECT COUNT( cal_id ) FROM webcal_entry_user
    WHERE cal_id = ? AND cal_login_id = ? AND cal_status IN ( \'A\',\'W\' )',
    array ( $eid, $user ) );
  if ( ! $rows )
    die_miserable_death ( str_replace ( 'XXX', dbi_error (),
        translate ( 'Database error XXX.' ) ) );

  if ( ! empty ( $rows[0] ) ) {
    $row = $rows[0];
    if ( ! empty ( $row ) )
      $ret = ( $row[0] > 0 );
  }

  return $ret;
}

/* Checks to see if user's IP in in the IP Domain
 * specified by the /includes/blacklist.php file
 *
 * @return bool  Is user's IP in required domain?
 *
 * @see /includes/blacklist.php
 * @todo:  There has to be a way to vastly improve on this logic.
 */
function validate_domain () {

  if ( ! getPref ( 'SELF_REGISTRATION_BLACKLIST' ) )
    return true;

  $allow_true = $deny_true = array ();
  $ip_authorized = false;
  $rmt_long = ip2long ( $_SERVER['REMOTE_ADDR'] );
  $fd = @fopen ( 'includes/blacklist.php', 'rb', false );
  if ( ! empty ( $fd ) ) {
    // We don't use fgets () since it seems to have problems with Mac-formatted
    // text files.
    // Instead, we read in the entire file, then split the lines manually.
    $data = '';
    while ( ! feof ( $fd ) ) {
      $data .= fgets ( $fd, 4096 );
    }
    fclose ( $fd );

    // Replace any combination of carriage return (\r) and new line (\n)
    // with a single new line.
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );

    // Split the data into lines.
    $blacklistLines = explode ( "\n", $data );

    for ( $n = 0, $cnt = count ( $blacklistLines ); $n < $cnt; $n++ ) {
      $buffer = trim ( $blacklistLines[$n], "\r\n " );
      if ( preg_match ( '/^#/', $buffer ) )
        continue;

      if ( preg_match ( '/(\S+):\s*(\S+):\s*(\S+)/', $buffer, $matches ) ) {
        $permission = $matches[1];
        $black_long = ip2long ( $matches[2] );
        $mask = ip2long ( $matches[3] );
        if ( $matches[2] == '255.255.255.255' )
          $black_long = $rmt_long;

        if ( ( $black_long & $mask ) == ( $rmt_long & $mask ) ) {
          if ( $permission == 'deny' )
            $deny_true[] = true;
          elseif ( $permission == 'allow' )
            $allow_true[] = true;
        }
      }
    }
    $ip_authorized = ( count ( $deny_true ) && ! count ( $allow_true )
      ? false : true );
  }

  return $ip_authorized;
}

/* Returns either the full name or the abbreviation of the day.
 *
 * @param int     $w       Number of the day in the week (0=Sun,...,6=Sat)
 * @param string  $format  'l' (lowercase L) = Full, 'D' = abbreviation.
 *
 * @return string The weekday name ("Sunday" or "Sun")
 */
function weekday_name ( $w, $format = 'l' ) {
  global $lang;
  static $local_lang, $week_names;

  // We may have switched languages.
  if ( $local_lang != $lang )
    $week_names = $weekday_names = array ();

  $local_lang = $lang;

  // We may pass $DISPLAY_LONG_DAYS as $format.
  if ( $format == 'N' )
    $format = 'D';
  if ( $format == 'Y' )
    $format = 'l';

  if ( empty ( $weekday_names[0] ) )
    $weekday_names = array (
      translate ( 'Sunday' ),
      translate ( 'Monday' ),
      translate ( 'Tuesday' ),
      translate ( 'Wednesday' ),
      translate ( 'Thursday' ),
      translate ( 'Friday' ),
      translate ( 'Saturday' )
      );

  if ( empty ( $week_names[0] ) )
    $week_names = array (
      translate ( 'Sun' ),
      translate ( 'Mon' ),
      translate ( 'Tue' ),
      translate ( 'Wed' ),
      translate ( 'Thu' ),
      translate ( 'Fri' ),
      translate ( 'Sat' )
      );

  if ( $w >= 0 && $w < 7 )
    return ( $format == 'l' ? $weekday_names[$w] : $week_names[$w] );

  return translate ( 'unknown-weekday' ) . " ($w)";
}

/* ****************************************************************************
 *     Functions for getting information about boss and their assistants.     *
 **************************************************************************** */

/* Checks the boss user preferences to see if the boss must approve events
 * added to their calendar.
 *
 * @param string $assistant  Assistant login
 * @param string $boss       Boss login
 *
 * @return bool  True if the boss must approve new events.
 */
function boss_must_approve_event ( $assistant, $boss ) {
  if ( access_user_calendar ( 'assistant', $assistant, $boss ) )
    return getPref ( 'APPROVE_ASSISTANT_EVENT', 0, $boss );

  return true;
}

/* Checks the boss user preferences to see if the boss wants to be notified via
 * email on changes to their calendar.
 *
 * @param string $assistant  Assistant login
 * @param string $boss       Boss login
 *
 * @return bool  True if the boss wants email notifications.
 */
function boss_must_be_notified ( $assistant, $boss ) {
 //TODO
  return true;
}


/* Gets a list of an assistant's boss from the webcal_asst table.
 *
 * @param string $assistant Login of assistant
 *
 * @return array  Array of bosses,
 *                where each boss is an array with the following fields:
 * - <var>cal_login</var>
 * - <var>cal_fullname</var>
 */ 
function user_get_boss_list ( $assistant ) {
  global $WC;
  $count = 0;
  $ret = array ();
  $rows = dbi_get_cached_rows ( 'SELECT cal_other_user_id 
	  FROM webcal_access_user
    WHERE cal_login_id = ? 
		AND cal_assistant = \'Y\'', array ( $assistant ) );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $bossData = $WC->User->loadVariables ( $row[0], 'boss_' );
      $ret[$count++] = array (
        'cal_login_id' => $row[0],
        'cal_fullname' => $bossData['fullname']
        );
    }
  }
  return $ret;
}

/* Is this user an assistant?
 *
 * @param string $assistant  Login for user
 *
 * @return bool  true if the user is an assistant to one or more bosses.
 */
function user_has_boss ( $assistant ) {
  $ret = false;
  return $ret;
}

/* Builds the HTML for the event label.
 *
 * @param string  $can_access
 * @param string  $time_only
 *
 * @return string  The HTML for the event label
 */
function build_entry_label ( $event, $popupid,
  $can_access, $timestr, $time_only = 'N' ) {
  global $WC, $user, $eventinfo;
  $ret  = '';
  // Get reminders display string.
  $reminder = getReminders ( $event->getId (), true );
  $not_my_entry = ( ( $WC->loginId() != $user && strlen ( $user ) ) ||
    ( $WC->loginId() != $event->getLoginId () && strlen ( $event->getLoginId () ) ) );

  $sum_length = getPref ( 'SUMMARY_LENGTH' );
  if ( $event->isAllDay () || $event->isUntimed () )
    $sum_length += 6;

  $tmpAccess = $event->getAccess ();
  $tmpId = $event->getId ();
  $tmpLogin = $event->getLoginId ();
  $tmpName = $event->getName ();
  $tmp_ret = htmlspecialchars ( substr ( $tmpName, 0, $sum_length )
     . ( strlen ( $tmpName ) > $sum_length ? '...' : '' ) );

  if ( $not_my_entry && $tmpAccess == 'R' && !
    ( $can_access &PRIVATE_WT ) ) {
    if ( $time_only != 'Y' )
      $ret = '(' . translate ( 'Private' ) . ')';

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      str_replace ( 'XXX', translate ( 'private', true ),
        translate ( 'This event is XXX.', true ) ), '' );
  } else
  if ( $not_my_entry && $tmpAccess == 'C' && !
    ( $can_access &CONF_WT ) ) {
    if ( $time_only != 'Y' )
      $ret = '(' . translate ( 'Conf.' ) . ')';

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      str_replace ( 'XXX', translate ( 'confidential', true ),
        translate ( 'This event is XXX.', true ) ), '' );
  } else
  if ( $can_access == 0 ) {
    if ( $time_only != 'Y' )
      $ret = $tmp_ret;

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin, '',
      $timestr, '', '', $tmpName, '' );
  } else {
    if ( $time_only != 'Y' )
      $ret = $tmp_ret;

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      $event->getDescription (), $timestr, site_extras_for_popup ( $tmpId ),
      $event->getLocation (), $tmpName, $tmpId, $reminder );
  }
  return $ret;
}

/* Builds the HTML for the entry popup.
 *
 * @param string $popupid      CSS id to use for event popup
 * @param string $user         Username of user the event pertains to
 * @param string $description  Event description
 * @param string $time         Time of the event
 *                             (already formatted in a display format)
 * @param string $site_extras  HTML for any site_extras for this event
 *
 * @return string  The HTML for the event popup.
 */
function build_entry_popup ( $popupid, $user, $description = '', $time,
  $site_extras = '', $location = '', $name = '', $eid = '', $reminder = '' ) {
  global $WC, $popup_fullnames, $popuptemp_fullname,
  $tempfullname;

  if ( getPref ( 'DISABLE_POPUPS' ) )
    return;

  // Restrict info if time only set.
  $details = true;
  if ( ! $WC->isLogin( $user ) ) {
    $time_only = access_user_calendar ( 'time', $user );
    $details = ( $time_only == 'N' ? 1 : 0 );
  }

  $ret = 'new Tip( $(\'ev' . substr ( $popupid, 13 ) 
	  . '\'), \'<div id="' . $popupid . '" ><dl>';

  if ( empty ( $popup_fullnames ) )
    $popup_fullnames = array ();

  $partList = array ();
  if ( $details && $eid != '' && getPref ( 'PARTICIPANTS_IN_POPUP' ) ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_login_id, cal_status
      FROM webcal_entry_user WHERE cal_id = ? AND cal_status IN ( \'A\',\'W\' )',
      array ( $eid ) );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $participants[] = $row;
      }
    }
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      $WC->User->loadVariables ( $participants[$i][0], 'temp' );
      $partList[] = $tempfullname . ' '
       . ( $participants[$i][1] == 'W' ? '(?)' : '' );
    }
    $rows = dbi_get_cached_rows ( 'SELECT cal_fullname FROM webcal_entry_ext_user
      WHERE cal_id = ? ORDER by cal_fullname', array ( $eid ) );
    if ( $rows ) {
      $extStr = translate ( 'External User' );
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $partList[] = $row[0] . ' (' . $extStr . ')';
      }
    }
  }

  if ( ! $WC->isLogin( $user ) ) {
    if ( empty ( $popup_fullnames[$user] ) ) {
      $WC->User->loadVariables ( $user, 'popuptemp_' );
      $popup_fullnames[$user] = $popuptemp_fullname;
    }
    $ret .= '<dt>' . translate ( 'User' )
     . ":</dt><dd>$popup_fullnames[$user]</dd>";
  }
  $ret .= ( getPref ( 'SUMMARY_LENGTH' ) < 80 && strlen ( $name ) && $details
    ? '<dt>' . htmlspecialchars ( substr ( $name, 0, 40 ) ) . "</dt>" : '' )
   . ( strlen ( $time )
    ? '<dt>' . translate ( 'Time' ) . ":</dt><dd>$time</dd>" : '' )
   . ( ! empty ( $location ) && $details
    ? '<dt>' . translate ( 'Location' ) . ":</dt><dd> $location</dd>" : '' )
   . ( ! empty ( $reminder ) && $details
    ? '<dt>' . translate ( 'Send Reminder' ) . ":</dt><dd> $reminder</dd>" : '' );

  if ( ! empty ( $partList ) && $details ) {
    $ret .= '<dt>' . translate ( 'Participants' ) . ":</dt>";
    foreach ( $partList as $parts ) {
      $ret .= "<dd> $parts</dd>";
    }
  }

  if ( ! empty ( $description ) && $details ) {
    $ret .= '<dt>' . translate ( 'Description' ) . ":</dt><dd>";
    if ( getPref ( 'ALLOW_HTML_DESCRIPTION' ) ) {
      // Replace &s and decode special characters.
      $str = unhtmlentities (
        str_replace ( '&amp;amp;', '&amp;',
          str_replace ( '&', '&amp;', $description ) ) );
      // If there is no HTML found, then go ahead and replace
      // the line breaks ("\n") with the HTML break ("<br />").
      $ret .= ( strstr ( $str, '<' ) && strstr ( $str, '>' )
        ? $str : nl2br ( $str ) );
    } else
      // HTML not allowed in description, escape everything.
      $ret .= nl2br ( htmlspecialchars ( $description ) );

    $ret .= "</dd>";
  } //if $description
  return $ret . ( empty ( $site_extras ) ? '' : $site_extras ) . "</dl></div>');";
}

?>

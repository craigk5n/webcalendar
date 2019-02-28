<?php
/* Most of WebCalendar's functions.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */

/* Functions start here. All non-function code should be above this.
 *
 * Note to developers:
 *  Documentation is generated from the function comments below.
 *  When adding/updating functions, please follow these conventions.
 *  Your cooperation in this matter is appreciated. :-)
 *
 *  If you want your documentation to link to the db documentation,
 *  just make sure you mention the db table name followed by "table"
 *  on the same line. Here's an example:
 *    Retrieve preferences from the webcal_user_pref table.
 */

$tzInitSet = false;

/**
 * Logs a debug message.
 *
 * Generally, we try not to leave calls to this function in the code.
 * It is used for debugging only.
 *
 * @param string $msg Text to be logged
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  // error_log ( date ( 'Y-m-d H:i:s' ) . "> $msg\n<br />",
  // 3, 'd:/php/logs/debug.txt' );
  //$fd = fopen ( "/tmp/webcal.log", 'a+b' );
  //fwrite ( $fd, date ( 'Y-m-d H:i:s' ) . "> $msg\n" );
  //fclose ( $fd );
  // 3, '/tmp/webcal-debug.log' );
  // error_log ( date ( 'Y-m-d H:i:s' ) . "> $msg\n",
  // 2, 'sockieman:2000' );
}

/**
 * Looks for URLs in the given text, and makes them into links.
 *
 * @param string $text Input text
 *
 * @return string  The text altered to have HTML links for any web links.
 */
function activate_urls( $text ) {
  return preg_replace( '/[a-z]+:\/\/[^<> \t\r\n]+[a-z0-9\/]/i',
    '<a href="\\0">\\0</a>', $text );
}

/**
 * Adds something to the activity log for an event.
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
  $sql = 'INSERT INTO webcal_entry_log ( cal_log_id, cal_entry_id, cal_login,
    cal_user_cal, cal_type, cal_date, cal_time, cal_text )
    VALUES ( ?, ?, ?, ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, [$next_id, $event_id, $user,
        ( empty ( $user_cal ) ? null : $user_cal ), $type, gmdate ( 'Ymd' ),
          gmdate ( 'Gis' ), ( empty ( $text ) ? null : $text )] ) )
    db_error ( true, $sql );
}

/**
 * Get the corrected timestamp after adding or subtracting ONE_HOUR
 * to compensate for DST.
 */
function add_dstfree_time ( $date, $span, $interval = 1 ) {
  $ctime = date ( 'G', $date );
  $date += $span * $interval;
  $dtime = date ( 'G', $date );
  if ( $ctime == $dtime )
    return $date;
  elseif ( $ctime == 23 && $dtime == 0 )
    $date -= 3600;
  elseif ( ( $ctime == 0 && $dtime == 23 ) || $ctime > $dtime )
    $date += 3600;
  elseif ( $ctime < $dtime )
    $date -= 3600;

  return $date;
}

/**
 * Return the time in HHMMSS format of input time + duration
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
  // If we ran past 240000, then roll back over to 000000.
  $minutes %= ( 24 * 60 );

  return sprintf ( "%d%02d00", $minutes / 60, $minutes % 60 );
}

/**
 * Bump UNIX local timestamp with the given duration.
 *
 * param int $ts      input timestamp
 * param int $hour    hour duration
 * param int $minute  minute duration
 * param int $second  second duration
 * param int $month   month duration
 * param int $day     day duration
 * param int $year    year duration
 *
 * This function overcomes problems due to daylight saving change dates;
 *  it is based on the fact that function mktime() accepts out of range data.
 */

function bump_local_timestamp( $ts, $hour = 0, $minute = 0, $second = 0,
  $month = 0, $day = 0, $year = 0 ) {
  return mktime( date( 'G', $ts ) + $hour, date( 'i', $ts ) + $minute,
    date( 's', $ts ) + $second, date( 'n', $ts ) + $month,
    date( 'j', $ts ) + $day, date( 'Y', $ts ) + $year );
}

/**
 * Builds the HTML for the event label.
 *
 * @param string  $can_access
 * @param string  $time_only
 *
 * @return string  The HTML for the event label
 */
function build_entry_label ( $event, $popupid,
  $can_access, $timestr, $time_only = 'N' ) {
  global $eventinfo, $login, $SUMMARY_LENGTH, $UAC_ENABLED, $user;
  $ret = '';
  // Get reminders display string.
  $reminder = getReminders( $event->getId(), true );
  $can_access = ( $UAC_ENABLED == 'Y' ? $can_access : 0 );
  $not_my_entry = ( ( $login != $user && strlen ( $user ) ) ||
    ( $login != $event->getLogin() && strlen( $event->getLogin() ) ) );

  $sum_length = $SUMMARY_LENGTH;
  if( $event->isAllDay() || $event->isUntimed() )
    $sum_length += 6;

  $tmpAccess = $event->getAccess();
  $tmpId = $event->getId();
  $tmpLogin = $event->getLogin();
  $tmpName = $event->getName();
  $tmp_ret = htmlspecialchars ( substr ( $tmpName, 0, $sum_length )
     . ( strlen ( $tmpName ) > $sum_length ? '...' : '' ) );

  if ( $not_my_entry && $tmpAccess == 'R' && !
    ( $can_access &PRIVATE_WT ) ) {
    if ( $time_only != 'Y' )
      $ret = '<span class="parentheses">' . translate ( 'Private' ) . '</span>';

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      str_replace ( 'XXX', translate ( 'private' ),
        translate ( 'This event is XXX.' ) ), '' );
  } else
  if ( $not_my_entry && $tmpAccess == 'C' && !
    ( $can_access &CONF_WT ) ) {
    if ( $time_only != 'Y' )
      $ret = '(' . translate ( 'Conf.' ) . ')';

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      str_replace ( 'XXX', translate ( 'confidential' ),
        translate ( 'This event is XXX.' ) ), '' );
  } else
  if ( $can_access == 0 && $UAC_ENABLED == 'Y' ) {
    if ( $time_only != 'Y' )
      $ret = $tmp_ret;

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin, '',
      $timestr, '', '', $tmpName, '' );
  } else {
    if ( $time_only != 'Y' )
      $ret = $tmp_ret;

    $eventinfo .= build_entry_popup ( $popupid, $tmpLogin,
      $event->getDescription(), $timestr, site_extras_for_popup( $tmpId ),
      $event->getLocation(), $tmpName, $tmpId, $reminder );
  }
  return $ret;
}

/**
 * Calculates which row/slot this time represents.
 *
 * This is used in day and week views where hours of the time are separeted
 * into different cells in a table.
 *
 * <b>Note:</b> the global variable <var>$TIME_SLOTS</var> is used to determine
 * how many time slots there are and how many minutes each is. This variable
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
  global $TIME_SLOTS;

  $interval = 1440 / $TIME_SLOTS;
  $mins_since_midnight = time_to_minutes ( sprintf ( "%06d", $time ) );
  $ret = intval ( $mins_since_midnight / $interval );
  if ( $round_down && $ret * $interval == $mins_since_midnight )
    $ret--;

  if ( $ret > $TIME_SLOTS )
    $ret = $TIME_SLOTS;

  return $ret;
}

/**
 * Checks for conflicts.
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
 * @param string $login         The current user name
 * @param int    $id            Current event id (this keeps overlaps from
 *                              wrongly checking an event against itself)
 *
 * @return  Empty string for no conflicts or return the HTML of the
 *          conflicts when one or more are found.
 */
function check_for_conflicts ( $dates, $duration, $eventstart,
  $participants, $login, $id ) {
  global $LIMIT_APPTS, $LIMIT_APPTS_NUMBER, $repeated_events,
  $single_user, $single_user_login, $jumpdate;

  $datecnt = count ( $dates );
  if ( ! $datecnt )
    return false;

  $conflicts = '';
  $count = 0;
  $evtcnt = $found = $query_params = [];
  $partcnt = count ( $participants );

  $hour = gmdate ( 'H', $eventstart );
  $minute = gmdate ( 'i', $eventstart );

  $allDayStr = translate ( 'All day event' );
  $confidentialStr = translate ( 'Confidential' );
  $exceedsStr = translate ( 'exceeds limit of XXX events per day' );
  $onStr = translate ( 'on' );
  $privateStr = translate ( 'Private' );

  $sql = 'SELECT DISTINCT( weu.cal_login ), we.cal_time, we.cal_duration,
    we.cal_name, we.cal_id, we.cal_access, weu.cal_status, we.cal_date
    FROM webcal_entry we, webcal_entry_user weu WHERE we.cal_id = weu.cal_id AND ( ';

  for ( $i = 0; $i < $datecnt; $i++ ) {
    $sql .= ( $i != 0 ? ' OR ' : '' ) . 'we.cal_date = '
     . gmdate ( 'Ymd', $dates[$i] );
  }
  $sql .= ' ) AND we.cal_time >= 0 AND weu.cal_status IN ( \'A\',\'W\' ) AND ( ';
  if ( $single_user == 'Y' )
    $participants[0] = $single_user_login;
  else
  if ( strlen ( $participants[0] ) == 0 )
    // Likely called from a form with 1 user.
    $participants[0] = $login;

  for ( $i = 0; $i < $partcnt; $i++ ) {
    $sql .= ( $i > 0 ? ' OR ' : '' ) . 'weu.cal_login = ?';
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
      if ( $row[4] != $id ) {
        $cntkey = $row[0] . '-' . $row[7];
        $duration2 = $row[2];
        $time2 = sprintf ( "%06d", $row[1] );
        if ( empty ( $evtcnt[$cntkey] ) )
          $evtcnt[$cntkey] = 0;
        else
          $evtcnt[$cntkey]++;

        $over_limit = ( $LIMIT_APPTS == 'Y' && $LIMIT_APPTS_NUMBER > 0 &&
          $evtcnt[$cntkey] >= $LIMIT_APPTS_NUMBER ? 1 : 0 );

        if ( $over_limit ||
          times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
          $conflicts .= '
            <li>';

          if ( $single_user != 'Y' ) {
            user_load_variables ( $row[0], 'conflict_' );
            $conflicts .= $GLOBALS['conflict_fullname'] . ': ';
          }
          $conflicts .= ( $row[5] == 'C' && $row[0] != $login && !
            $is_assistant && ! $is_nonuser_admin
            // Assistants can see confidential stuff.
            ? '(' . $confidentialStr . ')'
            : ( $row[5] == 'R' && $row[0] != $login
              ? '( ' . $privateStr . ')'
              : '<a href="view_entry.php?id=' . $row[4]
               . ( $row[0] != $login ? '&amp;user=' . $row[0] : '' )
               . '">' . $row[3] . '</a>' ) )
           . ( $duration2 == 1440 && $time2 == 0
            ? ' (' . $allDayStr . ')'
            : ' (' . display_time ( $row[7] . $time2 )
             . ( $duration2 > 0
              ? '-' . display_time ( $row[7]
                 . add_duration ( $time2, $duration2 ) ) : '' ) . ')' )
           . ' ' . $onStr . ' '
           . date_to_str ( date ( 'Ymd', date_to_epoch ( $row[7]
                 . sprintf ( "%06d", $row[1] ) ) ) )
           . ( $over_limit ? ' (' . str_replace ( 'XXX', $LIMIT_APPTS_NUMBER,
              $exceedsStr ) . ')' : '' ) . '</li>';
        }
      }
    }
    dbi_free_result ( $res );
  } else
    db_error ( true );

  for ( $q = 0; $q < $partcnt; $q++ ) {
    // Read repeated events only once for a participant for performance reasons.
    $jumpdate = gmdate ( 'Ymd', $dates[count ( $dates )-1] );
    $repeated_events = query_events ( $participants[$q], true,
      // This date filter is not necessary for functional reasons, but it
      // eliminates some of the events that couldn't possibly match. This could
      // be made much more complex to put more of the searching work onto the
      // database server, or it could be dropped all together to put the
      // searching work onto the client.
      'AND ( we.cal_date <= ' . $jumpdate
       . ' AND ( wer.cal_end IS NULL OR wer.cal_end >= '
       . gmdate ( 'Ymd', $dates[0] ) . ' ) )' );
    for ( $i = 0; $i < $datecnt; $i++ ) {
      $dateYmd = gmdate ( 'Ymd', $dates[$i] );
      $list = get_repeating_entries ( $participants[$q], $dateYmd );
      for ( $j = 0, $listcnt = count ( $list ); $j < $listcnt; $j++ ) {
        // OK we've narrowed it down to a day, now I just gotta check the time...
        // I hope this is right...
        $row = $list[$j];
        if( $row->getID() != $id && ! in_array($row->getID(), $found )
            && ( $row->getExtForID() == '' || $row->getExtForID() != $id ) ) {
          $time2 = sprintf( "%06d", $row->getTime() );
          $duration2 = $row->getDuration();
          if ( times_overlap ( $time1, $duration1, $time2, $duration2 ) ) {
            $conflicts .= '
            <li>';
            if ( $single_user != 'Y' ) {
              user_load_variables( $row->getLogin(), 'conflict_' );
              $conflicts .= $GLOBALS['conflict_fullname'] . ': ';
            }
            $conflicts .= ( $row->getAccess() == 'C'
              && $row->getLogin() != $login && ! $is_assistant
              && ! $is_nonuser_admin
              // Assistants can see confidential stuff.
              ? '(' . $confidentialStr . ')'
              : ( $row->getAccess() == 'R' && $row->getLogin() != $login
                ? '(' . $privateStr . ')'
                : '<a href="view_entry.php?id=' . $row->getID()
                 . ( ! empty ( $user ) && $user != $login
                  ? '&amp;user=' . $user : '' )
                 . '">' . $row->getName() . '</a>' ) )
             . ' (' . display_time( $row->getDate() . $time2 )
             . ( $duration2 > 0
              ? '-' . display_time( $row->getDate()
                 . add_duration ( $time2, $duration2 ) ) : '' )
             . ')' . ' ' . $onStr . ' ' . date_to_str ( $dateYmd ) . '</li>';
          }
        }
      }
    }
  }

  return $conflicts;
}

/**
 * Replaces unsafe characters with HTML encoded equivalents.
 *
 * @param string $value  Input text
 *
 * @return string  The cleaned text.
 */
function clean_html ( $value ) {
  $value = htmlspecialchars ( $value, ENT_QUOTES );
  $value = strtr ( $value, [
      '(' => '&#40;',
      ')' => '&#41;'] );
  return $value;
}

/**
 * Removes non-digits from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_int ( $data ) {
  return preg_replace ( '/\D/', '', $data );
}

/**
 * Removes whitespace from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_whitespace ( $data ) {
  return preg_replace ( '/\s/', '', $data );
}

/**
 * Removes non-word characters from the specified text.
 *
 * @param string $data  Input text
 *
 * @return string  The converted text.
 */
function clean_word ( $data ) {
  return preg_replace ( '/\W/', '', $data );
}

/**
 * Combines the repeating and nonrepeating event arrays and sorts them
 *
 * The returned events will be sorted by time of day.
 *
 * @param array $ev   Array of events
 * @param array $rep  Array of repeating events
 *
 * @return array  Array of Events.
 */
function combine_and_sort_events ( $ev, $rep ) {
  $ids = [];

  // Repeating events show up in $ev and $rep.
  // Record their ids and don't add them to the combined array.
  foreach ( $rep as $obj ) {
    $ids[] = $obj->getID();
  }
  foreach ( $ev as $obj ) {
    if( ! in_array( $obj->getID(), $ids ) )
     $rep[] = $obj;
  }
  usort ( $rep, 'sort_events' );

  return $rep;
}

/**
 * Draws a daily outlook style availability grid showing events that are
 * approved and awaiting approval.
 *
 * @param string $date          Date to show the grid for
 * @param array  $participants  Which users should be included in the grid
 * @param string $popup         Not used
 *
 * @return string  HTML to display matrix.
 */
function daily_matrix ( $date, $participants, $popup = '' ) {
  global $CELLBG, $ENTRY_SLOTS, $events, $repeated_events, $TABLEBG, $THBG,
  $THFG, $thismonth, $thisyear, $TIME_FORMAT, $TODAYCELLBG, $user_fullname,
  $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR;

  $allAttendeesStr = translate ( 'All Attendees' );
  $busy = translate ( 'Busy' );
  $cnt = count ( $participants );
  $dateTS = date_to_epoch ( $date );
  $first_hour = $WORK_DAY_START_HOUR;
  $increment = intval ( 1440 /
    ( $ENTRY_SLOTS > 288 ? 288 : ( $ENTRY_SLOTS < 72 ? 72 : $ENTRY_SLOTS ) ) );
  $last_hour = $WORK_DAY_END_HOUR;
  $master = [];
  $MouseOut = $MouseOver = $str = '';
  $participant_pct = '20%'; //Use percentage.

  $tentative = translate ( 'Tentative' );
  $titleStr = ' title="' . translate ( 'Schedule an appointment for XXX.' ) . '">';
  $viewMsg = translate ( 'View this entry' );

  $hours = $last_hour - $first_hour;
  $interval = intval ( 60 / $increment );
  $cell_pct = intval ( 80 / ( $hours * $interval ) );
  $style_width = ( $cell_pct > 0 ? 'style="width:' . $cell_pct . '%;"' : '' );
  $thismonth = date ( 'm', $dateTS );
  $thisyear = date ( 'Y', $dateTS );
  $cols = ( ( $hours * $interval ) + 1 );
  $ret = <<<EOT
    <br />
    <table class="aligncenter matrixd" style="width:'80%';"
     >
      <tr>
        <td class="matrix" colspan="{$cols}"></td>
      </tr>
      <tr>
        <th style="width:{$participant_pct};">
EOT;
   $ret .= translate ( 'Participants' ) . '</th>';
  $tentative = translate ( 'Tentative' );
  $titleStr = ' title="' . translate ( 'Schedule an appointment for XXX.' ) . '">';
  $viewMsg = translate ( 'View this entry' );

  $hours = $last_hour - $first_hour;
  $interval = intval ( 60 / $increment );
  $cell_pct = intval ( 80 / ( $hours * $interval ) );
  $cols = ( ( $hours * $interval ) + 1 );
  $style_width = ( $cell_pct > 0 ? 'style="width:' . $cell_pct . '%;"' : '' );
  $thismonth = date ( 'm', $dateTS );
  $thisyear = date ( 'Y', $dateTS );

  // Build a master array containing all events for $participants.
  for ( $i = 0; $i < $cnt; $i++ ) {
    /* Pre-Load the repeated events for quckier access. */
    $repeated_events = read_repeated_events ( $participants[$i], $dateTS,
      $dateTS, '' );
    /* Pre-load the non-repeating events for quicker access. */
    $events = read_events ( $participants[$i], $dateTS, $dateTS );

    // Combine events for this date into a single array for easy processing.
    $ALL = array_merge (
      get_repeating_entries ( $participants[$i], $date ),
      get_entries ( $date )
      );
    foreach ( $ALL as $E ) {
      if( $E->getTime() == 0 ) {
        $duration = 60 * $hours;
        $time = $first_hour . '0000';
      } else {
        $duration = $E->getDuration();
        $time = date( 'His', $E->getDateTimeTS() );
      }
      $hour = substr ( $time, 0, 2 );
      $mins = substr ( $time, 2, 2 );

      // Convert cal_time to slot.
      $slot = $hour + substr ( $mins, 0, 1 );

      // Convert cal_duration to bars.
      $bars = $duration / $increment;

      // Never replace 'A' with 'W'.
      for ( $q = 0; $bars > $q; $q++ ) {
        $slot = sprintf ( "%02.2f", $slot );
        if ( strlen ( $slot ) == 4 )
          $slot = '0' . $slot; // Add leading zeros.

        $slot = $slot . ''; // Convert to a string.
        if ( empty ( $master['_all_'][$slot] ) ||
            ( $master['_all_'][$slot]['stat'] != 'A' ) )
          $master['_all_'][$slot]['stat'] = $E->getStatus();

        if ( empty ( $master[$participants[$i]][$slot] ) ||
            ( $master[$participants[$i]][$slot]['stat'] != 'A' ) ) {
          $master[$participants[$i]][$slot]['stat'] = $E->getStatus();
          $master[$participants[$i]][$slot]['ID'] = $E->getID();
        }
        $slot = $slot + ( $increment * .01 );
        if ( $slot - ( int )$slot >= .59 )
          $slot = ( int )$slot + 1;
      }
    }
  }

  for( $i = $first_hour; $i < $last_hour; $i++ ) {
    $hour = $i;
    if ( $TIME_FORMAT == '12' ) {
      $hour %= 12;
      if ( $hour == 0 )
        $hour = 12;

      $hourfmt = '%d';
    } else
      $hourfmt = '%02d';

    $halfway = intval ( ( $interval / 2 ) -1 );
    for( $j = 0; $j < $interval; $j++ ) {
      $inc_x_j = $increment * $j;
      $str .= '
        <td id="C' . ( $j + 1 ) . '" class="dailymatrix" ';
      $tmpTitle = 'onmousedown="schedule_event( ' . $i . ','
       . sprintf ( "%02d", $inc_x_j ) . ' );"' . $MouseOver . $MouseOut
       . str_replace ( 'XXX', sprintf ( $hourfmt, $hour ) . ':' .
          ( $inc_x_j <= 9 ? '0' : '' ) . $inc_x_j, $titleStr );
      switch ( $j ) {
        case $halfway:
          $k = ( $hour <= 9 ? '0' : substr ( $hour, 0, 1 ) );
          $str .= 'style="width:' . $cell_pct . '%; text-align:right;" '
           . $tmpTitle . $k . '</td>';
          break;
        case $halfway + 1:
          $k = ( $hour <= 9 ? substr ( $hour, 0, 1 ) : substr ( $hour, 1, 2 ) );
          $str .= 'style="width:' . $cell_pct . '%; text-align:left;" '
           . $tmpTitle . $k . '</td>';
          break;
        default:
          $str .= $style_width . $tmpTitle . '&nbsp;&nbsp;</td>';
      }
    }
  }
  $ret .= $str . '
      </tr>
      <tr>
        <td class="matrix" colspan="' . $cols . '"></td>
      </tr>';

  // Add user _all_ to beginning of $participants array.
  array_unshift ( $participants, '_all_' );
  // Javascript for cells.
  // Display each participant.
  for ( $i = 0; $i <= $cnt; $i++ ) {
    if ( $participants[$i] != '_all_' ) {
      // Load full name of user.
      user_load_variables ( $participants[$i], 'user_' );

      // Exchange space for &nbsp; to keep from breaking.
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $user_fullname );
    } else
      $user_nospace = preg_replace ( '/\s/', '&nbsp;', $allAttendeesStr );

    $ret .= '
      <tr>
        <th class="row" style="width:' . $participant_pct . ';">'
     . $user_nospace . '</th>';
    $col = 1;

    // Check each timebar.
    for ( $j = $first_hour; $j < $last_hour; $j++ ) {
      for ( $k = 0; $k < $interval; $k++ ) {
        $r = sprintf ( "%02d", $j ) . '.'
         . sprintf ( "%02d", ( $increment * $k ) ) . '';
        $space = '&nbsp;';

        if ( empty ( $master[$participants[$i]][$r] ) ) {
          // Ignore this..
        } else
        if ( empty ( $master[$participants[$i]][$r]['ID'] ) )
          // This is the first line for 'all' users. No event here.
          $space = '
          <span class="matrix"><img src="images/pix.gif" alt="" /></span>';
        else {
          $tmpMast = $master[$participants[$i]][$r]['stat'];
          if ( strpos ( 'AW', $tmpMast ) !== false )
            $space = '
          <a class="matrix" href="view_entry.php?id='
             . $master[$participants[$i]][$r]['ID']
             . '&friendly=1"><img src="images/pix' . ( $tmpMast = 'A' ? '' : 'b' )
             . '.gif" title="' . $viewMsg . '" alt="' . $viewMsg . '" /></a>';
        }

        $ret .= '
        <td class="matrixappts' . ( $k == '0' ? ' matrixledge' : '' ) . '" '
         . $style_width . ( $space == '&nbsp;' ? ' '
           . 'onmousedown="schedule_event( ' . $j . ','
           . sprintf ( "%02d", ( $increment * $k ) ) . ' );"'
           . " $MouseOver $MouseOut" : '' ) . '>' . $space . '</td>';
        $col++;
      }
    }

    $ret .= '
      </tr>
      <tr>
        <td class="matrix" colspan="' . $cols
     . '"><img src="images/pix.gif" alt="-" /></td>
      </tr>';
  } // End foreach participant.
  return $ret . <<<EOT
    </table><br />
    <table class="aligncenter">
      <tr>
        <td class="matrixlegend"><img src="images/pix.gif" title="{$busy}"
          alt="{$busy}" />{$busy}&nbsp;&nbsp;&nbsp;<img src="images/pixb.gif"
          title="{$tentative}" alt="{$tentative}" />{$tentative}</td>
      </tr>
    </table>
EOT;
}

/**
 * Date selection via DHTML.
 * This will create two HTML elements:
 * - a form element of type hidden that will hold the date value in
 *   YYYYMMDD format
 * - a span element that will display the nicely formatted date to the user
 * The CSS ids will be the datename parameter with '_fmt' and '_YMD'
 * appended.
 */
function datesel_Print ( $datename, $ymdValue='' )
{
  if ( empty ( $ymdValue ) )
    $ymdValue = date ( 'Ymd' );

  return '<input type="hidden" name="' . $datename .
    '_YMD" id="' . $datename . '_YMD" value="' . $ymdValue . '"/>' .
    '<span id="' . $datename . '_fmt">' .
    date_to_str ( $ymdValue ) . '</span> ' .
    '<img id="dateselIcon_' . $datename .
    '" class="dateselIcon" onclick="datesel_SelectDate(event,\'' .
    $datename  . '\' );" src="images/datesel.gif" />';
}


/**
 * Generate HTML for a date selection for use in a form.
 *
 * @param string $prefix   Prefix to use in front of form element names
 * @param string $date     Currently selected date (in YYYYMMDD format)
 * @param bool $trigger    Add onchange event trigger that
 *                         calls javascript function $prefix_datechanged()
 * @param int  $num_years  Number of years to display
 *
 * @return string  HTML for the selection box.
 */
function date_selection ( $prefix, $date, $trigger = false, $num_years = 20 ) {
  $selected = ' selected="selected"';
  $trigger_str = ( empty( $trigger ) ? '' : $prefix . 'datechanged();' );
  $onchange = ( empty ( $trigger_str ) ? '' : 'onchange="$trigger_str"' );
  if ( strlen ( $date ) != 8 )
    $date = date ( 'Ymd' );

  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  if ( $thisyear - date ( 'Y' ) >= ( $num_years - 1 ) )
    $num_years = $thisyear - date ( 'Y' ) + 2;

  $dd_select = '
      <select name="' . $prefix . 'day" id="' . $prefix . 'day"'
   . $onchange . '>';
  for ( $i = 1; $i <= 31; $i++ ) {
    $dd_select .= '
        <option value="' . "$i\""
     . ( $i == substr ( $date, 6, 2 ) ? $selected : '' ) . ">$i" . '</option>';
  }
  $dd_select .= '
      </select>';

  //  $mm_select ... number of month, $month_select name of month
  $month_select = '
      <select name="' . $prefix . 'month"' . $onchange . '>';
  $mm_select = '
      <select name="' . $prefix . 'month"' . $onchange . '>';
  for ( $i = 1; $i < 13; $i++ ) {
    $month_select .= '
        <option value="' . "$i\""
     . ( $i == substr ( $date, 4, 2 ) ? $selected : '' )
     . '>' . month_name ( $i - 1, 'M' ) . '</option>';

    $mm_select .= '
        <option value="' . "$i\""
     . ( $i == substr( $date, 4, 2 ) ? $selected : '' )
     . '>' . $i . '</option>';
  }
  $month_select .= '
      </select>';
  $mm_select .= '
      </select>';
  $yyyy_select = '
      <select name="' . $prefix . 'year"' . $onchange . '>';
  for ( $i = -10; $i < $num_years; $i++ ) {
    $y = $thisyear + $i;
    $yyyy_select .= '
        <option value="' . "$y\"" . ( $y == $thisyear ? $selected : '' )
     . ">$y" . '</option>';
  }
  $yyyy_select .= '
      </select>';
  $replace_strings = [
                           '__yyyy__'=>$yyyy_select,
                           '__month__'=>$month_select,
                           '__mm__'=>$mm_select,
                           '__dd__'=>$dd_select];
  $ret = strtr( translate( 'date_select'), $replace_strings );
  return $ret . '
      <input type="button" name="' . $prefix . 'btn" onclick="selectDate( \''
   . $prefix . 'day\',\'' . $prefix . 'month\',\'' . $prefix . "year','$date'"
   . ', event, this.form );" value="' . translate ( 'Select' ) . '..." />' . "\n";
}

/**
 * Converts a date to a timestamp.
 *
 * @param string $d   Date in YYYYMMDD or YYYYMMDDHHIISS format
 * @param bool   $gmt Whether to use GMT or LOCAL
 *
 * @return int  Timestamp representing, in UTC or LOCAL time.
 */
function date_to_epoch( $d, $gmt = true ) {
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

  if ( $gmt )
    return gmmktime ( $dH, $di, $ds,
      substr ( $d, 4, 2 ),
      substr ( $d, 6, 2 ),
      substr ( $d, 0, 4 ) );
  else
    return mktime ( $dH, $di, $ds,
      substr ( $d, 4, 2 ),
      substr ( $d, 6, 2 ),
      substr ( $d, 0, 4 ) );
}


/**
 * Converts a date in YYYYMMDD format into "Friday, December 31, 1999",
 * "Friday, 12-31-1999" or whatever format the user prefers.
 *
 * @param string  $indate        Date in YYYYMMDD format
 * @param string  $format        Format to use for date
 *                               (default is "__month__ __dd__, __yyyy__")
 * @param bool    $show_weekday  Should the day of week also be included?
 * @param bool    $short_months  Should the abbreviated month names be used
 *                               instead of the full month names?
 * @param bool   $forceTranslate Check to see if there is a translation for
 *                    the specified data format. If there is, then use
 *                    the translated format from the language file, but
 *                    only if $DATE_FORMAT is language-defined.
 *
 * @return string  Date in the specified format.
 *
 * @global string Preferred date format
 */
function date_to_str ( $indate, $format = '', $show_weekday = true,
  $short_months = false, $forceTranslate = false ) {
  global $DATE_FORMAT;

  if ( strlen ( $indate ) == 0 )
    $indate = date ( 'Ymd' );

  // If they have not set a preference yet...
  if ( $DATE_FORMAT == '' || $DATE_FORMAT == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );
  else if ( $DATE_FORMAT == 'LANGUAGE_DEFINED' &&
    $forceTranslate && $format != '' && translation_exists ( $format ) ) {
    $format = translate ( $format );
  }

  if ( empty ( $format ) )
    $format = $DATE_FORMAT;

  $y = intval ( $indate / 10000 );
  $m = intval ( $indate / 100 ) % 100;
  $d = $indate % 100;
  $wday = strftime ( "%w", mktime ( 0, 0, 0, $m, $d, $y ) );
  if ( $short_months ) {
    $month = month_name ( $m - 1, 'M' );
    $weekday = weekday_name ( $wday, 'D' );
  } else {
    $month = month_name ( $m - 1 );
    $weekday = weekday_name ( $wday );
  }

  $ret = str_replace ( '__dd__', $d, $format );
  $ret = str_replace ( '__j__', intval ( $d ), $ret );
  $ret = str_replace ( '__mm__', $m, $ret );
  $ret = str_replace ( '__mon__', $month, $ret );
  $ret = str_replace ( '__month__', $month, $ret );
  $ret = str_replace ( '__n__', sprintf ( "%02d", $m ), $ret );
  $ret = str_replace ( '__yy__', sprintf ( "%02d", $y % 100 ), $ret );

  return ( $show_weekday
    ? weekday_name ( strftime ( '%w', mktime ( 0, 0, 0, $m, $d, $y ) ),
      ( $short_months ? 'D' : '' ) ) . ', '
    : '' ) . str_replace ( '__yyyy__', $y, $ret );
}

/**
 * Extracts a user's name from a session id.
 *
 * This prevents users from begin able to edit their cookies.txt file and set
 * the username in plain text.
 *
 * @param string $instr  A hex-encoded string. "Hello" would be "678ea786a5".
 *
 * @return string  The decoded string.
 *
 * @global array Array of offsets
 *
 * @see encode_string
 */
function decode_string ( $instr ) {
  global $offsets;

  $cntOffsets = count ( $offsets );
  $orig = '';
  for ( $i = 0, $cnt = strlen ( $instr ); $i < $cnt; $i += 2 ) {
    $orig .= chr (
      ( hextoint ( substr ( $instr, $i, 1 ) ) * 16 +
        hextoint ( substr ( $instr, $i + 1, 1 ) ) - $offsets[
        ( $i / 2 ) % $cntOffsets ] + 256 ) % 256 );
  }
  return $orig;
}

/**
 * Display a text for a single activity log entry.
 *
 * @param string $cal_type  the log entry type
 * @param string $cal_text  addiitonal text to display
 *
 * @return string  HTML for one log entry.
 */
function display_activity_log( $cal_type, $cal_text = '', $break = '<br />&nbsp;' ) {
  if ( $cal_type == LOG_APPROVE )
    $ret = translate ( 'Event approved' );
  elseif ( $cal_type == LOG_APPROVE_J )
    $ret = translate ( 'Journal approved' );
  elseif ( $cal_type == LOG_APPROVE_T )
    $ret = translate ( 'Task approved' );
  elseif ( $cal_type == LOG_ATTACHMENT )
    $ret = translate ( 'Attachment' );
  elseif ( $cal_type == LOG_COMMENT )
    $ret = translate ( 'Comment' );
  elseif ( $cal_type == LOG_CREATE )
    $ret = translate ( 'Event created' );
  elseif ( $cal_type == LOG_CREATE_J )
    $ret = translate ( 'Journal created' );
  elseif ( $cal_type == LOG_CREATE_T )
    $ret = translate ( 'Task created' );
  elseif ( $cal_type == LOG_DELETE )
    $ret = translate ( 'Event deleted' );
  elseif ( $cal_type == LOG_DELETE_J )
    $ret = translate ( 'Journal deleted' );
  elseif ( $cal_type == LOG_DELETE_T )
    $ret = translate ( 'Task deleted' );
  elseif ( $cal_type == LOG_LOGIN_FAILURE )
    $ret = translate ( 'Invalid login' );
  elseif ( $cal_type == LOG_NEWUSER_EMAIL )
    $ret = translate ( 'New user via email (self registration)' );
  elseif ( $cal_type == LOG_NEWUSER_FULL )
    $ret = translate ( 'New user (self registration)' );
  elseif ( $cal_type == LOG_NOTIFICATION )
    $ret = translate ( 'Notification sent' );
  elseif ( $cal_type == LOG_REJECT )
    $ret = translate ( 'Event rejected' );
  elseif ( $cal_type == LOG_REJECT_J )
    $ret = translate ( 'Journal rejected' );
  elseif ( $cal_type == LOG_REJECT_T )
    $ret = translate ( 'Task rejected' );
  elseif ( $cal_type == LOG_REMINDER )
    $ret = translate ( 'Reminder sent' );
  elseif ( $cal_type == LOG_UPDATE )
    $ret = translate ( 'Event updated' );
  elseif ( $cal_type == LOG_UPDATE_J )
    $ret = translate ( 'Journal updated' );
  elseif ( $cal_type == LOG_UPDATE_T )
    $ret = translate ( 'Task updated' );
  elseif ( $cal_type == LOG_USER_ADD )
    $ret = translate ( 'Add User' );
  elseif ( $cal_type == LOG_USER_DELETE )
    $ret = translate ( 'Delete User' );
  elseif ( $cal_type == LOG_USER_UPDATE )
    $ret = translate ( 'Edit User' );
  else
    $ret = '???';
  //fix any broken special characters
  $cal_text = preg_replace( "/&amp;(#[0-9]+|[a-z]+);/i", "&$1;",
    htmlentities( $cal_text ) );
  return $ret
   . ( empty ( $cal_text ) ? '' : $break . $cal_text );
}

/**
 * Display the <<Admin link on pages if menus are not enabled
 *
 * @param bool $break  If true, include break if empty
 *
 * @return string  HTML for Admin Home link
 * @global string  (Y/N) Is the Top Menu Enabled
 */
function display_admin_link ( $break = true ) {
  global $MENU_ENABLED;

  $adminStr = translate ( 'Admin' );

  return ( $break ? '<br />' . "\n" : '' )
   . ( $MENU_ENABLED == 'N' ? '<a title="' . $adminStr
     . '" class="nav" href="adminhome.php">&laquo;&nbsp; ' . $adminStr
     . '</a><br /><br />' . "\n" : '' );
}

/**
 * Generate HTML to create a month display.
 * If $enableDblClick is set to true, the file js/dblclick_add.js should
 * be included in the array of includes passed to print_header().
 */
function display_month( $thismonth, $thisyear, $demo = false,
  $enableDblClick = false ) {
  global $DISPLAY_ALL_DAYS_IN_MONTH, $DISPLAY_LONG_DAYS, $DISPLAY_WEEKNUMBER,
  $is_admin, $is_nonuser, $login, $PUBLIC_ACCESS, $PUBLIC_ACCESS_CAN_ADD,
  $readonly, $today, $user, $WEEKENDBG, $WEEK_START;

  $ret = '';

  if ( $enableDblClick ) {
    $can_add = ( $readonly == 'N' || $is_admin );

    if ( $PUBLIC_ACCESS == 'Y' && $PUBLIC_ACCESS_CAN_ADD != 'Y'
        && $login == '__public__' )
      $can_add = false;

    if ( $readonly == 'Y' )
      $can_add = false;

    if ( $is_nonuser )
      $can_add = false;
  } else {
    // double-click not enabled
    $can_add = false;
  }

  // Add mouse-over help for table.
  if ( $can_add ) {
    $help = 'title="' .
      translate ( 'Double-click on empty cell to add new entry' ) . '"';
  } else {
    $help = '';
  }

  $ret .= '
    <table ' . $help . ' class="main" id="month_main">
      <tr>' . ( $DISPLAY_WEEKNUMBER == 'Y' ? '
        <th class="empty"></th>' : '' );

  for ( $i = 0; $i < 7; $i++ ) {
    $thday = ( $i + $WEEK_START ) % 7;
    $ret .= '
        <th' . ( is_weekend ( $thday ) ? ' class="weekend"' : '' )
     . '>' . weekday_name ( $thday, $DISPLAY_LONG_DAYS ) . '</th>';
  }
  $ret .= '
      </tr>';
  $charset = translate ( 'charset' );
  $weekStr = translate ( 'Week' );
  $WKStr = translate ( 'WK' );

  $wkstart = get_weekday_before ( $thisyear, $thismonth );
  // Generate values for first day and last day of month.
  $monthstart = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
  $monthend = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear ) );
  $monthend2 = date ( 'Ymd His', mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear ) );
  $todayYmd = date ( 'Ymd', $today );
  for ( $i = $wkstart; date ( 'Ymd', $i + 43200 ) <= $monthend; $i += 604800 ) {
    $ret .= '
      <tr>';
    if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
      $tmp = date( 'W', $i + 172800 );
      $ret .= '
        <td class="weekcell"><a title="' . $weekStr . ' ' . $tmp . '" href="'
       . ( $demo ? '' : 'week.php?date=' . date ( 'Ymd', $i + 86400 )
         . ( ! empty ( $user ) && $user != $login ? '&amp;user=' . $user : '' )
         . ( empty ( $cat_id ) ? '' : '&amp;cat_id=' . $cat_id ) ) . '"' . '>';

      $wkStr = $WKStr . $tmp;
      $wkStr2 = '';

      if ( $charset == 'UTF-8' )
        $wkStr2 = $wkStr;
      else {
        for ( $w = 0, $cnt = strlen ( $wkStr ); $w < $cnt; $w++ ) {
          $wkStr2 .= substr ( $wkStr, $w, 1 ) . '<br />';
        }
      }
      $ret .= $wkStr2 . '</a></td>';
    }

    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 86400 + 43200 );
      $dateYmd = date ( 'Ymd', $date );
      $dateD = date ( 'd', $date );
      $thiswday = date ( 'w', $date );
      $is_weekend = is_weekend ( $date ) && ( ! empty ( $WEEKENDBG ) );
      $ret .= '
        <td';

      if ( $can_add ) {
        $ret .= " ondblclick=\"dblclick_add( '$dateYmd', '$user' )\"";
      }

      $currMonth = ( $dateYmd >= $monthstart && $dateYmd <= $monthend );
      if ( $currMonth ||
        ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) && $DISPLAY_ALL_DAYS_IN_MONTH == 'Y' ) ) {
        $class = ( $currMonth
          ? ( ! $demo && $dateYmd == $todayYmd ? 'today' : ( $is_weekend ? 'weekend' : '' ) )
          : 'othermonth' );

        // Get events for this day.
        $ret_events = '';
        if ( ! $demo ) {
          $ret_events = print_date_entries ( $dateYmd,
            ( empty ( $user ) ? $login : $user ), false, true );
        } else {
          // Since we base this calendar on the current month,
          // the placement of the days always change so
          // set 3rd Thursday as "today" for the demo...
          if ( $dateD > 15 && $dateD < 23 && $thiswday == 4 ) {
            $class = 'today';
            $ret_events = translate ( 'Today' );
          }
          // ... and set 2nd Saturday and 2nd Tuesday as the demo event days.
          if ( $dateD > 7 && $dateD < 16 &&
            ( $thiswday == 2 || $thiswday == 6 ) ) {
            $class .= ' entry hasevents';
            $ret_events = translate ( 'My event text' );
          }
        }
        $class = trim ( $class );
        $class .= ( ! empty( $ret_events )
            && strstr( $ret_events, 'class="entry"' )
// If we decide we don't like it, just remove the next 1 line.
            || strstr( $ret_events, 'class="layerentry"' )
          ? ' hasevents' : '' );

        $ret .= ( strlen ( $class ) ? ' class="' . $class . '"' : '' )
         . '>' . $ret_events . '</td>';
      } else
        $ret .= ( $is_weekend ? ' class="weekend"' : '' ) . '>&nbsp;</td>';
    }
    $ret .= '
      </tr>';
  }
  return $ret . '
    </table>';
}

/**
 * Generate the HTML for the navigation bar.
 */
function display_navigation ( $name, $show_arrows = true, $show_cats = true ) {
  global $cat_id, $CATEGORIES_ENABLED, $caturl, $DATE_FORMAT_MY,
  $DISPLAY_SM_MONTH, $DISPLAY_TASKS, $DISPLAY_WEEKNUMBER, $is_admin,
  $is_assistant, $is_nonuser_admin, $login, $nextYmd, $nowYmd, $prevYmd,
  $single_user, $spacer, $thisday, $thismonth, $thisyear, $user, $user_fullname,
  $wkend, $wkstart;

  if ( empty ( $name ) )
    return;

  $nextStr = translate ( 'Next' );
  $prevStr = translate ( 'Previous' );
  $u_url = ( ! empty ( $user ) && $user != $login
    ? 'user=' . $user . '&amp;' : '' );
  $ret = '
      <div class="topnav"'
  // Hack to prevent giant space between minicals and navigation in IE.
  . ( get_web_browser() == 'MSIE' ? ' style="zoom:1"' : '' )
   . '>' . ( $show_arrows &&
    ( $name != 'month' || $DISPLAY_SM_MONTH == 'N' || $DISPLAY_TASKS == 'Y' ) ? '
        <a title="' . $nextStr . '" class="next" href="' . $name . '.php?'
     . $u_url . 'date=' . $nextYmd . $caturl
     . '"><img src="images/rightarrow.gif" alt="' . $nextStr . '" /></a>
        <a title="' . $prevStr . '" class="prev" href="' . $name . '.php?'
     . $u_url . 'date=' . $prevYmd . $caturl
     . '"><img src="images/leftarrow.gif" alt="' . $prevStr . '" /></a>' : '' ) . '
        <div class="title">
          <span class="date">';

  if ( $name == 'day' )
    $ret .= date_to_str ( $nowYmd );
  elseif ( $name == 'week' )
    $ret .= date_to_str ( date ( 'Ymd', $wkstart ), '', false )
     . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;'
     . date_to_str ( date ( 'Ymd', $wkend - 86400 ), '', false )
     . ( $DISPLAY_WEEKNUMBER == 'Y' ? " \n(" . translate ( 'Week' ) . ' '
       . date ( 'W', $wkstart + 86400 ) . ')' : '' );
  elseif ( $name == 'month' || $name == 'view_l' ) {
    $ret .= $spacer
     . date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
      $DATE_FORMAT_MY, false, false, true );
  }

  return $ret . '</span>
          <span class="user">'
  // Display current calendar's user (if not in single user).
  . ( $single_user == 'N' ? '<br />' . $user_fullname : '' )
   . ( $is_nonuser_admin ||
    ( $is_admin && ! empty ( $user ) && $user == '__public__' )
    ? '<br />-- ' . translate ( 'Admin mode' ) . ' --' : '' )
   . ( $is_assistant
    ? '<br />-- ' . translate ( 'Assistant mode' ) . ' --' : '' ) . '</span>'
   . ( $CATEGORIES_ENABLED == 'Y' && $show_cats &&
    ( ! $user || ( $user == $login || $is_assistant ) ) ? '<br /><br />'
     . print_category_menu ( $name,
      sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday ),
      $cat_id ) : '' ) . '
        </div>
      </div><br />';
}

/**
 * Prints out a minicalendar for a month.
 *
 * @todo Make day.php NOT be a special case
 *
 * @param int    $thismonth      Number of the month to print
 * @param int    $thisyear       Number of the year
 * @param bool   $showyear       Show the year in the calendar's title?
 * @param bool   $show_weeknums  Show week numbers to the left of each row?
 * @param string $minical_id     id attribute for the minical table
 * @param string $month_link     URL and query string for month link that should
 *                               come before the date specification (e.g.
 *                               month.php?  or  view_l.php?id=7&amp;)
 */
function display_small_month ( $thismonth, $thisyear, $showyear,
  $show_weeknums = false, $minical_id = '', $month_link = 'month.php?' ) {
  global $boldDays, $caturl, $DATE_FORMAT_MY, $DISPLAY_ALL_DAYS_IN_MONTH,
  $DISPLAY_TASKS, $DISPLAY_WEEKNUMBER, $get_unapproved, $login,
  $MINI_TARGET, // Used by minical.php
  $SCRIPT, $SHOW_EMPTY_WEEKENDS,//Used by year.php
  $thisday, // Needed for day.php
  $today, $use_http_auth, $user, $WEEK_START;

  $nextStr = translate ( 'Next' );
  $prevStr = translate ( 'Previous' );
  $u_url = ( $user != $login && ! empty ( $user )
    ? 'user=' . $user . '&amp;' : '' );
  $weekStr = translate ( 'Week' );

  // Start the minical table for each month.
  $ret = '
    <table class="minical"'
   . ( $minical_id != '' ? ' id="' . $minical_id . '"' : '' ) . '>';

  $monthstart = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
  $monthend = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear ) );
  // Determine if the week starts on Sunday or Monday.
  // TODO:  We need to be able to start a week on ANY day.
  $wkstart = get_weekday_before ( $thisyear, $thismonth );

  if ( $SCRIPT == 'day.php' ) {
    $month_ago =
    date ( 'Ymd', mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear ) );
    $month_ahead =
    date ( 'Ymd', mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear ) );

    $ret .= '<caption>' . $thisday . '</caption>
      <thead>
        <tr class="monthnav">
          <th colspan="' . ( $DISPLAY_WEEKNUMBER == true ? 8 : 7 ) . '">
            <a title="' . $prevStr . '" class="prev" href="day.php?' . $u_url
     . 'date=' . $month_ago . $caturl
     . '"><img src="images/leftarrowsmall.gif" alt="' . $prevStr . '" /></a>
            <a title="' . $nextStr . '" class="next" href="day.php?' . $u_url
     . 'date=' . $month_ahead . $caturl
     . '"><img src="images/rightarrowsmall.gif" alt="' . $nextStr . '" /></a>'
     . date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__' ), false ) . '
          </th>
        </tr>';
  } elseif ( $SCRIPT == 'minical.php' ) {
    $month_ago =
    date ( 'Ymd', mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear ) );
    $month_ahead =
    date ( 'Ymd', mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear ) );

    $ret .= '
      <thead>
        <tr class="monthnav">
          <th colspan="7">
            <a title="' . $prevStr . '" class="prev" href="minical.php?'
     . $u_url . 'date=' . $month_ago
     . '"><img src="images/leftarrowsmall.gif" alt="' . $prevStr . '" /></a>
            <a title="' . $nextStr . '" class="next" href="minical.php?'
     . $u_url . 'date=' . $month_ahead
     . '"><img src="images/rightarrowsmall.gif" alt="' . $nextStr . '" /></a>'
     . date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__' ), false ) . '
          </th>
        </tr>';
  } else // Not day or minical script. Print the month name.
    $ret .= '
      <caption><a href="' . $month_link . $u_url . 'year=' . $thisyear
     . '&amp;month=' . $thismonth . '">'
     . date_to_str ( sprintf ( "%04d%02d%02d", $thisyear, $thismonth, 1 ),
      ( $showyear != '' ? $DATE_FORMAT_MY : '__month__' ), false )
     . '</a></caption>
      <thead>';

  $ret .= '
        <tr>'
  // Print the headers to display the day of the week (Sun, Mon, Tues, etc.).
  // If we're showing week numbers we need an extra column.
  . ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' ? '
          <th class="empty">&nbsp;</th>' : '' );

  for ( $i = 0; $i < 7; $i++ ) {
    $thday = ( $i + $WEEK_START ) % 7;
    $ret .= '
          <th' . ( is_weekend ( $thday ) ? ' class="weekend"' : '' ) . '>'
     . weekday_name ( $thday, 'D' ) . '</th>';
  }
  // End the header row.
  $ret .= '
        </tr>
      </thead>
      <tbody>';
  for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthend; $i += 604800 ) {
    $tmp = $i + 172800; // 48 hours.
    $ret .= '
        <tr>' . ( $show_weeknums && $DISPLAY_WEEKNUMBER == 'Y' ? '
          <td><a class="weeknumber" ' . 'title="' . $weekStr . '&nbsp;'
       . date ( 'W', $i + 86400 ) . '" ' . 'href="week.php?' . $u_url . 'date='
       . date ( 'Ymd', $tmp ) . '">(' . date ( 'W', $tmp ) . ')</a></td>' : '' );

    for ( $j = 0; $j < 7; $j++ ) {
      // Add 12 hours just so we don't have DST problems.
      $date = $i + ( $j * 86400 + 43200 );
      $dateYmd = date ( 'Ymd', $date );
      $hasEvents = false;
      $title = '';
      $ret .= '
          <td';

      if ( $boldDays ) {
        $ev = get_entries ( $dateYmd, $get_unapproved, true, true );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
          $title = $ev[0]->getName();
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 ) {
            $hasEvents = true;
            $title = $rep[0]->getName();
          }
        }
      }
      if ( ( $dateYmd >= $monthstart && $dateYmd <= $monthend ) ||
          ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) &&
            $DISPLAY_ALL_DAYS_IN_MONTH == 'Y' ) ) {
        $class =
        // If it's a weekend.
        ( is_weekend ( $date ) ? 'weekend' : '' )
        // If the day being viewed is today AND script = day.php.
        . ( $dateYmd == $thisyear . $thismonth . $thisday && $SCRIPT == 'day.php'
          ? ' selectedday' : '' )
        // Are there any events scheduled for this date?
        . ( $hasEvents ? ' hasevents' : '' );

        $ret .= ( $class != '' ? ' class="' . $class . '"' : '' )
         . ( $dateYmd == date ( 'Ymd', $today ) ? ' id="today"' : '' )
         . '><a href="';

        if ( $SCRIPT == 'minical.php' )
          $ret .= ( $use_http_auth
            ? 'day.php?user=' . $user
            : 'nulogin.php?login=' . $user . '&amp;return_path=day.php' )
           . '&amp;date=' . $dateYmd . '"'
           . ( empty ( $MINI_TARGET ) ? '' : ' target="' . $MINI_TARGET . '"' )
           . ( empty ( $title ) ? '' : ' title="' . $title . '"' );
        else
          $ret .= 'day.php?' . $u_url . 'date=' . $dateYmd . '"';

        $ret .= '>' . date ( 'j', $date ) . '</a></td>';
      } else
        $ret .= ' class="empty' . ( ! empty ( $SHOW_EMPTY_WEEKENDS )
          && is_weekend ( $date ) ? ' weekend' : '' ) . '">&nbsp;</td>';
    } // end for $j
    $ret .= '
        </tr>';
  } // end for $i
  return $ret . '
      </tbody>
    </table>';
}

/**
 * Prints small task list for this $login user.
 */
function display_small_tasks ( $cat_id ) {
  global $caturl, $DATE_FORMAT_TASK, $eventinfo,
  $is_assistant, $login, $task_filter, $user;
  static $key = 0;

  if ( ! empty ( $user ) && $user != $login && ! $is_assistant )
    return false;

  $SORT_TASKS = 'Y';

  $pri[1] = translate ( 'High' );
  $pri[2] = translate ( 'Medium' );
  $pri[3] = translate ( 'Low' );
  $task_user = $login;
  $u_url = '';

  if ( $user != $login && ! empty ( $user ) ) {
    $u_url = 'user=' . $user . '&amp;';
    $task_user = $user;
  }
  $ajax = [];
  $dueSpacer = '&nbsp;';
  $task_cat = ( empty ( $cat_id ) ? -99 : $cat_id );

  if ( $SORT_TASKS == 'Y' ) {
    for ( $i = 0; $i < 4; $i++ ) {
      $ajax[$i] = '
        <td class="sorter" onclick="sortTasks( ' . $i . ', ' . $task_cat
       . ', this )"><img src="images/up.png" style="vertical-align:bottom" /></td>';
      $ajax[$i + 4] = '
        <td  class="sorter sorterbottom" onclick="sortTasks( ' .
      ( $i + 4 ) . ', ' . $task_cat
       . ', this )"><img src="images/down.png" style="vertical-align:top" /></td>';
    }
  } else {
    $dueSpacer = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $ajax = array_pad ( $ajax, 8, '
        <td></td>' );
  }

  $priorityStr = translate ( 'Priority' );
  $dateFormatStr = $DATE_FORMAT_TASK;
  $task_list = query_events ( $task_user, false,
    ( empty ( $task_filter ) ? '' : $task_filter ), $cat_id, true );
  $row_cnt = 1;
  $task_html = '
    <table class="minitask" cellpadding="2">
      <tr class="header">
        <th colspan="6">' . translate ( 'TASKS' ) . '</th>
        <th class="alignright" colspan="2"><a href="edit_entry.php?' . $u_url
   . 'eType=task' . $caturl
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
    $task_owner = $E->getLogin();
    if( access_is_enabled() ) {
      $can_access = access_user_calendar ( 'view', $task_owner, '',
        $E->getCalType(), $E->getAccess() );
      if ( $can_access == 0 )
        continue;
    }
    $cal_id = $E->getId();
    // Generate popup info.
    $linkid = 'pop' . "$cal_id-$key";
    $key++;
    $link = '<a href="view_entry.php?'
     . ( $task_owner != $login ? 'user=' . $task_owner . '&amp;' : '' )
     . 'id=' . $cal_id . '"';
    $task_html .= '
      <tr class="task" id="' . $linkid . '" style="background-color:'
     . rgb_luminance( $GLOBALS['BGCOLOR'], $E->getPriority() ) . '">
        <td colspan="2">' . $link . ' title="' . $priorityStr . '">'
     . $E->getPriority() . '</a></td>
        <td class="name" colspan="2" width="50%">&nbsp;' . $link . ' title="'
     . translate( 'Task Name' ) . ': ' . $E->getName() . '">'
     . substr( $E->getName(), 0, 15 )
     . ( strlen( $E->getName() ) > 15 ? '...' : '' ) . '</a></td>
        <td colspan="2">' . $link . ' title="' . translate ( 'Task Due Date' )
     . '">'
     . date_to_str( $E->getDueDate(), $dateFormatStr, false, false ) . '</a>'
     . '</td>
        <td class="pct" colspan="2">' . $link . ' title="% '
     . translate( 'Completed' ) . '">' . $E->getPercent() . '</a></td>
      </tr>';
    $row_cnt++;
    // Build special string to pass to popup.
    // TODO: Move this logic into build_entry_popup().
    $eventinfo .= build_entry_popup( 'eventinfo-' . $linkid, $E->getLogin(),
      $E->getDescription(), translate( 'Due Time' ) . ':'
       . display_time( '', 0, $E->getDueDateTimeTS() ) . '</dd><dd>'
       . translate ( 'Due Date' ) . ':'
       . date_to_str( $E->getDueDate(), '', false )
       . "</dd>\n<dt>" . $priorityStr . ":</dt>\n<dd>" . $E->getPriority()
       . '-' . $pri[ceil( $E->getPriority() / 3 )] . "</dd>\n<dt>"
       . translate( 'Percent Complete' ) . ":</dt>\n<dd>" . $E->getPercent()
       . '%', '', $E->getLocation(), $E->getName(), $cal_id );
  }
  for ( $i = 7; $i > $row_cnt; $i-- ) {
    $task_html .= '<tr><td colspan="8" class="filler">&nbsp;</td></tr>' . "\n";
  }
  $task_html .= "</table>\n";
  return $task_html;
}

/**
 * Displays a time in either 12 or 24 hour format.
 *
 * @param string $time       Input time in HHMMSS format
 *                           Optionally, the format can be YYYYMMDDHHMMSS
 * @param int   $control     bitwise command value
 *   0 default
 *   1 ignore_offset Do not use the timezone offset
 *   2 show_tzid Show abbrev TZ id ie EST after time
 *   4 use server's timezone
 * @param int    $timestamp  optional input time in timestamp format
 * @param string $format     user's TIME_FORMAT when sending emails
 *
 * @return string  The time in the user's timezone and preferred format.
 */
function display_time ( $time = '', $control = 0, $timestamp = '',
  $format = '' ) {
  global $SERVER_TIMEZONE, $TIME_FORMAT;

  if ( $control & 4 ) {
    $currentTZ = getenv ( 'TZ' );
    set_env ( 'TZ', $SERVER_TIMEZONE );
  }
  $t_format = ( empty ( $format ) ? $TIME_FORMAT : $format );
  $tzid = date ( ' T' ); //Default tzid for today.

  if ( ! empty ( $time ) && strlen ( $time ) > 12 )
    $timestamp = date_to_epoch ( $time );

  if ( ! empty ( $timestamp ) ) {
    $time = date ( 'His', $timestamp );
    $tzid = date ( ' T', $timestamp );
    // $control & 1 = do not do timezone calculations
    if ( $control & 1 ) {
      $time = gmdate ( 'His', $timestamp );
      $tzid = ' GMT';
    }
  }
  $hour = intval ( $time / 10000 );
  $min = abs ( ( $time / 100 ) % 100 );

  // Prevent goofy times like 8:00 9:30 9:00 10:30 10:00.
  if ( $time < 0 && $min > 0 )
    $hour--;
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
  } else {
    //$ret = sprintf ( "%02d&#58;%02d", $hour, $min );
    $ret = sprintf ( "%02d:%02d", $hour, $min );
  }

  if ( $control & 2 )
    $ret .= $tzid;

  // Reset timezone to previous value.
  if ( ! empty ( $currentTZ ) )
    set_env ( 'TZ', $currentTZ );

  return $ret;
}

/**
 * Checks for any unnaproved events.
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
  global $is_admin, $is_nonuser, $login, $MENU_ENABLED,
  $NONUSER_ENABLED, $PUBLIC_ACCESS;
  static $retval;

  // Don't do this for public access login,
  // admin user must approve public events if UAC is not enabled.
  if ( $user == '__public__' || $is_nonuser )
    return;

  // Don't run this more than once.
  if ( ! empty ( $retval[$user] ) )
    return $retval[$user];

  $app_user_hash = $app_users = $query_params = [];
  $query_params[] = $user;
  $ret = '';
  $sql = 'SELECT COUNT( weu.cal_id ) FROM webcal_entry_user weu, webcal_entry we
    WHERE weu.cal_id = we.cal_id AND weu.cal_status = \'W\'
    AND ( weu.cal_login = ?'
   . ( $PUBLIC_ACCESS == 'Y' && $is_admin && ! access_is_enabled()
    ? ' OR weu.cal_login = \'__public__\'' : '' );

  if( access_is_enabled() ) {
    $app_user_hash[$login] = 1;
    $app_users[] = $login;

    $all = ( $NONUSER_ENABLED == 'Y'
      // TODO:  Add 'approved' switch to these functions.
      ? array_merge( get_my_users(), get_my_nonusers() ) : get_my_users() );

    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( access_user_calendar ( 'approve', $x ) &&
          empty ( $app_user_hash[$x] ) ) {
        $app_user_hash[$x] = 1;
        $app_users[] = $x;
      }
    }
    for ( $i = 0, $cnt = count ( $app_users ); $i < $cnt; $i++ ) {
      $query_params[] = $app_users[$i];
      $sql .= ' OR weu.cal_login = ? ';
    }
  } else
  if ( $NONUSER_ENABLED == 'Y' ) {
    $admincals = get_my_nonusers ( $login );
    for ( $i = 0, $cnt = count ( $admincals ); $i < $cnt; $i++ ) {
      $query_params[] = $admincals[$i]['cal_login'];
      $sql .= ' OR weu.cal_login = ? ';
    }
  }
  $rows = dbi_get_cached_rows ( $sql . ' )', $query_params );
  if ( $rows ) {
    $row = $rows[0];
    if ( $row && $row[0] > 0 )
      $ret .= ( $MENU_ENABLED == 'N'
        ? '<a class="nav" href="list_unapproved.php'
         . ( $user != $login ? '?user=' . $user . '"' : '' )
         . '">' . str_replace ( 'XXX', $row[0],
          translate ( 'You have XXX unapproved entries' ) ) . "</a><br />\n"
        : // Return something that won't display in bottom menu
        // but still has strlen > 0.
        '<!--NOP-->' );
  }

  $retval[$user] = $ret;

  return $ret;
}

/**
 * Sends a redirect to the specified page.
 * The database connection is closed and execution terminates in this function.
 *
 * <b>Note:</b>  MS IIS/PWS has a bug that does not allow sending a cookie and a
 * redirect in the same HTTP header. When we detect that the web server is IIS,
 * we accomplish the redirect using meta-refresh.
 * See the following for more info on the IIS bug:
 * {@link http://www.faqts.com/knowledge_base/view.phtml/aid/9316/fid/4}
 *
 * @param string $url  The page to redirect to. In theory, this should be an
 *                     absolute URL, but all browsers accept relative URLs
 *                     (like "month.php").
 *
 * @global string    Type of webserver
 * @global array     Server variables
 * @global resource  Database connection
 */
function do_redirect ( $url ) {
  global $_SERVER, $c, $SERVER_SOFTWARE, $SERVER_URL;

  // Replace any '&amp;' with '&' since we don't want that in the HTTP header.
  $url = str_replace ( '&amp;', '&', $url );

  if ( empty ( $SERVER_SOFTWARE ) )
    $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

  // As of RFC 7231, Location redirects can be relative URLs.
  // See: https://tools.ietf.org/html/rfc7231#section-7.1.2

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

/**
 * Takes an input string and encode it into a slightly encoded hexval that we
 * can use as a session cookie.
 *
 * @param string $instr  Text to encode
 *
 * @return string  The encoded text.
 *
 * @global array Array of offsets
 *
 * @see decode_string
 */
function encode_string ( $instr ) {
  global $offsets;

  $cntOffsets = count ( $offsets );
  $ret = '';
  for ( $i = 0, $cnt = strlen ( $instr ); $i < $cnt; $i++ ) {
    $ret .= bin2hex ( chr ( ( ord ( substr ( $instr, $i, 1 ) ) + $offsets[ $i %
      $cntOffsets ] ) % 256 ) );
  }
  return $ret;
}

/**
 * Check for errors and return required HTML for display
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
    print_header ( '', '', '', true );
    $ret .= '
    <h2>' . print_error ( $error ) . '</h2>';
  } else {
    if ( $redirect )
      do_redirect ( $nextURL );

    $ret .= '<html>
  <head></head>
  <body onload="alert( \'' . translate ( 'Changes successfully saved', true )
     . '\' ); window.parent.location.href=\'' . $nextURL . '\';">';
  }
  return $ret . '
  </body>
</html>';
}

/**
 * Gets the list of external users for an event from the
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
    [$event_id] );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];

      // Remove [\d] if duplicate name.
      $ret .= trim ( preg_replace ( '/\[[\d]]/', '', $row[0] ) );
      if ( strlen ( $row[1] ) ) {
        $row_one = htmlentities ( " <$row[1]>" );
        $ret .= ( $use_mailto
          ? ' <a href="mailto:' . "$row[1]\">$row_one</a>" : $row_one );
      }
      $ret .= "\n";
    }
  }
  return $ret;
}

/**
 * Fakes an email for testing purposes.
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

/**
 * Generate activity log
 *
 *  @paran  int   $id       Event id if called from view_entry.php
 *  @param  bool  $sys      Display System Log ro Event Log
 *  @param  int   $startid  Event number to start off list
 *
 *  @return string  HTML to diplay log.
 */
function generate_activity_log ( $id = '', $sys = false, $startid = '' ) {
  global $GENERAL_USE_GMT, $nextpage, $PAGE_SIZE;

  $nextpage = '';
  $size = ( $id ? 'h3' : 'h2' );
  $sql_params = [];
  if ( ! empty ( $id ) )
    $sql_params[] = $id;

  $sql_params[] = $startid;
  $ret = "<$size>"
   . ( $sys ? translate ( 'System Log' ) : translate ( 'Activity Log' ) )
   . ( $sys ? '' : ' &nbsp;<a href="rss_activity_log.php">'
   . '<img src="images/rss.png" width="14" height="14" alt="RSS 2.0 - '
   . translate( 'Activity Log' ) . '" /></a>' )
   . "</$size>" . display_admin_link() . '
    <table class="embactlog">
      <tr>
        <th class="usr">' . translate ( 'User' ) . '</th>
        <th class="cal">' . translate ( 'Calendar' ) . '</th>
        <th class="scheduled">' . translate ( 'Date' ) . '/'
   . translate ( 'Time' ) . '</th>' . ( $sys || $id ? '' : '
        <th class="dsc">' . translate ( 'Event' ) . '</th>' ) . '
        <th class="action">' . translate ( 'Action' ) . '</th>
      </tr>';

  $sql = 'SELECT wel.cal_login, wel.cal_user_cal, wel.cal_type, wel.cal_date,
    wel.cal_time, wel.cal_text, '
   . ( $sys
    ? 'wel.cal_log_id FROM webcal_entry_log wel WHERE wel.cal_entry_id = 0'
    : 'we.cal_id, we.cal_name, wel.cal_log_id, we.cal_type
      FROM webcal_entry_log wel, webcal_entry we
      WHERE wel.cal_entry_id = we.cal_id' )
   . ( empty ( $id ) ? '' : ' AND we.cal_id = ?' )
   . ( empty ( $startid ) ? '' : ' AND wel.cal_log_id <= ?' )
   . ' ORDER BY wel.cal_log_id DESC';

  $res = dbi_execute ( $sql, $sql_params );

  if ( $res ) {
    $num = 0;
    while ( $row = dbi_fetch_row ( $res ) ) {
      $l_login = $row[0];
      $l_user = $row[1];
      $l_type = $row[2];
      $l_date = $row[3];
      $l_time = $row[4];
      $l_text = $row[5];

      if ( $sys )
        $l_id = $row[6];
      else {
        $l_eid = $row[6];
        $l_ename = $row[7];
        $l_id = $row[8];
        $l_etype = $row[9];
      }
      $num++;
      if ( $num > $PAGE_SIZE ) {
        $nextpage = $l_id;
        break;
      } else
        $ret .= '
      <tr' . ( $num % 2 ? ' class="odd"' : '' ) . '>
        <td>' . $l_login . '</td>
        <td>' . $l_user . '</td>
        <td>' . date_to_str ( $l_date ) . '&nbsp;'
         . display_time ( $l_date . $l_time,
          // Added TZ conversion
          ( ! empty ( $GENERAL_USE_GMT ) && $GENERAL_USE_GMT == 'Y' ? 3 : 2 ) )
         . '</td>
        <td>' . ( ! $sys && ! $id ? '<a title="' . htmlspecialchars ( $l_ename )
           . '" href="view_entry.php?id=' . $l_eid . '">'
           . htmlspecialchars ( $l_ename ) . '</a></td>
        <td>' : '' ) . display_activity_log ( $l_type, $l_text ) . '</td>
      </tr>';
    }
    dbi_free_result ( $res );
  }

  return $ret . '
    </table>';
}

/**
 * Generate Application Name
 *
 * @param bool $custom  Allow user name to be displayed
 */
function generate_application_name ( $custom = true ) {
  global $APPLICATION_NAME, $fullname;

  if ( empty ( $APPLICATION_NAME ) )
    $APPLICATION_NAME = 'Title';

  return ( $custom && ! empty ( $fullname ) && $APPLICATION_NAME == 'myname'
    ? $fullname
    : ( $APPLICATION_NAME == 'Title' || $APPLICATION_NAME == 'myname'
      ? ( function_exists ( 'translate' ) ? translate ( 'Title' ) : 'Title' )
      : htmlspecialchars ( $APPLICATION_NAME ) ) );
}

/**
 * Generate HTML to add Printer Friendly Link.
 * If called without parameter, return only the href string.
 *
 * @param string $hrefin  script name
 *
 * @return string  URL to printer friendly page.
 *
 * @global array SERVER
 * @global string SCRIPT name
 * @global string (Y/N) Top menu enabled
 */
function generate_printer_friendly ( $hrefin = '' ) {
  global $_SERVER, $MENU_ENABLED, $SCRIPT, $show_printer;

  // Set this to enable printer icon in top menu.
  $href = ( empty ( $href ) ? $SCRIPT : $hrefin ) . '?'
   . ( empty ( $_SERVER['QUERY_STRING'] ) ? '' : addslashes(htmlentities($_SERVER['QUERY_STRING'])) );
  $href .= ( substr ( $href, -1 ) == '?' ? '' : '&' ) . 'friendly=1';
  $show_printer = true;
  $href = str_replace ( '&amp;', '&', $href );
  if ( empty ( $hrefin ) ) // Menu will call this function without parameter.
    return $href;

  if ( $MENU_ENABLED == 'Y' ) // Return nothing if using menus.
    return '';

  $href = str_replace ( '&', '&amp;', $href );
  $displayStr = translate ( 'Printer Friendly' );
  $statusStr = translate ( 'Generate printer-friendly version' );

  return <<<EOT
    <a title="{$statusStr}" class="printer" href="{$href}"
      target="cal_printer_friendly">[{$displayStr}]</a>
EOT;
}

/**
 * Generate Refresh Meta Tag.
 *
 * @return  HTML for Meta Tag.
 */
function generate_refresh_meta() {
  global $AUTO_REFRESH, $AUTO_REFRESH_TIME, $REQUEST_URI;

  return ( $AUTO_REFRESH == 'Y' && ! empty ( $AUTO_REFRESH_TIME ) && !
    empty ( $REQUEST_URI )
    ? '
    <meta http-equiv="refresh" content="'
     . $AUTO_REFRESH_TIME * 60 // Convert to seconds.
     . '; url=' . addslashes(htmlentities($REQUEST_URI)) . '" />' : '' );
}

/**
 * Returns all the dates a specific event will fall on
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
  global $byday_names, $byday_values, $CONFLICT_REPEAT_MONTHS;

  $dateYmd = date ( 'Ymd', $date );
  $hour = date ( 'H', $date );
  $minute = date ( 'i', $date );

  if ( $Until == null && $Count == 999 ) {
    // Check for $CONFLICT_REPEAT_MONTHS months into future for conflicts.
    $thisyear = substr ( $dateYmd, 0, 4 );
    $thismonth = substr ( $dateYmd, 4, 2 ) + $CONFLICT_REPEAT_MONTHS;
    $thisday = substr ( $dateYmd, 6, 2 );
    if ( $thismonth > 12 ) {
      $thisyear++;
      $thismonth -= 12;
    }
    $realend = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
  } else
    $realend = ( $Count != 999
      ? mktime ( 0, 0, 0, 1, 1, 2038 ) // Set $until to some ridiculous value.
      : $Until );

  $ret = [];
  $date_excluded = false; //Flag to track ical results.
  // Do iterative checking here.
  // I floored the $realend so I check it against the floored date.
  if ( $rpt_type && ( floor ( $date / 86400 ) * 86400 ) < $realend ) {
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
          $cdate = add_dstfree_time ( $cdate, 86400, $interval );
        }
      } while ( $cdate <= $realend && $n < $Count ) {
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

        $cdate = add_dstfree_time ( $cdate, 86400, $interval );
        $date_excluded = false;
      }
    } elseif ( $rpt_type == 'weekly' ) {
      $r = 0;
      $dow = date ( 'w', $date );
      $cdate = $date - ( $dow * 86400 );
      if ( ! empty ( $jump ) && $Count == 999 ) {
        while ( ($cdate+604800) < $jump ) {
          $cdate = add_dstfree_time ( $cdate, 604800, $interval );
        }
      }

      while ( $cdate <= $realend && $n < $Count ) {
        if ( ! empty ( $byday ) ) {
          $WkstDay = $byday_values[$Wkst];
          for ( $i=$WkstDay; $i<=( $WkstDay + 6 ); $i++ ) {
            $td = $cdate + ( $i * 86400 );
            $tdDay = date ( 'w', $td );
            //echo $Count . '  ' . $n . '  ' .$WkstDay .'<br />';
            if ( in_array( $byday_names[$tdDay], $byday ) && $td >= $date
                && $td <= $realend && $n < $Count )
              $ret[$n++] = $td;
          }
        } else {
          $td = $cdate + ( $dow * 86400 );
          $cdow = date ( 'w', $td );
          if ( $cdow == $dow )
            $ret[$n++] = $td;
        }
        // Skip to the next week in question.
        $cdate = add_dstfree_time ( $cdate, 604800, $interval );
      }
    } elseif ( substr ( $rpt_type, 0, 7 ) == 'monthly' ) {
      $thisyear = substr ( $dateYmd, 0, 4 );
      $thismonth = substr ( $dateYmd, 4, 2 );
      $thisday = substr ( $dateYmd, 6, 2 );
      $hour = date ( 'H', $date );
      $minute = date ( 'i', $date );
      // Skip to this year if called from query_events and we don't need count.
      if ( ! empty ( $jump ) && $Count == 999 ) {
        while ( $cdate < $jump ) {
          $thismonth += $interval;
          $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
        }
      }
      $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
      $mdate = $cdate;
      while ( $cdate <= $realend && $n < $Count ) {
          $bydayvalues = $bymonthdayvalues = $yret = [];
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
        $thismonth += $interval;
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
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
      $thisday = substr ( $dateYmd, 6, 2 );
      // Skip to this year if called from query_events and we don't need count.
      if ( ! empty ( $jump ) && $Count == 999 ) {
        $jumpY = date ( 'Y', $jump );
        while ( date ( 'Y', $cdate ) < $jumpY ) {
          $thisyear += $interval;
          $cdate = mktime ( $hour, $minute, 0, 1, 1, $thisyear );
        }
      }
      $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
      while ( $cdate <= $realend && $n < $Count ) {
        $yret = [];
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
            $bydayvalues = $bymonthdayvalues = [];
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
                  : [mktime ( $hour, $minute, 0, $month, $thisday, $ycd )] ) );
          } //end foreach bymonth
        } elseif ( isset ( $byyearday ) ) { // end if isset bymonth
          foreach ( $byyearday as $yearday ) {
            preg_match( '/([-+]?)(\d{1,3})/', $yearday, $match );
            if ( $match[1] == '-' && ( $cdate >= $date ) )
              $yret[] =
              mktime ( $hour, $minute, 0, 12, 31 - $match[2] - 1, $thisyear );
            else
            if ( ( $n < $Count ) && ( $cdate >= $date ) )
              $yret[] = mktime ( $hour, $minute, 0, 1, $match[2], $thisyear );
          }
        } elseif ( isset ( $byweekno ) ) {
          $wkst_date = ( $Wkst == 'SU' ? $cdate + 86400 : $cdate );
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
        $cdate = mktime ( $hour, $minute, 0, $thismonth, $thisday, $thisyear );
      }
    } //end if rpt_type
  }
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

/**
 * Get the dates the correspond to the byday values.
 *
 * @param array $byday   ByDay values to process (MO,TU,-1MO,20MO...)
 * @param string $cdate  First day of target search (Unix timestamp)
 * @param string $type   Month, Year, Week (default = month)
 * @param string $date   First day of event (Unix timestamp)
 *
 * @return array  Dates that match ByDay (YYYYMMDD format).
 */
function get_byday ( $byday, $cdate, $type = 'month', $date ) {
  global $byday_values;

  if ( empty ( $byday ) )
    return;

  $ret = [];
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

    // It is possible to have spurious offset days within a 'daily' repetition,
    //   by setting them while in month/year repetition type, then changing
    //   type to 'daily'.
    // These situations will lead in a crash without the following test.
    if (is_numeric($dayOffset) && !isset($ditype))
      continue;

    $dowOffset = ( ( -1 * $byday_values[$dayTxt] ) + 7 ) % 7; //SU=0, MO=6, TU=5...
    if ( is_numeric ( $dayOffset ) && $dayOffset > 0 ) {
      // Offset from beginning of $type.
      $dayOffsetDays = ( ( $dayOffset - 1 ) * 7 ); //1 = 0, 2 = 7, 3 = 14...
      $forwardOffset = $byday_values[$dayTxt] - $fdow;
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
        if ( ( date ( 'w', $cdate ) == $byday_values[$dayTxt] ) && $cdate > $date )
          $ret[] = $cdate;
      } else {
        for ( $i = 1; $i <= $ditype; $i++ ) {
          $loopdate = mktime ( $hour, $minute, 0, $month, $i, $yr );
          if ( ( date ( 'w', $loopdate ) == $byday_values[$dayTxt] ) &&
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

/**
 * Get the dates the correspond to the bymonthday values.
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

  $ret = [];
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

/**
 * Get categories for a given event id
 * Global categories are changed to negative numbers
 *
 * @param int      $id  Id of event
 * @param string   $user normally this is $login
 * @param bool     $asterisk Include '*' if Global
 *
 * @return array   Array containing category names.
 */
function get_categories_by_id ( $id, $user, $asterisk = false ) {
  global $login;

  if ( empty ( $id ) )
    return false;

  $categories = [];

  $res = dbi_execute ( 'SELECT wc.cat_name, wc.cat_id, wec.cat_owner
    FROM webcal_categories wc, webcal_entry_categories wec WHERE wec.cal_id = ?
    AND wec.cat_id = wc.cat_id AND ( wc.cat_owner = ? OR wc.cat_owner IS NULL )
  ORDER BY wec.cat_order', [$id, ( empty ( $user ) ? $login : $user )] );
  while ( $row = dbi_fetch_row ( $res ) ) {
    $categories[ ( empty ( $row[2] ) ? - $row[1] : $row[1] ) ] = $row[0]
     . ( $asterisk && empty ( $row[2] ) ? '*' : '' );
  }
  dbi_free_result ( $res );

  return $categories;
}

/**
 * Gets all the events for a specific date.
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
  $ret = [];
  for ( $i = 0, $cnt = count ( $events ); $i < $cnt; $i++ ) {
    $event_date = $events[$i]->getDateTimeAdjusted();
    if( ! $get_unapproved && $events[$i]->getStatus() == 'W' )
      continue;

    if( $events[$i]->isAllDay() || $events[$i]->isUntimed() ) {
      if( $events[$i]->getDate() == $date )
        $ret[] = $events[$i];
    } else {
      if ( $event_date == $date )
        $ret[] = $events[$i];
    }
  }
  return $ret;
}

/**
 * Gets all the groups a user is authorized to see
 *
 *
 * @param string $user        Subject User
 *
 *
 * @return array  Array of Groups.
 */
function get_groups ( $user ) {
  global $GROUPS_ENABLED, $USER_SEES_ONLY_HIS_GROUPS,
  $is_nonuser_admin, $is_assistant, $login;

  if ( empty( $GROUPS_ENABLED ) || $GROUPS_ENABLED != 'Y' )
    return false;

  $owner = ( $is_nonuser_admin || $is_assistant ? $user : $login );

  // Load list of groups.
  $sql = 'SELECT wg.cal_group_id, wg.cal_name FROM webcal_group wg';
 $sql_params = [];
 if ( $USER_SEES_ONLY_HIS_GROUPS == 'Y' ) {
   $sql .= ', webcal_group_user wgu WHERE wg.cal_group_id = wgu.cal_group_id
     AND wgu.cal_login = ?';
    $sql_params[] = $owner;
 }

  $res = dbi_execute ( $sql . ' ORDER BY wg.cal_name', $sql_params );

  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
     $groups[] = [
        'cal_group_id' => $row[0],
       'cal_name' => $row[1]];
    }
   dbi_free_result ( $res );
 }
 return $groups;
}

/**
 * Gets the last page stored using {@link remember_this_view()}.
 *
 * @return string The URL of the last view or an empty string if it cannot be
 *                determined.
 *
 * @global array  Cookies
 */
function get_last_view ( $clear=true ) {
  $val = ( isset ( $_COOKIE['webcalendar_last_view'] )
    ? str_replace ( '&', '&amp;', $_COOKIE['webcalendar_last_view'] ) : '' );

  if ( $clear )
    SetCookie ( 'webcalendar_last_view', '', 0 );

  return $val;
}

/**
 * Gets a list of nonusers.
 *
 * If groups are enabled, this will restrict the list of nonusers to only those
 * that are in the same group(s) as the user (unless the user is an admin) or
 * the nonuser is a public calendar. We allow admin users to see all users
 * because they can also edit someone else's events (so they may need access to
 * users who are not in the same groups).
 *
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each nonuser's calendar. If not, then that nonuser
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
  global $GROUPS_ENABLED, $is_admin, $is_nonuser, $is_nonuser_admin, $login,
  $my_nonuser_array, $my_user_array, $PUBLIC_ACCESS, $PUBLIC_ACCESS_FULLNAME,
  $USER_SEES_ONLY_HIS_GROUPS, $USER_SORT_ORDER;

  $this_user = ( empty ( $user ) ? $login : $user );
  // Return the global variable (cached).
  if ( ! empty ( $my_nonuser_array[$this_user . $add_public] ) &&
      is_array ( $my_nonuser_array ) )
    return $my_nonuser_array[$this_user . $add_public];

  $u = get_nonuser_cals();
  if ( $GROUPS_ENABLED == 'Y' && $USER_SEES_ONLY_HIS_GROUPS == 'Y' && ! $is_admin ) {
    // Get current user's groups.
    $rows = dbi_get_cached_rows ( 'SELECT cal_group_id FROM webcal_group_user
  WHERE cal_login = ?', [$this_user] );
    $groups = $ret = $u_byname = [];
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $groups[] = $row[0];
      }
    }
    $groupcnt = count ( $groups );
    // Nonuser (public) can only see themself (unless access control is on).
    if( $is_nonuser && ! access_is_enabled() )
      return [$this_user];

    for ( $i = 0, $cnt = count ( $u ); $i < $cnt; $i++ ) {
      $u_byname[$u[$i]['cal_login']] = $u[$i];
    }

    if ( $groupcnt == 0 ) {
      // Eek. User is in no groups... Return only themselves.
      if ( isset ( $u_byname[$this_user] ) )
        $ret[] = $u_byname[$this_user];

      $my_nonuser_array[$this_user . $add_public] = $ret;
      return $ret;
    }
    // Get other members of current users' groups.
    $sql = 'SELECT DISTINCT( wnc.cal_login ), cal_lastname, cal_firstname,
      cal_is_public FROM webcal_group_user wgu, webcal_nonuser_cals wnc WHERE '
     . ( $add_public ? 'wnc.cal_is_public = \'Y\'  OR ' : '' )
     . ' cal_admin = ?
    OR ( wgu.cal_login = wnc.cal_login
      AND cal_group_id ' .
      ( $groupcnt == 1 ? '= ?' : 'IN ( ?' . str_repeat ( ',?', $groupcnt - 1 ) . ' )' );

    // Add $this_user to beginning of query params.
    array_unshift ( $groups, $this_user );
    $rows = dbi_get_cached_rows ( $sql . ' )
  ORDER BY '
       . ( empty ( $USER_SORT_ORDER ) ? '' : "$USER_SORT_ORDER" ), $groups );
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

  // We add Public Access if $add_public= true.
  // Admin already sees all users.
  if ( ! $is_admin && $add_public && $PUBLIC_ACCESS == 'Y' ) {
    $pa = user_get_users ( true );
    array_unshift ( $ret, $pa[0] );
  }
  // If user access control enabled,
  // remove any nonusers that this user does not have required access.
  if( access_is_enabled() ) {
    $newlist = [];
    for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
      $can_list = access_user_calendar ( $reason, $ret[$i]['cal_login'], $this_user );
      if ( $can_list == 'Y' || $can_list > 0 )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }
  $my_nonuser_array[$this_user . $add_public] = $ret;
  return $ret;
}

/**
 * Gets a list of users.
 *
 * If groups are enabled, this will restrict the list to only those users who
 * are in the same group(s) as this user (unless the user is an admin). We allow
 * admin users to see all users because they can also edit someone else's events
 * (so they may need access to users who are not in the same groups).
 *
 * If user access control is enabled, then we also check to see if this
 * user is allowed to view each user's calendar. If not, then that user
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
function get_my_users ( $user = '', $reason = 'invite' ) {
  global $GROUPS_ENABLED, $is_admin, $is_nonuser, $is_nonuser_admin, $login,
  $my_user_array, $USER_SEES_ONLY_HIS_GROUPS, $USER_SORT_ORDER;

  $this_user = ( empty ( $user ) ? $login : $user );
  // Return the global variable (cached).
  if ( ! empty ( $my_user_array[$this_user][$reason] ) &&
      is_array ( $my_user_array ) )
    return $my_user_array[$this_user][$reason];

  if ( $GROUPS_ENABLED == 'Y' && $USER_SEES_ONLY_HIS_GROUPS == 'Y' && ! $is_admin ) {
    // Get groups with current user as member.
    $rows = dbi_get_cached_rows ( 'SELECT cal_group_id FROM webcal_group_user
  WHERE cal_login = ?', [$this_user] );
    $groups = $ret = $u_byname = [];
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $groups[] = $row[0];
      }
    }
    $groupcnt = count ( $groups );
    // Nonuser (public) can only see themself (unless access control is on).
    if( $is_nonuser && ! access_is_enabled() )
      return [$this_user];

    $u = user_get_users();
    if ( $is_nonuser_admin )
      $u = array_merge( get_my_nonusers(), $u );

    for ( $i = 0, $cnt = count ( $u ); $i < $cnt; $i++ ) {
      $u_byname[$u[$i]['cal_login']] = $u[$i];
    }

    if ( $groupcnt == 0 ) {
      // Eek. User is in no groups... Return only themselves.
      if ( isset ( $u_byname[$this_user] ) )
        $ret[] = $u_byname[$this_user];

      $my_user_array[$this_user][$reason] = $ret;
      return $ret;
    }
    // Get other members of users' groups.
    $sql = 'SELECT DISTINCT(webcal_group_user.cal_login), cal_lastname,
      cal_firstname FROM webcal_group_user LEFT JOIN webcal_user
    ON webcal_group_user.cal_login = webcal_user.cal_login
  WHERE cal_group_id ' .
      ( $groupcnt == 1 ? '= ?' : 'IN ( ?' . str_repeat ( ',?', $groupcnt - 1 ) . ' )' );

    $rows = dbi_get_cached_rows ( $sql . ' ORDER BY '
       . ( empty ( $USER_SORT_ORDER ) ? '' : "$USER_SORT_ORDER, " )
       . 'webcal_group_user.cal_login', $groups );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        if ( isset ( $u_byname[$row[0]] ) )
          $ret[] = $u_byname[$row[0]];
      }
    }
  } else
    // Groups not enabled... return all users.
    $ret = user_get_users();

  // If user access control enabled,
  // remove any users that this user does not have required access.
  if( access_is_enabled() ) {
    $newlist = [];
    for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
      $can_list = access_user_calendar ( $reason, $ret[$i]['cal_login'], $this_user );
      if ( $can_list == 'Y' || $can_list > 0 )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }
  $my_user_array[$this_user][$reason] = $ret;
  return $ret;
}

/**
 * Gets a list of nonuser calendars and return info in an array.
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
  global $is_admin, $USER_SORT_ORDER;
  $count = 0;
  $query_params = $ret = [];
  $sql = 'SELECT cal_login, cal_lastname, cal_firstname, cal_admin,
    cal_is_public, cal_url FROM webcal_nonuser_cals WHERE cal_url IS '
   . ( $remote == false ? '' : 'NOT ' ) . 'NULL ';

  if ( $user != '' ) {
    $sql .= 'AND  cal_admin = ? ';
    $query_params[] = $user;
  }

  $rows = dbi_get_cached_rows ( $sql . 'ORDER BY '
     . ( empty ( $USER_SORT_ORDER ) ? '' : "$USER_SORT_ORDER, " ) . 'cal_login',
    $query_params );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];

      $ret[$count++] = [
        'cal_login' => $row[0],
        'cal_lastname' => $row[1],
        'cal_firstname' => $row[2],
        'cal_admin' => $row[3],
        'cal_is_public' => $row[4],
        'cal_url' => $row[5],
        'cal_fullname' => ( strlen ( $row[1] . $row[2] )
          ? "$row[2] $row[1]" : $row[0] )];
    }
  }
  // If user access control enabled,
  // remove any users that this user does not have 'view' access to.
  if( access_is_enabled() && ! $is_admin ) {
    $newlist = [];
    for ( $i = 0, $cnt = count ( $ret ); $i < $cnt; $i++ ) {
      if ( access_user_calendar ( 'view', $ret[$i]['cal_login'] ) )
        $newlist[] = $ret[$i];
    }
    $ret = $newlist;
  }
  return $ret;
}

/**
 * Gets the list of active plugins.
 *
 * Should be called after
 * {@link load_global_settings()} and {@link load_user_preferences()}.
 *
 * @internal cek: Ignored since I am not sure this will ever be used...
 *
 * @return array Active plugins
 *
 * @ignore
 */
function get_plugin_list ( $include_disabled = false ) {
  global $error;
  // First get list of available plugins.
  $res = dbi_execute ( 'SELECT cal_setting FROM webcal_config
    WHERE cal_setting LIKE \'%.plugin_status\' '
     . ( ! $include_disabled ? 'AND cal_value = \'Y\' ' : '' )
     . 'ORDER BY cal_setting' );
  $plugins = [];
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $e = explode ( '.', $row[0] );
      if ( $e[0] != '' )
        $plugins[] = $e[0];
    }
    dbi_free_result ( $res );
  } else
    $error = db_error ( true );

  if ( count ( $plugins ) == 0 )
    $plugins[] = 'webcalendar';

  return $plugins;
}

/**
 * Gets a preference setting for the specified user.
 *
 * If no value is found in the database,
 * then the system default setting will be returned.
 *
 * @param string $user     User login we are getting preference for
 * @param string $setting  Name of the setting
 * @param stirng $defaultSetting    Value to return if no value foun
 *            in the database
 *
 * @return string  The value found in the webcal_user_pref table for the
 *                 specified setting or the sytem default if no user settings
 *                 was found.
 */
function get_pref_setting ( $user, $setting, $defaultValue='' ) {
  $ret = $defaultValue;
  // Set default.
  if ( ! isset ( $GLOBALS['sys_' . $setting] ) ) {
    // This could happen if the current user has not saved any prefs yet.
    if ( ! empty ( $GLOBALS[$setting] ) ) {
      $ret = $GLOBALS[$setting];
    }
  } else {
    if ( isset ( $GLOBALS['sys_' . $setting] ) )
      $ret = $GLOBALS['sys_' . $setting];
  }

  $rows = dbi_get_cached_rows ( 'SELECT cal_value FROM webcal_user_pref
  WHERE cal_login = ?
    AND cal_setting = ?', [$user, $setting] );
  if ( $rows ) {
    $row = $rows[0];
    if ( $row && ! empty ( $row[0] ) )
      $ret = $row[0];
  }
  return $ret;
}

/**
 * Gets user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.
 * This is loaded from the user preferences (or system settings
 * if there are no user prefererences.)
 *
 * @param string $indate  Date to pass to preferred view in YYYYMMDD format
 * @param string $args    Arguments to include in the URL (such as "user=joe")
 *
 * @return string  URL of the user's preferred view.
 */
function get_preferred_view ( $indate = '', $args = '' ) {
  global $ALLOW_VIEW_OTHER, $is_admin, $STARTVIEW, $thisdate, $views;

  // We want user's to set  their pref on first login.
  if ( empty ( $STARTVIEW ) )
    return false;

  $url = $STARTVIEW;
  // We used to just store "month" in $STARTVIEW without the ".php".
  // This is just to prevent users from getting a "404 not found"
  // if they have not updated their preferences.
  $url .= ( ! strpos ( $STARTVIEW, '.php' ) ? '.php' : '' );

  // Prevent endless looping
  // if preferred view is custom and viewing others is not allowed.
  if ( substr ( $url, 0, 5 ) == 'view_' && $ALLOW_VIEW_OTHER == 'N' && !
      $is_admin )
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
    // the standard day/month/week/year pages. All that's left is either
    // a custom view that was created by them, or a global view.
    if ( count ( $views ) > 0 )
      $url = $views[0]['url'];
  }

  $url = str_replace ( '&amp;', '&', $url );
  $url = str_replace ( '&', '&amp;', $url );

  $xdate = ( empty ( $indate ) ? $thisdate : $indate );

  $url .= ( empty ( $xdate ) ? '' : ( strstr ( $url, '?' ) ? '&amp;' : '?' )
     . 'date=' . $xdate );
  $url .= ( empty ( $args ) ? '' : ( strstr ( $url, '?' ) ? '&amp;' : '?' )
     . $args );

  return $url;
}

/**
 * Gets all the repeating events for the specified date.
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
  $ret = [];
  for ( $i = 0, $cnt = count ( $repeated_events ); $i < $cnt; $i++ ) {
    if( ( $repeated_events[$i]->getStatus() == 'A' || $get_unapproved )
        && in_array( $dateYmd, $repeated_events[$i]->getRepeatAllDates() ) )
      $ret[$n++] = $repeated_events[$i];
  }
  return $ret;
}

/**
 * Gets all the tasks for a specific date.
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

  $ret = [];
  $today = date ( 'Ymd' );
  for ( $i = 0, $cnt = count ( $tasks ); $i < $cnt; $i++ ) {
    // In case of data corruption (or some other bug...).
    if( empty( $tasks[$i] ) || $tasks[$i]->getID() == ''
        || ( ! $get_unapproved && $tasks[$i]->getStatus() == 'W' ) )
      continue;

    $due_date = date( 'Ymd', $tasks[$i]->getDueDateTimeTS() );
    // Make overdue tasks float to today.
    if ( ( $date == $today && $due_date < $today ) || $due_date == $date )
      $ret[] = $tasks[$i];
  }
  return $ret;
}

/**
 * Get plugins available to the current user.
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
function get_user_plugin_list() {
  $ret = [];
  $all_plugins = get_plugin_list();
  for ( $i = 0, $cnt = count ( $all_plugins ); $i < $cnt; $i++ ) {
    if ( $GLOBALS[$all_plugins[$i] . '.disabled'] != 'N' )
      $ret[] = $all_plugins[$i];
  }
  return $ret;
}

/**
 * Get event ids for all events this user is a participant.
 *
 * @param string $user  User to retrieve event ids
 */
function get_users_event_ids ( $user ) {
  $events = [];
  $res = dbi_execute ( 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu
  WHERE we.cal_id = weu.cal_id
    AND weu.cal_login = ?', [$user] );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $events[] = $row[0];
    }
  }
  return $events;
}

/**
 * Identify user's browser.
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
function get_web_browser() {
  $agent = getenv( 'HTTP_USER_AGENT' );

  if ( preg_match( '/MSIE \d/', $agent ) )
    return 'MSIE';

  if ( preg_match( '/Mozilla\/[234]/', $agent ) )
    return 'Netscape';

  if ( preg_match( '/Mozilla\/[5678]/', $agent ) )
    return 'Mozilla';

  return 'Unknown';
}

/**
 * Gets the previous weekday of the week containing the specified date.
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
  global $DISPLAY_WEEKENDS, $WEEK_START, $weekday_names;

  // Construct string like 'last Sun'.
  $laststr = 'last ' . $weekday_names[$WEEK_START];
  // We default day=2 so if the 1ast is Sunday or Monday it will return the 1st.
  $newdate = strtotime ( $laststr,
    mktime ( 0, 0, 0, $month, $day, $year ) + $GLOBALS['tzOffset'] );
  // Check DST and adjust newdate.
  while ( date ( 'w', $newdate ) == date ( 'w', $newdate + 86400 ) ) {
    $newdate += 3600;
  }
  return $newdate;
}

/**
 * Get the moonphases for a given year and month.
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
function getMoonPhases ( $year, $month ) {
  global $DISPLAY_MOON_PHASES;
  static $moons;

  if ( empty ( $DISPLAY_MOON_PHASES ) || $DISPLAY_MOON_PHASES == 'N' )
    return false;

  if ( empty ( $moons ) && file_exists ( 'includes/moon_phases.php' ) ) {
    include_once ( 'includes/moon_phases.php' );
    $moons = calculateMoonPhases ( $year, $month );
  }

  return $moons;
}

/**
 * Calculate event rollover to next day and add partial event as needed.
 *
 * Create a cloned event on the fly as needed to display in next day slot.
 * The event times will be adjusted so that the total of all times will
 * equal the total time of the original event. This function will get called
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
  global $DISABLE_CROSSDAY_EVENTS, $result;
  static $originalDate, $originalItem, $realEndTS;

  if ( $DISABLE_CROSSDAY_EVENTS == 'Y' )
    return false;

  $lt = localtime( $item->getDateTimeTS() );
  $recurse = 0;

  $midnight = gmmktime( - ( date( 'Z', $item->getDateTimeTS() ) / 3600 ),
    0, 0, $lt[4] + 1, $lt[3] + 1, 1900 + $lt[5] );
  if ( $parent ) {
    $realEndTS = $item->getEndDateTimeTS();
    $originalDate = $item->getDate();
    $originalItem = $item;
  }
  $new_duration = ( $realEndTS - $midnight ) / 60;
  if ( $new_duration > 1440 ) {
    $new_duration = 1439;
    $recurse = 1;
  }
  if ( $realEndTS > $midnight ) {
    $result[$i] = clone ( $originalItem );
    $result[$i]->setClone ( $originalDate );
    $result[$i]->setDuration ( $new_duration );
    $result[$i]->setTime ( gmdate ( 'G0000', $midnight ) );
    $result[$i]->setDate ( gmdate ( 'Ymd', $midnight ) );
    $result[$i]->setName( $originalItem->getName() . ' ('
       . translate ( 'cont.' ) . ')' );

    $i++;
    if ( $parent )
      $item->setDuration( ( ( $midnight - $item->getDateTimeTS() ) / 60 ) -1 );
  }
  // Call this function recursively until duration < ONE_DAY.
  if ( $recurse == 1 )
   getOverLap ( $result[$i -1], $i, false );
}

/**
 * Hack to implement clone() for php4.x.
 *
 * @param mixed  Event object
 *
 * @return mixed  Clone of the original object.
 */
if( version_compare( phpversion(), '5.0' ) < 0 ) {
  eval ( '
    function clone ($item) {
      return $item;
    }
    ' );
}

/**
 * Get the reminder data for a given entry id.
 *
 * @param int $id        cal_id of requested entry
 * @param bool $display  if true, will create a displayable string
 *
 * @return string $str       string to display Reminder value.
 * @return array  $reminder
 */
function getReminders ( $id, $display = false ) {
  $reminder = [];
  $str = '';
  // Get reminders.
  $rows = dbi_get_cached_rows ( 'SELECT cal_id, cal_date, cal_offset,
    cal_related, cal_before, cal_repeats, cal_duration, cal_action,
    cal_last_sent, cal_times_sent FROM webcal_reminders
  WHERE cal_id = ?
  ORDER BY cal_date, cal_offset, cal_last_sent', [$id] );
  if ( $rows ) {
    $rowcnt = count ( $rows );
    for ( $i = 0; $i < $rowcnt; $i++ ) {
      $row = $rows[$i];
      $reminder['id'] = $row[0];
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
          $d = intval ( $minutes / (24*60) );
          $minutes -= ( $d * (24*60) );
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

/**
 * Remove :00 from times based on $DISPLAY_MINUTES value.
 *
 * @param string $timestr  time value to shorten
 *
 * @global string (Y/N)  Display 00 if on the hour
 */
function getShortTime ( $timestr ) {
  global $DISPLAY_MINUTES;

  return ( empty ( $DISPLAY_MINUTES ) || $DISPLAY_MINUTES == 'N'
    ? preg_replace ( '/(:00)/', '', $timestr ) : $timestr );
}

/**
 * Converts from Gregorian Year-Month-Day to ISO YearNumber-WeekNumber-WeekDay.
 *
 * @internal JGH borrowed gregorianToISO from PEAR Date_Calc Class and added
 *
 * $GLOBALS['WEEK_START'] (change noted)
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
  global $WEEK_START;

  $mnth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
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
  if ( ! $WEEK_START ) {
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

/**
 * Converts a hexadecimal digit to an integer.
 *
 * @param string $val Hexadecimal digit
 *
 * @return int Equivalent integer in base-10
 *
 * @ignore
 */
function hextoint ( $val ) {
  if ( empty ( $val ) )
    return 0;

  switch ( strtoupper ( $val ) ) {
    case '0': return 0;
    case '1': return 1;
    case '2': return 2;
    case '3': return 3;
    case '4': return 4;
    case '5': return 5;
    case '6': return 6;
    case '7': return 7;
    case '8': return 8;
    case '9': return 9;
    case 'A': return 10;
    case 'B': return 11;
    case 'C': return 12;
    case 'D': return 13;
    case 'E': return 14;
    case 'F': return 15;
  }
  return 0;
}


/**
 * Generates the HTML for an event to be viewed in the day-at-glance (day.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event  The event
 * @param string $date   Date of event in YYYYMMDD format
 */
function html_for_event_day_at_a_glance ( $event, $date ) {
  global $ALLOW_HTML_DESCRIPTION, $categories, $DISPLAY_DESC_PRINT_DAY,
  $DISPLAY_END_TIMES, $first_slot, $hour_arr, $last_slot, $layers, $login,
  $PHP_SELF, $rowspan, $rowspan_arr;
  static $key = 0;

  $can_access = CAN_DOALL;
  $end_timestr = $popup_timestr = '';
  $getCalTypeName = $event->getCalTypeName();
  $getCat = abs( $event->getCategory() );
  $getClone = $event->getClone();
  $getDesc = $event->getDescription();
  $getLogin = $event->getLogin();
  $getPri = $event->getPriority();
  $id = $event->getID();
  $ind = 9999;
  $isAllDay = $event->isAllDay();
  $linkid = "pop$id-$key";
  $name = $event->getName();
  $time = $event->getTime();
  $time_only = 'N';
  $view_text = translate ( 'View this event' );

  $catIcon = 'icons/cat-' . $getCat . '.gif';
  if ( ! file_exists ( $catIcon ) )
    $catIcon = 'icons/cat-' . $getCat . '.png';
  $key++;

  if( access_is_enabled() ) {
    $can_access = access_user_calendar ( 'view', $getLogin, '',
      $event->getCalType(), $event->getAccess() );
    $time_only = access_user_calendar ( 'time', $getLogin );
    if ( $getCalTypeName == 'task' && $can_access == 0 )
      return false;
  }

  // If TZ_OFFSET make this event before the start of the day or
  // after the end of the day, adjust the time slot accordingly.
  if( ! $event->isUntimed() && ! $isAllDay && $getCalTypeName != 'task' ) {
    $tz_time = date( 'His', $event->getDateTimeTS() );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;

    $tz_time2 = date( 'His', $event->getEndDateTimeTS() );
    $ind2 = calc_time_slot ( $tz_time2 );
    if ( $ind2 > $last_slot )
      $last_slot = $ind2;
  }
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  $class = ( $login != $getLogin && strlen ( $getLogin )
    ? 'layer' : ( $event->getStatus() == 'W' ? 'unapproved' : '' ) ) . 'entry';
  // If we are looking at a view, then always use "entry".
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
      strstr ( $PHP_SELF, 'view_t.php' ) ||
      strstr ( $PHP_SELF, 'view_v.php' ) ||
      strstr ( $PHP_SELF, 'view_w.php' ) )
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
    ? 'href="view_entry.php?id=' . $id . '&amp;date='
     . ( $getClone ? $getClone : $date )
     . ( strlen ( $GLOBALS['user'] ) > 0
      ? '&amp;user=' . $GLOBALS['user']
      : ( $class == 'layerentry' ? '&amp;user=' . $getLogin : '' ) ) . '"'
    : '' ) . '>' . ( $getPri == 3 ? '<strong>' : '' );

  if ( $login != $getLogin && strlen ( $getLogin ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruser'] == $getLogin ) {
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
    $end_timestr = '-' . display_time( $event->getEndDateTime() );
    $popup_timestr = display_time( $event->getDatetime() );

    $hour_arr[$ind] .= '[' . $popup_timestr;
    if( $event->getDuration() > 0 ) {
      $popup_timestr .= $end_timestr;
      if ( $DISPLAY_END_TIMES == 'Y' )
        $hour_arr[$ind] .= $end_timestr;
      // Which slot is end time in? take one off so we don't
      // show 11:00-12:00 as taking up both 11 and 12 slots.
      $end_time = date( 'His', $event->getEndDateTimeTS() );
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
  $hour_arr[$ind] .= '] ';
  }
  $hour_arr[$ind] .= build_entry_label ( $event, 'eventinfo-' . $linkid,
    $can_access, $popup_timestr, $time_only )
   . ( $getPri == 3 ? '</strong>' : '' ) . '</a>'
   . ( $DISPLAY_DESC_PRINT_DAY == 'Y' ? '
    <dl class="desc">
      <dt>' . translate ( 'Description' ) . ':</dt>
      <dd>'
     . ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y'
      ? $getDesc : strip_tags ( $getDesc ) ) . '</dd>
    </dl>' : '' ) . "<br />\n";
}

/**
 * Generates the HTML for an event to be viewed in the week-at-glance (week.php).
 *
 * The HTML will be stored in an array (global variable $hour_arr)
 * indexed on the event's starting hour.
 *
 * @param Event  $event           The event
 * @param string $date            Date for which we're printing (in YYYYMMDD format)
 * @param string $override_class  If set, then this is the class to use
 * @param bool   $show_time       If enabled, then event time is displayed
 */
function html_for_event_week_at_a_glance ( $event, $date,
  $override_class = '', $show_time = true ) {
  global $categories, $DISPLAY_ICONS, $DISPLAY_TZ, $eventinfo, $first_slot,
  $hour_arr, $is_assistant, $is_nonuser_admin, $last_slot, $layers, $login,
  $PHP_SELF, $rowspan, $rowspan_arr, $TIME_SPACER, $user;
  static $key = 0;

  $can_access = CAN_DOALL;
  $catAlt = $href = $timestr = '';
  $getCalTypeName = $event->getCalTypeName();
  $getCat = abs( $event->getCategory() );
  $getClone = $event->getClone();
  $getDatetime = $event->getDatetime();
  $getLoginStr = $event->getLogin();
  $getPri = $event->getPriority();
  $id = $event->getID();
  $ind = 9999;
  $isAllDay = $event->isAllDay();
  $isUntime = $event->isUntimed();
  $linkid = "pop$id-$key";
  $name = $event->getName();
  $time_only = 'N';
  $title = '<a title="';

  $catIcon = 'icons/cat-' . $getCat . '.gif';
  if ( ! file_exists ( $catIcon ) )
    $catIcon = 'icons/cat-' . $getCat . '.png';
  $key++;

  if( access_is_enabled() ) {
    $can_access = access_user_calendar ( 'view', $getLoginStr, '',
      $event->getCalType(), $event->getAccess() );
    $time_only = access_user_calendar ( 'time', $getLoginStr );
    if ( $getCalTypeName == 'task' && $can_access == 0 )
      return false;
  }

  // Figure out which time slot it goes in. Put tasks in with AllDay and Untimed.
  if ( ! $isUntime && ! $isAllDay && $getCalTypeName != 'task' ) {
    $tz_time = date( 'His', $event->getDateTimeTS() );
    $ind = calc_time_slot ( $tz_time );
    if ( $ind < $first_slot )
      $first_slot = $ind;

    if ( $ind > $last_slot )
      $last_slot = $ind;
  }

  $class = ( $login != $getLoginStr && strlen ( $getLoginStr )
    ? 'layer' : ( $event->getStatus() == 'W' ? 'unapproved' : '' ) ) . 'entry';
  // If we are looking at a view, then always use "entry".
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
      strstr ( $PHP_SELF, 'view_r.php' ) ||
      strstr ( $PHP_SELF, 'view_t.php' ) ||
      strstr ( $PHP_SELF, 'view_v.php' ) ||
      strstr ( $PHP_SELF, 'view_w.php' ) )
    $class = 'entry';

  if ( ! empty ( $override_class ) )
    $class .= ' ' . $override_class;

  // Avoid PHP warning for undefined array index.
  if ( empty ( $hour_arr[$ind] ) )
    $hour_arr[$ind] = '';

  if ( $getCat > 0 && file_exists ( $catIcon ) ) {
    $catAlt = translate ( 'Category' ) . ': ' . $categories[$getCat]['cat_name'];
    $hour_arr[$ind] .= '<img src="' . $catIcon . '" alt="' . $catAlt
     . '" title="' . $catAlt . '" />';
  }

  // Build entry link if UAC permits viewing.
  if ( $can_access != 0 && $time_only != 'Y' ) {
    // Make sure clones have parents URL date.
    $href = 'href="view_entry.php?id=' . $id . '&amp;date='
     . ( $getClone ? $getClone : $date );
    if ( $getCalTypeName == 'task' ) {
      $hour_arr[$ind] .= '<img src="images/task.gif" class="bullet" alt="*" /> ';

      $title .= translate ( 'View this task' );
    } else { // Must be event.
      if ( $isAllDay || $isUntime && $catAlt == '' )
        $hour_arr[$ind] .= '<img src="images/circle.gif" class="bullet" alt="*" /> ';

      $title .= translate ( 'View this event' );
    }
  }

  $hour_arr[$ind] .= $title . '" class="' . $class . '" id="' . $linkid . '" '
   . $href . ( strlen ( $GLOBALS['user'] ) > 0
    ? '&amp;user=' . $GLOBALS['user']
    : ( $class == 'layerentry' ? '&amp;user=' . $getLoginStr : '' ) ) . '">'
   . ( $getPri == 3 ? '<strong>' : '' );

  if ( $login != $getLoginStr && strlen ( $getLoginStr ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruser'] == $getLoginStr ) {
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
  if ( $isAllDay ) {
    $timestr = translate ( 'All day event' );
    // Set start cell of all-day event to beginning of work hours.
    if ( empty ( $rowspan_arr[$first_slot] ) )
      $rowspan_arr[$first_slot] = 0; // Avoid warning below.
    // We'll skip tasks here as well.
  } else
  if ( $event->getTime() >= 0 && $getCalTypeName != 'task' ) {
    if ( $show_time )
      $hour_arr[$ind] .= display_time ( $getDatetime )
       . ( $time_only == 'Y' ? '' : $TIME_SPACER );

    $timestr = display_time ( $getDatetime );
    if( $event->getDuration() > 0 ) {
      $end_time = date( 'His', $event->getEndDateTimeTS() );
      $timestr .= '-' . display_time( $event->getEndDateTime(), $DISPLAY_TZ );
      // This fixes the improper display if an event ends at or after midnight.
      if ( $end_time < $tz_time )
        $end_time += 240000;
    } else
      $end_time = 0;

    if ( empty ( $rowspan_arr[$ind] ) )
      $rowspan_arr[$ind] = 0; // Avoid warning below.

    // Which slot is end time in? take one off so we don't
    // show 11:00-12:00 as taking up both 11 and 12 slots.
    $endind = calc_time_slot ( $end_time, true );
    $rowspan = ( $endind == $ind ? 0 : $endind - $ind + 1 );

    if ( $rowspan > $rowspan_arr[$ind] && $rowspan > 1 )
      $rowspan_arr[$ind] = $rowspan;
  }

  $hour_arr[$ind] .= build_entry_label ( $event, 'eventinfo-' . $linkid,
    $can_access, $timestr, $time_only )
   . ( empty ( $in_span ) ? '' : '</span>' )// End color span.
   . ( $getPri == 3 ? '</strong>' : '' ) . '</a>'
  // . ( $DISPLAY_ICONS == 'Y' ? icon_text ( $id, true, true ) : '' )
  . "<br />\n";
}

/**
 * Converts HTML entities in 8bit.
 *
 * <b>Note:</b> Only supported for PHP4 (not PHP3).
 *
 * @param string $html  HTML text
 *
 * @return string  The converted text.
 */
function html_to_8bits ( $html ) {
  return ( floor( phpversion() ) < 4
   ? $html
   : strtr ( $html, array_flip ( get_html_translation_table ( HTML_ENTITIES ) ) ) );
}

/**
 * Generates the HTML for an add/edit/delete icon.
 *
 * This function is not yet used. Some of the places that will call it have to
 * be updated to also get the event owner so we know if the current user has
 * access to edit and delete.
 *
 * @param int  $id          Event ID
 * @param bool $can_edit    Can this user edit this event?
 * @param bool $can_delete  Can this user delete this event?
 *
 * @return  HTML for add/edit/delete icon.
 *
 * @ignore
 */
function icon_text ( $id, $can_edit, $can_delete ) {
  global $is_admin, $readonly;
  $deleteStr = translate ( 'Delete entry' );
  $editEntryStr = translate ( 'Edit entry' );
  $viewEntryStr = translate ( 'View this entry' );

  return '
        <a title="' . $viewEntryStr . '" href="view_entry.php?id=' . $id
   . '"><img src="images/view.gif" alt="' . $viewEntryStr
   . '" class="icon_text" /></a>' . ( $can_edit && $readonly == 'N' ? '
        <a title="' . $editEntryStr . '" href="edit_entry.php?id=' . $id
     . '"><img src="images/edit.gif" alt="' . $editEntryStr
     . '" class="icon_text" /></a>' : '' )
   . ( $can_delete && ( $readonly == 'N' || $is_admin ) ? '
        <a title="' . $deleteStr . '" href="del_entry.php?id=' . $id
     . '" onclick="return confirm( \''
     . translate( 'Are you sure you want to delete this entry?' ) . ' '
     . translate ( 'This will delete this entry for all users.' )
     . '\' );"><img src="images/delete.gif" alt="' . $deleteStr
     . '" class="icon_text" /></a>' : '' );
}

/**
 * Determine if date is a weekend
 *
 * @param int $date  Timestamp of subject date OR a weekday number 0-6
 *
 * @return bool  True = Date is weekend
 */
function is_weekend ( $date ) {
  global $WEEKEND_START;

  // We can't test for empty because $date may equal 0.
  if ( ! strlen ( $date ) )
    return false;

  if ( ! isset ( $WEEKEND_START ) )
    $WEEKEND_START = 6;

  // We may have been passed a weekday 0-6.
  if ( $date < 7 )
    return ( $date == $WEEKEND_START % 7 || $date == ( $WEEKEND_START + 1 ) % 7 );

  // We were passed a timestamp.
  $wday = date ( 'w', $date );
  return ( $wday == $WEEKEND_START % 7 || $wday == ( $WEEKEND_START + 1 ) % 7 );
}

/**
 * Is this a leap year?
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
    $year = strftime( '%Y', time() );

  if ( strlen ( $year ) != 4 || preg_match ( '/\D/', $year ) )
    return false;

  return ( ( $year % 4 == 0 && $year % 100 != 0 ) || $year % 400 == 0 );
}

/**
 * Loads default system settings (which can be updated via admin.php).
 *
 * System settings are stored in the webcal_config table.
 *
 * <b>Note:</b> If the setting for <var>server_url</var> is not set,
 * the value will be calculated and stored in the database.
 *
 * @global string  User's login name
 * @global bool    Readonly
 * @global string  HTTP hostname
 * @global int     Server's port number
 * @global string  Request string
 * @global array   Server variables
 */
function load_global_settings() {
  global $_SERVER, $APPLICATION_NAME, $FONTS, $HTTP_HOST,
  $LANGUAGE, $REQUEST_URI, $SERVER_PORT, $SERVER_URL;

  // Note:  When running from the command line (send_reminders.php),
  // these variables are (obviously) not set.
  // TODO:  This type of checking should be moved to a central location
  // like init.php.
  if ( isset ( $_SERVER ) && is_array ( $_SERVER ) ) {
    if ( empty ( $HTTP_HOST ) && isset ( $_SERVER['HTTP_HOST'] ) )
      $HTTP_HOST = $_SERVER['HTTP_HOST'];

    if ( empty ( $SERVER_PORT ) && isset ( $_SERVER['SERVER_PORT'] ) )
      $SERVER_PORT = $_SERVER['SERVER_PORT'];

    if ( ! isset ( $_SERVER['REQUEST_URI'] ) ) {
      $arr = explode ( '/', $_SERVER['PHP_SELF'] );
      $_SERVER['REQUEST_URI'] = '/' . $arr[count ( $arr )-1];
      if ( isset ( $_SERVER['argv'][0] ) && $_SERVER['argv'][0] != '' )
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['argv'][0];
    }
    if ( empty ( $REQUEST_URI ) && isset ( $_SERVER['REQUEST_URI'] ) )
      $REQUEST_URI = $_SERVER['REQUEST_URI'];

    // Hack to fix up IIS.
    if ( isset ( $_SERVER['SERVER_SOFTWARE'] ) &&
        strstr ( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) &&
        isset ( $_SERVER['SCRIPT_NAME'] ) )
      $REQUEST_URI = $_SERVER['SCRIPT_NAME'];
  }

  $rows = dbi_get_cached_rows ( 'SELECT cal_setting, cal_value
    FROM webcal_config' );
  for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
    $row = $rows[$i];
    $setting = $row[0];
    $GLOBALS[$setting] = $value = $row[1];
  }

  // Set SERVER TIMEZONE.
  if ( empty ( $GLOBALS['TIMEZONE'] ) )
    $GLOBALS['TIMEZONE'] = $GLOBALS['SERVER_TIMEZONE'];

  set_env ( 'TZ', $GLOBALS['TIMEZONE'] );
  if ( empty ( $tzInitSet ) ) {
    if ( function_exists ( "date_default_timezone_set" ) )
      date_default_timezone_set ( $GLOBALS['TIMEZONE'] );
  }

  // If app name not set.... default to "Title". This gets translated later
  // since this function is typically called before translate.php is included.
  // Note:  We usually use translate ( $APPLICATION_NAME ) instead of
  // translate ( 'Title' ).
  if ( empty ( $APPLICATION_NAME ) )
    $APPLICATION_NAME = 'Title';

  if ( empty ( $SERVER_URL ) &&
      ( ! empty ( $HTTP_HOST ) && ! empty ( $REQUEST_URI ) ) ) {
    $ptr = strrpos ( $REQUEST_URI, '/' );
    if ( $ptr > 0 ) {
      $SERVER_URL = 'http://' . $HTTP_HOST
       . ( ! empty ( $SERVER_PORT ) && $SERVER_PORT != 80
        ? ':' . $SERVER_PORT : '' )
       . substr ( $REQUEST_URI, 0, $ptr + 1 );

      dbi_execute ( 'INSERT INTO webcal_config ( cal_setting, cal_value )
        VALUES ( ?, ? )', ['SERVER_URL', $SERVER_URL] );
    }
  }

  // If no font settings, then set default.
  if ( empty ( $FONTS ) )
    $FONTS = ( $LANGUAGE == 'Japanese' ? 'Osaka, ' : '' )
     . 'Arial, Helvetica, sans-serif';
}

/**
 * Loads nonuser preferences from the webcal_user_pref table
 * if on a nonuser admin page.
 *
 * @param string $nonuser  Login name for nonuser calendar
 */
function load_nonuser_preferences ( $nonuser ) {
  global $DATE_FORMAT, $DATE_FORMAT_MD, $DATE_FORMAT_MY, $prefarray;

  $rows = dbi_get_cached_rows ( 'SELECT cal_setting, cal_value
  FROM webcal_user_pref
  WHERE cal_login = ?', [$nonuser] );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $setting = $row[0];
      $value = $row[1];
      // $sys_setting = 'sys_' . $setting;
      // save system defaults
      // ** Don't override ones set by load_user_prefs.
      if ( ! empty ( $GLOBALS[$setting] ) && empty ( $GLOBALS['sys_' . $setting] ) )
        $GLOBALS['sys_' . $setting] = $GLOBALS[$setting];

      $GLOBALS[$setting] = $prefarray[$setting] = $value;
    }
  }

  if ( empty ( $DATE_FORMAT ) || $DATE_FORMAT == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );

  if ( empty ( $DATE_FORMAT_MY ) || $DATE_FORMAT_MY == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT_MY = translate ( '__month__ __yyyy__' );

  if ( empty ( $DATE_FORMAT_MD ) || $DATE_FORMAT_MD == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT_MD = translate ( '__month__ __dd__' );
}

/**
 * Returns a custom header, stylesheet or tailer.
 *
 * The data will be loaded from the webcal_user_template table.
 * If the global variable $ALLOW_EXTERNAL_HEADER is set to 'Y',
 * then we load an external file using include.
 * This can have serious security issues since a
 * malicous user could open up /etc/passwd.
 *
 * @param string $login  Current user login
 * @param string $type   type of template
 *                       ('H' = header, 'S' = stylesheet, 'T' = trailer)
 */
function load_template ( $login, $type ) {
  global $ALLOW_EXTERNAL_HEADER, $ALLOW_USER_HEADER;

  $found = false;
  $ret = '';

  // First, check for a user-specific template.
  $sql = 'SELECT cal_template_text FROM webcal_user_template
    WHERE cal_type = ? and cal_login = ';
  if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' ) {
    $rows = dbi_get_cached_rows ( $sql . '?', [$type, $login] );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  // If no user-specific template, check for the system template.
  if ( ! $found ) {
    $rows = dbi_get_cached_rows ( $sql . '"__system__"', [$type] );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      $ret .= $row[0];
      $found = true;
    }
  }

  // If still not found, the check the old location (WebCalendar 1.0 and before).
  if ( ! $found ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_template_text
      FROM webcal_report_template
  WHERE cal_template_type = ?
    AND cal_report_id = 0', [$type] );
    if ( $rows && ! empty ( $rows[0] ) ) {
      $row = $rows[0];
      if ( ! empty ( $row ) ) {
        $ret .= $row[0];
        $found = true;
      }
    }
  }


  // Strip leading and trailing white space in file name candidate.
  $file = preg_replace ('/^\s*/', '', $ret);
  $file = preg_replace ('/\s*$/', '', $file);

  if ( $found &&
    ( ! empty ( $ALLOW_EXTERNAL_HEADER ) && $ALLOW_EXTERNAL_HEADER == 'Y' ) &&
      file_exists ( $file ) ) {
// Craig. Why not just do $ret = file_get_contents ( $file ) here?
    ob_start();
    include "$file";
    $ret = ob_get_contents();
    ob_end_clean();
  }

  return $ret;
}

/**
 * Loads current user's category info and stuff it into category global variable.
 *
 * @param string $ex_global Don't include global categories ('' or '1')
 */
function load_user_categories ( $ex_global = '' ) {
  global $categories, $CATEGORIES_ENABLED,
  $is_admin, $is_assistant, $login, $user;

  $categories = [];
  // These are default values.
  $categories[0]['cat_name'] = translate ( 'All' );
  $categories[-1]['cat_name'] = translate ( 'None' );
  if ( $CATEGORIES_ENABLED == 'Y' ) {
    $query_params = [];
    $query_params[] = ( ( ! empty ( $user ) && strlen ( $user ) ) &&
      ( $is_assistant || $is_admin ) ? $user : $login );
    $rows = dbi_get_cached_rows ( 'SELECT cat_id, cat_name, cat_owner, cat_color
      FROM webcal_categories WHERE ( cat_owner = ? ) ' . ( $ex_global == ''
        ? 'OR ( cat_owner IS NULL ) ORDER BY cat_owner,' : 'ORDER BY' )
       . ' cat_name', $query_params );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $categories[$row[0]] = [
          'cat_name' => $row[1],
          'cat_owner' => $row[2],
          'cat_global' => empty ( $row[2] ) ? 1 : 0,
          'cat_color' => ( empty ( $row[3] ) ? '#000000' : $row[3] )];
      }
    }
  }
}

/**
 * Loads current user's layer info into layer global variable.
 *
 * If the system setting <var>$ALLOW_VIEW_OTHER</var> is not set to 'Y', then
 * we ignore all layer functionality. If <var>$force</var> is 0, we only load
 * layers if the current user preferences have layers turned on.
 *
 * @param string $user   Username of user to load layers for
 * @param int    $force  If set to 1, then load layers for this user even if
 *                       user preferences have layers turned off.
 */
function load_user_layers ( $user = '', $force = 0 ) {
  global $ALLOW_VIEW_OTHER, $layers, $LAYERS_STATUS, $login;

  if ( $user == '' )
    $user = $login;

  $layers = [];

  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER != 'Y' )
    return; // Not allowed to view others' calendars, so cannot use layers.
  if ( $force || ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS != 'N' ) ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_layerid, cal_layeruser, cal_color,
      cal_dups FROM webcal_user_layers WHERE cal_login = ? ORDER BY cal_layerid',
      [$user] );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $layers[$row[0]] = [
          'cal_layerid' => $row[0],
          'cal_layeruser' => $row[1],
          'cal_color' => $row[2],
          'cal_dups' => $row[3]];
      }
    }
  }
}

/**
 * Loads the current user's preferences as global variables
 * from the webcal_user_pref table.
 *
 * Also loads the list of views for this user
 * (not really a preference, but this is a convenient place to put this...)
 *
 * <b>Notes:</b>
 * - If <var>$ALLOW_COLOR_CUSTOMIZATION</var> is set to 'N', then we ignore any
 *   color preferences.
 * - Other default values will also be set if the user has not saved a
 *   preference and no global value has been set by the administrator in the
 *   system settings.
 */
function load_user_preferences ( $guest = '' ) {
  global $ALLOW_COLOR_CUSTOMIZATION, $browser, $DATE_FORMAT, $DATE_FORMAT_MD,
  $DATE_FORMAT_MY, $DATE_FORMAT_TASK, $has_boss, $is_assistant, $is_nonuser,
  $is_nonuser_admin, $lang_file, $LANGUAGE, $login, $prefarray, $user, $views;

  $browser = get_web_browser();
  $browser_lang = get_browser_language();
  $colors = [
    'BGCOLOR' => 1,
    'CELLBG' => 1,
    'H2COLOR' => 1,
    'HASEVENTSBG' => 1,
    'MYEVENTS' => 1,
    'OTHERMONTHBG' => 1,
    'POPUP_BG' => 1,
    'POPUP_FG' => 1,
    'TABLEBG' => 1,
    'TEXTCOLOR' => 1,
    'THBG' => 1,
    'THFG' => 1,
    'TODAYCELLBG' => 1,
    'WEEKENDBG' => 1,
    'WEEKNUMBER' => 1];
  $lang_found = false;
  $prefarray = [];
  // Allow __public__ pref to be used if logging in or user not validated.
  $tmp_login = ( empty ( $guest )
    ? $login : ( $guest == 'guest' ? '__public__' : $guest ) );

  $rows = dbi_get_cached_rows ( 'SELECT cal_setting, cal_value
  FROM webcal_user_pref
  WHERE cal_login = ?', [$tmp_login] );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $setting = $row[0];
      $value = $row[1];

      if ( $setting == 'LANGUAGE' )
        $lang_found = true;

      if ( $ALLOW_COLOR_CUSTOMIZATION == 'N' &&
        isset ( $colors[$setting] ) )
        continue;

      // $sys_setting = 'sys_' . $setting;
      // Save system defaults.
      if ( ! empty ( $GLOBALS[$setting] ) )
        $GLOBALS['sys_' . $setting] = $GLOBALS[$setting];

      $GLOBALS[$setting] = $prefarray[$setting] = $value;
    }
  }

  // Set users timezone.
  if ( isset ( $GLOBALS['TIMEZONE'] ) )
    set_env ( 'TZ', $GLOBALS['TIMEZONE'] );

  // Get views for this user and global views.
  // If NUC and not authorized by UAC, disallow global views.
  $rows = dbi_get_cached_rows ( 'SELECT cal_view_id, cal_name, cal_view_type,
    cal_is_global, cal_owner FROM webcal_view WHERE cal_owner = ? '
     . ( $is_nonuser && ( ! access_is_enabled() ||
        ( access_is_enabled()
          && ! access_can_access_function( ACCESS_VIEW, $guest ) ) )
      ? '' : ' OR cal_is_global = \'Y\' ' )
     . 'ORDER BY cal_name', [$tmp_login] );
  $views = [];
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $url = 'view_';
      if ( $row[2] == 'E' )
        $url .= 'r.php?';
      elseif ( $row[2] == 'S' )
        $url .= 't.php?';
      elseif ( $row[2] == 'T' )
        $url .= 't.php?';
      else
        $url .= strtolower ( $row[2] ) . '.php?';

      $v = [
        'cal_view_id' => $row[0],
        'cal_name' => $row[1],
        'cal_view_type' => $row[2],
        'cal_is_global' => $row[3],
        'cal_owner' => $row[4],
        'url' => $url . 'id=' . $row[0]];
      $views[] = $v;
    }
  }

  // If user has not set a language preference and admin has not specified a
  // language, then use their browser settings to figure it out
  // and save it in the database for future use (email reminders).
  $lang = 'none';
  if ( ! $lang_found && strlen ( $tmp_login ) && $tmp_login != '__public__' ) {
    if ( $LANGUAGE == 'none' )
      $lang = $browser_lang;

    dbi_execute ( 'INSERT INTO webcal_user_pref ( cal_login, cal_setting,
     cal_value ) VALUES ( ?, ?, ? )', [$tmp_login, 'LANGUAGE', $lang] );
  }
  reset_language ( ! empty ( $LANGUAGE ) && $LANGUAGE != 'none'
    ? $LANGUAGE : $browser_lang );

  if ( empty ( $DATE_FORMAT ) || $DATE_FORMAT == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT = translate ( '__month__ __dd__, __yyyy__' );

  if ( empty ( $DATE_FORMAT_MY ) || $DATE_FORMAT_MY == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT_MY = translate ( '__month__ __yyyy__' );

  if ( empty ( $DATE_FORMAT_MD ) || $DATE_FORMAT_MD == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT_MD = translate ( '__month__ __dd__' );

  if ( empty ( $DATE_FORMAT_TASK ) || $DATE_FORMAT_TASK == 'LANGUAGE_DEFINED' )
    $DATE_FORMAT_TASK = translate ( '__mm__/__dd__/__yyyy__' );

  $has_boss = user_has_boss ( $tmp_login );
  $is_assistant = ( empty ( $user )
    ? false : user_is_assistant ( $tmp_login, $user ) );
  $is_nonuser_admin = ( $user
    ? user_is_nonuser_admin ( $tmp_login, $user ) : false );
  // if ( $is_nonuser_admin ) load_nonuser_preferences ($user);

}

/**
 * Returns the either the full name or the abbreviation of the specified month.
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
    $month_names = $monthshort_names = [];

  $local_lang = $lang;

  if ( empty ( $month_names[0] ) || empty ( $monthshort_names[0] ) ) {
    $month_names = [
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
      translate ( 'December' )];

    $monthshort_names = [
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
      translate ( 'Dec' )];
  }

  if ( $m >= 0 && $m < 12 )
    return ( $format == 'F' ? $month_names[$m] : $monthshort_names[$m] );

  return translate ( 'unknown-month' ) . " ($m)";
}

/**
 * Loads nonuser variables (login, firstname, etc.).
 *
 * The following variables will be set:
 * - <var>login</var>
 * - <var>firstname</var>
 * - <var>lastname</var>
 * - <var>fullname</var>
 * - <var>admin</var>
 * - <var>email</var>
 *
 * @param string $login   Login name of nonuser calendar
 * @param string $prefix  Prefix to use for variables that will be set.
 *                        For example, if prefix is "temp_", then the login will
 *                        be stored in the <var>$temp_login</var> global variable.
 */
function nonuser_load_variables ( $login, $prefix ) {
  global $error, $nuloadtmp_email;

  $ret = false;
  $rows = dbi_get_cached_rows ( 'SELECT cal_login, cal_lastname, cal_firstname,
    cal_admin, cal_is_public, cal_url FROM webcal_nonuser_cals
  WHERE cal_login = ?', [$login] );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $GLOBALS[$prefix . 'fullname'] = ( strlen ( $row[1] ) || strlen ( $row[2] )
        ? "$row[2] $row[1]" : $row[0] );
      $GLOBALS[$prefix . 'login'] = $row[0];
      $GLOBALS[$prefix . 'lastname'] = $row[1];
      $GLOBALS[$prefix . 'firstname'] = $row[2];
      $GLOBALS[$prefix . 'fullname'] = trim($raw[1] . ' ' . $row[2]);
      $GLOBALS[$prefix . 'admin'] = $row[3];
      $GLOBALS[$prefix . 'is_public'] = $row[4];
      $GLOBALS[$prefix . 'url'] = $row[5];
      $GLOBALS[$prefix . 'is_admin'] = false;
      $GLOBALS[$prefix . 'is_nonuser'] = true;
      // We need the email address for the admin.
      user_load_variables ( $row[3], 'nuloadtmp_' );
      $GLOBALS[$prefix . 'email'] = $nuloadtmp_email;
      $ret = true;
    }
  }
  return $ret;
}

/**
 * Prints dropdown HTML for categories.
 *
 * @param string $form    The page to submit data to (without .php)
 * @param string $date    Date in YYYYMMDD format
 * @param int    $cat_id  Category id that should be pre-selected
 */
function print_category_menu ( $form, $date = '', $cat_id = '' ) {
  global $categories, $login, $user, $CATEGORIES_ENABLED;

  if ( empty( $CATEGORIES_ENABLED ) || $CATEGORIES_ENABLED == 'N' )
    return false;

  $catStr = translate ( 'Category' );
  $printerStr = '';
  $ret = '
    <form action="' . $form . '.php" method="get" name="SelectCategory" '
   . 'class="categories">' . ( empty ( $date ) ? '' : '
      <input type="hidden" name="' . ( $form != 'year' ? 'date' : 'year' )
     . '" value="' . $date . '" />' )
   . ( ! empty ( $user ) && $user != $login ? '
      <input type="hidden" name="user" value="' . $user . '" />' : '' )
   . $catStr . ':
      <select name="cat_id" onchange="document.SelectCategory.submit()">';

  // 'None' and 'All' are added during load_user_categories
  if ( is_array ( $categories ) ) {
    foreach ( $categories as $K => $V ) {
      if ( ( ! empty ( $user ) && strlen ( $user ) ? $user : $login ) ||
          empty ( $categories[$K]['cat_owner'] ) ) {
        $ret .= '
        <option value="' . $K . '"';
        if ( $cat_id == $K ) {
          $printerStr .= '
    <span id="cat">' . $catStr . ': ' . $categories[$K]['cat_name'] . '</span>';
          $ret .= ' selected="selected"';
        }
        $ret .= ">{$V['cat_name']}</option>";
      }
    }
  }
  return $ret . '
      </select>
    </form>'
  // This is used for Printer Friendly view.
  . $printerStr;
}

/**
 * Generates HTML for checkbox form controls.
 *
 * @param array  $vals      (name, value, display, setting)
 * @param string $id        the id of the control
 * @param string $onchange  javascript function to call if needed
 *
 * @return string  HTML for the checkbox control.
 */
function print_checkbox( $vals, $id = '', $onchange = '' ) {
  global $prefarray, $s, $SCRIPT;
  static $checked, $No, $Yes;

  $setting  = ( empty( $vals[3] ) ? $vals[0] : $vals[3] );
  $variable = $vals[0];

   if( $SCRIPT == 'admin.php' ) {
    $setting  = $s[$vals[0]];
    $variable = 'admin_' . $vals[0];
  }
  
  if( $SCRIPT == 'pref.php' ) {
    $setting  = $prefarray[$vals[0]];
    $variable = 'pref_' . $vals[0];
  }
    
  $hidden = ( strpos( 'admin.phpref.php', $SCRIPT ) === false ? '' : '
    <input type="hidden" name="' . $variable . '" value="N" />' );


  if( ! empty( $id ) && $id = 'dito' )
    $id = $vals[0];

  if( empty( $checked ) ) {
    $checked = ' checked="checked"';
    $No  = translate( 'No' );
    $Yes = translate( 'Yes' );
  }

  return $hidden . '
      <label><input type="checkbox" name="' . $variable . '" value="' . $vals[1]
   . '" ' . ( empty( $id ) ? '' : 'id="' . $id . '" ' )
   . ( $setting == $vals[1] ? $checked : '' )
   . ( empty( $onchange ) ? '' : ' onchange="' . $onchange . '()"' )
   . ' />&nbsp;' . $vals[2] . '</label>';
}

/**
 * Generates HTML for color chooser options in admin and pref pages.
 *
 * @param string $varname  the name of the variable to display
 * @param string $title    color description
 * @param string $varval   the default value to display
 *
 * @return string  HTML for the color selector.
 */
function print_color_input_html ( $varname, $title, $varval = '' ) {
  global $prefarray, $s, $SCRIPT;
  static $select;

  $name = '';
  $setting = $varval;

  if ( empty ( $select ) )
    $select = translate ( 'Select' ) . '...';

  if ( $SCRIPT == 'admin.php' ) {
    $name = 'admin_';
    $setting = $s[$varname];
  } elseif ( $SCRIPT == 'pref.php' ) {
    $name = 'pref_';
    $setting = $prefarray[$varname];
  }

  $name .= $varname;

  return '
            <p><label for="' . $name . '">' . $title
   . ( $title == '' ? '' : ':' )
   . '</label><input type="text" name="' . $name . '" id="' . $name
   . '" size="7" maxlength="7" value="' . $setting
   . '" onchange="updateColor( this, \'' . $varname
   . '_sample\' );" /><span class="sample" id="' . $varname . '_sample" style="background:'
   . $setting . ';">&nbsp;</span><input type="button" onclick="selectColor( \''
   . $name . '\', event )" value="' . $select . '" /></p>';
}

/**
 * Prints all the calendar entries for the specified user for the specified date.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date  Date in YYYYMMDD format
 * @param string $user  Username
 * @param bool   $ssi   Is this being called from week_ssi.php?
 * @param bool   $disallowAddIcon  If true, then do not display the
 *          add icon, even if user can add events
 */
function print_date_entries ( $date, $user, $ssi = false,
  $disallowAddIcon = false ) {
  global $cat_id, $DISPLAY_TASKS_IN_GRID, $DISPLAY_UNAPPROVED, $events,
  $is_admin, $is_nonuser, $login, $PUBLIC_ACCESS, $PUBLIC_ACCESS_CAN_ADD,
  $readonly, $tasks, $WEEK_START;
  static $newEntryStr;

  if ( empty ( $newEntryStr ) )
    $newEntryStr = translate ( 'New Entry' );

  $cnt = 0;
  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
  $moons = getMoonPhases ( substr ( $date, 0, 4 ), substr ( $date, 4, 2 ) );
  $ret = '';

  $can_add = ( $readonly == 'N' || $is_admin );

  if ( $PUBLIC_ACCESS == 'Y' && $PUBLIC_ACCESS_CAN_ADD != 'Y'
      && $login == '__public__' )
    $can_add = false;

  if ( $readonly == 'Y' )
    $can_add = false;

  if ( $is_nonuser )
    $can_add = false;

  if ( $disallowAddIcon )
    $can_add = false;

  if ( ! $ssi ) {
  /* translate ( 'First Quarter Moon') translate ( 'Full Moon' )
     translate ( 'Last Quarter Moon') translate ( 'New Moon' )
   */
    $userCatStr = ( strcmp ( $user, $login ) ? 'user=' . $user . '&amp;' : '' )
     . ( empty ( $cat_id ) ? '' : 'cat_id=' . $cat_id . '&amp;' );
    $tmp = ( empty( $moons[$date] ) ? '' : $moons[$date] );
    $moon_title = ( empty ( $tmp ) ? '' : translate ( ucfirst ( $tmp )
     . ( strpos ( 'fullnew', $tmp ) !== false ? '' : ' Quarter' ) . ' Moon' ) );
    $ret = ( $can_add ? '
        <a title="' . $newEntryStr . '" href="edit_entry.php?' . $userCatStr
       . 'date=' . $date . '"><img src="images/new.gif" alt="' . $newEntryStr
       . '" class="new" /></a>' : '' ) . '
        <a class="dayofmonth" href="day.php?' . $userCatStr . 'date=' . $date
     . '">' . substr ( $date, 6, 2 ) . '</a>' . ( empty ( $tmp )
      ? '' : '<img src="images/' . $tmp . 'moon.gif" title="' . $moon_title
      . '" alt="' . $moon_title . '" />' ) . "<br />\n";
    $cnt++;
  }
  // Get, combime and sort the events for this date.
  $ev = combine_and_sort_events (
    // Get all the non-repeating events.
    get_entries ( $date, $get_unapproved ),
    // Get all the repeating events.
    get_repeating_entries ( $user, $date, $get_unapproved ) );

  // If wanted, get all due tasks for this date.
  if ( ( empty ( $DISPLAY_TASKS_IN_GRID ) || $DISPLAY_TASKS_IN_GRID == 'Y' ) &&
      ( $date >= date ( 'Ymd' ) ) )
    $ev = combine_and_sort_events ( $ev, get_tasks ( $date, $get_unapproved ) );

  for ( $i = 0, $evCnt = count ( $ev ); $i < $evCnt; $i++ ) {
    if( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      $ret .= print_entry ( $ev[$i], $date );
      $cnt++;
    }
  }
  if ( $cnt == 0 )
    $ret .= '&nbsp;'; // So the table cell has at least something.

  return $ret;
}

/**
 * Prints all the calendar entries for the specified user
 * for the specified date in day-at-a-glance format.
 *
 * If we are displaying data from someone other than
 * the logged in user, then check the access permission of the entry.
 *
 * @param string $date  Date in YYYYMMDD format
 * @param string $user  Username of calendar
 */
function print_day_at_a_glance ( $date, $user, $can_add = 0 ) {
  global $CELLBG, $DISPLAY_TASKS_IN_GRID, $DISPLAY_UNAPPROVED, $first_slot,
  $hour_arr, $last_slot, $rowspan, $rowspan_arr, $TABLEBG, $THBG, $THFG,
  $TIME_SLOTS, $today, $TODAYCELLBG, $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR;

  if ( empty ( $TIME_SLOTS ) )
    return translate ( 'Error TIME_SLOTS undefined!' ) . "<br />\n";

  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
  // Get, combine and sort the events for this date.
  $ev = combine_and_sort_events (
    get_entries ( $date, $get_unapproved ), // Get static non-repeating events.
    get_repeating_entries ( $user, $date )// Get all the repeating events.
    );
  if ( $date >= date ( 'Ymd' ) &&
      ( empty ( $DISPLAY_TASKS_IN_GRID ) || $DISPLAY_TASKS_IN_GRID == 'Y' ) )
    $ev = combine_and_sort_events ( $ev,
      get_tasks ( $date, $get_unapproved ) // Get all due tasks.
      );
  $hour_arr = $rowspan_arr = [];
  $interval = 1440 / $TIME_SLOTS; // Number of minutes per slot

  $first_slot = intval ( ( $WORK_DAY_START_HOUR * 60 ) / $interval );
  $last_slot = intval ( ( $WORK_DAY_END_HOUR * 60 ) / $interval );

  for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
    if( $get_unapproved || $ev[$i]->getStatus() == 'A' )
      html_for_event_day_at_a_glance ( $ev[$i], $date );
  }
  $last_row = -1;
  $ret = '';
  $rowspan = 0;
  // Squish events that use the same cell into the same cell.
  // For example, an event from 8:00-9:15 and another from 9:30-9:45 both
  // want to show up in the 8:00-9:59 cell.
  for ( $i = ( $first_slot < 0 ? $first_slot : 0 ); $i < $TIME_SLOTS; $i++ ) {
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
    <table class="main glance">'
   . ( empty ( $hour_arr[9999] ) ? '' : '
      <tr>
        <th class="empty">&nbsp;</th>
        <td class="hasevents">' . $hour_arr[9999] . '</td>
      </tr>' );

  $rowspan = 0;
  for ( $i = $first_slot; $i <= $last_slot; $i++ ) {
    $time_h = intval ( ( $i * $interval ) / 60 );
    $time_m = ( $i * $interval ) % 60;
    $ret .= '<tr><th class="row"';
    $ret .= '>'
     . display_time ( ( $time_h * 100 + $time_m ) * 100 ) . '</th>';
    if ( $rowspan > 1 ) {
      // This might mean there's an overlap, or it could mean one event
      // ends at 11:15 and another starts at 11:30.
      if ( ! empty ( $hour_arr[$i] ) ) {
        $ret .= '<td class="hasevents"';
        if ( $can_add )
          $ret .=
            " ondblclick=\"dblclick_add('$date','$user',$time_h,$time_m)\"";
        $ret .= '>' . $hour_arr[$i] . '</td>';
      }
      $rowspan--;
    } else {
      $ret .= '
        <td ';
      if ( empty ( $hour_arr[$i] ) ) {
        $ret .= ( $date == date ( 'Ymd', $today ) ? ' class="today"' : '' );
        if ( $can_add )
          $ret .=
            " ondblclick=\"dblclick_add('$date','$user',$time_h,$time_m)\"";
        $ret .= '>&nbsp;';
      } else {
        $rowspan = ( empty ( $rowspan_arr[$i] ) ? '' : $rowspan_arr[$i] );

        $ret .= ( $rowspan > 1 ? 'rowspan="' . $rowspan . '"' : '' )
         . 'class="hasevents"';
        if ( $can_add )
          $ret .=
            " ondblclick=\"dblclick_add('$date','$user',$time_h,$time_m)\"";
        $ret .= '>' . $hour_arr[$i];
      }
      $ret .= '</td>';
    }
    $ret .= '
      </tr>';
  }
  return $ret . '
    </table>';
}

/**
 * Prints the HTML for one event in the month view.
 *
 * @param Event  $event  The event
 * @param string $date   The data for which we're printing (YYYYMMDD)
 *
 * @staticvar int  Used to ensure all event popups have a unique id.
 *
 * @uses build_entry_popup
 */
function print_entry ( $event, $date ) {
  global $categories, $DISPLAY_END_TIMES, $DISPLAY_LOCATION,
  $DISPLAY_TASKS_IN_GRID, $eventinfo, $is_assistant, $is_nonuser_admin,
  $layers, $login, $PHP_SELF, $TIME_SPACER, $user;

  static $key = 0;
  static $viewEventStr, $viewTaskStr;

  if ( empty ( $viewEventStr ) ) {
    $viewEventStr = translate ( 'View this event' );
    $viewTaskStr = translate ( 'View this task' );
  }

  $catIcon = $in_span = $padding = $popup_timestr = $ret = $timestr = '';
  $cal_type = $event->getCalTypeName();
  $loginStr = $event->getLogin();

  if( access_is_enabled() ) {
    $can_access = access_user_calendar ( 'view', $loginStr, '',
      $event->getCalType(), $event->getAccess() );
    $time_only = access_user_calendar ( 'time', $loginStr );
    if ( $cal_type == 'task' && $can_access == 0 )
      return false;
  } else {
    $can_access = CAN_DOALL;
    $time_only = 'N';
  }

  // No need to display if show time only and not a timed event.
  if( $time_only == 'Y' && ! $event->Istimed() )
    return false;

  $class = ( $login != $loginStr && strlen ( $loginStr )
    ? 'layer' : ( $event->getStatus() == 'W' ? 'unapproved' : '' ) ) . 'entry';

  // If we are looking at a view, then always use "entry".
  if ( strstr ( $PHP_SELF, 'view_m.php' ) ||
      strstr ( $PHP_SELF, 'view_t.php' ) ||
      strstr ( $PHP_SELF, 'view_v.php' ) ||
      strstr ( $PHP_SELF, 'view_w.php' ) )
    $class = 'entry';

  if( $event->getPriority() < 4 )
    $ret .= '<strong>';

  $cloneStr = $event->getClone();
  $id = $event->getID();
  $linkid = 'pop' . "$id-$key";
  $name = $event->getName();
  $view_text = ( $cal_type == 'task' ? $viewTaskStr : $viewEventStr );

  $key++;

  // Build entry link if UAC permits viewing.
  if ( $can_access != 0 && $time_only != 'Y' ) {
    // Make sure clones have parents URL date.
    $href = 'href="view_entry.php?id=' . $id . '&amp;date='
     . ( $cloneStr ? $cloneStr : $date )
     . ( strlen ( $user ) > 0
      ? '&amp;user=' . $user
      : ( $class == 'layerentry' ? '&amp;user=' . $loginStr : '' ) ) . '"';
    $title = ' title="' . $view_text . '" ';
  } else
    $href = $title = '';

  $ret .= '
      <a ' . $title . ' class="' . $class . '" id="' . "$linkid\" $href"
   . '><img src="';

  $catNum = abs( $event->getCategory() );
  $icon = $cal_type . '.gif';
  if ( $catNum > 0 ) {
    $catIcon = 'icons/cat-' . $catNum . '.gif';
    if ( ! file_exists ( $catIcon ) )
      $catIcon = 'icons/cat-' . $catNum . '.png';
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

  if ( $login != $loginStr && strlen ( $loginStr ) ) {
    if ( $layers ) {
      foreach ( $layers as $layer ) {
        if ( $layer['cal_layeruser'] == $loginStr ) {
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

  if( $event->isAllDay() )
    $timestr = $popup_timestr = translate ( 'All day event' );
  elseif( ! $event->isUntimed() ) {
    $timestr = $popup_timestr = display_time( $event->getDateTime() );
    if( $event->getDuration() > 0 )
      $popup_timestr .= ' - ' . display_time( $event->getEndDateTime() );

    if ( $DISPLAY_END_TIMES == 'Y' )
      $timestr = $popup_timestr;

    if ( $cal_type == 'event' )
      $ret .= getShortTime ( $timestr )
       . ( $time_only == 'Y' ? '' : $TIME_SPACER );
  }
  return $ret . build_entry_label ( $event, 'eventinfo-' . $linkid, $can_access,
    $popup_timestr, $time_only )

  // Added to allow a small location to be displayed if wanted.
  . ( ! empty ( $location ) && !
    empty ( $DISPLAY_LOCATION ) && $DISPLAY_LOCATION == 'Y'
    ? '<br /><span class="location">('
     . htmlspecialchars ( $location ) . ')</span>' : '' )
   . ( $in_span == true ? '</span>' : '' ) . '</a>'
   . ( $event->getPriority() < 4 ? '</strong>' : '' ) // end font-weight span
  . '<br />';
}

/**
 * Generate standardized error message
 *
 * @param string $error  Message to display
 * @param bool   $full   Include extra text in display
 *
 * @return string  HTML to display error.
 *
 * @uses print_error_header
 */
function print_error ( $error, $full = false ) {
  return print_error_header()
   . ( $full ? translate ( 'The following error occurred' ) . ':' : '' ) . '
    <blockquote>' . $error . '</blockquote>';
}

/**
 * An h2 header error message.
 */
function print_error_header() {
  return '
    <h2>' . translate ( 'Error' ) . '</h2>';
}

/**
 * Generate standardized Not Authorized message
 *
 * @param bool $full  Include ERROR title
 *
 * @return string  HTML to display notice.
 *
 * @uses print_error_header
 */
function print_not_auth ( $full = false ) {
  $ret = ( $full ? print_error_header() : '' )
   . '!!!' . translate ( 'You are not authorized.' ) . "\n";
  return $ret;
}

/**
 * Generates HTML for radio buttons.
 *
 * @param string  $variable the name of the variable to display
 * @param array   $vals the value and display variables
 *                if empty ( Yes/No options will be displayed )
 * @param string  $onclick  javascript function to call if needed
 * @param string  $defIdx default array index to select
 * @param string  $sep HTML value between radio options (&nbsp;,<br />)
 *
 * @return string  HTML for the radio control.
 */
function print_radio ( $variable, $vals = '', $onclick = '', $defIdx = '',
  $sep = '&nbsp;' ) {
  global $prefarray, $s, $SCRIPT;
  static $checked, $No, $Yes;

  $ret = '';
  $setting = $defIdx;
  if ( empty ( $checked ) ) {
    $checked = ' checked="checked"';
    $No = translate ( 'No' );
    $Yes = translate ( 'Yes' );
  }
  if ( empty ( $vals ) )
    $vals = ['Y' => $Yes, 'N' => $No];

  if ( $SCRIPT == 'admin.php' ) {
    if ( ! empty ( $s[$variable] ) )
      $setting = $s[$variable];
    $variable = 'admin_' . $variable;
  }
  if ( $SCRIPT == 'pref.php' ) {
    if ( ! empty ( $prefarray[$variable] ) )
      $setting = $prefarray[$variable];
    $variable = 'pref_' . $variable;
  }
  $onclickStr = ( empty( $onclick ) ? '' : ' onclick="' . $onclick . '()"' );
  foreach ( $vals as $K => $V ) {
    $ret .= '
      <input type="radio" name="' . $variable . '" value="' . $K . '"'
     . ( $setting == $K ? $checked : '' ) . $onclickStr . ' />' . $V;
  }
  return $ret;
}

/**
 * Generate standardized Success message.
 *
 * @param bool $saved
 *
 * @return string  HTML to display error.
 */
function print_success ( $saved ) {
  return ( $saved ? '
    <script>
<!-- <![CDATA[
      alert ( \'' . translate ( 'Changes successfully saved', true ) . '\' );
//]]> -->
    </script>' : '' );
}

/**
 * Prints Timezone select for use on forms
 *
 * @param string  $prefix  Prefix for select control's name
 * @param string  $tz      Current timezone of logged in user
 *
 * @return string $ret  HTML for select control.
*/
function print_timezone_select_html ( $prefix, $tz ) {
  $ret = '';
  // We may be using php 4.x on Windows, so we can't use set_env() to
  // adjust the user's TIMEZONE. We'll need to reply on the old fashioned
  // way of using $tz_offset from the server's timezone.
  $can_setTZ = ( substr ( $tz, 0, 11 ) == 'WebCalendar' ? false : true );
  $old_TZ = getenv ( 'TZ' );
  set_env ( 'TZ', 'America/New_York' );
  $tmp_timezone = date ( 'T' );
  set_env ( 'TZ', $old_TZ );
  // Don't change this to date().
  // if ( date ( 'T' ) == 'Ame' || ! $can_setTZ ) { //We have a problem!!
  if ( 0 ) { // Ignore this code for now.
    $tz_value = ( ! $can_setTZ ? substr ( $tz, 12 ) : 0 );
    $ret = '
        <select name="' . $prefix . 'TIMEZONE" id="' . $prefix . 'TIMEZONE">';
    $text_add = translate ( 'Add N hours to' );
    $text_sub = translate ( 'Subtract N hours from' );
    for ( $i = -12; $i <= 13; $i++ ) {
      $ret .= '
          <option value="WebCalendar/' . $i . '"'
       . ( $tz_value == $i ? ' selected="selected"' : '' ) . '>' . ( $i < 0
        ? str_replace ( 'N', - $i, $text_sub ) : ( $i == 0
          ? translate ( 'same as' ) : str_replace ( 'N', $i, $text_add ) ) )
       . '</option>';
    }
    $ret .= '
        </select>&nbsp;' . translate ( 'server time' );
  } else { // This installation supports TZ env.
    // Import Timezone name. This file will not normally be available
    // on windows platforms, so we'll just include it with WebCalendar.
    $tz_file = 'includes/zone.tab';
    if ( ! $fd = @fopen ( $tz_file, 'r', false ) )
      return str_replace ( 'XXX', $tz_file,
        translate ( 'Cannot read timezone file XXX.' ) );
    else {
      while ( ( $data = fgets ( $fd, 1000 ) ) !== false ) {
        if ( ( substr ( trim ( $data ), 0, 1 ) == '#' ) || strlen ( $data ) <= 2 )
          continue;
        else {
          $data = trim ( $data, strrchr ( $data, '#' ) );
          $data = preg_split ( '/[\s,]+/', trim ( $data ) );
          $timezones[] = $data[2];
        }
      }
      fclose ( $fd );
    }
    sort ( $timezones );
    $ret = '
        <select name="' . $prefix . 'TIMEZONE" id="' . $prefix . 'TIMEZONE">';
    for ( $i = 0, $cnt = count ( $timezones ); $i < $cnt; $i++ ) {
      $ret .= '
          <option value="' . $timezones[$i] . '"'
       . ( $timezones[$i] == $tz ? ' selected="selected" ' : '' ) . '>'
       . unhtmlentities ( $timezones[$i] ) . '</option>';
    }
    $ret .= '
        </select>&nbsp;&nbsp;' . str_replace (' XXX ',
         '&nbsp;' . date ( 'Z' ) / 3600 . '&nbsp;',
         translate ( 'Your current GMT offset is XXX hours.' ) );
  }
  return $ret;
}

/**
 * Reads events visible to a user.
 *
 * Includes layers and possibly public access if enabled.
 * NOTE: The values for the global variables $thisyear and $thismonth
 * MUST be set!  (This will determine how far in the future to caclulate
 * repeating event dates.)
 *
 * @param string $user           Username
 * @param bool   $want_repeated  Get repeating events?
 * @param string $date_filter    SQL phrase starting with AND, to be appended to
 *                               the WHERE clause. May be empty string.
 * @param int    $cat_id         Category ID to filter on. May be empty.
 * @param bool   $is_task        Used to restrict results to events OR tasks
 *
 * @return array  Array of Events sorted by time of day.
 */
function query_events ( $user, $want_repeated, $date_filter, $cat_id = '',
  $is_task = false ) {
  global $db_connection_info, $jumpdate, $layers, $login, $max_until,
  $PUBLIC_ACCESS_DEFAULT_VISIBLE, $result, $thismonth, $thisyear;
  global $OVERRIDE_PUBLIC, $OVERRIDE_PUBLIC_TEXT;

  // New multiple categories requires some checking to see if this cat_id is
  // valid for this cal_id. It could be done with nested SQL,
  // but that may not work for all databases. This might be quicker also.
  $catlist = $cloneRepeats = $layers_byuser = $result = [];

  $sql = 'SELECT DISTINCT( cal_id ) FROM webcal_entry_categories ';
  // None was selected...return only events without categories.
  if ( $cat_id == -1 )
    $rows = dbi_get_cached_rows ( $sql, [] );
  elseif ( ! empty ( $cat_id ) ) {
    $cat_array = explode ( ',', $cat_id );
    $rows = dbi_get_cached_rows ( $sql . '
  WHERE cat_id IN ( ?' . str_repeat ( ',?', count ( $cat_array ) - 1 ) . ' )', $cat_array );
  }
  if ( ! empty ( $cat_id ) ) {
    // $rows = dbi_get_cached_rows ( $sql, [$cat_id] );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $catlist[$i] = $row[0];
      }
    }
  }
  $catlistcnt = count ( $catlist );
  $query_params = [];
  $sql = 'SELECT we.cal_name, we.cal_description, we.cal_date, we.cal_time,
    we.cal_id, we.cal_ext_for_id, we.cal_priority, we.cal_access,
    we.cal_duration, weu.cal_status, we.cal_create_by, weu.cal_login,
    we.cal_type, we.cal_location, we.cal_url, we.cal_due_date, we.cal_due_time,
    weu.cal_percent, we.cal_mod_date, we.cal_mod_time '
   . ( $want_repeated
    ? ', wer.cal_type, wer.cal_end, wer.cal_frequency,
      wer.cal_days, wer.cal_bymonth, wer.cal_bymonthday,
      wer.cal_byday, wer.cal_bysetpos, wer.cal_byweekno,
      wer.cal_byyearday, wer.cal_wkst, wer.cal_count, wer.cal_endtime
      FROM webcal_entry we, webcal_entry_repeats wer, webcal_entry_user weu
      WHERE we.cal_id = wer.cal_id AND '
    : 'FROM webcal_entry we, webcal_entry_user weu WHERE ' )
   . 'we.cal_id = weu.cal_id AND weu.cal_status IN ( \'A\',\'W\' ) ';

  if ( $catlistcnt > 0 ) {
    $placeholders = '?' . str_repeat ( ',?', $catlistcnt - 1 );
    for ( $p_i = 0; $p_i < $catlistcnt; $p_i++ ) {
      $query_params[] = $catlist[$p_i];
    }

    if ( $cat_id > 0 )
      $sql .= 'AND we.cal_id IN ( ' . $placeholders . ' ) ';
    elseif ( $cat_id == -1 ) // Eliminate events with categories.
      $sql .= 'AND we.cal_id NOT IN ( ' . $placeholders . ' ) ';
  } else
  if ( ! empty ( $cat_id ) )
    // Force no rows to be returned. No matching entries in category.
    $sql .= 'AND 1 = 0 ';

  $sql .= 'AND we.cal_type IN ( '
   . ( $is_task == false
    ? '\'E\',\'M\' ) ' : '\'N\',\'T\' ) AND ( we.cal_completed IS NULL ) ' )
   . ( strlen ( $user ) > 0 ? 'AND ( weu.cal_login = ? ' : '' );

  $query_params[] = $user;

  if ( $user == $login && strlen ( $user ) > 0 && $layers ) {
    foreach ( $layers as $layer ) {
      $layeruser = $layer['cal_layeruser'];

      $sql .= 'OR weu.cal_login = ? ';
      $query_params[] = $layeruser;

      // While we are parsing the whole layers array, build ourselves
      // a new array that will help when we have to check for dups.
      $layers_byuser[$layeruser] = $layer['cal_dups'];
    }
  }

  $rows = dbi_get_cached_rows( $sql . ( $user == $login && strlen( $user )
    && $PUBLIC_ACCESS_DEFAULT_VISIBLE == 'Y'
      ? 'OR weu.cal_login = \'__public__\' ' : '' )
   . ( strlen( $user ) > 0 ? ') ' : '' ) . $date_filter . ' ORDER BY '
    // Order the results by time, then name if not tasks.
    // Must also order by cal_id, in case there are more than
    // one event in a month with the same name and time.
   . ( $is_task ? '' : 'we.cal_time, we.cal_name, ' )
   . 'we.cal_id', $query_params );

  if ( $rows ) {
    $i = 0;
    $checkdup_id = $first_i_this_id = -1;
    for ( $ii = 0, $cnt = count ( $rows ); $ii < $cnt; $ii++ ) {
      $row = $rows[$ii];
      if ( $row[9] == 'D' || $row[9] == 'R' )
        continue; // Don't show deleted/rejected ones.

      // Get primary category for this event, used for icon and color.
      $categories = get_categories_by_id ( $row[4], $user );
      $cat_keys = array_keys ( $categories );
      $primary_cat = ( empty ( $cat_keys[0] ) ? '' : $cat_keys[0] );

      if ( $login == '__public__' && ! empty ( $OVERRIDE_PUBLIC ) &&
        $OVERRIDE_PUBLIC == 'Y' ) {
        $evt_name = $OVERRIDE_PUBLIC_TEXT;
        $evt_descr = $OVERRIDE_PUBLIC_TEXT;
      } else {
        $evt_name = $row[0];
        $evt_descr = $row[1];
      }

      if ( $want_repeated && ! empty ( $row[20] ) ) // row[20] = cal_type
        $item = new RepeatingEvent( $evt_name, $evt_descr, $row[2], $row[3],
          $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], $row[10],
          $primary_cat, $row[11], $row[12], $row[13], $row[14], $row[15],
          $row[16], $row[17], $row[18], $row[19], $row[20], $row[21], $row[22],
          $row[23], $row[24], $row[25], $row[26], $row[27], $row[28], $row[29],
          $row[30], $row[31], $row[32], [], [], [] );
      else
        $item = new Event( $evt_name, $evt_descr, $row[2], $row[3], $row[4],
          $row[5], $row[6], $row[7], $row[8], $row[9], $row[10], $primary_cat,
          $row[11], $row[12], $row[13], $row[14], $row[15], $row[16], $row[17],
          $row[18], $row[19] );

      if( $item->getID() != $checkdup_id ) {
        $checkdup_id = $item->getID();
        $first_i_this_id = $i;
      }

      if( $item->getLogin() == $user ) {
        // Insert this one before all others with this ID.
        array_splice ( $result, $first_i_this_id, 0, [$item] );
        $i++;

        if ( $first_i_this_id + 1 < $i ) {
          // There's another one with the same ID as the one we inserted.
          // Check for dupe and if so, delete it.
          $other_item = $result[$first_i_this_id + 1];
          $tmp = $layers_byuser[$other_item->getLogin()];

          if( empty( $tmp ) || $tmp == 'N' ) {
            array_splice( $result, $first_i_this_id + 1, 1 );
            $i--;
          }
        }
      } else {
        $tmp = isset($layers_byuser[$item->getLogin()]) ? $layers_byuser[$item->getLogin()] : '';

        if( $i == $first_i_this_id || ( ! empty( $tmp ) && $tmp == 'Y' ) )
          // This item is either the first one with its ID, or dupes allowed.
          // Add it to the end of the array.
          $result[$i++] = $item;
      }
      // Does event go past midnight?
      if( date ( 'Ymd', $item->getDateTimeTS() )
          != date( 'Ymd', $item->getEndDateTimeTS() )
          && ! $item->isAllDay() && $item->getCalTypeName() == 'event' ) {
        getOverLap ( $item, $i, true );
        $i = count ( $result );
      }
    }
  }

  if ( $want_repeated ) {
    // Now load event exceptions/inclusions and store as array.

    // TODO:  Allow passing this max_until as param in case we create
    // a custom report that shows N years of events.
    if ( empty ( $max_until ) )
      $max_until = mktime ( 0, 0, 0, $thismonth + 2, 1, $thisyear );

    for ( $i = 0, $resultcnt = count ( $result ); $i < $resultcnt; $i++ ) {
      if( $result[$i]->getID() != '' ) {
        $rows = dbi_get_cached_rows ( 'SELECT cal_date, cal_exdate
          FROM webcal_entry_repeats_not
  WHERE cal_id = ?', [$result[$i]->getID()] );
        for ( $ii = 0, $rowcnt = count ( $rows ); $ii < $rowcnt; $ii++ ) {
          $row = $rows[$ii];
          // If this is not a clone, add exception date.
          if( ! $result[$i]->getClone() )
            $except_date = $row[0];

          if ( $row[1] == 1 )
            $result[$i]->addRepeatException( $except_date, $result[$i]->getID() );
          else
            $result[$i]->addRepeatInclusion ( $except_date );
        }
        // Get all dates for this event.
        // If clone, we'll get the dates from parent later.
        if( ! $result[$i]->getClone() ) {
          $until = ( $result[$i]->getRepeatEndDateTimeTS()
            ? $result[$i]->getRepeatEndDateTimeTS()
            : // Make sure all January dates will appear in small calendars.
            $max_until );

          // Try to minimize the repeat search by shortening
          // until if BySetPos is not used.
          if( ! $result[$i]->getRepeatBySetPos() && $until > $max_until )
            $until = $max_until;

          $rpt_count = 999; //Some BIG number.
          // End date... for year view and some reports we need whole year...
          // So, let's do up to 365 days after current month.
          // TODO:  Add this end time as a parameter in case someone creates
          // a custom report that asks for N years of events.
          // $jump = mktime ( 0, 0, 0, $thismonth -1, 1, $thisyear);
          if( $result[$i]->getRepeatCount() )
            $rpt_count = $result[$i]->getRepeatCount();

          $date = $result[$i]->getDateTimeTS();
          if( $result[$i]->isAllDay() || $result[$i]->isUntimed() )
            $date += 43200; //A simple hack to prevent DST problems.

          // TODO get this to work
          // C heck if this event id has been cached.
          // $file = '';
          // if ( ! empty( $db_connection_info['cachedir'] ) ) {
          // $hash = md5( $result[$i]->getId() . $until . $jump );
          // $file = $db_connection_info['cachedir'] . '/' . $hash . '.dat';
          // }
          // if ( file_exists ( $file ) ) {
          // $dates = unserialize( file_get_contents( $file ) );
          // } else {
          $dates = get_all_dates(
            $date,
            $result[$i]->getRepeatType(),
            $result[$i]->getRepeatFrequency(),
            $result[$i]->getRepeatByMonth(),
            $result[$i]->getRepeatByWeekNo(),
            $result[$i]->getRepeatByYearDay(),
            $result[$i]->getRepeatByMonthDay(),
            $result[$i]->getRepeatByDay(),
            $result[$i]->getRepeatBySetPos(),
            $rpt_count,
            $until,
            $result[$i]->getRepeatWkst(),
            $result[$i]->getRepeatExceptions(),
            $result[$i]->getRepeatInclusions(),
            $jumpdate );
          $result[$i]->addRepeatAllDates( $dates );
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
          if( count( $result[$i-1]->getRepeatAllDates() ) > 0 ) {
            $parentRepeats = $result[$i-1]->getRepeatAllDates();
            $cloneRepeats = [];
            for( $j = 0, $parentRepeatscnt = count( $parentRepeats );
                $j < $parentRepeatscnt; $j++ ) {
              $cloneRepeats[] = gmdate( 'Ymd',
                date_to_epoch( $parentRepeats[$j] ) + 86400 );
            }
            $result[$i]->addRepeatAllDates( $cloneRepeats );
          }
        }
      }
    }
  }
  return $result;
}

/**
 * Reads all the events for a user for the specified range of dates.
 *
 * This is only called once per page request to improve performance. All the
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
function read_events ( $user, $startdate, $enddate, $cat_id = '' ) {
  global $login;

  // Shift date/times to UTC.
  $start_date = gmdate ( 'Ymd', $startdate );
  $end_date = gmdate ( 'Ymd', $enddate );
  return query_events( $user, false, 'AND ( ( we.cal_date >= ' . $start_date
     . ' AND we.cal_date <= ' . $end_date
     . ' AND we.cal_time = -1 ) OR ( we.cal_date > ' . $start_date
     . ' AND we.cal_date < ' . $end_date . ' ) OR ( we.cal_date = ' . $start_date
     . ' AND we.cal_time >= ' . gmdate ( 'His', $startdate )
     . ' ) OR ( we.cal_date = ' . $end_date . ' AND we.cal_time <= '
     . gmdate ( 'His', $enddate ) . ' ) )', $cat_id );
}

/**
 * Reads all the repeated events for a user.
 *
 * This is only called once per page request to improve performance.
 * All the events get loaded into the array <var>$repeated_events</var>
 * sorted by time of day (not date).
 *
 * This will load all the repeated events into memory.
 *
 * <b>Notes:</b>
 * - To get which events repeat on a specific date, use
 *   {@link get_repeating_entries()}.
 * - To get all the dates that one specific event repeats on, call
 *   {@link get_all_dates()}.
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
function read_repeated_events ( $user, $date = '', $enddate = '', $cat_id = '' ) {
  global $jumpdate, $login, $max_until;

  // This date should help speed up things
  // by eliminating events that won't display anyway.
  $jumpdate = $date;
  $max_until = $enddate + 86400;
  if ( $date != '' )
    $date = gmdate ( 'Ymd', $date );

  return query_events ( $user, true, ( $date != ''
      ? 'AND ( wer.cal_end >= ' . $date . ' OR wer.cal_end IS NULL )' : '' ),
    $cat_id );
}

/**
 * Reads all the tasks for a user with due date within the specified date range.
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
function read_tasks ( $user, $duedate, $cat_id = '' ) {
  $due_date = gmdate ( 'Ymd', $duedate );
  return query_events( $user, false, 'AND ( ( we.cal_due_date <= ' . $due_date
     . ' ) OR ( we.cal_due_date = ' . $due_date . ' AND we.cal_due_time <= '
     . gmdate ( 'His', $duedate ) . ' ) )', $cat_id, true );
}

/**
 * Generates a cookie that saves the last calendar view.
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

/**
 * This just sends the DOCTYPE used in a lot of places in the code.
 *
 * @param string  lang
 */
function send_doctype ( $doc_title = '' ) {
  global $charset, $lang, $LANGUAGE;

  $lang = ( empty ( $LANGUAGE ) ? '' : languageToAbbrev ( $LANGUAGE ) );
  if ( empty ( $lang ) )
    $lang = 'en';

  $charset = ( empty ( $LANGUAGE ) ? 'iso-8859-1' : translate ( 'charset' ) );

  return '<?xml version="1.0" encoding="' . $charset . '"?' . '>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $lang . '" lang="'
   . $lang . '">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=' . $charset
   . '" />' . ( empty ( $doc_title ) ? '' : '
    <title>' . $doc_title . '</title>' );
}

/**
 * Sends an HTTP login request to the browser and stops execution.
 *
 * @global string  name of language file
 * @global string  Application Name
 *
 */
function send_http_login() {
  global $lang_file;

  if ( strlen ( $lang_file ) ) {
    $not_authorized = print_not_auth();
    $title = translate ( 'Title' );
    $unauthorized = translate ( 'Unauthorized' );
  } else {
    $not_authorized = 'You are not authorized';
    $title = 'WebCalendar';
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

/**
 * Sends HTTP headers that tell the browser not to cache this page.
 *
 * Different browsers use different mechanisms for this,
 * so a series of HTTP header directives are sent.
 *
 * <b>Note:</b>  This function needs to be called before any HTML output is sent
 *               to the browser.
 */
function send_no_cache_header() {
  header ( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
  header ( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s' ) . ' GMT' );
  header ( 'Cache-Control: no-store, no-cache, must-revalidate' );
  header ( 'Cache-Control: post-check=0, pre-check=0', false );
  header ( 'Pragma: no-cache' );
}

/**
 * Sends a redirect to the user's preferred view.
 *
 * The user's preferred view is stored in the $STARTVIEW global variable.
 * This is loaded from the user preferences (or system settings
 * if there are no user prefererences.)
 *
 * @param string $indate  Date to pass to preferred view in YYYYMMDD format
 * @param string $args    Arguments to include in the URL (such as "user=joe")
 */
function send_to_preferred_view ( $indate = '', $args = '' ) {
  do_redirect ( get_preferred_view ( $indate, $args ) );
}

/**
 * Set an environment variable if system allows it.
 *
 * @param string $val      name of environment variable
 * @param string $setting  value to assign
 *
 * @return bool  true = success false = not allowed.
 */
function set_env ( $val, $setting ) {
  global $tzOffset;
  global $tzInitSet;

  // Set SERVER TIMEZONE.
  if ( ! $tzInitSet ) {
    if ( empty ( $GLOBALS['TIMEZONE'] ) )
      $GLOBALS['TIMEZONE'] = $GLOBALS['SERVER_TIMEZONE'];
    if ( function_exists ( "date_default_timezone_set" ) )
      date_default_timezone_set ( $GLOBALS['TIMEZONE'] );
  }

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
    $tzOffset = ( ! $can_setTZ ? substr ( $setting, 12 ) * 3600 : 0 );
    // Some say this is required to properly init timezone changes.
    mktime ( 0, 0, 0, 1, 1, 1970 );
  }

  return $ret;
}

/**
 * Determines what the day is and sets it globally.
 * All times are in the user's timezone
 *
 * The following global variables will be set:
 * - <var>$thisyear</var>
 * - <var>$thismonth</var>
 * - <var>$thisday</var>
 * - <var>$thisdate</var>
 * - <var>$today</var>
 *
 * @param string $date  The date in YYYYMMDD format
 */
function set_today ( $date = '' ) {
  global $day, $month, $thisdate, $thisday, $thismonth, $thisyear, $today, $year;

  $today = time();

  if ( empty ( $date ) ) {
    $thisyear = ( empty ( $year ) ? date ( 'Y', $today ) : $year );
    $thismonth = ( empty ( $month ) ? date ( 'm', $today ) : $month );
    $thisday = ( empty ( $day ) ? date ( 'd', $today ) : $day );
  } else {
    $thisyear = substr ( $date, 0, 4 );
    $thismonth = substr ( $date, 4, 2 );
    $thisday = substr ( $date, 6, 2 );
  }
  $thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
}

/**
 * Sorts the combined event arrays by timestamp then name.
 *
 * <b>Note:</b> This is a user-defined comparison function for usort().
 *
 * @params passed automatically by usort, don't pass them in your call
 */
function sort_events ( $a, $b ) {
  // Handle untimed events first.
  if( $a->isUntimed() || $b->isUntimed() )
    return strnatcmp( $b->isUntimed(), $a->isUntimed() );

  $retval = strnatcmp (
    display_time( '', 0, $a->getDateTimeTS(), 24 ),
    display_time( '', 0, $b->getDateTimeTS(), 24 ) );

  return ( $retval ? $retval : strnatcmp( $a->getName(), $b->getName() ) );
}

/**
 * Sorts the combined event arrays by timestamp then name (case insensitive).
 *
 * <b>Note:</b> This is a user-defined comparison function for usort().
 *
 * @params passed automatically by usort, don't pass them in your call.
 */
function sort_events_insensitive ( $a, $b ) {
  $retval = strnatcmp (
    display_time( '', 0, $a->getDateTimeTS(), 24 ),
    display_time( '', 0, $b->getDateTimeTS(), 24 ) );

  return ( $retval
    ? $retval
    : strnatcmp( strtolower( $a->getName() ), strtolower( $b->getName() ) ) );
}

/**
 * Sort user array based on $USER_SORT_ORDER.
 * <b>Note:</b> This is a user-defined comparison function for usort()
 * that will be called from user-xxx.php.
 * @TODO:  Move to user.php along with migration to user.class.
 *
 * @params passed automatically by usort, don't pass them in your call.
 */
function sort_users ( $a, $b ) {
  global $USER_SORT_ORDER;

  $first = strnatcmp ( strtolower ( $a['cal_firstname'] ),
    strtolower ( $b['cal_firstname'] ) );
  $last = strnatcmp ( strtolower ( $a['cal_lastname'] ),
    strtolower ( $b['cal_lastname'] ) );

  return ( ( empty ( $USER_SORT_ORDER )
      ? 'cal_lastname, cal_firstname,'
      : "$USER_SORT_ORDER," ) == 'cal_lastname, cal_firstname,'
    ? ( empty ( $last ) ? $first : $last )
    : ( empty ( $first ) ? $last : $first ) );
}

/**
 * Converts a time format HHMMSS (like 130000 for 1PM)
 * into number of minutes past midnight.
 *
 * @param string $time  Input time in HHMMSS format
 *
 * @return int  The number of minutes since midnight.
 */
function time_to_minutes ( $time ) {
  return intval ( $time / 10000 ) * 60 + intval ( ( $time / 100 ) % 100 );
}

/**
 * Checks to see if two events overlap.
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

/**
 * Updates event status and logs activity
 *
 * @param string $status  A,D,R,W to set cal_status
 * @param string $user    user to apply changes to
 * @param int    $id      event id
 * @param string $type    event type for logging
 *
 * @global string logged in user
 * @global string current error message
 */
function update_status ( $status, $user, $id, $type = 'E' ) {
  global $error, $login;

  if ( empty ( $status ) )
    return;

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
      $error_msg = translate ( 'Error approving event XXX.' );
      break;
    case 'D':
      $log_type = constant ( 'LOG_DELETE' . $log_type );
      $error_msg = translate ( 'Error deleting event XXX.' );
      break;
    case 'R':
      $log_type = constant ( 'LOG_REJECT' . $log_type );
      $error_msg = translate ( 'Error rejecting event XXX.' );
  }

  if ( ! dbi_execute ( 'UPDATE webcal_entry_user SET cal_status = ?
  WHERE cal_login = ?
    AND cal_id = ?', [$status, $user, $id] ) )
    $error = str_replace( 'XXX', dbi_error(), $error_msg );
  else
    activity_log ( $id, $login, $user, $log_type, '' );
}

/**
 * Checks the webcal_nonuser_cals table to determine if the user is the
 * administrator for the nonuser calendar.
 *
 * @param string $login    Login of user that is the potential administrator
 * @param string $nonuser  Login name for nonuser calendar
 *
 * @return bool  True if the user is the administrator for the nonuser calendar.
 */
function user_is_nonuser_admin ( $login, $nonuser ) {
  $rows = dbi_get_cached_rows ( 'SELECT cal_admin FROM webcal_nonuser_cals
  WHERE cal_login = ?
    AND cal_admin = ?', [$nonuser, $login] );
  return ( $rows && ! empty ( $rows[0] ) );
}

/**
 * Determine if the specified user is a participant in the event.
 * User must have status 'A' or 'W'.
 *
 * @param int    $id    event id
 * @param string $user  user login
 */
function user_is_participant ( $id, $user ) {
  $ret = false;

  $rows = dbi_get_cached_rows ( 'SELECT COUNT( cal_id ) FROM webcal_entry_user
    WHERE cal_id = ? AND cal_login = ? AND cal_status IN ( \'A\',\'W\' )',
    [$id, $user] );
  if ( ! $rows )
    die_miserable_death( str_replace( 'XXX', dbi_error(),
        translate ( 'Database error XXX.' ) ) );

  if ( ! empty ( $rows[0] ) ) {
    $row = $rows[0];
    if ( ! empty ( $row ) )
      $ret = ( $row[0] > 0 );
  }

  return $ret;
}

/**
 * Checks to see if user's IP in in the IP Domain
 * specified by the /includes/blacklist.php file
 *
 * @return bool  Is user's IP in required domain?
 *
 * @see /includes/blacklist.php
 * @todo:  There has to be a way to vastly improve on this logic.
 */
function validate_domain() {
  global $SELF_REGISTRATION_BLACKLIST;

  if ( empty ( $SELF_REGISTRATION_BLACKLIST ) || $SELF_REGISTRATION_BLACKLIST == 'N' )
    return true;

  $allow_true = $deny_true = [];
  $ip_authorized = false;
  $rmt_long = ip2long ( $_SERVER['REMOTE_ADDR'] );
  $fd = @fopen ( 'includes/blacklist.php', 'rb', false );
  if ( ! empty ( $fd ) ) {
    // We don't use fgets() since it seems to have problems with Mac-formatted
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

/**
 * Returns either the full name or the abbreviation of the day.
 *
 * @param int     $w       Number of the day in the week (0=Sun,...,6=Sat)
 * @param string  $format  'l' (lowercase L) = Full, 'D' = abbreviation.
 *
 * @return string The weekday name ("Sunday" or "Sun")
 */
function weekday_name ( $w, $format = 'l' ) {
  global $lang;
  static $local_lang, $week_names, $weekday_names;

  // We may have switched languages.
  if ( $local_lang != $lang )
    $week_names = $weekday_names = [];

  $local_lang = $lang;

  // We may pass $DISPLAY_LONG_DAYS as $format.
  if ( $format == 'N' )
    $format = 'D';

  if ( $format == 'Y' )
    $format = 'l';

  if ( empty ( $weekday_names[0] ) || empty ( $week_names[0] ) ) {
    $weekday_names = [
      translate ( 'Sunday' ),
      translate ( 'Monday' ),
      translate ( 'Tuesday' ),
      translate ( 'Wednesday' ),
      translate ( 'Thursday' ),
      translate ( 'Friday' ),
      translate ( 'Saturday' )];

    $week_names = [
      translate ( 'Sun' ),
      translate ( 'Mon' ),
      translate ( 'Tue' ),
      translate ( 'Wed' ),
      translate ( 'Thu' ),
      translate ( 'Fri' ),
      translate ( 'Sat' )];
  }

  if ( $w >= 0 && $w < 7 )
    return ( $format == 'l' ? $weekday_names[$w] : $week_names[$w] );

  return translate ( 'unknown-weekday' ) . " ($w)";
}

/* ****************************************************************************
 *     Functions for getting information about boss and their assistants.     *
 **************************************************************************** */

/**
 * Checks the boss user preferences to see if the boss must approve events
 * added to their calendar.
 *
 * @param string $assistant  Assistant login
 * @param string $boss       Boss login
 *
 * @return bool  True if the boss must approve new events.
 */
function boss_must_approve_event ( $assistant, $boss ) {
  if ( user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, 'APPROVE_ASSISTANT_EVENT' ) == 'Y'
      ? true : false );

  return true;
}

/**
 * Checks the boss user preferences to see if the boss wants to be notified via
 * email on changes to their calendar.
 *
 * @param string $assistant  Assistant login
 * @param string $boss       Boss login
 *
 * @return bool  True if the boss wants email notifications.
 */
function boss_must_be_notified ( $assistant, $boss ) {
  if ( user_is_assistant ( $assistant, $boss ) )
    return ( get_pref_setting ( $boss, 'EMAIL_ASSISTANT_EVENTS' ) == 'Y'
      ? true : false );

  return true;
}

/**
 * Is this user an assistant of this boss?
 *
 * @param string $assistant  Login of potential assistant
 * @param string $boss       Login of potential boss
 *
 * @return bool  True or false.
 */
function user_is_assistant ( $assistant, $boss ) {
  if ( empty ( $boss ) )
    return false;

  $ret = false;
  $rows = dbi_get_cached_rows ( 'SELECT * FROM webcal_asst
  WHERE cal_assistant = ?
    AND cal_boss = ?', [$assistant, $boss] );
  if ( $rows ) {
    $row = $rows[0];

    if ( ! empty ( $row[0] ) )
      $ret = true;
  }
  return $ret;
}

/**
 * Gets a list of an assistant's boss from the webcal_asst table.
 *
 * @param string $assistant Login of assistant
 *
 * @return array  Array of bosses,
 *                where each boss is an array with the following fields:
 * - <var>cal_login</var>
 * - <var>cal_fullname</var>
 */
function user_get_boss_list ( $assistant ) {
  global $bosstemp_fullname;

  $count = 0;
  $ret = [];
  $rows = dbi_get_cached_rows ( 'SELECT cal_boss FROM webcal_asst
  WHERE cal_assistant = ?', [$assistant] );
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      if (!user_load_variables ( $row[0], 'bosstemp_' ))
        nonuser_load_variables($row[0], 'bosstemp_');
      $ret[$count++] = [
        'cal_login' => $row[0],
        'cal_fullname' => $bosstemp_fullname];
    }
  }
  return $ret;
}

/**
 * Is this user an assistant?
 *
 * @param string $assistant  Login for user
 *
 * @return bool  true if the user is an assistant to one or more bosses.
 */
function user_has_boss ( $assistant ) {
  $ret = false;
  $rows = dbi_get_cached_rows ( 'SELECT * FROM webcal_asst
  WHERE cal_assistant = ?', [$assistant] );
  if ( $rows ) {
    $row = $rows[0];
     if ( ! empty ( $row[0] ) )
      $ret = true;
  }
  return $ret;
}

/* ****************************************************************************
 *                       Functions to handle site_extras                      *
 **************************************************************************** */

/**
 * Builds the HTML for the entry popup.
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
  $site_extras = '', $location = '', $name = '', $id = '', $reminder = '' ) {
  global $ALLOW_HTML_DESCRIPTION, $DISABLE_POPUPS, $login,
  $PARTICIPANTS_IN_POPUP, $popup_fullnames, $popuptemp_fullname,
  $PUBLIC_ACCESS_VIEW_PART, $SUMMARY_LENGTH, $tempfullname;

  if ( ! empty ( $DISABLE_POPUPS ) && $DISABLE_POPUPS == 'Y' )
    return;

  // Restrict info if time only set.
  $details = true;
  if ( function_exists ( 'access_is_enabled' ) &&
      access_is_enabled() && $user != $login ) {
    $time_only = access_user_calendar ( 'time', $user );
    $details = ( $time_only == 'N' ? 1 : 0 );
  }

  $ret = '<dl id="' . $popupid . '" class="popup">' . "\n";

  if ( empty ( $popup_fullnames ) )
    $popup_fullnames = [];

  $partList = [];
  if ( $details && $id != '' && !
    empty ( $PARTICIPANTS_IN_POPUP ) && $PARTICIPANTS_IN_POPUP == 'Y' && !
      ( $PUBLIC_ACCESS_VIEW_PART == 'N' && $login == '__public__' ) ) {
    $rows = dbi_get_cached_rows ( 'SELECT cal_login, cal_status
  FROM webcal_entry_user
  WHERE cal_id = ?
    AND cal_status IN ( "A","W" )', [$id] );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $participants[] = $row;
      }
    }
    for ( $i = 0, $cnt = count ( $participants ); $i < $cnt; $i++ ) {
      user_load_variables ( $participants[$i][0], 'temp' );
      $partList[] = $tempfullname . ' '
       . ( $participants[$i][1] == 'W' ? '(?)' : '' );
    }
    $rows = dbi_get_cached_rows ( 'SELECT cal_fullname FROM webcal_entry_ext_user
  WHERE cal_id = ?
  ORDER by cal_fullname', [$id] );
    if ( $rows ) {
      $extStr = translate ( 'External User' );
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $partList[] = $row[0] . ' (' . $extStr . ')';
      }
    }
  }

  if ( $user != $login ) {
    if ( empty ( $popup_fullnames[$user] ) ) {
      user_load_variables ( $user, 'popuptemp_' );
      $popup_fullnames[$user] = $popuptemp_fullname;
    }
    $ret .= '<dt>' . translate ( 'User' )
     . ":</dt>\n<dd>$popup_fullnames[$user]</dd>\n";
  }
  $ret .= ( $SUMMARY_LENGTH < 80 && strlen ( $name ) && $details
    ? '<dt>' . htmlspecialchars ( substr ( $name, 0, 40 ) ) . "</dt>\n" : '' )
   . ( strlen ( $time )
    ? '<dt>' . translate ( 'Time' ) . ":</dt>\n<dd>$time</dd>\n" : '' )
   . ( ! empty ( $location ) && $details
    ? '<dt>' . translate ( 'Location' ) . ":</dt>\n<dd> $location</dd>\n" : '' )
   . ( ! empty ( $reminder ) && $details
    ? '<dt>' . translate ( 'Send Reminder' ) . ":</dt>\n<dd> $reminder</dd>\n" : '' );

  if ( ! empty ( $partList ) && $details ) {
    $ret .= '<dt>' . translate ( 'Participants' ) . ":</dt>\n";
    foreach ( $partList as $parts ) {
      $ret .= "<dd> $parts</dd>\n";
    }
  }

  if ( ! empty ( $description ) && $details ) {
    $ret .= '<dt>' . translate ( 'Description' ) . ":</dt>\n<dd>";
    if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) && $ALLOW_HTML_DESCRIPTION == 'Y' ) {
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

    $ret .= "</dd>\n";
  } //if $description
  return $ret . ( empty ( $site_extras ) ? '' : $site_extras ) . "</dl>\n";
}

/**
 * Formats site_extras for display according to their type.
 *
 * This will return an array containing formatted extras indexed on their
 * unique names. Each formatted extra is another array containing two
 * indices: 'name' and 'data', which hold the name of the site_extra and the
 * formatted data, respectively. So, to access the name and data of an extra
 * uniquely name 'Reminder', you would access
 * <var>$array['Reminder']['name']</var> and
 * <var>$array['Reminder']['data']</var>
 *
 * @param array $extras  Array of site_extras for an event as returned by
 *                       {@link get_site_extra_fields()}
 * @param int   $filter  CONSTANT 'view settings' values from site_extras.php
 *
 * @return array  Array of formatted extras.
 */
function format_site_extras ( $extras, $filter = '' ) {
  global $site_extras;

  if ( empty ( $site_extras ) || empty ( $extras ) )
    return;

  $ret = [];
  $extra_view = 1;
  foreach ( $site_extras as $site_extra ) {
    $data = '';
    $extra_name = $site_extra[0];
    $extra_desc = $site_extra[1];
    $extra_type = $site_extra[2];
    $extra_arg1 = $site_extra[3];
    $extra_arg2 = $site_extra[4];
    if ( ! empty ( $site_extra[5] ) && ! empty ( $filter ) )
      $extra_view = $site_extra[5] & $filter;
    if ( ! empty ( $extras[$extra_name] ) && !
        empty ( $extras[$extra_name]['cal_name'] ) && ! empty ( $extra_view ) ) {
      $name = translate ( $extra_desc );

      if ( $extra_type == EXTRA_DATE ) {
        if ( $extras[$extra_name]['cal_date'] > 0 )
          $data = date_to_str ( $extras[$extra_name]['cal_date'] );
      } elseif ( $extra_type == EXTRA_TEXT || $extra_type == EXTRA_MULTILINETEXT )
        $data = nl2br ( $extras[$extra_name]['cal_data'] );
      elseif ( $extra_type == EXTRA_RADIO && !
        empty ( $extra_arg1[$extras[$extra_name]['cal_data']] ) )
        $data .= $extra_arg1[$extras[$extra_name]['cal_data']];
      else
        $data .= $extras[$extra_name]['cal_data'];

      $ret[$extra_name] = ['name' => $name, 'data' => $data];
    }
  }
  return $ret;
}

/**
 * Gets any site-specific fields for an entry that are stored in the database
 * in the webcal_site_extras table.
 *
 * @param int $eventid  Event ID
 *
 * @return array  Array with the keys as follows:
 *   - <var>cal_name</var>
 *   - <var>cal_type</var>
 *   - <var>cal_date</var>
 *   - <var>cal_remind</var>
 *   - <var>cal_data</var>
 */
function get_site_extra_fields ( $eventid ) {
  $rows = dbi_get_cached_rows ( 'SELECT cal_name, cal_type, cal_date, cal_remind, cal_data
  FROM webcal_site_extras
  WHERE cal_id = ?', [$eventid] );
  $extras = [];
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      // Save by cal_name (e.g. "URL").
      $extras[$row[0]] = [
        'cal_name' => $row[0],
        'cal_type' => $row[1],
        'cal_date' => $row[2],
        'cal_remind' => $row[3],
        'cal_data' => $row[4]];
    }
  }
  return $extras;
}

/**
 * Extract the names of all site_extras.
 *
 * @param int $filter  CONSTANT 'view setting' from site_extras.php
 *
 * @return array  Array of site_extras names.
 */
function get_site_extras_names ( $filter = '' ) {
  global $site_extras;

  $ret = [];

  foreach ( $site_extras as $extra ) {
    if ( $extra == 'FIELDSET' ||
      ( ! empty ( $extra[5] ) && ! empty ( $filter ) && !
          ( $extra[5] & $filter ) ) )
      continue;

    $ret[] = $extra[0];
  }

  return $ret;
}

/**
 * Generates the HTML used in an event popup for the site_extras fields.
 *
 * @param int $id  Event ID
 *
 * @return string  The HTML to be used within the event popup for any site_extra
 *                 fields found for the specified event.
 */
function site_extras_for_popup ( $id ) {
  global $SITE_EXTRAS_IN_POPUP;

  if ( $SITE_EXTRAS_IN_POPUP != 'Y' )
    return '';

  $extras = format_site_extras ( get_site_extra_fields ( $id ), EXTRA_DISPLAY_POPUP );
  if ( empty ( $extras ) )
    return '';

  $ret = '';

  foreach ( $extras as $extra ) {
    $ret .= '<dt>' . $extra['name'] . ":</dt>\n<dd>" . $extra['data'] . "</dd>\n";
  }

  return $ret;
}

// Print a box with an error message and a nice error icon.
function print_error_box ( $msg )
{
  echo '<div class="warningBox">' .
    '<table><tr><td class="alignmiddle">' .
    '<img src="images/warning.png" width="40" height="40" class="alignmiddle" alt="' .
    translate ( 'Error' ) . '" /></td><td class="alignmiddle">' .
    translate('The permissions for the icons directory are set to read-only') .
    "</td></tr></table></div>\n";
}

// Convert an HTML color ('#ff00ff') into an array of red/green/blue values
// of 0 to 255.
function html2rgb($color)
{
  if ($color[0] == '#')
    $color = substr($color, 1);

  if (strlen($color) == 6) {
    list($r, $g, $b) = [$color[0].$color[1], $color[2].$color[3], $color[4].$color[5]];
  } elseif (strlen($color) == 3) {
    list($r, $g, $b) = [$color[0].$color[0], $color[1].$color[1], $color[2].$color[2]];
  } else {
    return false;
  }

  $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

  return [$r, $g, $b];
}

// Convert RGB values (0-255) into HTML color ('#ffffff')
function rgb2html($r, $g=-1, $b=-1)
{
  if (is_array($r) && sizeof($r) == 3)
    list($r, $g, $b) = $r;

  $r = intval($r); $g = intval($g);
  $b = intval($b);

  $r = dechex($r<0?0:($r>255?255:$r));
  $g = dechex($g<0?0:($g>255?255:$g));
  $b = dechex($b<0?0:($b>255?255:$b));

  $color = (strlen($r) < 2?'0':'').$r;
  $color .= (strlen($g) < 2?'0':'').$g;
  $color .= (strlen($b) < 2?'0':'').$b;
  return '#'.$color;
}

/**
  * Require a valid HTT_REFERER value in the HTTP header.  This will
  * prevent XSRF (cross-site request forgery).
  *
  * For example, suppose a * a "bad guy" sends an email with a link that
  * would delete an event in webcalendar to the admin.  If the admin user
  * clicks on that link we don't want to actually delete the event.
  */
function require_valid_referring_url ()
{
  global $SERVER_URL, $settings;

  // Allow value in settings.php to disable this.  If you run PHP
  // inside a docker container, you will need to do this since the IP
  // address will be different.
  if ( isset ( $settings['disable_referer_check'] ) &&
    $settings['disable_referer_check'] == 'true' ) {
    return;
  }

  if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
    // Missing the REFERER value
    //die_miserable_death ( translate ( 'Invalid referring URL' ) );
    // Unfortunately, some version of MSIE do not send this info.
    return;
  }
  if ( ! preg_match ( "@$SERVER_URL@", $_SERVER['HTTP_REFERER'] ) ) {
    // Gotcha.  URL of referring page is not the same as our server.
    // This can be an instance of XSRF.
    // (This may also happen when more than address is used for your server.
    // However, you're not supposed to do that with this version of
    // WebCalendar anyhow...)
    die_miserable_death ( translate ( 'Invalid referring URL' ) );
  }
}

?>

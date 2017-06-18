<?php
/**
* All of WebCalendar's ical/vcal functions
*
* @author Craig Knudsen <cknudsen@cknudsen.com>
* @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
* @license http://www.gnu.org/licenses/gpl.html GNU GPL
* @version $Id: xcal.php,v 1.79.2.19 2008/09/27 15:00:06 cknudsen Exp $
* @package WebCalendar
*/

/*
 * Generate Product ID string
 *
 */
function generate_prodid ( $type='' ) {
  global $PROGRAM_VERSION, $PROGRAM_NAME;
  $ret = 'PRODID:-//WebCalendar-' .$type . '-' ;
  if ( ! empty ( $PROGRAM_VERSION ) )
    $ret .= $PROGRAM_VERSION;
  else if ( preg_match ( "/WebCalendar v(\S+)/", $PROGRAM_NAME, $match ) )
    $ret .= $match[1];
  else
    $ret .= "UnknownVersion";

  $ret .= "\r\n";
  return $ret;
}
/*
 * Export a quoted Printable String
 */

function export_quoted_printable_encode( $car ) {
  $res = '';

  if ( ( ord ( $car ) >= 33 && ord ( $car ) <= 60 ) ||
    ( ord ( $car ) >= 62 && ord ( $car ) <= 126 ) ||
      ord ( $car ) == 9 || ord ( $car ) == 32 )
    $res = $car;
  else
    $res = sprintf ( "=%02X", ord ( $car ) );
   //end if

  return $res;
} //end function export_quoted_printable_encode
function export_fold_lines ( $string, $encoding = 'none', $limit = 76 ) {
  global $enable_mbstring;

  if ($enable_mbstring) {
    $res = mb_export_fold_lines ( $string, $encoding, $limit);
  } else {
    $res = wc_export_fold_lines ( $string, $encoding, $limit);
  }
  return $res;
}

function mb_export_fold_lines ( $string, $encoding = 'none', $limit = 76 ) {
  $res = array();
  $line = '';
  mb_language('japanese');
  mb_internal_encoding('UTF-8');

  $line = mb_strcut($string, 0, $limit);    // multibyte operation
  $string = substr($string, strlen($line)); // siglebyte(bytestream) operation
  $res[] = $line;

  while (0 < mb_strlen($string)) {
    $line = " " . mb_strcut($string, 0, $limit - 1);
    $string = substr($string, strlen($line) - 1);

    $res[] = $line;
  }
  return $res;
}

function wc_export_fold_lines ( $string, $encoding = 'none', $limit = 76 ) {
  $len = strlen ( $string );
  $fold = $limit;
  $res = array ();
  $row = '';
  $enc = '';
  $lwsp = 0; // position of the last linear whitespace (where to fold)
  $res_ind = 0; // row index
  $start_encode = 0; // we start encoding only after the ': ' character is encountered
  if ( strcmp( $encoding, 'quotedprintable' ) == 0 )
    $fold--; // must take into account the soft line break
 for ( $i = 0; $i < $len; $i++ ) {
    $enc = $string[$i];

    if ( $start_encode ) {
      if ( strcmp( $encoding, 'quotedprintable' ) == 0 )
        $enc = export_quoted_printable_encode( $string[$i] );
      else if ( strcmp( $encoding, 'utf8' ) == 0 )
        $enc = utf8_encode ( $string[$i] );
    }
    if ( $string[$i] == ':' )
      $start_encode = 1;

    if ( ( strlen ( $row ) + strlen ( $enc ) ) > $fold ) {
      $delta = 0;

      if ( $lwsp == 0 )
        $lwsp = $fold - 1; // the folding will occur in the middle of a word
      if ( $row[$lwsp] == ' ' || $row[$lwsp] == "\t" )
        $delta = -1;

      $res[$res_ind] = substr ( $row, 0, $lwsp + 1 + $delta );

      if ( strcmp( $encoding, 'quotedprintable' ) == 0 )
        $res[$res_ind] .= '='; // soft line break;
      $row = substr ( $row, $lwsp + 1 );

      $row = ' ' . $row;

      if ( $delta == -1 && strcmp( $encoding, 'utf8' ) == 0 )
        $row = ' ' . $row;

      if ( $res_ind == 0 )
        $fold--; //reduce row length of 1 to take into account the whitespace
      // at the beginning of lines
      $res_ind++; // next line
      $lwsp = 0;
    } //end if ((strlen ($row) + strlen ($enc)) > $fold)
    $row .= $enc;

    if ( $string[$i] == ' ' || $string[$i] == "\t" || $string[$i] == ';' ||
      $string[$i] == ',' )
      $lwsp = strlen ( $row ) - 1;

    if ( $string[$i] == ':' && ( strcmp( $encoding, 'quotedprintable' ) == 0 ) )
      $lwsp = strlen ( $row ) - 1; // We cut at ':' only for quoted printable.
  } //end for ($i = 0; $i < $len; $i++)
  $res[$res_ind] = $row; // Add last row (or first if no folding is necessary)
  return $res;
} //end function wc_export_fold_lines ($string, $encoding="none", $limit=76)

function search_users($arrInArray, $varSearchValue){
  foreach ($arrInArray as $key => $row){
    if ($row['cal_login'] == $varSearchValue) {
      return $key;
    }
  }
  return -1;
}

function export_get_attendee( $id, $export ) {
  global $login, $EMAIL_FALLBACK_FROM;

  $request = 'SELECT weu.cal_login, weu.cal_status, we.cal_create_by
    FROM webcal_entry_user weu LEFT JOIN  webcal_entry we
    ON weu.cal_id = we.cal_id
    WHERE weu.cal_id = ? AND weu.cal_status <> \'D\'';

  $att_res = dbi_execute ( $request, array ( $id ) );

  $count = 0;

  $attendee = array ();
  $entry_array = array ();

  while ( $entry = dbi_fetch_row( $att_res ) ) {
    $entry_array[$count++] = $entry;
  }

  dbi_free_result ( $att_res );

  $count = 0;

  $userlist = user_get_users ();

  while ( list ( $key, $row ) = each ( $entry_array ) ) {
    // $user[0] = cal_firstname, cal_lastname, cal_email, cal_login
    $userPos = search_users($userlist, $row[0]);
    if ($userPos == -1) {
      continue;
    } else {
      $user = $userlist[$userPos];
      $attendee[$count] = 'ATTENDEE;ROLE=';
      if ( strcmp( $export, 'vcal' ) == 0 )
      $attendee[$count] .= ( $row[0] == $row[2] ) ? 'OWNER;': 'ATTENDEE;';
      else
        $attendee[$count] .= ( $row[0] == $row[2] ) ? 'CHAIR;': 'REQ-PARTICIPANT;';      
      if ( strcmp( $export, 'vcal' ) == 0 )
        $attendee[$count] .= 'STATUS=';
      else
      $attendee[$count] .= 'PARTSTAT=';

      switch ( $row[1] ) {
        case 'A':
          $attendee[$count] .= 'ACCEPTED';
          break;
        case 'R':
          $attendee[$count] .= 'DECLINED';
          break;
        case 'W':
          $attendee[$count] .= 'NEEDS-ACTION';
          break;
        default:
          continue;
      } //end switch
      if ( strcmp( $export, 'vcal' ) == 0 ) {
        $attendee[$count] .= ';ENCODING=QUOTED-PRINTABLE:';
        if ( empty ( $user['cal_firstname'] ) && empty ( $user['cal_lastname'] ) )
          $attendee[$count] .= $user['cal_login'] .'"';
        else
          $attendee[$count] .= $user['cal_firstname']
           . ' ' .  $user['cal_lastname'];  
        if ( ! empty ( $user['cal_email'] ) )
          $attendee[$count]  .= '<' . $user['cal_email'] . '>'; 
        else 
          $attendee[$count]  .= '<' . $EMAIL_FALLBACK_FROM . '>';   
      } else {
      // Use "Full Name <email>" if we have it, just "login" if that's all
      // we have.
      if ( empty ( $user['cal_firstname'] ) && empty ( $user['cal_lastname'] ) )
          $attendee[$count] .= ';CN="' . $user['cal_login'] .'"';
      else
        $attendee[$count] .= ';CN="' . utf8_encode($user['cal_firstname']) 
          . ' ' .  utf8_encode($user['cal_lastname']).'"';
      if ( ! empty ( $user['cal_email'] ) )
        $attendee[$count]  .= ':MAILTO:' . $user['cal_email'];
        else 
          $attendee[$count]  .= ':MAILTO:' . $EMAIL_FALLBACK_FROM;
      }
      $count++;
    } //end if ( count ( $user ) > 0 )
  } //end while
  return $attendee;
} //end function export_get_attendee($id, $export)

// All times are now stored in UTC time, so no conversions are needed
// other than formatting
//
// NOTE: Forcing the DTEND to include a 'T000000' as a DATETIME rather
// than just a DATE is needed to avoid a bug in Sunbird 0.7.  If the
// DTSTART has a DATETIME and the DTEND is just DATE, then Sunbird locks up.
function export_time ( $date, $duration, $time, $texport, $vtype = 'E' ) {
  global $TIMEZONE, $vtimezone_data, $use_vtimezone;
  $ret = $vtimezone_exists = '';
  $eventstart = date_to_epoch ( $date . ( $time > 0 ? $time : 0 ), $time>0 );
  $eventend = $eventstart + ( $duration * 60 );
  if ( $time == 0 && $duration == 1440 && strcmp( $texport, 'ical' ) == 0  ) {
    // all day.
    if ( $use_vtimezone && ( $vtimezone_data = get_vtimezone ( $TIMEZONE, $dtstart ) ) ) {
      $vtimezone_exists = true;
      $dtstart = $date . 'T000000';
      $ret .= 'DTSTART;TZID=' . $TIMEZONE . ':' . $dtstart. "\r\n";
     }else
      $ret .= "DTSTART;VALUE=DATE:$date\r\n";
  } else if ( $time == -1 ) {
    // untimed event: this is the same regardless of timezone. For example,
    // New Year's Day starts at 12am localtime regardless of timezone.
    $ret .= "DTSTART;VALUE=DATE:$date\r\n";
  } else {
    // timed  event
    $utc_start = export_ts_utc_date ( $eventstart );
    $dtstart = $date . 'T000000';
    if ( $use_vtimezone && ( $vtimezone_data = get_vtimezone ( $TIMEZONE, $dtstart ) ) ) {
      $vtimezone_exists = true;
      $ret .= 'DTSTART;TZID=' . $TIMEZONE . ':' . $utc_start . "\r\n";
    } else {
    $ret .= "DTSTART:$utc_start\r\n";
  }
  }
  if ( strcmp( $texport, 'ical' ) == 0 ) {
    $utc_dtstamp = export_ts_utc_date ( time () );
    $ret .= "DTSTAMP:$utc_dtstamp\r\n";
    // We don' want DTEND for VTODOs
    if ( $vtype == 'T' || $vtype == 'N' ) return $ret;
    if ( $time == 0 && $duration == 1440 ) {
      // all day event: better to use end date than duration since
      // duration will be 23hr and 25hrs on DST switch-over days.
      if ( $vtimezone_exists ) {
        $ret .= 'DTEND;TZID=' . $TIMEZONE . ':' . date ( 'Ymd', $eventend ) . "T000000\r\n";
      }else
      $ret .= 'DTEND;VALUE=DATE:' . gmdate ( 'Ymd', $eventend ) . "\r\n";
    }
    else  if ( $time == -1 )
    // untimed event   
     $ret .= "DTEND;VALUE=DATE:$date\r\n";
    else if ( $time > 0 ) {
      // timed  event
      if ( $vtimezone_exists ) {
        $ret .= 'DTEND;TZID=' . $TIMEZONE . ':' . date ( 'Ymd', $eventend ) . "T000000\r\n";
      }else {
      $utc_end = export_ts_utc_date ( $eventend );
      $ret .= "DTEND:$utc_end\r\n";
      }
    }
  } elseif ( strcmp( $texport, 'vcal' ) == 0 ) {
      $utc_end = export_ts_utc_date ( $eventend );
      $ret .= "DTEND:$utc_end\r\n";
  } else {
    $ret .= "DURATION:P$str_duration\r\n";
  }

  return $ret;
}
// $simple allows for easy reading
function export_recurrence_ical ( $id, $simple = false ) {
  global $DATE_FORMAT_TASK, $lang_file;

  $recurrance = '';
  $sql = 'SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not
    WHERE cal_id = ?';

  $res = dbi_execute ( $sql, array ( $id ) );

  if ( $res ) {
    $exdate = $rdate = array ();
    while ( $row = dbi_fetch_row( $res ) ) {
      if ( $row[1] == 1 )
        $exdate[] = $row[0];
      else
        $rdate[] = $row[0];
    }
    dbi_free_result ( $res );
  }
  $sql = 'SELECT wer.cal_type, wer.cal_end, wer.cal_endtime, wer.cal_frequency,
    we.cal_date, we.cal_time, wer.cal_bymonth, wer.cal_bymonthday, wer.cal_byday,
    wer.cal_bysetpos, wer.cal_byweekno, wer.cal_byyearday, wer.cal_wkst,
    wer.cal_count, we.cal_duration
    FROM webcal_entry we, webcal_entry_repeats wer WHERE wer.cal_id = ?
    AND we.cal_id = ? ORDER BY we.cal_date';

  $res = dbi_execute ( $sql, array ( $id, $id ) );

  if ( $res ) {
    if ( $row = dbi_fetch_row( $res ) ) {
      $type = $row[0];
      $end = $row[1];
      $endtime = $row[2];
      $interval = $row[3];
      $day = $row[4];
      $time = sprintf ( "%06d", $row[5] );
      $bymonth = $row[6];
      $bymonthday = $row[7];
      $byday = $row[8];
      $bysetpos = $row[9];
      $byweekno = $row[10];
      $byyearday = $row[11];
      $wkst = $wkst2 = $row[12];
      $cal_count = $row[13];
      $duration = $row[14];

      $rrule = '';

      if ( ! $simple )
        $rrule = 'RRULE:';
      else { // Translate byday and wkst string if needed.
        // Make sure these get picked up by update_translation.pl.
        // translate ( 'MO' ) translate ( 'TU' ) translate ( 'WE' )
        // translate ( 'TH' ) translate ( 'FR' ) translate ( 'SA' )
        // translate ( 'SU' )
        if ( ! empty ( $byday ) && ! empty ( $lang_file ) &&
           ! strstr ( $lang_file, 'English-US.txt' ) ) {
          $bydayArr = explode ( ',', $byday );
          foreach ( $bydayArr as $bydayIdx ) {
            $bydayOut[] = substr ( $bydayIdx, 0, strlen ( $bydayIdx ) -2 )
              . translate ( substr ( $bydayIdx, -2 ) );
          }
          $byday = implode ( ',', $bydayOut );
        }
        if ( ! empty ( $wkst ) )
          $wkst = translate ( $wkst );
      }

      /* recurrence frequency */
      switch ( $type ) {
        case 'daily':
          $rrule .= ( $simple ? translate ( 'Daily' ) : 'FREQ=DAILY' );
          break;
        case 'weekly':
          $rrule .= ( $simple ? translate ( 'Weekly' ) : 'FREQ=WEEKLY' );
          break;
        case 'monthlyBySetPos':
        case 'monthlyByDay':
        case 'monthlyByDate':
          $rrule .= ( $simple ? translate ( 'Monthly' ) : 'FREQ=MONTHLY' );
          break;
        case 'yearly':
          $rrule .= ( $simple ? translate ( 'Yearly' ) : 'FREQ=YEARLY' );
          break;
      }

      if ( ! empty ( $interval ) && $interval > 1 )
        $rrule .= ';' . ( $simple ? translate ( 'Interval' ) : 'INTERVAL' )
         . "=$interval";

      if ( ! empty ( $bymonth ) )
        $rrule .= ';' . ( $simple ? translate ( 'Months' ) : 'BYMONTH' )
         . "=$bymonth";

      if ( ! empty ( $bymonthday ) )
        $rrule .= ';' . ( $simple ? translate ( 'Month Days' ) : 'BYMONTHDAY' )
         . "=$bymonthday";

      if ( ! empty ( $byday ) )
        $rrule .= ';' . ( $simple ? translate ( 'Days' ) : 'BYDAY' )
         . "=$byday";

      if ( ! empty ( $byweekno ) )
        $rrule .= ';' . ( $simple ? translate ( 'Weeks' ) : 'BYWEEKNO' )
         . "=$byweekno";

      if ( ! empty ( $bysetpos ) )
        $rrule .= ';' . ( $simple ? translate ( 'Position' ) : 'BYSETPOS' )
         . "=$bysetpos";

      if ( ! empty ( $wkst ) && $wkst2 != 'MO' )
        $rrule .= ';' . ( $simple ? translate ( 'Week Start' ) : 'WKST' )
         . "=$wkst";

      if ( ! empty ( $end ) ) {
        $endtime = ( empty ( $endtime ) ? 0 : $endtime );
        $rrule .= ';' . ( $simple ? translate ( 'Until' ) : 'UNTIL' ) . '=';
        $utc = ( $simple
         ? date_to_str ( $end, $DATE_FORMAT_TASK, false ) . ' '
          . display_time ( $endtime )
         : export_get_utc_date ( $end, $endtime ) );
        $rrule .= $utc;
      } else
      if ( ! empty ( $cal_count ) && $cal_count != 999 )
        $rrule .= ';' . ( $simple ? translate ( 'Count' ) : 'COUNT' )
         . "=$cal_count";
      //.
      // wrap line if necessary
      $rrule = export_fold_lines ( $rrule );
      while ( list ( $key, $value ) = each ( $rrule ) ) {
        $recurrance .= "$value\r\n";
      }
      // If type = manual, undo what we just did and process RDATE && EXDATE.
      if ( $type == 'manual' )
       $recurrance = '';

      if ( count ( $rdate ) > 0 ) {
        $rdatesStr = '';
        foreach ( $rdate as $rdates ) {
          $rdatesStr .= date_to_str ( $rdates, $DATE_FORMAT_TASK, false ) . ' ';
        }
        $string = ( $simple
        ? ',' . translate ( 'Inclusion Dates' ) . '=' . $rdatesStr
        : 'RDATE;VALUE=DATE:' . implode ( ',', $rdate ) );
        $string = export_fold_lines ( $string );
        while ( list ( $key, $value ) = each ( $string ) ) {
          $recurrance .= "$value\r\n";
        }
      }
      if ( $simple )
       $recurrance .= '<br />';

      if ( count ( $exdate ) > 0 ) {
        $exdatesStr = '';
        foreach ( $exdate as $exdates ) {
          $exdatesStr .= date_to_str ( $exdates, $DATE_FORMAT_TASK, false ) . ' ';
        }
        $string = ( $simple
         ? ',' . translate ( 'Exclusion Dates' ) . '=' . $exdatesStr
         : 'EXDATE;VALUE=DATE:' . implode ( ',', $exdate ) );
        $string = export_fold_lines ( $string );
        while ( list ( $key, $value ) = each ( $string ) ) {
          $recurrance .= "$value\r\n";
        }
      }
    }
  }
  
  return $recurrance;
}

function export_recurrence_vcal( $id, $date ) {
  $sql = 'SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not
    WHERE cal_id = ?';

  $res = dbi_execute ( $sql, array ( $id ) );

  if ( $res ) {
    $exdate = array ();
    $rdate = array ();
    while ( $row = dbi_fetch_row( $res ) ) {
      if ( $row[1] == 1 ) {
        $exdate[] = $row[0];
      } else {
        $rdate[] = $row[0];
      }
    }
    dbi_free_result ( $res );
  }

  $sql = 'SELECT wer.cal_type, wer.cal_end,
    wer.cal_endtime, wer.cal_frequency,
    we.cal_date, we.cal_time, wer.cal_bymonth,
    wer.cal_bymonthday,  wer.cal_byday,
    wer.cal_bysetpos, wer.cal_byweekno,
    wer.cal_byyearday, wer.cal_wkst,
    wer.cal_count  FROM webcal_entry we, webcal_entry_repeats wer
    WHERE wer.cal_id = ? AND we.cal_id = ?';

  $res = dbi_execute ( $sql, array ( $id, $id ) );

  if ( $res ) {
    if ( $row = dbi_fetch_row( $res ) ) {
      $type = $row[0];
      $end = $row[1];
      $endtime = $row[2];
      $interval = $row[3];
      $day = $row[4];
      $time = sprintf ( "%06d", $row[5] );
      $bymonth = str_replace ( ',', ' ', $row[6] );
      $bymonthday = str_replace ( ',', ' ', $row[7] );
      $byday = str_replace ( ',', ' ', $row[8] );
      $bysetpos = str_replace ( ',', ' ', $row[9] );
      $byweekno = $row[10];
      $byyearday = str_replace ( ',', ' ', $row[11] );
      $wkst = $row[12];
      $count = $row[13];

      echo 'RRULE:';

      /* recurrence frequency */
      switch ( $type ) {
        case 'daily' :
          echo "D$interval ";
          break;
        case 'weekly' :
          echo "W$interval ";
          if ( ! empty ( $byday ) )
            echo $byday . ' ';
          break;
        case 'monthlyByDay':
          echo "MP$interval ";
          if ( ! empty ( $byday ) )
            echo $byday . ' ';
          break;
        case 'monthlyBySetPos':
          echo "MP$interval ";
          if ( ! empty ( $bymonthday ) )
            echo $bymonthday . ' ';
          break;
        case 'monthlyByDate' :
          echo "MD$interval ";
          if ( ! empty ( $byday ) )
            echo $byday . ' ';
          break;
        case 'yearly' :
          if ( ! empty ( $byyearday ) )
            echo "YM$interval ";
          else
            echo "YD$interval ";
          break;
      }

      if ( ! empty ( $count ) && $count > 0 ) {
        echo '#' . $count . ' ';
      } else if ( ! empty ( $count ) && $count == 0 && empty ( $end ) ) {
        echo '#' . $count . ' ';
      }
      // End Date - For all types
      if ( ! empty ( $end ) )
        echo export_get_utc_date ( $end, $time );

      echo "\r\n";
      // Repeating Exceptions
      $num = count ( $exdate );
      if ( $num > 0 ) {
        $string = 'EXDATE:';
        for ( $i = 0;$i < $num;$i++ ) {
          $string .= $exdate[$i] . 'T000000,';
        }
        echo rtrim( $string, ',' ) . "\r\n";
      }
    }
  }
}

/*
 * Create a date-time format (e.g. "20041130T123000Z") that is
 * Times are now stored in GMT so no conversion is needed
 */
function export_get_utc_date ( $date, $time = 0 ) {
  $time = sprintf ( "%06d", $time );
  $utc = sprintf ( "%sT%sZ", $date, $time );

  return $utc;
}

/*
 * Create a date-time format (e.g. "20041130T123000Z") that is
 * Times are now stored in GMT so no conversion is needed
 */
function export_ts_utc_date ( $timestamp ) {
  $utc = gmdate ( 'Ymd\THis\Z', $timestamp );
  return $utc;
}

function export_alarm_vcal( $id, $date ) {
  // Don't send reminder for event in the past
  if ( $date < date ( 'Ymd' )  )
    return;
  // get reminders
  $reminder = getReminders ( $id );

  if ( ! empty ( $reminder['date'] ) ) {
    echo 'DALARM:' . $reminder['date'] . 'T'
     . $reminder['time'] . "Z\r\n";
  }
}
// Convert the webcalendar reminder to an ical VALARM
function export_alarm_ical ( $id, $date, $description, $task_complete = true ) {
  global $cal_type;

  $ret = '';
  $reminder = array ();
  // Don't send reminder for event in the past
  // unless this is a task that may be overdue
  if ( $date < date ( 'Ymd' ) && $task_complete == true )
    return;
  // get reminders
  $reminder = getReminders ( $id );

  if ( ! empty ( $reminder ) ) {
    // Sunbird requires this line
    if ( ! empty ( $reminder['offset'] ) )
      $ret .= 'X-MOZILLA-ALARM-DEFAULT-LENGTH:' . $reminder['offset'] . "\r\n";
    $ret .= "BEGIN:VALARM\r\n";
    $ret .= 'TRIGGER';

    if ( ! empty ( $reminder['date'] ) ) {
      $ret .= ';VALUE=DATE-TIME:' . $reminder['date'] . 'T'
       . $reminder['time'] . "Z\r\n";
    }
    // related to entry end/due date/time
    if ( $reminder['related'] == 'E' ) {
      $ret .= ';RELATED=END';
    }
    if ( empty ( $reminder['date'] ) ) { // offset may be zero
      // before edge needs a '-'
      $sign = ( $reminder['before'] == 'Y' ? '-' : '' );
      $ret .= ':' . $sign . 'PT' . $reminder['offset'] . "M\r\n";
    }

    if ( ! empty ( $reminder['repeats'] ) ) {
      $ret .= 'REPEAT:' . $reminder['repeats'] . "\r\n";
      $ret .= 'DURATION:PT' . $reminder['duration'] . "M\r\n";
    }
    $ret .= 'ACTION:' . $reminder['action'] . "\r\n";

    $array = export_fold_lines ( $description, 'utf8' );
    while ( list ( $key, $value ) = each ( $array ) ) {
      $ret .= "$value\r\n";
    }

    $ret .= "END:VALARM\r\n";
  }

  return $ret;
}

function export_get_event_entry( $id = 'all', $attachment = false ) {
  global $use_all_dates, $include_layers, $startdate,
  $enddate, $moddate, $login, $user;
  global $DISPLAY_UNAPPROVED, $layers, $type, $USER_REMOTE_ACCESS, $cat_filter;

  $sql_params = array ();
  $sql = 'SELECT we.cal_id, we.cal_name, we.cal_priority, we.cal_date,
    we.cal_time, weu.cal_status, we.cal_create_by, we.cal_access, we.cal_duration,
    we.cal_description, weu.cal_percent, we.cal_completed, we.cal_due_date,
    we.cal_due_time, we.cal_location, we.cal_url, we.cal_type, we.cal_mod_date,
    we.cal_mod_time
    FROM webcal_entry we, webcal_entry_user weu ';

  if ( $id == 'all' ) {
    $sql .= 'WHERE we.cal_id = weu.cal_id AND ( weu.cal_login = ?';
    $sql_params[] = $login;
    if ( $user && $user != $login ) {
      $sql .= ' OR weu.cal_login = ?';
      $sql_params[] = $user;
    } else if ( $include_layers && $layers ) {
      foreach ( $layers as $layer ) {
        $sql .= ' OR weu.cal_login = ?';
        $sql_params[] = $layer['cal_layeruser'];
      }
    }
    $sql .= ' ) ';

    if ( !$use_all_dates ) {
      $sql .= ' AND we.cal_date >= ? AND we.cal_date <= ?';
      $sql_params[] = $startdate;
      $sql_params[] = $enddate;
      $sql .= ' AND we.cal_mod_date >= ?';
      $sql_params[] = $moddate;
    }
  } else {
    $sql .= 'WHERE we.cal_id = ? AND weu.cal_id = ? AND
      ( weu.cal_login = ?';
    $sql_params[] = $id;
    $sql_params[] = $id;
    $sql_params[] = $login;
    // TODO: add support for user in URL so we can export from other
    // calendars, particularly non-user calendars.
    // "webcal_entry_user.cal_id = '$id'";
    // there may be a better to do this
    if ( $attachment == true && empty ( $login ) ) {
      $sql .= ' OR weu.cal_login = we.cal_create_by';
    } else if ( ! empty ( $user ) && $user != $login ) {
      $sql .= ' OR weu.cal_login = ?';
      $sql_params[] = $user;
    } else if ( $include_layers && $layers ) {
      foreach ( $layers as $layer ) {
        $sql .= ' OR weu.cal_login = ?';
        $sql_params[] = $layer['cal_layeruser'];
      }
    }
    $sql .= ' ) ';
  } //end if $id=all
  if ( $DISPLAY_UNAPPROVED == 'N' ) {
    $sql .= " AND weu.cal_status = 'A'";
  } else {
    $sql .= " AND weu.cal_status IN ('W','A')";
  }

  if ( ! empty ( $type ) && $type = 'publish' ) {
    if ( $USER_REMOTE_ACCESS == 0 ) {
      $sql .= " AND we.cal_access = 'P'";
    } else if ( $USER_REMOTE_ACCESS == 1 ) {
      $sql .= " AND we.cal_access IN ('P', 'C' )";
    }
  }

  $sql .= ' ORDER BY we.cal_date';
  $res = dbi_execute ( $sql, $sql_params );

  return $res;
} //end function export_get_event_entry($id)
function generate_uid ( $id = '' ) {
  global $SERVER_URL, $login;

  $uid = $SERVER_URL;
  if ( empty ( $uid ) )
    $uid = 'UNCONFIGURED-WEBCALENDAR';
  $uid = str_replace ( 'http://', ' ', $uid );
  $uid .= sprintf ( "-%s-%010d", $login, $id );
  $uid = preg_replace ( "/[\s\/\.-]+/", '-', $uid );
  $uid = strtoupper ( $uid );
  return $uid;
}
// Add entries in the webcal_import and webcal_import_data tables.
// This allows us to associate webcalendar events with the ical UID.
// If the user updates the event and the updated event gets sent back, we can
// then figure out which webcalendar event goes with the UID so we can
// update the correct event.
function save_uid_for_event ( $importId, $id, $uid ) {
  global $login, $error;
  // Note: We can get a duplicate key error here if this event was
  // created by an import from another calendar. Say someone invites you
  // to an event and sends along an ics attachement via email. You use
  // that to import the event. Now, who is the definitive source of the
  // event?  If the original author sends an update or if the ical client
  // tries to update it?  I'm not really sure, but we will assume that
  // events imported into webcalendar become property of webcalendar.
  dbi_execute ( 'DELETE FROM webcal_import_data WHERE ' .
    'cal_id = ? AND cal_login = ?', array ( $id, $login ) );
  $sql = 'INSERT INTO webcal_import_data ( cal_import_id, cal_id,
    cal_login, cal_import_type, cal_external_id ) VALUES ( ?, ?, ?, ?, ? )';
  // do_debug ( "SQL: $sql" );
  if ( ! dbi_execute ( $sql, array ( $importId, $id, $login, 'publish', $uid ) ) ) {
    $error = db_error ();
    // do_debug ( $error );
  }
  // do_debug ( "leaving func" );
}
// Add an entry in webcal_import. For each import or publish request,
// we create a single webcal_import row that goes with the many
// webcal_import_data rows (one for each event).
function create_import_instance () {
  global $login, $prodid;

  $name = $prodid;
  $importId = 1;

  $sql = 'SELECT MAX(cal_import_id) FROM webcal_import';
  $res = dbi_execute ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $importId = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  $sql = 'INSERT INTO webcal_import ( cal_import_id, cal_name,
    cal_date, cal_type, cal_login ) VALUES ( ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array ( $importId, $name, date ( 'Ymd' ),
    'publish', $login ) ) ) {
    $error = db_error ();
    return;
  }
  return ( $importId );
}

function export_vcal ( $id ) {
  global $login;

  header ( 'Content-Type: text/x-vcalendar' );
  // header ( "Content-Type: text/plain" );
  $res = export_get_event_entry( $id );

  $entry_array = array ();
  $count = 0;

  while ( $entry = dbi_fetch_row( $res ) ) {
    $entry_array[$count++] = $entry;
  }

  dbi_free_result ( $res );

  if ( count ( $entry_array ) > 0 ) {
    echo "BEGIN:VCALENDAR\r\n";
    echo generate_prodid ( 'vcs' );
    echo "VERSION:1.0\r\n";
  } while ( list ( $key, $row ) = each ( $entry_array ) ) {
    $id = $row[0];
    $export_uid = generate_uid ();
    $name = $row[1];
    $priority = $row[2];
    $date = $row[3];
    $time = sprintf ( "%06d", $row[4] );
    $status = $row[5];
    $create_by = $row[6];
    $access = $row[7];
    $duration = $row[8];
    $description = $row[9];
    $percent = $row[10];
    $completed = $row[11];
    $due_date = $row[12];
    $due_time = $row[13];
    $location = $row[14];
    $url = $row[15];
    $cal_type = $row[16];

    // Figure out Categories
    $categories = get_categories_by_id ( $id, $login );
    /* Start of event/task */
    if ( $cal_type == 'E' || $cal_type == 'M' )
      echo "BEGIN:VEVENT\r\n";
    elseif ( $cal_type == 'T' || $cal_type == 'N' )
      echo "BEGIN:VTODO\r\n";
    else
      // VJOURNALS are not allowed in VCS files.
      continue;


    /* UID of the event (folded to 76 char) */
    $export_uid = "UID:$export_uid";
    $array = export_fold_lines ( $export_uid );
    while ( list ( $key, $value ) = each ( $array ) ) {
      echo "$value\r\n";
    }

    /* SUMMARY of the event (folded to 76 char) */
    $name = preg_replace( "/\\\\/", "\\\\\\", $name ); // ??
    $name = 'SUMMARY;ENCODING=QUOTED-PRINTABLE:' . $name;
    $array = export_fold_lines ( $name, 'quotedprintable' );

    while ( list ( $key, $value ) = each ( $array ) )
    echo "$value\r\n";

    /* DESCRIPTION if any (folded to 76 char) */
    if ( $description != '' ) {
      $description = preg_replace( "/\\\\/", "\\\\\\", $description ); // ??
      $description = 'DESCRIPTION;ENCODING=QUOTED-PRINTABLE:' . $description;
      $array = export_fold_lines ( $description, 'quotedprintable' );
      while ( list ( $key, $value ) = each ( $array ) )
      echo "$value\r\n";
    } //end if ($description != '')
    
    /* CATEGORIES if any (folded to 76 char) */
    if ( isset ( $categories ) && count ( $categories ) ) {
      $categories = 'CATEGORIES:' . implode ( ';', $categories );
      $array = export_fold_lines ( $categories, 'quotedprintable' );
      while ( list ( $key, $value ) = each ( $array ) )
      $ret .= "$value\r\n";
    }
    
    /* CLASS either "PRIVATE", "CONFIDENTIAL, or "PUBLIC" (the default) */
    if ( $access == 'R' ) {
      echo "CLASS:PRIVATE\r\n";
    } else if ( $access == 'C' ) {
      echo "CLASS:CONFIDENTIAL\r\n";
    } else {
      echo "CLASS:PUBLIC\r\n";
    }
    // ATTENDEE of the event
   // $attendee = export_get_attendee( $row[0], 'vcal' );
    //$attendcnt = count ( $attendee );
    //for ( $i = 0; $i < $attendcnt; $i++ ) {
    //  $attendee[$i] = export_fold_lines ( $attendee[$i], 'quotedprintable' );
    //  while ( list ( $key, $value ) = each ( $attendee[$i] ) )
    //  echo "$value\r\n";
    //}

    /* Time - all times are utc */
    echo export_time ( $date, $duration, $time, 'vcal' );

    echo export_recurrence_vcal( $id, $date );

    export_alarm_vcal( $id, $date );

    /* Goodbye event/task */
    if ( $cal_type == 'E' || $cal_type == 'M' ) {
      echo "END:VEVENT\r\n";
    } else {
      echo "END:VTODO\r\n";
    }
  } //end while (list ($key,$row) = each ( $entry_array))
  if ( count ( $entry_array ) > 0 )
    echo "END:VCALENDAR\r\n";
} //end function

function export_ical ( $id = 'all', $attachment = false ) {
  global $publish_fullname, $login, $cal_type,
    $cat_filter, $vtimezone_data, $use_vtimezone;

  $exportId = -1;
  $ret = $Vret = $vtimezone_data = $use_vtimezone = '';

  $res = export_get_event_entry( $id, $attachment );
  $entry_array = array ();
  $count = 0;
  while ( $entry = dbi_fetch_row( $res ) ) {
    $entry_array[$count++] = $entry;
  }
  dbi_free_result ( $res );
  //abort if no records to output
  if ( $count == 0 )
    return;
  // Always output something, even if no records come back
  // This prevents errors on the iCal client
  $ret = "BEGIN:VCALENDAR\r\n";
  $title = utf8_encode ( 'X-WR-CALNAME;VALUE=TEXT:' .
  ( empty ( $publish_fullname ) ? $login : translate ( $publish_fullname ) ) );
  $title = str_replace ( ',', "\\,", $title );
  $ret .= "$title\r\n";
  $ret .= generate_prodid ( 'ics' );
  $ret .= "VERSION:2.0\r\n";
  $ret .= "METHOD:PUBLISH\r\n";

  while ( list ( $key, $row ) = each ( $entry_array ) ) {
    $id = $row[0];
    $event_uid = generate_uid ( $id );
    $name = $row[1];
    $priority = $row[2];
    $date = $row[3];
    $time = sprintf ( "%06d", $row[4] );
    $status = $row[5];
    $create_by = $row[6];
    $access = $row[7];
    $duration = $row[8];
    $description = $row[9];
    // New columns to support tasks
    $percent = ( empty ( $row[10] ) ? 0 : $row[10] );
    $completed = ( empty ( $row[11] )
      ? ''
      : substr ( $row[11], 0, 8 ) . 'T'
       . sprintf ( "%06d", substr ( $row[11], 9, 6 ) ) );
    $due_date = $row[12];
    $due_time = $row[13];
    $location = $row[14];
    $url = $row[15];
    $cal_type = $row[16];
    $moddate = $row[17];
    $modtime = $row[18];
    // Figure out Categories
    $categories = get_categories_by_id ( $id, $login );

    // Add entry in webcal_import_data if it does not exist.
    // Even thought we're exporting, this data needs to be valid
    // for proper parsing of response from iCal client.
    // If this is the first event that has never been published,
    // then create a new import instance to associate with what we are doing.
    $sql = 'SELECT wid.cal_external_id
      FROM webcal_import_data wid, webcal_entry_user weu
      WHERE wid.cal_id = weu.cal_id AND wid.cal_id = ? AND
      weu.cal_login = ?';
    $res = dbi_execute ( $sql, array ( $id, $login ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        // event has been published (or imported) before
        $event_uid = $row[0];
      } else {
        if ( $exportId < 0 ) {
          // This is first event that has not been published before.
          // Create an entry in webcal_import.
          // It would be nice if we could put a name in here of who
          // or where the remote cal subscription is coming from in case
          // they update some of our events. But, I cannot see a way to
          // do that.
          $exportId = create_import_instance ();
        }
        if ( $attachment == false )
          save_uid_for_event ( $exportId, $id, $event_uid );
      }
      dbi_free_result ( $res );
    }
    // get recurrance info
    $recurrance = export_recurrence_ical ( $id );
    if ( ! empty ( $recurrance  ) )
      $use_vtimezone = true;

    /* snippet from RFC2445
  The "VTIMEZONE" calendar component MUST be present if the iCalendar
   object contains an RRULE that generates dates on both sides of a time
   zone shift (e.g. both in Standard Time and Daylight Saving Time)
   unless the iCalendar object intends to convey a floating time (See
   the section "4.1.10.11 Time" for proper interpretation of floating
   time). It can be present if the iCalendar object does not contain
   such a RRULE. In addition, if a RRULE is present, there MUST be valid
   time zone information for all recurrence instances.

   However, this is not possible to implement at this time
   */
    // if Categories were selected as an export filter, then abort this
    // event if it does not contain that category
    if ( ! empty ( $cat_filter ) ) {
      if ( count ( $categories ) == 0 ||
        ! array_key_exists ( $cat_filter, $categories ) )
        continue;
    }

    if ( $cal_type == 'E' || $cal_type == 'M' ) {
      $exporting_event = true;
      /* Start of event */
      $Vret .= "BEGIN:VEVENT\r\n";
    } else if ( $cal_type == 'T' || $cal_type == 'N' ) {
      $exporting_event = false;
      /* Start of VTODO */
      $Vret .= "BEGIN:VTODO\r\n";
    } else if ( $cal_type == 'J' || $cal_type == 'O' ) {
      $exporting_event = false;
      /* Start of VJOURNAL */
      $Vret .= "BEGIN:VJOURNAL\r\n";
    }

    /* UID of the event (folded to 76 char) */
    $array = export_fold_lines ( "UID:$event_uid" );
    while ( list ( $key, $value ) = each ( $array ) )
    $Vret .= "$value\r\n";

    $Vret .= 'LAST-MODIFIED:' . export_get_utc_date ( $moddate,$modtime ) . "\r\n";

    $name = preg_replace( "/\r/", ' ', $name );
    // escape,;  \ in octal ascii
    $name = addcslashes ( $name, "\54\73\134" );
    $description = preg_replace( "/\r/", ' ', $description );
    $description = addcslashes ( $description, "\54\73\134" );
    $description = str_replace ( chr( 10 ), chr( 92 ) . chr( 110 ), $description );

    /* SUMMARY of the event (folded to 76 char) */
    $name = 'SUMMARY:' . $name;
    $array = export_fold_lines ( $name, 'utf8' );

    while ( list ( $key, $value ) = each ( $array ) )
    $Vret .= "$value\r\n";

    /* DESCRIPTION if any (folded to 76 char) */
    if ( $description != '' ) {
      $description = 'DESCRIPTION:' . $description;
      $array = export_fold_lines ( $description, 'utf8' );
      while ( list ( $key, $value ) = each ( $array ) )
      $Vret .= "$value\r\n";
    }

    /* LOCATION if any (folded to 76 char) */
    if ( $location != '' ) {
      $location = 'LOCATION:' . $location;
      $array = export_fold_lines ( $location, 'utf8' );
      while ( list ( $key, $value ) = each ( $array ) )
      $Vret .= "$value\r\n";
    }

    /* URL if any (folded to 76 char) */
    if ( $url != '' ) {
      $url = 'URL:' . $url;
      $array = export_fold_lines ( $url, 'utf8' );
      while ( list ( $key, $value ) = each ( $array ) )
      $Vret .= "$value\r\n";
    }

    /* CATEGORIES if any (folded to 76 char) */
    if ( isset ( $categories ) && count ( $categories ) ) {
      $categories = 'CATEGORIES:' . implode ( ',', $categories );
      $array = export_fold_lines ( $categories, 'utf8' );
      while ( list ( $key, $value ) = each ( $array ) )
      $Vret .= "$value\r\n";
    }

    /* CLASS either "PRIVATE", "CONFIDENTIAL",  or "PUBLIC" (the default) */
    if ( $access == 'R' ) {
      $Vret .= "CLASS:PRIVATE\r\n";
    } else if ( $access == 'C' ) {
      $Vret .= "CLASS:CONFIDENTIAL\r\n";
    } else {
      $Vret .= "CLASS:PUBLIC\r\n";
    }

    /* STATUS */
    if ( $cal_type == 'E' || $cal_type == 'M' ) {
      if ( $status == 'A' ) {
        $Vret .= "STATUS:CONFIRMED\r\n";
      } else if ( $status == 'W' ) {
        $Vret .= "STATUS:TENTATIVE\r\n";
      } else if ( $status == 'D' ) {
        $Vret .= "STATUS:CANCELLED\r\n";
      }
    } else if ( $cal_type == 'T' || $cal_type == 'N' ) {
      if ( $status == 'A' && empty ( $completed ) ) {
        $Vret .= "STATUS:IN-PROCESS\r\n";
      } else if ( $status == 'A' ) {
        $Vret .= "STATUS:COMPLETED\r\n";
      } else if ( $status == 'W' ) {
        $Vret .= "STATUS:NEEDS-ACTION\r\n";
      } else if ( $status == 'D' ) {
        $Vret .= "STATUS:CANCELLED\r\n";
      }
    }
    // ATTENDEE of the event
    $attendee = export_get_attendee( $id, 'ical' );
    $attendcnt = count ( $attendee );
    for ( $i = 0; $i < $attendcnt; $i++ ) {
      $attendee[$i] = export_fold_lines ( $attendee[$i], 'utf8' );
      while ( list ( $key, $value ) = each ( $attendee[$i] ) )
        $Vret .= "$value\r\n";
    }
    /* Time - all times are utc */
    $Vret .= export_time ( $date, $duration, $time, 'ical', $cal_type );
    // VTODO specific items
    $task_complete = false;
    if ( $cal_type == 'T' || $cal_type == 'N' ) {
      $Vret .= 'DUE:' . $due_date . 'T'
       . sprintf ( "%06d", $due_time ) . "Z\r\n";
      if ( ! empty ( $completed ) ) {
        $Vret .= 'COMPLETED:' . $completed . "\r\n";
        $task_complete = true;
      }
      $Vret .= 'PERCENT-COMPLETE:' . $percent . "\r\n";
    }

    /* Recurrence */
    $Vret .= $recurrance;

    /* handle alarms */
    $Vret .= export_alarm_ical( $id, $date, $description, $task_complete );

    if ( $cal_type == 'E' || $cal_type == 'M' ) {
      /* End of event */
      $Vret .= "END:VEVENT\r\n";
    } else if ( $cal_type == 'T' || $cal_type == 'N' ) {
      /* Start of VTODO */
      $Vret .= "END:VTODO\r\n";
    } else if ( $cal_type == 'J' || $cal_type == 'O' ) {
      /* Start of VJOURNAL */
      $Vret .= "END:VJOURNAL\r\n";
    }
  }

  /* VTIMEZONE Set in export_time () if needed */
  $ret .= $vtimezone_data  . $Vret;


  $ret .= "END:VCALENDAR\r\n";
  // attachment will be true if called during email creation
  if ( !$attachment ) {
    echo $ret;
  } else {
    return $ret;
  }
}
// IMPORT FUNCTIONS BELOW HERE
/* Import the data structure
$Entry[CalendarType]       =  VEVENT, VTODO, VTIMEZONE
$Entry[RecordID]           =  Record ID (in the Palm) ** palm desktop only
$Entry[StartTime]          =  In seconds since 1970 (Unix Epoch)
$Entry[EndTime]            =  In seconds since 1970 (Unix Epoch)
$Entry[Summary]            =  Summary of event (string)
$Entry[Duration]           =  How long the event lasts (in minutes)
$Entry[Description]        =  Full Description (string)
$Entry[Untimed]            =  1 = true  0 = false
$Entry[Class]              =  R = PRIVATE,C = CONFIDENTIAL  P = PUBLIC
$Entry[Location]           =  Location of event
$Entry[Priority]           =  1 = Highest 5=Normal 9=Lowest
$Entry[Tranparency]        =  1 = Transparent, 0 = Opaque (Used for Free/Busy)
$Entry[Categories]         =  String containing Categories
$Entry[Due]                =  UTC datetime when VTODO is due
$Entry[Completed]          =  Datetime when VTODO was completed
$Entry[Percent]            =  Percentage of VTODO complete 0-100
$Entry[AlarmSet]           =  1 = true  0 = false
$Entry[Alarm]              =  String containg VALARM TRIGGERS
$Entry[Repeat]             =  Array containing repeat information (if repeat)
$Entry[Repeat][Frequency]  =  1=daily,2=weekly,3=MonthlyByDay,4=MonthlyByDate,
                              5=MonthBySetPos,6=Yearly,7=manual
$Entry[Repeat][Interval]   =  How often event occurs.
$Entry[Repeat][Until]      =  When the repeat ends (Unix Epoch)
$Entry[Repeat][Exceptions] =  Exceptions to the repeat  (Unix Epoch)
$Entry[Repeat][Inclusions] =  Inclusions to the repeat (Unix Epoch)
$Entry[Repeat][ByDay]      =  What days to repeat on
$Entry[Repeat][ByMonthDay] =  Days of month events fall on
$Entry[Repeat][ByMonth]    =  Months that event will occur (12 chars y or n)
$Entry[Repeat][BySetPos]   =  Position in other ByXxxx that events occur
$Entry[Repeat][ByYearDay]  =  Days in year that event occurs
$Entry[Repeat][ByWeekNo]   =  Week that a yearly event repeats
$Entry[Repeat][WkSt]       =  Day that week starts on (default MO)
$Entry[Repeat][Count]      =  Number of occurances, may be used instead of UNTIL
*/

function import_data ( $data, $overwrite, $type ) {
  global $login, $count_con, $count_suc, $error_num, $ImportType;
  global $single_user, $single_user_login, $numDeleted, $errormsg;
  global $ALLOW_CONFLICTS, $ALLOW_CONFLICT_OVERRIDE, $H2COLOR;
  global $calUser, $sqlLog;

  $oldUIDs = array ();
  $oldIds = array ();
  $firstEventId = $count_suc = 0;
  // $importId = -1;
  $importId = 1;
  $subType = '';
  if ( $type == 'icalclient' ) {
    $ImportType = 'ICAL';
    $type = 'ical';
    $subType = 'icalclient';
  } else if ( $type == 'remoteics' || $type == 'hcal' ) {
    $ImportType = 'RMTICS';
    $type = 'rmtics';
    $subType = 'remoteics';
  }
  // Generate a unique import id
  $res = dbi_execute ( 'SELECT MAX(cal_import_id) FROM webcal_import' );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $importId = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  $sql = 'INSERT INTO webcal_import ( cal_import_id, cal_name,
    cal_date, cal_type, cal_login ) VALUES ( ?, NULL, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array ( $importId, date ( 'Ymd' ),
    $type, $login ) ) ) {
    $errormsg = db_error ();
    return;
  }
  if ( ! is_array ( $data ) )
    return false;
  foreach ( $data as $Entry ) {
    // do_debug ( "Entry Array " . print_r ( $Entry, true ) );
    $participants[0] = $calUser;
    // $participants[0] = $login;
    $Entry['start_date'] = gmdate ( 'Ymd', $Entry['StartTime'] );
    $Entry['start_time'] = gmdate ( 'His', $Entry['StartTime'] );
    $Entry['end_date'] = gmdate ( 'Ymd', $Entry['EndTime'] );
    $Entry['end_time'] = gmdate ( 'His', $Entry['EndTime'] );
    // not in icalclient
    if ( $overwrite && ! empty ( $Entry['UID'] ) ) {
      if ( empty ( $oldUIDs[$Entry['UID']] ) ) {
        $oldUIDs[$Entry['UID']] = 1;
      } else {
        $oldUIDs[$Entry['UID']]++;
      }
    }
    // Check for untimed
    if ( ! empty ( $Entry['Untimed'] ) && $Entry['Untimed'] == 1 ) {
      $Entry['start_time'] = 0;
    }
    // Check for all day
    if ( ! empty ( $Entry['AllDay'] ) && $Entry['AllDay'] == 1 ) {
      $Entry['start_time'] = 0;
      $Entry['end_time'] = 0;
      $Entry['Duration'] = '1440';
    }

    $priority = ( ! empty (  $Entry['Priority'] ) ?
      $Entry['Priority'] : 5 );

    if ( ! empty ( $Entry['Completed'] ) ) {
      $cal_completed = substr ( $Entry['Completed'], 0, 8 );
    } else {
      $cal_completed = '';
    }
    if ( strlen ( $cal_completed < 8 ) ) $cal_completed = '';

    $months = ( ! empty ( $Entry['Repeat']['ByMonth'] ) ) ?
    $Entry['Repeat']['ByMonth'] : '';

    $updateMode = false;
    // See if event already is there from prior import.
    // The same UID is used for all events imported at once with iCal.
    // So, we still don't have enough info to find the exact
    // event we want to replace. We could just delete all
    // existing events that correspond to the UID.
    // NOTE:(cek) commented out 'publish'. Will not work if event
    // was originally created from importing.
    if ( ! empty ( $Entry['UID'] ) ) {
      $res = dbi_execute ( 'SELECT wid.cal_id '
         . 'FROM webcal_import_data wid, webcal_entry_user weu WHERE '
        // "cal_import_type = 'publish' AND " .
        . 'wid.cal_id = weu.cal_id AND '
         . 'weu.cal_login = ? AND '
         . 'cal_external_id = ?', array ( $login, $Entry['UID'] ) );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) {
          if ( ! empty ( $row[0] ) ) {
            $id = $row[0];
            $updateMode = true;
            // update rather than add a new event
          }
        }
      }
    }

    if ( ! $updateMode && $subType != 'icalclient' && $subType != 'remoteics' ) {
      // first check for any schedule conflicts
      if ( ( $ALLOW_CONFLICT_OVERRIDE == 'N' && $ALLOW_CONFLICTS == 'N' ) &&
          ( $Entry['Duration'] != 0 ) ) {
        $ex_days = array ();
        if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
          foreach ( $Entry['Repeat']['Exceptions'] as $ex_date ) {
            $ex_days[] = gmdate ( 'Ymd', $ex_date );
          }
        }
        $inc_days = array ();
        if ( ! empty ( $Entry['Repeat']['Inclusions'] ) ) {
          foreach ( $Entry['Repeat']['Inclusions'] as $inc_date ) {
            $inc_days[] = gmdate ( 'Ymd', $inc_date );
          }
        }
        // test if all Repeat Elements exist
        $rep_interval = ( ! empty ( $Entry['Repeat']['Interval'] ) ?
          $Entry['Repeat']['Interval'] : '' );
        $rep_bymonth = ( ! empty ( $Entry['Repeat']['ByMonth'] ) ?
          $Entry['Repeat']['ByMonth'] : '' );
        $rep_byweekno = ( ! empty ( $Entry['Repeat']['ByWeekNo'] ) ?
          $Entry['Repeat']['ByWeekNo'] : '' );
        $rep_byyearday = ( ! empty ( $Entry['Repeat']['ByYearDay'] ) ?
          $Entry['Repeat']['ByYearDay'] : '' );
        $rep_byweekno = ( ! empty ( $Entry['Repeat']['ByWeekNo'] ) ?
          $Entry['Repeat']['ByWeekNo'] : '' );
        $rep_byweekno = ( ! empty ( $Entry['Repeat']['ByWeekNo'] ) ?
          $Entry['Repeat']['ByWeekNo'] : '' );
        $rep_byweekno = ( ! empty ( $Entry['Repeat']['ByWeekNo'] ) ?
          $Entry['Repeat']['ByWeekNo'] : '' );
        $rep_bymonthday = ( ! empty ( $Entry['Repeat']['ByMonthDay'] ) ?
          $Entry['Repeat']['ByMonthDay'] : '' );
        $rep_byday = ( ! empty ( $Entry['Repeat']['ByDay'] ) ?
          $Entry['Repeat']['ByDay'] : '' );
        $rep_bysetpos = ( ! empty ( $Entry['Repeat']['BySetPos'] ) ?
          $Entry['Repeat']['BySetPos'] : '' );
        $rep_count = ( ! empty ( $Entry['Repeat']['Count'] ) ?
          $Entry['Repeat']['Count'] : '' );
        $rep_until = ( ! empty ( $Entry['Repeat']['Until'] ) ?
          $Entry['Repeat']['Until'] : '' );
        $rep_wkst = ( ! empty ( $Entry['Repeat']['Wkst'] ) ?
          $Entry['Repeat']['Wkst'] : '' );

        $dates = get_all_dates( $Entry['StartTime'],
          RepeatType( $Entry['Repeat']['Frequency'] ), $rep_interval,
          array ( $rep_bymonth, $rep_byweekno, $rep_byyearday, $rep_bymonthday,
          $rep_byday, $rep_bysetpos ), $rep_count, $rep_until, $rep_wkst,
          $ex_days, $inc_days );

        $overlap = check_for_conflicts ( $dates, $Entry['Duration'],
          $Entry['StartTime'], $participants, $login, 0 );
      }
    } //end  $subType != 'icalclient' && != 'remoteics'
    if ( empty ( $error ) ) {
      if ( ! $updateMode ) {
        // Add the Event
        $res = dbi_execute ( 'SELECT MAX(cal_id) FROM webcal_entry' );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $id = $row[0] + 1;
          dbi_free_result ( $res );
        } else {
          $id = 1;
        }
      }
      // not in icalclient
      if ( $firstEventId == 0 )
        $firstEventId = $id;

      $names = array ();
      $values = array ();
      $names[] = 'cal_id';
      $values[] = $id;
      if ( ! $updateMode ) {
        $names[] = 'cal_create_by';
        $values[] = ( $ImportType == 'RMTICS' ? $calUser : $login );
      }
      $names[] = 'cal_date';
      $values[] = $Entry['start_date'];
      $names[] = 'cal_time';
      $values[] = ( ! empty ( $Entry['Untimed'] ) && $Entry['Untimed'] == 1 )
      ? '-1' : $Entry['start_time'];
      $names[] = 'cal_mod_date';
      $values[] = gmdate ( 'Ymd' );
      $names[] = 'cal_mod_time';
      $values[] = gmdate ( 'Gis' );
      $names[] = 'cal_duration';
      $values[] = sprintf ( "%d", $Entry['Duration'] );
      $names[] = 'cal_priority';
      $values[] = $priority;

      if ( ! empty ( $Entry['Class'] ) ) {
        $names[] = 'cal_access';
        $entryclass = $Entry['Class'];
        $values[] = $entryclass;
      }

      if ( ! empty ( $Entry['Location'] ) ) {
        $names[] = 'cal_location';
        $entryclass = $Entry['Location'];
        $values[] = $entryclass;
      }

      if ( ! empty ( $Entry['URL'] ) ) {
        $names[] = 'cal_url';
        $entryclass = $Entry['URL'];
        $values[] = $entryclass;
      }

      if ( ! empty ( $cal_completed ) ) {
        $names[] = 'cal_completed';
        $values[] = $cal_completed;
      }
      if ( ! empty ( $Entry['Due'] ) ) {
        $names[] = 'cal_due_date';
        $values[] = sprintf ( "%d", substr ( $Entry['Due'], 0, 8 ) );
        $names[] = 'cal_due_time';
        $values[] = sprintf ( "%d", substr ( $Entry['Due'], 9, 6 ) );
      }
      if ( ! empty ( $Entry['CalendarType'] ) ) {
        $names[] = 'cal_type';
        if ( $Entry['CalendarType'] == 'VEVENT' || $Entry['CalendarType'] == 'VFREEBUSY' ) {
          $values[] = ( ! empty ( $Entry['Repeat'] ) )? 'M': 'E';
        } else if ( $Entry['CalendarType'] == 'VTODO' ) {
          $values[] = ( ! empty ( $Entry['Repeat'] ) )? 'N': 'T';
        }
      }
      if ( strlen ( $Entry['Summary'] ) == 0 )
        $Entry['Summary'] = translate ( 'Unnamed Event' );
      if ( empty ( $Entry['Description'] ) )
        $Entry['Description'] = $Entry['Summary'];
      $Entry['Summary'] = str_replace ( "\\n", "\n", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "\\'", "'", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "\\\"", "\"", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "'", "\\'", $Entry['Summary'] );
      $names[] = 'cal_name';
      $values[] = $Entry['Summary'];
      $Entry['Description'] = str_replace ( "\\n", "\n", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "\\'", "'", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "\\\"", "\"", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "'", "\\'", $Entry['Description'] );
      // added these to try and compensate for Sunbird escaping html
      $Entry['Description'] = str_replace ( "\;", ";", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "\,", ",", $Entry['Description'] );
      // Mozilla will send this goofy string, so replace it with real html
      $Entry['Description'] = str_replace ( '=0D=0A=', '<br />',
        $Entry['Description'] );
      $Entry['Description'] = str_replace ( '=0D=0A', '',
        $Entry['Description'] );
      // Allow option to not limit description size
      // This will only be practical for mysql and MSSQL/Postgres as
      // these do not have limits on the table definition
      // TODO Add this option to preferences
      if ( empty ( $LIMIT_DESCRIPTION_SIZE ) || $LIMIT_DESCRIPTION_SIZE == 'Y' ) {
        // limit length to 1024 chars since we setup tables that way
        if ( strlen ( $Entry['Description'] ) >= 1024 ) {
          $Entry['Description'] = substr ( $Entry['Description'], 0, 1019 )
           . '...';
        }
      }
      $names[] = 'cal_description';
      $values[] = $Entry['Description'];
      // do_debug ( "descr='" . $Entry['Description'] . "'" );
      $sql_params = array ();
      $namecnt = count ( $names );
      if ( $updateMode ) {
        $sql = 'UPDATE webcal_entry SET ';
        for ( $f = 0; $f < $namecnt; $f++ ) {
          if ( $f > 0 )
            $sql .= ', ';
          $sql .= $names[$f] . ' = ?';
          $sql_params[] = $values[$f];
        }
        $sql .= ' WHERE cal_id = ?';
        $sql_params[] = $id;
      } else {
        $string_names = '';
        $string_values = '';
        for ( $f = 0; $f < $namecnt; $f++ ) {
          if ( $f > 0 ) {
            $string_names .= ', ';
            $string_values .= ', ';
          }
          $string_names .= $names[$f];
          $string_values .= '?';
          $sql_params[] = $values[$f];
        }
        $sql = 'INSERT INTO webcal_entry ( ' . $string_names . ' ) VALUES ( '
         . $string_values . ' )';
      }
      // do_debug ( "SQL> $sql" );
      if ( empty ( $error ) ) {
        if ( ! dbi_execute ( $sql, $sql_params ) ) {
          $error .= db_error ();
          // do_debug ( $error );
          break;
        } else if ( $ImportType == 'RMTICS' ) {
          $count_suc++;
        }
      }
      // log add/update
      if ( $Entry['CalendarType'] == 'VTODO' ) {
        activity_log ( $id, $login, $calUser,
          $updateMode ? LOG_UPDATE_T : LOG_CREATE_T, 'Import from '
           . $ImportType );
      } else {
        activity_log ( $id, $login, $calUser,
          $updateMode ? LOG_UPDATE : LOG_CREATE, 'Import from ' . $ImportType );
      }
      // not in icalclient
      if ( $single_user == 'Y' ) {
        $participants[0] = $single_user_login;
      }
      // Now add to webcal_import_data
      if ( ! $updateMode ) {
        // only in icalclient
        // add entry to webcal_import and webcal_import_data
        $uid = generate_uid ( $id );
        $uid = empty ( $Entry['UID'] ) ? $uid : $Entry['UID'];
        if ( $importId < 0 ) {
          $importId = create_import_instance ();
        }

        if ( $ImportType == 'PALMDESKTOP' ) {
          $sql = 'INSERT INTO webcal_import_data ( cal_import_id, cal_id,
            cal_login, cal_import_type, cal_external_id )
            VALUES ( ?, ?, ?, ?, ? )';
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_execute ( $sql, array ( $importId, $id,
                $calUser, 'palm', $Entry['RecordID'] ) ) ) {
            $error = db_error ();
            break;
          }
        } else if ( $ImportType == 'VCAL' ) {
          $uid = empty ( $Entry['UID'] ) ? null : $Entry['UID'];
          if ( strlen ( $uid ) > 200 )
            $uid = null;
          $sql = 'INSERT INTO webcal_import_data ( cal_import_id, cal_id,
            cal_login, cal_import_type, cal_external_id )
            VALUES ( ?, ?, ?, ?, ? )';
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_execute ( $sql, array ( $importId, $id, $calUser, 'vcal', $uid ) ) ) {
            $error = db_error ();
            break;
          }
        } else if ( $ImportType == 'ICAL' ) {
          $uid = empty ( $Entry['UID'] ) ? null : $Entry['UID'];
          // This may cause problems
          if ( strlen ( $uid ) > 200 )
            $uid = substr ( $uid, 0, 200 );
          $sql = 'INSERT INTO webcal_import_data ( cal_import_id, cal_id,
            cal_login, cal_import_type, cal_external_id )
            VALUES ( ?, ?, ?, ?, ? )';
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_execute ( $sql, array ( $importId, $id, $calUser, 'ical', $uid ) ) ) {
            $error = db_error ();
            break;
          }
        }
      }
      // Now add participants
      $status = ( ! empty ( $Entry['Status'] ) ? $Entry['Status'] : 'A' );
      $percent = ( ! empty ( $Entry['Percent'] ) ? $Entry['Percent'] : '0' );
      if ( ! $updateMode ) {
        $sql = 'INSERT INTO webcal_entry_user
          ( cal_id, cal_login, cal_status, cal_percent )
          VALUES ( ?, ?, ?, ? )';
        // do_debug ( "SQL> $sql" );
        if ( ! dbi_execute ( $sql, array ( $id, $participants[0], $status,
          $percent ) ) ) {
          $error = db_error ();
          // do_debug ( "Error: " . $error );
          break;
        }
      } else {
        // do_debug ( "SQL> $sql" );
        $sql = 'UPDATE webcal_entry_user SET cal_status = ?
          WHERE cal_id = ?';
        if ( ! dbi_execute ( $sql, array ( $status, $id ) ) ) {
          $error = db_error ();
          // do_debug ( "Error: " . $error );
          break;
        }
        // update percentage only if set
        if ( $percent != '' ) {
          $sql = 'UPDATE webcal_entry_user SET cal_percent = ?
            WHERE cal_id = ?';
          if ( ! dbi_execute ( $sql, array ( $percent, $id ) ) ) {
            $error = db_error ();
            // do_debug ( "Error: " . $error );
            break;
          }
        }
        dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cal_id = ?',
          array ( $id ) );
      }
      // update Categories
      if ( ! empty ( $Entry['Categories'] ) ) {
        $cat_ids = $Entry['Categories'];
        $cat_order = 1;
        foreach ( $cat_ids as $cat_id ) {
          $sql = 'INSERT INTO webcal_entry_categories
            ( cal_id, cat_id, cat_order, cat_owner ) VALUES ( ?, ?, ?, ? )';

          if ( ! dbi_execute ( $sql, array ( $id, $cat_id, $cat_order++, $login ) ) ) {
            $error = db_error ();
            // do_debug ( "Error: " . $error );
            break;
          }
        }
      }
      // Add repeating info
      if ( $updateMode ) {
        // remove old repeating info
        dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?',
          array ( $id ) );
        dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?',
          array ( $id ) );
      }
      $names = array ();
      $values = array ();
      if ( ! empty ( $Entry['Repeat']['Frequency'] ) ) {
        $names[] = 'cal_id';
        $values[] = $id;

        $names[] = 'cal_type';
        $values[] = RepeatType( $Entry['Repeat']['Frequency'] );

        $names[] = 'cal_frequency';
        $values[] = ( ! empty ( $Entry['Repeat']['Interval'] ) ?
          $Entry['Repeat']['Interval'] : 1 );

        if ( ! empty ( $Entry['Repeat']['ByMonth'] ) ) {
          $names[] = 'cal_bymonth';
          $values[] = $Entry['Repeat']['ByMonth'];
        }

        if ( ! empty ( $Entry['Repeat']['ByMonthDay'] ) ) {
          $names[] = 'cal_bymonthday';
          $values[] = $Entry['Repeat']['ByMonthDay'];
        }
        if ( ! empty ( $Entry['Repeat']['ByDay'] ) ) {
          $names[] = 'cal_byday';
          $values[] = $Entry['Repeat']['ByDay'];
        }
        if ( ! empty ( $Entry['Repeat']['BySetPos'] ) ) {
          $names[] = 'cal_bysetpos';
          $values[] = $Entry['Repeat']['BySetPos'];
        }
        if ( ! empty ( $Entry['Repeat']['ByWeekNo'] ) ) {
          $names[] = 'cal_byweekno';
          $values[] = $Entry['Repeat']['ByWeekNo'];
        }
        if ( ! empty ( $Entry['Repeat']['ByYearDay'] ) ) {
          $names[] = 'cal_byyearday';
          $values[] = $Entry['Repeat']['ByYearDay'];
        }
        if ( ! empty ( $Entry['Repeat']['Wkst'] ) ) {
          $names[] = 'cal_wkst';
          $values[] = $Entry['Repeat']['Wkst'];
        }

        if ( ! empty ( $Entry['Repeat']['Count'] ) ) {
          $names[] = 'cal_count';
          $values[] = $Entry['Repeat']['Count'];
        }

        if ( ! empty ( $Entry['Repeat']['Until'] ) ) {
          $REND = localtime ( $Entry['Repeat']['Until'] );
          if ( ! empty ( $Entry['Repeat']['Count'] ) ) {
            // Get end time from DTSTART
            $RENDTIME = $Entry['start_time'];
          } else {
            $RENDTIME = gmdate ( 'His', $Entry['Repeat']['Until'] );
          }
          $names[] = 'cal_end';
          $values[] = gmdate ( 'Ymd', $Entry['Repeat']['Until'] );
          // if ( $RENDTIME != '000000' ) {
          $names[] = 'cal_endtime';
          $values[] = $RENDTIME;
          // }
        }

        $string_names = '';
        $string_values = '';
        $sql_params = array ();
        $namecnt = count ( $names );
        for ( $f = 0; $f < $namecnt; $f++ ) {
          if ( $f > 0 ) {
            $string_names .= ', ';
            $string_values .= ', ';
          }
          $string_names .= $names[$f];
          $string_values .= '?';
          $sql_params[] = $values[$f];
        }
        $sql = 'INSERT INTO webcal_entry_repeats ( ' . $string_names
         . ' ) VALUES ( ' . $string_values . ' )';

        if ( ! dbi_execute ( $sql, $sql_params ) ) {
          $error = 'Unable to add to webcal_entry_repeats: '
           . dbi_error () . "<br /><br />\n<b>SQL:</b> $sql";
          break;
        }
        // Repeating Exceptions...
        if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
          foreach ( $Entry['Repeat']['Exceptions'] as $ex_date ) {
            $ex_date = gmdate ( 'Ymd', $ex_date );
            $sql = 'INSERT INTO webcal_entry_repeats_not
              ( cal_id, cal_date, cal_exdate ) VALUES ( ?,?,? )';

            if ( ! dbi_execute ( $sql, array ( $id, $ex_date, 1 ) ) ) {
              $error = 'Unable to add to webcal_entry_repeats_not: ' .
              dbi_error () . "<br /><br />\n<b>SQL:</b> $sql";
              break;
            }
          }
        }
        // Repeating Inclusions...
        if ( ! empty ( $Entry['Repeat']['Inclusions'] ) ) {
          foreach ( $Entry['Repeat']['Inclusions'] as $inc_date ) {
            $inc_date = gmdate ( 'Ymd', $inc_date );
            $sql = 'INSERT INTO webcal_entry_repeats_not
              ( cal_id, cal_date, cal_exdate ) VALUES ( ?,?,? )';

            if ( ! dbi_execute ( $sql, array ( $id, $inc_date, 0 ) ) ) {
              $error = 'Unable to add to webcal_entry_repeats_not: ' .
              dbi_error () . "<br /><br />\n<b>SQL:</b> $sql";
              break;
            }
          }
        }
      } // End Repeat
      // Add Alarm info
      if ( $updateMode )
        dbi_execute ( 'DELETE FROM webcal_reminders WHERE  cal_id = ?',
         array ( $id ) );

      if ( ! empty ( $Entry['AlarmSet'] ) && $Entry['AlarmSet'] == 1 ) {
        $names = array ();
        $values = array ();

        $names[] = 'cal_id';
        $values[] = $id;
        if ( ! empty ( $Entry['ADate'] ) ) {
          $names[] = 'cal_date';
          $values[] = $Entry['ADate'];
        }
        if ( ! empty ( $Entry['AOffset'] ) ) {
          $names[] = 'cal_offset';
          $values[] = $Entry['AOffset'];
        }
        if ( ! empty ( $Entry['ADuration'] ) ) {
          $names[] = 'cal_duration';
          $values[] = $Entry['ADuration'];
        }
        if ( ! empty ( $Entry['ARepeat'] ) ) {
          $names[] = 'cal_repeats';
          $values[] = $Entry['ARepeat'];
        }
        if ( ! empty ( $Entry['ABefore'] ) ) {
          $names[] = 'cal_before';
          $values[] = $Entry['ABefore'];
        }
        if ( ! empty ( $Entry['ARelated'] ) ) {
          $names[] = 'cal_related';
          $values[] = $Entry['ARelated'];
        }
        if ( ! empty ( $Entry['AAction'] ) ) {
          $names[] = 'cal_action';
          $values[] = $Entry['AAction'];
        }
        $string_names = '';
        $string_values = '';
        $sql_params = array ();
        $namecnt = count ( $names );
        for ( $f = 0; $f < $namecnt; $f++ ) {
          if ( $f > 0 ) {
            $string_names .= ', ';
            $string_values .= ', ';
          }
          $string_names .= $names[$f];
          $string_values .= '?';
          $sql_params[] = $values[$f];
        }
        $sql = 'INSERT INTO webcal_reminders (' . $string_names . ' ) '
         . ' VALUES ( ' . $string_values . ' )';
        if ( ! dbi_execute ( $sql, $sql_params ) )
          $error = db_error ();
      }
    }
    // here to end not in icalclient
    if ( $subType != 'icalclient' && $subType != 'remoteics' ) {
      if ( ! empty ( $error ) && empty ( $overlap ) ) {
        $error_num++;
        echo print_error ( $error ) . "\n<br />\n";
      }
      if ( $Entry['Duration'] > 0 ) {
        $time = trim( display_time ( '', 0, $Entry['StartTime'] )
           . '-' . display_time ( '', 2, $Entry['EndTime'] ) );
      }
      // Conflicting
      if ( ! empty ( $overlap ) ) {
        echo '<b><h2>' .
        translate ( 'Scheduling Conflict' ) . ': ';
        $count_con++;
        echo '</h2></b>';

        $dd = date ( 'm-d-Y', $Entry['StartTime'] );
        $Entry['Summary'] = str_replace ( "''", "'", $Entry['Summary'] );
        $Entry['Summary'] = str_replace ( "'", "\\'", $Entry['Summary'] );
        echo htmlspecialchars ( $Entry['Summary'] );
        echo ' (' . $dd;
        if ( ! empty ( $time ) )
          echo '&nbsp; ' . $time;
        echo ")<br />\n";
        etranslate ( 'conflicts with the following existing calendar entries' );
        echo ":<ul>\n" . $overlap . "</ul>\n";
      } else {
        // No Conflict
        if ( $count_suc == 0 ) {
          echo '<b><h2>' .
          translate ( 'Event Imported' ) . ":</h2></b><br />\n";
        }
        $count_suc++;

        $dd = $Entry['start_date'];
        echo "<a class=\"entry\" href=\"view_entry.php?id=$id";
        echo '" title="' . translate ( 'View this entry' ) . '">';
        $Entry['Summary'] = str_replace( "''", "'", $Entry['Summary'] );
        $Entry['Summary'] = str_replace( "\\", ' ', $Entry['Summary'] );
        echo htmlspecialchars ( $Entry['Summary'] ). '</a> ( ' . $dd;
        
        if ( isset ( $Entry['AllDay'] )  && $Entry['AllDay'] == 1)
          echo '&nbsp; ' . translate ( 'All day event' );
        else if ( ! empty ( $time ) )
          echo '&nbsp; ' . $time;
        echo " )<br /><br />\n";
      }
      // Reset Variables
      $overlap = $error = $dd = $time = '';
    }
    // Mark old events from prior import as deleted.
    if ( $overwrite && count ( $oldUIDs ) > 0 ) {
      // We could do this with a single SQL using sub-select, but
      // I'm pretty sure MySQL does not support it.
      $old = array_keys ( $oldUIDs );
      $oldcnt = count ( $old );
      for ( $i = 0; $i < $oldcnt; $i++ ) {
        $sql = 'SELECT cal_id FROM webcal_import_data
          WHERE cal_import_type = ? AND cal_external_id = ?
          AND cal_login = ? AND cal_id < ?';
        $res = dbi_execute ( $sql, array ( $type, $old[$i], $calUser, $firstEventId ) );
        if ( $res ) {
          while ( $row = dbi_fetch_row ( $res ) ) {
            $oldIds[] = $row[0];
          }
          dbi_free_result ( $res );
        } else {
          echo db_error () . "<br />\n";
        }
      }
      $oldidcnt = count ( $oldIds );
      for ( $i = 0; $i < $oldidcnt; $i++ ) {
        $sql = "UPDATE webcal_entry_user SET cal_status = 'D' "
         . "WHERE cal_id = ?";
        $sqlLog .= $sql . "<br />\n";
        dbi_execute ( $sql, array ( $oldIds[$i] ) );
        $numDeleted++;
      }
    }
  }
}

/*Functions from import_ical.php
 * This file incudes functions for parsing iCal data files during
 * an import.
 *
 * It will be included by import_handler.php.
 *
 * The iCal specification is available online at:
 * http://www.ietf.org/rfc/rfc2445.txt
 *
 */
// Parse the ical file and return the data hash.
// NOTE!!!!!
// There seems to be a bug in certain versions of PHP where the fgets ()
// returns a blank string when reading stdin. I found this to be
// a problem with PHP 4.1.2 on Linux.
// It did work correctly with PHP 5.0.2.
function parse_ical ( $cal_file, $source = 'file' ) {
  global $tz, $errormsg;
  $ical_data = array ();
  if ( $source == 'file' || $source == 'remoteics' ) {
    if ( ! $fd = @fopen ( $cal_file, 'r' ) ) {
      $errormsg .= "Can't read temporary file: $cal_file\n";
      exit ();
    } else {
      // Read in contents of entire file first
      $data = '';
      $line = 0;
      while ( ! feof( $fd ) && empty ( $error ) ) {
        $line++;
        $data .= fgets( $fd, 4096 );
      }
      fclose ( $fd );
    }
  } else if ( $source == 'icalclient' ) {
    //do_debug ( "before fopen on stdin..." );
    $stdin = fopen ( 'php://input', 'rb' );
    // $stdin = fopen ("/dev/stdin", "r");
    // $stdin = fopen ("/dev/fd/0", "r");
    //do_debug ( "after fopen on stdin..." );
    // Read in contents of entire file first
    $data = '';
    $cnt = 0;
    while ( ! feof ( $stdin ) ) {
      $line = fgets ( $stdin, 1024 );
      $cnt++;
      // do_debug ( "cnt = " . ( ++$cnt ) );
      $data .= $line;
      if ( $cnt > 10 && strlen ( $data ) == 0 ) {
        // do_debug ( "Read $cnt lines of data, but got no data :-(" );
        // do_debug ( "Informing user of PHP server bug (PHP v" . phpversion () . ")" );
        // Note: Mozilla Calendar does not display this error for some reason.
        echo '<br /><b>Error:</b> Your PHP server ' . phpversion ()
         . ' seems to have a bug reading stdin. '
         . 'Try upgrading to a newer PHP release.<br />';
        exit;
      }
    }
    fclose ( $stdin );
    // do_debug ( "strlen (data)=" . strlen ($data) );
    // Check for PHP stdin bug
    if ( $cnt > 5 && strlen ( $data ) < 10 ) {
       //do_debug ( "Read $cnt lines of data, but got no data :-(" );
       //do_debug ( "Informing user of PHP server bug" );
      header ( 'Content-Type: text/plain' );
      echo 'Error: Your PHP server ' . phpversion ()
       . ' seems to have a bug reading stdin.' . "\n"
       . 'Try upgrading to a newer release.' . "\n";
      exit;
    }
  }
  // Now fix folding. According to RFC, lines can fold by having
  // a CRLF and then a single white space character.
  // We will allow it to be CRLF, CR or LF or any repeated sequence
  // so long as there is a single white space character next.
  // echo "Orig:<br /><pre>$data</pre><br /><br />\n";
  // Special cases for  stupid Sunbird wrapping every line!
  $data = preg_replace ( "/[\r\n]+[\t ];/", ";", $data );
  $data = preg_replace ( "/[\r\n]+[\t ]:/", ":", $data );

  $data = preg_replace ( "/[\r\n]+[\t ]/", " ", $data );
  $data = preg_replace ( "/[\r\n]+/", "\n", $data );
  // echo "Data:<br /><pre>$data</pre><p>";
  // reflect the section where we are in the file:
  // VEVENT, VTODO, VJORNAL, VFREEBUSY, VTIMEZONE
  $state = 'NONE';
  $substate = 'none'; // reflect the sub section
  $subsubstate = ''; // reflect the sub-sub section
  $error = false;
  $line = 0;
  $event = '';
  $lines = explode ( "\n", $data );
  $linecnt = count ( $lines );
  for ( $n = 0; $n < $linecnt && ! $error; $n++ ) {
    $line++;
    if ( $line > 5 && $line < 10 && $state == 'NONE' ) {
      // we are probably not reading an ics file
      return false;
    }
    $buff = trim( $lines[$n] );
 
    if ( preg_match ( "/^PRODID:(.+)$/i", $buff, $match ) ) {
      $prodid = $match[1];
      $prodid = str_replace ( "-//", "", $prodid );
      $prodid = str_replace ( "\,", ",", $prodid );
      $event['prodid'] = $prodid;
      // do_debug ( "Product ID: " . $prodid );
    }
    // parser debugging code...
    // echo "line = $line<br />";
    // echo "state = $state<br />";
    // echo "substate = $substate<br />";
    // echo "subsubstate = $subsubstate<br />";
    // echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";
    if ( $state == 'VEVENT' || $state == 'VTODO' ) {
      if ( ! empty ( $subsubstate ) ) {
        if ( preg_match ( '/^END.*:(.+)$/i', $buff, $match ) ) {
          if ( $match[1] == $subsubstate )
            $subsubstate = '';

        } else if ( $subsubstate == 'VALARM' ) {
          if ( preg_match ( "/TRIGGER(.+)$/i", $buff, $match ) ) {
            // Example: TRIGGER;VALUE=DATE-TIME:19970317T133000Z
            $substate = 'alarm_trigger';
            $event[$substate] = $match[1];
          } else if ( preg_match ( "/ACTION.*:(.+)$/i", $buff, $match ) ) {
            $substate = 'alarm_action';
            $event[$substate] = $match[1];
          } else if ( preg_match ( "/REPEAT.*:(.+)$/i", $buff, $match ) ) {
            $substate = 'alarm_repeat';
            $event[$substate] = $match[1];
          } else if ( preg_match ( "/DURATION.*:(.+)$/i", $buff, $match ) ) {
            $substate = 'alarm_duration';
            $event[$substate] = $match[1];
          } else if ( preg_match ( "/RELATED.*:(.+)$/i", $buff, $match ) ) {
            $substate = 'alarm_related';
            $event[$substate] = $match[1];
          }
        }
      } else if ( preg_match ( '/^BEGIN.*:(.+)$/i', $buff, $match ) )
        $subsubstate = $match[1];
      //.
      // we suppose ': ' is on the same line as property name,
      // this can perhaps cause problems
      else if ( preg_match ( "/^SUMMARY\s*(;.+)?:(.+)$/iU", $buff, $match ) ) {
        $substate = 'summary';
        if ( stristr( $match[1], 'ENCODING=QUOTED-PRINTABLE' ) )
          $match[2] = quoted_printable_decode( $match[2] );
        $event[$substate] = $match[2];
      } elseif ( preg_match ( "/^DESCRIPTION\s*(;.+)?:(.+)$/iU", $buff, $match ) ) {
        $substate = 'description';
        if ( stristr( $match[1], 'ENCODING=QUOTED-PRINTABLE' ) )
          $match[2] = quoted_printable_decode( $match[2] );
        $event[$substate] = $match[2];
      } elseif ( preg_match ( "/^CLASS.*:(.*)$/i", $buff, $match ) ) {
        $substate = 'class';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^LOCATION.*?:(.+)$/i", $buff, $match ) ) {
        $substate = 'location';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^URL.*?:(.+)$/i", $buff, $match ) ) {
        $substate = 'url';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^TRANSP.*:(.+)$/i", $buff, $match ) ) {
        $substate = 'transparency';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^STATUS.*:(.*)$/i", $buff, $match ) ) {
        $substate = 'status';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^PRIORITY.*:(.*)$/i", $buff, $match ) ) {
        $substate = 'priority';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^DTSTART\s*(.*):\s*(.*)\s*$/i", $buff, $match ) ) {
        $substate = 'dtstart';
        $event[$substate] = $match[2];
        if ( preg_match ( "/TZID=(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtstartTzid';
          $event[$substate] = $submatch[1];
        } else if ( preg_match ( "/VALUE=\"{0,1}DATE-TIME\"{0,1}(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtstartDATETIME';
          $event[$substate] = true;
        } else if ( preg_match ( "/VALUE=\"{0,1}DATE\"{0,1}(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtstartDATE';
          $event[$substate] = true;
        }
      } elseif ( preg_match ( "/^DTEND\s*(.*):\s*(.*)\s*$/i", $buff, $match ) ) {
        $substate = 'dtend';
        $event[$substate] = $match[2];
        if ( preg_match ( "/TZID=(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtendTzid';
          $event[$substate] = $submatch[1];
        } else if ( preg_match ( "/VALUE=DATE-TIME(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtendDATETIME';
          $event[$substate] = true;
        } else if ( preg_match ( "/VALUE=DATE(.*)$/i", $match[1], $submatch ) ) {
          $substate = 'dtendDATE';
          $event[$substate] = true;
        }
      } elseif ( preg_match ( "/^DUE.*:\s*(.*)\s*$/i", $buff, $match ) ) {
        $substate = 'due';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^COMPLETED.*:\s*(.*)\s*$/i", $buff, $match ) ) {
        $substate = 'completed';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^PERCENT-COMPLETE.*:\s*(.*)\s*$/i", $buff, $match ) ) {
        $substate = 'percent';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( "/^DURATION.*:(.+)\s*$/i", $buff, $match ) ) {
        $substate = 'duration';
        $event[$substate] = parse_ISO8601_duration ( $match[1] );
      } elseif ( preg_match ( '/^RRULE.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'rrule';
        $event[$substate] = $match[1];
      } elseif ( preg_match ( '/^EXDATE.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'exdate';
        // allows multiple ocurrances of EXDATE to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $match[1];
         else
          $event[$substate] = $match[1];

      } elseif ( preg_match ( '/^RDATE.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'rdate';
        // allows multiple ocurrances of RDATE to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $match[1];
         else
          $event[$substate] = $match[1];

      } elseif ( preg_match ( '/^CATEGORIES.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'categories';
        // allows multiple ocurrances of CATEGORIES to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $match[1];
         else
          $event[$substate] = $match[1];

      } elseif ( preg_match ( '/^UID.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'uid';
        $event[$substate] = $match[1];
      } else if ( preg_match ( "/^BEGIN:VALARM/i", $buff ) ) {
        $subsubstate = 'VALARM';
      } elseif ( preg_match ( '/^END:VEVENT$/i', $buff, $match ) ) {
        if ( $tmp_data = format_ical ( $event ) ) $ical_data[] = $tmp_data;
        $state = 'VCALENDAR';
        $substate = 'none';
        $subsubstate = '';
        // clear out data for new event
        $event = '';
      } elseif ( preg_match ( "/^END:VTODO$/i", $buff, $match ) ) {
        if ( $tmp_data = format_ical ( $event ) ) $ical_data[] = $tmp_data;
        $state = 'VCALENDAR';
        $substate = 'none';
        $subsubstate = '';
        // clear out data for new event
        $event = '';
        // folded lines?, this shouldn't happen
      } elseif ( preg_match ( '/^\s(\S.*)$/', $buff, $match ) ) {
        if ( $substate != 'none' ) {
          $event[$substate] .= $match[1];
        } else {
          $errormsg .= "iCal parse error on line $line:<br />$buff\n";
          $error = true;
        }
        // For unsupported properties
      } else {
        $substate = 'none';
      }
    } elseif ( $state == 'VCALENDAR' ) {
      if ( preg_match ( "/^BEGIN:VEVENT/i", $buff ) ) {
        $state = 'VEVENT';
      } elseif ( preg_match ( "/^END:VCALENDAR/i", $buff ) ) {
        $state = 'NONE';
      } else if ( preg_match ( "/^BEGIN:VTIMEZONE/i", $buff ) ) {
        $state = 'VTIMEZONE';
        $event['VTIMEZONE'] = $buff;
      } else if ( preg_match ( "/^BEGIN:VTODO/i", $buff ) ) {
        $state = 'VTODO';
       } else if ( preg_match ( "/^BEGIN:VFREEBUSY/i", $buff ) ) {
         $state = 'VFREEBUSY';
         $freebusycount=0;
         $event['organizer'] = 'unknown_organizer';
      }
      $event['state'] = $state;
    } elseif ( $state == 'VTIMEZONE' ) {
      // We don't do much with timezone info yet...
      if ( preg_match ( '/^TZID.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'tzid';
        $event[$substate] = parse_tzid ( $match[1] );
        $buff = 'TZID:' . $event[$substate];
      }
      if ( preg_match ( '/^X-LIC-LOCATION.*:(.+)$/i', $buff, $match ) ) {
        $substate = 'tzlocation';
        $event[$substate] = $match[1];
      }
      if ( preg_match ( "/^DTSTART.*:(.+)$/i", $buff, $match ) ) {
        $substate = 'dtstart';
        if ( empty ( $event[$substate] ) || $match[1] < $event[$substate] )
          $event[$substate] = $match[1];
      }
      if ( preg_match ( "/^DTEND.*:(.+)$/i", $buff, $match ) ) {
        $substate = 'dtend';
        if ( empty ( $event[$substate] ) || $match[1] < $event[$substate] )
          $event[$substate] = $match[1];
      }
      $event['VTIMEZONE'] .= "\n" . $buff;
      if ( preg_match ( '/^END:VTIMEZONE$/i', $buff ) ) {
        save_vtimezone ( $event );
        $state = 'VCALENDAR';
      }
     }elseif ( $state == 'VFREEBUSY' ) {
       if ( preg_match ( '/^END:VFREEBUSY$/i', $buff, $match ) ) {
         $state = 'VCALENDAR';
         $substate = 'none';
         $subsubstate = '';
         $event = '';
       } elseif ( preg_match ( '/^ORGANIZER.*:(.+)$/i', $buff, $match ) ) {
         $substate = 'organizer';
         $event[$substate] = $match[1];
       } elseif ( preg_match ( '/^UID.*:(.+)$/i', $buff, $match ) ) {
         $substate = 'uid';
         $event[$substate] = $match[1];
       } elseif ( preg_match ( '/^FREEBUSY\s*(.*):\s*(.*)\/(.*)\s*$/i',
        $buff, $match ) ) {
         $substate = 'freebusy';
         $event['dtstart']=$match[2];
         $event['dtend']  =$match[3];
         if ( empty ($event['uid']) )
          $event['uid']=$freebusycount++.'-' . $event['organizer'];
 #
 # Let's save the FREEBUSY data as an event. While not a perfect solution, it's better
 # than nothing and allows Outlook users to store Free/Busy times in WebCalendar
 #
 # If not provided, UID is auto-generaated in an attempt to use WebCalendar's duplicate
 # prevention feature. There could be left-over events if the number of free/busy
 # entries decreases, but those entries will hopefullly be in the past so it won't matter.
 # Not a great solution, but I suspect it will work well.
 #
         if ( $tmp_data = format_ical ( $event ) ) $ical_data[] = $tmp_data;
         $event['dtstart']='';
         $event['dtend']  ='';
         $event['uid']  ='';
       } else {
         $substate = 'none';
       }
    } elseif ( $state == 'NONE' ) {
      if ( preg_match ( '/^BEGIN:VCALENDAR$/i', $buff ) )
        $state = 'VCALENDAR';
    }
  } // End while
  return $ical_data;
}
// Parse the hcal array
function parse_hcal ( $hcal_array ) {
  global $tz, $errormsg;
  $ical_data = array ();

  $error = false;
  $event = '';
  if ( ! is_array ( $hcal_array ) ) {
    return false;
  }
  foreach ( $hcal_array as $hcal ) {
    foreach ( $hcal as $key => $value ) {
      $value = trim( $value );
      // set default UID
      $event['uid'] = generate_uid ( 1 );
      // parser debugging code...
      // echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";
      if ( $key == 'SUMMARY' ) {
        $substate = 'summary';
        $event[$substate] = $value;
      } elseif ( $key == 'DESCRIPTION' ) {
        $substate = 'description';
        $event[$substate] = $value;
      } elseif ( $key == 'CLASS' ) {
        $substate = 'class';
        $event[$substate] = $value;
      } elseif ( $key == 'LOCATION' ) {
        $substate = 'location';
        $event[$substate] = $value;
      } elseif ( $key == 'URL' ) {
        $substate = 'url';
        $event[$substate] = $value;
      } elseif ( $key == 'TRANSP' ) {
        $substate = 'transparency';
        $event[$substate] = $value;
      } elseif ( $key == 'STATUS' ) {
        $substate = 'status';
        $event[$substate] = $value;
      } elseif ( $key == 'PRIORITY' ) {
        $substate = 'priority';
        $event[$substate] = $value;
      } elseif ( $key == 'DTSTART' ) {
        $substate = 'dtstart';
        $event[$substate] = $value;
        if ( strlen ( $value ) > 8 ) {
          $substate = 'dtstartDATETIME';
          $event[$substate] = true;
        } else {
          $substate = 'dtstartDATE';
          $event[$substate] = true;
        }
      } elseif ( $key == 'DTEND' ) {
        $substate = 'dtend';
        $event[$substate] = $value;
        if ( strlen ( $value ) > 8 ) {
          $substate = 'dtendDATETIME';
          $event[$substate] = true;
        } else {
          $substate = 'dtendDATE';
          $event[$substate] = true;
        }
      } elseif ( $key == 'TZ' ) {
        $substate = 'tzid';
        $event[$substate] = $value;
      } elseif ( $key == 'DUE' ) {
        $substate = 'due';
        $event[$substate] = $value;
      } elseif ( $key == 'COMPLETED' ) {
        $substate = 'completed';
        $event[$substate] = $value;
      } elseif ( $key == 'PERCENT-COMPLETE' ) {
        $substate = 'percent';
        $event[$substate] = $value;
      } elseif ( $key == 'DURATION' ) {
        $substate = 'duration';
        $event[$substate] = parse_ISO8601_duration ( $value );
      } elseif ( $key == 'RRULE' ) {
        $substate = 'rrule';
        $event[$substate] = $value;
      } elseif ( $key == 'EXDATE' ) {
        $substate = 'exdate';
        // allows multiple ocurrances of EXDATE to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $value;
         else
          $event[$substate] = $value;

      } elseif ( $key == 'RDATE' ) {
        $substate = 'rdate';
        // allows multiple ocurrances of RDATE to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $value;
         else
          $event[$substate] = $value;

      } elseif ( $key == 'CATEGORIES' ) {
        $substate = 'categories';
        // allows multiple ocurrances of CATEGORIES to be processed
        if ( isset ( $event[$substate] ) )
          $event[$substate] .= ',' . $value;
         else
          $event[$substate] = $value;

      } elseif ( $key == 'UID' ) {
        $substate = 'uid';
        $event[$substate] = $value;
      }
    } // End foreach $hcal
    $event['state'] = 'VEVENT';
    if ( $tmp_data = format_ical ( $event ) ) $hcal_data[] = $tmp_data;
    $event = '';
  } // End foreach $hcal_array
  return $hcal_data;
}
// Convert interval to webcal repeat type
function RepeatType ( $type ) {
  $Repeat = array ( 0, 'daily', 'weekly', 'monthlyByDay', 'monthlyByDate',
    'monthlyBySetPos', 'yearly', 'manual' );
  return $Repeat[$type];
}


// Convert ical format (yyyymmddThhmmssZ) to epoch time
function icaldate_to_timestamp ( $vdate, $tzid = '', $plus_d = '0',
  $plus_m = '0', $plus_y = '0' ) {
  global $SERVER_TIMEZONE, $calUser;
  $this_TIMEZONE = $Z = '';
  // Just in case, trim off leading/trailing whitespace.
  $vdate = trim ( $vdate );

  $user_TIMEZONE = get_pref_setting ( $calUser, 'TIMEZONE' );

  $H = $M = $S = 0;
  $y = substr ( $vdate, 0, 4 ) + $plus_y;
  $m = substr ( $vdate, 4, 2 ) + $plus_m;
  $d = substr ( $vdate, 6, 2 ) + $plus_d;
  if ( strlen ( $vdate ) > 8 ) {
    $H = substr ( $vdate, 9, 2 );
    $M = substr ( $vdate, 11, 2 );
    $S = substr ( $vdate, 13, 2 );
    $Z = substr ( $vdate, 15, 1 );
  }
  // if we get a Mozilla TZID we try to parse it
  $tzid = parse_tzid ( $tzid );

  // Sunbird does not do Timezone right so...
  // We'll just hardcode their GMT timezone def here
  switch ( $tzid ) {
    case '/Mozilla.org/BasicTimezones/GMT':
    case 'GMT':
      // I think this is the only real timezone set to UTC...since 1972 at least
      $this_TIMEZONE = 'Africa/Monrovia';
      $Z = 'Z';
      break;
    case 'US-Eastern':
    case 'US/Eastern':
      $this_TIMEZONE = 'America/New_York';
      break;
    case 'US-Central':
    case 'US/Central':
      $this_TIMEZONE = 'America/America/Chicago';
      break;
    case 'US-Pacific':
    case 'US/Pacific':
      $this_TIMEZONE = 'America/Los_Angeles';
      break;
    case '':
      break;
    default:
      $this_TIMEZONE = $tzid;
      break;
  } //end switch
  // Convert time from user's timezone to GMT if datetime value
  if ( empty ( $this_TIMEZONE ) ) {
    $this_TIMEZONE = ( ! empty ( $user_TIMEZONE ) ? $user_TIMEZONE : $SERVER_TIMEZONE );
  }
  if ( empty ( $Z ) ) {
    putenv ( "TZ=$this_TIMEZONE" );
    $TS = mktime ( $H, $M, $S, $m, $d, $y );
  } else {
    $TS = gmmktime ( $H, $M, $S, $m, $d, $y );
  }
  set_env ( 'TZ', $user_TIMEZONE );
  return $TS;
}
// Put all ical data into import hash structure
function format_ical ( $event ) {
  global $login;

  // Set Product ID
  $fevent['Prodid'] = ( ! empty ( $event['prodid'] ) ? $event['prodid'] : '' );

  // Set Calendar Type for easier processing later
  $fevent['CalendarType'] = $event['state'];

  $fevent['Untimed'] = $fevent['AllDay'] = 0;
  // Categories
  if ( isset ( $event['categories'] ) ) {
    // $fevent['Categories']  will contain an array of cat_id(s) that match the
    // category_names
    $fevent['Categories'] = get_categories_id_byname ( utf8_decode ( $event['categories'] ) );
  }
  // Start and end time
  /* Snippet from RFC2445
  For cases where a "VEVENT" calendar component specifies a "DTSTART"
   property with a DATE data type but no "DTEND" property, the events
   non-inclusive end is the end of the calendar date specified by the
   "DTSTART" property. For cases where a "VEVENT" calendar component
   specifies a "DTSTART" property with a DATE-TIME data type but no
   "DTEND" property, the event ends on the same calendar date and time
   of day specified by the "DTSTART" property. */

  $dtstartTzid = ( ! empty ( $event['dtstartTzid'] )?$event['dtstartTzid'] : '' );
  $fevent['StartTime'] = icaldate_to_timestamp ( $event['dtstart'], $dtstartTzid );
  if ( isset ( $event['dtend'] ) ) {
    $dtendTzid = ( ! empty ( $event['dtendTzid'] )?$event['dtendTzid'] : '' );
    $fevent['EndTime'] = icaldate_to_timestamp ( $event['dtend'], $dtendTzid );
    if ( $fevent['StartTime'] == $fevent['EndTime'] ) {
      $fevent['Untimed'] = 1;
      $fevent['Duration'] = 0;
    } else {
      $fevent['Duration'] = ( $fevent['EndTime'] - $fevent['StartTime'] ) / 60;
    }
  } else if ( isset ( $event['duration'] ) ) {
    $fevent['EndTime'] = $fevent['StartTime'] + $event['duration'] * 60;
    $fevent['Duration'] = $event['duration'];
  } else if ( isset ( $event['dtstartDATETIME'] ) ) {
    // Untimed
    $fevent['EndTime'] = $fevent['StartTime'];
    $fevent['Untimed'] = 1;
    $fevent['Duration'] = 0;
  }

  if ( isset ( $event['dtstartDATE'] ) && ! isset ( $event['dtendDATE'] ) ) {
    // Untimed
    $fevent['StartTime'] = icaldate_to_timestamp ( $event['dtstart'], 'GMT' );
    $fevent['EndTime'] = $fevent['StartTime'];
    $fevent['Untimed'] = 1;
    $fevent['Duration'] = 0;
    //$fevent['EndTime'] = $fevent['StartTime'] + 86400;
    //$fevent['AllDay'] = 1;
    //$fevent['Duration'] = 1440;
  } else if ( isset ( $event['dtstartDATE'] ) && isset ( $event['dtendDATE'] ) ) {
    $fevent['StartTime'] = icaldate_to_timestamp ( $event['dtstart'], 'GMT' );
    // This is an untimed event
    if ( $event['dtstart']  == $event['dtend'] ) {
      $fevent['EndTime'] = $fevent['StartTime'];
      $fevent['Untimed'] = 1;
      $fevent['Duration'] = 0;
    } else {
      $fevent['EndTime'] = icaldate_to_timestamp ( $event['dtend'], 'GMT' );
      $fevent['Duration'] = ( $fevent['EndTime'] - $fevent['StartTime'] ) / 60;
      if ( $fevent['Duration'] == 1440 ) $fevent['AllDay'] = 1;
    }
  }
  // catch 22
  if ( ! isset ( $fevent['EndTime'] ) ) {
    $fevent['EndTime'] = $fevent['StartTime'];
  }
  if ( ! isset ( $fevent['Duration'] ) ) {
    $fevent['Duration'] = 0;
  }
  if ( empty ( $event['summary'] ) )
    $event['summary'] = translate ( 'Unnamed Event' );
  $fevent['Summary'] = utf8_decode ( $event['summary'] );
  if ( ! empty ( $event['description'] ) ) {
    $fevent['Description'] = utf8_decode ( $event['description'] );
  } else {
    $fevent['Description'] = $fevent['Summary'];
  }

  if ( ! empty ( $event['class'] ) ) {
    // Added  Confidential as new CLASS
    if ( preg_match ( '/private/i', $event['class'] ) ) {
      $fevent['Class'] = 'R';
    } elseif ( preg_match ( '/confidential/i', $event['class'] ) ) {
      $fevent['Class'] = 'C';
    } else {
      $fevent['Class'] = 'P';
    }
  }

  $fevent['UID'] = $event['uid'];
  // Process VALARM stuff
  if ( ! empty ( $event['alarm_trigger'] ) ) {
    $fevent['AlarmSet'] = 1;
    if ( preg_match ( "/VALUE=DATE-TIME:(.*)$/i", $event['alarm_trigger'], $match ) ) {
      $fevent['ADate'] = icaldate_to_timestamp ( $match[1] );
    } else {
      $duration = parse_ISO8601_duration ( $event['alarm_trigger'] );
      $fevent['AOffset'] = abs ( $duration );
      $fevent['ABefore'] = ( $duration < 0 ? 'N':'Y' );
    }

    if ( ! empty ( $event['alarm_action'] ) ) {
      $fevent['AAction'] = $event['alarm_action'];
    }
    if ( ! empty ( $event['alarm_repeat'] ) ) {
      $fevent['ARepeat'] = $event['alarm_repeat'];
    }
    if ( ! empty ( $event['alarm_duration'] ) ) {
      $fevent['ADuration'] = abs ( parse_ISO8601_duration ( $event['alarm_duration'] ) );
    }
    if ( ! empty ( $event['alarm_related'] ) ) {
      $fevent['ARelated'] = ( $event['alarm_related'] == 'END'? 'E':'S' );
    }
  }

  if ( ! empty ( $event['status'] ) ) {
    switch ( $event['status'] ) {
      case 'TENTATIVE':
        // case 'NEEDS-ACTION': Sunbird sets this if you touch task without
        // changing anything else. Not sure about other clients yet
        $fevent['Status'] = 'W';
        break;
      case 'CONFIRMED':
      case 'ACCEPTED':
        $fevent['Status'] = 'A';
        break;
      case 'CANCELLED';
        $fevent['Status'] = 'D';
        break;
      case 'DECLINED':
        $fevent['Status'] = 'R';
        break;
      case 'COMPLETED':
        $fevent['Status'] = 'C';
        break;
      case 'IN-PROGRESS':
        $fevent['Status'] = 'P';
        break;
      default:
        $fevent['Status'] = 'A';
        break;
    } //end switch
  } else {
    $fevent['Status'] = 'A';
  }

  if ( ! empty ( $event['location'] ) ) {
    $fevent['Location'] = utf8_decode ( $event['location'] );
  }

  if ( ! empty ( $event['url'] ) ) {
    $fevent['URL'] = utf8_decode ( $event['url'] );
  }

  if ( ! empty ( $event['priority'] ) ) {
    $fevent['PRIORITY'] = $event['priority'];
  }

  if ( ! empty ( $event['transparency'] ) ) {
    if ( preg_match ( '/TRANSPARENT/i', $event['transparency'] )
        OR $event['transparency'] == 1 ) {
      $fevent['Transparency'] = 1;
    } else {
      $fevent['Transparency'] = 0;
    }
  } else {
    $fevent['Transparency'] = 0;
  }
  // VTODO specific items
  if ( ! empty ( $event['due'] ) ) {
    $fevent['Due'] = $event['due'];
  }

  if ( ! empty ( $event['completed'] ) ) {
    $fevent['Completed'] = $event['completed'];
  }

  if ( ! empty ( $event['percent'] ) ) {
    $fevent['Percent'] = $event['percent'];
  }
  // Repeating exceptions
  $fevent['Repeat']['Exceptions'] = array ();
  if ( ! empty ( $event['exdate'] ) && $event['exdate'] ) {
    $EX = explode ( ',', $event['exdate'] );
    foreach ( $EX as $exdate ) {
      $fevent['Repeat']['Exceptions'][] = icaldate_to_timestamp ( $exdate );
    }
    $fevent['Repeat']['Frequency'] = 7; //manual, this can be changed later
  } // Repeating inclusions
  $fevent['Repeat']['Inclusions'] = array ();
  if ( ! empty ( $event['rdate'] ) && $event['rdate'] ) {
    $R = explode ( ',', $event['rdate'] );
    foreach ( $R as $rdate ) {
      $fevent['Repeat']['Inclusions'][] = icaldate_to_timestamp ( $rdate );
    }
    $fevent['Repeat']['Frequency'] = 7; //manual, this can be changed later
  }
  /* Repeats
  Snippet from RFC2445
 If multiple BYxxx rule parts are specified, then after evaluating the
   specified FREQ and INTERVAL rule parts, the BYxxx rule parts are
   applied to the current set of evaluated occurrences in the following
   order: BYMONTH, BYWEEKNO, BYYEARDAY, BYMONTHDAY, BYDAY, BYHOUR,
   BYMINUTE, BYSECOND and BYSETPOS; then COUNT and UNTIL are evaluated.
 */
  // Handle RRULE
  if ( ! empty ( $event['rrule'] ) ) {
    // default value
    // first remove any UNTIL that may have been calculated above
    unset ( $fevent['Repeat']['Until'] );
    // split into pieces
    // echo "RRULE line: $event[rrule]<br />\n";
    $RR = explode ( ';', $event['rrule'] );
    // create an associative array of key-value pairs in $RR2[]
    $rrcnt = count ( $RR );
    for ( $i = 0; $i < $rrcnt; $i++ ) {
      $ar = explode ( '=', $RR[$i] );
      $RR2[$ar[0]] = $ar[1];
    }
    for ( $i = 0; $i < $rrcnt; $i++ ) {
      if ( preg_match ( "/^FREQ=(.+)$/i", $RR[$i], $match ) ) {
        if ( preg_match ( "/YEARLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Frequency'] = 6;
        } else if ( preg_match ( "/MONTHLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Frequency'] = 3; //MonthByDay
        } else if ( preg_match ( "/WEEKLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Frequency'] = 2;
        } else if ( preg_match ( "/DAILY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Frequency'] = 1;
        } else {
          // not supported :-(
          // but don't overwrite Manual setting from above
          if ( $fevent['Repeat']['Frequency'] != 7 )
            $fevent['Repeat']['Frequency'] = 0;
          echo "Unsupported iCal FREQ value \"$match[1]\"<br />\n";
          // Abort this import
          return;
        }
      } else if ( preg_match ( "/^INTERVAL=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['Interval'] = $match[1];
      } else if ( preg_match ( "/^UNTIL=(.+)$/i", $RR[$i], $match ) ) {
        // specifies an end date
        $fevent['Repeat']['Until'] = icaldate_to_timestamp ( $match[1] );
      } else if ( preg_match ( "/^COUNT=(.+)$/i", $RR[$i], $match ) ) {
        // specifies the number of repeats
        // We convert this to a true UNTIL after we parse exceptions
        $fevent['Repeat']['Count'] = $match[1];
      } else if ( preg_match ( "/^BYSECOND=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal BYSECOND value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYMINUTE=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal BYMINUTE value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYHOUR=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal BYHOUR value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYMONTH=(.+)$/i", $RR[$i], $match ) ) {
        // this event repeats during the specified months
        $fevent['Repeat']['ByMonth'] = $match[1];
      } else if ( preg_match ( "/^BYDAY=(.+)$/i", $RR[$i], $match ) ) {
        // this array contains integer offset (i.e. 1SU,1MO,1TU)
        $fevent['Repeat']['ByDay'] = $match[1];
      } else if ( preg_match ( "/^BYMONTHDAY=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['ByMonthDay'] = $match[1];
        // $fevent['Repeat']['Frequency'] = 3; //MonthlyByDay
      } else if ( preg_match ( "/^BYSETPOS=(.+)$/i", $RR[$i], $match ) ) {
        // if not already Yearly, mark as MonthlyBySetPos
        if ( $fevent['Repeat']['Frequency'] != 6 )
          $fevent['Repeat']['Frequency'] = 5;
        $fevent['Repeat']['BySetPos'] = $match[1];
      } else if ( preg_match ( "/^BYWEEKNO=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['ByWeekNo'] = $match[1];
      } else if ( preg_match ( "/^BYYEARDAY=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['ByYearDay'] = $match[1];
      } else if ( preg_match ( "/^WKST=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['Wkst'] = $match[1];
      }
    }
  } // end if rrule
  return $fevent;
}
// Figure out days of week for BYDAY values
// If value has no numeric offset, then set it's corresponding
// day value to  f. This selection is arbritary but gives
// plenty of room on either side to adjust because we need
// to allow values from -5 to +5
// For example  MO = f, -1MO = e, -2MO = d, +2MO - g, +3MO =h
// Note: f = chr(102) and 'n' is still a not present value
function rrule_repeat_days ( $RA ) {
  global $byday_names;
  
  $ret = array ();
  foreach ( $RA as $item ) {
    $item = strtoupper ( $item );
    if ( in_array ( $item, $byday_names ) )
      $ret[] = $item;
  }
  
  return ( empty ( $ret ) ? false : implode ( ',', $ret ) );
}
// Convert PYMDTHMS format to minutes
function parse_ISO8601_duration ( $duration ) {
  // we'll skip Years and Months
  $const = array ( 'M' => 1,
    'H' => 60,
    'D' => 1440,
    'W' => 10080
    );
  $ret = 0;
  $result = preg_split ( '/(P|D|T|H|M)/', $duration, -1,
    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
  $resultcnt = count ( $result );
  for ( $i = 0; $i < $resultcnt; $i++ ) {
    if ( is_numeric ( $result[$i] ) && isset ( $result[$i + 1] ) ) {
      $ret += ( $result[$i] * $const[$result[$i + 1]] );
    }
  }
  if ( $result[0] == '-' ) $ret = - $ret;
  return $ret;
}

// Functions from import_vcal.php
// Parse the vcal file and return the data hash.
function parse_vcal( $cal_file ) {
  global $tz, $errormsg;

  $vcal_data = array ();
  // echo "Parsing vcal file...<br />\n";
  if ( ! $fd = @fopen ( $cal_file, 'r' ) ) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit ();
  } else {
    // reflect the section where we are in the file:
    // VCALENDAR, TZ/DAYLIGHT, VEVENT, ALARM
    $state = 'NONE';
    $substate = 'none'; // reflect the sub section
    $subsubstate = ''; // reflect the sub-sub section
    $error = false;
    $line = 0;
    $event = '';

    while ( !feof( $fd ) && !$error ) {
      $line++;
      if ( $line > 5 && $line < 10 && $state == 'NONE' ) {
        // we are probably not reading a vcs file
        return false;
      }
      $buff = fgets( $fd, 4096 );
      $buff = chop( $buff );
      if ( $state == 'VEVENT' ) {
        if ( ! empty ( $subsubstate ) ) {
          if ( preg_match ( '/^END:(.+)$/i', $buff, $match ) ) {
            if ( $match[1] == $subsubstate ) {
              $subsubstate = '';
            }
          } else if ( $subsubstate == 'VALARM' &&
            preg_match ( "/TRIGGER:(.+)$/i", $buff, $match ) ) {
          }
        } else if ( preg_match ( '/^BEGIN:(.+)$/i', $buff, $match ) ) {
          $subsubstate = $match[1];
        } else if ( preg_match ( '/^SUMMARY.*:(.+)$/iU', $buff, $match ) ) {
          $substate = 'summary';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^DESCRIPTION:(.+)$/iU', $buff, $match ) ) {
          $substate = 'description';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^DESCRIPTION;ENCODING=QUOTED-PRINTABLE:(.+)$/i',
          $buff, $match ) ) {
          $substate = 'descriptionqp';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^CLASS.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'class';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^PRIORITY.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'priority';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^DTSTART.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'dtstart';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^DTEND.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'dtend';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^RRULE.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'rrule';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^EXDATE.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'exdate';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^DALARM.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'dalarm';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^CATEGORIES.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'categories';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^UID.*:(.+)$/i', $buff, $match ) ) {
          $substate = 'uid';
          $event[$substate] = $match[1];
        } elseif ( preg_match ( '/^END:VEVENT$/i', $buff, $match ) ) {
          $state = 'VCALENDAR';
          $substate = 'none';
          $subsubstate = '';
          if ( $tmp_data = format_vcal( $event ) ) $vcal_data[] = $tmp_data;
          // clear out data for new event
          $event = '';
          // TODO: QUOTED-PRINTABLE descriptions
          // folded lines
        } elseif ( preg_match ( '/^[ ]{1}(.+)$/', $buff, $match ) ) {
          if ( $substate != 'none' ) {
            $event[$substate] .= $match[1];
          } else {
            $errormsg .= "Error in file $cal_file line $line:<br />$buff\n";
            $error = true;
          }
          // For unsupported properties
        } else {
          $substate = 'none';
        }
      } elseif ( $state == 'VCALENDAR' ) {
        if ( preg_match ( '/^TZ.*:(.+)$/i', $buff, $match ) ) {
          $event['tz'] = $match[1];
        } elseif ( preg_match ( '/^DAYLIGHT.*:(.+)$/i', $buff, $match ) ) {
          $event['daylight'] = $match[1];
        } elseif ( preg_match ( '/^BEGIN:VEVENT$/i', $buff ) ) {
          $state = 'VEVENT';
        } elseif ( preg_match ( '/^END:VCALENDAR$/i', $buff ) ) {
          $state = 'NONE';
        }
      } elseif ( $state == 'NONE' ) {
        if ( preg_match ( '/^BEGIN:VCALENDAR$/i', $buff ) )
          $state = 'VCALENDAR';
        else if ( preg_match ( '/^BEGIN:ALARM$/i', $buff ) )
          $state = 'ALARM';
      }
      $event['state'] = $state;
    } //End while
    fclose ( $fd );
  }

  return $vcal_data;
}
// Convert vcal format (yyyymmddThhmmssZ) to epoch time
function vcaldate_to_timestamp( $vdate, $plus_d = '0', $plus_m = '0', $plus_y = '0' ) {
  $y = substr ( $vdate, 0, 4 ) + $plus_y;
  $m = substr ( $vdate, 4, 2 ) + $plus_m;
  $d = substr ( $vdate, 6, 2 ) + $plus_d;
  $H = substr ( $vdate, 9, 2 );
  $M = substr ( $vdate, 11, 2 );
  $S = substr ( $vdate, 13, 2 );
  $Z = substr ( $vdate, 15, 1 );
  if ( $Z == 'Z' ) {
    $TS = gmmktime ( $H, $M, $S, $m, $d, $y );
  } else {
    // Problem here if server in different timezone
    $TS = mktime ( $H, $M, $S, $m, $d, $y );
  }

  return $TS;
}
// Put all vcal data into import hash structure
function format_vcal( $event ) {
  // Start and end time
  
  // Set Calendar Type for easier processing later
  $fevent['CalendarType'] = $event['state'];
  
  $fevent['Untimed'] = $fevent['AllDay'] = 0;
  
  $fevent['StartTime'] = vcaldate_to_timestamp( $event['dtstart'] );
  if ( $fevent['StartTime'] == '-1' ) return false;
  $fevent['EndTime'] = vcaldate_to_timestamp( $event['dtend'] );
  
  if ( $fevent['StartTime'] == $fevent['EndTime'] ) {
    $fevent['Untimed'] = 1;
    $fevent['Duration'] = 0;
  } else {
  // Calculate duration in minutes
  $fevent['Duration'] = ( $fevent['EndTime'] - $fevent['StartTime'] ) / 60;
    if ( $fevent['Duration'] == '1440' && date ( 'His', $fevent['StartTime'] ) == 0 )
      $fevent['AllDay'] = 1;
  }
  if ( ! empty ( $event['summary'] ) ) $fevent['Summary'] = $event['summary'];
  if ( ! empty ( $event['description'] ) )
    $fevent['Description'] = $event['description'];
  if ( ! empty ( $event['descriptionqp'] ) ) {
    $fevent['Description'] = quoted_printable_decode ( $event['descriptionqp'] );
    // hack for mozilla sunbird's extra = signs
    $fevent['Description'] = preg_replace( '/^=/', '', $fevent['Description'] );
    $fevent['Description'] = str_replace( "\n=", "\n", $fevent['Description'] );
  }

  if ( ! empty ( $event['class'] ) ) {
    // Added  Confidential as new CLASS
    if ( preg_match ( '/private/i', $event['class'] ) ) {
      $fevent['Class'] = 'R';
    } elseif ( preg_match ( '/confidential/i', $event['class'] ) ) {
      $fevent['Class'] = 'C';
    } else {
      $fevent['Class'] = 'P';
    }
  }

  if ( ! empty ( $fevent['UID'] ) ) $fevent['UID'] = $event['uid'];
  // Repeats
  // vcal 1.0 repeats can be very complicated and the webcalendar doesn't
  // actually support all of the ways repeats can be specified. We will
  // focus on vcals dumped from Palm Desktop and Lotus Notes, which are simple
  // and the ones webcalendar should fully support.
  if ( ! empty ( $event['rrule'] ) ) {
    // split into pieces
    $RR = explode ( ' ', $event['rrule'] );

    if ( preg_match ( '/^D(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '1';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif ( preg_match ( '/^W(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '2';
      $fevent['Repeat']['Interval'] = $match[1];
      $fevent['Repeat']['ByDay'] = rrule_repeat_days ( $RR );
    } elseif ( preg_match ( '/^MP(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '3';
      $fevent['Repeat']['Interval'] = $match[1];
      if ( $RR[1] == '5+' ) {
        $fevent['Repeat']['Frequency'] = '3';
      }
    } elseif ( preg_match ( '/^MD(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '4';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif ( preg_match ( '/^YM(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '6';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif ( preg_match ( '/^YD(.+)$/i', $RR[0], $match ) ) {
      $fevent['Repeat']['Frequency'] = '6';
      $fevent['Repeat']['Interval'] = $match[1];
    }

    $end = end( $RR );
    // No end in Palm is 12-31-2031
    if ( ( $end != '20311231' ) && ( $end != '#0' ) )
      if ( preg_match ( '/^\#(.+)$/i', $end, $match ) )
        $fevent['Repeat']['Count'] = $match[1];
    //.
    // Repeating exceptions?
    if ( ! empty ( $event['exdate'] ) ) {
      $fevent['Repeat']['Exceptions'] = array ();
      $EX = explode ( ',', $event['exdate'] );
      foreach ( $EX as $exdate ) {
        $fevent['Repeat']['Exceptions'][] = vcaldate_to_timestamp( $exdate );
      }
    }
  } // end if rrule
  // TODO
  // $fevent[Category];
  return $fevent;
}

function get_categories_id_byname ( $cat_names ) {
  global $login, $IMPORT_CATEGORIES;
  $categories = explode ( ',', $cat_names );
  foreach ( $categories as $cat_name ) {
    $res = dbi_execute ( 'SELECT cat_id FROM webcal_categories
      WHERE cat_name  = ? AND ( cat_owner = ? OR cat_owner IS NULL )',
        array ( $cat_name, $login ) );
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $ret[] = $row[0];
        dbi_free_result ( $res );
      } else if ( ! empty ( $IMPORT_CATEGORIES ) && $IMPORT_CATEGORIES == 'Y' ) {
        // Need to insert new Category
        $res = dbi_execute ( 'SELECT MAX(cat_id) FROM webcal_categories' );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $id = $row[0] + 1;
          dbi_free_result ( $res );
          $sql = 'INSERT INTO webcal_categories '
           . '( cat_id, cat_owner, cat_name ) ' . 'VALUES ( ?,?,? )';
          if ( ! dbi_execute ( $sql, array ( $id, $login, $cat_name ) ) ) {
            $error = db_error ();
            // do_debug ( $error );
          } else {
            $ret[] = $id;
          }
        } //end if $res
      } else { // skip adding Categories
        $ret = '';
      } // end if row
    } else { // no res
      $error = db_error ();
      // do_debug ( $error );
    }
  } //end foreach
  return $ret;
}
// Generate the FREEBUSY line of text for a single event
function fb_export_time ( $date, $duration, $time, $texport ) {
  $ret = '';
  $time = sprintf ( "%06d", $time );
  $allday = ( $time == -1 || $duration == 1440 );
  $year = ( int ) substr ( $date, 0, -4 );
  $month = ( int ) substr ( $date, - 4, 2 );
  $day = ( int ) substr ( $date, -2, 2 );
  // No time, or an "All day" event"
  if ( $allday ) {
    // untimed event - consider this to not be busy
  } else {
    // normal/timed event (or all-day event)
    $hour = ( int ) substr ( $time, 0, -4 );
    $min = ( int ) substr ( $time, -4, 2 );
    $sec = ( int ) substr ( $time, -2, 2 );
    $duration = $duration * 60;

    $start_tmstamp = mktime ( $hour, $min, $sec, $month, $day, $year );

    $utc_start = export_get_utc_date ( $date, $time );

    $end_tmstamp = $start_tmstamp + $duration;
    $utc_end = export_get_utc_date ( date ( 'Ymd', $end_tmstamp ),
      date ( 'His', $end_tmstamp ) );
    $ret .= "FREEBUSY:$utc_start/$utc_end\r\n";
  }
  return $ret;
}
// Generate export select.
function generate_export_select ( $jsaction = '', $name = 'exformat' ) {
  $palmStr = translate ( 'Palm Pilot' );
  return '
      <select name="format" id="' . $name . '"'
   . ( empty ( $jsaction ) ? '' : 'onchange="' . $jsaction . '();"' ) . '>
        <option value="ical">iCalendar</option>
        <option value="vcal">vCalendar</option>
        <option value="pilot-csv">Pilot-datebook CSV (' . $palmStr . ')</option>
        <option value="pilot-text">Install-datebook (' . $palmStr . ')</option>
      </select>';
}

function save_vtimezone ( $event ) {
  //do_debug ( print_r ( $event, true ) ) ;
  $tzidLong = parse_tzid ( $event['tzid'] );
  $tzid = ( ! empty ( $event['tzlocation'] ) ? $event['tzlocation'] :
    ( ! empty ( $tzidLong ) ? $tzidLong : '' ) );
  $dtstart = ( ! empty ( $event['dtstart'] ) ? $event['dtstart'] : '' );
  $dtend = ( ! empty ( $event['dtend'] ) ? $event['dtend'] : '' );
  //delete any record already found for this tzid
  dbi_execute ( 'DELETE FROM webcal_timezones WHERE tzid = ? AND dtstart = ?',
    array ( $tzid, $dtstart ) );
  $sql = 'INSERT INTO webcal_timezones ( tzid, dtstart, dtend, vtimezone )
    VALUES ( ?, ?, ?, ?)';
  if ( ! dbi_execute ( $sql, array ( $tzid, $dtstart, $dtend, $event['VTIMEZONE'] ) ) ) {
    $error = db_error ();
    // do_debug ( $error );
  }

}

function get_vtimezone ( $tzid, $dtstart, $dtend='' ) {
  $ret = '';
  $sql = 'SELECT vtimezone FROM webcal_timezones
    WHERE tzid = ? AND  dtstart <= ? AND ( dtend >= ? OR dtend IS NULL )';

  $res = dbi_execute ( $sql, array ( $tzid, $dtstart, $dtend ) );

  if ( $res ) {
    while ( $row = dbi_fetch_row( $res ) ) {
      $ret = $row[0] . "\r\n";
    }
    dbi_free_result ( $res );
  }

  return $ret;
}

function parse_tzid ( $tzid ) {
  // if we get a complex TZID we try to parse it
  if ( strstr ( $tzid, 'ozilla.org' ) or strstr ( $tzid, 'softwarestudio.org' ) ) {
    $tzAr = explode ( '/', $tzid );
    $tzArCnt = count ( $tzAr );
    $tzid = $tzAr[3];
    // we may recieve a 2 word tzid
    if ( $tzArCnt == 5 ) $tzid .= '/' . $tzAr[4];
    // and even maybe a 3 word tzid
    if ( $tzArCnt == 6 ) $tzid .= '/' . $tzAr[4] . '/' . $tzAr[5];
  }
  return $tzid;
}
?>

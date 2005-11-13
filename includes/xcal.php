<?php
/**
 * All of WebCalendar's ical/vcal functions
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 *
 */

/*
 * Export a quoted Printable String
 * 
 */

function export_quoted_printable_encode($car) {
  $res = "";

  if ((ord($car) >= 33 && ord($car) <= 60) || (ord($car) >= 62 && ord($car) <= 126) || 
      ord($car) == 9 || ord($car) == 32) {
      $res = $car;
  } else {
      $res = sprintf("=%02X", ord($car));
  } //end if

  return $res;
} //end function export_quoted_printable_encode

function export_fold_lines($string, $encoding="none", $limit=76) {
  $len = strlen($string);
  $fold = $limit; 
  $res = array();
  $row = "";
  $enc = "";
  $lwsp = 0; // position of the last linear whitespace (where to fold)
  $res_ind = 0; // row index
  $start_encode = 0; // we start encoding only after the ":" character is encountered

  if (strcmp($encoding,"quotedprintable") == 0)
    $fold--; // must take into account the soft line break

  for ($i = 0; $i < $len; $i++) {
      $enc = $string[$i];

 if ($start_encode) {
   if (strcmp($encoding,"quotedprintable") == 0)
     $enc = export_quoted_printable_encode($string[$i]);
   else if (strcmp($encoding,"utf8") == 0)
     $enc = utf8_encode($string[$i]);
 }

 if ($string[$i] == ":")
   $start_encode = 1;

 if ((strlen($row) + strlen($enc)) > $fold) {
   $delta = 0;

   if ($lwsp == 0)
     $lwsp = $fold - 1; // the folding will occur in the middle of a word

   if ($row[$lwsp] == " " || $row[$lwsp] == "\t")
     $delta = -1;

   $res[$res_ind] = substr($row, 0, $lwsp+1+$delta);

   if (strcmp($encoding,"quotedprintable") == 0)
     $res[$res_ind] .= "="; // soft line break;

   $row = substr($row, $lwsp+1);

   $row = " " . $row;

   if ($delta == -1 && strcmp($encoding,"utf8") == 0)
     $row = " " . $row;

   if ($res_ind == 0)
     $fold--; //reduce row length of 1 to take into account the whitespace 
            //at the beginning of lines

   $res_ind++; // next line

   $lwsp = 0;
 } //end if ((strlen($row) + strlen($enc)) > $fold)

      $row .= $enc;

      if ($string[$i] == " " || $string[$i] == "\t" || $string[$i] == ";" || $string[$i] == ",")
 $lwsp = strlen($row) - 1;

      if ($string[$i] == ":" && (strcmp($encoding,"quotedprintable") == 0))
 $lwsp = strlen($row) - 1; // we cut at ':' only for quoted printable
    } //end for ($i = 0; $i < $len; $i++)

  $res[$res_ind] = $row; // Add last row (or first if no folding is necessary)

  return $res;
} //end function export_fold_lines($string, $encoding="none", $limit=76)

function export_get_attendee($id, $export) {
  global $login;

  $request = "SELECT webcal_entry_user.cal_login, webcal_entry_user.cal_status, " .
    " webcal_entry.cal_create_by " . 
    "FROM webcal_entry_user LEFT JOIN  webcal_entry " .
    " ON webcal_entry_user.cal_id = webcal_entry.cal_id " .
    " WHERE webcal_entry_user.cal_id = '$id' AND webcal_entry_user.cal_status <> 'D'";

  $att_res =  dbi_query($request);

  $count = 0;

  $attendee = array();
  $entry_array = array();

  while ($entry = dbi_fetch_row($att_res)) {
      $entry_array[$count++] = $entry;
  }

  dbi_free_result($att_res);

  $count = 0;

  while (list($key,$row) = each($entry_array)) {
      $request = "SELECT cal_firstname, cal_lastname, cal_email, cal_login " .
        " FROM webcal_user where cal_login = '". $row[0] . "'";

      $user_res = dbi_query($request);

      $user = dbi_fetch_row($user_res);

      dbi_free_result($user_res);

 if (count($user) > 0) {
   $attendee[$count] = "ATTENDEE;ROLE=";
   $attendee[$count] .= ($row[2] == $user[3]) ? "OWNER;" : "ATTENDEE;";
   $attendee[$count] .= "STATUS=";

   switch ($row[1]) {
     case 'A':
       $attendee[$count] .= "CONFIRMED";
       break;
     case 'R':
       $attendee[$count] .= "DECLINED";
       break;
     case 'W':
       $attendee[$count] .= "SENT";
       break;
     default:
       continue;
   } //end switch

   if (strcmp($export,"vcal") == 0)
     $attendee[$count] .= ";ENCODING=QUOTED-PRINTABLE";

   $attendee[$count] .= ":$user[0] $user[1] <$user[2]>";

   $count++;
 } //end if (count($user) > 0)
  } //end while

  return $attendee;
} //end function export_get_attendee($id, $export)

//All times are now stored in UTC time, so no conversions are needed
// other than formatting
function export_time($date, $duration, $time, $texport, $vtype='E') {
 

  $year = (int) substr($date,0,-4);
  $month = (int) substr($date,-4,2);
  $day = (int) substr($date,-2,2);
 if ( $time != -1 ) {
   $time = sprintf ( "%06d", $time ); 
    $hour = (int) substr($time,0,-4);
    $min = (int) substr($time,-4,2);
    $sec = (int) substr($time,-2,2);
 }
 if ( $time == -1  || ( $time == 0 && $duration == 1440 ) ) {
    // untimed event or all day
    echo "DTSTART;VALUE=DATE:$date\r\n";
  } else {
    // timed event 
    $utc_start = export_get_utc_date($date, $time);
    echo "DTSTART:$utc_start\r\n";
  }
  if (strcmp($texport,"ical") == 0) {
    $utc_dtstamp = export_get_utc_date(date("Ymd", mktime()),
      date("His", mktime()));
    echo "DTSTAMP:$utc_dtstamp\r\n";
  //We don' want DTEND for VTODOs
  if ( $vtype == "T" || $vtype == "N" ) return;
   if ($time == -1  || ( $time == 0 && $duration == 1440 ) ) {
      // untimed event
     $end_date = date("Ymd", mktime(12, 0, 0, $month, $day +1, $year));
     echo "DTEND;VALUE=DATE:$end_date\r\n";
   } else {
     $end_tmstamp = mktime($hour, $min + $duration, 0, $month, $day, $year);
    //echo date("YmdHis", $end_tmstamp);
     $utc_end = export_get_utc_date(date("Ymd", $end_tmstamp), date("His", $end_tmstamp));
    //echo $hour." " .$min ." " .$duration." " .$month." " . $day." " . $year;
     echo "DTEND:$utc_end\r\n";
   }
  } elseif (strcmp($texport,"vcal") == 0) {
   if ($time == -1  || ( $time == 0 && $duration == 1440 ) ) {
     $end_tmstamp = mktime($hour, $min, 0, $month, $day +1, $year);
     $utc_end = date("Ymd", $end_tmstamp);
     echo "DTEND:$utc_end\r\n";
   } else {
     $end_tmstamp = mktime($hour, $min + $duration, 0, $month, $day, $year);;
     $utc_end = export_get_utc_date(date("Ymd", $end_tmstamp), date("His", $end_tmstamp));
     echo "DTEND:$utc_end\r\n";
   }
  } else {
    echo "DURATION:P$str_duration\r\n";
  }
}
//$simple allows for easy reading 
function export_recurrence_ical( $id, $simple=false ) {
  global $timestamp_RRULE, $TIMEZONE;
  
 $recurrance = '';
  $sql = "SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not WHERE cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
    $exdate = array();
   $rdate = array();
   while ($row = dbi_fetch_row($res)) {
     if ( $row[1] == 1 ) {
    $exdate[] = $row[0];
  } else {
    $rdate[] = $row[0];  
  }
   } 
    dbi_free_result($res);
  }
  $sql = "SELECT webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
    . " webcal_entry_repeats.cal_endtime, webcal_entry_repeats.cal_frequency, "
  . " webcal_entry.cal_date, webcal_entry.cal_time, webcal_entry_repeats.cal_bymonth, "
  . " webcal_entry_repeats.cal_bymonthday,  webcal_entry_repeats.cal_byday, "
  . " webcal_entry_repeats.cal_bysetpos, webcal_entry_repeats.cal_byweekno, "
  . " webcal_entry_repeats.cal_byyearday, webcal_entry_repeats.cal_wkst, "
  . " webcal_entry_repeats.cal_count, webcal_entry.cal_duration"
    . " FROM webcal_entry, webcal_entry_repeats WHERE webcal_entry_repeats.cal_id = '$id'"
    . " AND webcal_entry.cal_id = '$id' ORDER BY webcal_entry.cal_date";

  $res = dbi_query($sql);

  if ($res) {
    if ( $row = dbi_fetch_row($res) ) {
      $type       = $row[0];
      $end        = $row[1];
      $endtime    = $row[2];  
      $interval   = $row[3];
      $day        = $row[4];
      $time       = $row[5];
      $bymonth    = $row[6];
      $bymonthday = $row[7];
      $byday      = $row[8];
      $bysetpos   = $row[9];
      $byweekno   = $row[10];
      $byyearday  = $row[11];
      $wkst       = $row[12];
      $cal_count  = $row[13];
      $duration   = $row[14];
      
      // set $timestamp_RRULE for use in VTIMEZONE
   //skip if UNTIMED or ALL DAY Event
   //this violates RFC2445, but oh well :)
   //Also this can be disabled to vastly improve performance
   if ( ! empty ( $ICS_TIMEZONES ) && $ICS_TIMEZONES == "Y" ) {    
    if ( ( empty ( $timestamp_RRULE ) && $simple == false ) &&
     ( $time != -1 || ($time == 0 && $duration == 1440 ) ) ){
     $day_epoch = date_to_epoch ( $day . $time, 0 );
     if ( ! empty ( $end ) ) {
       $until = date_to_epoch ( $end . $endtime, 0 );
     } else { 
       $until = mktime ( 0,0,0,12,31,2037 );
     }
     $cal_count_local = ( empty ( $cal_count )? 999: $cal_count );
 
     //we'll get_all_dates to see if this RRULE spans DST
     $dates = get_all_dates( $day_epoch, $type, $interval, $bymonth, 
      $byweekno, $byyearday,$bymonthday, $byday,$bysetpos, $cal_count_local,
      $until, $wkst);
    
     if ( count ( $dates ) > 1  ) {
      $is_dst = $tz_info = array();
      for ( $j =0; $j < count ($dates); $j++ ) {
       $tz_info = get_tz_offset ( $TIMEZONE, $dates[$j]  );
       $is_dst[$j] =  $tz_info[0];     
      }  
      if ( count ( array_count_values($is_dst) > 1) )
       $timestamp_RRULE = $day_epoch;
     }
    }
   }  


      $rrule = '';
   
      if (! $simple ) $rrule = "RRULE:";

      /* recurrence frequency */
      switch ($type) {
       case 'daily' :
         $rrule .= "FREQ=DAILY";
         break;
       case 'weekly' :
         $rrule .= "FREQ=WEEKLY";
         break;
        case 'monthlyBySetPos':
       case 'monthlyByDay':
       case 'monthlyByDate' :
         $rrule .= "FREQ=MONTHLY";
         break;
       case 'yearly' :
         $rrule .= "FREQ=YEARLY";
         break;
       }

    if ( ! empty ( $interval ) && $interval > 1 )
        $rrule .= ";INTERVAL=$interval";

      if ( ! empty ( $bymonth ) ) 
         $rrule .= ";BYMONTH=". $bymonth;
 
      if ( ! empty ( $bymonthday ) ) 
         $rrule .= ";BYMONTHDAY=". $bymonthday;

      if ( ! empty ( $byday ) ) 
         $rrule .= ";BYDAY=". $byday;

      if ( ! empty ( $byweekno ) ) 
         $rrule .= ";BYWEEKNO=". $byweekno;

      if ( ! empty ( $bysetpos ) ) 
         $rrule .= ";BYSETPOS=". $bysetpos;
   
   if ( ! empty ( $wkst ) && $wkst != 'MO' ) 
         $rrule .= ";WKST=". $wkst;

    if (!empty($end)) {
    $endtime = ( ! empty ( $endtime)? $endtime:0);
     $rrule .= ";UNTIL=";
     $utc = export_get_utc_date($end, $endtime );
     $rrule .= $utc;
    } else if (! empty ($cal_count ) && $cal_count != 999 ) {
    $rrule .= ";COUNT=" . $cal_count;
  }
  
    //wrap line if necessary
   $rrule = export_fold_lines($rrule);
   while (list($key,$value) = each($rrule)) 
     $recurrance .= "$value\r\n";

   //If type = manual, undo what we just did and onlt process RDATE && EXDATE
   if ( $type == "manual" ) $recurrance = '';
   
   if (count($rdate) > 0) {
     if ( ! $simple ) $string = "RDATE;VALUE=DATE:" . implode (",", $rdate);
     if ( $simple ) $string = translate ("INCLUSION DATES") . ":" . implode (",", $rdate);
     $string = export_fold_lines($string);
     while (list($key,$value) = each($string)) 
       $recurrance .= "$value\r\n";
   }
     if ( $simple ) $recurrance .= "<br />";
   
     if (count($exdate) > 0) {
       if ( ! $simple ) $string = "EXDATE;VALUE=DATE:". implode (",", $exdate);
       if ( $simple ) $string = translate ("EXCLUSION DATES") . ":". implode (",", $exdate);
       $string = export_fold_lines($string);
       while (list($key,$value) = each($string)) 
         $recurrance .= "$value\r\n";
     }
   }
  }
 return $recurrance;
}

function export_recurrence_vcal($id, $date) {
  $sql = "SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not WHERE cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
    $exdate = array();
   $rdate = array();
   while ($row = dbi_fetch_row($res)) {
     if ( $row[1] == 1 ) {
      $exdate[] = $row[0];
    } else {
      $rdate[] = $row[0];  
    }
   } 
    dbi_free_result($res);
  }

  $sql = "SELECT webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
    . " webcal_entry_repeats.cal_endtime, webcal_entry_repeats.cal_frequency, "
  . " webcal_entry.cal_date, webcal_entry.cal_time, webcal_entry_repeats.cal_bymonth, "
  . " webcal_entry_repeats.cal_bymonthday,  webcal_entry_repeats.cal_byday, "
  . " webcal_entry_repeats.cal_bysetpos, webcal_entry_repeats.cal_byweekno, "
  . " webcal_entry_repeats.cal_byyearday, webcal_entry_repeats.cal_wkst "
    . " FROM webcal_entry, webcal_entry_repeats WHERE webcal_entry_repeats.cal_id = '$id'"
    . "AND webcal_entry.cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
    if ( $row = dbi_fetch_row($res) ) {
      $type = $row[0];
      $end = $row[1];
     $endtime = $row[2];  
      $interval = $row[3];
      $day = $row[4];
      $time = $row[5];
   $bymonth = $row[6];
   $bymonthday = $row[7];
   $byday = $row[8];
   $bysetpos = $row[9];
   $byweekno = $row[10];
   $byyearday = $row[11];
   $wkst = $row[12];

      echo "RRULE:";

      /* recurrence frequency */
      switch ($type) {
       case 'daily' :
         echo "D";
         break;
       case 'weekly' :
         echo "W";
         break;
       case 'monthlyByDay':
         echo "MP";
         break;
       case 'monthlyBySetPos':
         echo "MP";
         break;
       case 'monthlyByDate' :
         echo "MD";
         break;
       case 'yearly' :
         echo "YM";
         break;
      }

      echo $interval." ";

 if ($type == "weekly") {
   if ($day != "nnnnnnn") {
     for ($i=0; $i < strlen($day); $i++) {
       if ($day[$i] == 'y') {
        $byday .= $str_day[$i] ." ";
        }
     }
     echo $byday;
    }
 } elseif ($type == "monthlyByDate") {
   $day = (int) substr($date,-2,2);
   echo "$day ";
 } elseif ($type == "monthlyByDay" ) {
   $year = (int) substr($date,0,-4);
   $month = (int) substr($date,-4,2);
   $day = (int) substr($date,-2,2);

   $stamp = mktime(0, 0, 0, $month, $day, $year);
   $date_array = getdate($stamp);
          $day_no = $str_day[$date_array['wday']];

   $next_stamp = $stamp + 7 * 24 * 60 * 60;
   $next_date_array = getdate($next_stamp);

   if ($date_array['mon'] != $next_date_array['mon']) {
     $pos = 5;
   } else {
     $pos = (int) ($day / 7);
      if (($day % 7) > 0) {
        $pos++;
      }
   }
   echo $pos."+ $day_no ";
 } elseif ($type == "yearly") {
    $month = (int) substr($date,-4,2);
    echo "$month ";
 }

 // End Date - For all types
 if (!empty($end)) {
   echo export_get_utc_date($end, $time);
 }
  echo "\r\n";

    // Repeating Exceptions
    $num = count($exdate);
    if ($num > 0) {
      $string = "EXDATE:";
      for ($i=0;$i<$num;$i++) {
        $string .= $exdate[$i]."T000000,";
      }
      echo rtrim($string,",")."\r\n";
    }
  }
  }
}


/*
 * Create a date-time format (e.g. "20041130T123000Z") that is
 * Times are now stored in GMT so no conversion is needed
 */
function export_get_utc_date($date, $time=0) {
  $time = sprintf ( "%06d", $time);
  $utc = sprintf ("%sT%sZ", $date, $time);

  return $utc;
}

function export_alarm_vcal($id,$date,$time=0) {
  $sql = "SELECT cal_data FROM webcal_site_extras " .
         "WHERE cal_id = $id AND cal_type = 7 AND cal_remind = 1";
  $res = dbi_query ( $sql );
  $row = dbi_fetch_row ( $res );
  dbi_free_result ( $res );

  if ($row) {
    echo "DALARM:";
    $offset = $row[0] * 60; // How many seconds
    $year = (int) substr($date,0,-4);
    $month = (int) substr($date,-4,2);
    $day = (int) substr($date,-2,2);
    $hour = ($time > 0) ? (int) substr($time,0,-4) : 0;
    $min  = ($time > 0) ? (int) substr($time,-4,2) : 0;
    $sec  = ($time > 0) ? (int) substr($time,-2,2) : 0;
    $stamp = mktime($hour, $min, $sec, $month, $day, $year);
    $atime = $stamp - $offset;
    echo gmdate("Ymd\THis\Z", $atime)."\r\n";
  }
}


// Convert the webcalendar reminder to an ical VALARM
// TODO: need to loop through the site_extras[] array to determine
// what type of reminder (with date or with offset) since this info
// is not stored in the database.  Then, use that to determine when
// the reminder should be sent.  Check the webcal_reminder_log to
// make sure the reminder has not already been sent.
function export_alarm_ical ( $id, $date, $description ) {
  global $cal_type;
  // Don't send reminder for event in the past
  if ( $date < date("Ymd") )
    return;

  $sql = "SELECT cal_data FROM webcal_site_extras " .
    "WHERE cal_id = $id AND cal_type = " . EXTRA_REMINDER .  " AND " .
    "cal_remind = 1";
  $res = dbi_query ( $sql );
  $row = dbi_fetch_row ( $res );
  dbi_free_result ( $res );

  if ( $row ) {
    //Sunbird requires this line
    echo "X-MOZILLA-ALARM-DEFAULT-LENGTH:" . $row[0] . "\r\n";
    echo "BEGIN:VALARM\r\n";
  echo "TRIGGER";
  //Tasks will use Alarms related to due date
    if ( $cal_type == "T" || $cal_type == "N" ) {
    echo ";RELATED=END";  
  }
  echo ":-PT".$row[0]."M\r\n";
    echo "ACTION:DISPLAY\r\n";

    $array = export_fold_lines ( $description, "utf8" );
    while  ( list ( $key, $value ) = each ( $array ) ) {
      echo "$value\r\n";
    }

    echo "END:VALARM\r\n";
  }
}

function export_get_event_entry($id='all') {
  global $use_all_dates, $include_layers, $fromyear,$frommonth,$fromday,
    $endyear,$endmonth,$endday,$modyear,$modmonth,$modday,$login,$user;
  global $DISPLAY_UNAPPROVED, $layers;
  // We export repeating events only with the pilot-datebook CSV format
  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name " .
    ", webcal_entry.cal_priority, webcal_entry.cal_date " .
    ", webcal_entry.cal_time " .
    ", webcal_entry_user.cal_status, webcal_entry.cal_create_by " .
    ", webcal_entry.cal_access, webcal_entry.cal_duration " .
    ", webcal_entry.cal_description " .
    ", webcal_entry_user.cal_category " .
    ", webcal_entry_user.cal_percent, webcal_entry.cal_completed " .
    ", webcal_entry.cal_due_date, webcal_entry.cal_due_time " .
    ", webcal_entry.cal_location, webcal_entry.cal_url " .
    ", webcal_entry.cal_type " .
    "FROM webcal_entry, webcal_entry_user ";

  if ($id == "all") {
      $sql .= "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
 " ( webcal_entry_user.cal_login = '" . $login . "'";
      if ( $user && $user != $login ) {
        $sql .= " OR webcal_entry_user.cal_login = '$user'";
      } else if ( $include_layers && $layers ) {
        foreach ( $layers as $layer ) {
          $sql .= " OR webcal_entry_user.cal_login = '" .
            $layer['cal_layeruser'] . "'";
        }
      }
      $sql .= " ) ";

 if (!$use_all_dates) {
   $startdate = sprintf ( "%04d%02d%02d", $fromyear, $frommonth, $fromday );
   $enddate = sprintf ( "%04d%02d%02d", $endyear, $endmonth, $endday );
   $sql .= " AND webcal_entry.cal_date >= $startdate " .
     "AND webcal_entry.cal_date <= $enddate";
   $moddate = sprintf ( "%04d%02d%02d", $modyear, $modmonth, $modday );
   $sql .= " AND webcal_entry.cal_mod_date >= $moddate";
 }
  } else {
      $sql .= "WHERE webcal_entry.cal_id = '$id' AND " .
 "webcal_entry_user.cal_id = '$id' AND " .
 "( webcal_entry_user.cal_login = '" . $login . "'";
        // TODO: add support for user in URL so we can export from other
        // calendars, particularly non-user calendars.
 //"webcal_entry_user.cal_id = '$id'";
      if ( ! empty ( $user )  && $user != $login ) {
        $sql .= " OR webcal_entry_user.cal_login = '$user'";
      } else if ( $layers ) {
        foreach ( $layers as $layer ) {
          $sql .= " OR webcal_entry_user.cal_login = '" .
             $layer['cal_layeruser'] . "'";
        }
      }
      $sql .= " ) ";
  } //end if $id=all

  if ( $DISPLAY_UNAPPROVED == "N"  || $login == "__public__" )
    $sql .= " AND webcal_entry_user.cal_status = 'A'";
  else
    $sql .= " AND webcal_entry_user.cal_status IN ('W','A')";

  $sql .= " ORDER BY webcal_entry.cal_date";

  //echo "SQL: $sql <p>";
  $res = dbi_query ( $sql );

  return $res;
} //end function export_get_event_entry($id)


function generate_uid($id='') {
  global  $SERVER_URL, $login;

  $uid = $SERVER_URL;
  if ( empty ( $uid ) )
    $uid = "UNCONFIGURED-WEBCALENDAR";
  $uid = str_replace ( "http://", "", $uid );
  $uid .= sprintf ( "-%s-%010d", $login, $id );
  $uid = preg_replace ( "/[\s\/\.-]+/", "-", $uid );
  $uid = strtoupper ( $uid );
  return $uid;
}

// Add entries in the webcal_import and webcal_import_data tables.
// This allows us to associate webcalendar events with the ical UID.
// If the user updates the event and the updated event gets sent back, we can
// then figure out which webcalendar event goes with the UID so we can
// update the correct event.
function save_uid_for_event ( $importId, $id , $uid ) {
  global $login, $error;

  // Note: We can get a duplicate key error here if this event was
  // created by an import from another calendar.  Say someone invites you
  // to an event and sends along an ics attachement via email.  You use
  // that to import the event.  Now, who is the definitive source of the
  // event?  If the original author sends an update or if the ical client
  // tries to update it?  I'm not really sure, but we will assume that
  // events imported into webcalendar become property of webcalendar.
  $sql = "INSERT INTO webcal_import_data ( cal_import_id, cal_id, " .
    "cal_login, cal_import_type, cal_external_id ) VALUES ( " .
    "$importId, $id, '$login', 'publish', '$uid' )";
   
  //do_debug ( "SQL: $sql" );
  if ( ! dbi_query ( $sql ) ) {
    $error = translate("Database error") . ": " . dbi_error ();
    //do_debug ( $error );
  }
  //do_debug ( "leaving func" );
}

// Add an entry in webcal_import.  For each import or publish request,
// we create a single webcal_import row that goes with the many
// webcal_import_data rows (one for each event).
function create_import_instance () {
  global $login, $prodid;

  $name = $prodid;
  $importId = 1;

  $sql = "SELECT MAX(cal_import_id) FROM webcal_import";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $importId = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  $sql = "INSERT INTO webcal_import ( cal_import_id, cal_name, " .
    "cal_date, cal_type, cal_login ) VALUES ( $importId, '$name', " .
    date("Ymd") . ", 'publish', '$login' )";
  if ( ! dbi_query ( $sql ) ) {
    $error = translate("Database error") . ": " . dbi_error ();
    return;
  }
  return ( $importId );
}

function export_vcal ($id) {
  global $prodid;
  header ( "Content-Type: text/x-vcalendar" );
  //header ( "Content-Type: text/plain" );

  $res = export_get_event_entry($id);

  $entry_array = array();
  $count = 0;

  while ( $entry = dbi_fetch_row($res) ) {
      $entry_array[$count++] = $entry;
  }

  dbi_free_result($res);

  if (count($entry_array) > 0) {
    echo "BEGIN:VCALENDAR\r\n";
    echo "$prodid\r\n";
    echo "VERSION:1.0\r\n";

    /* Time Zone
 $tzdate = mktime();
 $gmdate = gmmktime();
 $tzdiff = ($gmdate - $tzdate) / 60 / 60; //FIXME only hours are represented

 $tz = sprintf("%02d", $tzdiff);

 echo "TZ:";
 echo ($tzdiff >= 0) ? "+" : "-";
 echo "$tz\r\n";

    */
  }

  while (list($key,$row) = each($entry_array)) {
      $uid = $row[0];
      $export_uid = generate_uid();
      $name = $row[1];
      $priority = $row[2];
      $date = $row[3];
      $time = $row[4];
      $status = $row[5];
      $create_by = $row[6];
      $access = $row[7];
      $duration = $row[8];
      $description = $row[9];

      /* Start of event */
      echo "BEGIN:VEVENT\r\n";

      /* UID of the event (folded to 76 char) */
      $export_uid = "UID:$export_uid";
      $array = export_fold_lines($export_uid);
      while (list($key,$value) = each($array))
 echo "$value\r\n";

      /* SUMMARY of the event (folded to 76 char) */
      $name = preg_replace("/\\\\/", "\\\\\\", $name); // ??
      $name = "SUMMARY;ENCODING=QUOTED-PRINTABLE:" . $name;
      $array = export_fold_lines($name,"quotedprintable");

      while (list($key,$value) = each($array))
 echo "$value\r\n";

      /* DESCRIPTION if any (folded to 76 char) */
      if ($description != "") {
   $description = preg_replace("/\\\\/", "\\\\\\", $description); // ??
   $description = "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . $description;
   $array = export_fold_lines($description,"quotedprintable");
   while (list($key,$value) = each($array))
     echo "$value\r\n";
      } //end if ($description != "")

      /* CLASS either "PRIVATE", "CONFIDENTIAL, or "PUBLIC" (the default) */
      if ($access == "R") {
   echo "CLASS:PRIVATE\r\n";
      } else  if ($access == "C"){
   echo "CLASS:CONFIDENTIAL\r\n";
      } else {
   echo "CLASS:PUBLIC\r\n";
      }


      // ATTENDEE of the event
      $attendee = export_get_attendee($row[0], "vcal");

      for ($i = 0; $i < count($attendee); $i++) {
   $attendee[$i] = export_fold_lines($attendee[$i],"quotedprintable");
   while (list($key,$value) = each($attendee[$i]))
     echo "$value\r\n";
      }

      /* Time - all times are utc */
      export_time($date, $duration, $time, "vcal");

      export_recurrence_vcal($uid, $date);

      // FIXME: handle alarms
      export_alarm_vcal($uid,$date,$time);

      /* Goodbye event */
      echo "END:VEVENT\n";
    } //end while (list($key,$row) = each($entry_array))

  if (count($entry_array) > 0)
    echo "END:VCALENDAR\r\n";
} //end function

function export_ical ( $id='all' ) {
  global $publish_fullname, $login, $PROGRAM_VERSION,
   $PROGRAM_NAME, $cal_type,  $timestamp_RRULE;
  $exportId = -1;
 
 $res = export_get_event_entry($id);
  $entry_array = array();
  $count = 0;
  while ( $entry = dbi_fetch_row($res) ) {
    $entry_array[$count++] = $entry;
  }
  dbi_free_result ( $res );

  //  Always output something, even if no records come back
  //  This prevents errors on the iCal client
  echo "BEGIN:VCALENDAR\r\n";
  $title = "X-WR-CALNAME;VALUE=TEXT:" .
    ( empty ( $publish_fullname ) ? $login : translate($publish_fullname) );
  $title = str_replace ( ",", "\\,", $title );
  echo "$title\r\n";
  if ( ! empty ( $PROGRAM_VERSION ) ) {
    echo "PRODID:-//WebCalendar-$PROGRAM_VERSION\r\n";
  } else if ( preg_match ( "/WebCalendar v(\S+)/", $PROGRAM_NAME, $match ) ) {
    echo "PRODID:-//WebCalendar-$match[1]\r\n";
  } else {
    echo "PRODID:-//WebCalendar-UnknownVersion\r\n";
  }
  echo "VERSION:2.0\r\n";
  echo "METHOD:PUBLISH\r\n";

  while (list($key,$row) = each($entry_array)) {
    $id = $row[0];
    $event_uid = generate_uid($id);
    $name = $row[1];
    $priority = $row[2];
    $date = $row[3];
    $time = $row[4];
    $status = $row[5];
    $create_by = $row[6];
    $access = $row[7];
    $duration = $row[8];
    $description = $row[9];
    //New columns to support tasks
    $percent = ( ! empty ( $row[11] )? $row[11] : 0 );
    $completed = ( ! empty ( $row[12] )? substr( $row[12], 0 ,8 ) . 
      "T" . sprintf ( "%06d", substr( $row[12], 9 ,6 ) ) : '');
    $due_date = $row[13];
    $due_time = $row[14];
    $location = $row[15];
    $url = $row[16];
    $cal_type = $row[17];

 
    // Figure out Categories
    $categories = array();
    $sql = "SELECT webcal_categories.cat_name " .
      " FROM webcal_categories, webcal_entry_categories " .
      " WHERE webcal_entry_categories.cal_id = $id AND " . 
      " webcal_entry_categories.cat_id = webcal_categories.cat_id AND ".
      " (webcal_entry_categories.cat_owner = '" . $login . "' OR  " . 
      " webcal_entry_categories.cat_owner IS NULL) " .
      " ORDER BY webcal_entry_categories.cat_order";
    $res = dbi_query ( $sql );
    while ( $row = dbi_fetch_row ( $res ) ) {
      $categories[] = $row[0];
    }
    dbi_free_result ( $res );

    // Add entry in webcal_import_data if it does not exist.
    // Even thought we're exporting, this data needs to be valid 
    // for proper parsing of response from iCal client.
    // If this is the first event that has never been published,
    // then create a new import instance to associate with what we are doing.
    $sql = "SELECT webcal_import_data.cal_external_id " .
      "FROM webcal_import_data, webcal_entry_user WHERE " .
      "webcal_import_data.cal_id = webcal_entry_user.cal_id AND " .
      "webcal_import_data.cal_id = $id AND " .
      "webcal_entry_user.cal_login = '$login'";
    $res = dbi_query ( $sql );
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
          // they update some of our events.  But, I cannot see a way to
          // do that.
          $exportId = create_import_instance ();
        }
        save_uid_for_event ( $exportId, $id , $event_uid);
      }
      dbi_free_result ( $res );
    }
  
  //get recurrance info
  $recurrance = export_recurrence_ical($id);
    
  /* snippet from RFC2445
  The "VTIMEZONE" calendar component MUST be present if the iCalendar
   object contains an RRULE that generates dates on both sides of a time
   zone shift (e.g. both in Standard Time and Daylight Saving Time)
   unless the iCalendar object intends to convey a floating time (See
   the section "4.1.10.11 Time" for proper interpretation of floating
   time). It can be present if the iCalendar object does not contain
   such a RRULE. In addition, if a RRULE is present, there MUST be valid
   time zone information for all recurrence instances. */
  
  if ( ! empty ( $timestamp_RRULE ) ){ //$timestamp_RRULE set in export_recurrence_ical
   export_vtimezone( $timestamp_RRULE );
  }
    
  if ( $cal_type == "E" || $cal_type == "M" ) {
   $exporting_event = true;
    /* Start of event */
    echo "BEGIN:VEVENT\r\n";
  } else if ( $cal_type == "T" || $cal_type == "N" ) {
   $exporting_event = false;
    /* Start of VTODO */
    echo "BEGIN:VTODO\r\n";  
  }

    /* UID of the event (folded to 76 char) */
    $array = export_fold_lines("UID:$event_uid");
    while (list($key,$value) = each($array))
       echo "$value\r\n";

    $name = preg_replace("/\r/", "", $name);
    $name = preg_replace("/\n/", "\\n", $name);
    $name = preg_replace("/\\\\/", "\\\\\\", $name); // ??
    $description = preg_replace("/\r/", "", $description);
    $description = preg_replace("/\n/", "\\n", $description);
    $description = preg_replace("/\\\\/", "\\\\\\", $description); // ??

    /* SUMMARY of the event (folded to 76 char) */
    $name = "SUMMARY:" . $name;
    $array = export_fold_lines($name,"utf8");

    while (list($key,$value) = each($array))
       echo "$value\r\n";

    /* DESCRIPTION if any (folded to 76 char) */
    if ($description != "") {
      $description = "DESCRIPTION:" . $description;
      $array = export_fold_lines($description,"utf8");
      while (list($key,$value) = each($array))
        echo "$value\r\n";
    }

    /* LOCATION if any (folded to 76 char) */
    if ($location != "") {
      $location = "LOCATION:" . $location;
      $array = export_fold_lines($location,"utf8");
      while (list($key,$value) = each($array))
        echo "$value\r\n";
    } 

    /* CATEGORIES if any (folded to 76 char) */
    if (isset( $categories )) {
      $categories = "CATEGORIES:" . implode ( ",", $categories);
      $array = export_fold_lines($categories,"utf8");
      while (list($key,$value) = each($array))
        echo "$value\r\n";
    }

    /* CLASS either "PRIVATE", "CONFIDENTIAL",  or "PUBLIC" (the default) */
    if ($access == "R") {
      echo "CLASS:PRIVATE\r\n";
      } else  if ($access == "C"){
        echo "CLASS:CONFIDENTIAL\r\n";
      } else {
        echo "CLASS:PUBLIC\r\n";
      }
 
    /* STATUS */
  if ( $cal_type == "E" || $cal_type == "M" ) {  
    if ($status == "A") {
      echo "STATUS:CONFIRMED\r\n";
    } else if ($status == "W") {
      echo "STATUS:TENTATIVE\r\n";
    }
  } else if ( $cal_type == "T" || $cal_type == "N" ) {
    if ($status == "A" && empty ( $completed ) ) {
      echo "STATUS:ACCEPTED\r\n";
    } else if ($status == "A") {
      echo "STATUS:COMPLETED\r\n";
    } else if ($status == "W") {
      echo "STATUS:NEEDS-ACTION\r\n";
    } 
 } 

    /* Time - all times are utc */
    export_time($date, $duration, $time, "ical", $cal_type );
    
  //VTODO specific items
    if ( $cal_type == "T" || $cal_type == "N" ) {
      echo "DUE:" . $due_date. "T". sprintf ( "%06d", $due_time ) . "Z\r\n";
    if ( ! empty ( $completed ) ) echo "COMPLETED:" . $completed . "\r\n";
      echo "PERCENT-COMPLETE:" . $percent . "\r\n";
  }
  
    /* Recurrence */
    echo $recurrance;

    /* handle alarms */
    export_alarm_ical($id,$date,$description);

  if ( $cal_type == "E" || $cal_type == "M" ) {
      /* End of event */
      echo "END:VEVENT\r\n";
  } else if ( $cal_type == "T" || $cal_type == "N" ) {
      /* Start of VTODO */
      echo "END:VTODO\r\n";  
  }
  }

  
  echo "END:VCALENDAR\r\n";
}

//IMPORT FUNCTIONS BELOW HERE

/* Import the data structure
$Entry[CalendarType]       =  VEVENT, VTODO, VTIMEZONE
$Entry[RecordID]           =  Record ID (in the Palm) ** only required for palm desktop
$Entry[StartTime]          =  In seconds since 1970 (Unix Epoch)
$Entry[EndTime]            =  In seconds since 1970 (Unix Epoch)
$Entry[Summary]            =  Summary of event (string)
$Entry[Duration]           =  How long the event lasts (in minutes)
$Entry[Description]        =  Full Description (string)
$Entry[Untimed]            =  1 = true  0 = false
$Entry[Class]              =  R = PRIVATE,C = CONFIDENTIAL  P = PUBLIC
$Entry[Location]           =  Location of event
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
$Entry[Repeat][Interval]   =  How often event occurs. (1=every, 2=every other,etc.)
$Entry[Repeat][Until]      =  When the repeat ends (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][Exceptions] =  Exceptions to the repeat (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][Inclusions] =  Inclusions to the repeat (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][ByDay]      =  What days to repeat on (7 characters...f or n for each day)
$Entry[Repeat][ByMonthDay] =  Days of month events fall on (nnnxyz.....)x=neg,y=pos,z=both
$Entry[Repeat][ByMonth]    =  Months that event will occur (12 chars y or n)
$Entry[Repeat][BySetPos]   =  Position in other ByXxxx that events occur(1,3,-1,-2, etc)
$Entry[Repeat][ByYearDay]  =  Days in year that event occurs
$Entry[Repeat][ByWeekNo]   =  Week that a yearly event repeats
$Entry[Repeat][WkSt]       =  Day that week starts on (default MO)
$Entry[Repeat][Count]      =  Number of occurances, may be used instead of UNTIL
*/

function import_data ( $data, $overwrite, $type ) {
  global $login, $count_con, $count_suc, $error_num, $ImportType;
  global $single_user, $single_user_login, $ALLOW_CONFLICTS, $ALLOW_CONFLICT_OVERRIDE;
  global $numDeleted, $errormsg;
  global $calUser, $H2COLOR, $sqlLog;

  $oldUIDs = array ();
  $oldIds = array ();
  $firstEventId = 0;
  //  $importId = -1;  
  $importId = 1;
 $subType = '';
 if ( $type == 'icalclient' ) {
   $type = 'ical';
  $subType = 'icalclient';
 }
  // Generate a unique import id
  $res = dbi_query ( "SELECT MAX(cal_import_id) FROM webcal_import" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $importId = $row[0] + 1;
    }
    dbi_free_result ( $res );
  }
  $sql = "INSERT INTO webcal_import ( cal_import_id, cal_name, " .
    "cal_date, cal_type, cal_login ) VALUES ( $importId, NULL, " .
    date("Ymd") . ", '$type', '$login' )";
  if ( ! dbi_query ( $sql ) ) {
    $errormsg = translate("Database error") . ": " . dbi_error ();
    return;
  }

foreach ( $data as $Entry ){

    //do_debug ( "Entry Array " . print_r ( $Entry , true ) );
    $priority = 2;
    $participants[0] = $calUser;
    //$participants[0] = $login;

    // Some additional date/time info
    $START = $Entry['StartTime'] > 0 ? localtime($Entry['StartTime']) : 0;
    $END   = $Entry['EndTime'] > 0 ? localtime($Entry['EndTime']) : 0;
    $Entry['StartMinute']        = sprintf ("%02d",$START[1]);
    $Entry['StartHour']          = sprintf ("%02d",$START[2]);
    $Entry['StartDay']           = sprintf ("%02d",$START[3]);
    $Entry['StartMonth']         = sprintf ("%02d",$START[4] + 1);
    $Entry['StartYear']          = sprintf ("%04d",$START[5] + 1900);
    $Entry['EndMinute']          = sprintf ("%02d",$END[1]);
    $Entry['EndHour']            = sprintf ("%02d",$END[2]);
    $Entry['EndDay']             = sprintf ("%02d",$END[3]);
    $Entry['EndMonth']           = sprintf ("%02d",$END[4] + 1);
    $Entry['EndYear']            = sprintf ("%04d",$END[5] + 1900);
   //not in icalclient
    if ( $overwrite && ! empty ( $Entry['UID'] ) ) {
      if ( empty ( $oldUIDs[$Entry['UID']] ) ) {
        $oldUIDs[$Entry['UID']] = 1;
      } else {
        $oldUIDs[$Entry['UID']]++;
      }
    }

    // Check for untimed
    if ( ! empty ( $Entry['Untimed'] ) && $Entry['Untimed'] == 1) {
      $Entry['StartMinute'] = '';
      $Entry['StartHour'] = '';
      $Entry['EndMinute'] = '';
      $Entry['EndHour'] = '';
    }

    // Check for all day
    if ( ! empty ( $Entry['AllDay'] ) && $Entry['AllDay'] == 1) {
      $Entry['StartMinute'] = '0';
      $Entry['StartHour'] = '0';
      $Entry['EndMinute'] = '0';
      $Entry['EndHour'] = '0';
      $Entry['Duration'] = '1440';
    }

  if ( ! empty  ( $Entry['Completed'] ) ) {
    $cal_completed = substr ( $Entry['Completed'], 0, 8 );
  } else {
    $cal_completed = '';
  }
    if ( strlen ( $cal_completed < 8 ) ) $cal_completed = '';
  

   $months =  (! empty ( $Entry['Repeat']['ByMonth'] ) ) ? 
     $Entry['Repeat']['ByMonth'] : "";
     
   if  ( $subType != 'icalclient' ) {
    // first check for any schedule conflicts
    if ( ( $ALLOW_CONFLICT_OVERRIDE == "N" && $ALLOW_CONFLICTS == "N" ) &&
      ( $Entry['Duration'] != 0 )) {
      $date = mktime (0,0,0,$Entry['StartMonth'],
        $Entry['StartDay'],$Entry['StartYear']);

    
      $ex_days = array ();
      if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
        foreach ($Entry['Repeat']['Exceptions'] as $ex_date) {
          $ex_days[] = date("Ymd",$ex_date);
        }
      }
   $inc_days = array ();
      if ( ! empty ( $Entry['Repeat']['Inclusions'] ) ) {
        foreach ($Entry['Repeat']['Inclusions'] as $inc_date) {
          $inc_days[] = date("Ymd",$inc_date);
        }
      }

    $dates = get_all_dates($date, RepeatType($Entry['Repeat']['Frequency']), 
      $Entry['Repeat']['Interval'], $Entry['Repeat']['ByMonth'], 
      $Entry['Repeat']['ByWeekNo'], $Entry['Repeat']['ByYearDay'],
      $Entry['Repeat']['ByMonthDay'], $Entry['Repeat']['ByDay'],
      $Entry['Repeat']['BySetPos'], $Entry['Repeat']['Count'],
      $Entry['Repeat']['Until'], $Entry['Repeat']['Wkst'],
      $ex_days, $inc_days);

      $overlap = check_for_conflicts ( $dates, $Entry['Duration'], 
        $Entry['StartHour'], $Entry['StartMinute'], $participants, $login, 0 );
    }

    if ( empty ( $error ) && ! empty ( $overlap ) ) {
      $error = translate("The following conflicts with the suggested time").
        ":<ul>$overlap</ul>\n";
    }
  } //end  $subType != 'icalclient'
  
    if ( empty ( $error ) ) { 
      $updateMode = false;
    
      // See if event already is there from prior import.
      // The same UID is used for all events imported at once with iCal.
      // So, we still don't have enough info to find the exact
      // event we want to replace.  We could just delete all
      // existing events that correspond to the UID.

    
      // NOTE: (cek) commented out 'publish'.  Will not work if event
      // was originally created from importing.
      if ( ! empty ( $Entry['UID'] ) ) {
        $res = dbi_query ( "SELECT webcal_import_data.cal_id " .
          "FROM webcal_import_data, webcal_entry_user WHERE " .
          //"cal_import_type = 'publish' AND " .
          "webcal_import_data.cal_id = webcal_entry_user.cal_id AND " .
          "webcal_entry_user.cal_login = '$login' AND " .
          "cal_external_id = '$Entry[UID]'" );
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
     
      if ( ! $updateMode ) {
        // Add the Event
        $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $id = $row[0] + 1;
          dbi_free_result ( $res );
        } else {
          $id = 1;
        }
      }

//not in icalclient
      if ( $firstEventId == 0 )
        $firstEventId = $id;

      $names = array ();
      $values = array ();
      $names[] = 'cal_id';
      $values[] = "$id";
      if ( ! $updateMode ) {
        $names[] = 'cal_create_by';
        $values[] = "'$login'";
      }
      $names[] = 'cal_date';
      $values[] = sprintf ( "%04d%02d%02d",
        $Entry['StartYear'],$Entry['StartMonth'],$Entry['StartDay']);
      $names[] = 'cal_time';
      $values[] = ( ! empty ( $Entry['Untimed'] ) && 
        $Entry['Untimed'] == 1) ? "-1" :
        sprintf ( "%02d%02d00", $Entry['StartHour'],$Entry['StartMinute']);
      $names[] = 'cal_mod_date';
      $values[] = gmdate("Ymd");
      $names[] = 'cal_mod_time';
      $values[] = gmdate("Gis");
      $names[] = 'cal_duration';
      $values[] = sprintf ( "%d", $Entry['Duration'] );
      $names[] = 'cal_priority';
      $values[] = $priority;

      if ( ! empty ( $Entry['Class'])){   
     $names[] = 'cal_access';     
    $entryclass = ($Entry['Class'] == "Private"? "R":
      ( $Entry['Class'] == "Confidential"?"C":"P") );
        $values[] = "'$entryclass'";
      } 

      if ( ! empty ( $cal_completed ) ) {
          $names[] = 'cal_completed';
          $values[] = "'$cal_completed'";
       }
   if ( ! empty ( $Entry['Due'] ) ) {
        $names[] = 'cal_due_date';
        $values[] = sprintf ( "%d" , substr ( $Entry['Due'], 0, 8 ) );
        $names[] = 'cal_due_time';
        $values[] = sprintf ( "%d" , substr ( $Entry['Due'], 9, 6 ) );
   }
      if ( ! empty ( $Entry['CalendarType'] ) ){      
        $names[] = 'cal_type';
     if ( $Entry['CalendarType'] == "VEVENT" ) {
          $values[] = (! empty ( $Entry['Repeat']) )? "'M'" : "'E'";
     } else if ( $Entry['CalendarType'] == "VTODO" ) {
          $values[] = (! empty ( $Entry['Repeat']) )? "'N'" : "'T'";
     }
      }
      if ( strlen ( $Entry['Summary'] ) == 0 )
        $Entry['Summary'] = translate("Unnamed Event");
      if ( empty ( $Entry['Description'] ) )
        $Entry['Description'] = $Entry['Summary'];
      $Entry['Summary'] = str_replace ( "\\n", "\n", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "\\'", "'", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "\\\"", "\"", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "'", "\\'", $Entry['Summary'] );
      $names[] = 'cal_name';
      $values[] = "'" . $Entry['Summary'] .  "'";
      $Entry['Description'] = str_replace ( "\\n", "\n", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "\\'", "'", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "\\\"", "\"", $Entry['Description'] );
      $Entry['Description'] = str_replace ( "'", "\\'", $Entry['Description'] );
     //added these to try and compensate for Sunbird escaping html
     $Entry['Description'] = str_replace ( "\;", ";", $Entry['Description'] );
     $Entry['Description'] = str_replace ( "\,", ",", $Entry['Description'] );
      // Mozilla will send this goofy string, so replace it with real html
      $Entry['Description'] = str_replace ( "=0D=0A=", "<br />", 
        $Entry['Description'] );
      $Entry['Description'] = str_replace ( "=0D=0A", "", 
        $Entry['Description'] );
      // Allow option to not limit description size
      // This will only be practical for mysql and MSSQL/Postgres as 
      //these do not have limits on the table definition
      //TODO Add this option to preferences
      if ( empty ( $LIMIT_DESCRIPTION_SIZE ) || 
         $LIMIT_DESCRIPTION_SIZE == "Y" ) {
      // limit length to 1024 chars since we setup tables that way
        if ( strlen ( $Entry['Description'] ) >= 1024 ) {
        $Entry['Description'] = substr ( $Entry['Description'], 0, 1019 ) . "...";
        }
      }
      $names[] = 'cal_description';
      $values[] = "'" . $Entry['Description'] .  "'";
      //do_debug ( "descr='" . $Entry['Description'] . "'" );
      if ( $updateMode ) {
        $sql = "UPDATE webcal_entry SET ";
        for ( $f = 0; $f < count ( $names ); $f++ ) {
          if ( $f > 0 )
            $sql .= ", ";
          $sql .= $names[$f] . " = " . $values[$f];
        }
        $sql .= " WHERE cal_id = $id";
      } else {
        $sql = "INSERT INTO webcal_entry ( " . implode ( ", ", $names ) .
          " ) VALUES ( " . implode ( ", ", $values ) . " )";
      }

      //do_debug ( "SQL> $sql" );
      if ( empty ( $error ) ) {
        if ( ! dbi_query ( $sql ) ) {
          $error .= translate("Database error") . ": " . dbi_error ();
          do_debug ( $error );
          break;
        }
      }

      // log add/update
   if ( $Entry['CalendarType'] == "VTODO" ) {
        activity_log ( $id, $login, $login,
          $updateMode ? LOG_UPDATE_T : LOG_CREATE_T, "Import from $ImportType" );
   } else {
        activity_log ( $id, $login, $login,
          $updateMode ? LOG_UPDATE : LOG_CREATE, "Import from $ImportType" );   
   }
 //not in icalclient
      if ( $single_user == "Y" ) {
        $participants[0] = $single_user_login;
      }

      // Now add to webcal_import_data
      if ( ! $updateMode ) {
     //only in icalclient
 //add entry to webcal_import and webcal_import_data
        $uid = generate_uid ($id);
        $uid = empty ( $Entry['UID'] ) ? $uid  : $Entry['UID'];
        if ( $importId < 0 ) {
          $importId = create_import_instance ();
        }

        if ($ImportType == "PALMDESKTOP") {
          $sql = "INSERT INTO webcal_import_data ( cal_import_id, cal_id, " .
            "cal_login, cal_import_type, cal_external_id ) VALUES ( " .
            "$importId, $id, '$calUser', 'palm', '$Entry[RecordID]' )";
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_query ( $sql ) ) {
            $error = translate("Database error") . ": " . dbi_error ();
            break;
          }
        }
        else if ($ImportType == "VCAL") {
          $uid = empty ( $Entry['UID'] ) ? "null" : "'$Entry[UID]'";
          if ( strlen ( $uid ) > 200 )
            $uid = "NULL";
          $sql = "INSERT INTO webcal_import_data ( cal_import_id, cal_id, " .
            "cal_login, cal_import_type, cal_external_id ) VALUES ( " .
            "$importId, $id, '$calUser', 'vcal', $uid )";
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_query ( $sql ) ) {
            $error = translate("Database error") . ": " . dbi_error ();
            break;
          }
        }
        else if ($ImportType == "ICAL") {
          $uid = empty ( $Entry['UID'] ) ? "null" : "'$Entry[UID]'";
          // This may cause problems
          if ( strlen ( $uid ) > 200 )
            $uid = "NULL";
          $sql = "INSERT INTO webcal_import_data ( cal_import_id, cal_id, " .
            "cal_login, cal_import_type, cal_external_id ) VALUES ( " .
            "$importId, $id, '$calUser', 'ical', $uid )";
          $sqlLog .= $sql . "<br />\n";
          if ( ! dbi_query ( $sql ) ) {
            $error = translate("Database error") . ": " . dbi_error ();
            break;
          }
        }
      }

      // Now add participants
      $status = ( ! empty ( $Entry['Status'] ) ? $Entry['Status'] : "A" );
      $percent = ( ! empty ( $Entry['Percent'] ) ? $Entry['Percent'] : '0' );
      if ( ! $updateMode ) {
        //do_debug ( "Adding event $id for user $participants[0] with status=$status" );
        $sql = "INSERT INTO webcal_entry_user " .
          "( cal_id, cal_login, cal_status, cal_percent ) VALUES ( $id, '" .
          $participants[0] . "', '" . $status . "', $percent )";
        //do_debug ( "SQL> $sql" );
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
          do_debug ( "Error: " . $error );
          break;
        }
      } else {
        //do_debug ( "Updating event $id for user $participants[0] with status=$status" );
        //do_debug ( "SQL> $sql" );
        $sql = "UPDATE webcal_entry_user SET cal_status = '". $status . "' ," .
         " cal_percent = '" . $percent . "' " .
          " WHERE cal_id = $id";
       if ( ! dbi_query ( $sql ) ) {
         $error = translate("Database error") . ": " . dbi_error ();
         do_debug ( "Error: " . $error );
         break;
       }
    dbi_query ( "DELETE FROM webcal_entry_categories WHERE cal_id = $id" );    
     }
     //update Categories
   if ( ! empty ($Entry['Categories'] ) ) {
      $cat_ids = $Entry['Categories'];
     $cat_order = 1;
     foreach ( $cat_ids as $cat_id ) {
           $sql = "INSERT INTO webcal_entry_categories " .
            "( cal_id, cat_id, cat_order ) VALUES ( $id, " .
       $cat_id . ", $cat_order )";
       $cat_order++;   

          if ( ! dbi_query ( $sql ) ) {
            $error = translate("Database error") . ": " . dbi_error ();
            do_debug ( "Error: " . $error );
            break;
          } 
    } 
    }
     // Add repeating info
     if ( $updateMode ) {
       // remove old repeating info
       dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
       dbi_query ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = $id" );
     }
   
     if (! empty ($Entry['Repeat']['Frequency'])) {
       $names = array();
       $values = array();
       $names[] = 'cal_id';
       $values[]  = $id;
   
       $names[]  = 'cal_type';
       $values[] = "'" . RepeatType($Entry['Repeat']['Frequency']) . "'";
        
       $names[] = 'cal_frequency';
       $values[] = ( ! empty ( $Entry['Repeat']['Interval'] ) ? 
         $Entry['Repeat']['Interval'] : 1 );
 
      if (! empty ( $Entry['Repeat']['ByMonth'] ) ){
        $names[] = 'cal_bymonth';
        $values[]  = "'" . $Entry['Repeat']['ByMonth'] . "'";
      } 
    
      if (! empty ( $Entry['Repeat']['ByMonthDay'] ) ){
        $names[] = 'cal_bymonthday';
        $values[]  = "'" . $Entry['Repeat']['ByMonthDay'] . "'";
      } 
      if ( ! empty ( $Entry['Repeat']['ByDay'] ) ){
        $names[] = 'cal_byday';
        $values[] =  "'" . $Entry['Repeat']['ByDay'] . "'";
      }
   if (! empty ( $Entry['Repeat']['BySetPos'] ) ){
    $names[] = 'cal_bysetpos';
    $values[] = "'" . $Entry['Repeat']['BySetPos'] . "'";
   }
   if (! empty ( $Entry['Repeat']['ByWeekNo'] ) ){
    $names[] = 'cal_byweekno';
    $values[] = "'" . $Entry['Repeat']['ByWeekNo']. "'";
   }
   if (! empty ( $Entry['Repeat']['ByYearDay'] ) ) {
    $names[] = 'cal_byyearday';
    $values[] = "'" . $Entry['Repeat']['ByYearDay'] . "'";
   }
   if (! empty ( $Entry['Repeat']['Wkst'] ) ) {
    $names[] = 'cal_wkst';
    $values[] = "'" . $Entry['Repeat']['Wkst'] . "'";
   }
 
    if (! empty ( $Entry['Repeat']['Count'] ) ) {
    $names[] = 'cal_count';
    $values[] = "'" . $Entry['Repeat']['Count'] . "'";
   }

      if ( ! empty ( $Entry['Repeat']['Until'] ) ) {
         $REND   = localtime($Entry['Repeat']['Until']);
     if (! empty ( $Entry['Repeat']['Count'] ) ) {
       //Get end time from DTSTART
      $RENDTIME =sprintf ( "%02d%02d00", $Entry['StartHour'],$Entry['StartMinute']);
     } else {
       $RENDTIME = sprintf ( "%02d%02d%02d", $REND[2], $REND[1], $REND[0]); 
      }
     $names[] = 'cal_end';
         $values[] = sprintf ( "%04d%02d%02d",$REND[5] + 1900, $REND[4] + 1, $REND[3]);
    // if ( $RENDTIME != '000000' ) {
        $names[] = 'cal_endtime';         
           $values[] = $RENDTIME;
      //  }
     }
 
    $sql = "INSERT INTO webcal_entry_repeats ( " . implode ( ", ", $names ) .
          " ) VALUES ( " . implode ( ", ", $values ) . " )";
     
       if ( ! dbi_query ( $sql ) ) {
         $error = "Unable to add to webcal_entry_repeats: ".dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
         break;
        }

        // Repeating Exceptions...
        if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
          foreach ($Entry['Repeat']['Exceptions'] as $ex_date) {
            $ex_date = date("Ymd",$ex_date);
            $sql = "INSERT INTO webcal_entry_repeats_not " .
              "( cal_id, cal_date, cal_exdate ) VALUES ( $id, $ex_date, 1 )";
 
            if ( ! dbi_query ( $sql ) ) {
              $error = "Unable to add to webcal_entry_repeats_not: ".
                dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
              break;
            }
          }
        }
  // Repeating Inclusions...
        if ( ! empty ( $Entry['Repeat']['Inclusions'] ) ) {
          foreach ($Entry['Repeat']['Inclusions'] as $inc_date) {
            $inc_date = date("Ymd",$inc_date);
            $sql = "INSERT INTO webcal_entry_repeats_not " .
              "( cal_id, cal_date, cal_exdate ) VALUES ( $id, $inc_date, 0 )";
 
            if ( ! dbi_query ( $sql ) ) {
              $error = "Unable to add to webcal_entry_repeats_not: ".
                dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
              break;
            }
          }
        }
      } // End Repeat


      // Add Alarm info -> site_extras
      if ( $updateMode ) {
        dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_type = 7 AND cal_id = $id" );
      }
      if ( ! empty ( $Entry['AlarmSet'] ) && $Entry['AlarmSet'] == 1 ) {
        $alarms = explode ( ",", $Entry['Alarm']);
       $rem_count = 1;
    foreach ( $alarms as $alarm ) {
     $alH = $alM = $alD = 0;
          if ( preg_match ( "/PT([0-9]+)H/", $alarm, $submatch ) ) {
            $alH = $submatch[1];
         $Entry['AlarmAdvanceType'] = 1;
        }
          if ( preg_match ( "/PT([0-9]+)M/", $alarm, $submatch ) ) {
           $alM = $submatch[1];
         $Entry['AlarmAdvanceType'] = 0;
        }
        if ( preg_match ( "/P([0-9]+)D/", $alarm, $submatch ) ) {
           $alD = $submatch[1];
         $Entry['AlarmAdvanceType'] = 2;
        }
          $RM = $alD  + $alH  + $alM;

          if ($Entry['AlarmAdvanceType'] == 1){ $RM = $RM * 60; }
          if ($Entry['AlarmAdvanceType'] == 2){ $RM = $RM * ( 60 * 24 ); }
          $sql = "INSERT INTO webcal_site_extras ( cal_id, " .
            "cal_name, cal_type, cal_remind, cal_data ) VALUES " .
            "( $id, 'Reminder" . $rem_count . "', 7, 1, '$RM' )";

          if ( ! dbi_query ( $sql ) ) {
            $error = translate("Database error") . ": " . dbi_error ();
          }
     $rem_count++;
       }//end foreach
     }

  //here to end not in icalclient
 if ( $subType != 'icalclient' ) {
    if ( ! empty ($error) && empty ($overlap))  {
      $error_num++;
      echo "<h2>". translate("Error") .
        "</h2>\n<blockquote>\n";
      echo $error . "</blockquote>\n<br />\n";
    }

    // Conflicting
    if ( ! empty ( $overlap ) ) {
      echo "<b><h2>" .
        translate("Scheduling Conflict") . ": ";
      $count_con++;
      echo "</h2></b>";

      if ( $Entry['Duration'] > 0 ) {
        $time = display_time ( $Entry['StartHour'].$Entry['StartMinute']."00", 1 ) .
          " - " . display_time ( $Entry['EndHour'].$Entry['EndMinute']."00", 3 );
      }
      $dd = $Entry['StartMonth'] . "-" .  $Entry['StartDay'] . "-" . $Entry['StartYear'];
      $Entry['Summary'] = str_replace ( "''", "'", $Entry['Summary'] );
      $Entry['Summary'] = str_replace ( "'", "\\'", $Entry['Summary'] );
      echo htmlspecialchars ( $Entry['Summary'] );
      echo " (" . $dd;
      $time = trim ( $time );
      if ( ! empty ( $time ) )
        echo "&nbsp; " . $time;
      echo ")<br />\n";
      etranslate("conflicts with the following existing calendar entries");
      echo ":<ul>\n" . $overlap . "</ul>\n";
    } else {

    // No Conflict
  if  ( $count_suc == 0 ) {
      echo "<b><h2>" .
        translate("Event Imported") . ":</h2></b><br />\n";
  }
      $count_suc++;
      if ( $Entry['Duration'] > 0 ) {
        $time = display_time ( $Entry['StartHour'].$Entry['StartMinute']."00", 1 ) .
          " - " . display_time ( $Entry['EndHour'].$Entry['EndMinute']."00", 3 );
      }
      $dateYmd = sprintf ( "%04d%02d%02d", $Entry['StartYear'],
        $Entry['StartMonth'], $Entry['StartDay'] );
      $dd = date_to_str ( $dateYmd );
      echo "<a class=\"entry\" href=\"view_entry.php?id=$id";
      echo "\" onmouseover=\"window.status='" . translate("View this entry") .
        "'; return true;\" onmouseout=\"window.status=''; return true;\">";
      $Entry['Summary'] = str_replace( "''", "'", $Entry['Summary']);
      $Entry['Summary'] = str_replace( "\\", "", $Entry['Summary']);
      echo htmlspecialchars ( $Entry['Summary'] );
      echo "</a> (" . $dd;
      if ( ! empty ( $time ) )
        echo "&nbsp; " . $time;
      echo ")<br /><br />\n";
    }

    // Reset Variables
    $overlap = $error = $dd = $time = '';
  }

  // Mark old events from prior import as deleted.
  if ( $overwrite && count ( $oldUIDs ) > 0 ) {
    // We could do this with a single SQL using sub-select, but
    // I'm pretty sure MySQL does not support it.
    $old = array_keys ( $oldUIDs );
    for ( $i = 0; $i < count ( $old ); $i++ ) {
      $sql = "SELECT cal_id FROM webcal_import_data WHERE " .
        "cal_import_type = '$type' AND " .
        "cal_external_id = '$old[$i]' AND " .
        "cal_login = '$calUser' AND " .
        "cal_id < $firstEventId";
      $res = dbi_query ( $sql );
      if ( $res ) {
        while ( $row = dbi_fetch_row ( $res ) ) {
          $oldIds[] = $row[0];
        }
        dbi_free_result ( $res );
      } else {
        echo translate("Database error") . ": " . dbi_error () . "<br />\n";
      }
    }
    for ( $i = 0; $i < count ( $oldIds ); $i++ ) {
      $sql = "UPDATE webcal_entry_user SET cal_status = 'D' " .
        "WHERE cal_id = $oldIds[$i]";
      $sqlLog .= $sql . "<br />\n";
      dbi_query ( $sql );
      $numDeleted++;
    }
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
// There seems to be a bug in certain versions of PHP where the fgets()
// returns a blank string when reading stdin.  I found this to be
// a problem with PHP 4.1.2 on Linux.
// I did work correctly with PHP 5.0.2.
function parse_ical ( $cal_file, $source='file' ) {
  global $tz, $errormsg;
  $ical_data = array();
 if ( $source == 'file' ) {
  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {

    // Read in contents of entire file first
    $data = '';
    $line = 0;
    while (!feof($fd) && empty( $error ) ) {
      $line++;
      $data .= fgets($fd, 4096);
    }
    fclose($fd);
  }
 } else if ( $source == 'icalclient' ) {
  //do_debug ( "before fopen on stdin..." );
  $stdin = fopen ("php://input", "r");
  //$stdin = fopen ("/dev/stdin", "r");
  //$stdin = fopen ("/dev/fd/0", "r");
  //do_debug ( "after fopen on stdin..." );
  // Read in contents of entire file first
  $data = '';
  $cnt = 0;
  while ( ! feof ( $stdin ) ) {
    $line = fgets ( $stdin, 1024 );
    //do_debug ( "data-> '" . $line . "'" );
    $cnt++;
    //do_debug ( "cnt = " . ( ++$cnt ) );
    $data .= $line;
    if ( $cnt > 10 && strlen ( $data ) == 0 ) {
      do_debug ( "Read $cnt lines of data, but got no data :-(" );
      do_debug ( "Informing user of PHP server bug (PHP v" . phpversion() . ")" );
      // Note: Mozilla Calendar does not display this error for some reason.
      echo "<br /><b>Error:</b> Your PHP server " . phpversion () .
        " seems to have a bug reading stdin.  " .
        "Try upgrading to a newer PHP release.  <br />";
      exit;
    }
  }
  fclose ( $stdin );

  //do_debug ( "strlen(data)=" . strlen($data) );

   // Check for PHP stdin bug
   if ( $cnt > 5 && strlen ( $data ) < 10 ) {
      do_debug ( "Read $cnt lines of data, but got no data :-(" );
      do_debug ( "Informing user of PHP server bug" );
      header ( "Content-Type: text/plain" );
      echo "Error: Your PHP server " . phpversion () .
        " seems to have a bug reading stdin.\n" .
        "Try upgrading to a newer release.\n";
      exit;
    } 
 }
    // Now fix folding.  According to RFC, lines can fold by having
    // a CRLF and then a single white space character.
    // We will allow it to be CRLF, CR or LF or any repeated sequence
    // so long as there is a single white space character next.
    //echo "Orig:<br /><pre>$data</pre><br /><br />\n";
    $data = preg_replace ( "/[\r\n]+ /", "", $data );
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );
    //echo "Data:<br /><pre>$data</pre><p>";

    // reflect the section where we are in the file:
    // VEVENT, VTODO, VJORNAL, VFREEBUSY, VTIMEZONE
    $state = "NONE";
    $substate = "none"; // reflect the sub section
    $subsubstate = ""; // reflect the sub-sub section
    $error = false;
    $line = 0;
    $event = '';
    $lines = explode ( "\n", $data );
    for ( $n = 0; $n < count ( $lines ) && ! $error; $n++ ) {
      $line++;
      $buff = trim( $lines[$n] );
      if ( preg_match ( "/^PRODID:(.+)$/i", $buff, $match) ) {
        $prodid = $match[1];
        $prodid = str_replace ( "-//", "", $prodid);
        $prodid = str_replace ( "\,", ",", $prodid );
        //do_debug ( "Product ID: " . $prodid );
      }
      // parser debugging code...
      //echo "line = $line <br />";
      //echo "state = $state <br />";
      //echo "substate = $substate <br />";
      //echo "subsubstate = $subsubstate <br />";
      //echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";

      if ($state == "VEVENT" || $state == "VTODO") {
          if ( ! empty ( $subsubstate ) ) {
            if (preg_match("/^END:(.+)$/i", $buff, $match)) {
              if ( $match[1] == $subsubstate ) {
                $subsubstate = '';
              }
            } else if ( $subsubstate == "VALARM" && 
              preg_match ( "/TRIGGER.*:(.+)$/i", $buff, $match ) ) {
              // Example: TRIGGER;VALUE=DATE-TIME:19970317T133000Z
          $substate = "alarm";
           //allows multiple ocurrances of VALRM to be processed
           if (isset($event[$substate] ) ) {
                $event[$substate] .= "," . $match[1];
           } else {
                $event[$substate] = $match[1];   
           }
            }
          }
          else if (preg_match("/^BEGIN:(.+)$/i", $buff, $match)) {
            $subsubstate = $match[1];
          }
           // we suppose ":" is on the same line as property name, this can perhaps cause problems
          else if (preg_match("/^SUMMARY.*:(.+)$/i", $buff, $match)) {
              $substate = "summary";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION.*:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CLASS.*:(.*)$/i", $buff, $match)) {
              $substate = "class";
              $event[$substate] = $match[1];
        } elseif (preg_match("/^LOCATION.*:(.+)$/i", $buff, $match)) {
              $substate = "location";
              $event[$substate] = $match[1];
        } elseif (preg_match("/^TRANSP.*:(.+)$/i", $buff, $match)) {
              $substate = "transparency";
              $event[$substate] = $match[1];
        } elseif (preg_match("/^STATUS.*:(.*)$/i", $buff, $match)) {
              $substate = "status";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY.*:(.*)$/i", $buff, $match)) {
              $substate = "priority";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTSTART(.*):\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[2];
              if ( preg_match ( "/TZID=(.*)$/i", $match[1], $submatch ) ) {
        $substate = "dtstartTzid"; 
        $event[$substate] = $submatch[1];
           } else if ( preg_match ( "/VALUE=DATE-TIME(.*)$/i", $match[1], $submatch ) ) {
        $substate = "dtstartDATETIME"; 
        $event[$substate] = true;
           } else if ( preg_match ( "/VALUE=DATE(.*)$/i", $match[1], $submatch ) ) {
        $substate = "dtstartDATE"; 
        $event[$substate] = true;      
       }
          } elseif (preg_match("/^DTEND(.*):\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "dtend";
              $event[$substate] = $match[2];
              if ( preg_match ( "/TZID=(.*)$/i", $match[1], $submatch ) ) {
        $substate = "dtendTzid"; 
        $event[$substate] = $match[1];
           }
          } elseif (preg_match("/^DUE.*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "due";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^COMPLETED.*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "completed";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PERCENT-COMPLETE.*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "percent";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DURATION.*:(.+)\s*$/i", $buff, $match)) {
              $substate = "duration";
              $durH = $durM = 0;
              if ( preg_match ( "/PT.*([0-9]+)H/", $match[1], $submatch ) )
                $durH = $submatch[1];
              if ( preg_match ( "/PT.*([0-9]+)M/", $match[1], $submatch ) )
                $durM = $submatch[1];
              $event[$substate] = $durH * 60 + $durM;
          } elseif (preg_match("/^RRULE.*:(.+)$/i", $buff, $match)) {
              $substate = "rrule";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^EXDATE.*:(.+)$/i", $buff, $match)) {
              $substate = "exdate";
          //allows multiple ocurrances of EXDATE to be processed
          if (isset($event[$substate] ) ) {
                $event[$substate] .= "," . $match[1];
          } else {
                $event[$substate] = $match[1];   
          }
          } elseif (preg_match("/^RDATE.*:(.+)$/i", $buff, $match)) {
              $substate = "rdate";
           //allows multiple ocurrances of RDATE to be processed
           if (isset($event[$substate] ) ) {
                $event[$substate] .= "," . $match[1];
           } else {
                $event[$substate] = $match[1];   
           }
          } elseif (preg_match("/^CATEGORIES.*:(.+)$/i", $buff, $match)) {
              $substate = "categories";
           //allows multiple ocurrances of CATEGORIES to be processed
           if (isset($event[$substate] ) ) {
                $event[$substate] .= "," . $match[1];
           } else {
                $event[$substate] = $match[1];   
           }
          } elseif (preg_match("/^UID.*:(.+)$/i", $buff, $match)) {
              $substate = "uid";
              $event[$substate] = $match[1];
        } else if (preg_match("/^BEGIN:VALARM/i", $buff)) {
            $subsubstate = "VALARM";
          } elseif (preg_match("/^END:VEVENT$/i", $buff, $match)) {
              if ($tmp_data = format_ical($event)) $ical_data[] = $tmp_data;
              $state = "VCALENDAR";
              $substate = "none";
              $subsubstate = '';
              // clear out data for new event
              $event = '';
          } elseif (preg_match("/^END:VTODO$/i", $buff, $match)) {
              if ($tmp_data = format_ical($event)) $ical_data[] = $tmp_data;
              $state = "VCALENDAR";
              $substate = "none";
              $subsubstate = '';
              // clear out data for new event
              $event = '';

      //Folded Lines have already been taken care of
          } elseif (preg_match("/^\s(\S.*)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
                  $errormsg .= "iCal parse error on line $line:<br />$buff\n";
                  $error = true;
              }
          // For unsupported properties
   } else {
            $substate = "none";
          }
      } elseif ($state == "VCALENDAR") {
          if (preg_match("/^BEGIN:VEVENT/i", $buff)) {
            $state = "VEVENT";
          } elseif (preg_match("/^END:VCALENDAR/i", $buff)) {
            $state = "NONE";
          } else if (preg_match("/^BEGIN:VTIMEZONE/i", $buff)) {
            $state = "VTIMEZONE";
          } else if (preg_match("/^BEGIN:VTODO/i", $buff)) {
            $state = "VTODO";
          }
     $event['state'] = $state;
      } elseif ($state == "VTIMEZONE") {
        // We don't do much with timezone info yet...
        if (preg_match("/^END:VTIMEZONE$/i", $buff)) {
          $state = "VCALENDAR";
        }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
           $state = "VCALENDAR";
      }
    } // End while

  return $ical_data;
}

// Convert interval to webcal repeat type
function RepeatType ($type) {
  $Repeat = array (0,'daily','weekly','monthlyByDay','monthlyByDate',
    'monthlyBySetPos','yearly','manual');
  return $Repeat[$type];
}

// Convert ical format (yyyymmddThhmmssZ) to epoch time
function icaldate_to_timestamp ($vdate, $tzid = '', $plus_d = '0', $plus_m = '0',
  $plus_y = '0') {
  global $SERVER_TIMEZONE, $calUser;;
  $this_TIMEZONE = $Z = '';
 
  $y = substr($vdate, 0, 4) + $plus_y;
  $m = substr($vdate, 4, 2) + $plus_m;
  $d = substr($vdate, 6, 2) + $plus_d;
  $H = substr($vdate, 9, 2);
  $M = substr($vdate, 11, 2);
  $S = substr($vdate, 13, 2);
  $Z = substr($vdate, 15, 1);

  //Sunbird does not do Timezone right so...
 //We'll just hardcode their GMT timezone def here
 switch  ( $tzid ) {
   case "/Mozilla.org/BasicTimezones/GMT":
     $Z = "Z"; //force GMT
     break;
   case "US-Eastern":
   case "US/Eastern":
     $this_TIMEZONE = "America/New_York"; 
     break;
   case "US-Central":
   case "US/Central":
     $this_TIMEZONE = "America/America/Chicago"; 
     break;
   case "US-Pacific":
   case "US/Pacific":
     $this_TIMEZONE = "America/Los_Angeles"; 
     break;
   case "":
     break;   
   default:
     $sql = "SELECT zone_name FROM webcal_tz_zones WHERE zone_name = '$tzid'";
     $res = dbi_query($sql);
     if ( $row = dbi_fetch_row($res) ) {
       $this_TIMEZONE = $tzid;
    }   else {
       echo translate ( "Unknown Timezone" ) . ": <b>$tzid</b> " . 
         translate ( "defaulting to GMT" ) . ". ";
       echo "<a href=\"docs/WebCalendar-SysAdmin.html#faq\" target=\"_docs\">" .
         translate ( "Please see FAQ" ) . "</a><br />"; 
       $Z = "Z"; //force GMT        
    }   
   break;
 } //end switch
 
  $TS = mktime($H,$M,$S,$m,$d,$y);
  if ($Z != 'Z') {
    // Convert time from user's timezone to GMT if datetime value
    if ( strlen ( $vdate ) > 8 ) {
      if ( empty ( $this_TIMEZONE ) ) {
        $this_TIMEZONE = get_pref_setting ( $calUser, "TIMEZONE" );
        $this_TIMEZONE = ( ! empty ( $user_TIMEZONE ) ? $user_TIMEZONE : $SERVER_TIMEZONE );
      }
      $tz_offset = get_tz_offset ( $this_TIMEZONE, $TS );
      $TS = $TS - ( $tz_offset[0] * 3600 );
    }
  }
  return $TS;
}


// Put all ical data into import hash structure
function format_ical($event) {
global $login;

  //Set Calendar Type for easier processing later
 $fevent['CalendarType'] = $event['state'];

 
  //Categories
  if ( isset ( $event['categories'] ) ) {
   //$fevent['Categories']  will contain an array of cat_id(s) that match the
  //category_names
    $fevent['Categories'] = get_categories_id_byname  ($event['categories']);
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
  $fevent['StartTime'] = icaldate_to_timestamp($event['dtstart'], $dtstartTzid );
  if ( isset ( $event['dtend'] ) ) {
   $dtendTzid = ( ! empty ( $event['dtendTzid'] )?$event['dtendTzid'] : '' );
    $fevent['EndTime'] = icaldate_to_timestamp($event['dtend'], $dtendTzid );
  } else if ( isset ( $event['duration'] ) ) {
    $fevent['EndTime'] = $fevent['StartTime'] + $event['duration'] * 60;
  } else if ( isset ( $event['dtstartDATETIME'] ) ) {
   //Untimed
    $fevent['EndTime'] = $fevent['StartTime'];
    $fevent['Untimed'] = 1;
  } else if ( isset ( $event['dtstartDATE'] ) ) {
   //This is an all day event
    $fevent['EndTime'] = $fevent['StartTime'] + 3600;
    $event['duration'] = 1440;
  } else {
    $fevent['EndTime'] = $fevent['StartTime'];
  }

 
  // Calculate duration in minutes
  if ( isset ( $event['duration'] ) ) {
    $fevent['Duration'] = $event['duration'];
  } else if ( empty ( $fevent['Duration'] ) ) {
    $fevent['Duration'] = ($fevent['EndTime'] - $fevent['StartTime']) / 60;
  }
//  if ( $fevent['Duration'] == '1440' ) {
    // All day event... nothing to do here :-)
//  } else if ( preg_match ( "/\d\d\d\d\d\d\d\d$/",
//    $event['dtstart'], $pmatch ) ) {
    // Untimed event
//    $fevent['Duration'] = 0;
//    $fevent['Untimed'] = 1;
 // }
//do_debug ( print_r ( $fevent, true) ) ;
//do_debug ( date ("YmdHis", $fevent['StartTime']) . " " . date ("YmdHis", $fevent['EndTime']) );
  if ( isset ( $event['dtend'] ) && preg_match ( "/\d\d\d\d\d\d\d\d$/", $event['dtstart'],
    $pmatch ) && preg_match ( "/\d\d\d\d\d\d\d\d$/", $event['dtend'],
  $pmatch2 ) && $event['dtstart'] != $event['dtend'] ) {
    $startTime = icaldate_to_timestamp($event['dtstart']);
    $endTime = icaldate_to_timestamp($event['dtend']);
    // Not sure... should this be untimed or allday?
    if ( $endTime - $startTime == ( 3600 * 24 ) ) {
      // They used a DTEND set to the next day to say this is an all day
      // event.  We will call this an all day event.
      $fevent['Duration'] = '1440';
      $fevent['Untimed'] = 0;

    } else if ( $endTime - $startTime > ( 3600 * 24 ) ){
      // Event spans multiple days.  The EndTime actually represents
      // the first day the event does _not_ take place.  So,
      // we need to back up one day since WebCalendar end date is the
      // last day the event takes place.
      //$fevent['Repeat']['Frequency'] = '1'; // 1 = daily
      //$fevent['Repeat']['Interval'] = '1'; // 1 = every day
     // $fevent['Duration'] = '0';
     // $fevent['Untimed'] = 1;
     // $fevent['Repeat']['Until'] = $endTime;
    }
  }
  if ( empty ( $event['summary'] ) ) $event['summary'] = "Unnamed Event";
  $fevent['Summary'] = $event['summary'];
  if ( ! empty ( $event['description'] ) ) {
    $fevent['Description'] = $event['description'];
  } else {
    $fevent['Description'] = $event['summary'];
  }
 
  if ( ! empty ( $event['class'] ) ) {
    //Added  Confidential as new CLASS
    if (preg_match("/private/i", $event['class'])){
    $fevent['Class'] =  'R';
    } elseif (preg_match("/confidential/i", $event['class'])){
    $fevent['Class'] =  'C';
    } else {
    $fevent['Class'] =  'P';
    }
  }
 
  $fevent['UID'] = $event['uid'];
  
 //there may be many alarms, we'll parse these later
  if ( ! empty ( $event['alarm'] ) ){
   $fevent['AlarmSet'] = 1;
    $fevent['Alarm'] = $event['alarm'];
 }


  if  ( ! empty ( $event['status'] ) ) {
   switch ( $event['status'] ) {
    case 'TENTATIVE':
    //case 'NEEDS-ACTION': Sunbird sets this if you touch task without 
  //changing anything else. Not sure about other clients yet
      $fevent['Status'] = "W";
    break;
   case 'CONFIRMED':
   case 'ACCEPTED':
    $fevent['Status'] = "A";
    break;
   case 'CANCELLED';
    $fevent['Status'] = "D";
    break;
   case 'DECLINED':
    $fevent['Status'] = "R";
    break;          
   case 'COMPLETED':
    $fevent['Status'] = "C";
    break;  
   case 'IN-PROGRESS':
    $fevent['Status'] = "P";
    break;
   default:
    $fevent['Status'] = "A";
    break;        
  } //end switch
 } else {
    $fevent['Status'] = "A";
 }
 
  if  ( ! empty ( $event['location'] ) ) {
   $fevent['Location'] = $event['location']; 
 }

  if  ( ! empty ( $event['transparency'] ) ) {
    if (preg_match("/TRANSPARENT/i", $event['transparency'])  OR $event['transparency'] == 1){
     $fevent['Transparency'] = 1;
    } else {
    $fevent['Transparency'] = 0;
    } 
 } else {
    $fevent['Transparency'] = 0; 
 }
 
 
 //VTODO specific items
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
  $fevent['Repeat']['Exceptions'] = array();
  if ( ! empty ( $event['exdate'] ) && $event['exdate']) {
    $EX = explode(",", $event['exdate']);
    foreach ( $EX as $exdate ){
      $fevent['Repeat']['Exceptions'][] = icaldate_to_timestamp($exdate);
    }
    $fevent['Repeat']['Frequency'] = 7;  //manual, this can be changed later
  } // Repeating inclusions
  $fevent['Repeat']['Inclusions'] = array();
  if ( ! empty ( $event['rdate'] ) && $event['rdate']) {
    $R = explode(",", $event['rdate']);
    foreach ( $R as $rdate ){
      $fevent['Repeat']['Inclusions'][] = icaldate_to_timestamp($rdate);
    }
    $fevent['Repeat']['Frequency'] = 7;  //manual, this can be changed later
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
   //default value
    // first remove any UNTIL that may have been calculated above
    unset ( $fevent['Repeat']['Until'] );
    //split into pieces
    //echo "RRULE line: $event[rrule] <br />\n";
    $RR = explode ( ";", $event['rrule'] );

    // create an associative array of key-value pairs in $RR2[]
    for ( $i = 0; $i < count ( $RR ); $i++ ) {
      $ar = explode ( "=", $RR[$i] );
      $RR2[$ar[0]] = $ar[1];
    }
    for ( $i = 0; $i < count ( $RR ); $i++ ) {
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
          //but don't overwrite Manual setting from above
          if ( $fevent['Repeat']['Frequency'] != 7 ) $fevent['Repeat']['Frequency'] = 0;
          echo "Unsupported iCal FREQ value \"$match[1]\"<br />\n";
          //Abort this import
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
        //$fevent['Repeat']['Frequency'] = 3; //MonthlyByDay
      } else if ( preg_match ( "/^BYSETPOS=(.+)$/i", $RR[$i], $match ) ) {
        //if not already Yearly, mark as MonthlyBySetPos
        if ( $fevent['Repeat']['Frequency'] != 6 ) $fevent['Repeat']['Frequency'] = 5;
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
function rrule_repeat_days($RA) {
  $RA =  explode(",",  $RA );
  $T = count( $RA ) ;
  $sun = $mon = $tue = $wed = $thu = $fri = $sat = 'n';
  for ($i = 0; $i < $T; $i++) {
   $RADay = substr ( $RA[$i], -2, 2 );
  $RANum = ( strlen ( $RA[$i] ) > 2 ? substr ( $RA[$i], 0, strlen ( $RA[$i] ) -2 ) : 0 ) + 102;
    if ( $RADay == 'SU') {
      $sun = chr($RANum);
    } elseif ($RADay == 'MO') {
      $mon = chr($RANum);
    } elseif ($RADay == 'TU') {
      $tue = chr($RANum);
    } elseif ($RADay == 'WE') {
      $wed = chr($RANum);
    } elseif ($RADay == 'TH') {
      $thu = chr($RANum);
    } elseif ($RADay == 'FR') {
      $fri = chr($RANum);
    } elseif ($RADay == 'SA') {
      $sat = chr($RANum);
    }
  }
   return $sun.$mon.$tue.$wed.$thu.$fri.$sat;
}


// Calculate repeating ending time
function rrule_endtime($int,$interval,$start,$end) {

  // if # then we have to add the difference to the start time
  if (preg_match("/^#(.+)$/i", $end, $M)) {
    $T = $M[1] * $interval;
    $plus_d = $plus_m = $plus_y = '0';
    if ($int == '1') {
      $plus_d = $T;
    } elseif ($int == '2') {
      $plus_d = $T * 7;
    } elseif ($int == '3') {
      $plus_m = $T;
    } elseif ($int == '4') {
      $plus_m = $T;
    } elseif ($int == '5') {
      $plus_y = $T;
    } elseif ($int == '6') {
      $plus_m = $T;
    }
    $endtime = icaldate_to_timestamp($start,'', $plus_d,$plus_m,$plus_y);

  // if we have the enddate
  } else {
    $endtime = icaldate_to_timestamp($end);
  }
  return $endtime;
}
//Functions from import_vcal.php
// Parse the vcal file and return the data hash.
function parse_vcal($cal_file) {
  global $tz, $errormsg;

  $vcal_data = array();

  //echo "Parsing vcal file... <br />\n";

  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {
    // reflect the section where we are in the file:
    // VCALENDAR, TZ/DAYLIGHT, VEVENT, ALARM
    $state = "NONE";
    $substate = "none"; // reflect the sub section
    $subsubstate = ""; // reflect the sub-sub section
    $error = false;
    $line = 0;
    $event = '';

    while (!feof($fd) && !$error) {
      $line++;
      $buff = fgets($fd, 4096);
      $buff = chop($buff);

      // parser debugging code...
      //echo "line = $line <br />";
      //echo "state = $state <br />";
      //echo "substate = $substate <br />";
      //echo "subsubstate = $subsubstate <br />";
      //echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";

      if ($state == "VEVENT") {
          if ( ! empty ( $subsubstate ) ) {
            if (preg_match("/^END:(.+)$/i", $buff, $match)) {
              if ( $match[1] == $subsubstate ) {
                $subsubstate = '';
              }
            } else if ( $subsubstate == "VALARM" && 
              preg_match ( "/TRIGGER:(.+)$/i", $buff, $match ) ) {
  //echo "Set reminder to $match[1]<br />";
  //reminder time is $match[1]
            }
          }
          else if (preg_match("/^BEGIN:(.+)$/i", $buff, $match)) {
            $subsubstate = $match[1];
          }
           // we suppose ":" is on the same line as property name, this can perhaps cause problems
   else if (preg_match("/^SUMMARY.*:(.+)$/i", $buff, $match)) {
              $substate = "summary";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION:(.+)$/i", $buff, $match)) {
              $substate = "description";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION;ENCODING=QUOTED-PRINTABLE:(.+)$/i", $buff, $match)) {
//              $substate = "description";
//              $event[$substate] = quoted_printable_decode ( $match[1] );
              $substate = "descriptionqp";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CLASS.*:(.+)$/i", $buff, $match)) {
              $substate = "class";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY.*:(.+)$/i", $buff, $match)) {
              $substate = "priority";
              $event[$substate] = $match[1];
         } elseif (preg_match("/^DTSTART.*:(.+)$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTEND.*:(.+)$/i", $buff, $match)) {
              $substate = "dtend";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^RRULE.*:(.+)$/i", $buff, $match)) {
              $substate = "rrule";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^EXDATE.*:(.+)$/i", $buff, $match)) {
              $substate = "exdate";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DALARM.*:(.+)$/i", $buff, $match)) {
              $substate = "dalarm";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CATEGORIES.*:(.+)$/i", $buff, $match)) {
              $substate = "categories";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^UID.*:(.+)$/i", $buff, $match)) {
              $substate = "uid";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^END:VEVENT$/i", $buff, $match)) {
              $state = "VCALENDAR";
              $substate = "none";
              $subsubstate = '';
       if ($tmp_data = format_vcal($event)) $vcal_data[] = $tmp_data;
              // clear out data for new event
              $event = '';

   // TODO: QUOTED-PRINTABLE descriptions

   // folded lines
          } elseif (preg_match("/^[ ]{1}(.+)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
                  $errormsg .= "Error in file $cal_file line $line:<br />$buff\n";
                  $error = true;
              }
          // For unsupported properties
   } else {
            $substate = "none";
          }
      } elseif ($state == "VCALENDAR") {
          if (preg_match("/^TZ.*:(.+)$/i", $buff, $match)) {
            $event['tz'] = $match[1];
          } elseif (preg_match("/^DAYLIGHT.*:(.+)$/i", $buff, $match)) {
            $event['daylight'] = $match[1];
          } elseif (preg_match("/^BEGIN:VEVENT$/i", $buff)) {
            $state = "VEVENT";
          } elseif (preg_match("/^END:VCALENDAR$/i", $buff)) {
            $state = "NONE";
          }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
           $state = "VCALENDAR";
         else if (preg_match("/^BEGIN:ALARM$/i", $buff))
           $state = "ALARM";
      }
    } //End while
    fclose($fd);
  }

  return $vcal_data;
}

// Convert vcal format (yyyymmddThhmmssZ) to epoch time
function vcaldate_to_timestamp($vdate,$plus_d = '0',$plus_m = '0', $plus_y = '0') {
  global $TZoffset;

  $y = substr($vdate, 0, 4) + $plus_y;
  $m = substr($vdate, 4, 2) + $plus_m;
  $d = substr($vdate, 6, 2) + $plus_d;
  $H = substr($vdate, 9, 2);
  $M = substr($vdate, 11, 2);
  $S = substr($vdate, 13, 2);
  $Z = substr($vdate, 15, 1);
  if ($Z == 'Z') {
    $TS = gmmktime($H,$M,$S,$m,$d,$y);
  } else {
    // Problem here if server in different timezone
    $TS = mktime($H,$M,$S,$m,$d,$y);
  }

  return $TS;
}

// Put all vcal data into import hash structure
function format_vcal($event) {
  // Start and end time
  $fevent['StartTime'] = vcaldate_to_timestamp($event['dtstart']);
  if ($fevent['StartTime'] == '-1') return false;
  $fevent['EndTime'] = vcaldate_to_timestamp($event['dtend']);

  // Calculate duration in minutes
  $fevent['Duration']           = ($fevent['EndTime'] - $fevent['StartTime']) / 60;
  if ($fevent['Duration'] == '1440') { $fevent['Duration'] = '0'; $fevent['Untimed'] = 1; } //All day (untimed)

  if (! empty($event['summary'])) $fevent['Summary'] = $event['summary'];
  if (! empty($event['description'])) $fevent['Description'] = $event['description'];
  if (! empty($event['descriptionqp'])) {
    $fevent['Description'] = quoted_printable_decode ( $event['descriptionqp'] );
    
    // hack for mozilla sunbird's extra = signs
    $fevent['Description'] = preg_replace('/^=/', '', $fevent['Description']);
    $fevent['Description'] = str_replace("\n=", "\n", $fevent['Description']);
  }

  if ( ! empty ( $event['class'] ) ) {
    //Added  Confidential as new CLASS
    if (preg_match("/private/i", $event['class'])){
    $fevent['Class'] =  'R';
    } elseif (preg_match("/confidential/i", $event['class'])){
    $fevent['Class'] =  'C';
    } else {
    $fevent['Class'] =  'P';
    }
  }
 
 
  if (! empty($fevent['UID'])) $fevent['UID'] = $event['uid'];

  // Repeats
  //
  // vcal 1.0 repeats can be very complicated and the webcalendar doesn't
  // actually support all of the ways repeats can be specified.  We will
  // focus on vcals dumped from Palm Desktop and Lotus Notes, which are simple
  // and the ones webcalendar should fully support.
  if (! empty($event['rrule'])) {
    //split into pieces
    $RR = explode(" ", $event['rrule']);

    if (preg_match("/^D(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '1';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif (preg_match("/^W(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '2';
      $fevent['Repeat']['Interval'] = $match[1];
      $fevent['Repeat']['ByDay'] = rrule_repeat_days($RR);
    } elseif (preg_match("/^MP(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '3';
      $fevent['Repeat']['Interval'] = $match[1];
      if ($RR[1] == '5+') {
        $fevent['Repeat']['Frequency'] = '3';
      }
    } elseif (preg_match("/^MD(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '4';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif (preg_match("/^YM(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '6';
      $fevent['Repeat']['Interval'] = $match[1];
    } elseif (preg_match("/^YD(.+)$/i", $RR[0], $match)) {
      $fevent['Repeat']['Frequency'] = '6';
      $fevent['Repeat']['Interval'] = $match[1];
    }

    $end = end($RR);

    // No end in Palm is 12-31-2031
    if (($end != '20311231') && ($end != '#0')) {
      $fevent['Repeat']['Until'] = rrule_endtime($fevent['Repeat']['Frequency'],$fevent['Repeat']['Interval'],$event['dtstart'],$end);
    }

    // Repeating exceptions?
    if (!empty($event['exdate'])) {
      $fevent['Repeat']['Exceptions'] = array();
      $EX = explode(",", $event['exdate']);
      foreach ( $EX as $exdate ){
        $fevent['Repeat']['Exceptions'][] = vcaldate_to_timestamp($exdate);
      }
    }
  } // end if rrule

// TODO
//  $fevent[Category];
  return $fevent;
}

function get_categories_id_byname ( $cat_names) {
  global $login, $IMPORT_CATEGORIES;
 $categories = explode (",", $cat_names );
 foreach ( $categories as $cat_name ) {
  $res = dbi_query ( "SELECT cat_id FROM webcal_categories WHERE " .
   "cat_name  = '" . $cat_name . "' AND " . 
   "(cat_owner = '" . $login . "' OR cat_owner IS NULL )");
  if ( $res ) {
   if ( $row = dbi_fetch_row ( $res ) ) {
    $ret[] = $row[0];
    dbi_free_result ( $res );
   } else if ( ! empty ( $IMPORT_CATEGORIES )&& $IMPORT_CATEGORIES == "Y"){ 
     //Need to insert new Category
    $res = dbi_query ( "SELECT MAX(cat_id) FROM webcal_categories" );
    if ( $res ) {
     $row = dbi_fetch_row ( $res );
     $id = $row[0] + 1;
     dbi_free_result ( $res );
     $sql = "INSERT INTO webcal_categories " .
      "( cat_id, cat_owner, cat_name ) " .
      "VALUES ( $id, '$login', '$cat_name' )";
     if ( ! dbi_query ( $sql ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
      do_debug ( $error );
     } else {
      $ret[] = $id;
     }
    } //end if $res
   } else { //skip adding Categories
     $ret = '';
    
   }   // end if row
  } else { //no res
   $error = translate("Database error") . ": " . dbi_error ();
   do_debug ( $error );
  }
 } //end foreach
  return $ret;
}

// Generate the FREEBUSY line of text for a single event
function fb_export_time ( $date, $duration, $time, $texport ) {
  $ret = '';
  $allday = ( $time == -1 || $duration == 24 * 60 );
  $year = (int) substr ( $date, 0, -4 );
  $month = (int) substr ( $date,- 4, 2 );
  $day = (int) substr ( $date, -2, 2 );

  //No time, or an "All day" event"
  if ( $allday ) {
    // untimed event - consider this to not be busy
  } else {
    // normal/timed event (or all-day event)
    $hour = (int) substr ( $time, 0,-4 );
    $min = (int) substr ( $time, -4, 2 );
    $sec = (int) substr ( $time, -2, 2 );
    $duration = $duration * 60;

    $start_tmstamp = mktime ( $hour, $min, $sec, $month, $day, $year );

    $utc_start = export_get_utc_date ( $date, $time );

    $end_tmstamp = $start_tmstamp + $duration;
    $utc_end = export_get_utc_date ( date ( "Ymd", $end_tmstamp ),
      date ( "His", $end_tmstamp ) );
    $ret .= "FREEBUSY:$utc_start/$utc_end\r\n";
  }
  return $ret;
}

/**
 * Generate VTIMEZONE element
 *
 * $parm int  $timestamp Date containing year that this 
 *   VTIMEZONE element will describe (Unix timestamp)
 *
 * Example
 * BEGIN:VTIMEZONE
 * TZID:Eastern Time (US & Canada)
 * BEGIN:STANDARD
 * DTSTART:20041031T020000
 * RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
 * TZOFFSETFROM:-0400
 * TZOFFSETTO:-0500
 * TZNAME:Standard Time
 * END:STANDARD
 * BEGIN:DAYLIGHT
 * DTSTART:20050403T020000
 * RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=4
 * TZOFFSETFROM:-0500
 * TZOFFSETTO:-0400
 * TZNAME:Daylight Savings Time
 * END:DAYLIGHT
 * END:VTIMEZONE
 *
 */
function export_vtimezone( $timestamp ) {
   global $TIMEZONE;

  
  $tz_info = array();
  //Get TZID value
  $tzid = $TIMEZONE; //default value
  //see if a short version is available
  //found out many apps simply use the Olsen names...comment out for now
  //$res = dbi_query ( "SELECT tz_list_text " .
   //  "FROM webcal_tz_list WHERE tz_list_name = '$TIMEZONE'" );
  //if ( $res ) {
  //  $row = dbi_fetch_row ( $res );
  // if ( $row ) {
   //    $tzid = substr ( $row[0], strpos ( $row[0], ")", 1) +3);
  // }
  //}
  $GLOBALS['TZID']  = ( ! empty ( $tzid) ? $tzid : "" );
  $tz_info = get_tz_info ( $timestamp );
   //print_r ($tz_info);
  echo "BEGIN:VTIMEZONE\r\n";
  echo "TZID:" .$tzid . "\r\n";
  foreach ( $tz_info as $tz_data) {
   if ( $tz_data['rule_save'] == 0 ) {
    echo "BEGIN:STANDARD\r\n";
    echo "DTSTART:" . $tz_data['start'] . "\r\n";
       if ( ! empty ($tz_data['rrule'] ) ) {
         $name = $tz_data['rrule'];
         $array = export_fold_lines($name,"utf8");
         while (list($key,$value) = each($array))
           echo "$value\r\n";    
    }
    echo "TZOFFSETFROM:" . $tz_data['offsetfrom'] . "\r\n";
    echo "TZOFFSETTO:" . $tz_data['offsetto'] . "\r\n";
    echo "TZNAME:" . $tz_data['name'] . "\r\n";
    echo "END:STANDARD\r\n";
   } else {
    echo "BEGIN:DAYLIGHT\r\n";
    echo "DTSTART:" . $tz_data['start'] . "\r\n";
       if ( ! empty ($tz_data['rrule'] ) ) {
         $name = $tz_data['rrule'];
         $array = export_fold_lines($name,"utf8");
         while (list($key,$value) = each($array))
           echo "$value\r\n";    
    }    
    echo "TZOFFSETFROM:" . $tz_data['offsetfrom'] . "\r\n";
    echo "TZOFFSETTO:" . $tz_data['offsetto'] . "\r\n";
    echo "TZNAME:" . $tz_data['name'] . "\r\n";
    echo "END:DAYLIGHT\r\n";
   }
  }
  echo "END:VTIMEZONE\r\n";
}

/**
 * Return TIMEZONE info for a given year
 *  this function is very similar to get_tz_time in functions.php
 *
 * @param timestamp  $timestamp   UNIX format
 *
 * @return array
 *  dst_results['name']      = abbreviated name of TZ
 *  dst_results['timestamp'] = UNIX timestamp of converted time/date
 */ 
function get_tz_info ( $timestamp ) {
  global $TIMEZONE;
  $sql = "SELECT  zone_rules, zone_gmtoff, zone_format " . 
    " FROM webcal_tz_zones WHERE zone_name  = '" . trim( $TIMEZONE ) . "' " .
    " AND zone_from <= $timestamp AND zone_until >= $timestamp";
  $res = dbi_query (  $sql );
  $dst_rules = array ();
  $dst_results = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ($row[0] ) )  {// Zone rules apply
        $dst_rules = get_vtimezone_rules ( $row[0], $timestamp );
         $i=0;
     foreach ( $dst_rules as $dst_rule ) {
      if ( $dst_rule['rule_save'] > 0 ) $current_save = $dst_rule['rule_save'];
            $rule_save = $dst_rule['rule_save'];
       if ( $dst_rule['rule_save'] == 0 ) { //going to Standard Time
           $start = str_replace ( ":", "T", date ( "Ymd:His", $dst_rule['rule_date']  ) );
       $rrule = $dst_rule['rule_rrule'];
       $offsetfrom = seconds_to_hhmm ( $row[1] + $current_save);  
       $offsetto = seconds_to_hhmm ( $row[1] );
       $name = str_replace ( "%s", $dst_rule['rule_letter'], $row[2] );    
            } else {         
       $start = str_replace ( ":", "T", date ( "Ymd:His", $dst_rule['rule_date'] ) );
       $rrule = $dst_rule['rule_rrule'];
       $offsetto = seconds_to_hhmm ( $row[1] + $current_save );
       $offsetfrom = seconds_to_hhmm ( $row[1]  );
       $name = str_replace ( "%s", $dst_rule['rule_letter'], $row[2] );
         }
      
      $dst_results[$i] = array (
        "rule_save" => $rule_save,
        "start" => $start,
        "rrule" => $rrule,
        "offsetfrom" => $offsetfrom,
        "offsetto" => $offsetto,
        "name" => $name,
             );
             $i++;
     } //end foreach
   }
     dbi_free_result ( $res );
   //print_r ( $row);
  // print_r ( $dst_results);
     return $dst_results;
   }
 }

}

/**
 * Return the timezone rules for a given zone rule
 * copied from functions.php so changes can be made as needed
 * @param string $zone_rule   
 * @param int $timestamp  UNIX timestamp of requested rule
 *
 * $global array $days_of_week Sun => 0...Sat => 6
 * @return array   
 *   dst_rules[0]['rule_date']   = first time change timestamp
 *   dst_rules[0]['rule_save']   = first time savings in seconds
 *   dst_rules[0]['rule_letter'] = first letter to apply to TZ abbreviation
 *   dst_rules[1]['rule_date']   = second time change timestamp
 *   dst_rules[1]['rule_save']   = second time savings in seconds
 *   dst_rules[1]['rule_letter'] = second letter to apply to TZ abbreviation
 *   dst_rules['lastyear']       = last year time savings in seconds
 *   dst_rules['lastletter']     - last year letter to apply to TZ abbreviation 
 */
function get_vtimezone_rules ( $zone_rule, $timestamp  ) {
 global $days_of_week;

 $year = date ("Y", $timestamp );

 $sql = "SELECT rule_from, rule_to, rule_in, rule_on, rule_at, rule_save, rule_letter, rule_at_suffix  " . 
   "FROM webcal_tz_rules " .
  " WHERE rule_name  = '" . $zone_rule  ."'"  . 
   " AND rule_to >= $year " .
  " ORDER BY rule_to DESC, rule_save DESC, rule_from  ";
  
  $res = dbi_query ( $sql );

  $dst_rules = array();
  $i = $row_cnt = 0;
  if ( $res ) {

    while ( $row = dbi_fetch_row ( $res ) ) {
    $row_cnt++;
   $bymonthday = array();
   $year1 = $row[0];
   if ( $year1 < 1970 ) $year1 = 1970;
   $yearL = $row[1];
   if ( $yearL > 2037 ) $yearL = 2037;
      if ( substr ( $row[3], 0, 4 ) == "last" ) {
          $lastday = date ( "w", mktime ( 0, 0, 0, $row[2] + 1 , 0, $year ) );
          $offset = -( ( $lastday +7 - $days_of_week[substr($row[3], 4, 3)]) % 7);
          $changeday = mktime ( 0, 0, 0, $row[2] + 1, $offset, $year1 ) + $row[4];
          $rrule = "RRULE:FREQ=YEARLY;BYMONTH=" .$row[2] . ";BYDAY=-1" . 
       strtoupper ( substr( $row[3],4,2));
     } else if ( substr ( $row[3], 3, 2 ) == "<="  OR substr ( $row[3], 3, 2 ) == ">=") {
          $rule_day = substr( $row[3], 5, strlen( $row[3]) -5);
          $givenday = date ( "w", mktime ( 0, 0, 0, $row[2] , $rule_day, $year ) );
          if ( substr ( $row[3], 3, 2) == "<=" ) {
            $offset = -( ( $givenday  + 7  - $days_of_week[ substr( $row[3], 0, 3)]) % 7);            
        for ($j= $rule_day -6; $j<=$rule_day;$j++) {
          if($j <1) $j =1;
          $bymonthday[] = $j;
       }
       sort ($bymonthday );
         $rrule = "RRULE:FREQ=YEARLY;BYMONTH=" .$row[2] . ";BYDAY=" . 
            strtoupper ( substr( $row[3],0,2)). ";BYMONTHDAY=" . implode(",",$bymonthday);
      
          } else {
            $offset = ( ( $days_of_week[ substr( $row[3], 0, 3)] + 7 - $givenday   ) % 7);
            $dim = date ( "t", mktime ( 0,0,0,$row[2], 1, $year)); //days in month
            if ( $rule_day <= 6 ) {
         $rrule = "RRULE:FREQ=YEARLY;BYMONTH=" .$row[2] . ";BYDAY=" . 
         strtoupper ( substr( $row[3],0,2));         
      } else {
        for ($j= $rule_day +6; $j>=$rule_day;$j--) {
          if($j > $dim) $j = $dim;
          $bymonthday[] = $j;
        }
          sort ($bymonthday );
         $rrule = "RRULE:FREQ=YEARLY;BYMONTH=" .$row[2] . ";BYDAY=" . 
            strtoupper ( substr( $row[3],0,2)). ";BYMONTHDAY=" . implode(",",$bymonthday);       
      } 
          }
          $changeday = mktime (  0, 0, 0, $row[2] , $rule_day + $offset, $year1 ) + $row[4];
      } else {
     $offset = 0;
        $changeday = mktime (  0, 0, 0, $row[2] , $row[3], $year1 ) + $row[4];
        $rrule = "RRULE:FREQ=YEARLY;BYMONTH=" .$row[2] . ";BYMONTHDAY=" . $row[3];
      }  
      //delete rrule if only one year in rule
   if ( $row[0] == $row[1] ) {
     $rrule = '';
    //add UNTIL only if more than 2 definitions
   } else if ( $row_cnt  > 2 ) {
        $rrule .= ";UNTIL=" . str_replace ( ":", "T", 
       date ( "Ymd:His", gmmktime(0,0,0,date("m",$changeday ),date("d",$changeday)-1, $yearL) ) ). "Z";
     }
      $dst_rules[$i] = array (
       "rule_date" => $changeday,
       "rule_rrule" => $rrule,
       "rule_month" => $row[2],
       "rule_day" => $row[3],
       "rule_save" => $row[5],
       "rule_letter" => $row[6]
      );
      $i++;
    }
    dbi_free_result ( $res );
  }
// print_r ( $dst_rules);
  return $dst_rules;
}

function seconds_to_hhmm ( $seconds ) {
   
  $neg =  ( $seconds < 0 )? true : false;
  $seconds = abs ( $seconds);
  $m = $seconds;
   $h = ( $m / 3600 );
   $m = ( $h  - (int) $h ) * 60;
   $h = (int) $h; 
   $ret = sprintf ( "%02d%02d", $h, $m );
  if ( $neg ) $ret = "-" . $ret;
  return $ret;
}
?>

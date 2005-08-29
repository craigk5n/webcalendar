<?php
/*
 * $Id$
 *
 * Description:
 *	Handler for exporting webcalendar events to various formats.
 *
 * Comments:
 *	All-day events and untimed events are treated differently.  An
 *	all-day event is a 12am event with duration 24 hours.  We store
 *	untimed events with a start time of -1 in the webcalendar database.
 *
 *********************************************************************/
include_once 'includes/init.php';

if ( ! empty ( $PROGRAM_VERSION ) ) {
  $prodid = "PRODID:-//WebCalendar-$PROGRAM_VERSION";
} else if ( preg_match ( "/v(\d\S+) /", $GLOBALS['PROGRAM_NAME'], $matches ) ) {
  $prodid = "PRODID:-//WebCalendar-$matches[1]";
} else {
  $prodid = "PRODID:-//WebCalendar-UnknownVersion";
}

if ( empty ( $user ) || $user == $login )
  load_user_layers ();

function export_get_event_entry($id) {
  global $use_all_dates, $include_layers, $fromyear,$frommonth,$fromday,
    $endyear,$endmonth,$endday,$modyear,$modmonth,$modday,$login;
  global $DISPLAY_UNAPPROVED, $layers;

  // We export repeating events only with the pilot-datebook CSV format
  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name " .
    ", webcal_entry.cal_priority, webcal_entry.cal_date " .
    ", webcal_entry.cal_time " .
    ", webcal_entry_user.cal_status, webcal_entry.cal_create_by " .
    ", webcal_entry.cal_access, webcal_entry.cal_duration " .
    ", webcal_entry.cal_description " .
    ", webcal_entry_user.cal_category " .
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
  $start_encode = 0; // we start encoding only after the ":" caracter is encountered

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
	    $fold--; //reduce row length of 1 to take into account the whitespace at the beginning of lines

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
	  $attendee[$count] .=	($row[2] == $user[3]) ? "OWNER;" : "ATTENDEE;";
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

function export_time($date, $duration, $time, $texport) {
  $year = (int) substr($date,0,-4);
  $month = (int) substr($date,-4,2);
  $day = (int) substr($date,-2,2);

  if ( $time == -1 ) {
      // untimed event
      $hour = 0;
      $min = 0;
      $sec = 0;
      $duration = 24 * 60 * 60;
      $str_duration = "1D";

      $start_tmstamp = mktime($hour, $min, $sec, $month, $day, $year);

      $start_date = date("Ymd", $start_tmstamp);
      echo "DTSTART;VALUE=DATE:$start_date\r\n";
  } else {
      // timed event or all-day event
      $hour = (int) substr($time,0,-4);
      $min = (int) substr($time,-4,2);
      $sec = (int) substr($time,-2,2);
      $str_duration = "T" . $duration . "M";
      $duration = $duration * 60;

      $start_tmstamp = mktime($hour, $min, $sec, $month, $day, $year);

      $utc_start = export_get_utc_date($date, $time);
      echo "DTSTART:$utc_start\r\n";
  }

  if (strcmp($texport,"ical") == 0) {
      $utc_dtstamp = export_get_utc_date(date("Ymd", mktime()),
        date("His", mktime()));
      echo "DTSTAMP:$utc_dtstamp\r\n";

	if ($time == -1) {
          // untimed event
	  $end_tmstamp = $start_tmstamp + 24*60*60;
	  $utc_end = date("Ymd", $end_tmstamp);
	  echo "DTEND;VALUE=DATE:$utc_end\r\n";
	} else {
	  $end_tmstamp = $start_tmstamp + $duration;
	  $utc_end = export_get_utc_date(date("Ymd", $end_tmstamp), date("His", $end_tmstamp));
	  echo "DTEND:$utc_end\r\n";
	}
  } elseif (strcmp($texport,"vcal") == 0) {
	if ($time == -1) {
	  $end_tmstamp = $start_tmstamp + 24*60*60;
	  $utc_end = date("Ymd", $end_tmstamp);
	  echo "DTEND:$utc_end\r\n";
	} else {
	  $end_tmstamp = $start_tmstamp + $duration;
	  $utc_end = export_get_utc_date(date("Ymd", $end_tmstamp), date("His", $end_tmstamp));
	  echo "DTEND:$utc_end\r\n";
	}
  } else {
      echo "DURATION:P$str_duration\r\n";
  }
}

function export_recurrence_ical($id, $date) {
  $sql = "SELECT cal_date FROM webcal_entry_repeats_not WHERE cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
      $exdate = array();
      $i = 0;
	while ($row = dbi_fetch_row($res)) {
	  $exdate[$i] = $row[0];
	  $i++;
	}
  }

  dbi_free_result($res);

  $sql = "SELECT webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
    . "webcal_entry_repeats.cal_frequency, webcal_entry_repeats.cal_days, webcal_entry.cal_time"
    . " FROM webcal_entry, webcal_entry_repeats WHERE webcal_entry_repeats.cal_id = '$id'"
    . "AND webcal_entry.cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res)
    $row = dbi_fetch_row($res);

  if ($row) {
      $type = $row[0];
      $end = $row[1];
      $freq = $row[2];
      $day = $row[3];
      $time = $row[4];
      $str_day = array( 'SU','MO','TU','WE','TH','FR','SA' );
      $byday = "";

      echo "RRULE:";

      /* recurrence frequency */
      switch ($type) {
	case 'daily' :
	  echo "FREQ=DAILY";
	  break;
	case 'weekly' :
	  echo "FREQ=WEEKLY";
	  break;
	case 'monthlyByDay':
	case 'monthlyByDate' :
	  echo "FREQ=MONTHLY";
	  break;
	case 'yearly' :
	  echo "FREQ=YEARLY";
	  break;
      }

      echo ";INTERVAL=$freq";

      if ($type == "weekly") {
	  if ($day != "nnnnnnn") {
	      echo ";BYDAY=";
		for ($i = 0; $i < strlen($day); $i++) {
		  if ($day[$i] == 'y') {
		      $byday .= $str_day[$i] .",";
		  }
		}
	      $byday = substr($byday, 0, strlen($byday)-1); // suppress last ','
	      echo $byday;
	  }
      } elseif ($type == "monthlyByDate") {
	  $day = (int) substr($date,-2,2);

	  echo ";BYMONTHDAY=$day";
      } elseif ($type == "monthlyByDay") {
	  echo ";BYDAY=";

	  $year = (int) substr($date,0,-4);
	  $month = (int) substr($date,-4,2);
	  $day = (int) substr($date,-2,2);

	  $stamp = mktime(0, 0, 0, $month, $day, $year);

	  $date_array = getdate($stamp);

	  echo $str_day[$date_array['wday']];

	  $next_stamp = $stamp + 7 * 24 * 60 * 60;

	  $next_date_array = getdate($next_stamp);

	  if ($date_array['mon'] != $next_date_array['mon'])
	    $pos = -1;
	  else {
	      $pos = (int) ($day / 7);

	      if (($day % 7) > 0) {
		  $pos++;
	      }
	   }
	  echo ";BYSETPOS=$pos";
      }

      if (!empty($end))	{
	  echo ";UNTIL=";

	  $utc = export_get_utc_date($end, $time);

	  echo $utc;
      }

      echo "\r\n";

      if (count($exdate) > 0) {
	  $string = "EXDATE:";
	  $i = 0;
	  while ($i < count($exdate)) {
	      $date = export_get_utc_date($exdate[$i],$time);
	      $string .= "$date,";
	      $i++;
	  }

	  $string = substr($string, 0, strlen($string)-1); // suppress last ','

	  $string = export_fold_lines($string);

	  while (list($key,$value) = each($string))
	    echo "$value\r\n";
      }
    }
}

function export_recurrence_vcal($id, $date) {
  $sql = "SELECT cal_date FROM webcal_entry_repeats_not WHERE cal_id = '$id'";
  $res = dbi_query($sql);

  if ($res) {
    $exdate = array();
    while ($row = dbi_fetch_row($res)) {
      $exdate[] = $row[0];
    }
  }

  dbi_free_result($res);

  $sql = "SELECT webcal_entry_repeats.cal_type, webcal_entry_repeats.cal_end, "
    . "webcal_entry_repeats.cal_frequency, webcal_entry_repeats.cal_days, webcal_entry.cal_time"
    . " FROM webcal_entry, webcal_entry_repeats WHERE webcal_entry_repeats.cal_id = '$id'"
    . "AND webcal_entry.cal_id = '$id'";

  $res = dbi_query($sql);
  $row = dbi_fetch_row($res);

  //echo $sql;exit;

  if ($row) {
      $type = $row[0];
      $end = $row[1];
      $freq = $row[2];
      $day = $row[3];
      $time = $row[4];
      $str_day = array('SU','MO','TU','WE','TH','FR','SA');
      $byday = "";

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
	case 'monthlyByDayR':
	  echo "MP";
	  break;
	case 'monthlyByDate' :
	  echo "MD";
	  break;
	case 'yearly' :
	  echo "YM";
	  break;
      }

      echo $freq." ";

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
	} elseif (($type == "monthlyByDay") || ($type == "monthlyByDayR")) {
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
	  //echo export_get_utc_date($end, $time);
	  echo $end;
	} else {
	  echo "20031231";
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


/*
 * Create a date-time format (e.g. "20041130T123000Z") that is
 * converted from local timezone to GMT.
 */
function export_get_utc_date($date, $time=0) {
  $year = (int) substr($date,0,-4);
  $month = (int) substr($date,-4,2);
  $day = (int) substr($date,-2,2);

  if ($time <= 0) {
      $hour = 0;
      $min = 0;
      $sec = 0;
  } else {
      $hour = (int) substr($time,0,-4);
      $min = (int) substr($time,-4,2);
      $sec = (int) substr($time,-2,2);
  }

  $tmstamp = mktime($hour, $min, $sec, $month, $day, $year);

  $utc_date = gmdate("Ymd", $tmstamp);
  $utc_hour = gmdate("His", $tmstamp);

  $utc = sprintf ("%sT%sZ", $utc_date, $utc_hour);

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


function export_alarm_ical($id, $description) {
  $sql = "SELECT cal_data FROM webcal_site_extras " .
         "WHERE cal_id = $id AND cal_type = 7 AND cal_remind = 1";
  $res = dbi_query ( $sql );
  $row = dbi_fetch_row ( $res );
  dbi_free_result ( $res );

  if ($row) {
    echo "BEGIN:VALARM\r\n";
    echo "TRIGGER:-PT".$row[0]."M\r\n";
    echo "ACTION:DISPLAY\r\n";

    $array = export_fold_lines($description,"utf8");
    while (list($key,$value) = each($array)) {
      echo "$value\r\n";
    }

    echo "END:VALARM\r\n";
  }
}


function generate_uid() {
  $rand = mt_rand(1000000,9999999);

  $utc_id_date = gmdate("Ymd", mktime());
  $utc_id_hour = gmdate("His", mktime());
  $pid = getmypid() or die ("System error");
  $host = getenv("SERVER_NAME");

  return sprintf ("%sT%sZ-%d-%d@%s", $utc_id_date, $utc_id_hour, $rand, $pid, $host);
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

      /* CLASS either "PRIVATE" or "PUBLIC" (the default) */
      if ($access == "R") {
	  echo "CLASS:PRIVATE\r\n";
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

function export_ical ($id) {
  global $prodid;

  header ( "Content-Type: text/calendar" );
  //header ( "Content-Type: text/plain" );

  $res = export_get_event_entry($id);

  $entry_array = array();
  $count = 0;

  while ( $entry = dbi_fetch_row($res) ) {
      $entry_array[$count++] = $entry;
  }

  if ($count > 0) {
    echo "BEGIN:VCALENDAR\r\n";
    echo "$prodid\r\n";
    echo "VERSION:2.0\r\n";
    echo "METHOD:PUBLISH\r\n";
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
      $array = export_fold_lines("UID:$export_uid");
      while (list($key,$value) = each($array))
	echo "$value\r\n";

      $name = preg_replace("/\r/", "", $name);
      //$name = preg_replace("/\n/", "\\n", $name); //PER RFC2445
      $name = preg_replace("/\\\\/", "\\\\\\", $name); // ??
      $description = preg_replace("/\r/", "", $description);
      //$description = preg_replace("/\n/", "\\n", $description); //PER RFC2445
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

      /* CLASS either "PRIVATE" or "PUBLIC" (the default) */
      if ($access == "R") {
	  echo "CLASS:PRIVATE\r\n";
      } else {
	  echo "CLASS:PUBLIC\r\n";
      }

      // ATTENDEE of the event
      $attendee = export_get_attendee($row[0], "ical");

      for ($i = 0; $i < count($attendee); $i++) {
	  $attendee[$i] = export_fold_lines($attendee[$i],"utf8");
	  while (list($key,$value) = each($attendee[$i]))
	    echo "$value\r\n";
      }

      /* Time - all times are utc */
      export_time($date, $duration, $time, "ical");

      /* Recurrence */
      export_recurrence_ical($uid, $date);

      // FIXME: handle alarms
      export_alarm_ical($uid, $description);

      /* Goodbye event */
      echo "END:VEVENT\r\n";
    } //end while (list($key,$row) = each($entry_array))

  if ($count > 0)
    echo "END:VCALENDAR\r\n";
} //end function


// convert time in ("hhmmss") format, plus duration (as a number of
// minutes), to end time ($hour = number of hours, $min = number of
// minutes).
// FIXME: doesn't handle wrap to next day correctly.
function get_end_time ( $time, $duration, &$hour, &$min) {
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $hour = $minutes / 60;
  $min = $minutes % 60;
}

// convert calendar date to a format suitable for the install-datebook
// utility (part of pilot-link)
function pilot_date_time ( $date, $time, $duration, $csv=false ) {
  $year = (int) ( $date / 10000 );
  $month = (int) ( $date / 100 ) % 100;
  $mday = $date % 100;
  get_end_time ( $time, $duration, $hour, $min );

  // Assume that the user is in the same timezone as server
  $tz_offset = date ( "Z" ); // in seconds
  $tzh = (int) ( $tz_offset / 3600 );
  $tzm = (int) ( $tz_offset / 60 ) % 60;
  if ( $tzh < 0 ) {
    $tzsign = "-";
    $tzh = abs ( $tzh );
  } else
    $tzsign = "+";

  if ( $csv )
    return sprintf ( "%04d-%02d-%02d%s%02d:%02d:00",
		     $year, $month, $mday, $csv, $hour, $min );
  else
    return sprintf ( "%04d/%02d/%02d %02d%02d  GMT%s%d%02d",
		     $year, $month, $mday, $hour, $min, $tzsign, $tzh, $tzm );
} //end function

function export_install_datebook ($id) {
  $res = export_get_event_entry($id);

  while ( $row = dbi_fetch_row ( $res ) ) {
    $start_time = pilot_date_time ( $row[3], $row[4], 0 );
    $end_time = pilot_date_time ( $row[3], $row[4], $row[8] );
    printf ( "%s\t%s\t\t%s\n",
	     $start_time, $end_time, $row[1] );
    echo "Start time: $start_time\n";
    echo "End time: $end_time\n";
    echo "Duration: $row[8]\n";
    echo "Name: $row[1]\n";
  }
} //end function

function get_cal_ent_extras($id, $from, $where = false) {
  $res = dbi_query( "SELECT * FROM $from WHERE cal_id='$id'".	( $where?"AND ( $where );":';') );
  if ( $res )
    return ( dbi_fetch_row($res) );
  else
    return ( false );
} //end function

function export_pilot_csv ($id) {
  /* to be imported to a Palm with:
   *		pilot-datebook -r csv -f webcalendar-export.txt -w hotsync
   */

  $res = export_get_event_entry($id);

  echo "uid,attributes,category,untimed,beginDate,beginTime,endDate,endTime,description,note,alarm,advance,advanceUnit,repeatType,repeatForever,repeatEnd,repeatFrequency,repeatDay,repeatWeekdays,repeatWeekstart\n";
  while ( $row = dbi_fetch_row ( $res ) ) {
    // uid (long)
    echo $row[0], ',';
    // attributes (int)
    //  128 = 0x80 : Deleted
    //   64 = 0x40 : Dirty
    //   32 = 0x20 : Busy
    //   16 = 0x10 : Secret/Private
    echo ($row[7] == 'R')?'16,':'0,';
    // category (int: 0=Unfiled)
    echo '0,';
    // untimed (int: 0=Appointment, 1=Untimed)
    // note: Palm "Untimed" is WebCalendar "AllDay"
    if ( $row[4] < 0 ) {
      echo
	'1,',				// untimed
	substr($row[3],0,4), '-',	// beginDate (str: YYYY-MM-DD) + beginTime
	substr($row[3],4,2), '-',
	substr($row[3],6,2), ',00:00:00,',
	substr($row[3],0,4), '-',	// endDate + endTime
	substr($row[3],4,2), '-',
	substr($row[3],6,2), ',00:00:00,';
    } else {
      echo '0,', // untimed
	pilot_date_time($row[3], $row[4], 0, ','), ',',	// beginDate,beginTime
	pilot_date_time($row[3], $row[4], $row[8], ','), ',';	//endDate,endTime
    } //end if ( $row[4] < 0 )
    // description (str)
    echo '"', preg_replace("/\x0D?\n/", "\\n", $row[1]), '",';
    // note (str)
    echo '"', preg_replace("/\x0D?\n/", "\\n", $row[9]), '",';
    // alarm, advance, advanceUnit
    // alarm (int: 0=no alarm, 1=alarm)
    // FIXME: verify if WebCal. DB interpreted correctly
    // advance (int), advanceUnit (int: 0=minutes, 1=hours, 2=days)
    // FIXME: better adjust unit
    $ext = get_cal_ent_extras($row[0], 'webcal_site_extras', "cal_name = 'Reminder' AND cal_remind = 1");
    if ( $ext )
      echo '1,', $ext[5], ',0,';
    else
      echo '0,0,0,';
    // repeat:
    // repeatType (int: 0=none, 1=daily, 2=weekly, 3=monthly, 4=monthly/weekday,
    // repeatForever (int: 0=not forever, 1=forever)                   5=yearly)
    // repeatEnd (time)
    // repeatFrequency (int)
    // repeatDay (int: day# or 0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
    //					21..27 4th,  28-34 last week)
    // repeatWeekdays (int: add - 1=Sun,2=Mon,4=Tue,8=Wed,16=Thu,32=Fri,64=Sat)
    // repeatWeekstart (int)
    $ext = get_cal_ent_extras($row[0], 'webcal_entry_repeats');
    if ( $ext ) {
      switch ( $ext[1] ) {
	case 'daily':		$repType = 1; break;
	case 'weekly':		$repType = 2; break;
	case 'monthlyByDate':	$repType = 3; break;
	case 'monthlyByDay':	$repType = 4; break;
	case 'yearly':		$repType = 5; break;
	default:			$repType = 0;
      }
    } else $repType = 0;
    if ( $repType ) {
      echo $repType, ',';		// repeatType
      if ( $ext[2] ) {
	echo '0,', 			// repeatForever
	  substr($ext[2],0,4), '-',	// repeatEnd
	  substr($ext[2],4,2), '-',
	  substr($ext[2],6,2), ' 00:00:00,';
      } else
	echo '1,,';	// repeatForever,repeatEnd
      echo $ext[3], ',';// repeatFrequency
      switch ( $repType ) {
	case 2:	// weekly
	  echo '0,', bindec(strtr(strrev($ext[4]),'yn','10')) ,",1\n";
	  break;
	case 3:	// monthly/weekday
		// repeatDay (0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
		// 21..27 4th,  28-34 last week)
		echo floor( substr($row[3], 6, 2) / 7) *7
		  + date( 'w', date_to_epoch($row[3]) ), ",0,0\n";
		break;
	case 1:	// daily
	case 4:	// monthly
	case 5:	// yearly
		echo "0,0,0\n";
      } //end switch
    } else
      echo "0,0,,0,0,0,0\n";
    } //end if ( $repType )
} //end function

function transmit_header ( $mime, $file ) {
  header ( "Content-Type: application/octet-stream" );
  //header ( "Content-Type: $mime" );
  header ( 'Content-Disposition: attachment; filename="' . $file .  '"');
  header ( 'Pragma: no-cache');
  header ( 'Cache-Control: no-cache' );
} //end function

/*******************************************/
/*** Let's go ***/
/*******************************************/

$id = getPostValue  ( 'id' );
$format = getPostValue  ( 'format' );
$use_all_dates = getPostValue  ( 'use_all_dates' );
$include_layers = getPostValue  ( 'include_layers' );
$fromyear = getPostValue  ( 'fromyear' );
$frommonth = getPostValue  ( 'frommonth' );
$fromday = getPostValue  ( 'fromday' );
$endyear = getPostValue  ( 'endyear' );
$endmonth = getPostValue  ( 'endmonth' );
$endday = getPostValue  ( 'endday' );
$modyear = getPostValue  ( 'modyear' );
$modmonth = getPostValue  ( 'modmonth' );
$modday = getPostValue  ( 'modday' );

mt_srand((float) microtime()*1000000);

if (empty($id)) {
  $id = "all";
}

if ($format == "ical") {
  transmit_header ( 'text/ical', "webcalendar-$id.ics" );
  export_ical($id);
} elseif ($format == "vcal") {
  transmit_header ( 'text/vcal', "webcalendar-$id.vcs" );
  export_vcal($id);
} elseif ($format == "pilot-csv") {
  transmit_header ( 'text/csv', "webcalendar-$id.csv" );
  export_pilot_csv ( $id );
} elseif ($format == "pilot-text") {
  transmit_header('text/plain', "webcalendar-$id.txt" );
  export_install_datebook($id);
} else {
  //exit;

  print_header();

  echo "<h2>";
  etranslate("Export");
  echo " ";
  etranslate("Error");
  echo "</h2>\n";
  echo "<span style=\"font-weight:bold;\">";
  etranslate("Error");
  echo ":</span> ";
  echo translate("export format not defined or incorrect") . ".";
  echo "<br />\n";

  print_trailer ();

  echo " </body>\n";
  echo "</html>";
} //end if ($format == "ical")

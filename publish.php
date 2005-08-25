<?php
/*
 * $Id$
 *
 * Description:
 *	Creates the iCal output for a single user's calendar so
 *	that remote users can "subscribe" to a WebCalendar calendar.
 *	Both Apple iCal and Mozilla's Calendar support subscribing
 *	to remote calendars.
 *
 *	Note that unlink the export to iCal, this page does not include
 *	attendee info.  This improves the performance considerably, BTW.
 *
 * Notes:
 *	Does anyone know when a client (iCal, for example) refreshes its
 *	data, does it delete all old data and reload?  Just wondering
 *	if we need to somehow send a delete notification on updates...
 *
 * Input parameters:
 *	URL should be the form of /xxx/publish.php/username.ics
 *	or /xxx/publish.php?user=username
 *
 * Security:
 *	If $PUBLISH_ENABLED is not 'Y' (set in Admin System Settings),
 *	  do not allow.
 *	If $USER_PUBLISH_ENABLED is not 'Y' (set in each user's
 *	  Preferences), do not allow.
 */

require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/php-dbi.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include 'includes/translate.php';
include 'includes/site_extras.php';

$WebCalendar->initializeSecondPhase();

// Calculate username.
if ( empty ( $user ) ) {
  $arr = explode ( "/", $PHP_SELF );
  $user = $arr[count($arr)-1];
  # remove any trailing ".ics" in user name
  $user = preg_replace ( "/\.[iI][cC][sS]$/", '', $user );
  if ( $user == 'public' )
    $user = '__public__';
}

load_global_settings ();

$WebCalendar->setLanguage();

if ( empty ( $PUBLISH_ENABLED ) || $PUBLISH_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}

// Make sure they specified a username
if ( empty ( $user ) ) {
  echo "<?xml version=\"1.0\" encoding=\"utf8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
 <head>\n<title>" . translate("Error") . "</title>\n</head>\n" .
    "<body>\n<h2>" . translate("Error") . "</h2>\n" .
    "No user specified.\n</body>\n</html>";
}

// Load user preferences (to get the USER_PUBLISH_ENABLED and
// DISPLAY_UNAPPROVED setting for this user).
$login = $user;
load_user_preferences ();

if ( empty ( $USER_PUBLISH_ENABLED ) || $USER_PUBLISH_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}

// Load user name, etc.
user_load_variables ( $user, "publish_" );

function get_events_for_publish ()
{
  global $user;
  global $DISPLAY_UNAPPROVED;

  // We exporting repeating events only with the pilot-datebook CSV format
  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name " .
    ", webcal_entry.cal_priority, webcal_entry.cal_date " .
    ", webcal_entry.cal_time " .
    ", webcal_entry_user.cal_status, webcal_entry.cal_create_by " .
    ", webcal_entry.cal_access, webcal_entry.cal_duration " .
    ", webcal_entry.cal_description " .
    ", webcal_entry_user.cal_category " .
    "FROM webcal_entry, webcal_entry_user ";

  $sql .= "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
    "webcal_entry_user.cal_login = '" . $user . "'";

  // Include unapproved events if the user has asked to do so in
  // their preferences.
  if ( $DISPLAY_UNAPPROVED == "N"  || $user == "__public__" )
    $sql .= " AND webcal_entry_user.cal_status = 'A'";
  else
    $sql .= " AND webcal_entry_user.cal_status IN ('W','A')";

  $sql .= " ORDER BY webcal_entry.cal_date";

  $res = dbi_query ( $sql );

  return $res;
}

function export_quoted_printable_encode($car) {
  $res = "";

  if ((ord($car) >= 33 && ord($car) <= 60) ||
     (ord($car) >= 62 && ord($car) <= 126) ||
     ord($car) == 9 || ord($car) == 32) {
    $res = $car;
  } else {
    $res = sprintf("=%02X", ord($car));
  }

  return $res;
}

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

      // reduce row length of 1 to take into account the whitespace
      // at the beginning of lines
      if ($res_ind == 0)
        $fold--;

      $res_ind++; // next line

      $lwsp = 0;
    }

    $row .= $enc;

    if ($string[$i] == " " || $string[$i] == "\t" ||
      $string[$i] == ";" || $string[$i] == ",")
      $lwsp = strlen($row) - 1;

    if ($string[$i] == ":" && (strcmp($encoding,"quotedprintable") == 0))
      $lwsp = strlen($row) - 1; // we cut at ':' only for quoted printable
  }

  $res[$res_ind] = $row; // Add last row (or first if no folding is necessary)

  return $res;
}

function export_time($date, $duration, $time, $texport) {
  $allday = ( $time == -1 || $duration == 24*60 );
  $year = (int) substr($date,0,-4);
  $month = (int) substr($date,-4,2);
  $day = (int) substr($date,-2,2);

  //No time, or an "All day" event"
  if ( $allday ) {
    // untimed event
    $hour = 0;
    $min = 0;
    $sec = 0;
    $start_tmstamp = mktime($hour, $min, $sec, $month, $day, $year);
    $start_date = date("Ymd", $start_tmstamp);
    echo "DTSTART;VALUE=DATE:$start_date\r\n";
  } else {
    // normal/timed event
    $hour = (int) substr($time,0,-4);
    $min = (int) substr($time,-4,2);
    $sec = (int) substr($time,-2,2);
    $duration = $duration * 60;

    $start_tmstamp = mktime($hour, $min, $sec, $month, $day, $year);

    $utc_start = export_get_utc_date($date, $time);
    echo "DTSTART:$utc_start\r\n";
  }

  $utc_dtstamp = export_get_utc_date(date("Ymd", mktime()),
    date("His", mktime()));
  echo "DTSTAMP:$utc_dtstamp\r\n";

  // Only include and end time on all-day events and timed events
  // (and not for untimed events)
  if ( !$allday ) {
    $end_tmstamp = $start_tmstamp + $duration;
    $utc_end = export_get_utc_date(date("Ymd", $end_tmstamp),
      date("His", $end_tmstamp));
    echo "DTEND:$utc_end\r\n";
  }
}

function export_recurrence_ical($id, $date) {
  global $days_per_month, $ldays_per_month;
  $str_day = array( 'SU','MO','TU','WE','TH','FR','SA' );

  $sql = "SELECT cal_date FROM webcal_entry_repeats_not WHERE cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
    $exdate = array();
    $i = 0;
    while ($row = dbi_fetch_row($res)) {
      $exdate[$i] = $row[0];
      $i++;
    }
    dbi_free_result($res);
  }

  $sql = "SELECT webcal_entry_repeats.cal_type, " .
    "webcal_entry_repeats.cal_end, webcal_entry_repeats.cal_frequency, " .
    "webcal_entry_repeats.cal_days, webcal_entry.cal_time " .
    "FROM webcal_entry, webcal_entry_repeats " .
    "WHERE webcal_entry_repeats.cal_id = '$id' " .
    "AND webcal_entry.cal_id = '$id'";

  $res = dbi_query($sql);

  if ($res) {
    if ( $row = dbi_fetch_row($res) ) {
      $type = $row[0];
      $end = $row[1];
      $freq = $row[2];
      $day = $row[3];
      $time = $row[4];
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
        case 'monthlyByDayR':
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
        $year = (int) substr($date,0,-4);
        $month = (int) substr($date,-4,2);
        $day = (int) substr($date,-2,2);
        $stamp = mktime(0, 0, 0, $month, $day, $year);
        $dow = date ( "w", $stamp );
        $dow1 = date ( "w", mktime ( 0, 0, 0, $month, 1, $year ) );
        $partWeek = ( 7 - $dow1 ) % 7;
        $whichWeek = ceil ( ( $day - $partWeek ) / 7 );
        if ( $partWeek && $dow >= $dow1 )
          $whichWeek++;
        printf ( ";BYDAY=%d%s", $whichWeek, $str_day[$dow] );
      } elseif ($type == "monthlyByDayR") {
        $year = (int) substr($date,0,-4);
        $month = (int) substr($date,-4,2);
        $day = (int) substr($date,-2,2);
        $stamp = mktime(0, 0, 0, $month, $day, $year);
        $dow = date ( "w", $stamp );
        // get number of days in this month
        $daysthismonth = ( $year % 4 == 0 ) ? $ldays_per_month[$month] :
          $days_per_month[$month];
        // how many weekdays like this one remain in the month?
        // 0=last one, 1=one more after this one, etc.
        $whichWeek = floor ( ( $daysthismonth - $day ) / 7 );
        printf ( ";BYDAY=%d%s", -1 - $whichWeek, $str_day[$dow] );
      }

      if (!empty($end)) {
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
}

function export_alarm_ical($id, $description) {
  $sql = "SELECT cal_data FROM webcal_site_extras " .
         "WHERE cal_id = $id AND cal_type = ". EXTRA_REMINDER . " AND cal_remind = 1";
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

  $utc_date = date("Ymd", $tmstamp);
  $utc_hour = date("His", $tmstamp);

  $utc = sprintf ("%sT%sZ", $utc_date, $utc_hour);

  return $utc;
}

function generate_uid($id) {
  global $user, $server_url;

  $uid = $server_url;
  if ( empty ( $uid ) )
    $uid = "UNCONFIGURED-WEBCALENDAR";
  $uid = str_replace ( "http://", "", $uid );
  $uid .= sprintf ( "-%s-%010d", $user, $id );
  $uid = preg_replace ( "/[\s\/\.-]+/", "-", $uid );
  $uid = strtoupper ( $uid );
  return $uid;
}

function export_ical () {
  global $publish_fullname, $user, $PROGRAM_NAME;
  $res = get_events_for_publish ();

  $entry_array = array();
  $count = 0;

  while ( $entry = dbi_fetch_row($res) ) {
    $entry_array[$count++] = $entry;
  }

  if ($count > 0) {
    echo "BEGIN:VCALENDAR\r\n";
    $title = "X-WR-CALNAME;VALUE=TEXT:" .
      ( empty ( $publish_fullname ) ? $user : translate($publish_fullname) );
    $title = str_replace ( ",", "\\,", $title );
    echo "$title\r\n";
    if ( preg_match ( "/WebCalendar v(\S+)/", $PROGRAM_NAME, $match ) ) {
      echo "PRODID:-//WebCalendar-$match[1]\r\n";
    } else {
      echo "PRODID:-//WebCalendar-UnknownVersion\r\n";
    }
    echo "VERSION:2.0\r\n";
    echo "METHOD:PUBLISH\r\n";
  }

  while (list($key,$row) = each($entry_array)) {
    $uid = $row[0];
    $export_uid = generate_uid($uid);
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

    /* CLASS either "PRIVATE" or "PUBLIC" (the default) */
    if ($access == "R") {
      echo "CLASS:PRIVATE\r\n";
    } else {
      echo "CLASS:PUBLIC\r\n";
    }

    /* Time - all times are utc */
    export_time($date, $duration, $time, "ical");

    /* Recurrence */
    export_recurrence_ical($uid, $date);

    /* handle alarms */
    export_alarm_ical($uid,$description);

    /* Goodbye event */
    echo "END:VEVENT\r\n";
  }

  if ($count > 0)
    echo "END:VCALENDAR\r\n";
}

//header ( "Content-Type: text/plain" );
header ( "Content-Type: text/calendar" );
export_ical();
?>

<?php
/*
 * $Id$
 *
 * WARNING * WARNING * WARNING * WARNING * WARNING * WARNING
 *	This script is still considered alpha level.  Please backup
 *	your database before using it.
 * WARNING * WARNING * WARNING * WARNING * WARNING * WARNING
 *
 * Description:
 *	Creates the iCal output for a single user's calendar so
 *	that remote users can "subscribe" to a WebCalendar calendar.
 *	Both Apple iCal and Mozilla's Calendar (Sunbird) support subscribing
 *	to remote calendars and publishing events back to the server
 *	(WebCalendar in this case).
 *
 *	This file was based on publish.php and may replace it when
 *	it is found to be stable.
 *
 *	Note that unlike the export to iCal, this page does not include
 *	attendee info.  This improves the performance considerably, BTW.
 *
 *	ERROR !!!!!
 *	There seems to be a bug in certain versions of PHP where the fgets()
 *	returns a blank string when reading stdin.  I found this to be
 *	a problem with PHP 4.1.2 on Linux.  If this is true for your PHP,
 *	you will not be able to import the events back from the ical client.
 *	It did work correctly with PHP 5.0.2.
 *
 *	The script sends an error message back to the iCal client, but
 *	Mozilla Calendar does not seem to display the message.  (Strange,
 *	since it did display a PHP compile error message...)
 *
 * Usage Requirements:
 *	For this work, at least on Apache, the following needs
 *	to be added to the http.conf file:
 *		<Directory "/var/www/html/webcalendar">
 *		  Script PUT /subsciption.php    
 *		</Directory>
 *	Of course, replace "/var/www/html/webcalendar" with the
 *	directory where you installed WebCalendar.
 *
 * Input parameters:
 *	None
 *
 * Security:
 *	If $PUBLISH_ENABLED is not 'Y' (set in Admin System Settings),
 *	  do not allow.
 *	If $USER_PUBLISH_RW_ENABLED is not 'Y' (set in each user's
 *	  Preferences), do not allow.
 *
 * Change List:
 *	06-Jan-2005	Ray Jones
 *			Added logic to publish calendars to remote iCal clients
 *			The clients I've tested use METHOD:PUT to upload
 *			their data to the server.  This file does not use
 *			WEBDAV, but the client doesn't know or seem to care
 *
 * Notes:
 *	Because data is being written back to WebCalendar, the user is prompted 
 *	for username and password via the 401 HEADER 
 *	SEE TO DO for needed work
 * 
 *	To Delete an event from the iCal client, mark it as 'Cancelled'.
 *	This will translate into a 'D' in webcal_entry_user.cal_status.
 * 
 * TODO:
 *	Security!  If an event update comes back from an iCal client,
 *	we need to make sure that the remote user has the authority to
 *	modify the event.  (If they are only a participant and not the
 *	creator of the event or an admin, then they should not be able
 *	to update an event.)
 *
 *	MAYBE add logic to loop through webcal_import_data and delete 
 *	any records that don't come back from the iCal client. This would
 *	indicate events were deleted from the client instead of being marked
 *	'Cancelled'.
 *
 *	HTML in cal_description gets escaped when coming back from iCal client
 *	some formatting is getting deleted. I added a couple lines to modify
 *	these and it seems to work. However....you never know what it might
 *	break.
 *
 *	Testing needs to be done with various RRULE options on import.
 *
 *	Better support for event reminders.  Reminders for past events
 *	are not sent currently.  This is because Mozilla Calendar may
 *	popup all reminders (even ones that are years old) when the
 *	calendar is loaded.  Ideally, we should check the webcal_reminder_log
 *	table to see if an event reminder was already sent.  Also, not
 *	sure if reminders for repeated events are handled properly yet.
 *  
 */

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
// If WebCalendar is configured to use http authentication, then
// we can use validate.php.  If we are not using http auth, we will
// create our own http auth just for this page since an iCal client cannot
// login via a web-based login.
if ( ! empty ( $use_http_auth ) && $use_http_auth ) {
  include_once "includes/validate.php";
}
include "includes/connect.php";
include "includes/translate.php";
include 'includes/site_extras.php';

// Require an authenticated user HTTP Auth
// TODO: make this work for CGI installations
//       see http://us3.php.net/manual/en/features.http-auth.php
global $login;

if ( empty ( $application_name ) ) {
  $application_name = "WebCalendar";
}

// If WebCalendar is using http auth, then $login will be set in
// validate.php.


if ( empty ( $login ) ) {
  if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="' . $application_name . '"' );
    header('HTTP/1.0 401 Unauthorized');
    exit;
  } else {
    if ( user_valid_login ( $_SERVER['PHP_AUTH_USER'],
      $_SERVER['PHP_AUTH_PW'] )) {
      $login = $_SERVER['PHP_AUTH_USER'];
    } else {
      unset($_SERVER['PHP_AUTH_USER']);
      unset($_SERVER['PHP_AUTH_PW']);
      //TO DO should be able code this better to eliminate duplicate code
      header('WWW-Authenticate: Basic realm="WebCalendar Publisher - ' . $application_name );
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
  }  
}
 
load_global_settings ($login);
load_user_preferences ($login);


if ( empty ( $PUBLISH_ENABLED ) || $PUBLISH_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  // Mozilla Calendar does not bother showing errors, so they won't
  // see this error message anyhow....  Not sure about Apple iCal or
  // other clients.
  etranslate ("Publishing Disabled (Admin)" );
  exit;
}

if ( empty ( $USER_PUBLISH_RW_ENABLED ) || $USER_PUBLISH_RW_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  //TO DO add to translations (???)
  etranslate ("Publishing Disabled (User)" );
  exit;
}


$prodid = "Unnamed iCal client";



// Load user name, etc.
user_load_variables ( $login, "publish_" );

//TO DO move common functions to a new file
// the existing import_handler and export_handler files share much code
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
        $dow1 = date ( "w", mktime ( 3, 0, 0, $month, 1, $year ) );
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
		// This doesn't work on Sunbird
		// We just need the basic date non UTC
        //  $date = export_get_utc_date($exdate[$i], 0);
		  $date = $exdate[$i] . "T000000";
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

// Convert the webcalendar reminder to an ical VALARM
// TODO: need to loop through the site_extras[] array to determine
// what type of reminder (with date or with offset) since this info
// is not stored in the database.  Then, use that to determine when
// the reminder should be sent.  Check the webcal_reminder_log to
// make sure the reminder has not already been sent.
function export_alarm_ical ( $id, $date, $description ) {

  // Don't send reminder for event in the past
  if ( $date < date("Ymd") )
    return;

  $sql = "SELECT cal_data FROM webcal_site_extras " .
    "WHERE cal_id = $id AND cal_type = EXTRA_REMINDER AND " .
    "cal_remind = 1";
  $res = dbi_query ( $sql );
  $row = dbi_fetch_row ( $res );
  dbi_free_result ( $res );

  if ( $row ) {
    //Sunbird requires this line
    echo "X-MOZILLA-ALARM-DEFAULT-LENGTH:" . $row[0] . "\r\n";
    echo "BEGIN:VALARM\r\n";
    echo "TRIGGER:-PT".$row[0]."M\r\n";
    echo "ACTION:DISPLAY\r\n";

    $array = export_fold_lines ( $description, "utf8" );
    while  ( list ( $key, $value ) = each ( $array ) ) {
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

  $utc_date = gmdate("Ymd", $tmstamp);
  $utc_hour = gmdate("His", $tmstamp);

  $utc = sprintf ("%sT%sZ", $utc_date, $utc_hour);

  return $utc;
}

function generate_uid($id) {
  global  $server_url, $login;

  $uid = $server_url;
  if ( empty ( $uid ) )
    $uid = "UNCONFIGURED-WEBCALENDAR";
  $uid = str_replace ( "http://", "", $uid );
  $uid .= sprintf ( "-%s-%010d", $login, $id );
  $uid = preg_replace ( "/[\s\/\.-]+/", "-", $uid );
  $uid = strtoupper ( $uid );
  return $uid;
}

function export_ical () {
  global $publish_fullname, $login, $PROGRAM_VERSION, $PROGRAM_NAME;
  $exportId = -1;
 
  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name " .
    ", webcal_entry.cal_priority, webcal_entry.cal_date " .
    ", webcal_entry.cal_time " .
    ", webcal_entry_user.cal_status, webcal_entry.cal_create_by " .
    ", webcal_entry.cal_access, webcal_entry.cal_duration " .
    ", webcal_entry.cal_description " .
    ", webcal_entry_user.cal_category " .
    "FROM webcal_entry, webcal_entry_user ";
  $sql .= "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
    "webcal_entry_user.cal_login = '" . $login . "'";
  $sql .= " AND webcal_entry_user.cal_status IN ('W','A')";
  $sql .= " ORDER BY webcal_entry.cal_date";
  // Note: we may want to include just approved events if user's preference
  // say to not show unapproved events.

  $res = dbi_query ( $sql );
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

    // Figure out Categories
    $categories = $row[10];
    $sql = "SELECT cat_name FROM webcal_categories " .
      "WHERE cat_owner = '" . $login . "'" .
      " AND cat_id = '" . $categories ."'" ;
    $res = dbi_query ( $sql );
    if ( $row = dbi_fetch_row ( $res ) ) {
      $categories = $row[0];
    } else {
      unset ( $categories );
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

    /* Start of event */
    echo "BEGIN:VEVENT\r\n";

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
	
    /* CATEGORIES if any (folded to 76 char) */
    if (isset( $categories )) {
      $categories = "CATEGORIES:" . $categories;
      $array = export_fold_lines($categories,"utf8");
      while (list($key,$value) = each($array))
        echo "$value\r\n";
    }

    /* CLASS either "PRIVATE" or "PUBLIC" (the default) */
    if ($access == "R") {
      echo "CLASS:PRIVATE\r\n";
    } else {
      echo "CLASS:PUBLIC\r\n";
    }
	
    /* STATUS */
    if ($status == "A") {
      echo "STATUS:CONFIRMED\r\n";
    } else if ($status == "W") {
      echo "STATUS:TENTATIVE\r\n";
    }

    /* Time - all times are utc */
    export_time($date, $duration, $time, "ical");

    /* Recurrence */
    export_recurrence_ical($id, $date);

    /* handle alarms */
    export_alarm_ical($id,$date,$description);

    /* Goodbye event */
    echo "END:VEVENT\r\n";
	
  }

  echo "END:VCALENDAR\r\n";
}

//Copied functions from import_handler.php

/* Import the data structure
$Entry[RecordID]           =  Record ID (in the Palm) ** only required for palm desktop
$Entry[StartTime]          =  In seconds since 1970 (Unix Epoch)
$Entry[EndTime]            =  In seconds since 1970 (Unix Epoch)
$Entry[Summary]            =  Summary of event (string)
$Entry[Duration]           =  How long the event lasts (in minutes)
$Entry[Description]        =  Full Description (string)
$Entry[Untimed]            =  1 = true  0 = false
$Entry[Class]              =  R = PRIVATE,CONFIDENTIAL  P = PUBLIC
$Entry[Categories]           =  String containing Categories
$Entry[AlarmSet]           =  1 = true  0 = false
$Entry[AlarmAdvanceAmount] =  How many units in AlarmAdvanceType (-1 means not set)
$Entry[AlarmAdvanceType]   =  Units: (0=minutes, 1=hours, 2=days)
$Entry[Repeat]             =  Array containing repeat information (if repeat)
$Entry[Repeat][Interval]   =  1=daily,2=weekly,3=MonthlyByDay,4=MonthlyByDate,5=Yearly,6=monthlyByDayR
$Entry[Repeat][Frequency]  =  How often event occurs. (1=every, 2=every other,etc.)
$Entry[Repeat][EndTime]    =  When the repeat ends (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][Exceptions] =  Exceptions to the repeat (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][RepeatDays] =  For Weekly: What days to repeat on (7 characters...y or n for each day)
*/

function import_ical () {
global $login;

do_debug ( "before parse_ical" );
$data = parse_ical();
do_debug ( "after parse_ical" );

$importId = -1;

foreach ( $data as $Entry ){

    do_debug ( "next entry" );
    $priority = 2;
    $participants[0] = $login;

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


    // Check for untimed
    if ($Entry['Untimed'] == 1) {
      $Entry['StartMinute'] = '';
      $Entry['StartHour'] = '';
      $Entry['EndMinute'] = '';
      $Entry['EndHour'] = '';
    }

    if ( empty ( $error ) ) { 
      $updateMode = false;
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

      do_debug ( "updateMode = " . ( $updateMode ? "true" : "false" ) .
        ", id=$id" );

      $entryclass = $Entry['Class'];
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
      $values[] = ($Entry['Untimed'] == 1) ? "-1" :
        sprintf ( "%02d%02d00", $Entry['StartHour'],$Entry['StartMinute']);
      $names[] = 'cal_mod_date';
      $values[] = date("Ymd");
      $names[] = 'cal_mod_time';
      $values[] = date("Gis");
      $names[] = 'cal_duration';
      $values[] = sprintf ( "%d", $Entry['Duration'] );
      $names[] = 'cal_priority';
      $values[] = $priority;
      $names[] = 'cal_access';
      $values[] = "'$entryclass'";
      $names[] = 'cal_type';
      $values[] = ($Entry['Repeat']) ? "'M'" : "'E'";

      if ( strlen ( $Entry['Summary'] ) == 0 )
        $Entry['Summary'] = translate("Unnamed Event");
      if ( strlen ( $Entry['Description'] ) == 0 )
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
      // limit length to 1024 chars since we setup tables that way
      if ( strlen ( $Entry['Description'] ) >= 1024 )
        $Entry['Description'] = substr ( $Entry['Description'], 0, 1019 ) . "...";
      $names[] = 'cal_description';
      $values[] = "'" . $Entry['Description'] .  "'";
      do_debug ( "descr='" . $Entry['Description'] . "'" );
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

      do_debug ( "SQL> $sql" );
      if ( empty ( $error ) ) {
        if ( ! dbi_query ( $sql ) ) {
          $error .= translate("Database error") . ": " . dbi_error ();
          do_debug ( $error );
          break;
        }
      }

      // log add/update
      activity_log ( $id, $login, $login,
        $updateMode ? LOG_UPDATE : LOG_CREATE, "Publish Update" );

      // Now add to webcal_import_data
      if ( ! $updateMode ) {
	//add entry to webcal_import and webcal_import_data
        $uid = generate_uid ($id);
        $uid = empty ( $Entry['UID'] ) ? $uid  : $Entry[UID];
        if ( $importId < 0 ) {
          $importId = create_import_instance ();
        }
        do_debug ( "Saving UID=$uid for id=$id" );
        save_uid_for_event ( $importId, $id , $uid);
      }

      // Now add participants
      $status = $Entry['Status'];
      $cat_id = ( ! empty ( $Entry['Categories'] ) ? $Entry['Categories'] :
        'NULL' );

      if ( ! $updateMode ) {
        do_debug ( "Adding event $id for user $participants[0] with status=$status" );
        $sql = "INSERT INTO webcal_entry_user " .
          "( cal_id, cal_login, cal_status, cal_category ) VALUES ( $id, '" .
          $participants[0] . "', '" . $status . "', $cat_id )";
        do_debug ( "SQL> $sql" );
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
          do_debug ( "Error: " . $error );
          break;
        }
      } else {
        do_debug ( "Updating event $id for user $participants[0] with status=$status" );
        do_debug ( "SQL> $sql" );
        $sql = "UPDATE webcal_entry_user SET cal_status = '". $status . "' ," .
	  " cal_category = '" . $cat_id . "'" .
          " WHERE cal_id = $id";
       if ( ! dbi_query ( $sql ) ) {
         $error = translate("Database error") . ": " . dbi_error ();
         do_debug ( "Error: " . $error );
         break;
       }
     }
     
     // Add repeating info
     if ( $updateMode ) {
       // remove old repeating info
       dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
       dbi_query ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = $id" );
     }
	  
     if (! empty ($Entry['Repeat']['Interval'])) {
       $rpt_type = RepeatType($Entry['Repeat']['Interval']);
       $freq = ( $Entry['Repeat']['Frequency'] ? $Entry['Repeat']['Frequency'] : 1 );
       if ( strlen ( $Entry['Repeat']['EndTime'] ) ) {
         $REND   = localtime($Entry['Repeat']['EndTime']);
	 $end = sprintf ( "%04d%02d%02d",$REND[5] + 1900,$REND[4] + 1,$REND[3]);
       } else {
         $end = 'NULL';
       }
       $days = (! empty ($Entry['Repeat']['RepeatDays'])) ? "'".$Entry['Repeat']['RepeatDays']."'" : 'NULL';
       $sql = "INSERT INTO webcal_entry_repeats ( cal_id, " .
         "cal_type, cal_end, cal_days, cal_frequency ) VALUES " .
         "( $id, '$rpt_type', $end, $days, $freq )";
       if ( ! dbi_query ( $sql ) ) {
         $error = "Unable to add to webcal_entry_repeats: ".dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
         break;
        }

        // Repeating Exceptions...
        if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
          foreach ($Entry['Repeat']['Exceptions'] as $ex_date) {
            $ex_date = date("Ymd",$ex_date);
            $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date ) VALUES ( $id, $ex_date )";
 
            if ( ! dbi_query ( $sql ) ) {
              $error = "Unable to add to webcal_entry_repeats_not: ".dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
              break;
            }
          }
        }
      } // End Repeat


      // Add Alarm info -> site_extras
      if ( $updateMode ) {
        dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_type = 7 AND cal_id = $id" );
      }
      if ($Entry['AlarmSet'] == 1) {
        $RM = $Entry['AlarmAdvanceAmount'];
        $sql = "INSERT INTO webcal_site_extras ( cal_id, " .
          "cal_name, cal_type, cal_remind, cal_data ) VALUES " .
          "( $id, 'Reminder', 7, 1, '$RM' )";

        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
        }
      }
    }

  }
}

// Convert interval to webcal repeat type
function RepeatType ($type) {
  $Repeat = array (0,'daily','weekly','monthlyByDay','monthlyByDate','yearly','monthlyByDayR');
  return $Repeat[$type];
}


// Add an entry in webcal_import.  For each import or publish request,
// we create a single webcal_import row that goes with the many
// webcal_import_data rows (one for each event).
function create_import_instance ()
{
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
			
  do_debug ( "SQL: $sql" );
  if ( ! dbi_query ( $sql ) ) {
    $error = translate("Database error") . ": " . dbi_error ();
    do_debug ( $error );
  }
  do_debug ( "leaving func" );
}


function dump_globals ()
{
  foreach ( $GLOBALS as $K => $V ) {
    if ( strlen ( $V ) < 70 )
      do_debug ( "GLOBALS[$K] => $V" );
    else
      do_debug ( "GLOBALS[$K] => (too long)" );
  }
  foreach ( $GLOBALS['HTTP_POST_VARS'] as $K => $V ) {
    if ( strlen ( $V ) < 70 )
      do_debug ( "GLOBALS[$HTTP_POST_VARS[$K]] => $V" );
    else
      do_debug ( "GLOBALS[$HTTP_POST_VARS[$K]] => (too long)" );
  }
}

// NOTE!!!!!
// There seems to be a bug in certain versions of PHP where the fgets()
// returns a blank string when reading stdin.  I found this to be
// a problem with PHP 4.1.2 on Linux.
// I did work correctly with PHP 5.0.2.
function parse_ical () {
  global $tz,  $login, $prodid;
  $ical_data = array();

  //dump_globals ();

  do_debug ( "before fopen on stdin..." );
  $stdin = fopen ("php://input", "r");
  //$stdin = fopen ("/dev/stdin", "r");
  //$stdin = fopen ("/dev/fd/0", "r");
  do_debug ( "after fopen on stdin..." );
  // Read in contents of entire file first
  $data = '';
  $cnt = 0;
  while ( ! feof ( $stdin ) ) {
    $line = fgets ( $stdin, 1024 );
    do_debug ( "data-> '" . $line . "'" );
    $cnt++;
    //do_debug ( "cnt = " . ( ++$cnt ) );
    $data .= $line;
    if ( $cnt > 10 && strlen ( $data ) == 0 ) {
      do_debug ( "Read $cnt lines of data, but got no data :-(" );
      do_debug ( "Informing user of PHP server bug (PHP v" . phpversion() . ")" );
      // Note: Mozilla Calendar does not display this error for some reason.
      echo "<br><b>Error:</b> Your PHP server " . phpversion () .
        " seems to have a bug reading stdin.  " .
        "Try upgrading to a newer PHP release.  <br>";
      exit;
    }
  }
  fclose ( $stdin );

  do_debug ( "strlen(data)=" . strlen($data) );

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

  //do_debug ( "read stdin...: '$data'" );

    // Now fix folding.  According to RFC, lines can fold by having
    // a CRLF and then a single white space character.
    // We will allow it to be CRLF, CR or LF or any repeated sequence
    // so long as there is a single white space character next.
    //echo "Orig:<br><pre>$data</pre><br/><br/>\n";
    $data = preg_replace ( "/[\r\n]+ /", "", $data );
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );

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
      $buff = $lines[$n];
      if ( preg_match ( "/^PRODID:(.+)$/i", $buff, $match) ) {
        $prodid = $match[1];
        $prodid = str_replace ( "-//", "" );
        $prodid = str_replace ( "\,", "," );
        do_debug ( "Product ID: " . $prodid );
      }
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
            } else if ( $subsubstate == "VALARM"){ 
              if (preg_match ( "/^TRIGGER.*:(.+)$/i", $buff, $match )){
				//echo $match[1];
				$substate = "alarm";
                $event[$substate] = $match[1];
			  }
			}
          }
          else if (preg_match("/^BEGIN:(.+)$/i", $buff, $match)) {
            $subsubstate = $match[1];
          }
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
		  } elseif (preg_match("/^STATUS.*:(.*)$/i", $buff, $match)) {
              $substate = "status";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY.*:(.*)$/i", $buff, $match)) {
              $substate = "priority";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART.*:\s*(\d+T\d+Z?)\s*$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART.*:\s*(\d+)\s*$/i", $buff, $match)) {
              $substate = "dtstart";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTEND.*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "dtend";
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
			  //this seems to be the only item that needs it, but we'll see
			  $comma =(isset($event[$substate] )) ? "," :"";
              $event[$substate] .= $comma . $match[1];
          } elseif (preg_match("/^CATEGORIES.*:(.+)$/i", $buff, $match)) {
              $substate = "categories";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^UID.*:(.+)$/i", $buff, $match)) {
              $substate = "uid";
              $event[$substate] = $match[1];	  
		 } else if (preg_match("/^BEGIN:VALARM/i", $buff)) {
            $state = "VALARM";
			
			
			
          } elseif (preg_match("/^END:VEVENT$/i", $buff, $match)) {
              $state = "VCALENDAR";
              $substate = "none";
              $subsubstate = '';
	      $ical_data[] = format_ical($event);
              // clear out data for new event
              $event = '';

	 
	     //Folded Lines have already been taken care of
          } elseif (preg_match("/^\s(\S.*)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
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
           }
      } elseif ($state == "VTIMEZONE") {
        // We don't do much with timezone info yet...
        if (preg_match("/^END:VTIMEZONE$/i", $buff)) {
          $state = "VCALENDAR";
        }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
           $state = "VCALENDAR";
      }

  }

  return $ical_data;
}

// Convert ical format (yyyymmddThhmmssZ) to epoch time
function icaldate_to_timestamp ($vdate, $plus_d = '0', $plus_m = '0',
  $plus_y = '0') {
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


// Put all ical data into import hash structure
function format_ical($event) {
global $login;
  //Categories
   if ( isset ( $event['categories'] ) ) {
  		$fevent['Categories'] = get_categories_id_byname  ($event['categories'], $login);
  }
  // Start and end time
  $fevent['StartTime'] = icaldate_to_timestamp($event['dtstart']);
  if ( isset ( $event['dtend'] ) ) {
    $fevent['EndTime'] = icaldate_to_timestamp($event['dtend']);
  } else {
    if ( isset ( $event['duration'] ) ) {
      $fevent['EndTime'] = $fevent['StartTime'] + $event['duration'] * 60;
    } else {
      $fevent['EndTime'] = $fevent['StartTime'];
    }
  }

  // Calculate duration in minutes
  if ( isset ( $event['duration'] ) ) {
    $fevent['Duration'] = $event['duration'];
  } else if ( empty ( $fevent['Duration'] ) ) {
    $fevent['Duration'] = ($fevent['EndTime'] - $fevent['StartTime']) / 60;
  }
  if ( $fevent['Duration'] == '1440' ) {
    // All day event... nothing to do here :-)
  } else if ( preg_match ( "/\d\d\d\d\d\d\d\d$/",
    $event['dtstart'], $pmatch ) ) {
    // Untimed event
    $fevent['Duration'] = 0;
    $fevent['Untimed'] = 1;
  }
  if ( preg_match ( "/\d\d\d\d\d\d\d\d$/", $event['dtstart'],
    $pmatch ) && preg_match ( "/\d\d\d\d\d\d\d\d$/", $event['dtend'],
    $pmatch2 ) && $event['dtstart'] != $event['dtend'] ) {
    $startTime = icaldate_to_timestamp($event['dtstart']);
    $endTime = icaldate_to_timestamp($event['dtend']);
    // Not sure... should this be untimed or allday?
    if ( $endTime - $startTime == ( 3600 * 24 ) ) {
      // They used a DTEND set to the next day to say this is an all day
      // event.  We will call this an untimed event.
      $fevent['Duration'] = '0';
      $fevent['Untimed'] = 1;
    } else {
      // Event spans multiple days.  The EndTime actually represents
      // the first day the event does _not_ take place.  So,
      // we need to back up one day since WebCalendar end date is the
      // last day the event takes place.
      $fevent['Repeat']['Interval'] = '1'; // 1 = daily
      $fevent['Repeat']['Frequency'] = '1'; // 1 = every day
      $fevent['Duration'] = '0';
      $fevent['Untimed'] = 1;
      $fevent['Repeat']['EndTime'] = $endTime - ( 24 * 3600 );
    }
  }

  $fevent['Summary'] = $event['summary'];
  $fevent['Description'] = $event['description'];
  $fevent['UID'] = $event['uid'];

//Added 
  if ($event['alarm']){
  	$fevent['AlarmSet'] = 1;
	$alH = $alM = 0;

     if ( preg_match ( "/PT([0-9]+)H/", $event['alarm'], $submatch ) )
          $alH = $submatch[1];
     if ( preg_match ( "/PT([0-9]+)M/", $event['alarm'], $submatch ) )
	 	  $alM = $submatch[1];
    $fevent['AlarmAdvanceAmount'] = ($alH * 60) + $alM;
  }
 

  if ($event['class'] == "PRIVATE") {
  		$fevent['Class'] = "R";
  } else if ($event['class'] == "CONFIDENTIAL") {
 		$fevent['Class'] = "R";
  } else {
  		$fevent['Class'] = "P";
  }
  
  if ($event['status'] == "TENTATIVE") {
  		$fevent['Status'] = "W";
  } else if ($event['status'] == "CONFIRMED") {
 		$fevent['Status'] = "A";
  } else if ($event['status'] == "CANCELLED"){
  		$fevent['Status'] = "D";
  }

  // Repeats
  //
  // Handle RRULE
  if ($event['rrule']) {
    // first remove and EndTime that may have been calculated above
    unset ( $fevent['Repeat']['EndTime'] );
    //split into pieces
    //echo "RRULE line: $event[rrule] <br />\n";
    $RR = explode ( ";", $event['rrule'] );

    // create an associative array of key-value paris in $RR2[]
    for ( $i = 0; $i < count ( $RR ); $i++ ) {
      $ar = explode ( "=", $RR[$i] );
      $RR2[$ar[0]] = $ar[1];
    }

    for ( $i = 0; $i < count ( $RR ); $i++ ) {
      //echo "RR $i = $RR[$i] <br />";
      if ( preg_match ( "/^FREQ=(.+)$/i", $RR[$i], $match ) ) {
        if ( preg_match ( "/YEARLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 5;
        } else if ( preg_match ( "/MONTHLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 2;
        } else if ( preg_match ( "/WEEKLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 2;
        } else if ( preg_match ( "/DAILY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 1;
        } else {
          // not supported :-(
          echo "Unsupported iCal FREQ value \"$match[1]\"<br />\n";
        }
      } else if ( preg_match ( "/^INTERVAL=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['Frequency'] = $match[1];
      } else if ( preg_match ( "/^UNTIL=(.+)$/i", $RR[$i], $match ) ) {
        // specifies an end date
        $fevent['Repeat']['EndTime'] = icaldate_to_timestamp ( $match[1] );
      } else if ( preg_match ( "/^COUNT=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal COUNT value \"$RR[$i]\"<br />\n";
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
        $months = explode ( ",", $match[1] );
        if ( count ( $months ) == 1 ) {
          // Change this to a monthly event so we can support repeat by
          // day of month (if needed)
          // Frequency = 3 (by day), 4 (by date), 6 (by day reverse)
          if ( ! empty ( $RR2['BYDAY'] ) ) {
            if ( preg_match ( "/^-/", $RR2['BYDAY'], $junk ) )
              $fevent['Repeat']['Interval'] = 6; // monthly by day reverse
            else
              $fevent['Repeat']['Interval'] = 3; // monthly by day
            $fevent['Repeat']['Frequency'] = 12; // once every 12 months
          } else {
            // could convert this to monthly by date, but we will just
            // leave it as yearly.
            //$fevent['Repeat']['Interval'] = 4; // monthly by date
          }
        } else {
          // WebCalendar does not support this
          echo "Unsupported iCal BYMONTH value \"$match[1]\"<br />\n";
        }
      } else if ( preg_match ( "/^BYDAY=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['RepeatDays'] = rrule_repeat_days( $match );
      } else if ( preg_match ( "/^BYMONTHDAY=(.+)$/i", $RR[$i], $match ) ) {

        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal BYMONTHDAY value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYSETPOS=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        echo "Unsupported iCal BYSETPOS value \"$RR[$i]\"<br />\n";
      }
    }

    // Repeating exceptions?
    if ($event['exdate']) {
      $fevent['Repeat']['Exceptions'] = array();
      $EX = explode(",", $event['exdate']);
      foreach ( $EX as $exdate ){
        $fevent['Repeat']['Exceptions'][] = icaldate_to_timestamp($exdate);
      }
    }
  } // end if rrule

  return $fevent;
}
//TO DO Sunbird will output 2TH meaning 2nd Thursaday
//need to parse this better
// Figure out days of week for weekly repeats
//Had to change some code from the original
// import_handler to get this working
function rrule_repeat_days($RA) {
  $T = count($RA);
//  $j = $T - 1;
  $sun = $mon = $tue = $wed = $thu = $fri = $sat = 'n';


  for ($i = 0 ; $i < $T ; $i++) {

    if ($RA[$i] == 'SU') {
      $sun = 'y';
    } elseif ($RA[$i] == 'MO') {
      $mon = 'y';
    } elseif ($RA[$i] == 'TU') {
      $tue = 'y';
    } elseif ($RA[$i] == 'WE') {
      $wed = 'y';
    } elseif ($RA[$i] == 'TH') {
      $thu = 'y';
    } elseif ($RA[$i] == 'FR') {
      $fri = 'y';
    } elseif ($RA[$i] == 'SA') {
      $sat = 'y';
    }
  }

  return $sun.$mon.$tue.$wed.$thu.$fri.$sat;
}


// Calculate repeating ending time
function rrule_endtime($int,$freq,$start,$end) {

  // if # then we have to add the difference to the start time
  if (preg_match("/^#(.+)$/i", $end, $M)) {
    $T = $M[1] * $freq;
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
    $endtime = icaldate_to_timestamp($start,$plus_d,$plus_m,$plus_y);

  // if we have the enddate
  } else {
    $endtime = icaldate_to_timestamp($end);
  }
  return $endtime;
}

function get_categories_id_byname ( $cat_name) {
  global $login;
  $res = dbi_query ( "SELECT cat_id FROM webcal_categories WHERE " .
    "cat_name  = '" . $cat_name . "' AND cat_owner = '" . $login . "'");
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $ret = $row[0];
      dbi_free_result ( $res );
    } else { //Need to insert new Category
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
          $ret = $id;
        }
      } //end if $res
    }   // end if row
  } else { //no res
    $error = translate("Database error") . ": " . dbi_error ();
    do_debug ( $error );
  }
  return $ret;
}

if ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
  do_debug ( "Importing updated remote calendar" );
  import_ical ();
} else {
  do_debug ( "Exporting updated remote calendar" );
  header ( "Content-Type: text/calendar" );
  header ( 'Content-Disposition: attachment; filename="' . $login .  '.ics"' );
  export_ical();
}
?>

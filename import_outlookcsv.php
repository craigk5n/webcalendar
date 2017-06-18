<?php
/* $Id: import_outlookcsv.php,v 1.15.2.3 2011/07/12 19:17:42 rjones6061 Exp $
 *
 * File Description:
 * This file incudes functions for parsing CSV files generated from MS Outlook.
 *
 * It will be included by import_handler.php.
 *
 * Limitations:
 * This only works when the user does not "Map Custom Fields"
 * during the export from Outlook.
 */

/* Parse the Outlook CSV file and return the data hash.
 */
function parse_outlookcsv ( $cal_file ) {
  global $errormsg, $tz;

  $outlookcsv_data = array ();

  if ( ! $fd = @fopen ( $cal_file, 'r' ) ) {
    $errormsg .= 'Cannot read temporary file: ' . "$cal_file\n";
    exit ();
  } else {

    # Burn First Row of Headers
    $data = fgetcsv ( $fd, @filesize ( $cal_file ), ',' );

    while ( $data = fgetcsv ( $fd, @filesize ( $cal_file ) ) ) {
      $subject = addslashes ( $data[0] );
      $start = icaldate_to_timestamp ( date ( 'Ymd\THis', strtotime ( $data[1]
             . ' ' . $data[2] ) ) );
      $end = icaldate_to_timestamp ( date ( 'Ymd\THis', strtotime ( $data[3]
             . ' ' . $data[4] ) ) );
      $all_day_event = ( int ) toBoolean ( $data[5] );
      $remind_on_off = ( int ) toBoolean ( $data[6] );
      $reminder = icaldate_to_timestamp ( date ( 'Ymd\THis', strtotime ( $data[7]
             . ' ' . $data[8] ) ) );
      $meeting_organizer = $data[9];
      $required_attendies = $data[10];
      $optional_attendies = $data[11];
      $meeting_resources = $data[12];
      $billing_information = $data[13];
      $categories = addslashes ( str_replace ( ';', ',', $data[14] ) );
      $description = addslashes ( $data[15] );
      $location = addslashes ( $data[16] );
      $mileage = $data[17];
      $priority = $data[18];
      $class = ( int ) toBoolean ( $data[19] );
      $sensitivity = $data[20];
      $show_time_as = $data[21];

      /*
       * Start New Section For Outlook CSV
       */
      // $tmp_data['RecordID']  = ;
      $tmp_data['StartTime'] = $start; // In seconds since 1970 (Unix Epoch)
      $tmp_data['EndTime'] = $end; // In seconds since 1970 (Unix Epoch)
      $tmp_data['Summary'] = $subject; // Summary of event (string)
      $tmp_data['Duration'] =
        dateDifference ( $start, $end, 1 ); // How long the event lasts (in minutes)
      $tmp_data['Description'] = $description; // Full Description (string)
      $tmp_data['Location'] = $location; // Location (string)
      $tmp_data['AllDay'] = $all_day_event; // 1 = true  0 = false
      $tmp_data['Class'] = ( $class == 1 ? 'R': 'P' );
      $tmp_data['Categories'] = get_categories_id_byname ( $categories );
      $tmp_data['AlarmSet'] = $remind_on_off; // 1 = true  0 = false
      $tmp_data['ADate'] = $reminder; // Date/Time of Alarm
      $tmp_data['AAction'] = 'EMAIL'; // The default action
      $tmp_data['CalendarType'] = 'VEVENT'; // The default type

      $outlookcsv_data[] = $tmp_data;
    } // End while
    fclose ( $fd );
  }

  return $outlookcsv_data;
}

function dateDifference ( $start_timestamp, $end_timestamp, $unit = 0 ) {
  $days_seconds_star = ( 23 * 56 * 60 ) + 4.091; // Star Day
  $days_seconds_sun = 86400; // Sun Day
  $difference_seconds = $end_timestamp - $start_timestamp;
  switch ( $unit ) {
    case 3: // Days
      $difference_days = round ( ( $difference_seconds / $days_seconds_sun ), 2 );
      return 'approx. ' . $difference_hours . ' Days';
    case 2: // Hours
      $difference_hours = round ( ( $difference_seconds / 3600 ), 2 );
      return 'approx. ' . $difference_hours . ' Hours';
      break;
    case 1: // Minutes
      $difference_minutes = round ( ( $difference_seconds / 60 ), 2 );
      return $difference_minutes;
      break;
    default: // Seconds
      return $difference_seconds . ' Second'
       . ( $difference_seconds != 1 ? 's' : '' );
  }
}

function toBoolean ( $string ) {
  return in_array ( strtoupper ( $string ), array ( 'TRUE', 'T', '1', 'TR' ) );
}

?>

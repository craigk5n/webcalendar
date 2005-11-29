<?php
/*
 * $Id$
 *
 * Description:
 * Handler for exporting webcalendar events to various formats.
 *
 * Comments:
 * All-day events and untimed events are treated differently.  An
 * all-day event is a 12am event with duration 24 hours.  We store
 * untimed events with a start time of -1 in the webcalendar database.
 *
 * TODO:
 * Add support for categories
 *
 *********************************************************************/
include_once 'includes/init.php';
include_once 'includes/xcal.php';

if ( ! empty ( $PROGRAM_VERSION ) ) {
  $prodid = "PRODID:-//WebCalendar-$PROGRAM_VERSION";
} else if ( preg_match ( "/v(\d\S+) /", $GLOBALS['PROGRAM_NAME'], $matches ) ) {
  $prodid = "PRODID:-//WebCalendar-$matches[1]";
} else {
  $prodid = "PRODID:-//WebCalendar-UnknownVersion";
}

if ( empty ( $user ) || $user == $login )
  load_user_layers ();


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

  // All times are now stored as GMT
  //TODO Palm uses local time, so convert to users' time
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
  $res = dbi_query( "SELECT * FROM $from WHERE cal_id='$id'". ( $where?"AND ( $where );":';') );
  if ( $res )
    return ( dbi_fetch_row($res) );
  else
    return ( false );
} //end function

function export_pilot_csv ($id) {
  /* to be imported to a Palm with:
   *  pilot-datebook -r csv -f webcalendar-export.txt -w hotsync
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
    echo ($row[7] == 'R' || $row[7] == 'C')?'16,':'0,';
    // category (int: 0=Unfiled)
    echo '0,';
    // untimed (int: 0=Appointment, 1=Untimed)
    // note: Palm "Untimed" is WebCalendar "AllDay"
    if ( $row[4] < 0 ) {
      echo
 '1,',    // untimed
 substr($row[3],0,4), '-', // beginDate (str: YYYY-MM-DD) + beginTime
 substr($row[3],4,2), '-',
 substr($row[3],6,2), ',00:00:00,',
 substr($row[3],0,4), '-', // endDate + endTime
 substr($row[3],4,2), '-',
 substr($row[3],6,2), ',00:00:00,';
    } else {
      echo '0,', // untimed
 pilot_date_time($row[3], $row[4], 0, ','), ',', // beginDate,beginTime
 pilot_date_time($row[3], $row[4], $row[8], ','), ','; //endDate,endTime
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
    //     21..27 4th,  28-34 last week)
    // repeatWeekdays (int: add - 1=Sun,2=Mon,4=Tue,8=Wed,16=Thu,32=Fri,64=Sat)
    // repeatWeekstart (int)
    $ext = get_cal_ent_extras($row[0], 'webcal_entry_repeats');
    if ( $ext ) {
      switch ( $ext[1] ) {
 case 'daily':  $repType = 1; break;
 case 'weekly':  $repType = 2; break;
 case 'monthlyByDate': $repType = 3; break;
 case 'monthlyByDay': $repType = 4; break;
 case 'yearly':  $repType = 5; break;
 default:   $repType = 0;
      }
    } else $repType = 0;
    if ( $repType ) {
      echo $repType, ',';  // repeatType
      if ( $ext[2] ) {
 echo '0,',    // repeatForever
   substr($ext[2],0,4), '-', // repeatEnd
   substr($ext[2],4,2), '-',
   substr($ext[2],6,2), ' 00:00:00,';
      } else
 echo '1,,'; // repeatForever,repeatEnd
      echo $ext[3], ',';// repeatFrequency
      switch ( $repType ) {
 case 2: // weekly
   echo '0,', bindec(strtr(strrev($ext[4]),'yn','10')) ,",1\n";
   break;
 case 3: // monthly/weekday
  // repeatDay (0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
  // 21..27 4th,  28-34 last week)
  echo floor( substr($row[3], 6, 2) / 7) *7
    + date( 'w', date_to_epoch($row[3]) ), ",0,0\n";
  break;
 case 1: // daily
 case 4: // monthly
 case 5: // yearly
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

$id = getIntValue  ( 'id', true );
$format = getValue  ( 'format' );
if ( $format != 'ical' && $format != 'vcal' && $format != 'pilot-csv' &&
  $format != 'pilot-text' )
  die_miserable_death ( "Invalid format '" . $format . "'" );

$use_all_dates = getPostValue  ( 'use_all_dates' );
if ( $use_all_dates != 'y' )
  $use_all_dates = '';

$include_layers = getPostValue  ( 'include_layers' );
if ( $include_layers != 'y' )
 $include_layers = '';

$fromyear = getIntValue  ( 'fromyear', true );
$frommonth = getIntValue  ( 'frommonth', true );
$fromday = getIntValue  ( 'fromday', true );
$endyear = getIntValue  ( 'endyear', true );
$endmonth = getIntValue  ( 'endmonth', true );
$endday = getIntValue  ( 'endday', true );
$modyear = getIntValue  ( 'modyear', true );
$modmonth = getIntValue  ( 'modmonth', true );
$modday = getIntValue  ( 'modday', true );

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
?>
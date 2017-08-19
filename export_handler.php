<?php
/**
 * Description:
 * Handler for exporting webcalendar events to various formats.
 *
 * Comments:
 * All-day events and untimed events are treated differently. An
 * all-day event is a 12am event with duration 24 hours. We store
 * untimed events with a start time of -1 in the webcalendar database.
 *
 * TODO:
 * Add support for categories for other than ical
 *
 *********************************************************************/
include_once 'includes/init.php';
require_valid_referring_url ();
include_once 'includes/xcal.php';

$user = getPostValue ( 'user' );
if ( empty ( $user ) || $user == $login )
  load_user_layers();

// Convert time in ("hhmmss") format, plus duration (as a number of minutes),
// to end time ($hour = number of hours, $min = number of minutes).
// FIXME: doesn't handle wrap to next day correctly.
function get_end_time ( $time, $duration, &$hour, &$min ) {
  $hour = intval ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $hour = $minutes / 60;
  $min = $minutes % 60;
}

// Convert calendar date to a format suitable for the
// install-datebook utility (part of pilot-link)
function pilot_date_time ( $date, $time, $duration, $csv = false ) {
  $mday = $date % 100;
  $month = intval ( ( $date / 100 ) % 100 );
  $year = intval ( $date / 10000 );
  get_end_time ( $time, $duration, $hour, $min );

  // All times are now stored as GMT.
  // TODO Palm uses local time, so convert to users' time.
  $tz_offset = date ( 'Z' ); // in seconds
  $tzh = intval ( $tz_offset / 3600 );
  $tzm = intval ( ( $tz_offset / 60 ) % 60 );
  $tzsign = '+';

  if ( $tzh < 0 ) {
    $tzsign = '-';
    $tzh = abs ( $tzh );
  }

 return ( $csv
  ? sprintf ( "%04d-%02d-%02d%s%02d:%02d:00",
      $year, $month, $mday, $csv, $hour, $min )
  : sprintf ( "%04d/%02d/%02d %02d%02d  GMT%s%d%02d",
      $year, $month, $mday, $hour, $min, $tzsign, $tzh, $tzm ) );
}

function export_install_datebook ( $id ) {
  $res = export_get_event_entry ( $id );

  while ( $row = dbi_fetch_row ( $res ) ) {
    $start_time = pilot_date_time ( $row[3], $row[4], 0 );
    $end_time = pilot_date_time ( $row[3], $row[4], $row[8] );
    printf ( "%s\t%s\t\t%s\n", $start_time, $end_time, $row[1] );
    echo 'Start time: ' . $start_time . '
End time: ' . $end_time . '
Duration: ' . $row[8] . '
Name: ' . $row[1] . "\n";
  }
}

function get_cal_ent_extras ( $id, $from, $where = false ) {
  $res = dbi_execute ( 'SELECT * FROM ' . $from . 'WHERE cal_id = ?'
     . ( $where ? '
    AND ( ' . $where . ' )' : '' ), [$id] );
  return ( $res ? ( dbi_fetch_row ( $res ) ) : ( false ) );
}
/**
 * export_pilot_csv (needs description)
 */
function export_pilot_csv( $id ) {
  /* To be imported to a Palm with:
   * pilot-datebook -r csv -f webcalendar-export.txt -w hotsync
   */

  $res = export_get_event_entry ( $id );

  echo 'uid,attributes,category,untimed,beginDate,beginTime,endDate,endTime,'
   . 'description,note,alarm,advance,advanceUnit,repeatType,repeatForever,'
   . 'repeatEnd,repeatFrequency,repeatDay,repeatWeekdays,repeatWeekstart' . "\n";
  while ( $row = dbi_fetch_row ( $res ) ) {
    // uid (long)
    echo $row[0], ','
    /*
     attributes (int)
      128 = 0x80 : Deleted
       64 = 0x40 : Dirty
       32 = 0x20 : Busy
       16 = 0x10 : Secret/Private
     */
     . ( $row[7] == 'C' || $row[7] == 'R' ? '16,' : '0,' )
    // category (int: 0=Unfiled)
    . '0,';
    // untimed (int: 0=Appointment, 1=Untimed)
    // Note: Palm "Untimed" is WebCalendar "AllDay".
    if ( $row[4] < 0 ) {
      echo
      '1,', // untimed
      substr ( $row[3], 0, 4 ), '-', // beginDate (str: YYYY-MM-DD) + beginTime
      substr ( $row[3], 4, 2 ), '-',
      substr ( $row[3], 6, 2 ), ',00:00:00,',
      substr ( $row[3], 0, 4 ), '-', // endDate + endTime
      substr ( $row[3], 4, 2 ), '-',
      substr ( $row[3], 6, 2 ), ',00:00:00,';
    } else {
      echo '0,', // untimed
      pilot_date_time ( $row[3], $row[4], 0, ',' ), ',', // beginDate,beginTime
      pilot_date_time ( $row[3], $row[4], $row[8], ',' ), ','; //endDate,endTime
    } //end if ( $row[4] < 0 )
    // description (str)
    echo '"', preg_replace ( '/\x0D?\n/', "\\n", $row[1] ), '",'
    // note (str)
    . '"', preg_replace ( '/\x0D?\n/', "\\n", $row[9] ), '",';
    // alarm, advance, advanceUnit
    // alarm (int: 0=no alarm, 1=alarm)
    // FIXME: verify if WebCal. DB interpreted correctly
    // advance (int), advanceUnit (int: 0=minutes, 1=hours, 2=days)
    // FIXME: better adjust unit
    $ext = get_cal_ent_extras ( $row[0], 'webcal_reminders' );
    if ( $ext )
      echo '1,', $ext[2], ',0,';
    else
      echo '0,0,0,';
    // repeat:
    // repeatType (int: 0=none, 1=daily, 2=weekly, 3=monthly, 4=monthly/weekday,
    // repeatForever (int: 0=not forever, 1=forever)                   5=yearly)
    // repeatEnd (time)
    // repeatFrequency (int)
    // repeatDay (int: day# or 0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
    // 21..27 4th, 28-34 last week)
    // repeatWeekdays (int: add - 1=Sun,2=Mon,4=Tue,8=Wed,16=Thu,32=Fri,64=Sat)
    // repeatWeekstart (int)
    $ext = get_cal_ent_extras ( $row[0], 'webcal_entry_repeats' );
    if ( $ext ) {
      switch ( $ext[1] ) {
        case 'daily':
          $repType = 1;
          break;
        case 'weekly':
          $repType = 2;
          break;
        case 'monthlyByDate':
          $repType = 3;
          break;
        case 'monthlyByDay':
          $repType = 4;
          break;
        case 'yearly':
          $repType = 5;
          break;
        default:
          $repType = 0;
      }
    } else
      $repType = 0;

    if ( $repType ) {
      echo $repType, ','; // repeatType
      if ( $ext[2] ) {
        echo '0,', // repeatForever
        substr ( $ext[2], 0, 4 ), '-', // repeatEnd
        substr ( $ext[2], 4, 2 ), '-',
        substr ( $ext[2], 6, 2 ), ' 00:00:00,';
      } else
        echo '1,,'; // repeatForever,repeatEnd

      echo $ext[3], ','; // repeatFrequency
      switch ( $repType ) {
        case 2: // weekly
          echo '0,', bindec ( strtr ( strrev ( $ext[4] ), 'yn', '10' ) ), ",1\n";
          break;
        case 3: // monthly/weekday
          // repeatDay (0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
          // 21..27 4th, 28-34 last week)
          echo floor ( substr ( $row[3], 6, 2 ) / 7 ) * 7 + date ( 'w',
            date_to_epoch ( $row[3] ) ), ",0,0\n";
          break;
        case 1: // daily
        case 4: // monthly
        case 5: // yearly
          echo "0,0,0\n";
      } //end switch
    } else
      echo "0,0,,0,0,0,0\n";
  } //end if ( $repType )
}
/**
 * transmit_header (needs description)
 */
function transmit_header( $mime, $file ) {
  // header ( 'Content-Type: application/octet-stream' );
  header ( 'Content-Type: ' . $mime );
  header ( 'Content-Disposition: attachment; filename="' . $file . '"' );
  header ( 'Pragma: private' );
  header ( 'Cache-control: private, must-revalidate' );
}

/* ********************************** */
/*              Let's go              */
/* ********************************** */

$format = getValue ( 'format' );
if ( $format != 'ical' && $format != 'vcal' && $format != 'pilot-csv' &&
  $format != 'pilot-text' )
  die_miserable_death ( 'Invalid format "' . htmlspecialchars($format) . '"' );
$id = getValue ( 'id', '-?[0-9]+', true );

$use_all_dates = getPostValue ( 'use_all_dates' );
if ( strtolower ( $use_all_dates ) != 'y' )
  $use_all_dates = '';

$include_layers = getPostValue ( 'include_layers' );
if ( strtolower ( $include_layers ) != 'y' )
  $include_layers = '';

$include_deleted = getPostValue ( 'include_deleted' );
if ( strtolower ( $include_deleted ) != 'y' )
  $include_deleted = '';

$cat_filter = getPostValue ( 'cat_filter' );
if ( $cat_filter == 0 )
  $cat_filter = '';

$endday = getValue ( 'endday', '-?[0-9]+', true );
$endmonth = getValue ( 'endmonth', '-?[0-9]+', true );
$endyear = getValue ( 'endyear', '-?[0-9]+', true );
$fromday = getValue ( 'fromday', '-?[0-9]+', true );
$frommonth = getValue ( 'frommonth', '-?[0-9]+', true );
$fromyear = getValue ( 'fromyear', '-?[0-9]+', true );
$modday = getValue ( 'modday', '-?[0-9]+', true );
$modmonth = getValue ( 'modmonth', '-?[0-9]+', true );
$modyear = getValue ( 'modyear', '-?[0-9]+', true );

$startdate = sprintf ( "%04d%02d%02d", $fromyear, $frommonth, $fromday );
$enddate = sprintf ( "%04d%02d%02d", $endyear, $endmonth, $endday );
$moddate = sprintf ( "%04d%02d%02d", $modyear, $modmonth, $modday );

mt_srand ( ( float ) microtime() * 1000000 );

if ( empty ( $id ) )
  $id = 'all';

$outputName = 'webcalendar-' . "$login-$id";
if ( substr ( $format, 0, 4 ) == 'ical' ) {
  transmit_header ( 'text/calendar', $outputName . '.ics' );
  export_ical ( $id );
} elseif ( $format == 'vcal' ) {
  transmit_header ( 'text/x-vCalendar', $outputName . '.vcs' );
  export_vcal ( $id );
} elseif ( $format == 'pilot-csv' ) {
  transmit_header ( 'text/csv', $outputName . '.csv' );
  export_pilot_csv ( $id );
} elseif ( $format == 'pilot-text' ) {
  transmit_header ( 'text/plain', $outputName . '.txt' );
  export_install_datebook ( $id );
} else {
  print_header();
  $errorStr = translate ( 'Error' );
  echo '
    <h2>' . translate ( 'Export' ) . ' ' . $errorStr . '</h2>
    <span class="bold">' . $errorStr . ':</span> '
   . translate( 'export format not defined or incorrect.' ) . '<br />
    ' . print_trailer();
} //end if ($format == "ical")

?>

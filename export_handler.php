<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
include "includes/validate.inc";
include "includes/translate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

$error = "";

// We don't handle exporting repeating events since the install-datebook
// utility doesn't support repeating events (yet)
$sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
  "webcal_entry.cal_priority, webcal_entry.cal_date, " .
  "webcal_entry.cal_time, " .
  "webcal_entry_user.cal_status, webcal_entry.cal_create_by, " .
  "webcal_entry.cal_access, webcal_entry.cal_duration, " .
  "webcal_entry.cal_description " .
  "FROM webcal_entry, webcal_entry_user " .
  "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
  "webcal_entry_user.cal_login = '" . $login . "'";
if (!$use_all_dates)
{
  $startdate = sprintf ( "%04d%02d%02d", $fromyear, $frommonth, $fromday );
  $enddate = sprintf ( "%04d%02d%02d", $endyear, $endmonth, $endday );
  $sql .= " AND webcal_entry.cal_date >= $startdate " .
    "AND webcal_entry.cal_date <= $enddate";
  $moddate = sprintf ( "%04d%02d%02d", $modyear, $modmonth, $modday );
  $sql .= " AND webcal_entry.cal_mod_date >= $moddate";
}
if ( $DISPLAY_UNAPPROVED == "N" )
  $sql .= " AND webcal_entry_user.cal_status = 'A'";
$sql .= " ORDER BY webcal_entry.cal_date";

$res = dbi_query ( $sql );

function export_ical ($res) {
  echo "BEGIN:VCALENDAR\n";
  echo "PRODID:-//WebCalendar\n";
  echo "VERSION:0.9\n";

  while ( $row = dbi_fetch_row ( $res ) ) {
    $id = $row[0];
    $name = $row[1];
    $priority = $row[2];
    $date = $row[3];
    $time = $row[4];
    $status = $row[5];
    $create_by = $row[6];
    $access = $row[7];
    $duration = "T" . $row[8] . "M";
    $description = $row[9];

    $name = preg_replace("/\n/", "\\n", $name);

    // FIXME: break long values into continuation lines

    echo "BEGIN:VEVENT\n";
    echo "X-WEBCALENDAR-ID:$id\n";
    echo "SUMMARY:$name\n";
    if ( $time == -1 )
    {
      // all day event
      $hour = 0;
      $min = 0;
      $duration = "1D";
    }
    else
    {
      get_end_time ( $time, 0, $hour, $min );
    }
    printf ("DTSTART:%08dT%02d%02d00\n", $date, $hour, $min);
    echo "DURATION:P$duration\n";
    // FIXME: handle recurrence
    // FIXME: handle alarms
    // FIXME: handle description
    echo "END:VEVENT\n";
  }

  echo "END:VCALENDAR\n";
}

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
function pilot_date_time ( $date, $time, $duration ) {
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
  return sprintf ( "%04d/%02d/%02d %02d%02d  GMT%s%d%02d",
    $year, $month, $mday, $hour, $min, $tzsign, $tzh, $tzm );
}

function export_install_datebook ($res) {
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
}

// Set the output to be text.
header ( "Content-Type: text/plain" );
// Even though this is text/plain, use "application/octet-stream", so the
// use is prompted to save the file.
//header ( "Content-Type: application/octet-stream" );

//echo "SQL: $sql\n";

if ($format == "ical") {
  export_ical ( $res );
}
else {
  export_install_datebook ( $res );
}

exit;
?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Export")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Export") . " " . etranslate("Error")?></FONT></H2>


<B><php etranslate("Error")?>:</B> <?php echo $error?>

<P>

<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>

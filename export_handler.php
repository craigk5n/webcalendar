<?php
include_once 'includes/init.php';

$error = "";

// We exporting repeating events only with the pilot-datebook CSV format
$sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name " .
  ", webcal_entry.cal_priority, webcal_entry.cal_date " .
  ", webcal_entry.cal_time " .
  ", webcal_entry_user.cal_status, webcal_entry.cal_create_by " .
  ", webcal_entry.cal_access, webcal_entry.cal_duration " .
  ", webcal_entry.cal_description " .
  ", webcal_entry_user.cal_category " .
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
if ( $DISPLAY_UNAPPROVED == "N" || $login == "__public__" )
  $sql .= " AND webcal_entry_user.cal_status = 'A'";
else
  $sql .= " AND webcal_entry_user.cal_status IN ('W','A')";
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

function get_cal_ent_extras($id, $from, $where = false) {

	$res = dbi_query( "SELECT * FROM `$from` WHERE cal_id='$id'".
																				( $where?"AND ( $where );":';') );
	if ( $res )
  	return ( dbi_fetch_row($res) );
	else
		return ( false );

}

function export_pilot_csv ($res) {
	/* to be imported to a Palm with:
	 *		pilot-datebook -r csv -f webcalendar-export.txt -w hotsync
	 */
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
				'1,',											// untimed
				substr($row[3],0,4), '-',	// beginDate (str: YYYY-MM-DD) + beginTime
					substr($row[3],4,2), '-',
					substr($row[3],6,2), ',00:00:00,',
				substr($row[3],0,4), '-',	// endDate + endTime
					substr($row[3],4,2), '-',
					substr($row[3],6,2), ',00:00:00,';
		} else {
			echo
				'0,',																							// untimed
			  pilot_date_time($row[3], $row[4], 0, ','), ',',	// beginDate,beginTime
				pilot_date_time($row[3], $row[4], $row[8], ','), ',';	//endDate,endTime
		}
		// description (str)
		echo '"', preg_replace("/\x0D?\n/", "\\n", $row[1]), '",';
		// note (str)
		echo '"', preg_replace("/\x0D?\n/", "\\n", $row[9]), '",';
		// alarm, advance, advanceUnit
		// alarm (int: 0=no alarm, 1=alarm)
			// FIXME: verify if WebCal. DB interpreted correctly
		// advance (int), advanceUnit (int: 0=minutes, 1=hours, 2=days)
			// FIXME: better adjust unit
		$ext = get_cal_ent_extras($row[0],
			'webcal_site_extras', "cal_name = 'Reminder' AND cal_remind = 1");
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
		//																						21..27 4th,  28-34 last week)
		// repeatWeekdays (int: add - 1=Sun,2=Mon,4=Tue,8=Wed,16=Thu,32=Fri,64=Sat)
		// repeatWeekstart (int)
		$ext = get_cal_ent_extras($row[0], 'webcal_entry_repeats');
		if ( $ext ) {
			switch ( $ext[1] ) {
				case 'daily':					$repType = 1; break;
				case 'weekly':				$repType = 2; break;
				case 'monthlyByDate':	$repType = 3; break;
				case 'monthlyByDay':	$repType = 4; break;
				case 'yearly':				$repType = 5; break;
				default:							$repType = 0;
			}
		} else $repType = 0;
		if ( $repType ) {
			echo $repType, ',';						// repeatType
			if ( $ext[2] ) {
				echo '0,', 									// repeatForever
					substr($ext[2],0,4), '-',	// repeatEnd
					substr($ext[2],4,2), '-',
					substr($ext[2],6,2), ' 00:00:00,';
			} else
				echo '1,,';									// repeatForever,repeatEnd
			echo $ext[3], ',';						// repeatFrequency
			switch ( $repType ) {
				case 2:	// weekly
					echo '0,', bindec(strtr(strrev($ext[4]),'yn','10')) ,",1\n";
					break;
				case 3:	// monthly/weekday
					// repeatDay (0..6=Sun..Sat 1st, 7..13 2nd, 14..20 3rd,
					//															21..27 4th,  28-34 last week)
					echo floor( substr($row[3], 6, 2) / 7) *7
								+ date( 'w', date_to_epoch($row[3]) ), ",0,0\n";
					break;
				case 1:	// daily
				case 4:	// monthly
				case 5:	// yearly
					echo "0,0,0\n";
			}
		} else
			echo "0,0,,0,0,0,0\n";
  }
}

function transmit_header($mime) {
  header ( "Content-Type: application/octet-stream" );
  //header ( "Content-Type: $mime" );
	//if ( eregi('MSIE', $HTTP_USER_AGENT) ) {
	//	header('Content-Disposition: inline; filename="webcalendar-export.txt"');
	//	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	//	header('Pragma: public');
	//} else {
	//	header('Content-Disposition: attachment; filename="webcalendar-export.txt"');
	//	header('Pragma: no-cache');
	//}
}

if ($format == "ical") {
	transmit_header('text/calendar');
  export_ical ( $res );
}
elseif ($format == "pilot-csv") {
	transmit_header('text/csv');
  export_pilot_csv ( $res );
}
else {
	transmit_header('text/plain');
  export_install_datebook ( $res );
}


exit;
print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Export") . " " . etranslate("Error")?></FONT></H2>

<B><php etranslate("Error")?>:</B> <?php echo $error?>

<P>

<?php include_once "includes/trailer.php"; ?>

</BODY>
</HTML>
<?php

include_once 'includes/init.php';
include_once 'includes/site_extras.php';
$error = '';
print_header();

if ($HTTP_POST_FILES['FileName']['size'] > 0) {
  switch ($ImportType) {

// ADD New modules here:

//    case MODULE:
//      include "import_module.php";
//      $data = parse_module($HTTP_POST_FILES['FileName']['tmp_name']);
//      break;
//
    case PALMDESKTOP:
      include "import_palmdesktop.php";
      if (delete_palm_events($login) != 1) $errormsg = "Error deleting palm events from webcalendar.";
      $data = parse_palmdesktop($HTTP_POST_FILES['FileName']['tmp_name'], $exc_private);
      break;

    case VCAL:
      include "import_vcal.php";
      $data = parse_vcal($HTTP_POST_FILES['FileName']['tmp_name']);
      break;
  }

  $count_con = $count_suc = $error_num = 0;
  if (! empty ($data) && empty ($errormsg) ) {
    import_data($data);
    echo "<P><B>Datebook Import Report:</B><BR>\n";
    echo "Successful Imports: $count_suc<BR>\n";
    if ( empty ( $allow_conflicts )) echo "Conflicting Events: $count_con<BR>\n";
    echo "Errors: $error_num<BR>\n<BR>";
    $url = sprintf ( "%s.php?date=%04d%02d%02d",$STARTVIEW, $year, $month, $day );
    echo "  <A CLASS=\"navlinks\" HREF=\"$url\">" . translate("Back To My Calendar") . "</A></p>\n";
  } elseif ($errormsg) {
    echo "<P><B>ERROR:</B> $errormsg<BR>\n";
  } else {
    echo "<P><B>ERROR:</B> There was an error parsing the file or no events were returned.<BR>\n";
  }
} else {
  echo "<P><B>ERROR:</B> The file contained no data.<BR>\n";
}

include "includes/trailer.php";
echo "</BODY>\n</HTML>";


/* ====== Functions ====== */

/*
// Input time format "235900", duration is minutes
function add_duration ( $time, $duration ) {
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d%02d00", $h, $m );
  //echo "add_duration ( $time, $duration ) = $ret <BR>";
  return $ret;
}

// check to see if two events overlap
// time1 and time2 should be an integer like 235900
// duration1 and duration2 are integers in minutes
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  //echo "times_overlap ( $time1, $duration1, $time2, $duration2 )<BR>";
  $hour1 = (int) ( $time1 / 10000 );
  $min1 = ( $time1 / 100 ) % 100;
  $hour2 = (int) ( $time2 / 10000 );
  $min2 = ( $time2 / 100 ) % 100;
  // convert to minutes since midnight
  // remove 1 minute from duration so 9AM-10AM will not conflict with 10AM-11AM
  if ( $duration1 > 0 )
    $duration1 -= 1;
  if ( $duration2 > 0 )
    $duration2 -= 1;
  $tmins1start = $hour1 * 60 + $min1;
  $tmins1end = $tmins1start + $duration1;
  $tmins2start = $hour2 * 60 + $min2;
  $tmins2end = $tmins2start + $duration2;
  if ( ( $tmins1start >= $tmins2end ) || ( $tmins2start >= $tmins1end ) )
    return false;
  return true;

}
*/

/* Import the data structure
$Entry[RecordID]           =  Record ID (in the Palm) ** only required for palm desktop
$Entry[StartTime]          =  In seconds since 1970 (Unix Epoch)
$Entry[EndTime]            =  In seconds since 1970 (Unix Epoch)
$Entry[Summary]            =  Summary of event (string)
$Entry[Duration]           =  How long the event lasts (in minutes)
$Entry[Description]        =  Full Description (string)
$Entry[Untimed]            =  1 = true  0 = false
$Entry[Private]            =  1 = true  0 = false
$Entry[Category]           =  useless for Palm (not supported yet)
$Entry[AlarmSet]           =  1 = true  0 = false
$Entry[AlarmAdvanceAmount] =  How many units in AlarmAdvanceType (-1 means not set)
$Entry[AlarmAdvanceType]   =  Units: (0=minutes, 1=hours, 2=days)
$Entry[Repeat]             =  Array containing repeat information (if repeat)
$Entry[Repeat][Interval]   =  1=daily,2=weekly,3=MonthlyByDate,4=MonthlyByDay,5=Yearly
$Entry[Repeat][Frequency]  =  How often event occurs. (1=every, 2=every other,etc.)
$Entry[Repeat][EndTime]    =  When the repeat ends (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][Exceptions] =  Exceptions to the repeat (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][RepeatDays] =  For Weekly: What days to repeat on (7 characters...y or n for each day)
*/
//
// TODO: Figure out category from $Entry[Category] or have a drop-down asking which
//       category to import into.
//
function import_data($data) {
  global $login, $count_con, $count_suc, $error_num, $ImportType, $LOG_CREATE;
  global $single_user, $single_user_login, $allow_conflicts;

  foreach ( $data as $Entry ){

    $priority = 2;
    $participants[0] = $login;

    // Some additional date/time info
    $START = localtime($Entry[StartTime]);
    $END   = localtime($Entry[EndTime]);
    $Entry[StartMinute]        = sprintf ("%02d",$START[1]);
    $Entry[StartHour]          = sprintf ("%02d",$START[2]);
    $Entry[StartDay]           = sprintf ("%02d",$START[3]);
    $Entry[StartMonth]         = sprintf ("%02d",$START[4] + 1);
    $Entry[StartYear]          = sprintf ("%04d",$START[5] + 1900);
    $Entry[EndMinute]          = sprintf ("%02d",$END[1]);
    $Entry[EndHour]            = sprintf ("%02d",$END[2]);
    $Entry[EndDay]             = sprintf ("%02d",$END[3]);
    $Entry[EndMonth]           = sprintf ("%02d",$END[4] + 1);
    $Entry[EndYear]            = sprintf ("%04d",$END[5] + 1900);

    // Check for untimed
    if ($Entry[Untimed] == 1) {
      $Entry[StartMinute] = '';
      $Entry[StartHour] = '';
      $Entry[EndMinute] = '';
      $Entry[EndHour] = '';
    }

    // first check for any schedule conflicts
    if ( empty ( $allow_conflicts )  &&  ( $Entry[Duration] != 0 )) {
      $date = mktime (0,0,0,$Entry[StartMonth],$Entry[StartDay],$Entry[StartYear]);
      $endt =  (! empty ( $Entry[Repeat][EndTime] ) ) ? $Entry[Repeat][EndTime] : 'NULL';
      $dayst =  (! empty ( $Entry[Repeat][RepeatDays] ) ) ? $Entry[Repeat][RepeatDays] : "nnnnnnn";

      $ex_days = array ();
      if ( ! empty ( $Entry[Repeat][Exceptions] ) ) {
        foreach ($Entry[Repeat][Exceptions] as $ex_date) {
          $ex_days[] = date("Ymd",$ex_date);
        }
      }

      $dates = get_all_dates($date, RepeatType($Entry[Repeat][Interval]), $endt, $dayst, $ex_days, $Entry[Repeat][Frequency]);
      $overlap = overlap ( $dates, $Entry[Duration], $Entry[StartHour], $Entry[StartMinute], $participants, $login, 0 );
    }

    if ( empty ( $error ) && ! empty ( $overlap ) ) {
      $error = translate("The following conflicts with the suggested time").":<UL>$overlap</UL>";
    }

    if ( empty ( $error ) ) {

      // Add the Event
      $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $id = $row[0] + 1;
        dbi_free_result ( $res );
      } else {
        $id = 1;
        //$error = "Unable to select MAX cal_id: " . dbi_error () . "<P><B>SQL:</B> $sql";
        //break;
      }


      $sql = "INSERT INTO webcal_entry ( cal_id, cal_create_by, cal_date, " .
        "cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, " .
        "cal_access, cal_type, cal_name, cal_description ) " .
        "VALUES ( $id, '$login', ";

      $sql .= sprintf ( "%04d%02d%02d, ", $Entry[StartYear],$Entry[StartMonth],$Entry[StartDay]);
      $sql .=  ($Entry[Untimed] == 1) ? "-1, " : sprintf ( "%02d%02d00, ", $Entry[StartHour],$Entry[StartMinute]);
      $sql .= date ( "Ymd" ) . ", " . date ( "Gis" ) . ", ";
      $sql .= sprintf ( "%d, ", $Entry[Duration] );
      $sql .= "$priority, ";
      $sql .=  ($Entry[Private] == 1) ? "'R', " : "'P', ";
      $sql .=  ($Entry[Repeat]) ? "'M', " : "'E', ";
      if ( strlen ( $Entry[Summary] ) == 0 )
        $Entry[Summary] = translate("Unnamed Event");
      $sql .= "'" . $Entry[Summary] .  "', ";
      if ( strlen ( $Entry[Description] ) == 0 )
        $Entry[Description] = $Entry[Summary];
      $Entry[Description] = str_replace ( "\\,", ",", $Entry[Description] );
      $Entry[Description] = str_replace ( "\\n", "\n", $Entry[Description] );
      $Entry[Description] = str_replace ( "'", "\\'", $Entry[Description] );
      // limit length to 1024 chars since we setup tables that way
      if ( strlen ( $Entry[Description] ) >= 1024 )
        $Entry[Description] = substr ( $Entry[Description], 0, 1019 ) . "...";
      $sql .= "'" . $Entry[Description] . "' )";
      //echo "Summary:<p>" . nl2br ( htmlspecialchars ( $Entry[Summary] ) ) . "<p>";
      //echo "Description:<p>" . nl2br ( htmlspecialchars ( $Entry[Description] ) ); exit;

      if ( empty ( $error ) ) {
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
          break;
        }
      }

      // log add/update
      activity_log ( $id, $login, $login, $LOG_CREATE, "" );

      if ( $single_user == "Y" ) {
        $participants[0] = $single_user_login;
      }

      // Now add to webcal_import_data
      if ($ImportType == "PALMDESKTOP") {
        $sql = "INSERT INTO webcal_import_data VALUES ( $id, '" . $login . 
          "', 'palm', '" .  $Entry[RecordID] . "' )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
          break;
        }
      }
      else if ($ImportType == "VCAL") {
        $uid = empty ( $Entry[UID] ) ? "null" : "'$Entry[UID]'";
        if ( strlen ( $uid ) > 200 )
          $uid = "null";
        $sql = "INSERT INTO webcal_import_data VALUES ( $id, '" . $login . 
          "', 'vcal', $uid )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
          break;
        }
      }

      // Now add participants
      $status = ( $login == "__public__" ) ? 'W' : 'A';
      if ( empty ( $cat_id ) ) $cat_id = 'NULL';
      $sql = "INSERT INTO webcal_entry_user " .
        "( cal_id, cal_login, cal_status, cal_category ) VALUES ( $id, '" .
        $participants[0] . "', '$status', $cat_id )";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate("Database error") . ": " . dbi_error ();
        break;
      }

      // Add repeating info
      if (! empty ($Entry[Repeat][Interval])) {
        $rpt_type = RepeatType($Entry[Repeat][Interval]);
        $freq = ( $Entry[Repeat][Frequency] ? $Entry[Repeat][Frequency] : 1 );
        if ( strlen ( $Entry[Repeat][EndTime] ) ) {
          $REND   = localtime($Entry[Repeat][EndTime]);
	  $end = sprintf ( "%04d%02d%02d",$REND[5] + 1900,$REND[4] + 1,$REND[3]);
        } else {
          $end = 'NULL';
        }
        $days = (! empty ($Entry[Repeat][RepeatDays])) ? "'".$Entry[Repeat][RepeatDays]."'" : 'NULL';
        $sql = "INSERT INTO webcal_entry_repeats ( cal_id, " .
          "cal_type, cal_end, cal_days, cal_frequency ) VALUES " .
          "( $id, '$rpt_type', $end, $days, $freq )";
        if ( ! dbi_query ( $sql ) ) {
            $error = "Unable to add to webcal_entry_repeats: ".dbi_error ()."<P><B>SQL:</B> $sql";
            break;
        }

        // Repeating Exceptions...
        if ( ! empty ( $Entry[Repeat][Exceptions] ) ) {
          foreach ($Entry[Repeat][Exceptions] as $ex_date) {
            $ex_date = date("Ymd",$ex_date);
            $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date ) VALUES ( $id, $ex_date )";
            if ( ! dbi_query ( $sql ) ) {
              $error = "Unable to add to webcal_entry_repeats_not: ".dbi_error ()."<P><B>SQL:</B> $sql";
              break;
            }
          }
        }
      } // End Repeat

      // Add Alarm info -> site_extras
      if ($Entry[AlarmSet] == 1) {
        $RM = $Entry[AlarmAdvanceAmount];
        if ($Entry[AlarmAdvanceType] == 1){ $RM = $RM * 60; }
        if ($Entry[AlarmAdvanceType] == 2){ $RM = $RM * 60 * 24; }
        $sql = "INSERT INTO webcal_site_extras ( cal_id, " .
          "cal_name, cal_type, cal_remind, cal_data ) VALUES " .
          "( $id, 'Reminder', 7, 1, $RM )";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
        }
      }
    }

    if ( ! empty ($error) && empty ($overlap))  {
      $error_num++;
      echo "<H2><FONT COLOR=\"$H2COLOR\">".etranslate("Error")."</H2></FONT>\n<BLOCKQUOTE>\n";
      echo $error . "</BLOCKQUOTE><BR>\n";
    }

    // Conflicting
    if ( ! empty ( $overlap ) ) {
      echo "<B><FONT COLOR=\"$H2COLOR\">". etranslate("Scheduling Conflict: ");
      $count_con++;
      echo "</B></FONT>";

      if ( $Entry[Duration] > 0 ) {
        $time = display_time ( $Entry[StartHour].$Entry[StartMinute]."00" ) . " - " . display_time ( $Entry[EndHour].$Entry[EndMinute]."00" );
      }
      $dd = $Entry[StartMonth] . "-" .  $Entry[StartDay] . "-" . $Entry[StartYear];
      echo "<A CLASS=\"entry\" HREF=\"view_entry.php?id=$id";
      echo "\" onMouseOver=\"window.status='" . translate("View this entry") ."'; return true;\" onMouseOut=\"window.status=''; return true;\">";
      $Entry[Summary] = str_replace ( "''", "'", $Entry[Summary] );
      $Entry[Summary] = str_replace ( "'", "\\'", $Entry[Summary] );
      echo htmlspecialchars ( $Entry[Summary] );
      echo "</A> (" . $dd . "&nbsp;  " . $time . ")<BR>\n";
      etranslate("conflicts with the following existing calendar entries");
      echo ":<UL>\n" . $overlap . "</UL>\n";
    } else {

    // No Conflict
      echo "<B><FONT COLOR=\"$H2COLOR\">".etranslate("Event Imported:")."</B></FONT>\n";
      $count_suc++;
      if ( $Entry[Duration] > 0 ) {
        $time = display_time ( $Entry[StartHour].$Entry[StartMinute]."00" ) .
          " - " . display_time ( $Entry[EndHour].$Entry[EndMinute]."00" );
      }
      $dateYmd = sprintf ( "%04d%02d%02d", $Entry[StartYear],
        $Entry[StartMonth], $Entry[StartDay] );
      $dd = date_to_str ( $dateYmd );
      echo "<A CLASS=\"entry\" HREF=\"view_entry.php?id=$id";
      echo "\" onMouseOver=\"window.status='" . translate("View this entry") ."'; return true;\" onMouseOut=\"window.status=''; return true;\">";
      $Entry[Summary] = str_replace( "''", "'", $Entry[Summary]);
      echo htmlspecialchars ( $Entry[Summary] );
      echo "</A> (" . $dd . "&nbsp;  " . $time . ")<BR>\n";
    }

    // Reset Variables
    $overlap = $error = $dd = $time = '';
  }
}

// Convert interval to webcal repeat type
function RepeatType ($type) {
  $Repeat = array (0,'daily','weekly','monthlyByDay','monthlyByDate','yearly');
  return $Repeat[$type];
}

?>

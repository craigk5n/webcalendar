<?php
/*
 * $Id: 
 *
 * Description:
 * Loads appropriate import file parser and processes the data returned
 *    Currently supported:
 *      Palmdesktop (dba file)
 *      iCal (ics file)
 *      vCal (vcs file)
 *
 *
 * Notes:
 * User defined inport routines may be used, see example
 *    in the SWITCH statement below
 *
 * Input parameters:
 * FileName: File name specified by user on import.php
 *    calUser: user's calendar to import data into, unless
 *      single user = Y or Admin, caluser will equal logged
 *      in user.
 *    exc_private: exclude private records from Palmdesktop import
 *    overwrite: Overwrite previous import 
 *
 * Security:
 * TBD
 */
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
$error = '';
print_header();

$overwrite = getValue("overwrite");
$doOverwrite = ( empty ( $overwrite ) || $overwrite != 'Y' ) ? false : true;
$numDeleted = 0;

$sqlLog = '';

if ( ! empty ( $_FILES['FileName'] ) ) {
  $file = $_FILES['FileName'];
} else if ( ! empty ( $HTTP_POST_FILES['FileName'] ) ) {
  $file = $HTTP_POST_FILES['FileName'];
}

if ( empty ( $file ) ) {
  echo "No file!<br />";
}

// Handle user
$calUser = getValue ( "calUser" );
if ( ! empty ( $calUser ) ) {
  if ( $single_user == "N" && ! $is_admin ) $calUser = $login;
} else {
  $calUser = $login;
}

if ($file['size'] > 0) {
  switch ($ImportType) {

// ADD New modules here:

//    case 'MODULE':
//      include "import_module.php";
//      $data = parse_module($HTTP_POST_FILES['FileName']['tmp_name']);
//      break;
//
    case 'PALMDESKTOP':
      include "import_palmdesktop.php";
      if (delete_palm_events($login) != 1) $errormsg = "Error deleting palm events from webcalendar.";
      $data = parse_palmdesktop($file['tmp_name'], $exc_private);
      $type = 'palm';
      break;

    case 'VCAL':
      include "import_vcal.php";
      $data = parse_vcal($file['tmp_name']);
      $type = 'vcal';
      break;

    case 'ICAL':
      include "import_ical.php";
      $data = parse_ical($file['tmp_name']);
      $type = 'ical';
      break;
  }

  $count_con = $count_suc = $error_num = 0;
  if (! empty ($data) && empty ($errormsg) ) {
    import_data ( $data, $doOverwrite, $type );
    echo "<p>" . translate("Import Results") . "</p>\n<br /><br />\n" .
      translate("Events successfully imported") . ": $count_suc<br />\n";
    echo translate("Events from prior import marked as deleted") . ": $numDeleted<br />\n";
    if ( empty ( $allow_conflicts ) ) {
      echo translate("Conflicting events") . ": " . $count_con . "<br />\n";
    }
    echo translate ( "Errors" ) . ": $error_num<br><br>\n";
  } elseif (! empty ( $errormsg ) ) {
    echo "<br /><br />\n<b>" . translate("Error") . ":</b> $errormsg<br />\n";
  } else {
    echo "<br /><br />\n<b>" . translate("Error") . ":</b> " .
      translate("There was an error parsing the import file or no events were returned") .
      ".<br />\n";
  }
} else {
 echo "<br /><br />\n<b>" . translate("Error") . ":</b> " .
    translate("The import file contained no data") . ".<br />\n";
}


//echo "<hr />$sqlLog\n";

print_trailer ();
echo "</body>\n</html>";

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
$Entry[Repeat][Interval]   =  1=daily,2=weekly,3=MonthlyByDay,4=MonthlyByDate,5=Yearly,6=monthlyByDayR
$Entry[Repeat][Frequency]  =  How often event occurs. (1=every, 2=every other,etc.)
$Entry[Repeat][EndTime]    =  When the repeat ends (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][Exceptions] =  Exceptions to the repeat (In seconds since 1970 (Unix Epoch))
$Entry[Repeat][RepeatDays] =  For Weekly: What days to repeat on (7 characters...y or n for each day)
*/
//
// TODO: Figure out category from $Entry[Category] or have a drop-down asking which
//       category to import into.
//
function import_data ( $data, $overwrite, $type ) {
  global $login, $count_con, $count_suc, $error_num, $ImportType, $LOG_CREATE;
  global $single_user, $single_user_login, $allow_conflicts;
  global $numDeleted, $errormsg;
  global $calUser, $H2COLOR, $sqlLog;

  $oldUIDs = array ();
  $oldIds = array ();
  $firstEventId = 0;
  $importId = 1;
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

    $priority = 2;
    $participants[0] = $calUser;

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

    // first check for any schedule conflicts
    if ( ( empty ( $allow_conflicts )  || $allow_conflicts == "N" ) &&
      ( $Entry['Duration'] != 0 )) {
      $date = mktime (0,0,0,$Entry['StartMonth'],
        $Entry['StartDay'],$Entry['StartYear']);
      $endt =  (! empty ( $Entry['Repeat']['EndTime'] ) ) ? 
        $Entry['Repeat']['EndTime'] : 'NULL';
      $dayst =  (! empty ( $Entry['Repeat']['RepeatDays'] ) ) ? 
        $Entry['Repeat']['RepeatDays'] : "nnnnnnn";

      $ex_days = array ();
      if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
        foreach ($Entry['Repeat']['Exceptions'] as $ex_date) {
          $ex_days[] = date("Ymd",$ex_date);
        }
      }

      $dates = get_all_dates($date, RepeatType($Entry['Repeat']['Interval']), 
        $endt, $dayst, $ex_days, $Entry['Repeat']['Frequency']);
      $overlap = check_for_conflicts ( $dates, $Entry['Duration'], 
        $Entry['StartHour'], $Entry['StartMinute'], $participants, $login, 0 );
    }

    if ( empty ( $error ) && ! empty ( $overlap ) ) {
      $error = translate("The following conflicts with the suggested time").
        ":<ul>$overlap</ul>\n";
    }

    if ( empty ( $error ) ) {

      $updateMode = false;

      // See if event already is there from prior import.
      // The same UID is used for all events imported at once with iCal.
      // So, we still don't have enough info to find the exact
      // event we want to replace.  We could just delete all
      // existing events that correspond to the UID.
/************************************************************************
  Not sure what to do with this code since I don't know how Palm and vCal
  use the UID stuff yet...
  
      if ( ! empty ( $Entry['UID'] ) ) {
        $res = dbi_query ( "SELECT webcal_import_data.cal_id " .
          "FROM webcal_import_data, webcal_entry_user " .
          "WHERE cal_import_type = 'ical' AND " .
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
************************************************************************/

      // Add the Event
      $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $id = $row[0] + 1;
        dbi_free_result ( $res );
      } else {
        $id = 1;
        //$error = "Unable to select MAX cal_id: " . dbi_error () . 
        //  "<br /><br />\n<b>SQL:</b> $sql";
        //break;
      }
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
      $values[] = date("Ymd");
      $names[] = 'cal_mod_time';
      $values[] = date("Gis");
      $names[] = 'cal_duration';
      $values[] = sprintf ( "%d", $Entry['Duration'] );
      $names[] = 'cal_priority';
      $values[] = $priority;
      $names[] = 'cal_access';
      $values[] = ( ! empty ( $Entry['Private'] ) && 
        $Entry['Private'] == 1) ? "'R'" : "'P'";
      $names[] = 'cal_type';
      $values[] = ( ! empty ( $Entry['Repeat'] ) ) ? "'M'" : "'E'";

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

      if ( empty ( $error ) ) {
        $sqlLog .= $sql . "<br />\n";
        //echo "SQL: $sql <br />\n";
        if ( ! dbi_query ( $sql ) ) {
          $error .= "<p>" . translate("Database error") . ": " . dbi_error () .
            "</p>\n";
          break;
        }
      }

      // log add/update
      activity_log ( $id, $login, $login,
        $updateMode ? $LOG_UPDATE : $LOG_CREATE, "Import from $ImportType" );

      if ( $single_user == "Y" ) {
        $participants[0] = $single_user_login;
      }

      // Now add to webcal_import_data
      if ( ! $updateMode ) {
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
      if ( ! $updateMode ) {
        $status = ( $login == "__public__" ) ? 'W' : 'A';
        if ( empty ( $cat_id ) ) $cat_id = 'NULL';
        $sql = "INSERT INTO webcal_entry_user " .
          "( cal_id, cal_login, cal_status, cal_category ) VALUES ( $id, '" .
          $participants[0] . "', '$status', $cat_id )";
        $sqlLog .= $sql . "<br />\n";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
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
        //while ( list($k,$v) = each ( $Entry['Repeat'] ) ) {
        //  echo "$k: $v <br />\n";
        //}
        $rpt_type = RepeatType($Entry['Repeat']['Interval']);
        $freq = ( ! empty ( $Entry['Repeat']['Frequency'] ) ? 
          $Entry['Repeat']['Frequency'] : 1 );
        if ( ! empty ( $Entry['Repeat']['EndTime'] ) ) {
          $REND   = localtime($Entry['Repeat']['EndTime']);
          $end = sprintf ( "%04d%02d%02d",$REND[5] + 1900,$REND[4] + 1,$REND[3]);
        } else {
          $end = 'NULL';
        }
        $days = (! empty ($Entry['Repeat']['RepeatDays'])) ? 
          "'".$Entry['Repeat']['RepeatDays']."'" : 'NULL';
        $sql = "INSERT INTO webcal_entry_repeats ( cal_id, " .
          "cal_type, cal_end, cal_days, cal_frequency ) VALUES " .
          "( $id, '$rpt_type', $end, $days, $freq )";
        $sqlLog .= $sql . "<br />\n";
        if ( ! dbi_query ( $sql ) ) {
          $error = "Unable to add to webcal_entry_repeats: ".
            dbi_error ()."<br /><br />\n<b>SQL:</b> $sql";
          break;
        }

        // Repeating Exceptions...
        if ( ! empty ( $Entry['Repeat']['Exceptions'] ) ) {
          foreach ($Entry['Repeat']['Exceptions'] as $ex_date) {
            $ex_date = date("Ymd",$ex_date);
            $sql = "INSERT INTO webcal_entry_repeats_not ( cal_id, cal_date ) VALUES ( $id, $ex_date )";
            $sqlLog .= $sql . "<br />\n";
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
        dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
      }
      if ( ! empty ( $Entry['AlarmSet'] ) && $Entry['AlarmSet'] == 1 ) {
        $RM = $Entry['AlarmAdvanceAmount'];
        if ($Entry['AlarmAdvanceType'] == 1){ $RM = $RM * 60; }
        if ($Entry['AlarmAdvanceType'] == 2){ $RM = $RM * 60 * 24; }
        $sql = "INSERT INTO webcal_site_extras ( cal_id, " .
          "cal_name, cal_type, cal_remind, cal_data ) VALUES " .
          "( $id, 'Reminder', 7, 1, $RM )";
        $sqlLog .= $sql . "<br />\n";
        if ( ! dbi_query ( $sql ) ) {
          $error = translate("Database error") . ": " . dbi_error ();
        }
      }
    }

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
        $time = display_time ( $Entry['StartHour'].$Entry['StartMinute']."00" ) .
          " - " . display_time ( $Entry['EndHour'].$Entry['EndMinute']."00" );
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
      echo "<b><h2>" .
        translate("Event Imported") . ":</h2></b>\n";
      $count_suc++;
      if ( $Entry['Duration'] > 0 ) {
        $time = display_time ( $Entry['StartHour'].$Entry['StartMinute']."00" ) .
          " - " . display_time ( $Entry['EndHour'].$Entry['EndMinute']."00" );
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
      echo ")<br />\n";
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

  //echo "<b>SQL:</b><br />\n$sqlLog\n";
}

// Convert interval to webcal repeat type
function RepeatType ($type) {
  $Repeat = array (0,'daily','weekly','monthlyByDay','monthlyByDate','yearly','monthlyByDayR');
  return $Repeat[$type];
}
?>

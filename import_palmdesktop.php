<?php

// Parse the datebook file and return the data hash.
//
function parse_palmdesktop ($file, $exc_private = 1) {
  $file = EscapeShellArg($file);
  $exc_private = EscapeShellArg($exc_private);
  exec ("perl tools/palm_datebook.pl $file $exc_private", $Entries);
  $data = array ();
  while ( list( $line_num, $line ) = each( $Entries ) ) {
    $data[] = ParseLine($line);
  }
  return $data;
}

// Delete all Palm Events for $login to clear any events deleted in the palm
// Return 1 if success
function delete_palm_events($login) {
  $res = dbi_query ( "SELECT cal_id FROM webcal_import_data " .
    "WHERE cal_login = '$login' AND cal_import_type = 'palm'" );
  if ( $res ) {
     while ( $row = dbi_fetch_row ( $res ) ) {
       dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_entry_repeats_not WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_entry_log WHERE cal_entry_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_reminder_log WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_import_data WHERE cal_id = $row[0]" );
       dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $row[0]" );
     }
  }
  dbi_free_result ( $res );
  return 1;
}

function ParseLine($line){
  list(
    $Entry['RecordID'],
    $Entry['StartTime'],
    $Entry['EndTime'],
    $Entry['Summary'],
    $Entry['Duration'],
    $Entry['Description'],
    $Entry['Untimed'],
    $Entry['Private'],
    $Entry['Category'],
    $Entry['AlarmSet'],
    $Entry['AlarmAdvanceAmount'],
    $Entry['AlarmAdvanceType'],
    $Entry['Repeat']['Interval'],
    $Entry['Repeat']['Frequency'],
    $Entry['Repeat']['EndTime'],
    $Exceptions,
    $Entry['Repeat']['RepeatDays'],
    $WeekNum,
      ) = explode("|", $line);

  if ($Exceptions) $Entry['Repeat']['Exceptions'] = explode(":",$Exceptions);
  if (($WeekNum == '5') && ($Entry['Repeat']['Interval'] == '3')) $Entry['Repeat']['Interval'] = '6';
  return $Entry;
}
?>

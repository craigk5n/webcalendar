<?php
/* $Id: import_palmdesktop.php,v 1.18.2.2 2007/08/06 02:28:30 cknudsen Exp $ */

/* Parse the datebook file.
 *
 * @return the data hash.
 */
function parse_palmdesktop ( $file, $exc_private = 1 ) {
  $file = EscapeShellArg ( $file );
  $exc_private = EscapeShellArg ( $exc_private );
  exec ( 'perl tools/palm_datebook.pl ' . "$file $exc_private", $Entries );
  $data = array ();
  while ( list ( $line_num, $line ) = each ( $Entries ) ) {
    $data[] = ParseLine ( $line );
  }
  return $data;
}

/* Delete all Palm Events for $login to clear any events deleted in the palm.
 *
 * @return 1 if successful.
 */
function delete_palm_events ( $login ) {
  $res = dbi_execute ( 'SELECT cal_id FROM webcal_import_data
    WHERE cal_login = ? AND cal_import_type = ?', array ( $login, 'palm' ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      dbi_execute ( 'DELETE FROM webcal_blob WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_entry_log WHERE cal_entry_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_entry_repeats WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_entry_repeats_not WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_import_data WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_reminders WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_site_extras WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_entry_user WHERE cal_id = ?',
        array ( $row[0] ) );
      dbi_execute ( 'DELETE FROM webcal_entry WHERE cal_id = ?',
        array ( $row[0] ) );
    }
  }
  dbi_free_result ( $res );
  return 1;
}

function ParseLine ( $line ) {
  global $calUser;

  list ( // .
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
    ) = explode ( '|', $line );
  // .
  // Adjust times to users Timezone if not Untimed.
  if ( isset ( $Entry['Untimed'] ) && $Entry['Untimed'] == 0 ) {
    $Entry['StartTime'] -= date ( 'Z', $Entry['StartTime'] );
    $Entry['EndTime'] -= date ( 'Z', $Entry['EndTime'] );
  }

  if ( $Exceptions )
    $Entry['Repeat']['Exceptions'] = explode ( ': ', $Exceptions );

  if ( ( $WeekNum == '5' ) && ( $Entry['Repeat']['Interval'] == '3' ) )
    $Entry['Repeat']['Interval'] = '6';

  return $Entry;
}

?>

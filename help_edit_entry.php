<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );

ob_start ();

echo $helpListStr . '
    <h2>' . $translations['Help'] . ': '
 . translate ( 'Adding/Editing Calendar Entries' ) . '</h2>';

$tmp_arr = array (
  $translations['Brief Description'] => $translations['brief-description-help'],
  $translations['Full Description'] => $translations['full-description-help'],
  $translations['Date'] => $translations['date-help'],
  $translations['Time'] => $translations['time-help'],
  );

if ( $TIMED_EVT_LEN != 'E' )
  $tmp_arr[ $translations['Duration'] ] = $translations['duration-help'];
else
  $tmp_arr[ $translations['End Time'] ] = $translations['end-time-help'];

if ( $DISABLE_PRIORITY_FIELD != 'Y' )
  $tmp_arr[ $translations['Priority'] ] = $translations['priority-help'];

if ( $DISABLE_ACCESS_FIELD != 'Y' )
  $tmp_arr[ $translations['Access'] ] = $translations['access-help'];

if ( $single_user == 'N' &&
  ( $is_admin || $DISABLE_PARTICIPANTS_FIELD != 'Y' ) )
  $tmp_arr[ $translations['Participants'] ] = $translations['participants-help'];

if ( $DISABLE_REPEATING_FIELD != 'Y' ) {
  $tmp_arr[ translate ( 'Repeat Type' ) ] = $translations['repeat-type-help'];
  list_help ( $tmp_arr );
  echo '
      <p><a class="underline" href="docs/WebCalendar-UserManual.html#repeat">'
   . translate ( 'For More Information...' ) . '</a></p>';
  $tmp_arr = array (
    translate ( 'Repeat End Date' ) => $translations['repeat-end-date-help'],
    translate ( 'Repeat Day' ) => translate ( 'repeat-day-help' ),
    $translations['Frequency'] => $translations['repeat-frequency-help'],
    );
  list_help ( $tmp_arr );
}

ob_end_flush ();

echo print_trailer ( false, true, true );

?>

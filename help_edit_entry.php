<?php
require_once 'includes/init.php';
require_once 'includes/help_list.php';

print_header ( [], '', '', true );
echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': '
 . translate ( 'Adding/Editing Calendar Entries' ) . '</h2>';

$tmp_arr = [
  translate ( 'Brief Description' ) => translate ( 'brief-description-help' ),
  translate ( 'Full Description' ) => translate ( 'full-description-help' ),
  translate ( 'Date' ) => translate ( 'date-help' ),
  translate ( 'Time' ) => translate ( 'time-help' )];

if ( $TIMED_EVT_LEN != 'E' )
  $tmp_arr[ translate ( 'Duration' ) ] = translate ( 'duration-help' );
else
  $tmp_arr[ translate ( 'End Time' ) ] = translate ( 'end-time-help' );

if ( $DISABLE_PRIORITY_FIELD != 'Y' )
  $tmp_arr[ translate ( 'Priority' ) ] = translate ( 'priority-help' );

if ( $DISABLE_ACCESS_FIELD != 'Y' )
  $tmp_arr[ translate ( 'Access' ) ] = translate ( 'access-help' );

if ( $single_user == 'N' &&
  ( $is_admin || $DISABLE_PARTICIPANTS_FIELD != 'Y' ) )
  $tmp_arr[ translate ( 'Participants' ) ] = translate ( 'participants-help' );

if ( $DISABLE_REPEATING_FIELD != 'Y' ) {
  $tmp_arr[ translate ( 'Repeat Type' )  ] = translate ( 'repeat-type-help' );
  list_help ( $tmp_arr );
  echo '
      <p><a class="underline" href="docs/WebCalendar-UserManual.html#repeat">'
   . translate ( 'For More Information...' ) . '</a></p>';
  $tmp_arr = [
    translate ( 'Repeat End Date' ) => translate ( 'repeat-end-date-help' ),
    translate ( 'Repeat Day' ) => translate ( 'repeat-day-help' ),
    translate ( 'Frequency' ) => translate ( 'repeat-frequency-help' )];
  list_help ( $tmp_arr );
}
echo print_trailer ( false, true, true );

?>

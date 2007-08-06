<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

build_header ( '', '', '', 29 );

ob_start ();

echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': '
 . translate ( 'Adding/Editing Calendar Entries' ) . '</h2>';

$tmp_arr = array (
  translate ( 'Brief Description' ) => translate ( 'brief-description-help' ),
  translate ( 'Full Description' ) => translate ( 'full-description-help' ),
  translate ( 'Date' ) => translate ( 'date-help' ),
  translate ( 'Time' ) => translate ( 'time-help' ),
  );

if ( getPref ( 'TIMED_EVT_LEN' ) != 'E' )
  $tmp_arr[ translate ( 'Duration' ) ] = translate ( 'duration-help' );
else
  $tmp_arr[ translate ( 'End Time' ) ] = translate ( 'end-time-help' );

if ( ! getPref ( 'DISABLE_PRIORITY_FIELD' ) )
  $tmp_arr[ translate ( 'Priority' ) ] = translate ( 'priority-help' );

if ( ! getPref ( 'DISABLE_ACCESS_FIELD' ) )
  $tmp_arr[ translate ( 'Access' ) ] = translate ( 'access-help' );

if ( ! _WC_SINGLE_USER &&
  ( $WC->isAdmin() || ! getPref ( 'DISABLE_PARTICIPANTS_FIELD' ) ) )
  $tmp_arr[ translate ( 'Participants' ) ] = translate ( 'participants-help' );

if ( ! getPref ( 'DISABLE_REPEATING_FIELD' ) ) {
  $tmp_arr[ translate ( 'Repeat Type' )  ] = translate ( 'repeat-type-help' );
  list_help ( $tmp_arr );
  echo '
      <p><a class="underline" href="docs/WebCalendar-UserManual.html#repeat">'
   . translate ( 'For More Information...' ) . '</a></p>';
  $tmp_arr = array (
    translate ( 'Repeat End Date' ) => translate ( 'repeat-end-date-help' ),
    translate ( 'Repeat Day' ) => translate ( 'repeat-day-help' ),
    translate ( 'Frequency' ) => translate ( 'repeat-frequency-help' ),
    );
  list_help ( $tmp_arr );
}

ob_end_flush ();

echo print_trailer ( false, true, true );

?>

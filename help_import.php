<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

ob_start();
print_header( '', '', '', true );

echo $helpListStr . '
    <h2>' . translate( 'Help Import' ) . '</h2>
    <h3>' . translate ( 'Palm Desktop' ) . '</h3>
    <p>' . translate ( 'to import entries from Palm' ) . '</p>
    <p>' . translate ( 'these entries not imported' ) . '</p>
    <ul>
      <li>' . translate ( 'Entries older than current date' ) . '</li>
      <li>' . translate ( 'Entries created in Palm' ) . '</li>
    </ul>
    <p>' . translate ( 'things imported from Palm' ) . '</p>
    <h3>' . translate ( 'vCal' ) . '</h3>
    <p>' . translate ( 'This will import (.vcs)' )
 . '</p>
    <p>' . translate ( 'These formats have been tested' ) . '</p>
    <ul>
      <li>Palm Desktop 4</li>
      <li>Lotus Organizer 6</li>
      <li>Microsoft Outlook 2002</li>
    </ul>
    <h3>iCalendar</h3>
    <p>' . translate ( 'This will import (.ics)' )
/* These have all been combined into the above phrase.
 . translate ( 'Enabling' ) . ' <b>' . translate ( 'Overwrite Prior Import' )
 . '</b>, ' . translate( 'causes events imported previously' )
*/
 . '</p>'
 . print_trailer( false, true, true );

ob_end_flush();

?>

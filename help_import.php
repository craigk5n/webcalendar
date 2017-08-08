<?php // $Id: help_import.php,v 1.27 2009/11/22 16:47:45 bbannon Exp $
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );
echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Import' ) . '</h2>
    <h3>' . translate ( 'Palm Desktop' ) . '</h3>
    <p>' . translate ( 'allow you to import entries from the Palm...' ) . '</p>
    <p>' . translate ( 'The following entries will not be imported' ) . '</p>
    <ul>
      <li>' . translate ( 'Entries older than the current date' ) . '</li>
      <li>' . translate ( 'Entries created in the Palm Desktop...' ) . '</li>
    </ul>
    <p>' . translate ( 'Anything imported from Palm...' ) . '</p>
    <h3>' . translate ( 'vCal' ) . '</h3>
    <p>' . translate ( 'This form will import vCalendar (.vcs) 1.0 events.' )
 . '</p>
    <p>' . translate ( 'The following formats have been tested' ) . '</p>
    <ul>
      <li>Palm Desktop 4</li>
      <li>Lotus Organizer 6</li>
      <li>Microsoft Outlook 2002</li>
    </ul>
    <h3>iCalendar</h3>
    <p>' . translate ( 'This form will import iCalendar (.ics) events.' ) . ' '
 . translate ( 'Enabling' ) . ' <b>' . translate ( 'Overwrite Prior Import' )
 . '</b>, ' . translate ( 'will cause events imported previously...' ) . '</p>';

echo print_trailer ( false, true, true );

?>

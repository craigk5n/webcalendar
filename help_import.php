<?php
/* $Id: help_import.php,v 1.25.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

print_header ( '', '', '', true );

ob_start ();

echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Import' ) . '</h2>
    <h3>' . translate ( 'Palm Desktop' ) . '</h3>
    <p>' .
translate ( 'This form will allow you to import entries from the Palm Desktop Datebook.' )
 . '<br />' .
translate ( 'It should be located in your Palm directory in <tt>datebook/datebook.dat</tt> in a subdirectory named by your username.' )
 . '</p>
    <p>' . translate ( 'The following entries will not be imported' ) . ':</p>
    <ul>
      <li>' . translate ( 'Entries older than the current date' ) . '</li>
      <li>' .
translate ( 'Entries created in the Palm Desktop...' )
 . '</li>
    </ul>
    <p>' .
translate ( 'Anything imported from Palm will be overwritten during the next import (unless the event date has passed).' )
 . translate ( 'Therefore, updates should be made in the Palm Desktop.' )
 . '</p>
    <h3>' . translate ( 'vCal' ) . '</h3>
    <p>' . translate ( 'This form will import vCalendar (.vcs) 1.0 events' )
 . '.</p>
    <p>' . translate ( 'The following formats have been tested' ) . ':</p>
    <ul>
      <li>Palm Desktop 4</li>
      <li>Lotus Organizer 6</li>
      <li>Microsoft Outlook 2002</li>
    </ul>
    <h3>iCalendar</h3>
    <p>' . translate ( 'This form will import iCalendar (.ics) events' ) . '. '
 . translate ( 'Enabling' ) . ' <b>' . translate ( 'Overwrite Prior Import' )
 . '</b>, ' .
translate ( 'will cause events imported previously, that used the same UID as an event from the new import file, to be marked as deleted. This should allow an updated iCalendar file to be imported without creating duplicates.' )
 . '</p>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
